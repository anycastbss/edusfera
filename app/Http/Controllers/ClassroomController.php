<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\ClassroomChatMessage;
use App\Models\ClassroomFile;
use App\Models\ClassroomNote;
use App\Models\ClassroomSession;
use App\Models\DiagnosticAttempt;
use App\Models\HomeworkAssignment;
use App\Models\Lesson;
use App\Models\ProgressSnapshot;
use App\Models\SkillGap;
use App\Models\StudentGoal;
use App\Services\Classroom\AiService;
use App\Services\Classroom\LiveKitService;
use App\Services\ClassroomService;
use App\Services\Payment\PaymentService;
use App\Support\Formatters;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ClassroomController extends Controller
{
    public function __construct(private readonly ClassroomService $classroomService) {}

    public function demoRoom(Request $request): View
    {
        $guestId = (string) $request->session()->get('demo_guest_id');
        if ($guestId === '') {
            $guestId = 'guest_demo_' . Str::random(8);
            $request->session()->put('demo_guest_id', $guestId);
        }

        $demoAiHistory = [
            [
                'role' => 'assistant',
                'text' => "Здравствуйте! Я ваш ИИ-помощник Edusfera PRO.\n\nВот пример экспресс-подготовки материалов к уроку:\n\n**Тест по теме «Квадратные уравнения» (формат ЦТ / ЦЭ):**\n\n1. **Задание 1:** Найдите сумму корней уравнения \$x^2 - 7x + 12 = 0\$.\n   - А) -7   Б) 7   В) 12   Г) -12\n   *Ответ:* Б) 7 (по теореме Виета \$x_1 + x_2 = 7\$).\n\n2. **Задание 2:** При каком значении \$c\$ уравнение \$2x^2 + 4x + c = 0\$ имеет ровно один корень?\n   *Решение:* \$D = 16 - 8c = 0 \\Rightarrow c = 2\$.\n   *Ответ:* \$c = 2\$.\n\n3. **Задание 3:** Решите уравнение \$x^2 - 5|x| + 6 = 0\$.\n   *Ответ:* \$x \\in \\{-3, -2, 2, 3\\}\$.\n\nНажмите кнопку ниже, чтобы сгенерировать новые задачи по геометрии или составить вариант теста 👇",
            ],
        ];

        $classroomConfig = [
            'isDemo' => true,
            'isPro' => true,
            'guestSessionId' => $guestId,
            'lessonId' => 'demo',
            'subject' => 'Песочница PRO',
            'topic' => 'Тест-драйв виртуального класса Edusfera',
            'userName' => 'Вы (Преподаватель)',
            'partnerName' => 'Ученик (Тестовый режим)',
            'userRole' => 'tutor',
            'userId' => $guestId,
            'isBoardLocked' => false,
            'studentInviteUrl' => route('register', ['role' => 'tutor']),
            'csrfToken' => csrf_token(),
            'liveKitConfigured' => false,
            'liveKitToken' => null,
            'liveKitWsUrl' => null,
            'jitsiDomain' => 'meet.jit.si',
            'jitsiRoomName' => 'edusfera_demo_' . substr(md5($guestId), 0, 8),
            'mediaServerUrl' => null,
            'iceServers' => [],
            'maxLessonDurationMinutes' => 60,
            'aiChatHistory' => $demoAiHistory,
            'initialElements' => $this->getDemoWhiteboardElements(),
            'exitUrl' => route('register', ['role' => 'tutor']),
        ];

        return view('classroom.show', [
            'lesson' => (object) [
                'id' => 'demo',
                'subject' => 'Песочница PRO',
                'topic' => 'Тест-драйв виртуального класса Edusfera',
                'tutor' => (object) ['name' => 'Вы (Преподаватель)'],
                'student' => (object) ['name' => 'Ученик (Тестовый режим)'],
            ],
            'isDemo' => true,
            'classroomConfig' => $classroomConfig,
        ]);
    }

    public function demoAiChat(Request $request, AiService $aiService): JsonResponse
    {
        $validated = $request->validate([
            'message' => ['nullable', 'string', 'max:2000', 'required_without:prompt'],
            'prompt' => ['nullable', 'string', 'max:2000'],
            'history' => ['nullable', 'array', 'max:30'],
        ]);

        $messageText = trim((string) ($validated['message'] ?? $validated['prompt'] ?? ''));
        if ($messageText === '') {
            return response()->json(['error' => 'Message is required'], 422);
        }

        $lower = mb_strtolower($messageText);

        if (str_contains($lower, 'геометр') || str_contains($lower, '5 задач')) {
            $reply = "📐 **5 задач по геометрии (Планиметрия, формат ЦТ / ЦЭ):**\n\n" .
                "1. **Задача 1 (Прямоугольный треугольник):**\n" .
                "   В прямоугольном треугольнике катеты равны 6 см и 8 см. Найдите высоту, проведенную к гипотенузе.\n" .
                "   *Решение:* Гипотенуза \$c = \\sqrt{6^2 + 8^2} = 10\$ см. Площадь \$S = \\frac{1}{2} \\cdot 6 \\cdot 8 = 24\$. Высота \$h = \\frac{2S}{c} = \\frac{48}{10} = 4{,}8\$ см.\n" .
                "   *Ответ:* **4,8 см**.\n\n" .
                "2. **Задача 2 (Теорема синусов):**\n" .
                "   В треугольнике \$ABC\$ угол \$A = 30^\\circ\$, сторона \$BC = 7\$ см. Найдите радиус описанной около треугольника окружности.\n" .
                "   *Решение:* По теореме синусов \$2R = \\frac{a}{\\sin A} = \\frac{7}{\\sin 30^\\circ} = 14 \\Rightarrow R = 7\$ см.\n" .
                "   *Ответ:* **7 см**.\n\n" .
                "3. **Задача 3 (Площадь параллелограмма):**\n" .
                "   Стороны параллелограмма равны 5 см и 12 см, а угол между ними равен \$150^\\circ\$. Найдите площадь параллелограмма.\n" .
                "   *Решение:* \$S = a \\cdot b \\cdot \\sin 150^\\circ = 5 \\cdot 12 \\cdot \\frac{1}{2} = 30\$ см².\n" .
                "   *Ответ:* **30 см²**.\n\n" .
                "4. **Задача 4 (Подобие треугольников):**\n" .
                "   Площади двух подобных треугольников относятся как \$16 : 25\$. Периметр меньшего треугольника равен 24 см. Найдите периметр большего.\n" .
                "   *Решение:* Коэффициент подобия \$k = \\sqrt{\\frac{16}{25}} = \\frac{4}{5}\$. \$P_2 = \\frac{24 \\cdot 5}{4} = 30\$ см.\n" .
                "   *Ответ:* **30 см**.\n\n" .
                "5. **Задача 5 (Окружность и касательная):**\n" .
                "   Из точки \$M\$ к окружности проведены касательная \$MA = 6\$ см и секущая, внешняя часть которой \$MB = 4\$ см. Найдите длину всей секущей \$MC\$.\n" .
                "   *Решение:* По теореме о касательной и секущей \$MA^2 = MB \\cdot MC \\Rightarrow 36 = 4 \\cdot MC \\Rightarrow MC = 9\$ см.\n" .
                "   *Ответ:* **9 см**.";

            return response()->json(['reply' => $reply]);
        }

        if (str_contains($lower, 'тест') && (str_contains($lower, 'цт') || str_contains($lower, 'вариант'))) {
            $reply = "📝 **Экспресс-вариант теста по математике (стандарт ЦТ / ЦЭ):**\n\n" .
                "**Часть А (с выбором ответа):**\n\n" .
                "**А1.** Укажите значение выражения: \$\\sqrt{48} - 2\\sqrt{3} + \\sqrt{12}\$.\n" .
                "   1) \$2\\sqrt{3}\$   2) \$4\\sqrt{3}\$   3) \$5\\sqrt{3}\$   4) 6   5) 0\n" .
                "   *Пояснение:* \$\\sqrt{48} = 4\\sqrt{3}\$, \$\\sqrt{12} = 2\\sqrt{3}\$. Итого: \$4\\sqrt{3} - 2\\sqrt{3} + 2\\sqrt{3} = 4\\sqrt{3}\$.\n" .
                "   *Ответ:* **2**.\n\n" .
                "**А2.** Решите неравенство: \$\\frac{x - 3}{x + 5} \\le 0\$.\n" .
                "   1) \$(-5; 3]\$   2) \$[-5; 3]\$   3) \$(-\\infty; -5) \\cup [3; +\\infty)\$   4) \$(-5; 3)\$\n" .
                "   *Пояснение:* Метод интервалов, точка \$x = -5\$ выколота (знаменатель), \$x = 3\$ закрашена.\n" .
                "   *Ответ:* **1**.\n\n" .
                "**А3.** Найдите область определения функции \$y = \\log_3(12 - 4x)\$:\n" .
                "   1) \$x > 3\$   2) \$x < 3\$   3) \$x \\ge 3\$   4) \$x \\le 3\$\n" .
                "   *Пояснение:* \$12 - 4x > 0 \\Rightarrow 4x < 12 \\Rightarrow x < 3\$.\n" .
                "   *Ответ:* **2**.\n\n" .
                "**Часть Б (краткий ответ целым числом):**\n\n" .
                "**Б1.** Найдите произведение корней уравнения \$x^3 - 4x^2 - 5x = 0\$.\n" .
                "   *Решение:* \$x(x^2 - 4x - 5) = 0 \\Rightarrow x_1 = 0, x_2 = 5, x_3 = -1\$. Произведение равно \$0 \\cdot 5 \\cdot (-1) = 0\$.\n" .
                "   *Ответ:* **0**.\n\n" .
                "**Б2.** В арифметической прогрессии \$a_1 = 3\$, разность \$d = 4\$. Найдите номер члена прогрессии, равного 47.\n" .
                "   *Решение:* \$a_n = a_1 + (n - 1)d \\Rightarrow 47 = 3 + 4(n - 1) \\Rightarrow 44 = 4(n - 1) \\Rightarrow n = 12\$.\n" .
                "   *Ответ:* **12**.";

            return response()->json(['reply' => $reply]);
        }

        if (str_contains($lower, 'план') || str_contains($lower, '45 минут')) {
            $reply = "💡 **Поурочный план занятия (45 минут) для онлайн-класса:**\n\n" .
                "**Тема:** Квадратные уравнения и теорема Виета\n**Оборудование:** Интерактивная доска Excalidraw, ИИ-ассистент Edusfera\n\n" .
                "⏱️ **1. Организационный момент и разминка (0–5 мин):**\n" .
                "- Подключение ученика, проверка связи и звука.\n" .
                "- Устный счет: нахождение корней через теорему Виета в простых случаях.\n\n" .
                "⏱️ **2. Актуализация теории на доске (5–17 мин):**\n" .
                "- Запись общего вида \$ax^2 + bx + c = 0\$ и формулы дискриминанта.\n" .
                "- Вывод теоремы Виета: \$x_1 + x_2 = -\\frac{b}{a}\$, \$x_1 \\cdot x_2 = \\frac{c}{a}\$.\n" .
                "- Совместное решение 2 примеров на интерактивной доске.\n\n" .
                "⏱️ **3. Практикум и самостоятельное решение (17–37 мин):**\n" .
                "- Ученик решает задачу маркером прямо на доске.\n" .
                "- Разбор ловушек ЦТ (случай \$a = 0\$, случай \$D < 0\$, знаки корней).\n\n" .
                "⏱️ **4. Экспресс-тест и итоги (37–45 мин):**\n" .
                "- Блиц-тест из 3 заданий через ИИ-помощника.\n" .
                "- Домашнее задание и фиксация прогресса в кабинете.";

            return response()->json(['reply' => $reply]);
        }

        try {
            $roomId = 'demo_' . ($request->session()->get('demo_guest_id') ?? 'sandbox');
            $history = (array) ($validated['history'] ?? []);
            $result = $aiService->chat($messageText, $roomId, null, null, $history);
            if (! empty($result['reply'])) {
                return response()->json(['reply' => $result['reply']]);
            }
        } catch (\Throwable $e) {
            Log::info('Demo AI fallback used: ' . $e->getMessage());
        }

        return response()->json([
            'reply' => "Готово! По вашему запросу «{$messageText}»:\n\nМатериал скомпонован для интерактивной работы на доске. В полной версии преподаватель может мгновенно переносить формулы и задачи из чата прямо на полотно доски в один клик!"
        ]);
    }

    private function getDemoWhiteboardElements(): array
    {
        return [
            [
                'id' => 'el_title',
                'type' => 'text',
                'x' => 50,
                'y' => 50,
                'width' => 740,
                'height' => 40,
                'angle' => 0,
                'strokeColor' => '#7D39EB',
                'backgroundColor' => 'transparent',
                'fillStyle' => 'solid',
                'strokeWidth' => 2,
                'strokeStyle' => 'solid',
                'roughness' => 0,
                'opacity' => 100,
                'groupIds' => [],
                'roundness' => null,
                'seed' => 1001,
                'version' => 2,
                'versionNonce' => 2,
                'isDeleted' => false,
                'text' => '🎯 Тема: Квадратные уравнения и планиметрия',
                'fontSize' => 22,
                'fontFamily' => 1,
                'textAlign' => 'left',
                'verticalAlign' => 'top',
                'baseline' => 18,
                'containerId' => null,
                'originalText' => '🎯 Тема: Квадратные уравнения и планиметрия',
                'lineHeight' => 1.25,
            ],
            [
                'id' => 'el_formulas_bg',
                'type' => 'rectangle',
                'x' => 50,
                'y' => 100,
                'width' => 360,
                'height' => 230,
                'angle' => 0,
                'strokeColor' => '#7D39EB',
                'backgroundColor' => '#F5F3FF',
                'fillStyle' => 'solid',
                'strokeWidth' => 2,
                'strokeStyle' => 'solid',
                'roughness' => 0,
                'opacity' => 100,
                'groupIds' => [],
                'roundness' => ['type' => 3],
                'seed' => 1002,
                'version' => 2,
                'versionNonce' => 2,
                'isDeleted' => false,
            ],
            [
                'id' => 'el_formulas_text',
                'type' => 'text',
                'x' => 70,
                'y' => 118,
                'width' => 320,
                'height' => 190,
                'angle' => 0,
                'strokeColor' => '#1E1B4B',
                'backgroundColor' => 'transparent',
                'fillStyle' => 'solid',
                'strokeWidth' => 1,
                'strokeStyle' => 'solid',
                'roughness' => 0,
                'opacity' => 100,
                'groupIds' => [],
                'roundness' => null,
                'seed' => 1003,
                'version' => 2,
                'versionNonce' => 2,
                'isDeleted' => false,
                'text' => "1. Общий вид уравнения:\n   a·x² + b·x + c = 0 (a ≠ 0)\n\n2. Дискриминант:\n   D = b² - 4·a·c\n\n3. Формула корней:\n   x₁,₂ = (-b ± √D) / (2·a)",
                'fontSize' => 16,
                'fontFamily' => 1,
                'textAlign' => 'left',
                'verticalAlign' => 'top',
                'baseline' => 14,
                'containerId' => null,
                'originalText' => "1. Общий вид уравнения:\n   a·x² + b·x + c = 0 (a ≠ 0)\n\n2. Дискриминант:\n   D = b² - 4·a·c\n\n3. Формула корней:\n   x₁,₂ = (-b ± √D) / (2·a)",
                'lineHeight' => 1.35,
            ],
            [
                'id' => 'el_geom_bg',
                'type' => 'rectangle',
                'x' => 430,
                'y' => 100,
                'width' => 360,
                'height' => 230,
                'angle' => 0,
                'strokeColor' => '#0284C7',
                'backgroundColor' => '#F0F9FF',
                'fillStyle' => 'solid',
                'strokeWidth' => 2,
                'strokeStyle' => 'solid',
                'roughness' => 0,
                'opacity' => 100,
                'groupIds' => [],
                'roundness' => ['type' => 3],
                'seed' => 1004,
                'version' => 2,
                'versionNonce' => 2,
                'isDeleted' => false,
            ],
            [
                'id' => 'el_geom_text_title',
                'type' => 'text',
                'x' => 450,
                'y' => 115,
                'width' => 320,
                'height' => 26,
                'angle' => 0,
                'strokeColor' => '#0369A1',
                'backgroundColor' => 'transparent',
                'fillStyle' => 'solid',
                'strokeWidth' => 1,
                'strokeStyle' => 'solid',
                'roughness' => 0,
                'opacity' => 100,
                'groupIds' => [],
                'roundness' => null,
                'seed' => 1005,
                'version' => 2,
                'versionNonce' => 2,
                'isDeleted' => false,
                'text' => "Теорема Пифагора: a² + b² = c²",
                'fontSize' => 16,
                'fontFamily' => 1,
                'textAlign' => 'left',
                'verticalAlign' => 'top',
                'baseline' => 14,
                'containerId' => null,
                'originalText' => "Теорема Пифагора: a² + b² = c²",
                'lineHeight' => 1.25,
            ],
            [
                'id' => 'el_triangle_base',
                'type' => 'line',
                'x' => 470,
                'y' => 280,
                'width' => 150,
                'height' => 0,
                'angle' => 0,
                'strokeColor' => '#0284C7',
                'backgroundColor' => 'transparent',
                'fillStyle' => 'solid',
                'strokeWidth' => 2,
                'strokeStyle' => 'solid',
                'roughness' => 0,
                'opacity' => 100,
                'groupIds' => [],
                'roundness' => null,
                'seed' => 1006,
                'version' => 2,
                'versionNonce' => 2,
                'isDeleted' => false,
                'points' => [[0, 0], [150, 0]],
                'lastCommittedPoint' => null,
                'startBinding' => null,
                'endBinding' => null,
                'startArrowhead' => null,
                'endArrowhead' => null,
            ],
            [
                'id' => 'el_triangle_height',
                'type' => 'line',
                'x' => 620,
                'y' => 175,
                'width' => 0,
                'height' => 105,
                'angle' => 0,
                'strokeColor' => '#0284C7',
                'backgroundColor' => 'transparent',
                'fillStyle' => 'solid',
                'strokeWidth' => 2,
                'strokeStyle' => 'solid',
                'roughness' => 0,
                'opacity' => 100,
                'groupIds' => [],
                'roundness' => null,
                'seed' => 1007,
                'version' => 2,
                'versionNonce' => 2,
                'isDeleted' => false,
                'points' => [[0, 0], [0, 105]],
                'lastCommittedPoint' => null,
                'startBinding' => null,
                'endBinding' => null,
                'startArrowhead' => null,
                'endArrowhead' => null,
            ],
            [
                'id' => 'el_triangle_hypo',
                'type' => 'line',
                'x' => 470,
                'y' => 280,
                'width' => 150,
                'height' => 105,
                'angle' => 0,
                'strokeColor' => '#0284C7',
                'backgroundColor' => 'transparent',
                'fillStyle' => 'solid',
                'strokeWidth' => 2,
                'strokeStyle' => 'solid',
                'roughness' => 0,
                'opacity' => 100,
                'groupIds' => [],
                'roundness' => null,
                'seed' => 1008,
                'version' => 2,
                'versionNonce' => 2,
                'isDeleted' => false,
                'points' => [[0, 0], [150, -105]],
                'lastCommittedPoint' => null,
                'startBinding' => null,
                'endBinding' => null,
                'startArrowhead' => null,
                'endArrowhead' => null,
            ],
            [
                'id' => 'el_right_angle',
                'type' => 'rectangle',
                'x' => 605,
                'y' => 265,
                'width' => 15,
                'height' => 15,
                'angle' => 0,
                'strokeColor' => '#0284C7',
                'backgroundColor' => 'transparent',
                'fillStyle' => 'solid',
                'strokeWidth' => 1.5,
                'strokeStyle' => 'solid',
                'roughness' => 0,
                'opacity' => 100,
                'groupIds' => [],
                'roundness' => null,
                'seed' => 1009,
                'version' => 2,
                'versionNonce' => 2,
                'isDeleted' => false,
            ],
            [
                'id' => 'el_label_a',
                'type' => 'text',
                'x' => 530,
                'y' => 286,
                'width' => 45,
                'height' => 20,
                'angle' => 0,
                'strokeColor' => '#0369A1',
                'backgroundColor' => 'transparent',
                'fillStyle' => 'solid',
                'strokeWidth' => 1,
                'strokeStyle' => 'solid',
                'roughness' => 0,
                'opacity' => 100,
                'groupIds' => [],
                'roundness' => null,
                'seed' => 1010,
                'version' => 2,
                'versionNonce' => 2,
                'isDeleted' => false,
                'text' => 'a = 4',
                'fontSize' => 15,
                'fontFamily' => 1,
                'textAlign' => 'left',
                'verticalAlign' => 'top',
                'baseline' => 12,
                'containerId' => null,
                'originalText' => 'a = 4',
                'lineHeight' => 1.25,
            ],
            [
                'id' => 'el_label_b',
                'type' => 'text',
                'x' => 630,
                'y' => 218,
                'width' => 45,
                'height' => 20,
                'angle' => 0,
                'strokeColor' => '#0369A1',
                'backgroundColor' => 'transparent',
                'fillStyle' => 'solid',
                'strokeWidth' => 1,
                'strokeStyle' => 'solid',
                'roughness' => 0,
                'opacity' => 100,
                'groupIds' => [],
                'roundness' => null,
                'seed' => 1011,
                'version' => 2,
                'versionNonce' => 2,
                'isDeleted' => false,
                'text' => 'b = 3',
                'fontSize' => 15,
                'fontFamily' => 1,
                'textAlign' => 'left',
                'verticalAlign' => 'top',
                'baseline' => 12,
                'containerId' => null,
                'originalText' => 'b = 3',
                'lineHeight' => 1.25,
            ],
            [
                'id' => 'el_label_c',
                'type' => 'text',
                'x' => 520,
                'y' => 205,
                'width' => 60,
                'height' => 20,
                'angle' => 0,
                'strokeColor' => '#0369A1',
                'backgroundColor' => 'transparent',
                'fillStyle' => 'solid',
                'strokeWidth' => 1,
                'strokeStyle' => 'solid',
                'roughness' => 0,
                'opacity' => 100,
                'groupIds' => [],
                'roundness' => null,
                'seed' => 1012,
                'version' => 2,
                'versionNonce' => 2,
                'isDeleted' => false,
                'text' => 'c = 5 ?',
                'fontSize' => 16,
                'fontFamily' => 1,
                'textAlign' => 'left',
                'verticalAlign' => 'top',
                'baseline' => 12,
                'containerId' => null,
                'originalText' => 'c = 5 ?',
                'lineHeight' => 1.25,
            ],
            [
                'id' => 'el_hint_bg',
                'type' => 'rectangle',
                'x' => 50,
                'y' => 350,
                'width' => 740,
                'height' => 110,
                'angle' => 0,
                'strokeColor' => '#CA8A04',
                'backgroundColor' => '#FEF08A',
                'fillStyle' => 'solid',
                'strokeWidth' => 2,
                'strokeStyle' => 'solid',
                'roughness' => 0,
                'opacity' => 100,
                'groupIds' => [],
                'roundness' => ['type' => 3],
                'seed' => 1013,
                'version' => 2,
                'versionNonce' => 2,
                'isDeleted' => false,
            ],
            [
                'id' => 'el_hint_text',
                'type' => 'text',
                'x' => 70,
                'y' => 365,
                'width' => 700,
                'height' => 80,
                'angle' => 0,
                'strokeColor' => '#713F12',
                'backgroundColor' => 'transparent',
                'fillStyle' => 'solid',
                'strokeWidth' => 1,
                'strokeStyle' => 'solid',
                'roughness' => 0,
                'opacity' => 100,
                'groupIds' => [],
                'roundness' => null,
                'seed' => 1014,
                'version' => 2,
                'versionNonce' => 2,
                'isDeleted' => false,
                'text' => "✏️ ПОПРОБУЙТЕ СЕЙЧАС:\n1. Выберите инструмент «Карандаш» в верхней панели и решите пример рядом на доске.\n2. Справа в ИИ-помощнике нажмите «5 задач по геометрии» или «План урока» —\n   готовые материалы с формулами генерируются в 1 клик!",
                'fontSize' => 15,
                'fontFamily' => 1,
                'textAlign' => 'left',
                'verticalAlign' => 'top',
                'baseline' => 13,
                'containerId' => null,
                'originalText' => "✏️ ПОПРОБУЙТЕ СЕЙЧАС:\n1. Выберите инструмент «Карандаш» в верхней панели и решите пример рядом на доске.\n2. Справа в ИИ-помощнике нажмите «5 задач по геометрии» или «План урока» —\n   готовые материалы с формулами генерируются в 1 клик!",
                'lineHeight' => 1.35,
            ],
        ];
    }

    public function testRoom(Request $request): View
    {
        $user = $request->user();
        if (! $user) {
            abort(401);
        }

        $lesson = Lesson::query()->firstOrCreate(
            [
                'tutor_id' => $user->id,
                'notes' => 'Тестирование камеры, звука и интерактивной доски',
            ],
            [
                'student_id' => $user->id,
                'status' => Lesson::STATUS_CONFIRMED,
                'start_time' => now()->subMinutes(5),
                'end_time' => now()->addHours(2),
                'duration_minutes' => 60,
                'price' => 0.00,
                'platform_commission' => 0.00,
                'net_amount' => 0.00,
                'payment_status' => Lesson::PAYMENT_PAID,
            ]
        );

        return $this->show($lesson, $request);
    }

    public function show(Lesson $lesson, Request $request): View
    {
        $user = $request->user();

        // 1. One-click confirm by tutor or admin upon opening classroom
        if ($lesson->status === Lesson::STATUS_PENDING && ($request->has('confirm') || $request->isMethod('post')) && ($user->id === $lesson->tutor_id || $user->isAdmin())) {
            $lesson->update(['status' => Lesson::STATUS_CONFIRMED]);
        }

        // 2. Pending Lesson: Show friendly waiting view instead of raw 403
        if ($lesson->status === Lesson::STATUS_PENDING) {
            $lesson->loadMissing('tutor', 'student');
            return view('classroom.waiting', [
                'lesson' => $lesson,
                'user' => $user,
                'reason' => 'pending',
                'isTutor' => ($user->id === $lesson->tutor_id || $user->isAdmin()),
            ]);
        }

        // 3. Completed or Cancelled Lesson: Show informative ended view
        if (in_array($lesson->status, [Lesson::STATUS_CANCELLED, Lesson::STATUS_COMPLETED], true)) {
            $hasActiveSession = ClassroomSession::query()
                ->where('lesson_id', $lesson->id)
                ->whereIn('status', [ClassroomSession::STATUS_WAITING, ClassroomSession::STATUS_ACTIVE])
                ->exists();
            if (! $hasActiveSession) {
                $lesson->loadMissing('tutor', 'student');
                return view('classroom.waiting', [
                    'lesson' => $lesson,
                    'user' => $user,
                    'reason' => 'ended',
                    'isTutor' => ($user->id === $lesson->tutor_id || $user->isAdmin()),
                ]);
            }
        }

        // 4. Too early check (>2 hours before scheduled start)
        if ($lesson->start_time) {
            $earliest = $lesson->start_time->copy()->subHours(2);
            $hasActiveSession = ClassroomSession::query()
                ->where('lesson_id', $lesson->id)
                ->whereIn('status', [ClassroomSession::STATUS_WAITING, ClassroomSession::STATUS_ACTIVE])
                ->exists();

            if (now()->isBefore($earliest) && ! $hasActiveSession && ! $user->isAdmin() && $user->id !== $lesson->tutor_id) {
                $lesson->loadMissing('tutor', 'student');
                return view('classroom.waiting', [
                    'lesson' => $lesson,
                    'user' => $user,
                    'reason' => 'too_early',
                    'isTutor' => false,
                ]);
            }
        }

        if (! $this->classroomService->canAccess($lesson, $user)) {
            abort(403, 'У вас нет доступа к этому виртуальному классу.');
        }

        if (! config('classroom.enabled', false)) {
            $lesson->loadMissing('tutor', 'student');

            return view('classroom.maintenance', [
                'lesson' => $lesson,
                'user' => $user,
            ]);
        }

        $session = $this->classroomService->openClassroom($lesson, $user);
        $token = $this->classroomService->generateMediaToken($session, $user);

        $lesson->loadMissing('tutor.tutorProfile', 'student');

        $requestedRole = (string) $request->query('role');
        $isTutor = ($user->id === $lesson->tutor_id || $user->isAdmin()) && ($requestedRole !== 'student');
        $userRole = $isTutor ? 'tutor' : 'student';

        $liveKitService = app(LiveKitService::class);
        $liveKitConfigured = $liveKitService->isConfigured();
        $liveKitToken = $liveKitConfigured ? $liveKitService->generateToken($lesson, $user, 7200, $userRole) : null;
        $liveKitWsUrl = $liveKitConfigured ? $liveKitService->getWsUrl() : null;

        $joinToken = substr(hash_hmac('sha256', 'classroom_join_'.$lesson->id, (string) config('app.key')), 0, 16);
        $studentInviteUrl = route('classroom.join', ['lesson' => $lesson->id, 'token' => $joinToken]);

        $jitsiSecret = substr(hash_hmac('sha256', 'jitsi_room_'.$lesson->id.'_'.$session->room_id, (string) config('app.key')), 0, 12);
        $jitsiRoomName = 'edusfera_'.$lesson->id.'_'.$jitsiSecret;
        $jitsiDomain = (string) config('classroom.jitsi_domain', 'meet.jit.si');

        $tutorUser = $lesson->tutor;
        $maxLessonDurationMinutes = $tutorUser ? app(\App\Domain\Subscription\Services\SubscriptionFeatureGate::class)->maxLessonDurationMinutes($tutorUser) : 60;

        return view('classroom.show', [
            'lesson' => $lesson,
            'session' => $session,
            'token' => $token,
            'mediaServerUrl' => $this->classroomService->getMediaServerUrl(),
            'iceServers' => $this->classroomService->getIceServers(),
            'liveKitConfigured' => $liveKitConfigured,
            'liveKitToken' => $liveKitToken,
            'liveKitWsUrl' => $liveKitWsUrl,
            'jitsiDomain' => $jitsiDomain,
            'jitsiRoomName' => $jitsiRoomName,
            'studentInviteUrl' => $studentInviteUrl,
            'userRole' => $userRole,
            'aiChatHistory' => $session->meta['ai_chat_history'] ?? [],
            'maxLessonDurationMinutes' => $maxLessonDurationMinutes,
        ]);
    }

    public function join(Lesson $lesson, Request $request): Response|RedirectResponse
    {
        $expectedToken = substr(hash_hmac('sha256', 'classroom_join_'.$lesson->id, (string) config('app.key')), 0, 16);
        $providedToken = (string) $request->query('token');

        if (! hash_equals($expectedToken, $providedToken)) {
            abort(403, 'Недействительная ссылка приглашения на урок.');
        }

        $student = $lesson->student;
        if (! $student) {
            abort(404, 'Ученик для данного урока не найден.');
        }

        $currentUser = $request->user();
        if (! $currentUser) {
            Auth::login($student, false);
            $request->session()->regenerate();
            $currentUser = $student;
        }

        if (in_array($lesson->status, [Lesson::STATUS_CANCELLED, Lesson::STATUS_COMPLETED], true)) {
            $hasActiveSession = ClassroomSession::query()
                ->where('lesson_id', $lesson->id)
                ->whereIn('status', [ClassroomSession::STATUS_WAITING, ClassroomSession::STATUS_ACTIVE])
                ->exists();
            if (! $hasActiveSession) {
                $lesson->loadMissing('tutor', 'student');
                return response()->view('classroom.waiting', [
                    'lesson' => $lesson,
                    'user' => $currentUser,
                    'reason' => 'ended',
                    'isTutor' => false,
                ]);
            }
        }

        if ($lesson->status === Lesson::STATUS_PENDING) {
            $lesson->loadMissing('tutor', 'student');
            return response()->view('classroom.waiting', [
                'lesson' => $lesson,
                'user' => $currentUser,
                'reason' => 'pending',
                'isTutor' => ($currentUser->id === $lesson->tutor_id || $currentUser->isAdmin()),
            ]);
        }

        if ($lesson->start_time) {
            $earliest = $lesson->start_time->copy()->subHours(2);
            $hasActiveSession = ClassroomSession::query()
                ->where('lesson_id', $lesson->id)
                ->whereIn('status', [ClassroomSession::STATUS_WAITING, ClassroomSession::STATUS_ACTIVE])
                ->exists();

            if (now()->isBefore($earliest) && ! $hasActiveSession && ! $currentUser->isAdmin() && $currentUser->id !== $lesson->tutor_id) {
                $lesson->loadMissing('tutor', 'student');
                return response()->view('classroom.waiting', [
                    'lesson' => $lesson,
                    'user' => $currentUser,
                    'reason' => 'too_early',
                    'isTutor' => false,
                ]);
            }
        }

        return redirect()->route('classroom.show', ['lesson' => $lesson, 'role' => 'student']);
    }

    public function status(Lesson $lesson, Request $request): JsonResponse
    {
        $hasActiveSession = ClassroomSession::query()
            ->where('lesson_id', $lesson->id)
            ->whereIn('status', [ClassroomSession::STATUS_WAITING, ClassroomSession::STATUS_ACTIVE])
            ->exists();

        $canEnter = in_array($lesson->status, [Lesson::STATUS_CONFIRMED, Lesson::STATUS_COMPLETED], true) || $hasActiveSession;

        return response()->json([
            'status' => $lesson->status instanceof \BackedEnum ? $lesson->status->value : $lesson->status,
            'can_enter' => $canEnter,
            'has_active_session' => $hasActiveSession,
            'redirect_url' => route('classroom.show', $lesson),
        ]);
    }

    public function end(Lesson $lesson, Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->id !== $lesson->tutor_id) {
            abort(403, 'Только преподаватель может завершить урок.');
        }

        $session = $lesson->activeClassroom;

        if ($session) {
            $this->classroomService->endClassroom($session);
        }

        if ($lesson->status === Lesson::STATUS_CONFIRMED && ($lesson->hasStarted() || ($lesson->end_time?->isPast() ?? false))) {
            $lesson->update(['status' => Lesson::STATUS_COMPLETED]);

            // Завершение урока должно запускать сеттлмент (перевод доли
            // репетитора из pending в available + захват холда в шлюзе).
            // Раньше статус менялся напрямую, урок выпадал из планировщика
            // lessons:complete (он выбирает только CONFIRMED), и деньги
            // репетитора зависали в pending навсегда.
            try {
                app(PaymentService::class)->settleCompletedLesson($lesson->fresh());
            } catch (\Throwable $e) {
                Log::warning('Classroom lesson settlement failed: '.$e->getMessage(), [
                    'lesson_id' => $lesson->id,
                ]);
            }
        }

        return redirect()->route('filament.admin.resources.lessons.index')->with('success', 'Виртуальный класс завершён.');
    }

    public function storeNote(Lesson $lesson, Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $this->classroomService->canAccess($lesson, $user)) {
            abort(403);
        }

        $validated = $request->validate([
            // Фронтенд отправляет { text }, API-контракт — content: принимаем оба.
            'content' => ['nullable', 'string', 'required_without:text'],
            'text' => ['nullable', 'string', 'required_without:content'],
            'is_shared' => ['nullable', 'boolean'],
        ]);

        $session = $lesson->activeClassroom;

        if (! $session) {
            throw ValidationException::withMessages([
                'session' => 'Нет активной сессии виртуального класса.',
            ]);
        }

        $note = ClassroomNote::query()->create([
            'classroom_session_id' => $session->id,
            'author_id' => $user->id,
            'content' => trim((string) ($validated['content'] ?? $validated['text'])),
            'is_shared' => (bool) ($validated['is_shared'] ?? false),
        ]);

        return response()->json([
            'success' => true,
            'note' => $note->load('author'),
        ], 201);
    }

    public function uploadFile(Lesson $lesson, Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $this->classroomService->canAccess($lesson, $user)) {
            abort(403);
        }

        $request->validate([
            'file' => ['required', 'file', 'max:51200', 'mimes:pdf,doc,docx,png,jpg,jpeg,gif', 'mimetypes:application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,image/png,image/jpeg,image/gif'],
        ]);

        $session = $lesson->activeClassroom;

        if (! $session) {
            throw ValidationException::withMessages([
                'session' => 'Нет активной сессии виртуального класса.',
            ]);
        }

        $uploadedFile = $request->file('file');
        $storagePath = $uploadedFile->store("classroom-files/{$session->id}", 'local');

        $file = ClassroomFile::query()->create([
            'classroom_session_id' => $session->id,
            'uploaded_by' => $user->id,
            'original_name' => $uploadedFile->getClientOriginalName(),
            'path' => $storagePath,
            'mime_type' => $uploadedFile->getClientMimeType(),
            'size_bytes' => $uploadedFile->getSize(),
        ]);

        return response()->json([
            'success' => true,
            'file' => $file->load('uploader'),
        ], 201);
    }

    public function downloadFile(Lesson $lesson, ClassroomFile $file, Request $request): StreamedResponse
    {
        $user = $request->user();

        if (! $this->classroomService->canAccess($lesson, $user)) {
            abort(403);
        }

        $sessionIds = $lesson->classroomSessions()->pluck('id');

        if (! $sessionIds->contains($file->classroom_session_id)) {
            abort(404, 'Файл не найден.');
        }

        if (! Storage::disk('local')->exists($file->path)) {
            abort(404, 'Файл не найден.');
        }

        // L3: strip newlines to prevent Content-Disposition header injection
        $safeName = str_replace(["\r", "\n"], '', $file->original_name);
        $safeName = preg_replace('/[^\w.\-]/', '_', $safeName);

        return Storage::disk('local')->download($file->path, $safeName, [
            'Content-Type' => $file->mime_type,
        ]);
    }

    public function submitReport(Lesson $lesson, Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->id !== $lesson->tutor_id) {
            abort(403, 'Только преподаватель может отправлять отчёт.');
        }

        $validated = $request->validate([
            'summary' => ['required', 'string'],
            'focus' => ['required', 'string'],
            // Фронтенд отправляет next_steps (множественное), API — next_step.
            'next_step' => ['nullable', 'string', 'required_without:next_steps'],
            'next_steps' => ['nullable', 'string'],
            'homework_summary' => ['nullable', 'string'],
            'score' => ['nullable', 'integer', 'min:1', 'max:10'],
        ]);

        $nextStep = trim((string) ($validated['next_step'] ?? $validated['next_steps'] ?? ''));

        $lesson->update([
            'tutor_report_summary' => trim($validated['summary']),
            'tutor_report_focus' => trim($validated['focus']),
            'tutor_next_step' => $nextStep,
            'tutor_homework_summary' => isset($validated['homework_summary']) && trim($validated['homework_summary']) !== ''
                ? trim($validated['homework_summary'])
                : null,
            'tutor_report_score' => $validated['score'] ?? null,
            'tutor_reported_at' => now('UTC'),
        ]);

        return response()->json([
            'success' => true,
            'lesson' => $lesson->fresh(),
        ]);
    }

    public function getNotes(Lesson $lesson, Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $this->classroomService->canAccess($lesson, $user)) {
            abort(403);
        }

        $session = $lesson->activeClassroom;
        if (! $session) {
            return response()->json([]);
        }

        // Приватные заметки (is_shared=false) видит только их автор и
        // репетитор. Раньше студент получал ВСЕ заметки, включая личные
        // заметки репетитора о занятии.
        $isTutor = $user->id === $lesson->tutor_id;

        $notes = ClassroomNote::query()
            ->where('classroom_session_id', $session->id)
            ->when(! $isTutor, function ($query) use ($user): void {
                $query->where(function ($inner) use ($user): void {
                    $inner->where('is_shared', true)->orWhere('author_id', $user->id);
                });
            })
            ->latest('id')
            ->get()
            ->map(fn ($note) => [
                'id' => $note->id,
                'text' => $note->content,
                'time' => $note->created_at->setTimezone(config('app.timezone'))->format('H:i'),
            ]);

        return response()->json($notes);
    }

    public function getFiles(Lesson $lesson, Request $request): JsonResponse
    {
        if (! $this->classroomService->canAccess($lesson, $request->user())) {
            abort(403);
        }

        $sessionIds = $lesson->classroomSessions()->pluck('id');
        if ($sessionIds->isEmpty()) {
            return response()->json([]);
        }

        $files = ClassroomFile::query()
            ->whereIn('classroom_session_id', $sessionIds)
            ->latest('id')
            ->get()
            ->map(fn ($file) => [
                'id' => $file->id,
                'name' => $file->original_name,
                'size' => Formatters::formatBytes($file->size_bytes),
                'url' => route('classroom.files.download', ['lesson' => $lesson->id, 'file' => $file->id]),
            ]);

        return response()->json($files);
    }

    public function getChat(Lesson $lesson, Request $request): JsonResponse
    {
        if (! $this->classroomService->canAccess($lesson, $request->user())) {
            abort(403);
        }

        $session = $lesson->activeClassroom;
        if (! $session) {
            return response()->json([]);
        }

        $messages = ClassroomChatMessage::query()
            ->where('classroom_session_id', $session->id)
            ->oldest('id')
            ->get()
            ->map(fn ($msg) => [
                'id' => $msg->id,
                'userId' => $msg->sender_id,
                'userName' => $msg->sender->name ?? 'User',
                'text' => $msg->message,
                'time' => $msg->created_at->setTimezone(config('app.timezone'))->format('H:i'),
            ]);

        return response()->json($messages);
    }

    public function storeChat(Lesson $lesson, Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $this->classroomService->canAccess($lesson, $user)) {
            abort(403);
        }

        $session = $lesson->activeClassroom;
        if (! $session) {
            return response()->json(['error' => 'No active session'], 400);
        }

        $validated = $request->validate([
            'message' => ['required', 'string', 'max:1000'],
        ]);

        $message = ClassroomChatMessage::query()->create([
            'classroom_session_id' => $session->id,
            'sender_id' => $user->id,
            'message' => trim($validated['message']),
        ]);

        return response()->json([
            'id' => $message->id,
            'userId' => $user->id,
            'userName' => $user->name,
            'text' => $message->message,
            'time' => $message->created_at->setTimezone(config('app.timezone'))->format('H:i'),
        ], 201);
    }

    public function getHomework(Lesson $lesson, Request $request): JsonResponse
    {
        if (! $this->classroomService->canAccess($lesson, $request->user())) {
            abort(403);
        }

        $homework = HomeworkAssignment::query()
            ->where('lesson_id', $lesson->id)
            ->latest('id')
            ->get()
            ->map(fn ($hw) => [
                'id' => $hw->id,
                'title' => $hw->title,
                'due_date' => $hw->due_at ? $hw->due_at->format('Y-m-d') : 'Нет',
                'status' => $hw->status === 'completed' ? 'done' : 'pending',
            ]);

        return response()->json($homework);
    }

    public function assignHomework(Lesson $lesson, Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->id !== $lesson->tutor_id) {
            abort(403, 'Только преподаватель может назначать домашнее задание.');
        }

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:160'],
            // Фронтенд отправляет description и due_date (YYYY-MM-DD),
            // API-контракт — instructions и due_at (datetime). Принимаем оба.
            'instructions' => ['nullable', 'string'],
            'description' => ['nullable', 'string'],
            'due_at' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date'],
        ]);

        $instructions = trim((string) ($validated['instructions'] ?? $validated['description'] ?? ''));
        $dueAt = $validated['due_at'] ?? null;

        if ($dueAt === null && isset($validated['due_date'])) {
            // Дата без времени — дедлайн до конца указанного дня.
            $dueAt = Carbon::parse($validated['due_date'])->setTime(23, 59, 0);
        }

        $homework = HomeworkAssignment::query()->create([
            'lesson_id' => $lesson->id,
            'student_id' => $lesson->student_id,
            'tutor_id' => $lesson->tutor_id,
            'title' => trim($validated['title']),
            'instructions' => $instructions !== '' ? $instructions : null,
            'source' => 'tutor',
            'status' => 'assigned',
            'assigned_at' => now('UTC'),
            'due_at' => $dueAt,
        ]);

        return response()->json([
            'success' => true,
            'homework' => $homework,
        ], 201);
    }

    public function studentProfile(Lesson $lesson, Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->id !== $lesson->tutor_id) {
            abort(403, 'Только преподаватель может просматривать профиль ученика.');
        }

        $studentId = $lesson->student_id;
        $tutorId = $lesson->tutor_id;

        $goals = StudentGoal::query()
            ->where('student_id', $studentId)
            ->where('tutor_id', $tutorId)
            ->where('status', 'active')
            ->latest('id')
            ->get();

        $progress = ProgressSnapshot::query()
            ->where('student_id', $studentId)
            ->where('tutor_id', $tutorId)
            ->latest('snapshot_date')
            ->limit(10)
            ->get();

        $skillGaps = SkillGap::query()
            ->where('student_id', $studentId)
            ->where('status', 'open')
            ->latest('id')
            ->get();

        $homework = HomeworkAssignment::query()
            ->where('student_id', $studentId)
            ->where('tutor_id', $tutorId)
            ->latest('id')
            ->limit(10)
            ->get();

        $diagnostic = DiagnosticAttempt::query()
            ->where('student_id', $studentId)
            ->where('tutor_id', $tutorId)
            ->latest('taken_at')
            ->first();

        return response()->json([
            'goals' => $goals,
            'progress' => $progress,
            'skillGaps' => $skillGaps,
            'homework' => $homework,
            'lastDiagnostic' => $diagnostic,
        ]);
    }

    public function saveWhiteboardState(string $roomId, Request $request): JsonResponse
    {
        // M2: Use dedicated internal_secret with timing-safe comparison.
        // Never reuse jwt_secret for auth — it's for JWT signing only.
        $authHeader = $request->header('Authorization', '');
        $internalSecret = config('classroom.internal_secret');

        // empty(), а не === '': при null (env не задан) строгое сравнение
        // пропускало дальше, и 'Bearer ' с пустым секретом проходил проверку.
        if (! is_string($internalSecret) || $internalSecret === '' || ! hash_equals('Bearer '.$internalSecret, $authHeader)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $session = ClassroomSession::query()->where('room_id', $roomId)->first();
        if (! $session) {
            return response()->json(['error' => 'Session not found'], 404);
        }

        $state = $request->json()->all();
        if (empty($state)) {
            return response()->json(['success' => true]);
        }

        $this->classroomService->saveWhiteboardState($session, $state);

        return response()->json(['success' => true]);
    }

    public function chatAi(Lesson $lesson, Request $request, AiService $aiService): JsonResponse
    {
        $user = $request->user();
        if (! $this->classroomService->canAccess($lesson, $user)) {
            abort(403);
        }

        $session = $lesson->activeClassroom;
        if (! $session) {
            return response()->json(['error' => 'No active session'], 400);
        }

        $validated = $request->validate([
            'message' => ['nullable', 'string', 'max:2000', 'required_without:prompt'],
            'prompt' => ['nullable', 'string', 'max:2000'],
            'history' => ['nullable', 'array', 'max:30'],
            'history.*.role' => ['nullable', 'string', 'in:user,assistant,model'],
            'history.*.text' => ['nullable', 'string', 'max:3000'],
            'history.*.content' => ['nullable', 'string', 'max:3000'],
        ]);

        $messageText = trim((string) ($validated['message'] ?? $validated['prompt'] ?? ''));
        if ($messageText === '') {
            return response()->json(['error' => 'Message is required'], 422);
        }

        $lesson->loadMissing('tutor.tutorProfile', 'student');

        $sessionHistory = is_array($session->meta['ai_chat_history'] ?? null)
            ? $session->meta['ai_chat_history']
            : [];

        // Priority to incoming history, fallback to session meta history
        $history = ! empty($validated['history']) ? $validated['history'] : $sessionHistory;

        $result = $aiService->chat(
            $messageText,
            $session->room_id,
            $lesson,
            $user,
            $history
        );

        // Update persistent conversation history in session meta
        $userMsgId = 'msg-'.(string) Str::uuid();
        $aiMsgId = 'msg-'.(string) Str::uuid();

        $sessionHistory[] = [
            'id' => $userMsgId,
            'role' => 'user',
            'text' => $messageText,
            'timestamp' => now('UTC')->toIso8601String(),
        ];

        $sessionHistory[] = [
            'id' => $aiMsgId,
            'role' => 'assistant',
            'text' => $result['reply'],
            'actions' => $result['actions'] ?? [],
            'created_entities' => $result['created_entities'] ?? [],
            'timestamp' => now('UTC')->toIso8601String(),
        ];

        if (count($sessionHistory) > 30) {
            $sessionHistory = array_slice($sessionHistory, -30);
        }

        $meta = $session->meta ?? [];
        $meta['ai_chat_history'] = $sessionHistory;
        $session->meta = $meta;
        $session->save();

        return response()->json([
            'reply' => $result['reply'],
            'actions' => $result['actions'] ?? [],
            'created_entities' => $result['created_entities'] ?? [],
            'history' => $sessionHistory,
        ]);
    }

    public function updateMeetingLink(Lesson $lesson, Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->id !== $lesson->tutor_id && ! $user->isAdmin()) {
            abort(403, 'Только репетитор может указать ссылку на видеовстречу.');
        }

        $validated = $request->validate([
            'meeting_link' => ['required', 'url', 'max:500'],
        ]);

        $lesson->update([
            'meeting_link' => $validated['meeting_link'],
        ]);

        return back()->with('status', 'Ссылка на видеовстречу успешно сохранена!');
    }

    public function sendSignal(Lesson $lesson, Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $this->classroomService->canAccess($lesson, $user)) {
            abort(403, 'У вас нет доступа к этому уроку.');
        }

        $validated = $request->validate([
            'type' => ['required', 'string', 'in:offer,answer,candidate,candidates,hangup,ping,hello,ready,restart,screenshare-state,wb-action,wb_broadcast,chat-message,media-state'],
            'payload' => ['nullable'],
            'client_id' => ['nullable', 'string', 'max:100'],
            'role' => ['nullable', 'string', 'in:tutor,student'],
        ]);

        $payload = $validated['payload'] ?? null;
        if (is_array($payload) && isset($payload['sdp']) && is_string($payload['sdp'])) {
            $lines = preg_split("/\r\n|\r|\n/", trim($payload['sdp']));
            $filtered = array_filter(array_map('trim', $lines), fn ($l) => $l !== '');
            $payload['sdp'] = implode("\r\n", $filtered)."\r\n";
        }

        if ($request->hasSession()) {
            $request->session()->save();
        }

        $nowMs = (int) (microtime(true) * 1000);
        $clientId = $validated['client_id'] ?? $request->header('X-Client-ID') ?? ('user_'.$user->id);
        $role = $validated['role'] ?? ($request->header('X-Client-Role')) ?? (($user->id === $lesson->tutor_id || $user->isAdmin()) ? 'tutor' : 'student');

        $newSignal = [
            'id' => (string) Str::uuid(),
            'sender_id' => $user->id,
            'client_id' => $clientId,
            'sender_role' => $role,
            'type' => $validated['type'],
            'payload' => $payload,
            'created_at_ms' => $nowMs,
        ];

        $cacheKey = 'classroom_signals_'.$lesson->id;
        $lockKey = 'lock_classroom_signals_'.$lesson->id;

        $updateSignals = static function () use ($cacheKey, &$newSignal, $nowMs): bool {
            $signals = Cache::get($cacheKey, []);
            if (! is_array($signals)) {
                $signals = [];
            }

            // On ICE restart, purge stale signals so renegotiation starts clean
            if ($newSignal['type'] === 'restart') {
                $signals = [];
            }

            // Find current maximum seq
            $lastSeq = 0;
            foreach ($signals as $item) {
                if (isset($item['seq']) && (int) $item['seq'] > $lastSeq) {
                    $lastSeq = (int) $item['seq'];
                }
            }
            $newSignal['seq'] = $lastSeq + 1;

            // Retain only signals from last 2 minutes to prevent unbounded growth
            $signals = array_values(array_filter($signals, static function (array $item) use ($nowMs): bool {
                return ($nowMs - ((int) ($item['created_at_ms'] ?? 0))) < 120000;
            }));

            $signals[] = $newSignal;
            Cache::put($cacheKey, $signals, now()->addHours(2));

            return true;
        };

        try {
            // Non-blocking fast lock acquisition (max 1s lock duration) to prevent PHP-FPM starvation and 503 errors
            $acquired = Cache::lock($lockKey, 1)->get($updateSignals);
            if (! $acquired) {
                $updateSignals();
            }
        } catch (\Throwable) {
            $updateSignals();
        }

        return response()->json([
            'status' => 'ok',
            'signal_id' => $newSignal['id'],
            'seq' => $newSignal['seq'] ?? null,
            'server_time' => $nowMs,
        ]);
    }

    public function getSignals(Lesson $lesson, Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $this->classroomService->canAccess($lesson, $user)) {
            abort(403, 'У вас нет доступа к этому уроку.');
        }

        if ($request->hasSession()) {
            $request->session()->save();
        }

        $clientId = $request->query('client_id') ?? $request->header('X-Client-ID');
        $sinceSeq = $request->has('since_seq') ? (int) $request->query('since_seq') : null;
        $since = (int) $request->query('since', 0);
        $nowMs = (int) (microtime(true) * 1000);

        // Strict role resolution: tutor only listens to student, student only listens to tutor
        // Respects explicit role passed from client (query param or header) to support testing and multiple tabs
        $requestedRole = (string) ($request->query('role') ?? $request->header('X-Client-Role'));
        if ($requestedRole === 'tutor' || $requestedRole === 'student') {
            $userRole = $requestedRole;
        } else {
            $isTutorRole = ($user->id === $lesson->tutor_id || $user->isAdmin()) && ($requestedRole !== 'student');
            $userRole = $isTutorRole ? 'tutor' : 'student';
        }
        $expectedPeerRole = ($userRole === 'tutor') ? 'student' : 'tutor';

        $cacheKey = 'classroom_signals_'.$lesson->id;
        $signals = Cache::get($cacheKey, []);
        if (! is_array($signals)) {
            $signals = [];
        }

        $maxSeq = 0;
        foreach ($signals as $s) {
            if (isset($s['seq']) && (int) $s['seq'] > $maxSeq) {
                $maxSeq = (int) $s['seq'];
            }
        }

        $minTimestamp = ($since > 0) ? ($since - 1500) : ($nowMs - 60000);

        // Return only signals from the counterpart participant
        $incoming = array_values(array_filter($signals, static function (array $item) use ($user, $clientId, $userRole, $expectedPeerRole, $sinceSeq, $minTimestamp): bool {
            // 1. NEVER receive own signals by client ID
            if (! empty($clientId) && ! empty($item['client_id']) && $item['client_id'] === $clientId) {
                return false;
            }

            // 2. NEVER receive own signals by user ID if client ID is matching or not specified
            if ((int) ($item['sender_id'] ?? 0) === (int) $user->id && (empty($clientId) || empty($item['client_id']))) {
                return false;
            }

            // 3. STRICT 1-on-1 counterpart role filtering:
            // Tutor MUST ONLY receive signals from student.
            // Student MUST ONLY receive signals from tutor.
            if (! empty($item['sender_role'])) {
                if ($item['sender_role'] === $userRole) {
                    return false;
                }
                if ($item['sender_role'] !== $expectedPeerRole) {
                    return false;
                }
            }

            // 4. Sequence filtering (strictly monotonic)
            if ($sinceSeq !== null && $sinceSeq >= 0) {
                return (int) ($item['seq'] ?? 0) > $sinceSeq;
            }

            // 5. Fallback to timestamp filtering with grace window
            return (int) ($item['created_at_ms'] ?? 0) > $minTimestamp;
        }));

        return response()->json([
            'signals' => $incoming,
            'server_time' => $nowMs,
            'max_seq' => $maxSeq,
        ]);
    }

    public function getWhiteboardState(Lesson $lesson, Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $this->classroomService->canAccess($lesson, $user)) {
            abort(403, 'У вас нет доступа к этому уроку.');
        }

        $session = $lesson->activeClassroom ?? ClassroomSession::query()
            ->where('lesson_id', $lesson->id)
            ->latest('id')
            ->first();

        return response()->json([
            'state' => $session?->whiteboard_state ?? [],
        ]);
    }

    public function saveWhiteboardStateHttp(Lesson $lesson, Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $this->classroomService->canAccess($lesson, $user)) {
            abort(403, 'У вас нет доступа к этому уроку.');
        }

        $session = $lesson->activeClassroom ?? ClassroomSession::query()
            ->where('lesson_id', $lesson->id)
            ->latest('id')
            ->first();

        if ($session) {
            $state = $request->input('state', []);
            if (is_array($state)) {
                $this->classroomService->saveWhiteboardState($session, $state);
            }
        }

        return response()->json(['success' => true]);
    }

    public function syncWhiteboard(Lesson $lesson, Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $this->classroomService->canAccess($lesson, $user)) {
            abort(403, 'У вас нет доступа к этому уроку.');
        }

        if ($request->hasSession()) {
            $request->session()->save();
        }

        $validated = $request->validate([
            'elements' => ['nullable', 'array', 'max:5000'],
            'version' => ['nullable', 'numeric'],
            'client_id' => ['nullable', 'string', 'max:100'],
            'is_locked' => ['nullable', 'boolean'],
        ]);

        $elements = $validated['elements'] ?? [];
        $version = (int) ($validated['version'] ?? (int) (microtime(true) * 1000));
        $clientId = (string) ($validated['client_id'] ?? '');
        $isLocked = isset($validated['is_locked']) ? (bool) $validated['is_locked'] : null;

        $cacheKey = 'classroom_wb_'.$lesson->id;
        $prev = Cache::get($cacheKey, []);

        $state = [
            'elements' => is_array($elements) ? $elements : [],
            'version' => $version,
            'sender_id' => $user->id,
            'client_id' => $clientId,
            'is_locked' => $isLocked !== null ? $isLocked : ($prev['is_locked'] ?? false),
            'updated_at_ms' => (int) (microtime(true) * 1000),
        ];

        Cache::put($cacheKey, $state, now()->addHours(6));

        // Periodic database persistence (debounced to avoid thrashing MySQL)
        $dbCacheKey = 'classroom_wb_dbsave_'.$lesson->id;
        if (! Cache::has($dbCacheKey)) {
            Cache::put($dbCacheKey, true, 5);
            $session = $lesson->activeClassroom ?? ClassroomSession::query()
                ->where('lesson_id', $lesson->id)
                ->latest('id')
                ->first();
            if ($session && is_array($elements)) {
                $session->update(['whiteboard_state' => $elements]);
            }
        }

        return response()->json([
            'status' => 'ok',
            'version' => $version,
            'server_time' => $state['updated_at_ms'],
        ]);
    }

    public function pollWhiteboard(Lesson $lesson, Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $this->classroomService->canAccess($lesson, $user)) {
            abort(403, 'У вас нет доступа к этому уроку.');
        }

        if ($request->hasSession()) {
            $request->session()->save();
        }

        $sinceVersion = (int) $request->query('since_version', 0);
        $clientId = (string) $request->query('client_id', '');
        $cacheKey = 'classroom_wb_'.$lesson->id;

        // 1. Initial check (fast-path)
        $current = Cache::get($cacheKey);
        if ($current && isset($current['version'])) {
            if ($sinceVersion === 0 || ($current['version'] > $sinceVersion && ($current['client_id'] ?? '') !== $clientId)) {
                return response()->json([
                    'has_update' => true,
                    'state' => $current,
                ]);
            }
        } elseif ($sinceVersion === 0) {
            // Fallback to database for initial load if cache is empty
            $session = $lesson->activeClassroom ?? ClassroomSession::query()
                ->where('lesson_id', $lesson->id)
                ->latest('id')
                ->first();
            $dbElements = $session?->whiteboard_state ?? [];
            if (! empty($dbElements)) {
                $state = [
                    'elements' => $dbElements,
                    'version' => (int) (microtime(true) * 1000),
                    'sender_id' => null,
                    'client_id' => 'db_init',
                    'is_locked' => false,
                    'updated_at_ms' => (int) (microtime(true) * 1000),
                ];
                Cache::put($cacheKey, $state, now()->addHours(6));

                return response()->json([
                    'has_update' => true,
                    'state' => $state,
                ]);
            }
        }

        // 2. Long-poll: hold up to 2 seconds checking every 100ms (prevents PHP-FPM worker starvation)
        $startTime = microtime(true);
        while ((microtime(true) - $startTime) < 2.0) {
            usleep(100000); // 100ms
            $current = Cache::get($cacheKey);
            if ($current && isset($current['version']) && $current['version'] > $sinceVersion && ($current['client_id'] ?? '') !== $clientId) {
                return response()->json([
                    'has_update' => true,
                    'state' => $current,
                ]);
            }
        }

        return response()->json([
            'has_update' => false,
            'version' => $sinceVersion,
        ]);
    }
}
