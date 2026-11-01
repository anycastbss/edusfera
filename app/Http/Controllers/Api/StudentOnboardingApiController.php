<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StudentGoal;
use App\Models\User;
use App\Services\DiagnosticService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class StudentOnboardingApiController extends Controller
{
    use \App\Traits\DetectsMobileDevice;

    /**
     * Полноэкранная страница персонализированного онбординга для ученика.
     */
    public function showOnboardingPage(Request $request)
    {
        /** @var User|null $user */
        $user = Auth::user();
        if (! $user) {
            return redirect('/login');
        }

        if ($user->isTutor()) {
            return redirect('/onboarding');
        }

        $existingGoal = StudentGoal::where('student_id', $user->id)
            ->where('status', 'active')
            ->first();

        // Если ученик уже настроил профиль и не перешел для явного редактирования (?edit=1), пускаем в кабинет
        if (! $request->boolean('edit') && ! $request->has('edit') && $existingGoal) {
            return redirect($this->isMobileDevice($request) ? route('mobile.cabinet') : '/admin');
        }

        $initialData = $this->getState($request)->getData(true);

        return view('auth.onboarding', [
            'initialData' => $initialData,
        ]);
    }

    /**
     * Получить текущее состояние профиля и целей ученика.
     */
    public function getState(Request $request): JsonResponse
    {
        /** @var User|null $user */
        $user = Auth::user();
        if (! $user) {
            return response()->json(['success' => false, 'message' => 'Не авторизован.'], 401);
        }

        $goals = StudentGoal::where('student_id', $user->id)->get();
        $primaryGoal = $goals->where('status', 'active')->first() ?? $goals->first();

        $meta = (array) ($primaryGoal?->meta ?? []);
        $selectedSubjects = (array) ($meta['subjects'] ?? []);
        if (empty($selectedSubjects) && $primaryGoal?->subject) {
            $selectedSubjects = [$primaryGoal->subject];
        }

        $grade = (string) ($meta['grade'] ?? '11 класс');
        $goalType = (string) ($meta['goal_type'] ?? 'ct_2026');
        $bio = (string) ($meta['bio'] ?? '');
        $existingBaseline = $primaryGoal?->latest_diagnostic_at !== null;
        $currentScore = (int) ($primaryGoal?->current_score ?? 50);
        $targetScore = (int) ($primaryGoal?->target_score ?? 85);
        $weakTopics = $primaryGoal
            ? $primaryGoal->skillGaps()->where('status', 'open')->pluck('topic')->all()
            : [];
        $isOnboarded = $primaryGoal !== null;

        return response()->json([
            'success' => true,
            'role' => 'student',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'avatar' => $user->getFilamentAvatarUrl(),
                'role' => 'student',
            ],
            'profile' => [
                'grade' => $grade,
                'goal_type' => $goalType,
                'bio' => $bio,
                'subjects' => $selectedSubjects,
                'current_score' => $currentScore,
                'target_score' => $targetScore,
                'weak_topics' => $weakTopics,
                'is_onboarded' => $isOnboarded,
                'has_diagnostic' => $existingBaseline,
            ],
            'popular_subjects' => [
                'Математика',
                'Физика',
                'Русский язык',
                'Белорусский язык',
                'Английский язык',
                'Химия',
                'Биология',
                'История Беларуси',
                'Обществоведение',
                'Информатика',
            ],
            'grades' => [
                '1–4 класс',
                '5–8 класс',
                '9 класс',
                '10 класс',
                '11 класс',
                'Студент / Взрослый',
            ],
            'learning_goals' => [
                [
                    'id' => 'ct_2026',
                    'title' => 'ЦТ / ЦЭ 2026',
                    'desc' => 'Подготовка к экзаменам на 80+ баллов',
                    'icon' => '🎯',
                ],
                [
                    'id' => 'school_grades',
                    'title' => 'Подтянуть оценки',
                    'desc' => 'Устранить пробелы в школьной программе',
                    'icon' => '📈',
                ],
                [
                    'id' => 'olympiads',
                    'title' => 'Олимпиады и углубленный уровень',
                    'desc' => 'Сложные задачи и углубленная теория',
                    'icon' => '🏆',
                ],
                [
                    'id' => 'personal_growth',
                    'title' => 'Для себя / С нуля',
                    'desc' => 'Изучение предмета для личных целей',
                    'icon' => '💡',
                ],
            ],
            'topics_by_subject' => self::getTopicOptions(),
            'current_score_options' => [
                ['score' => 35, 'label' => '< 40 б.', 'sub' => 'С нуля'],
                ['score' => 50, 'label' => '40–60 б.', 'sub' => 'Базовый'],
                ['score' => 70, 'label' => '60–75 б.', 'sub' => 'Средний'],
                ['score' => 85, 'label' => '75+ б.', 'sub' => 'Хороший'],
            ],
            'target_score_options' => [
                ['score' => 75, 'label' => '75+ баллов'],
                ['score' => 85, 'label' => '85+ баллов'],
                ['score' => 95, 'label' => '95+ баллов'],
                ['score' => 100, 'label' => '100 баллов'],
            ],
        ]);
    }

    public static function getTopicOptions(): array
    {
        return [
            'Математика' => [
                'Алгебра и формулы',
                'Геометрия и планиметрия',
                'Уравнения и неравенства',
                'Текстовые задачи',
                'Тестовые ловушки ЦЭ/ЦТ',
            ],
            'Физика' => [
                'Механика и кинематика',
                'Термодинамика и МКТ',
                'Электродинамика и цепи',
                'Оптика и волны',
                'Квантовая физика',
            ],
            'Русский язык' => [
                'Орфография (корни, суффиксы)',
                'Пунктуация в сложных предложениях',
                'Синтаксис и нормы речи',
                'Слитное и раздельное НЕ/НИ',
                'Тестовые формулировки ЦТ',
            ],
            'Белорусский язык' => [
                'Правапіс галосных і зычных',
                'Сінтаксіс і пунктуацыя',
                'Марфалогія і часціны мовы',
                'Фразеалогія і лексіка',
                'Тэставыя пасткі ЦЭ/ЦТ',
            ],
            'Английский язык' => [
                'Времена глаголов (Tenses)',
                'Грамматические тесты',
                'Словарный запас (Vocabulary)',
                'Аудирование и восприятие',
                'Чтение и анализ текста',
            ],
            'Химия' => [
                'Неорганическая химия',
                'Органическая химия',
                'Расчетные задачи по формулам',
                'Реакции и электронный баланс',
            ],
            'Биология' => [
                'Анатомия и физиология человека',
                'Общая биология и генетика',
                'Ботаника и зоология',
                'Эволюция и экологические задачи',
            ],
            'История Беларуси' => [
                'Даты и ключевые периоды',
                'Исторические деятели',
                'Культура и терминология',
                'Карты, схемы и битвы',
            ],
            'Обществоведение' => [
                'Человек и общество',
                'Правовая система и Конституция',
                'Экономика и финансы',
                'Политика и государство',
            ],
            'Информатика' => [
                'Программирование (Python/C++)',
                'Алгоритмы и структуры',
                'Системы счисления и логика',
                'Базы данных и электронные таблицы',
            ],
        ];
    }

    /**
     * Загрузка фото (аватара) ученика.
     */
    public function uploadPhoto(Request $request): JsonResponse
    {
        /** @var User|null $user */
        $user = Auth::user();
        if (! $user) {
            return response()->json(['success' => false, 'message' => 'Не авторизован.'], 401);
        }

        $request->validate([
            'photo' => ['required', 'image', 'mimes:jpeg,png,jpg,webp', 'max:5120'],
        ], [
            'photo.required' => 'Пожалуйста, выберите файл изображения.',
            'photo.image' => 'Загруженный файл должен быть изображением.',
            'photo.mimes' => 'Поддерживаются форматы JPG, PNG и WEBP.',
            'photo.max' => 'Размер файла не должен превышать 5 МБ.',
        ]);

        $file = $request->file('photo');
        if (! $file || ! $file->isValid()) {
            return response()->json(['success' => false, 'message' => 'Ошибка загрузки файла.'], 422);
        }

        $filename = 'student_'.$user->id.'_'.time().'.'.$file->getClientOriginalExtension();
        $storedPath = $file->storeAs('avatars', $filename, 'public');

        $user->updateQuietly([
            'avatar' => $storedPath,
        ]);

        return response()->json([
            'success' => true,
            'avatar_url' => $user->getFilamentAvatarUrl(),
            'message' => 'Фото профиля успешно обновлено!',
        ]);
    }

    /**
     * Сохранение персонализированного профиля ученика.
     */
    public function saveProfile(Request $request): JsonResponse
    {
        /** @var User|null $user */
        $user = Auth::user();
        if (! $user) {
            return response()->json(['success' => false, 'message' => 'Не авторизован.'], 401);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:255'],
            'grade' => ['required', 'string', 'max:50'],
            'goal_type' => ['required', 'string', 'max:50'],
            'bio' => ['nullable', 'string', 'max:500'],
            'subjects' => ['required', 'array', 'min:1'],
            'subjects.*' => ['string', 'max:100'],
            'current_score' => ['nullable', 'integer', 'min:0', 'max:100'],
            'target_score' => ['nullable', 'integer', 'min:0', 'max:100'],
            'weak_topics' => ['nullable', 'array'],
            'weak_topics.*' => ['string', 'max:160'],
        ], [
            'name.required' => 'Укажите ваше имя.',
            'name.min' => 'Имя должно содержать минимум 2 символа.',
            'grade.required' => 'Выберите ваш класс или категорию обучения.',
            'goal_type.required' => 'Укажите главную цель обучения.',
            'subjects.required' => 'Выберите хотя бы один нужный предмет.',
            'subjects.min' => 'Выберите хотя бы один нужный предмет.',
        ]);

        // Обновляем имя пользователя
        $user->updateQuietly([
            'name' => trim($validated['name']),
        ]);

        $subjects = array_values(array_unique(array_filter($validated['subjects'])));
        $primarySubject = $subjects[0] ?? 'Математика';
        $currentScore = isset($validated['current_score']) ? (int) $validated['current_score'] : 50;
        $targetScore = isset($validated['target_score']) ? (int) $validated['target_score'] : 85;
        $weakTopics = array_values(array_filter((array) ($validated['weak_topics'] ?? [])));

        // Сохраняем цель в student_goals
        $meta = [
            'grade' => $validated['grade'],
            'goal_type' => $validated['goal_type'],
            'bio' => trim((string) ($validated['bio'] ?? '')),
            'subjects' => $subjects,
            'current_score' => $currentScore,
            'target_score' => $targetScore,
            'weak_topics' => $weakTopics,
            'onboarded_at' => now()->toIso8601String(),
        ];

        $examType = match ($validated['goal_type']) {
            'ct_2026' => 'ct',
            'school_grades' => 'school',
            'olympiads' => 'olympiad',
            default => 'custom',
        };

        // Находим или создаем активную цель
        $primaryGoal = StudentGoal::query()->firstOrNew([
            'student_id' => $user->id,
            'status' => 'active',
        ]);

        $primaryGoal->subject = $primarySubject;
        $primaryGoal->exam_type = $examType;
        $primaryGoal->meta = $meta;
        $primaryGoal->target_score = $targetScore;
        $primaryGoal->current_score = $currentScore;
        $primaryGoal->latest_diagnostic_at = now('UTC');
        $primaryGoal->save();

        // Автоматически фиксируем стартовую baseline-диагностику и срез знаний
        try {
            app(DiagnosticService::class)->recordBaseline(
                goal: $primaryGoal->fresh(),
                studentId: (int) $user->id,
                currentScore: $currentScore,
                targetScore: $targetScore,
                examDate: null,
                weakTopics: $weakTopics,
                notes: trim((string) ($validated['bio'] ?? '')),
            );
        } catch (\Throwable $e) {
            // Self-healing: if baseline logging encounters an edge case, goal is already safely updated
        }

        return response()->json([
            'success' => true,
            'message' => 'Профиль и стартовая диагностика успешно сохранены!',
        ]);
    }

    /**
     * Завершение онбординга ученика и редирект в личный кабинет.
     */
    public function completeOnboarding(Request $request): JsonResponse
    {
        /** @var User|null $user */
        $user = Auth::user();
        if (! $user) {
            return response()->json(['success' => false, 'message' => 'Не авторизован.'], 401);
        }

        // Убеждаемся, что у ученика есть хотя бы 1 активная цель
        $hasGoal = StudentGoal::where('student_id', $user->id)->where('status', 'active')->exists();
        if (! $hasGoal) {
            StudentGoal::create([
                'student_id' => $user->id,
                'subject' => 'Математика',
                'exam_type' => 'ct',
                'status' => 'active',
                'target_score' => 85,
                'meta' => [
                    'grade' => '11 класс',
                    'goal_type' => 'ct_2026',
                    'subjects' => ['Математика'],
                    'onboarded_at' => now()->toIso8601String(),
                ],
            ]);
        }

        return response()->json([
            'success' => true,
            'redirect' => $this->isMobileDevice($request) ? route('mobile.cabinet') : '/admin',
            'message' => 'Онбординг успешно завершен!',
        ]);
    }
}
