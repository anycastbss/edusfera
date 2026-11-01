<?php

declare(strict_types=1);

namespace App\Filament\Resources\LessonResource\Pages;

use App\Enums\UserRole;
use App\Filament\Resources\LessonResource;
use App\Filament\Resources\LessonResource\Widgets\LessonOverviewWidget;
use App\Models\Lesson;
use App\Models\User;
use Carbon\Carbon;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ListLessons extends ListRecords
{
    protected static string $resource = LessonResource::class;

    protected static string $view = 'filament.resources.lesson-resource.pages.list-lessons';

    public string $viewMode = 'calendar';

    public ?string $currentWeekStart = null;

    public ?int $selectedCalendarLessonId = null;

    public ?string $quickPrepPlan = null;

    public bool $isGeneratingPrep = false;

    public ?int $prepLessonId = null;

    public function mount(): void
    {
        parent::mount();

        $user = auth()->user();
        $this->viewMode = ($user?->isTutor() ?? false) ? 'calendar' : 'table';

        $displayTz = config('booking.display_timezone', 'Europe/Minsk');
        $this->currentWeekStart = Carbon::now($displayTz)->startOfWeek(Carbon::MONDAY)->format('Y-m-d');
    }

    public function getTitle(): string
    {
        return LessonResource::getNavigationLabel();
    }

    public function setViewMode(string $mode): void
    {
        if (in_array($mode, ['calendar', 'table', 'agenda'], true)) {
            $this->viewMode = $mode;
        }
    }

    public function previousWeek(): void
    {
        $displayTz = config('booking.display_timezone', 'Europe/Minsk');
        $this->currentWeekStart = Carbon::parse($this->currentWeekStart, $displayTz)
            ->subWeek()
            ->startOfWeek(Carbon::MONDAY)
            ->format('Y-m-d');
    }

    public function nextWeek(): void
    {
        $displayTz = config('booking.display_timezone', 'Europe/Minsk');
        $this->currentWeekStart = Carbon::parse($this->currentWeekStart, $displayTz)
            ->addWeek()
            ->startOfWeek(Carbon::MONDAY)
            ->format('Y-m-d');
    }

    public function goToToday(): void
    {
        $displayTz = config('booking.display_timezone', 'Europe/Minsk');
        $this->currentWeekStart = Carbon::now($displayTz)->startOfWeek(Carbon::MONDAY)->format('Y-m-d');
    }

    public ?string $prepTopic = null;

    public function openCalendarLesson(int $lessonId): void
    {
        $this->selectedCalendarLessonId = $lessonId;
        $this->quickPrepPlan = null;
        $this->prepLessonId = null;
        $lesson = Lesson::with(['student', 'tutor.tutorProfile'])->find($lessonId);
        if ($lesson) {
            $context = $this->detectLessonPrepContext($lesson);
            $this->prepTopic = $context['detected_topic'];
        }
    }

    public function resetPrepTopic(int $lessonId): void
    {
        $lesson = Lesson::with(['student', 'tutor.tutorProfile'])->find($lessonId);
        if ($lesson) {
            $context = $this->detectLessonPrepContext($lesson);
            $this->prepTopic = $context['detected_topic'];
        }
    }

    public function detectLessonPrepContext(Lesson $lesson): array
    {
        $subject = trim((string) ($lesson->subject ?: ''));
        if (empty($subject)) {
            $tutorProfile = $lesson->tutor?->tutorProfile;
            if ($tutorProfile && ! empty($tutorProfile->subjects)) {
                $subjects = is_array($tutorProfile->subjects) ? $tutorProfile->subjects : json_decode((string) $tutorProfile->subjects, true);
                if (is_array($subjects) && count($subjects) > 0) {
                    $subject = (string) $subjects[0];
                }
            }
        }
        if (empty($subject)) {
            $subject = 'Математика';
        }

        // Previous completed lesson with this student
        $previousLesson = Lesson::query()
            ->where('student_id', $lesson->student_id)
            ->where('tutor_id', $lesson->tutor_id)
            ->where('status', Lesson::STATUS_COMPLETED)
            ->where('id', '!=', $lesson->id)
            ->latest('end_time')
            ->first();

        // Calculate lesson sequence number with this student
        $lessonNumber = Lesson::query()
            ->where('student_id', $lesson->student_id)
            ->where('tutor_id', $lesson->tutor_id)
            ->where('status', '!=', Lesson::STATUS_CANCELLED)
            ->where('start_time', '<=', $lesson->start_time)
            ->count();
        if ($lessonNumber === 0) {
            $lessonNumber = 1;
        }

        // Check if notes contain specific topic
        $notesTopic = null;
        if (! empty($lesson->notes)) {
            if (preg_match('/(?:Тема|тема|Раздел|раздел|План|план)[:\s]+([^\r\n,.]+)/u', $lesson->notes, $m)) {
                $notesTopic = trim($m[1]);
            }
        }

        // Active goal & skill gaps for this student
        $activeGoal = \App\Models\StudentGoal::query()
            ->with(['skillGaps' => fn ($q) => $q->where('status', 'open')->latest('id')])
            ->where('student_id', $lesson->student_id)
            ->where('tutor_id', $lesson->tutor_id)
            ->where('status', 'active')
            ->latest('id')
            ->first();

        $topSkillGap = $activeGoal?->skillGaps->first();

        $isFirstLesson = ($lessonNumber === 1 && ! $previousLesson);

        // Determine detected topic and source
        $detectedTopic = '';
        $topicSource = '';

        if (! empty($notesTopic)) {
            $detectedTopic = $notesTopic;
            $topicSource = 'Заметки к занятию';
        } elseif ($previousLesson && ! empty($previousLesson->tutor_next_step)) {
            $detectedTopic = $previousLesson->tutor_next_step;
            $topicSource = 'Намечено на уроке #' . $previousLesson->id;
        } elseif ($isFirstLesson) {
            $detectedTopic = 'Входная диагностика и аудит знаний';
            $topicSource = 'Вводный урок (Урок №1)';
        } elseif ($previousLesson && ! empty($previousLesson->tutor_report_summary)) {
            $detectedTopic = 'Закрепление: ' . $previousLesson->tutor_report_summary;
            $topicSource = 'Итоги урока #' . $previousLesson->id;
        } elseif ($topSkillGap) {
            $detectedTopic = 'Ликвидация пробела: ' . $topSkillGap->topic;
            $topicSource = 'Пробел из диагностики';
        } else {
            $detectedTopic = 'Подготовка к ЦТ/ЦЭ 2026: базовый модуль';
            $topicSource = 'Программа подготовки';
        }

        return [
            'subject' => $subject,
            'lesson_number' => $lessonNumber,
            'is_first_lesson' => $isFirstLesson,
            'previous_lesson' => $previousLesson,
            'detected_topic' => $detectedTopic,
            'topic_source' => $topicSource,
            'previous_summary' => $previousLesson?->tutor_report_summary,
            'previous_next_step' => $previousLesson?->tutor_next_step,
            'previous_focus' => $previousLesson?->tutor_report_focus,
            'previous_homework' => $previousLesson?->tutor_homework_summary,
            'target_score' => $activeGoal?->target_score,
            'current_score' => $activeGoal?->current_score,
            'skill_gap' => $topSkillGap?->topic,
        ];
    }

    public function generateQuickPrep(int $lessonId, ?string $customTopic = null): void
    {
        $this->isGeneratingPrep = true;
        $this->prepLessonId = $lessonId;
        $lesson = Lesson::with(['student', 'tutor.tutorProfile'])->find($lessonId);

        if (! $lesson) {
            Notification::make()->title('Урок не найден')->danger()->send();
            $this->isGeneratingPrep = false;

            return;
        }

        $user = auth()->user();
        if ($user && $user->isTutor()) {
            app(\App\Domain\Subscription\Services\SubscriptionService::class)->ensureTrialStarted($user);
            $gate = app(\App\Domain\Subscription\Services\SubscriptionFeatureGate::class);
            if (! $gate->canUseAiTools($user)) {
                $this->isGeneratingPrep = false;
                Notification::make()
                    ->title('Лимит ИИ-запросов исчерпан')
                    ->body('На тарифе «Стандарт» доступно 10 ИИ-генераций в месяц. Перейдите на тариф «Частная практика» (40 BYN) для безлимитного доступа.')
                    ->warning()
                    ->persistent()
                    ->actions([
                        \Filament\Notifications\Actions\Action::make('upgrade')
                            ->button()
                            ->label('Перейти на 40 BYN')
                            ->url(route('filament.admin.pages.tutor-subscription-page')),
                    ])
                    ->send();

                return;
            }
            $gate->incrementAiGenerationUsage($user);
        }

        $context = $this->detectLessonPrepContext($lesson);
        $topic = trim((string) ($customTopic ?: ($this->prepTopic ?: $context['detected_topic'])));
        $this->prepTopic = $topic;
        $subject = $context['subject'];
        $studentName = $lesson->student?->name ?? 'Ученик';
        $tutorName = $lesson->tutor?->name ?? 'Преподаватель';

        $historyContext = '';
        if ($context['is_first_lesson']) {
            $historyContext = "Это ПЕРВЫЙ вводный урок (Стартовое занятие) с этим учеником.\n".
                "Главная цель: входная диагностика, определение базового уровня, психологический контакт и выявление пробелов по предмету {$subject}.\n";
        } elseif ($context['previous_lesson']) {
            $historyContext = "Это занятие №{$context['lesson_number']} в курсе с этим учеником.\n".
                ($context['previous_summary'] ? "- На прошлом уроке прошли: «{$context['previous_summary']}».\n" : '').
                ($context['previous_focus'] ? "- Трудности/фокус ученика: «{$context['previous_focus']}».\n" : '').
                ($context['previous_next_step'] ? "- План репетитора на текущий шаг: «{$context['previous_next_step']}».\n" : '').
                ($context['previous_homework'] ? "- Было задано ДЗ: «{$context['previous_homework']}».\n" : '');
        }

        if (! empty($context['target_score'])) {
            $historyContext .= "- Целевой балл ученика: {$context['target_score']} (текущий: ".($context['current_score'] ?? 'в процессе').").\n";
        }
        if (! empty($context['skill_gap'])) {
            $historyContext .= "- Выявленный пробел в знаниях: «{$context['skill_gap']}».\n";
        }

        $prompt = "Ты ведущий эксперт-методист образовательной платформы Edusfera.by.\n".
            "Преподаватель {$tutorName} через 10 минут начинает урок с учеником {$studentName}.\n".
            "Предмет: {$subject}. Тема текущего занятия: «{$topic}».\n\n".
            $historyContext.
            "\nСоставь конспект-шпаргалку к уроку на 1 экран (шпаргалка для преподавателя перед занятием):\n".
            "1. **⚡ Разминка (3 мин):** 3 быстрых устных вопроса на проверку понятий/формул (свяжи с темой и проверкой ДЗ).\n".
            "2. **⚠️ Ловушки РИКЗ (ЦТ/ЦЭ 2026):** 2 типичные ошибки абитуриентов в этой теме.\n".
            "3. **📝 Практический блок (3 задачи):** краткие условия 3 ключевых задач для совместного решения на интерактивной доске.\n".
            "4. **🎯 Контрольный вопрос в финале (1 мин)** для быстрой фиксации усвоения темы.\n\n".
            "Пиши емко, структурировано, формулы оформляй понятно.";

        $system = 'Ты опытный методист-наставник Edusfera.by. Твой конспект должен читаться за 30 секунд.';

        try {
            $plan = app(\App\Services\Ai\GeminiService::class)->generateText($prompt, $system);
        } catch (\Throwable $e) {
            $plan = null;
        }

        if (empty(trim((string) $plan))) {
            $plan = "### ⚡ 1. Разминка (3 мин)\n1. Базовые определения и формулы темы «{$topic}» ({$subject}).\n2. Проверка понимания ключевых условий и ограничений.\n3. Вопрос на взаимосвязь с пройденным ранее материалом.\n\n### ⚠️ 2. Ловушки РИКЗ (ЦТ/ЦЭ 2026)\n- Типовые вычислительные неточности и невнимательность в знаках.\n- Подводные камни формулировок в тестах спецификации РИКЗ.\n\n### 📝 3. Практика (3 задачи)\n1. Задача базового уровня на первичное закрепление темы.\n2. Комбинированная задача уровня части А ЦТ.\n3. Задача с логическим усложнением уровня части Б.\n\n### 🎯 4. Контрольный вопрос в финале\n- Сформулировать вывод и контрольное правило самопроверки.";
        }

        $this->quickPrepPlan = $plan;
        $this->isGeneratingPrep = false;

        Notification::make()
            ->title('Экспресс-план готов!')
            ->body("Шпаргалка по теме «{$topic}» сформирована.")
            ->success()
            ->send();
    }

    public function closeQuickPrep(): void
    {
        $this->quickPrepPlan = null;
        $this->prepLessonId = null;
    }

    public function closeCalendarLesson(): void
    {
        $this->selectedCalendarLessonId = null;
        $this->quickPrepPlan = null;
        $this->prepLessonId = null;
    }

    public function markLessonPaidDirectly(int $lessonId): void
    {
        $user = auth()->user();
        $lesson = Lesson::query()
            ->when($user?->isTutor(), fn ($q) => $q->where('tutor_id', $user->id))
            ->findOrFail($lessonId);

        $lesson->update([
            'payment_status' => Lesson::PAYMENT_PAID,
            'status' => $lesson->status === Lesson::STATUS_PENDING ? Lesson::STATUS_CONFIRMED : $lesson->status,
        ]);

        Notification::make()
            ->title('Оплата зафиксирована')
            ->body('Урок отмечен как оплаченный напрямую. Доступ в виртуальный класс открыт.')
            ->success()
            ->send();
    }

    public function exportIcs(): StreamedResponse
    {
        $user = auth()->user();
        $displayTz = config('booking.display_timezone', 'Europe/Minsk');

        $lessons = Lesson::query()
            ->with(['tutor', 'student'])
            ->when($user?->isTutor(), fn (Builder $q) => $q->where('tutor_id', $user->id))
            ->when($user && ! $user->isTutor() && ! $user->isAdmin(), fn (Builder $q) => $q->where('student_id', $user->id))
            ->where('start_time', '>=', Carbon::now('UTC')->subMonth())
            ->where('status', '!=', Lesson::STATUS_CANCELLED)
            ->get();

        $icsContent = "BEGIN:VCALENDAR\r\n";
        $icsContent .= "VERSION:2.0\r\n";
        $icsContent .= "PRODID:-//Edusfera//Calendar 1.0//RU\r\n";
        $icsContent .= "CALSCALE:GREGORIAN\r\n";
        $icsContent .= "METHOD:PUBLISH\r\n";
        $icsContent .= "X-WR-CALNAME:Edusfera Расписание\r\n";
        $icsContent .= "X-WR-TIMEZONE:{$displayTz}\r\n";

        foreach ($lessons as $lesson) {
            $dtStart = $lesson->start_time->setTimezone('UTC')->format('Ymd\THis\Z');
            $dtEnd = $lesson->end_time->setTimezone('UTC')->format('Ymd\THis\Z');
            $summary = 'Урок Edusfera: '.($user?->isTutor() ? ($lesson->student?->name ?? 'Ученик') : ($lesson->tutor?->name ?? 'Репетитор'));
            $description = 'Онлайн-урок на платформе Edusfera. Ссылка на класс: '.route('classroom.show', $lesson);

            $icsContent .= "BEGIN:VEVENT\r\n";
            $icsContent .= "UID:edusfera-lesson-{$lesson->id}@edusfera.by\r\n";
            $icsContent .= 'DTSTAMP:'.Carbon::now('UTC')->format('Ymd\THis\Z')."\r\n";
            $icsContent .= "DTSTART:{$dtStart}\r\n";
            $icsContent .= "DTEND:{$dtEnd}\r\n";
            $icsContent .= "SUMMARY:{$summary}\r\n";
            $icsContent .= "DESCRIPTION:{$description}\r\n";
            $icsContent .= 'URL:'.route('classroom.show', $lesson)."\r\n";
            $icsContent .= "STATUS:CONFIRMED\r\n";
            $icsContent .= "END:VEVENT\r\n";
        }

        $icsContent .= "END:VCALENDAR\r\n";

        return response()->streamDownload(
            function () use ($icsContent) {
                echo $icsContent;
            },
            'edusfera-schedule.ics',
            [
                'Content-Type' => 'text/calendar; charset=utf-8',
                'Content-Disposition' => 'attachment; filename="edusfera-schedule.ics"',
            ]
        );
    }

    public function getCalendarWeekDaysProperty(): array
    {
        $displayTz = config('booking.display_timezone', 'Europe/Minsk');
        $startOfWeek = Carbon::parse($this->currentWeekStart, $displayTz)->startOfWeek(Carbon::MONDAY);
        $endOfWeek = $startOfWeek->copy()->endOfWeek(Carbon::SUNDAY);
        $today = Carbon::now($displayTz);

        $user = auth()->user();

        // Load all lessons for this week in UTC with safety margin for timezone offsets
        $startUtc = $startOfWeek->copy()->subDays(2)->startOfDay()->utc();
        $endUtc = $endOfWeek->copy()->addDays(2)->endOfDay()->utc();

        $lessons = Lesson::query()
            ->with(['tutor', 'student', 'parent'])
            ->when($user?->isTutor(), fn (Builder $q) => $q->where('tutor_id', $user->id))
            ->when($user && ! $user->isTutor() && ! $user->isAdmin(), fn (Builder $q) => $q->where('student_id', $user->id))
            ->where('start_time', '>=', $startUtc)
            ->where('start_time', '<=', $endUtc)
            ->get();

        $days = [];
        $russianDayNames = [
            1 => 'ПН',
            2 => 'ВТ',
            3 => 'СР',
            4 => 'ЧТ',
            5 => 'ПТ',
            6 => 'СБ',
            7 => 'ВС',
        ];

        for ($i = 0; $i < 7; $i++) {
            $dayDate = $startOfWeek->copy()->addDays($i);
            $dayKey = $dayDate->format('Y-m-d');
            $isToday = $dayDate->isSameDay($today);

            // Filter lessons for this day in local timezone
            $dayLessons = $lessons->filter(function (Lesson $lesson) use ($dayDate, $displayTz) {
                $lessonLocal = $lesson->start_time->setTimezone($displayTz);

                return $lessonLocal->isSameDay($dayDate);
            })->map(function (Lesson $lesson) use ($displayTz) {
                $startLocal = $lesson->start_time->setTimezone($displayTz);
                $endLocal = $lesson->end_time->setTimezone($displayTz);

                // Grid runs from 07:00 to 23:00 (16 hours = 960 mins)
                $gridStartHour = 7;
                $gridEndHour = 23;
                $totalMinutes = ($gridEndHour - $gridStartHour) * 60; // 960 min

                $startMinFromGrid = ($startLocal->hour - $gridStartHour) * 60 + $startLocal->minute;
                $durationMin = max(30, (int) $lesson->duration_minutes);

                $topPercent = max(0, min(100, ($startMinFromGrid / $totalMinutes) * 100));
                $heightPercent = max(3.5, min(100 - $topPercent, ($durationMin / $totalMinutes) * 100));

                $gcalUrl = 'https://calendar.google.com/calendar/render?'.http_build_query([
                    'action' => 'TEMPLATE',
                    'text' => 'Урок: '.($lesson->student?->name ?? 'Ученик'),
                    'dates' => $lesson->start_time->setTimezone('UTC')->format('Ymd\THis\Z').'/'.$lesson->end_time->setTimezone('UTC')->format('Ymd\THis\Z'),
                    'details' => 'Edusfera онлайн-урок. Ссылка: '.route('classroom.show', $lesson),
                    'location' => route('classroom.show', $lesson),
                ]);

                return [
                    'id' => $lesson->id,
                    'record' => $lesson,
                    'title' => auth()->user()?->isTutor() ? ($lesson->student?->name ?? 'Ученик') : ($lesson->tutor?->name ?? 'Репетитор'),
                    'student_name' => $lesson->student?->name ?? 'Ученик',
                    'tutor_name' => $lesson->tutor?->name ?? 'Репетитор',
                    'time_span' => $startLocal->format('H:i').' – '.$endLocal->format('H:i'),
                    'start_formatted' => $startLocal->format('H:i'),
                    'end_formatted' => $endLocal->format('H:i'),
                    'status' => $lesson->status,
                    'status_label' => LessonResource::statusLabel($lesson->status),
                    'payment_status' => $lesson->payment_status,
                    'price' => $lesson->price,
                    'meeting_link' => $lesson->meeting_link,
                    'top_percent' => round($topPercent, 2),
                    'height_percent' => round($heightPercent, 2),
                    'top_px' => max(4, min(900, $startMinFromGrid)),
                    'height_px' => max(36, min(180, $durationMin)),
                    'gcal_url' => $gcalUrl,
                    'classroom_url' => route('classroom.show', $lesson),
                ];
            })->values();

            $days[] = [
                'date' => $dayDate,
                'date_string' => $dayKey,
                'day_name' => $russianDayNames[$dayDate->dayOfWeekIso],
                'day_number' => $dayDate->format('j'),
                'is_today' => $isToday,
                'lessons' => $dayLessons,
            ];
        }

        return $days;
    }

    public function getCurrentTimePositionProperty(): ?int
    {
        $displayTz = config('booking.display_timezone', 'Europe/Minsk');
        $now = Carbon::now($displayTz);

        $startOfWeek = Carbon::parse($this->currentWeekStart, $displayTz)->startOfWeek(Carbon::MONDAY);
        $endOfWeek = $startOfWeek->copy()->endOfWeek(Carbon::SUNDAY);

        if (! $now->between($startOfWeek, $endOfWeek)) {
            return null;
        }

        $gridStartHour = 7;
        $gridEndHour = 23;
        $totalMinutes = ($gridEndHour - $gridStartHour) * 60;

        $currentMin = ($now->hour - $gridStartHour) * 60 + $now->minute;

        if ($currentMin < 0 || $currentMin > $totalMinutes) {
            return null;
        }

        return (int) $currentMin;
    }

    public function getSelectedLessonProperty(): ?Lesson
    {
        if (! $this->selectedCalendarLessonId) {
            return null;
        }

        return Lesson::query()
            ->with(['tutor', 'student', 'parent', 'review'])
            ->find($this->selectedCalendarLessonId);
    }

    public function getTabs(): array
    {
        return [
            'upcoming' => Tab::make('Предстоящие')
                ->icon('heroicon-m-calendar-days')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query
                    ->where('end_time', '>=', now('UTC'))
                    ->whereIn('status', [Lesson::STATUS_PENDING, Lesson::STATUS_CONFIRMED])),
            'completed' => Tab::make('Завершённые')
                ->icon('heroicon-m-check-circle')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('status', Lesson::STATUS_COMPLETED)),
            'all' => Tab::make('Все уроки')
                ->icon('heroicon-m-queue-list'),
        ];
    }

    public function getDefaultActiveTab(): string|int|null
    {
        return 'upcoming';
    }

    protected function getHeaderWidgets(): array
    {
        return [
            LessonOverviewWidget::class,
        ];
    }

    protected function getHeaderActions(): array
    {
        $user = auth()->user();

        return [
            Actions\Action::make('create_lesson')
                ->label('Запланировать урок')
                ->icon('heroicon-o-plus-circle')
                ->color('primary')
                ->visible(fn (): bool => $user?->isTutor() ?? false)
                ->modalHeading('Запланировать новый урок')
                ->modalDescription('Добавьте урок в расписание для своего ученика. Оплата производится напрямую репетитору.')
                ->modalSubmitActionLabel('Создать урок')
                ->modalWidth('lg')
                ->form([
                    Forms\Components\Select::make('student_mode')
                        ->label('Ученик')
                        ->options([
                            'existing' => 'Выбрать из базы учеников',
                            'new' => '+ Добавить нового ученика',
                        ])
                        ->default('existing')
                        ->live(),
                    Forms\Components\Select::make('student_id')
                        ->label('Выберите ученика')
                        ->options(function () {
                            return User::query()
                                ->where('role', UserRole::Student)
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->all();
                        })
                        ->searchable()
                        ->visible(fn (Forms\Get $get) => ($get('student_mode') ?? 'existing') === 'existing')
                        ->required(fn (Forms\Get $get) => ($get('student_mode') ?? 'existing') === 'existing'),
                    Forms\Components\TextInput::make('new_student_name')
                        ->label('Имя ученика')
                        ->placeholder('Например, Максим')
                        ->visible(fn (Forms\Get $get) => $get('student_mode') === 'new')
                        ->required(fn (Forms\Get $get) => $get('student_mode') === 'new'),
                    Forms\Components\TextInput::make('new_student_phone')
                        ->label('Телефон ученика')
                        ->placeholder('+37529XXXXXXX')
                        ->tel()
                        ->visible(fn (Forms\Get $get) => $get('student_mode') === 'new'),
                    Forms\Components\TextInput::make('subject')
                        ->label('Предмет')
                        ->default(fn () => auth()->user()?->tutorProfile?->subjects[0] ?? 'Математика')
                        ->required(),
                    Forms\Components\DateTimePicker::make('start_time')
                        ->label('Дата и время начала')
                        ->default(now()->addHour()->startOfHour())
                        ->native(false)
                        ->required(),
                    Forms\Components\Select::make('duration_minutes')
                        ->label('Длительность')
                        ->options([
                            45 => '45 минут',
                            60 => '60 минут (1 час)',
                            90 => '90 минут (1.5 часа)',
                            120 => '120 минут (2 часа)',
                        ])
                        ->default(60)
                        ->required(),
                    Forms\Components\TextInput::make('price')
                        ->label('Стоимость урока (BYN)')
                        ->numeric()
                        ->default(fn () => (float) (auth()->user()?->tutorProfile?->price_per_hour ?? 35.00))
                        ->prefix('BYN')
                        ->required(),
                    Forms\Components\Select::make('payment_status')
                        ->label('Статус оплаты')
                        ->options([
                            Lesson::PAYMENT_UNPAID => 'Ожидает оплаты напрямую',
                            Lesson::PAYMENT_PAID => 'Оплачено напрямую репетитору',
                        ])
                        ->default(Lesson::PAYMENT_UNPAID)
                        ->required(),
                    Forms\Components\Textarea::make('notes')
                        ->label('Заметки к уроку (тема, ссылка, ДЗ)')
                        ->rows(2),
                ])
                ->action(function (array $data, Actions\Action $action) {
                    $user = auth()->user();
                    $studentId = null;

                    if ($user?->isTutor()) {
                        $gate = app(\App\Domain\Subscription\Services\SubscriptionFeatureGate::class);
                        $checkStudentId = (($data['student_mode'] ?? 'existing') === 'existing') ? (int) ($data['student_id'] ?? 0) : null;
                        if (! $gate->canAddStudent($user, $checkStudentId)) {
                            Notification::make()
                                ->title('Лимит учеников: требуется тариф «Частная практика» (40 BYN)')
                                ->body('На тарифе «Стандарт» (20 BYN) действует лимит до 5 активных учеников. Чтобы добавить нового ученика, перейдите на тариф «Частная практика» (40 BYN) с безлимитным доступом.')
                                ->warning()
                                ->persistent()
                                ->actions([
                                    \Filament\Notifications\Actions\Action::make('upgrade')
                                        ->button()
                                        ->label('Перейти на 40 BYN')
                                        ->url(route('filament.admin.pages.tutor-subscription-page')),
                                ])
                                ->send();

                            $action->redirect(route('filament.admin.pages.tutor-subscription-page'));

                            return;
                        }
                    }

                    if (($data['student_mode'] ?? 'existing') === 'new') {
                        $phone = trim($data['new_student_phone'] ?? '');
                        $name = trim($data['new_student_name'] ?? 'Ученик');
                        $email = 'student_'.time().'_'.rand(100, 999).'@edusfera.local';

                        $student = User::create([
                            'name' => $name,
                            'phone' => $phone ?: '+37529'.rand(1000000, 9999999),
                            'email' => $email,
                            'password' => bcrypt(str()->random(16)),
                            'role' => UserRole::Student,
                        ]);
                        $studentId = $student->id;
                    } else {
                        $studentId = (int) $data['student_id'];
                    }

                    $start = Carbon::parse($data['start_time']);
                    $duration = (int) $data['duration_minutes'];
                    $end = $start->copy()->addMinutes($duration);

                    $subject = trim((string) ($data['subject'] ?? ''));
                    $notes = trim((string) ($data['notes'] ?? ''));
                    $combinedNotes = $subject !== ''
                        ? ("Предмет: {$subject}".($notes !== '' ? "\n{$notes}" : ''))
                        : ($notes !== '' ? $notes : null);

                    Lesson::create([
                        'tutor_id' => $user->id,
                        'student_id' => $studentId,
                        'start_time' => $start->utc(),
                        'end_time' => $end->utc(),
                        'duration_minutes' => $duration,
                        'price' => $data['price'],
                        'platform_commission' => 0.00,
                        'net_amount' => $data['price'],
                        'status' => Lesson::STATUS_CONFIRMED,
                        'payment_status' => $data['payment_status'],
                        'package_code' => 'single',
                        'package_lessons' => 1,
                        'notes' => $combinedNotes,
                    ]);

                    Notification::make()
                        ->title('Урок запланирован!')
                        ->body("Урок добавлен на {$start->format('d.m.Y H:i')}. Ссылка на класс доступна в расписании.")
                        ->success()
                        ->send();
                }),
            Actions\Action::make('book_lesson')
                ->label('Найти репетитора')
                ->icon('heroicon-o-magnifying-glass')
                ->color('primary')
                ->url('/tutors')
                ->visible(fn (): bool => $user !== null && ($user->isStudent() || $user->isParent()) && ! app(\App\Services\WhiteLabelService::class)->isWhiteLabelActive()),
            Actions\Action::make('availability')
                ->label('Рабочие часы')
                ->icon('heroicon-o-clock')
                ->url(fn (): string => route('filament.admin.pages.tutor-availability-page'))
                ->visible(fn (): bool => $user?->isTutor() ?? false),
            Actions\Action::make('npd')
                ->label('Налог НПД (МНС)')
                ->icon('heroicon-o-document-currency-dollar')
                ->url('/admin/tutor-npd')
                ->visible(fn (): bool => $user?->isTutor() ?? false),
            Actions\Action::make('print_weekly')
                ->label('Распечатать на неделю (А4)')
                ->icon('heroicon-o-printer')
                ->color('gray')
                ->url(fn (): string => route('admin.lessons.print-weekly', ['week' => $this->currentWeekStart]))
                ->openUrlInNewTab()
                ->visible(fn (): bool => $user?->isTutor() ?? false),
            Actions\Action::make('diagnostic')
                ->label('Диагностика')
                ->icon('heroicon-o-sparkles')
                ->url('/admin/diagnostic')
                ->visible(fn (): bool => $user !== null && ($user->isStudent() || $user->isParent())),
        ];
    }
}
