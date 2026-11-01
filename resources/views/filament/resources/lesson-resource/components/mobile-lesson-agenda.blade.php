@php
    $displayTz = config('booking.display_timezone', 'Europe/Minsk');
    $days = $this->calendarWeekDays;
    $weekStartCarbon = \Carbon\Carbon::parse($this->currentWeekStart, $displayTz);
    $totalWeekLessons = collect($days)->sum(fn ($d) => count($d['lessons']));
    $isTutorUser = auth()->user()?->isTutor() ?? false;
@endphp

<div class="space-y-5">
    {{-- Верхний статус недели для пожилых пользователей --}}
    <div class="flex items-center justify-between px-1 text-sm font-bold text-slate-600 dark:text-slate-300">
        <span>Расписание на неделю:</span>
        <span class="rounded-full bg-slate-100 dark:bg-slate-800 px-3 py-1 text-xs font-extrabold text-purple-600 dark:text-purple-400">
            @if ($totalWeekLessons === 0)
                0 занятий
            @else
                {{ $totalWeekLessons }} {{ $totalWeekLessons === 1 ? 'занятие' : ($totalWeekLessons < 5 ? 'занятия' : 'занятий') }}
            @endif
        </span>
    </div>

    @if ($totalWeekLessons === 0)
        {{-- Карточка пустой недели --}}
        <div class="rounded-2xl border-2 border-dashed border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-6 sm:p-8 text-center flex flex-col items-center justify-center gap-3">
            <div class="h-14 w-14 rounded-2xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-3xl">
                ☕
            </div>
            <h3 class="text-base sm:text-lg font-extrabold text-slate-800 dark:text-white">
                На этой неделе уроков нет
            </h3>
            <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 max-w-xs leading-relaxed">
                С {{ $weekStartCarbon->format('d.m') }} по {{ $weekStartCarbon->copy()->endOfWeek()->format('d.m.Y') }} занятий не запланировано.
            </p>
            @if ($isTutorUser)
                <div class="mt-2 w-full max-w-xs">
                    <button type="button"
                            wire:click="mountAction('create_lesson')"
                            class="flex items-center justify-center gap-2 w-full min-h-[48px] rounded-xl bg-purple-600 hover:bg-purple-700 text-white px-4 py-2.5 text-sm font-bold shadow-md active:scale-95 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4.5v15m7.5-7.5h-15" />
                        </svg>
                        <span>Запланировать урок</span>
                    </button>
                </div>
            @endif
        </div>
    @else
        {{-- Лента дней недели с карточками уроков --}}
        <div class="space-y-6">
            @foreach ($days as $day)
                @php
                    $hasLessons = count($day['lessons']) > 0;
                    $isToday = $day['is_today'];
                @endphp

                @if ($hasLessons || $isToday)
                    <div class="space-y-3">
                        {{-- Заголовок дня --}}
                        <div class="flex items-center justify-between gap-2 px-1">
                            <div class="flex items-center gap-2">
                                @if ($isToday)
                                    <span class="flex h-7 w-7 items-center justify-center rounded-full bg-sky-600 text-white font-extrabold text-xs shadow-sm">
                                        {{ $day['day_number'] }}
                                    </span>
                                    <span class="text-base font-black text-sky-600 dark:text-sky-400">
                                        Сегодня ({{ $day['date']->translatedFormat('l, j F') }})
                                    </span>
                                @else
                                    <span class="flex h-7 w-7 items-center justify-center rounded-full bg-slate-200 dark:bg-slate-800 text-slate-800 dark:text-slate-200 font-extrabold text-xs">
                                        {{ $day['day_number'] }}
                                    </span>
                                    <span class="text-base font-extrabold text-slate-800 dark:text-slate-200 capitalize">
                                        {{ $day['date']->translatedFormat('l, j F') }}
                                    </span>
                                @endif
                            </div>

                            @if ($hasLessons)
                                <span class="text-xs font-semibold text-slate-400">
                                    {{ count($day['lessons']) }} {{ count($day['lessons']) === 1 ? 'урок' : 'урока' }}
                                </span>
                            @endif
                        </div>

                        {{-- Если сегодня уроков нет --}}
                        @if ($isToday && ! $hasLessons)
                            <div class="rounded-xl border border-dashed border-slate-200 dark:border-slate-800 bg-slate-50/60 dark:bg-slate-900/40 p-4 text-center text-xs sm:text-sm text-slate-500 dark:text-slate-400">
                                На сегодня занятий не запланировано ☕
                            </div>
                        @endif

                        {{-- Карточки уроков в этот день --}}
                        @if ($hasLessons)
                            <div class="space-y-3">
                                @foreach ($day['lessons'] as $lesson)
                                    @php
                                        $record = $lesson['record'];
                                        $studentName = $lesson['student_name'];
                                        $tutorName = $lesson['tutor_name'];
                                        $displayName = $isTutorUser ? $studentName : $tutorName;
                                        $nameParts = preg_split('/\s+/', trim((string) $displayName));
                                        $initials = count($nameParts) >= 2
                                            ? (mb_substr($nameParts[0], 0, 1) . mb_substr($nameParts[1], 0, 1))
                                            : mb_substr((string) $displayName, 0, 2);
                                        $initials = mb_strtoupper($initials);

                                        $subject = 'Индивидуальное занятие';
                                        if (! empty($record->notes) && preg_match('/(?:Предмет|предмет)[:\s]+([^\r\n,.]+)/u', (string) $record->notes, $m)) {
                                            $subject = trim($m[1]);
                                        } elseif (! empty($record->tutor?->tutorProfile?->subjects)) {
                                            $subjects = is_array($record->tutor->tutorProfile->subjects) ? $record->tutor->tutorProfile->subjects : json_decode((string) $record->tutor->tutorProfile->subjects, true);
                                            if (! empty($subjects[0])) {
                                                $subject = (string) $subjects[0];
                                            }
                                        }
                                        $isPaid = $lesson['payment_status'] === 'paid';
                                        $status = $lesson['status'];

                                        $statusBadge = match ($status) {
                                            'completed' => ['label' => 'Завершён', 'class' => 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300'],
                                            'confirmed' => ['label' => 'Подтверждён', 'class' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/80 dark:text-emerald-300'],
                                            'pending' => ['label' => 'Ожидает', 'class' => 'bg-amber-100 text-amber-800 dark:bg-amber-950/80 dark:text-amber-300'],
                                            'cancelled' => ['label' => 'Отменён', 'class' => 'bg-rose-100 text-rose-800 dark:bg-rose-950/80 dark:text-rose-300'],
                                            default => ['label' => $status, 'class' => 'bg-slate-100 text-slate-700'],
                                        };

                                        $paymentBadge = $isPaid
                                            ? ['label' => 'Оплачено', 'class' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/80 dark:text-emerald-300', 'icon' => '✓']
                                            : ['label' => 'Ожидает оплаты', 'class' => 'bg-amber-100 text-amber-800 dark:bg-amber-950/80 dark:text-amber-300', 'icon' => '⏳'];

                                        $canJoin = ($status === 'confirmed' || $status === 'pending');
                                    @endphp

                                    <div class="rounded-2xl border border-slate-200/90 dark:border-slate-800 bg-white dark:bg-slate-900 p-4 sm:p-5 shadow-sm space-y-3.5 transition hover:border-slate-300 dark:hover:border-slate-700">
                                        {{-- 1. Время и статус крупно --}}
                                        <div class="flex items-start justify-between gap-2 border-b border-slate-100 dark:border-slate-800/80 pb-3">
                                            <div>
                                                <div class="text-lg sm:text-xl font-black text-slate-900 dark:text-white tracking-tight">
                                                    ⏰ {{ $lesson['time_span'] }}
                                                </div>
                                                <div class="text-xs font-semibold text-slate-500 dark:text-slate-400 mt-0.5">
                                                    ⏱ {{ $record->duration_minutes }} минут
                                                </div>
                                            </div>

                                            <div class="flex flex-col items-end gap-1.5 shrink-0">
                                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-extrabold {{ $statusBadge['class'] }}">
                                                    {{ $statusBadge['label'] }}
                                                </span>
                                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-bold {{ $paymentBadge['class'] }}">
                                                    {{ $paymentBadge['icon'] }} {{ $paymentBadge['label'] }}
                                                </span>
                                            </div>
                                        </div>

                                        {{-- 2. Участник, предмет и цена --}}
                                        <div class="flex items-center justify-between gap-3">
                                            <div class="flex items-center gap-3 min-w-0">
                                                <div class="h-12 w-12 rounded-xl bg-gradient-to-br from-indigo-500 to-purple-600 text-white font-extrabold text-base flex items-center justify-center shrink-0 shadow-sm">
                                                    {{ $initials }}
                                                </div>
                                                <div class="min-w-0">
                                                    <h4 class="text-base sm:text-lg font-extrabold text-slate-900 dark:text-white truncate">
                                                        {{ $displayName }}
                                                    </h4>
                                                    <p class="text-xs sm:text-sm font-medium text-slate-600 dark:text-slate-400 truncate mt-0.5">
                                                        📚 {{ $subject }}
                                                    </p>
                                                </div>
                                            </div>

                                            @if ($record->price > 0)
                                                <div class="text-right shrink-0">
                                                    <span class="text-base sm:text-lg font-black text-slate-900 dark:text-white">
                                                        {{ number_format((float) $record->price, 0) }} BYN
                                                    </span>
                                                </div>
                                            @endif
                                        </div>

                                        {{-- 3. Крупные кнопки действий (тач-таргеты 48-52px для аудитории 50+) --}}
                                        <div class="flex flex-col gap-2 pt-1">
                                            @if ($canJoin)
                                                <a href="{{ $lesson['classroom_url'] }}"
                                                   class="flex items-center justify-center gap-2.5 w-full min-h-[50px] px-4 py-3 rounded-xl bg-gradient-to-r from-emerald-600 via-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white font-extrabold text-base shadow-md active:scale-[0.98] transition -webkit-tap-highlight-color-transparent">
                                                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15.75 10.5l4.72-4.72a.75.75 0 011.28.53v11.38a.75.75 0 01-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 002.25-2.25v-9a2.25 2.25 0 00-2.25-2.25h-9A2.25 2.25 0 002.25 7.5v9a2.25 2.25 0 002.25 2.25z" />
                                                    </svg>
                                                    <span>Войти в виртуальный класс</span>
                                                </a>
                                            @endif

                                            <button type="button"
                                                    wire:click="openCalendarLesson({{ $lesson['id'] }})"
                                                    class="flex items-center justify-center gap-2 w-full min-h-[46px] px-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 hover:bg-slate-100 dark:bg-slate-800/80 dark:hover:bg-slate-800 text-slate-800 dark:text-slate-200 font-bold text-sm shadow-sm active:scale-[0.98] transition">
                                                <svg class="w-4 h-4 text-purple-600 dark:text-purple-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                                <span>Материалы к уроку</span>
                                            </button>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endif
            @endforeach
        </div>
    @endif
</div>
