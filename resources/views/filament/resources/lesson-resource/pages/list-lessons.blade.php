<x-filament-panels::page
    @class([
        'fi-resource-list-records-page',
        'fi-resource-' . str_replace('/', '-', $this->getResource()::getSlug()),
    ])
>
    @php
        $days = $this->calendarWeekDays;
        $currentTimePos = $this->currentTimePosition;
        $selectedLesson = $this->selectedLesson;
        $displayTz = config('booking.display_timezone', 'Europe/Minsk');
        $weekStartCarbon = \Carbon\Carbon::parse($this->currentWeekStart, $displayTz);
        $monthYearLabel = $weekStartCarbon->translatedFormat('F Y');
    @endphp

    <div class="space-y-6">
        {{-- Calendar Top Control Bar --}}
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 rounded-2xl border border-slate-200/90 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            {{-- Navigation and Date title --}}
            <div class="flex items-center justify-between sm:justify-start gap-2.5 sm:gap-3 w-full sm:w-auto">
                <button type="button"
                        wire:click="goToToday"
                        class="rounded-xl border border-slate-300 dark:border-slate-700 px-3.5 py-2 text-xs sm:text-sm font-bold text-slate-800 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 min-h-[40px] transition shadow-sm active:scale-95">
                    Сегодня
                </button>

                <div class="flex items-center rounded-xl border border-slate-300 dark:border-slate-700 overflow-hidden min-h-[40px]">
                    <button type="button"
                            wire:click="previousWeek"
                            title="Предыдущая неделя"
                            class="p-2 text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 min-h-[40px] min-w-[40px] flex items-center justify-center transition active:scale-95">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7" />
                        </svg>
                    </button>
                    <div class="h-4 w-px bg-slate-200 dark:bg-slate-800"></div>
                    <button type="button"
                            wire:click="nextWeek"
                            title="Следующая неделя"
                            class="p-2 text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 min-h-[40px] min-w-[40px] flex items-center justify-center transition active:scale-95">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7" />
                        </svg>
                    </button>
                </div>

                <h2 class="text-base sm:text-xl font-extrabold tracking-tight text-slate-900 dark:text-white capitalize ml-1 truncate">
                    {{ $monthYearLabel }}
                </h2>
            </div>

            {{-- Controls: View Mode & Google Calendar Sync --}}
            <div class="flex flex-wrap items-center gap-2.5 w-full md:w-auto">
                {{-- View Mode Switcher --}}
                <div class="inline-flex w-full sm:w-auto rounded-xl bg-slate-100 dark:bg-slate-950 p-1 border border-slate-200 dark:border-slate-800">
                    <button type="button"
                            wire:click="setViewMode('calendar')"
                            class="flex-1 sm:flex-initial inline-flex items-center justify-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-semibold transition {{ $this->viewMode === 'calendar' ? 'bg-white dark:bg-slate-800 text-sky-600 dark:text-sky-400 shadow-sm' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        <span>Календарь</span>
                    </button>
                    <button type="button"
                            wire:click="setViewMode('agenda')"
                            class="flex-1 sm:flex-initial inline-flex items-center justify-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-semibold transition {{ $this->viewMode === 'agenda' ? 'bg-white dark:bg-slate-800 text-sky-600 dark:text-sky-400 shadow-sm' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                        </svg>
                        <span>Карточки</span>
                    </button>
                    <button type="button"
                            wire:click="setViewMode('table')"
                            class="flex-1 sm:flex-initial inline-flex items-center justify-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-semibold transition {{ $this->viewMode === 'table' ? 'bg-white dark:bg-slate-800 text-sky-600 dark:text-sky-400 shadow-sm' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                        </svg>
                        <span>Таблица</span>
                    </button>
                </div>

                {{-- Google Calendar Sync Button --}}
                <button type="button"
                        wire:click="exportIcs"
                        title="Экспортировать расписание для Google Calendar и Apple Calendar"
                        class="inline-flex items-center gap-2 rounded-xl bg-slate-900 hover:bg-slate-800 dark:bg-white dark:hover:bg-slate-100 text-white dark:text-slate-900 px-3.5 py-2 text-xs font-semibold transition shadow-sm">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M19 4h-1V2h-2v2H8V2H6v2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 16H5V9h14v11z"/>
                    </svg>
                    <span class="hidden sm:inline">Синхронизация (.ics)</span>
                    <span class="sm:hidden">.ics</span>
                </button>

                {{-- Print A4 Weekly Schedule Button --}}
                <a href="{{ route('admin.lessons.print-weekly', ['week' => $this->currentWeekStart]) }}"
                   target="_blank"
                   title="Распечатать график на неделю (А4) в 1 клик для доски или ежедневника"
                   class="inline-flex items-center gap-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white hover:bg-slate-50 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-800 dark:text-slate-100 px-3.5 py-2 text-xs font-semibold transition shadow-sm">
                    <svg class="w-4 h-4 text-sky-600 dark:text-sky-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4H7v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                    </svg>
                    <span class="hidden sm:inline">Распечатать на неделю (А4)</span>
                    <span class="sm:hidden">Печать А4</span>
                </a>
            </div>
        </div>

        {{-- MAIN CONTENT AREA --}}
        @if ($this->viewMode === 'calendar')
            {{-- Google Calendar 7-Day Week Grid View (Для больших экранов >= md) --}}
            <div class="hidden md:block rounded-2xl border border-slate-200/90 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900 overflow-x-auto select-none">
                <div class="min-w-[760px]">
                    {{-- Calendar Column Headers --}}
                    <div style="display: grid; grid-template-columns: 60px repeat(7, minmax(0, 1fr));" class="border-b border-slate-200 dark:border-slate-800 bg-slate-50/80 dark:bg-slate-950/60 sticky top-0 z-30">
                        {{-- Timezone Corner Cell --}}
                        <div class="sticky left-0 z-40 flex items-center justify-center border-r border-slate-200 dark:border-slate-800 p-2 text-[10px] font-mono font-medium text-slate-400 bg-slate-50 dark:bg-slate-950">
                            GMT+3
                        </div>

                        {{-- 7 Day Headers (Mon - Sun) --}}
                        @foreach ($days as $day)
                            <div class="flex flex-col items-center justify-center py-3 border-r border-slate-200/70 dark:border-slate-800/80 last:border-r-0 {{ $day['is_today'] ? 'bg-sky-50/60 dark:bg-sky-950/30' : '' }}">
                                <span class="text-[11px] font-bold uppercase tracking-wider {{ $day['is_today'] ? 'text-sky-600 dark:text-sky-400' : 'text-slate-500 dark:text-slate-400' }}">
                                    {{ $day['day_name'] }}
                                </span>
                                <div class="mt-1 flex items-center justify-center">
                                    @if ($day['is_today'])
                                        <span class="flex h-8 w-8 items-center justify-center rounded-full bg-sky-600 text-white font-extrabold text-sm shadow-md">
                                            {{ $day['day_number'] }}
                                        </span>
                                    @else
                                        <span class="text-sm font-semibold text-slate-800 dark:text-slate-200">
                                            {{ $day['day_number'] }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>

                    {{-- Scrollable 16-Hour Grid Canvas (07:00 to 23:00 = 960px) --}}
                    <div class="relative">
                        <div style="display: grid; grid-template-columns: 60px repeat(7, minmax(0, 1fr)); height: 960px; min-width: 760px; position: relative;">
                            {{-- Hours Axis (07:00 to 22:00) sticky on the left --}}
                            <div style="height: 960px; position: sticky; left: 0; z-index: 20;" class="border-r border-slate-200 dark:border-slate-800 bg-slate-50/95 dark:bg-slate-950/95 backdrop-blur-xs">
                            @for ($hour = 7; $hour <= 22; $hour++)
                                <div class="absolute w-full text-right pr-2 -translate-y-2 text-[11px] font-mono text-slate-400 select-none"
                                     style="top: {{ ($hour - 7) * 60 }}px;">
                                    {{ sprintf('%02d:00', $hour) }}
                                </div>
                            @endfor
                        </div>

                        {{-- 7 Day Columns --}}
                        @foreach ($days as $dayIndex => $day)
                            <div style="height: 960px; position: relative;" class="border-r border-slate-200/70 dark:border-slate-800/80 last:border-r-0 {{ $day['is_today'] ? 'bg-sky-50/20 dark:bg-sky-950/10' : '' }}">
                                {{-- Horizontal Hour Guidelines --}}
                                @for ($hour = 7; $hour <= 22; $hour++)
                                    <div class="absolute left-0 right-0 border-t border-slate-100 dark:border-slate-800/60 pointer-events-none"
                                         style="top: {{ ($hour - 7) * 60 }}px;"></div>
                                @endfor

                                {{-- Red Current Time Line if Today --}}
                                @if ($day['is_today'] && $currentTimePos !== null)
                                    <div class="absolute left-0 right-0 z-20 pointer-events-none flex items-center"
                                         style="top: {{ $currentTimePos }}px;">
                                        <div class="h-2.5 w-2.5 -ml-1 rounded-full bg-rose-600 shadow-sm"></div>
                                        <div class="h-[2px] w-full bg-rose-600 shadow-sm"></div>
                                    </div>
                                @endif

                                {{-- Lesson Event Cards in this day --}}
                                @foreach ($day['lessons'] as $lesson)
                                    @php
                                        $colorClasses = match ($lesson['status']) {
                                            'completed' => 'bg-emerald-600 hover:bg-emerald-700 text-white border-l-4 border-emerald-800',
                                            'confirmed' => 'bg-sky-600 hover:bg-sky-700 text-white border-l-4 border-sky-800',
                                            'pending' => 'bg-amber-600 hover:bg-amber-700 text-white border-l-4 border-amber-800',
                                            default => 'bg-slate-600 hover:bg-slate-700 text-white border-l-4 border-slate-800',
                                        };
                                    @endphp
                                    <div wire:click="openCalendarLesson({{ $lesson['id'] }})"
                                         class="absolute left-1 right-1 z-10 rounded-lg p-2.5 shadow-md cursor-pointer transition-all duration-150 hover:scale-[1.02] hover:z-30 overflow-hidden {{ $colorClasses }}"
                                         style="top: {{ $lesson['top_px'] }}px; height: {{ $lesson['height_px'] }}px; min-height: 38px;"
                                         title="{{ $lesson['title'] }} ({{ $lesson['time_span'] }})">
                                        <div class="flex items-center justify-between gap-1 leading-none">
                                            <span class="text-[11px] font-bold tracking-tight truncate">
                                                {{ $lesson['title'] }}
                                            </span>
                                            <span class="text-[10px] font-mono opacity-90 shrink-0">
                                                {{ $lesson['start_formatted'] }}
                                            </span>
                                        </div>

                                        <div class="mt-1 flex items-center justify-between gap-1 text-[10px] opacity-90">
                                            <span class="font-medium truncate">{{ $lesson['time_span'] }}</span>
                                            @if ($lesson['payment_status'] === 'paid')
                                                <span class="rounded bg-white/20 px-1 py-0.5 text-[9px] font-bold uppercase">Оплачен</span>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Footer Legend --}}
                <div class="flex flex-wrap items-center justify-between gap-4 px-6 py-3.5 border-t border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-950/30 text-xs">
                    <div class="flex items-center gap-4">
                        <div class="flex items-center gap-1.5">
                            <span class="h-3 w-3 rounded-full bg-sky-600"></span>
                            <span class="text-slate-600 dark:text-slate-400">Подтверждён</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <span class="h-3 w-3 rounded-full bg-emerald-600"></span>
                            <span class="text-slate-600 dark:text-slate-400">Завершён</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <span class="h-3 w-3 rounded-full bg-amber-600"></span>
                            <span class="text-slate-600 dark:text-slate-400">Ожидает</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <span class="h-2.5 w-6 rounded-sm bg-rose-600"></span>
                            <span class="text-slate-600 dark:text-slate-400">Текущее время</span>
                        </div>
                    </div>

                    <span class="text-slate-400">Нажмите на любой урок для подробностей и входа в онлайн-класс</span>
                </div>
            </div>

            {{-- Карточный вид расписания уроков для смартфонов (Автоматически на экранах < 768px для удобства 50+) --}}
            <div class="block md:hidden">
                @include('filament.resources.lesson-resource.components.mobile-lesson-agenda')
            </div>
        @elseif ($this->viewMode === 'agenda')
            {{-- Полноценный карточный режим для всех экранов (Desktop + Mobile) --}}
            @include('filament.resources.lesson-resource.components.mobile-lesson-agenda')
        @else
            {{-- Standard Filament Table View with Tabs --}}
            <div class="flex flex-col gap-y-6">
                <x-filament-panels::resources.tabs />

                {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::RESOURCE_PAGES_LIST_RECORDS_TABLE_BEFORE, scopes: $this->getRenderHookScopes()) }}

                {{ $this->table }}

                {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::RESOURCE_PAGES_LIST_RECORDS_TABLE_AFTER, scopes: $this->getRenderHookScopes()) }}
            </div>
        @endif

        {{-- Premium Pixel-Perfect Lesson Detail Modal --}}
        @if ($selectedLesson)
            @php
                $lessonStart = $selectedLesson->start_time?->setTimezone($displayTz);
                $lessonEnd = $selectedLesson->end_time?->setTimezone($displayTz);

                $russianMonths = [
                    1 => 'января', 2 => 'февраля', 3 => 'марта', 4 => 'апреля',
                    5 => 'мая', 6 => 'июня', 7 => 'июля', 8 => 'августа',
                    9 => 'сентября', 10 => 'октября', 11 => 'ноября', 12 => 'декабря',
                ];
                $russianWeekdays = [
                    1 => 'Понедельник', 2 => 'Вторник', 3 => 'Среда', 4 => 'Четверг',
                    5 => 'Пятница', 6 => 'Суббота', 7 => 'Воскресенье',
                ];

                $dayOfWeek = $lessonStart ? ($russianWeekdays[$lessonStart->dayOfWeekIso] ?? '') : '';
                $formattedDate = $lessonStart
                    ? ($lessonStart->format('j') . ' ' . ($russianMonths[$lessonStart->month] ?? '') . ' ' . $lessonStart->format('Y'))
                    : '—';
                $timeRange = ($lessonStart && $lessonEnd)
                    ? ($lessonStart->format('H:i') . ' — ' . $lessonEnd->format('H:i'))
                    : '—';

                $gcalLink = 'https://calendar.google.com/calendar/render?' . http_build_query([
                    'action' => 'TEMPLATE',
                    'text' => 'Урок: ' . ($selectedLesson->student?->name ?? 'Ученик') . ' (' . ($selectedLesson->subject ?: 'Edusfera') . ')',
                    'dates' => $selectedLesson->start_time->setTimezone('UTC')->format('Ymd\THis\Z') . '/' . $selectedLesson->end_time->setTimezone('UTC')->format('Ymd\THis\Z'),
                    'details' => 'Edusfera онлайн-урок. Ссылка на виртуальный класс: ' . route('classroom.show', $selectedLesson),
                    'location' => route('classroom.show', $selectedLesson),
                ]);

                $studentName = $selectedLesson->student?->name ?? 'Ученик';
                $nameParts = preg_split('/\s+/', trim($studentName));
                $initials = count($nameParts) >= 2
                    ? (mb_substr($nameParts[0], 0, 1) . mb_substr($nameParts[1], 0, 1))
                    : mb_substr($studentName, 0, 2);
                $initials = mb_strtoupper($initials);

                $isTutorUser = auth()->user()?->isTutor() ?? false;
                $isAdminUser = auth()->user()?->isAdmin() ?? false;

                $statusData = match ($selectedLesson->status) {
                    'confirmed' => ['label' => 'Подтверждён', 'color' => '#059669', 'bg' => '#ecfdf5', 'border' => '#a7f3d0'],
                    'completed' => ['label' => 'Завершён', 'color' => '#475569', 'bg' => '#f1f5f9', 'border' => '#cbd5e1'],
                    'pending' => ['label' => 'Ожидает подтверждения', 'color' => '#d97706', 'bg' => '#fffbeb', 'border' => '#fde68a'],
                    'cancelled' => ['label' => 'Отменён', 'color' => '#e11d48', 'bg' => '#fff1f2', 'border' => '#fecdd3'],
                    default => ['label' => $selectedLesson->status, 'color' => '#64748b', 'bg' => '#f8fafc', 'border' => '#e2e8f0'],
                };

                $isPaid = $selectedLesson->payment_status === 'paid';
                $paymentData = $isPaid
                    ? ['label' => 'Оплачено', 'color' => '#059669', 'bg' => '#ecfdf5', 'border' => '#a7f3d0', 'icon' => '✓']
                    : ['label' => 'Ожидает оплаты', 'color' => '#e11d48', 'bg' => '#fff1f2', 'border' => '#fecdd3', 'icon' => '⏳'];

                $classUrl = (! config('classroom.enabled', false) && ! empty($selectedLesson->meeting_link) && str_starts_with($selectedLesson->meeting_link, 'http'))
                    ? $selectedLesson->meeting_link
                    : route('classroom.show', $selectedLesson);
                $isExternalClass = (! config('classroom.enabled', false) && ! empty($selectedLesson->meeting_link) && str_starts_with($selectedLesson->meeting_link, 'http'));
            @endphp

            <style>
                .edusfera-backdrop {
                    position: fixed;
                    inset: 0;
                    z-index: 9999;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    padding: 16px;
                    background-color: rgba(15, 23, 42, 0.65);
                    backdrop-filter: blur(8px);
                    -webkit-backdrop-filter: blur(8px);
                    animation: eduFadeIn 0.15s ease-out;
                }
                @keyframes eduFadeIn {
                    from { opacity: 0; transform: scale(0.97); }
                    to { opacity: 1; transform: scale(1); }
                }
                .edusfera-card {
                    position: relative;
                    width: 100%;
                    max-width: 520px;
                    background: #ffffff;
                    border-radius: 24px;
                    box-shadow: 0 25px 60px -15px rgba(15, 23, 42, 0.3), 0 0 0 1px rgba(15, 23, 42, 0.08);
                    overflow: hidden;
                    font-family: inherit;
                    color: #0f172a;
                }
                .dark .edusfera-card {
                    background: #0f172a;
                    border: 1px solid #1e293b;
                    color: #f8fafc;
                    box-shadow: 0 25px 60px -15px rgba(0, 0, 0, 0.8), 0 0 0 1px rgba(255, 255, 255, 0.08);
                }
                .edusfera-top-glow {
                    height: 4px;
                    width: 100%;
                    background: linear-gradient(90deg, #10b981 0%, #0ea5e9 50%, #6366f1 100%);
                }
                .edusfera-header {
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                    padding: 20px 24px 16px;
                    border-bottom: 1px solid #f1f5f9;
                }
                .dark .edusfera-header {
                    border-bottom-color: #1e293b;
                }
                .edusfera-avatar {
                    position: relative;
                    width: 46px;
                    height: 46px;
                    border-radius: 14px;
                    background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
                    color: #ffffff;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    font-weight: 800;
                    font-size: 16px;
                    letter-spacing: 0.5px;
                    flex-shrink: 0;
                    box-shadow: 0 4px 10px rgba(79, 70, 229, 0.25);
                }
                .edusfera-avatar-dot {
                    position: absolute;
                    bottom: -2px;
                    right: -2px;
                    width: 12px;
                    height: 12px;
                    border-radius: 50%;
                    border: 2px solid #ffffff;
                }
                .dark .edusfera-avatar-dot {
                    border-color: #0f172a;
                }
                .edusfera-close-btn {
                    width: 32px;
                    height: 32px;
                    border-radius: 50%;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    color: #94a3b8;
                    background: #f8fafc;
                    border: 1px solid #e2e8f0;
                    cursor: pointer;
                    transition: all 0.15s ease;
                }
                .edusfera-close-btn:hover {
                    background: #f1f5f9;
                    color: #0f172a;
                    border-color: #cbd5e1;
                }
                .dark .edusfera-close-btn {
                    background: #1e293b;
                    border-color: #334155;
                    color: #94a3b8;
                }
                .dark .edusfera-close-btn:hover {
                    background: #334155;
                    color: #ffffff;
                }
                .edusfera-body {
                    padding: 18px 24px 22px;
                    display: flex;
                    flex-direction: column;
                    gap: 14px;
                }
                .edusfera-schedule-box {
                    background: #f8fafc;
                    border: 1px solid #e2e8f0;
                    border-radius: 16px;
                    padding: 16px;
                }
                .dark .edusfera-schedule-box {
                    background: #1e293b;
                    border-color: #334155;
                }
                .edusfera-grid-2 {
                    display: grid;
                    grid-template-columns: 1fr 1fr;
                    gap: 12px;
                }
                .edusfera-tile {
                    background: #f8fafc;
                    border: 1px solid #e2e8f0;
                    border-radius: 14px;
                    padding: 12px 14px;
                    display: flex;
                    flex-direction: column;
                    justify-content: space-between;
                }
                .dark .edusfera-tile {
                    background: #1e293b;
                    border-color: #334155;
                }
                .edusfera-ai-tile {
                    background: #fffdf5;
                    border: 1px solid #fed7aa;
                    border-radius: 16px;
                    padding: 14px 16px;
                    display: flex;
                    flex-direction: column;
                    gap: 10px;
                }
                .dark .edusfera-ai-tile {
                    background: rgba(245, 158, 11, 0.08);
                    border-color: rgba(245, 158, 11, 0.25);
                }
                .edusfera-topic-input {
                    width: 100%;
                    padding: 8px 12px;
                    font-size: 13px;
                    font-weight: 600;
                    color: #0f172a;
                    background: #ffffff;
                    border: 1.5px solid #fed7aa;
                    border-radius: 10px;
                    outline: none;
                    transition: all 0.15s ease;
                    box-sizing: border-box;
                }
                .edusfera-topic-input:focus {
                    border-color: #f59e0b;
                    box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.2);
                }
                .dark .edusfera-topic-input {
                    background: #1e293b;
                    border-color: #475569;
                    color: #f8fafc;
                }
                .dark .edusfera-topic-input:focus {
                    border-color: #f59e0b;
                    box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.25);
                }
                .edusfera-ctx-badge {
                    display: inline-flex;
                    align-items: center;
                    gap: 5px;
                    padding: 3px 8px;
                    border-radius: 6px;
                    font-size: 10px;
                    font-weight: 700;
                    text-transform: uppercase;
                    letter-spacing: 0.04em;
                    background: #fef3c7;
                    color: #b45309;
                    border: 1px solid #fde68a;
                    white-space: nowrap;
                }
                .dark .edusfera-ctx-badge {
                    background: rgba(245, 158, 11, 0.15);
                    color: #fbbf24;
                    border-color: rgba(245, 158, 11, 0.3);
                }
                .edusfera-history-box {
                    padding: 8px 10px;
                    border-radius: 8px;
                    background: #ffffff;
                    border: 1px solid #fef3c7;
                    font-size: 11px;
                    color: #475569;
                    line-height: 1.4;
                }
                .dark .edusfera-history-box {
                    background: #1e293b;
                    border-color: #334155;
                    color: #cbd5e1;
                }
                .edusfera-plan-box {
                    margin-top: 4px;
                    padding: 12px;
                    background: #ffffff;
                    border-radius: 12px;
                    border: 1px solid #fde68a;
                }
                .dark .edusfera-plan-box {
                    background: #1e293b;
                    border-color: #334155;
                }
                .edusfera-btn-primary {
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    gap: 10px;
                    width: 100%;
                    padding: 13px 20px;
                    background: #059669 !important;
                    background: linear-gradient(135deg, #10b981 0%, #059669 100%) !important;
                    color: #ffffff !important;
                    font-weight: 700;
                    font-size: 14px;
                    border-radius: 14px;
                    text-decoration: none !important;
                    border: none;
                    cursor: pointer;
                    box-shadow: 0 6px 16px rgba(16, 185, 129, 0.3);
                    transition: all 0.15s ease;
                }
                .edusfera-btn-primary:hover {
                    background: #047857 !important;
                    background: linear-gradient(135deg, #059669 0%, #047857 100%) !important;
                    box-shadow: 0 8px 20px rgba(16, 185, 129, 0.4);
                    transform: translateY(-1px);
                }
                .edusfera-btn-primary:active {
                    transform: translateY(0);
                }
                .edusfera-btn-secondary {
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    gap: 6px;
                    flex: 1;
                    padding: 10px 12px;
                    background: #ffffff;
                    color: #334155;
                    font-weight: 600;
                    font-size: 12px;
                    border-radius: 12px;
                    text-decoration: none !important;
                    border: 1px solid #cbd5e1;
                    cursor: pointer;
                    transition: all 0.15s ease;
                }
                .edusfera-btn-secondary:hover {
                    background: #f8fafc;
                    border-color: #94a3b8;
                    color: #0f172a;
                }
                .dark .edusfera-btn-secondary {
                    background: #1e293b;
                    border-color: #334155;
                    color: #e2e8f0;
                }
                .dark .edusfera-btn-secondary:hover {
                    background: #334155;
                    color: #ffffff;
                }
                .edusfera-btn-ai {
                    display: inline-flex;
                    align-items: center;
                    gap: 6px;
                    padding: 8px 14px;
                    background: #f59e0b !important;
                    color: #ffffff !important;
                    font-weight: 700;
                    font-size: 12px;
                    border-radius: 10px;
                    border: none;
                    cursor: pointer;
                    box-shadow: 0 3px 8px rgba(245, 158, 11, 0.3);
                    transition: all 0.15s ease;
                    white-space: nowrap;
                }
                .edusfera-btn-ai:hover {
                    background: #d97706 !important;
                    transform: translateY(-1px);
                }
            </style>

            <div class="edusfera-backdrop" wire:click.self="closeCalendarLesson">
                <div class="edusfera-card" @click.stop>
                    
                    {{-- Ambient Top Glow Bar --}}
                    <div class="edusfera-top-glow"></div>

                    {{-- Header --}}
                    <div class="edusfera-header">
                        <div style="display: flex; align-items: center; gap: 14px; min-width: 0;">
                            <div class="edusfera-avatar">
                                {{ $initials }}
                                <div class="edusfera-avatar-dot" style="background-color: {{ $statusData['color'] }};"></div>
                            </div>

                            <div style="min-width: 0;">
                                <div style="display: flex; align-items: center; gap: 6px; flex-wrap: wrap;">
                                    <span style="display: inline-block; padding: 2px 8px; border-radius: 6px; font-size: 11px; font-weight: 700; background: #e0f2fe; color: #0369a1;">
                                        {{ $selectedLesson->package_label }}
                                    </span>
                                    @if ($selectedLesson->subject)
                                        <span style="display: inline-block; padding: 2px 8px; border-radius: 6px; font-size: 11px; font-weight: 600; background: #f1f5f9; color: #475569;">
                                            {{ $selectedLesson->subject }}
                                        </span>
                                    @endif
                                    <span style="font-size: 11px; font-family: monospace; color: #94a3b8;">
                                        #{{ $selectedLesson->id }}
                                    </span>
                                </div>
                                <h3 style="margin: 4px 0 0; font-size: 20px; font-weight: 800; line-height: 1.2; letter-spacing: -0.02em; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                    {{ $studentName }}
                                </h3>
                            </div>
                        </div>

                        <button type="button" wire:click="closeCalendarLesson" class="edusfera-close-btn" title="Закрыть">
                            <svg style="width: 16px; height: 16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    {{-- Modal Body --}}
                    <div class="edusfera-body">
                        {{-- Schedule Box --}}
                        <div class="edusfera-schedule-box">
                            <div style="display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid #e2e8f0; padding-bottom: 10px; margin-bottom: 12px;">
                                <div style="display: flex; align-items: center; gap: 8px; font-size: 12px; font-weight: 600; color: #64748b;">
                                    <svg style="width: 15px; height: 15px; color: #0ea5e9;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                    <span>{{ $dayOfWeek }}, {{ $formattedDate }}</span>
                                </div>

                                <div style="display: inline-flex; align-items: center; gap: 6px; padding: 4px 10px; border-radius: 999px; font-size: 11px; font-weight: 700; background: {{ $statusData['bg'] }}; color: {{ $statusData['color'] }}; border: 1px solid {{ $statusData['border'] }};">
                                    <span style="width: 7px; height: 7px; border-radius: 50%; background: {{ $statusData['color'] }};"></span>
                                    <span>{{ $statusData['label'] }}</span>
                                </div>
                            </div>

                            <div style="display: flex; align-items: center; justify-content: space-between;">
                                <div>
                                    <div style="font-size: 10px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.08em; color: #94a3b8;">
                                        Время проведения
                                    </div>
                                    <div style="font-size: 26px; font-weight: 800; font-family: ui-monospace, monospace; letter-spacing: -0.03em; margin-top: 2px;">
                                        {{ $timeRange }}
                                    </div>
                                </div>

                                <span style="display: inline-flex; align-items: center; gap: 5px; padding: 6px 12px; border-radius: 10px; background: #ffffff; border: 1px solid #cbd5e1; font-size: 12px; font-weight: 700; color: #334155;">
                                    <svg style="width: 14px; height: 14px; color: #94a3b8;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <span>{{ $selectedLesson->duration_minutes }} мин</span>
                                </span>
                            </div>
                        </div>

                        {{-- Details Grid (Price & Payment) --}}
                        <div class="edusfera-grid-2">
                            <div class="edusfera-tile">
                                <span style="font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; color: #94a3b8;">
                                    Стоимость
                                </span>
                                <div style="font-size: 20px; font-weight: 800; margin-top: 4px;">
                                    {{ number_format((float) $selectedLesson->price, 2, '.', ' ') }}
                                    <span style="font-size: 12px; font-weight: 600; color: #64748b;">BYN</span>
                                </div>
                            </div>

                            <div class="edusfera-tile">
                                <span style="font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; color: #94a3b8;">
                                    Статус оплаты
                                </span>
                                <div style="margin-top: 4px;">
                                    <span style="display: inline-flex; align-items: center; gap: 5px; padding: 4px 10px; border-radius: 8px; font-size: 12px; font-weight: 700; background: {{ $paymentData['bg'] }}; color: {{ $paymentData['color'] }}; border: 1px solid {{ $paymentData['border'] }};">
                                        <span>{{ $paymentData['icon'] }}</span>
                                        <span>{{ $paymentData['label'] }}</span>
                                    </span>
                                </div>
                            </div>
                        </div>

                        {{-- Parent & Notes rows if available --}}
                        @if ($selectedLesson->parent?->name)
                            <div style="display: flex; align-items: center; gap: 8px; padding: 9px 12px; border-radius: 10px; background: #f8fafc; border: 1px solid #e2e8f0; font-size: 12px; color: #475569;">
                                <svg style="width: 15px; height: 15px; color: #94a3b8;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                                <span>Родитель: <strong style="color: #0f172a;">{{ $selectedLesson->parent->name }}</strong></span>
                            </div>
                        @endif

                        @if ($selectedLesson->notes)
                            <div style="padding: 10px 12px; border-radius: 10px; background: #fffdf5; border: 1px solid #fde68a; font-size: 12px; color: #78350f;">
                                <span style="font-weight: 700; font-size: 10px; text-transform: uppercase; letter-spacing: 0.05em; display: block; margin-bottom: 2px;">Заметки к уроку:</span>
                                <span>{{ $selectedLesson->notes }}</span>
                            </div>
                        @endif

                        {{-- MAYA AI Prep Card for Tutors --}}
                        @if ($isTutorUser || $isAdminUser)
                            @php
                                $prepCtx = $this->detectLessonPrepContext($selectedLesson);
                            @endphp
                            <div class="edusfera-ai-tile">
                                {{-- AI Tile Header & Source Badge --}}
                                <div style="display: flex; align-items: center; justify-content: space-between; gap: 8px;">
                                    <div style="display: flex; align-items: center; gap: 8px; min-width: 0;">
                                        <div style="width: 28px; height: 28px; border-radius: 8px; background: #f59e0b; color: #ffffff; display: flex; align-items: center; justify-content: center; font-size: 14px; font-weight: 800; flex-shrink: 0;">
                                            ⚡
                                        </div>
                                        <div style="min-width: 0;">
                                            <div style="font-size: 12px; font-weight: 800; color: #78350f; line-height: 1.2;">
                                                ИИ-шпаргалка к занятию
                                            </div>
                                            <div style="font-size: 10px; color: #92400e; line-height: 1.2; margin-top: 1px;">
                                                Разминка • Ловушки РИКЗ • 3 задачи • Финал
                                            </div>
                                        </div>
                                    </div>

                                    <span class="edusfera-ctx-badge" title="Источник определения темы урока">
                                        <span>{{ $prepCtx['is_first_lesson'] ? '🔍' : '🎯' }}</span>
                                        <span>{{ $prepCtx['topic_source'] }}</span>
                                    </span>
                                </div>

                                {{-- Pedagogical Trajectory / Previous Lesson Context --}}
                                @if ($prepCtx['is_first_lesson'])
                                    <div style="padding: 7px 10px; border-radius: 8px; background: rgba(245, 158, 11, 0.1); border: 1px dashed #fcd34d; font-size: 11px; color: #92400e; line-height: 1.35;">
                                        ✨ <strong>Стартовый урок:</strong> первое занятие с учеником. План рассчитан на входную диагностику базы и психологический контакт.
                                    </div>
                                @elseif ($prepCtx['previous_lesson'])
                                    <div class="edusfera-history-box">
                                        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 2px;">
                                            <span style="font-weight: 700; color: #b45309; font-size: 10px; text-transform: uppercase;">Преемственность (Урок #{{ $prepCtx['previous_lesson']->id }}):</span>
                                            @if ($prepCtx['previous_homework'])
                                                <span style="font-size: 10px; color: #059669; font-weight: 600;">✓ ДЗ задано</span>
                                            @endif
                                        </div>
                                        @if ($prepCtx['previous_summary'])
                                            <div style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                                <strong>Прошли:</strong> {{ $prepCtx['previous_summary'] }}
                                            </div>
                                        @endif
                                        @if ($prepCtx['previous_focus'])
                                            <div style="color: #b45309; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                                <strong>Слабое место:</strong> {{ $prepCtx['previous_focus'] }}
                                            </div>
                                        @endif
                                        @if ($prepCtx['previous_next_step'])
                                            <div style="color: #0369a1; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                                <strong>Намечено:</strong> {{ $prepCtx['previous_next_step'] }}
                                            </div>
                                        @endif
                                    </div>
                                @endif

                                {{-- Topic Input Row --}}
                                <div style="display: flex; flex-direction: column; gap: 4px;">
                                    <div style="display: flex; align-items: center; justify-content: space-between;">
                                        <label style="font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: #92400e;">
                                            Тема сегодняшнего урока:
                                        </label>
                                        @if ($prepTopic !== $prepCtx['detected_topic'])
                                            <button type="button"
                                                    wire:click="resetPrepTopic({{ $selectedLesson->id }})"
                                                    style="font-size: 10px; color: #b45309; background: none; border: none; cursor: pointer; text-decoration: underline;">
                                                ↺ Сбросить на авто
                                            </button>
                                        @endif
                                    </div>

                                    <div style="display: flex; gap: 8px;">
                                        <input type="text"
                                               wire:model.live.debounce.400ms="prepTopic"
                                               placeholder="Например: Свойства логарифмов и ловушки ЦТ"
                                               class="edusfera-topic-input">

                                        <button type="button"
                                                wire:click="generateQuickPrep({{ $selectedLesson->id }})"
                                                wire:loading.attr="disabled"
                                                wire:target="generateQuickPrep"
                                                class="edusfera-btn-ai"
                                                style="flex-shrink: 0;">
                                            <svg wire:loading.remove wire:target="generateQuickPrep" style="width: 14px; height: 14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                            </svg>
                                            <svg wire:loading wire:target="generateQuickPrep" style="width: 14px; height: 14px; animation: spin 1s linear infinite;" fill="none" viewBox="0 0 24 24">
                                                <circle style="opacity: 0.25;" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                <path style="opacity: 0.75;" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                            </svg>
                                            <span>{{ $quickPrepPlan && $prepLessonId === $selectedLesson->id ? 'Обновить' : 'Составить' }}</span>
                                        </button>
                                    </div>
                                </div>

                                {{-- Generated Plan Result --}}
                                @if ($quickPrepPlan && $prepLessonId === $selectedLesson->id)
                                    <div class="edusfera-plan-box"
                                         x-data="{ copied: false }">
                                        <div style="display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid #fef3c7; padding-bottom: 8px; margin-bottom: 8px;">
                                            <span style="font-size: 10px; font-weight: 800; font-family: monospace; text-transform: uppercase; color: #b45309;">
                                                План занятия • ЦТ/ЦЭ 2026
                                            </span>
                                            <div style="display: flex; align-items: center; gap: 8px;">
                                                <button type="button"
                                                        @click="navigator.clipboard.writeText($refs.planText.innerText); copied = true; setTimeout(() => copied = false, 2000)"
                                                        style="font-size: 11px; font-weight: 700; color: #b45309; background: none; border: none; cursor: pointer; text-decoration: underline;">
                                                    <span x-show="!copied">📋 Скопировать</span>
                                                    <span x-show="copied" style="color: #059669;">✓ Скопировано!</span>
                                                </button>
                                                <button type="button" wire:click="closeQuickPrep" style="font-size: 12px; color: #94a3b8; background: none; border: none; cursor: pointer;">
                                                    ✕
                                                </button>
                                            </div>
                                        </div>
                                        <div x-ref="planText" style="font-size: 12px; line-height: 1.6; max-height: 220px; overflow-y: auto; white-space: pre-line; color: #334155;">
                                            {!! nl2br(e($quickPrepPlan)) !!}
                                        </div>
                                    </div>
                                @endif
                            </div>
                        @endif

                        {{-- Action Buttons --}}
                        <div style="display: flex; flex-direction: column; gap: 10px; margin-top: 4px;">
                            {{-- Primary CTA: Enter Classroom --}}
                            @if ($selectedLesson->status !== 'cancelled')
                                <a href="{{ $classUrl }}"
                                   target="_blank" rel="noopener noreferrer"
                                   class="edusfera-btn-primary">
                                    <svg style="width: 18px; height: 18px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                    </svg>
                                    <span>{{ $isExternalClass ? 'Подключиться к видеоуроку' : 'Войти в виртуальный класс' }}</span>
                                </a>
                            @endif

                            {{-- Secondary Row --}}
                            <div style="display: flex; align-items: center; gap: 8px;">
                                @if ($selectedLesson->payment_status !== 'paid' && $isTutorUser)
                                    <button type="button"
                                            wire:click="markLessonPaidDirectly({{ $selectedLesson->id }})"
                                            class="edusfera-btn-secondary"
                                            style="color: #059669; border-color: #a7f3d0; background: #ecfdf5;">
                                        <svg style="width: 14px; height: 14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                        </svg>
                                        <span>Отметить оплату</span>
                                    </button>
                                @endif

                                <a href="{{ $gcalLink }}" target="_blank" rel="noopener noreferrer" class="edusfera-btn-secondary">
                                    <svg style="width: 14px; height: 14px; color: #0284c7;" viewBox="0 0 24 24" fill="currentColor">
                                        <path d="M19 4h-1V2h-2v2H8V2H6v2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 16H5V9h14v11z"/>
                                    </svg>
                                    <span>В Google Calendar</span>
                                </a>

                                <a href="{{ route('filament.admin.resources.lessons.view', $selectedLesson) }}" class="edusfera-btn-secondary" style="flex: 0 0 auto; padding: 10px 14px;" title="Все подробности">
                                    <span>Детали</span>
                                    <svg style="width: 13px; height: 13px; color: #94a3b8;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                    </svg>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
</x-filament-panels::page>
