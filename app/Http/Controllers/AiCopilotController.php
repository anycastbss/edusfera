<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\Subscription\Services\SubscriptionFeatureGate;
use App\Models\HomeworkAssignment;
use App\Models\Lesson;
use App\Models\StudentGoal;
use App\Models\User;
use App\Services\Ai\GeminiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

use Symfony\Component\HttpFoundation\StreamedResponse;

class AiCopilotController extends Controller
{
    public function chat(Request $request, GeminiService $gemini): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['error' => 'Требуется авторизация'], 401);
        }

        $validated = $this->validateChatRequest($request);

        $prompt = trim($validated['prompt']);
        $rawHistory = $validated['history'] ?? [];
        $systemInstruction = $this->buildSystemInstruction($user, $validated);

        try {
            $messages = [];
            foreach ($rawHistory as $msg) {
                $messages[] = [
                    'role' => $msg['role'] === 'assistant' ? 'assistant' : 'user',
                    'content' => (string) $msg['content'],
                ];
            }
            $messages[] = ['role' => 'user', 'content' => $prompt];

            $reply = $gemini->chat($messages, $systemInstruction);

            if (empty(trim($reply))) {
                $reply = $gemini->generateText($prompt, $systemInstruction);
            }

            if (empty(trim($reply))) {
                $reply = 'Извините, не удалось сформировать ответ. Попробуйте переформулировать запрос.';
            }

            return response()->json([
                'success' => true,
                'reply' => $reply,
            ]);
        } catch (\Throwable $e) {
            Log::error('AI Copilot request error: '.$e->getMessage(), ['user_id' => $user->id]);

            return response()->json([
                'success' => false,
                'error' => 'Произошла ошибка при обращении к ИИ-ассистенту. Попробуйте еще раз через несколько секунд.',
            ], 500);
        }
    }

    /**
     * Stream multi-turn conversation in real time via SSE (Server-Sent Events)
     */
    public function stream(Request $request, GeminiService $gemini): StreamedResponse
    {
        $user = $request->user();
        if (! $user) {
            abort(401, 'Требуется авторизация');
        }

        $validated = $this->validateChatRequest($request);
        $prompt = trim($validated['prompt']);
        $rawHistory = $validated['history'] ?? [];
        $systemInstruction = $this->buildSystemInstruction($user, $validated);

        $messages = [];
        foreach ($rawHistory as $msg) {
            $messages[] = [
                'role' => $msg['role'] === 'assistant' ? 'assistant' : 'user',
                'content' => (string) $msg['content'],
            ];
        }
        $messages[] = ['role' => 'user', 'content' => $prompt];

        return response()->stream(function () use ($gemini, $messages, $systemInstruction, $prompt) {
            // Disable output buffering
            while (ob_get_level() > 0) {
                ob_end_flush();
            }

            try {
                $hasEmitted = false;
                $gemini->streamChat($messages, $systemInstruction, function (string $chunk) use (&$hasEmitted) {
                    $hasEmitted = true;
                    echo "data: ".json_encode(['text' => $chunk], JSON_UNESCAPED_UNICODE)."\n\n";
                    if (ob_get_level() > 0) {
                        ob_flush();
                    }
                    flush();
                });

                if (! $hasEmitted) {
                    $fallback = $gemini->generateText($prompt, $systemInstruction);
                    if (! empty($fallback)) {
                        echo "data: ".json_encode(['text' => $fallback], JSON_UNESCAPED_UNICODE)."\n\n";
                    }
                }
            } catch (\Throwable $e) {
                Log::error('AI stream error: '.$e->getMessage());
                echo "data: ".json_encode(['error' => 'Ошибка генерации потока'], JSON_UNESCAPED_UNICODE)."\n\n";
            } finally {
                echo "data: ".json_encode(['done' => true])."\n\n";
                if (ob_get_level() > 0) {
                    ob_flush();
                }
                flush();
            }
        }, 200, [
            'Content-Type' => 'text/event-stream; charset=utf-8',
            'Cache-Control' => 'no-cache, no-transform',
            'Connection' => 'keep-alive',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    /**
     * Execute one-click agent action on behalf of user
     */
    public function executeAction(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['success' => false, 'error' => 'Требуется авторизация'], 401);
        }

        $action = (string) $request->input('action');
        $payload = $request->input('payload', []);

        // 1. Create Homework
        if ($action === 'homework.create') {
            if (! $user->isTutor()) {
                return response()->json(['success' => false, 'error' => 'Только преподаватель может назначать ДЗ'], 403);
            }

            $title = trim((string) ($payload['title'] ?? 'Домашнее задание'));
            $instructions = trim((string) ($payload['instructions'] ?? ''));
            $studentId = (int) ($payload['student_id'] ?? 0);
            $dueDays = max(1, (int) ($payload['due_days'] ?? 3));

            if ($studentId <= 0) {
                $lastLesson = Lesson::where('tutor_id', $user->id)
                    ->whereNotNull('student_id')
                    ->latest('id')
                    ->first();
                $studentId = $lastLesson?->student_id ?? 0;
            }

            if ($studentId <= 0) {
                return response()->json([
                    'success' => false,
                    'error' => 'Не найден ученик для назначения задания. Выберите ученика в списке.',
                ], 422);
            }

            $hw = HomeworkAssignment::create([
                'student_id' => $studentId,
                'tutor_id' => $user->id,
                'title' => mb_substr($title, 0, 160),
                'instructions' => $instructions ?: null,
                'source' => 'ai_agent',
                'status' => 'assigned',
                'assigned_at' => now('UTC'),
                'due_at' => now('UTC')->addDays($dueDays)->setTime(23, 59, 0),
            ]);

            return response()->json([
                'success' => true,
                'message' => "Домашнее задание «{$hw->title}» успешно создано в системе!",
                'homework_id' => $hw->id,
            ]);
        }

        // 2. Create Student Goal
        if ($action === 'goal.create') {
            $studentId = (int) ($payload['student_id'] ?? ($user->isStudent() ? $user->id : 0));
            $subject = trim((string) ($payload['subject'] ?? 'Математика'));
            $targetScore = (int) ($payload['target_score'] ?? 85);
            $examType = (string) ($payload['exam_type'] ?? 'ЦТ 2026');

            if ($studentId > 0) {
                $goal = StudentGoal::create([
                    'student_id' => $studentId,
                    'tutor_id' => $user->isTutor() ? $user->id : null,
                    'subject' => $subject,
                    'exam_type' => $examType,
                    'target_score' => $targetScore,
                    'current_score' => 50,
                    'status' => 'active',
                ]);

                return response()->json([
                    'success' => true,
                    'message' => "Цель сдачи {$examType} на {$targetScore} баллов успешно зафиксирована!",
                    'goal_id' => $goal->id,
                ]);
            }
        }

        return response()->json(['success' => false, 'error' => 'Неизвестное действие агента'], 400);
    }

    /**
     * Get tutor's active students list for quick selector
     */
    public function getTutorStudents(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user || ! $user->isTutor()) {
            return response()->json(['students' => []]);
        }

        $studentIds = Lesson::where('tutor_id', $user->id)
            ->whereNotNull('student_id')
            ->pluck('student_id')
            ->unique();

        $students = User::whereIn('id', $studentIds)
            ->select('id', 'name', 'email')
            ->get();

        return response()->json([
            'students' => $students,
        ]);
    }

    protected function validateChatRequest(Request $request): array
    {
        return $request->validate([
            'prompt' => ['required', 'string', 'max:4000'],
            'history' => ['nullable', 'array', 'max:25'],
            'history.*.role' => ['required_with:history', 'string', 'in:user,assistant,model'],
            'history.*.content' => ['required_with:history', 'string', 'max:6000'],
            'persona' => ['nullable', 'string', 'in:default,methodologist,rikz_expert,express_solver,simple_analogy,homework_gen,free_chat'],
            'tone' => ['nullable', 'string', 'in:friendly,academic,concise,step_by_step'],
            'length' => ['nullable', 'string', 'in:balanced,short,detailed'],
            'custom_prompt' => ['nullable', 'string', 'max:1000'],
            'subject' => ['nullable', 'string', 'max:100'],
            'student_id' => ['nullable', 'integer'],
        ]);
    }

    protected function buildSystemInstruction(User $user, array $validated): string
    {
        $persona = (string) ($validated['persona'] ?? 'default');
        $tone = (string) ($validated['tone'] ?? 'friendly');
        $length = (string) ($validated['length'] ?? 'balanced');
        $customPrompt = trim((string) ($validated['custom_prompt'] ?? ''));
        $subject = trim((string) ($validated['subject'] ?? ''));
        $studentId = (int) ($validated['student_id'] ?? 0);

        $isTutor = $user->isTutor();
        $userName = explode(' ', trim((string) $user->name))[0] ?? 'Пользователь';

        $subjectContext = '';
        if (! empty($subject) && ! in_array($subject, ['Общий диалог', 'Общий режим', 'Все предметы'], true)) {
            $subjectContext = " Выбранный профильный предмет текущего диалога: «{$subject}». Все примеры, методические материалы, задачи и спецификации выстраивай с приоритетом под предмет «{$subject}».";
        } else {
            $subjectContext = " Диалог ведется в свободном/общем режиме: ты можешь консультировать по любым школьным и олимпиадным предметам, педагогике, психологии и организации учебного процесса.";
        }

        if ($persona === 'rikz_expert') {
            $baseRole = "Ты — ведущий эксперт-методист по тестированию ЦТ и ЦЭ Беларуси 2026. Твоего собеседника зовут {$userName}.{$subjectContext} Твой фокус — тонкости формулировок, ловушки в части А и Б, разбор типичных ошибок абитуриентов и критерии перевода баллов.";
        } elseif ($persona === 'express_solver') {
            $baseRole = "Ты — мгновенный аналитик и решатель задач для {$userName}.{$subjectContext} Давай строго аналитическое, выверенное решение с формулами, промежуточными расчетами и итоговым ответом.";
        } elseif ($persona === 'simple_analogy') {
            $baseRole = "Ты — мастер ярких педагогических аналогий и метафор для {$userName}.{$subjectContext} Твоя суперсила — объяснить любую сложнейшую тему школьной программы на простых жизненных примерах 'на пальцах'.";
        } elseif ($persona === 'homework_gen') {
            $baseRole = "Ты — генератор авторских домашних заданий и проверочных работ для {$userName}.{$subjectContext} Формируй задания блоками по уровню сложности, а в конце обязательно давай блок ответов и критериев быстрой проверки.";
        } elseif ($persona === 'methodologist' || ($persona === 'default' && $isTutor)) {
            $baseRole = "Ты — персональный ИИ-методист образовательной платформы Edusfera.by (Edusfera AI). Твоего собеседника зовут {$userName} (преподаватель/репетитор).{$subjectContext} Ты помогаешь составлять технологические карты занятий, практические задания, интерактивные приемы, дифференцированные конспекты и отвечать на любые профессиональные вопросы.";
        } elseif ($persona === 'free_chat') {
            $baseRole = "Ты — эрудированный, открытый и вдохновляющий ИИ-собеседник Edusfera AI для {$userName}.{$subjectContext} Твой фокус — живой образовательный брейншторм, обсуждение педагогических идей, методик и мотивации без жестких рамок.";
        } elseif ($user->isParent() || ($persona === 'default' && $user->isParent())) {
            $baseRole = "Ты — заботливый персональный ИИ-консультант Edusfera AI для родителей. Твоего собеседника зовут {$userName}.{$subjectContext} Ты помогаешь родителям отслеживать успеваемость ребенка, понимать специфику ЦТ/ЦЭ 2026, взаимодействовать с преподавателями и сохранять психологический комфорт в семье.";
        } elseif ($persona === 'default' && $user->isStudent()) {
            $baseRole = "Ты — заботливый и умный ИИ-тьютор Edusfera AI для {$userName}.{$subjectContext} Помогаешь понять учебный материал, подготовиться к урокам и экзаменам, мотивируешь и поддерживаешь интерес к учебе.";
        } else {
            $baseRole = "Ты — официальный ИИ-ассистент платформы Edusfera.by (Edusfera AI) для {$userName}.{$subjectContext} Отвечай структурированно, понятно и по делу.";
        }

        $toneGuide = match ($tone) {
            'academic' => 'Придерживайся строгого академического стиля, точной научной терминологии и доказательности.',
            'concise' => 'Отвечай предельно емко, тезисно, без лишних вводных слов и ритуалов вежливости.',
            'step_by_step' => 'Обязательно структурируй объяснение по пронумерованным этапам: Шаг 1, Шаг 2 и т.д.',
            default => 'Пиши живым, воодушевляющим и дружелюбным языком опытного наставника.',
        };

        $lengthGuide = match ($length) {
            'short' => 'Формат ответа: краткий конспект (не более 2-3 компактных пунктов/абзацев).',
            'detailed' => 'Формат ответа: развернутый, исчерпывающий разбор с подробными примерами и контекстом.',
            default => 'Формат ответа: сбалансированный объем, идеальный для быстрого чтения.',
        };

        $formatting = "Всегда используй красивый структурированный Markdown:\n".
            "- Все математические и физические формулы оформляй строго в LaTeX: встроенные формулы внутри $...$, а крупные формулы внутри $$...$$.\n".
            "- Для мнемонических правил и формул запоминания используй специальный блок:\n".
            "  > [!MNEMONIC] Название мнемонического правила\n".
            "  > [Компонент 1] · [Компонент 2] · [Компонент 3]\n".
            "- Для типичных ошибок и ловушек используй:\n".
            "  > [!WARNING] Внимание / Ловушка\n".
            "  > Описание типичной ошибки или ловушки\n".
            "- Для практических лайфхаков используй:\n".
            "  > [!TIP] Методический совет\n".
            "  > Описание совета\n".
            '- Структурируй ответ подзаголовками (###), списками и жирными акцентами (**акцент**).';

        $systemInstruction = "{$baseRole}\n\nИнструкции по стилю:\n- {$toneGuide}\n- {$lengthGuide}\n\nИнструкции по оформлению:\n{$formatting}";

        if ($studentId > 0) {
            try {
                $targetStudent = User::find($studentId);
                if ($targetStudent) {
                    $systemInstruction .= "\n\n### Контекст ученика для текущего диалога:\n".
                        "- Ученик: {$targetStudent->name}\n".
                        "- Контекст: диалог и генерация материалов ориентированы на индивидуальную работу с этим учеником.";
                }
            } catch (\Throwable $e) {}
        } else {
            $systemInstruction .= "\n\n- Контекст ученика: свободный диалог / общий режим без привязки к конкретному ученику.";
        }

        $platformContext = $this->buildUserPlatformContext($user);
        if (! empty($platformContext)) {
            $systemInstruction .= "\n\n{$platformContext}";
        }

        if (! empty($customPrompt)) {
            $systemInstruction .= "\n\nДополнительное указание от пользователя: {$customPrompt}";
        }

        return $systemInstruction;
    }

    /**
     * Build contextual knowledge about user's current platform data
     */
    protected function buildUserPlatformContext(User $user): string
    {
        $now = now()->setTimezone('Europe/Minsk');
        $lines = [
            '### Актуальный контекст платформы Edusfera.by:',
            '- Текущее время (Минск): '.$now->format('d.m.Y H:i').' ('.$now->translatedFormat('l').')',
        ];

        try {
            if ($user->isTutor()) {
                $lines[] = '- Роль: Преподаватель (Репетитор)';
                if ($user->tutorProfile) {
                    $lines[] = '- Предмет преподавателя: '.($user->tutorProfile->subject ?? 'Не указан');
                    if ($user->tutorProfile->hourly_rate) {
                        $lines[] = '- Ставка за урок: '.$user->tutorProfile->hourly_rate.' BYN';
                    }
                }

                $nextLessons = Lesson::where('tutor_id', $user->id)
                    ->where('start_time', '>=', $now)
                    ->whereIn('status', [Lesson::STATUS_CONFIRMED, Lesson::STATUS_PENDING])
                    ->orderBy('start_time')
                    ->with('student')
                    ->take(3)
                    ->get();

                if ($nextLessons->isNotEmpty()) {
                    $lessonDescriptions = [];
                    foreach ($nextLessons as $ls) {
                        $studentName = $ls->student?->name ?? 'Ученик';
                        $lessonDescriptions[] = $ls->start_time->setTimezone('Europe/Minsk')->format('d.m в H:i')." с {$studentName} (".($ls->notes ?: 'без темы').')';
                    }
                    $lines[] = '- Ближайшие уроки репетитора: '.implode('; ', $lessonDescriptions);
                } else {
                    $lines[] = '- Ближайшие уроки: Нет запланированных уроков на ближайшее время.';
                }

                $weekCount = Lesson::where('tutor_id', $user->id)
                    ->whereBetween('start_time', [$now->copy()->startOfWeek(), $now->copy()->endOfWeek()])
                    ->whereIn('status', [Lesson::STATUS_CONFIRMED, Lesson::STATUS_PENDING])
                    ->count();
                $lines[] = "- Количество запланированных уроков на этой неделе: {$weekCount}";
            } elseif ($user->isStudent()) {
                $lines[] = '- Роль: Ученик';

                $nextLessons = Lesson::where('student_id', $user->id)
                    ->where('start_time', '>=', $now)
                    ->whereIn('status', [Lesson::STATUS_CONFIRMED, Lesson::STATUS_PENDING])
                    ->orderBy('start_time')
                    ->with('tutor.tutorProfile')
                    ->take(3)
                    ->get();

                if ($nextLessons->isNotEmpty()) {
                    $lessonDescriptions = [];
                    foreach ($nextLessons as $ls) {
                        $tutorName = $ls->tutor?->name ?? 'Преподаватель';
                        $subj = $ls->tutor?->tutorProfile?->subject ?? 'Занятие';
                        $noteStr = $ls->notes ? ", тема: {$ls->notes}" : '';
                        $lessonDescriptions[] = $ls->start_time->setTimezone('Europe/Minsk')->format('d.m в H:i')." ({$subj}, репетитор {$tutorName}{$noteStr})";
                    }
                    $lines[] = '- Ближайшие запланированные занятия: '.implode('; ', $lessonDescriptions);
                } else {
                    $lines[] = '- Ближайшие занятия: Нет запланированных занятий.';
                }

                $pendingHw = HomeworkAssignment::where('student_id', $user->id)
                    ->where('status', '!=', 'completed')
                    ->orderBy('due_at')
                    ->take(3)
                    ->get();

                if ($pendingHw->isNotEmpty()) {
                    $hwList = [];
                    foreach ($pendingHw as $hw) {
                        $due = $hw->due_at ? 'до '.$hw->due_at->format('d.m') : 'без дедлайна';
                        $hwList[] = "«{$hw->title}» ({$due})";
                    }
                    $lines[] = '- Невыполненные домашние задания: '.implode(', ', $hwList);
                } else {
                    $lines[] = '- Домашние задания: Все текущие ДЗ выполнены или не назначены.';
                }

                $goals = StudentGoal::where('student_id', $user->id)
                    ->where('status', 'active')
                    ->get();

                if ($goals->isNotEmpty()) {
                    $goalsList = [];
                    foreach ($goals as $g) {
                        $goalsList[] = "{$g->subject} ({$g->exam_type}: цель {$g->target_score} б.)";
                    }
                    $lines[] = '- Активные цели ЦТ/ЦЭ: '.implode('; ', $goalsList);
                }
            } elseif ($user->isParent()) {
                $lines[] = '- Роль: Родитель';

                $nextLessons = Lesson::where('parent_id', $user->id)
                    ->where('start_time', '>=', $now)
                    ->whereIn('status', [Lesson::STATUS_CONFIRMED, Lesson::STATUS_PENDING])
                    ->orderBy('start_time')
                    ->with('tutor.tutorProfile', 'student')
                    ->take(3)
                    ->get();

                if ($nextLessons->isNotEmpty()) {
                    $lessonDescriptions = [];
                    foreach ($nextLessons as $ls) {
                        $childName = $ls->student?->name ?? 'Ребенок';
                        $tutorName = $ls->tutor?->name ?? 'Преподаватель';
                        $subj = $ls->tutor?->tutorProfile?->subject ?? 'Занятие';
                        $noteStr = $ls->notes ? ", тема: {$ls->notes}" : '';
                        $lessonDescriptions[] = $ls->start_time->setTimezone('Europe/Minsk')->format('d.m в H:i')." ({$subj} для {$childName}, репетитор {$tutorName}{$noteStr})";
                    }
                    $lines[] = '- Ближайшие запланированные занятия детей: '.implode('; ', $lessonDescriptions);
                } else {
                    $lines[] = '- Ближайшие занятия детей: Нет запланированных занятий.';
                }

                $childIds = Lesson::where('parent_id', $user->id)
                    ->pluck('student_id')
                    ->filter()
                    ->unique();

                if ($childIds->isNotEmpty()) {
                    $pendingHw = HomeworkAssignment::whereIn('student_id', $childIds)
                        ->where('status', '!=', 'completed')
                        ->orderBy('due_at')
                        ->take(3)
                        ->get();

                    if ($pendingHw->isNotEmpty()) {
                        $hwList = [];
                        foreach ($pendingHw as $hw) {
                            $due = $hw->due_at ? 'до '.$hw->due_at->format('d.m') : 'без дедлайна';
                            $hwList[] = "«{$hw->title}» ({$due})";
                        }
                        $lines[] = '- Активные домашние задания детей: '.implode(', ', $hwList);
                    }

                    $goals = StudentGoal::whereIn('student_id', $childIds)
                        ->where('status', 'active')
                        ->get();

                    if ($goals->isNotEmpty()) {
                        $goalsList = [];
                        foreach ($goals as $g) {
                            $goalsList[] = "{$g->subject} ({$g->exam_type}: цель {$g->target_score} б., текущий {$g->current_score} б.)";
                        }
                        $lines[] = '- Цели подготовки детей: '.implode('; ', $goalsList);
                    }
                }
            }

            $lines[] = 'Если пользователь спрашивает о своем расписании, следующем уроке или домашних заданиях, отвечай на основании этих фактов.';
        } catch (\Throwable $e) {
            // Silently fall back if DB is unavailable
        }

        return implode("\n", $lines);
    }

    /**
     * Step-by-step task breakdown and solution with RIKZ trap analysis
     */
    public function solveTask(Request $request, GeminiService $gemini): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['success' => false, 'error' => 'Требуется авторизация'], 401);
        }

        if (! $user->isAdmin() && ! app(SubscriptionFeatureGate::class)->canUseAiTools($user)) {
            return response()->json([
                'success' => false,
                'error' => 'ИИ-решатель задач доступен на тарифе Pro и в бесплатном 14-дневном периоде.',
                'upgrade_required' => true,
            ], 403);
        }

        $validated = $request->validate([
            'task' => ['required', 'string', 'max:4000'],
            'subject' => ['nullable', 'string', 'max:100'],
            'student_solution' => ['nullable', 'string', 'max:3000'],
        ]);

        $task = trim($validated['task']);
        $subject = trim($validated['subject'] ?? 'Математика');
        $studentSolution = trim((string) ($validated['student_solution'] ?? ''));

        $prompt = "Ты — опытнейший преподаватель и эксперт по предмету «{$subject}».\n".
            "Разбери и реши следующую задачу:\n\n".
            "УСЛОВИЕ ЗАДАЧИ:\n{$task}\n\n";

        if ($studentSolution !== '') {
            $prompt .= "ПРЕДВАРИТЕЛЬНОЕ РЕШЕНИЕ УЧЕНИКА:\n{$studentSolution}\n".
                "ОБЯЗАТЕЛЬНО: Проанализируй решение ученика, укажи, верное ли оно, и где допущена ошибка (если есть).\n\n";
        }

        $prompt .= "ОБЯЗАТЕЛЬНАЯ СТРУКТУРА РАЗБОРА:\n".
            "1. **Краткий анализ условия и ОДЗ/ограничений**.\n".
            "2. **Пошаговое математическое решение** с формулами в LaTeX ($...$ и $$...$$).\n".
            "3. **Итоговый ответ**.\n".
            "4. **Типовая ловушка** (на чем ошибаются абитуриенты в этом прототипе ЦТ/ЦЭ).\n".
            '5. **Методический совет**: как решить быстрее или проверить ответ обратным действием.';

        $system = 'Ты ведущий преподаватель-аналитик Edusfera.by. Твой стиль доказательный, ясный, с идеальным оформлением LaTeX формул и Markdown.';

        try {
            $result = $gemini->generateText($prompt, $system);
            if (empty(trim($result))) {
                return response()->json(['success' => false, 'error' => 'Не удалось получить ответ модели'], 502);
            }

            return response()->json([
                'success' => true,
                'result' => $result,
            ]);
        } catch (\Throwable $e) {
            Log::error('AI solve task error: '.$e->getMessage(), ['user_id' => $user->id]);

            return response()->json(['success' => false, 'error' => 'Ошибка разбора задачи: '.$e->getMessage()], 500);
        }
    }

    /**
     * Generate structured lesson plan with pedagogical timing, theory, and exercises
     */
    public function generateLessonPlan(Request $request, GeminiService $gemini): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['success' => false, 'error' => 'Требуется авторизация'], 401);
        }

        if (! $user->isAdmin() && ! app(SubscriptionFeatureGate::class)->canUseAiTools($user)) {
            return response()->json([
                'success' => false,
                'error' => 'ИИ-генератор конспектов доступен на тарифе Pro и в бесплатном 14-дневном периоде.',
                'upgrade_required' => true,
            ], 403);
        }

        $validated = $request->validate([
            'subject' => ['nullable', 'string', 'max:100'],
            'grade' => ['nullable', 'string', 'max:150'],
            'topic' => ['required', 'string', 'max:300'],
            'duration' => ['nullable', 'string', 'max:50'],
            'goal' => ['nullable', 'string', 'max:300'],
            'focus' => ['nullable', 'string', 'max:150'],
        ]);

        $subject = trim($validated['subject'] ?? 'Математика');
        $grade = trim($validated['grade'] ?? '11 класс (подготовка к ЦТ/ЦЭ)');
        $topic = trim($validated['topic']);
        $duration = trim($validated['duration'] ?? '60 минут');
        $goal = trim($validated['goal'] ?? '');
        $focus = trim($validated['focus'] ?? 'Практика ЦТ/ЦЭ и разбор ловушек');

        $prompt = "Ты — опытнейший методист и преподаватель Edusfera.by.\n".
            "Составь подробный, готовый к проведению технологический конспект занятия для репетитора.\n\n".
            "ПАРАМЕТРЫ ЗАНЯТИЯ:\n".
            "- Предмет: {$subject}\n".
            "- Аудитория / Уровень: {$grade}\n".
            "- Тема занятия: {$topic}\n".
            "- Длительность: {$duration}\n".
            "- Фокус урока: {$focus}\n".
            ($goal ? "- Ключевая цель: {$goal}\n" : '').
            "\nОБЯЗАТЕЛЬНАЯ СТРУКТУРА КОНСПЕКТА:\n".
            "1. **Цели и планируемый результат урока** (образовательные, развивающие, критерии успеха ученика).\n".
            "2. **Разминка и блиц-проверка (5-7 мин)**: 2-3 экспресс-вопроса на понимание базы.\n".
            "3. **Теоретический блок и наглядные алгоритмы**: ключевые свойства, формулы, правила и мнемонические подсказки.\n".
            "4. **Практический блок (с подробными решениями)**: 3-4 разноуровневые задачи (от базовой до уровня ЦТ/ЦЭ), обязательное указание *типичной ошибки / экзаменационной ловушки*.\n".
            "5. **Рефлексия и фиксация выводов (5 мин)**: контрольные вопросы для самопроверки ученика.\n".
            "6. **Домашнее задание**: 3 номера для закрепления с краткими ответами для проверки.\n\n".
            "ПРАВИЛА ОФОРМЛЕНИЯ:\n".
            "- Все математические и физические формулы оформляй строго в LaTeX: встроенные формулы внутри $ ... $, а отдельные крупные формулы внутри $$ ... $$.\n".
            '- Используй заголовки (###), списки и жирные акценты. Разделяй ключевые блоки разделителями `---`.';

        $system = 'Ты ведущий педагогический методист Edusfera.by. Твой стиль — структурированный, прикладной, живой и точный. Всегда используй правильный синтаксис LaTeX для формул ($...$ и $$...$$) и Markdown.';

        try {
            $result = $gemini->generateText($prompt, $system);
            if (empty(trim($result))) {
                return response()->json(['success' => false, 'error' => 'Не удалось получить ответ модели'], 502);
            }

            return response()->json([
                'success' => true,
                'result' => $result,
            ]);
        } catch (\Throwable $e) {
            Log::error('AI lesson plan generation error: '.$e->getMessage(), ['user_id' => $user->id]);

            return response()->json(['success' => false, 'error' => 'Ошибка генерации: '.$e->getMessage()], 500);
        }
    }

    /**
     * Generate quiz/tasks in Belarus CT/CE format
     */
    public function generateQuiz(Request $request, GeminiService $gemini): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['success' => false, 'error' => 'Требуется авторизация'], 401);
        }

        if (! $user->isAdmin() && ! app(SubscriptionFeatureGate::class)->canUseAiTools($user)) {
            return response()->json([
                'success' => false,
                'error' => 'ИИ-генератор тестов доступен на тарифе Pro и в бесплатном 14-дневном периоде.',
                'upgrade_required' => true,
            ], 403);
        }

        $validated = $request->validate([
            'subject' => ['nullable', 'string', 'max:100'],
            'topic' => ['required', 'string', 'max:300'],
            'count' => ['nullable', 'integer', 'min:1', 'max:15'],
            'difficulty' => ['nullable', 'string', 'max:150'],
            'format' => ['nullable', 'string', 'max:100'],
        ]);

        $subject = trim($validated['subject'] ?? 'Математика');
        $topic = trim($validated['topic']);
        $count = (int) ($validated['count'] ?? 5);
        $difficulty = trim($validated['difficulty'] ?? 'Средний (ЦТ/ЦЭ 2026, часть А и Б)');
        $format = trim($validated['format'] ?? 'Смешанный (Часть А с выбором и Часть Б с кратким числовым ответом)');

        $prompt = "Ты — эксперт-составитель тестовых материалов Беларуси по предмету «{$subject}».\n".
            "Составь качественный блок проверочных заданий ({$count} шт.) по теме: «{$topic}».\n".
            "Сложность: {$difficulty}.\n".
            "Формат заданий: {$format}.\n\n".
            "ДЛЯ КАЖДОГО ЗАДАНИЯ СТРОГО СФОРМИРУЙ:\n".
            "1. **Условие задачи** (четкая и однозначная формулировка в стиле ЦТ/ЦЭ 2026. Для части А — 4-5 вариантов ответа, для части Б — требование дать числовой ответ).\n".
            "2. **Правильный ответ**.\n".
            "3. **Пошаговое решение**.\n".
            "4. **Типовая ловушка** (на чем обычно ошибаются 80% учеников: не учли ОДЗ, потеряли корень, спутали знаки и т.д.).\n\n".
            "ПРАВИЛА ОФОРМЛЕНИЯ:\n".
            "- Все формулы строго в LaTeX ($...$ и $$...$$).\n".
            "- Четкая нумерация задач: ### Задание 1, ### Задание 2 и т.д.\n".
            '- В конце добавь краткую сводную таблицу ответов для быстрой проверки репетитором.';

        $system = 'Ты эксперт по составлению тестовых материалов ЦТ и ЦЭ 2026. Оформляй в аккуратном структурированном Markdown с LaTeX формулами.';

        try {
            $result = $gemini->generateText($prompt, $system);
            if (empty(trim($result))) {
                return response()->json(['success' => false, 'error' => 'Не удалось получить ответ модели'], 502);
            }

            return response()->json([
                'success' => true,
                'result' => $result,
            ]);
        } catch (\Throwable $e) {
            Log::error('AI quiz generation error: '.$e->getMessage(), ['user_id' => $user->id]);

            return response()->json(['success' => false, 'error' => 'Ошибка генерации: '.$e->getMessage()], 500);
        }
    }

    /**
     * MAYA Step 2: One-click AI homework generation & instant delivery for finished lessons
     */
    public function generateQuickHomework(Request $request, GeminiService $gemini, \App\Services\ChatService $chatService): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['success' => false, 'error' => 'Требуется авторизация'], 401);
        }

        if (! $user->isAdmin() && ! app(SubscriptionFeatureGate::class)->canUseAiTools($user)) {
            return response()->json([
                'success' => false,
                'error' => 'ИИ-генератор домашних заданий доступен на тарифе Pro и в бесплатном 14-дневном периоде.',
                'upgrade_required' => true,
            ], 403);
        }

        $validated = $request->validate([
            'lesson_id' => ['nullable', 'integer'],
            'student_id' => ['nullable', 'integer'],
            'custom_topic' => ['nullable', 'string', 'max:255'],
            'send_to_chat' => ['nullable', 'boolean'],
            'edited_text' => ['nullable', 'string', 'max:10000'],
        ]);

        $lesson = null;
        if (! empty($validated['lesson_id'])) {
            $lesson = Lesson::with(['student', 'tutor'])->find($validated['lesson_id']);
        } elseif (! empty($validated['student_id'])) {
            $lesson = Lesson::with(['student', 'tutor'])
                ->where('tutor_id', $user->id)
                ->where('student_id', $validated['student_id'])
                ->latest('id')
                ->first();
        }

        if (! $lesson) {
            return response()->json(['success' => false, 'error' => 'Урок не найден'], 404);
        }

        // Only the tutor of this lesson or admin can trigger it
        if (! $user->isAdmin() && $lesson->tutor_id !== $user->id) {
            return response()->json(['success' => false, 'error' => 'Доступ запрещён'], 403);
        }

        $topic = trim((string) ($validated['custom_topic'] ?? ''));
        if ($topic === '') {
            $topic = $lesson->subject ?: 'Пройденная тема';
        }

        $tutorName = $lesson->tutor?->name ?? 'Преподаватель';
        $studentName = $lesson->student?->name ?? 'Ученик';

        // Step 2b: Save and send to chat if text is provided
        if (! empty($validated['send_to_chat']) && ! empty($validated['edited_text'])) {
            $finalText = trim((string) $validated['edited_text']);

            if ($lesson->student_id) {
                // 1. Create HomeworkAssignment
                HomeworkAssignment::create([
                    'lesson_id' => $lesson->id,
                    'tutor_id' => $lesson->tutor_id,
                    'student_id' => $lesson->student_id,
                    'title' => mb_substr("ДЗ: {$topic}", 0, 160),
                    'instructions' => $finalText,
                    'source' => 'maya_quick_homework',
                    'status' => 'assigned',
                    'assigned_at' => now('UTC'),
                    'due_at' => now('UTC')->addDays(3)->setTime(23, 59, 0),
                ]);

                // 2. Deliver into conversation
                $conversation = $chatService->getOrCreateConversation($lesson->tutor_id, $lesson->student_id, $lesson->id);
                $msgText = "📚 Домашнее задание по теме «{$topic}»:\n\n".$finalText;
                $chatService->sendMessage($conversation->id, $lesson->tutor_id, $msgText);
            }

            return response()->json([
                'success' => true,
                'message' => 'Домашнее задание успешно отправлено ученику в чат!',
                'sent_to_chat' => true,
            ]);
        }

        // Step 2a: Generate 5 targeted questions via Gemini
        $prompt = "Ты — опытный репетитор и методист по предмету «{$lesson->subject}».\n".
            "Только что завершился урок для ученика {$studentName}.\n".
            "Тема занятия: «{$topic}».\n\n".
            "Сгенерируй структурированное домашнее задание ровно из 5 задач по пройденной теме:\n".
            "1. Задачи 1 и 2 — базовый уровень на первичное закрепление формул/понятий.\n".
            "2. Задачи 3 и 4 — средний уровень с комбинированным условием (в формате ЦТ/ЦЭ).\n".
            "3. Задача 5 — повышенной сложности со звездочкой на глубокое понимание.\n".
            "4. В самом конце краткий блок ответов для проверки репетитором.\n\n".
            "Пиши емко, аккуратно, задачи нумеруй (1, 2, 3, 4, 5). Все формулы оформляй в LaTeX.";

        $system = 'Ты персональный ИИ-методист Edusfera.by. Твой стиль — безупречная педагогическая точность, краткость и практичность.';

        try {
            $result = $gemini->generateText($prompt, $system);
            if (empty(trim($result))) {
                $result = "🎯 Домашнее задание по теме «{$topic}»:\n\n".
                    "1. Повторить теоретические положения и базовые формулы по теме «{$topic}».\n".
                    "2. Решить упражнение №1 на прямое применение изученного правила.\n".
                    "3. Выполнить 2 практических задания средней сложности.\n".
                    "4. Найти и устранить типичную ошибку в тестовом прототипе.\n".
                    "5. Задача повышенной сложности: применить пройденный метод в нестандартном контексте.\n\n".
                    "Краткие ответы и решения обсудим на следующем уроке.";
            }

            return response()->json([
                'success' => true,
                'topic' => $topic,
                'homework' => $result,
            ]);
        } catch (\Throwable $e) {
            Log::error('Quick homework generation error: '.$e->getMessage(), ['lesson_id' => $lesson->id]);

            return response()->json([
                'success' => false,
                'error' => 'Ошибка генерации ДЗ: '.$e->getMessage(),
            ], 500);
        }
    }
}
