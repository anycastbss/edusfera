<!DOCTYPE html>
<html lang="ru" class="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>Edusfera • Учительская (Teacher Studio) — Банк проверочных работ и ключей</title>
    <meta name="description" content="Рабочее пространство школьного учителя: проверочные работы, ключи и интерактивная доска для уроков на Edusfera.by.">
    <meta name="robots" content="noindex, nofollow">
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    @vite(['resources/css/app.css'])

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Geist:wght@300;400;500;600;700;800&family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        @font-face {
            font-family: 'Rimma Sans';
            src: url('{{ asset("fonts/Rimma_sans-Bold.ttf") }}') format('truetype');
            font-weight: 700;
            font-style: normal;
            font-display: swap;
        }
        .font-rimma {
            font-family: 'Rimma Sans', 'Inter', system-ui, sans-serif !important;
        }

        /* Default Light Theme styles */
        html:not(.dark), html:not(.dark) body {
            background-color: #F8FAFC !important;
            background: #F8FAFC !important;
            color: #0F172A !important;
        }

        /* Dark Theme overrides */
        html.dark, html.dark body {
            background-color: #0B0B0E !important;
            background: #0B0B0E !important;
            color: #FFFFFF !important;
        }

        /* Ambient glow in dark mode only */
        .ambient-glow {
            display: none;
        }
        html.dark .ambient-glow {
            display: block;
        }
    </style>

    <script>
        // Init theme immediately before render to prevent flash
        (function() {
            const savedTheme = localStorage.getItem('edusfera_theme') || 'light';
            if (savedTheme === 'dark') {
                document.documentElement.classList.add('dark');
                document.documentElement.classList.remove('light');
            } else {
                document.documentElement.classList.remove('dark');
                document.documentElement.classList.add('light');
            }
        })();
    </script>
</head>
<body class="min-h-screen font-sans selection:bg-[#C6FF33] selection:text-black antialiased transition-colors duration-200">
    
    {{-- Glow background aura (Visible in Dark mode) --}}
    <div class="ambient-glow fixed inset-0 overflow-hidden pointer-events-none z-0">
        <div class="absolute -top-32 left-1/2 -translate-x-1/2 w-[700px] h-[400px] bg-gradient-to-b from-[#7D39EB]/20 via-[#38BDF8]/10 to-transparent blur-[120px] opacity-70"></div>
        <div class="absolute bottom-10 right-10 w-[500px] h-[300px] bg-gradient-to-t from-[#C6FF33]/15 to-transparent blur-[140px] opacity-50"></div>
    </div>

    {{-- Main Container --}}
    <div class="relative z-10 max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8 space-y-6">
        
        {{-- Navigation Header (Edusfera Design System) --}}
        <header class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 p-4 sm:p-5 rounded-2xl bg-white dark:bg-[#121214] border border-slate-200/90 dark:border-white/10 shadow-sm transition-colors">
            <div class="flex items-center gap-3">
                <a href="{{ route('home') }}" class="flex items-center gap-3 group">
                    {{-- Official Edusfera Geometric Rhombus Logo --}}
                    <div class="w-10 h-10 rounded-xl bg-[#7D39EB] flex items-center justify-center shadow-sm shrink-0 group-hover:scale-105 transition-transform">
                        <svg width="22" height="22" viewBox="0 0 64 64" fill="none">
                            <path d="M32 10L54 32L32 54L10 32L32 10Z" stroke="#C6FF33" stroke-width="6" stroke-linejoin="round" />
                            <path d="M32 22L42 32L32 42L22 32L32 22Z" fill="#C6FF33" />
                        </svg>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="font-rimma text-2xl font-bold tracking-tight text-slate-900 dark:text-white">Edusfera</span>
                            <span class="px-2 py-0.5 rounded-md text-[11px] font-bold bg-[#7D39EB]/10 dark:bg-[#7D39EB]/20 text-[#7D39EB] dark:text-[#c084fc] border border-[#7D39EB]/20">Учительская</span>
                        </div>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Банк проверочных работ и ключей ответов</p>
                    </div>
                </a>
            </div>

            <div class="flex items-center gap-2.5 w-full sm:w-auto justify-end">
                {{-- Theme Switcher Button --}}
                <button 
                    type="button" 
                    onclick="toggleTheme()" 
                    id="theme-toggle-btn"
                    class="px-3 py-2 rounded-xl text-xs font-semibold bg-slate-100 hover:bg-slate-200 dark:bg-white/10 dark:hover:bg-white/15 text-slate-700 dark:text-slate-200 border border-slate-200 dark:border-white/10 transition-all flex items-center gap-1.5 cursor-pointer shadow-sm"
                    title="Переключить тему оформления"
                >
                    <span id="theme-toggle-icon">🌙</span>
                    <span id="theme-toggle-text">Тёмная тема</span>
                </button>

                {{-- Primary CTA: New Test --}}
                <a 
                    href="{{ route('generator') }}" 
                    class="px-4 sm:px-5 py-2 sm:py-2.5 rounded-xl text-xs sm:text-sm font-bold bg-[#7D39EB] hover:bg-[#6827d6] text-white shadow-sm hover:shadow-md active:scale-95 transition-all flex items-center gap-2 cursor-pointer whitespace-nowrap"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                    </svg>
                    <span>+ Создать новый тест</span>
                </a>
            </div>
        </header>

        {{-- Flash Status Message --}}
        @if(session('status'))
            <div class="p-4 rounded-xl bg-emerald-50 dark:bg-emerald-500/15 border border-emerald-200 dark:border-emerald-500/40 text-emerald-800 dark:text-emerald-300 text-xs flex items-center justify-between shadow-sm">
                <span class="font-medium">{{ session('status') }}</span>
                <button onclick="this.parentElement.remove()" class="text-emerald-700 dark:text-emerald-300 hover:text-black dark:hover:text-white text-base leading-none">&times;</button>
            </div>
        @endif

        {{-- Teacher Metric Cards (Unified, Airy Stats Bar) --}}
        @if($email && $totalOrders > 0)
            <div class="p-4 sm:p-5 rounded-2xl bg-white dark:bg-[#121214] border border-slate-200/90 dark:border-white/10 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4 transition-colors">
                <div class="flex flex-wrap items-center gap-6 sm:gap-8">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-purple-50 dark:bg-purple-500/10 text-[#7D39EB] dark:text-[#c084fc] flex items-center justify-center font-bold text-lg">
                            📚
                        </div>
                        <div>
                            <div class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white leading-none">
                                {{ $totalOrders }}
                            </div>
                            <span class="text-xs text-slate-500 dark:text-slate-400">работ в архиве</span>
                        </div>
                    </div>

                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-lime-50 dark:bg-lime-500/10 text-emerald-600 dark:text-[#C6FF33] flex items-center justify-center font-bold text-lg">
                            📝
                        </div>
                        <div>
                            <div class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white leading-none">
                                {{ $totalVariants }}
                            </div>
                            <span class="text-xs text-slate-500 dark:text-slate-400">готовых вариантов</span>
                        </div>
                    </div>

                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-sky-50 dark:bg-sky-500/10 text-sky-600 dark:text-[#38BDF8] flex items-center justify-center font-bold text-lg">
                            ⏱️
                        </div>
                        <div>
                            <div class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white leading-none">
                                ~{{ $savedHours }} ч
                            </div>
                            <span class="text-xs text-slate-500 dark:text-slate-400">сэкономлено</span>
                        </div>
                    </div>
                </div>

                {{-- Connected Email & Actions --}}
                <div class="flex items-center gap-2 pt-3 md:pt-0 border-t md:border-t-0 border-slate-100 dark:border-white/5 text-xs">
                    <div class="text-slate-500 dark:text-slate-400">
                        Привязан к: <b class="text-slate-800 dark:text-slate-200">{{ $email }}</b>
                    </div>
                    <button 
                        type="button" 
                        onclick="copyMagicLink()" 
                        class="px-2.5 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 dark:bg-white/10 dark:hover:bg-white/15 text-slate-700 dark:text-slate-300 font-medium transition-all cursor-pointer"
                        title="Скопировать ссылку быстрого входа без пароля"
                    >
                        🔗 Вход в 1 клик
                    </button>
                    <button 
                        type="button" 
                        onclick="document.getElementById('switch-email-modal').classList.remove('hidden')" 
                        class="px-2.5 py-1.5 rounded-lg text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-white font-medium hover:underline transition-all cursor-pointer"
                    >
                        Сменить ↺
                    </button>
                </div>
            </div>
        @endif

        {{-- Email Identification Prompt (if no email found) --}}
        @if(! $email)
            <div class="p-8 sm:p-12 rounded-2xl bg-white dark:bg-[#121214] border border-slate-200/90 dark:border-white/10 shadow-sm text-center max-w-xl mx-auto space-y-6">
                <div class="w-16 h-16 rounded-2xl bg-purple-50 dark:bg-white/5 border border-purple-200 dark:border-white/10 flex items-center justify-center mx-auto text-[#7D39EB] dark:text-[#C6FF33] shadow-sm">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                </div>
                <div class="space-y-2">
                    <h2 class="text-2xl font-bold text-slate-900 dark:text-white tracking-tight">Открыть архив моих материалов</h2>
                    <p class="text-xs sm:text-sm text-slate-600 dark:text-slate-300 max-w-md mx-auto leading-relaxed">
                        Введите email, который вы указывали при составлении тестов. Все сгенерированные PDF и ключи ответов откроются мгновенно без пароля.
                    </p>
                </div>

                <form action="{{ route('generator.cabinet.access') }}" method="POST" class="flex flex-col sm:flex-row gap-2.5 max-w-md mx-auto pt-2">
                    @csrf
                    <input 
                        type="email" 
                        name="email" 
                        required 
                        placeholder="teacher@school.by"
                        class="flex-1 px-4 py-3 rounded-xl bg-slate-50 dark:bg-black/60 border border-slate-300 dark:border-white/20 text-slate-900 dark:text-white placeholder-slate-400 text-sm focus:outline-none focus:border-[#7D39EB] transition-colors"
                    >
                    <button 
                        type="submit" 
                        class="px-6 py-3 rounded-xl bg-[#7D39EB] hover:bg-[#6827d6] text-white font-bold text-sm shadow-sm transition-all cursor-pointer whitespace-nowrap"
                    >
                        Найти мои тесты →
                    </button>
                </form>
            </div>
        @else

            {{-- Controls & Filter Bar --}}
            <div class="space-y-3">
                <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3">
                    {{-- Grade Group Filters --}}
                    <div class="flex items-center gap-1.5 overflow-x-auto pb-1 sm:pb-0 scrollbar-none text-xs font-medium">
                        <button 
                            type="button"
                            onclick="filterByGradeGroup('all')" 
                            class="grade-tab px-3.5 py-2 rounded-xl font-bold bg-[#7D39EB] text-white shadow-sm transition-all cursor-pointer"
                            data-target-grade="all"
                        >
                            Все классы
                        </button>
                        <button 
                            type="button"
                            onclick="filterByGradeGroup('5-6')" 
                            class="grade-tab px-3 py-2 rounded-xl bg-white dark:bg-[#121214] text-slate-700 dark:text-slate-300 border border-slate-200/90 dark:border-white/10 hover:border-slate-300 dark:hover:border-white/20 transition-all cursor-pointer whitespace-nowrap"
                            data-target-grade="5-6"
                        >
                            5–6 классы
                        </button>
                        <button 
                            type="button"
                            onclick="filterByGradeGroup('7-9')" 
                            class="grade-tab px-3 py-2 rounded-xl bg-white dark:bg-[#121214] text-slate-700 dark:text-slate-300 border border-slate-200/90 dark:border-white/10 hover:border-slate-300 dark:hover:border-white/20 transition-all cursor-pointer whitespace-nowrap"
                            data-target-grade="7-9"
                        >
                            7–9 классы
                        </button>
                        <button 
                            type="button"
                            onclick="filterByGradeGroup('10-11')" 
                            class="grade-tab px-3 py-2 rounded-xl bg-white dark:bg-[#121214] text-slate-700 dark:text-slate-300 border border-slate-200/90 dark:border-white/10 hover:border-slate-300 dark:hover:border-white/20 transition-all cursor-pointer whitespace-nowrap"
                            data-target-grade="10-11"
                        >
                            10–11 классы
                        </button>
                    </div>

                    {{-- Live Search Input --}}
                    <div class="relative w-full sm:w-72">
                        <input 
                            type="text" 
                            id="search-input" 
                            oninput="filterMaterials()" 
                            placeholder="Поиск по теме..." 
                            class="w-full pl-9 pr-4 py-2 rounded-xl bg-white dark:bg-[#121214] border border-slate-200/90 dark:border-white/15 text-xs text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:border-[#7D39EB] transition-colors shadow-sm"
                        >
                        <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                </div>

                {{-- Subject Filters --}}
                @if($distinctSubjects->count() > 1)
                    <div class="flex items-center gap-1.5 overflow-x-auto scrollbar-none text-xs">
                        <button 
                            type="button"
                            onclick="filterBySubject('all')" 
                            class="subject-tab px-3 py-1.5 rounded-lg font-bold bg-slate-900 dark:bg-white text-white dark:text-slate-900 transition-all cursor-pointer"
                            data-target-subject="all"
                        >
                            Все предметы ({{ $orders->count() }})
                        </button>
                        @foreach($distinctSubjects as $subj)
                            <button 
                                type="button"
                                onclick="filterBySubject('{{ addslashes($subj) }}')" 
                                class="subject-tab px-3 py-1.5 rounded-lg bg-slate-100 dark:bg-white/5 text-slate-600 dark:text-slate-300 border border-slate-200/90 dark:border-white/10 hover:border-slate-300 dark:hover:border-white/20 transition-all cursor-pointer whitespace-nowrap"
                                data-target-subject="{{ mb_strtolower($subj) }}"
                            >
                                {{ $subj }}
                            </button>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- Materials Grid --}}
            @if($orders->isEmpty())
                <div class="p-12 rounded-2xl bg-white dark:bg-[#121214] border border-slate-200/90 dark:border-white/10 text-center space-y-4 shadow-sm">
                    <div class="w-14 h-14 rounded-full bg-slate-100 dark:bg-white/5 border border-slate-200 dark:border-white/10 text-slate-400 flex items-center justify-center mx-auto">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                    </div>
                    <div class="space-y-1">
                        <p class="text-base text-slate-900 dark:text-white font-bold">Для адреса {{ $email }} пока нет сохраненных работ.</p>
                        <p class="text-xs text-slate-500 dark:text-slate-400 max-w-md mx-auto">Сгенерируйте первую контрольную — она моментально появится здесь для печати и вывода ключей на экран.</p>
                    </div>
                    <a href="{{ route('generator') }}" class="inline-flex items-center gap-1.5 px-5 py-2.5 rounded-xl bg-[#7D39EB] hover:bg-[#6827d6] text-white font-bold text-xs shadow-sm transition-all">
                        Сгенерировать первую работу →
                    </a>
                </div>
            @else
                <div id="materials-container" class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-5">
                    @foreach($orders as $item)
                        @php
                            $payload = is_array($item->payload) ? $item->payload : [];
                            $grade = $payload['grade'] ?? '7';
                            $gradeLabel = match($grade) {
                                'abiturient' => 'Абитуриент',
                                default => "{$grade} класс"
                            };
                            $numGrade = is_numeric($grade) ? (int)$grade : 11;
                            $gradeGroup = match(true) {
                                $numGrade <= 6 => '5-6',
                                $numGrade <= 9 => '7-9',
                                default => '10-11',
                            };
                            $workType = $payload['work_type'] ?? 'independent';
                            $workTypeTitle = match($workType) {
                                'control' => 'Контрольная работа',
                                'diagnostic' => 'Диагностическая работа',
                                'test' => 'Проверочный экспресс-тест',
                                'ct_ce', 'exam' => 'Пробный ЦЭ / ЦТ',
                                'olympiad' => 'Олимпиадная работа',
                                default => 'Самостоятельная работа'
                            };
                            $countA = $payload['count_a'] ?? 2;
                            $countB = $payload['count_b'] ?? 1;
                            $hasPdf = ! empty($item->pdf_path) || ! empty($item->generated_content);
                        @endphp

                        <div 
                            class="material-card p-5 sm:p-6 rounded-2xl bg-white dark:bg-[#121214] border border-slate-200/90 dark:border-white/10 shadow-[0_2px_12px_-2px_rgba(0,0,0,0.04)] dark:shadow-none hover:shadow-md hover:border-slate-300 dark:hover:border-white/20 transition-all flex flex-col justify-between space-y-4"
                            data-subject="{{ mb_strtolower($item->subject) }}"
                            data-grade-group="{{ $gradeGroup }}"
                            data-search="{{ mb_strtolower($item->subject . ' ' . $item->topic . ' ' . $gradeLabel . ' ' . $workTypeTitle . ' ' . $grade . ' класс') }}"
                        >
                            <div>
                                {{-- Card Header: Badges & Variant Count --}}
                                <div class="flex items-start justify-between gap-3">
                                    <div class="flex flex-wrap items-center gap-1.5">
                                        <span class="px-2.5 py-0.5 rounded-md text-[11px] font-bold bg-purple-50 dark:bg-purple-500/15 text-[#7D39EB] dark:text-[#c084fc] border border-purple-200 dark:border-purple-500/30">
                                            {{ $item->subject }}
                                        </span>
                                        <span class="px-2.5 py-0.5 rounded-md text-[11px] font-semibold bg-sky-50 dark:bg-sky-500/15 text-sky-700 dark:text-sky-300 border border-sky-200 dark:border-sky-500/30">
                                            {{ $gradeLabel }}
                                        </span>
                                        <span class="px-2.5 py-0.5 rounded-md text-[11px] font-medium bg-slate-100 dark:bg-white/10 text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-white/10">
                                            {{ $workTypeTitle }}
                                        </span>
                                    </div>

                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-lg bg-[#C6FF33]/30 dark:bg-[#C6FF33]/20 text-slate-900 dark:text-[#C6FF33] border border-[#C6FF33] text-xs font-bold shrink-0">
                                        {{ $item->variant_count }} вар.
                                    </span>
                                </div>

                                {{-- Topic Title --}}
                                <h3 class="text-base sm:text-lg font-bold text-slate-900 dark:text-white tracking-tight leading-snug pt-2.5 line-clamp-2" title="{{ $item->topic }}">
                                    {{ $item->topic }}
                                </h3>

                                {{-- Task specs & Date --}}
                                <div class="flex items-center justify-between text-xs text-slate-500 dark:text-slate-400 pt-2 border-t border-slate-100 dark:border-white/5 mt-3">
                                    <div class="flex items-center gap-3">
                                        <span>Часть А: <strong class="text-slate-800 dark:text-slate-200">{{ $countA }}</strong></span>
                                        <span>Часть Б: <strong class="text-slate-800 dark:text-slate-200">{{ $countB }}</strong></span>
                                        <span>Всего: <strong class="text-[#7D39EB] dark:text-[#c084fc] font-bold">{{ $countA + $countB }} зад.</strong></span>
                                    </div>
                                    <span class="text-[11px] text-slate-400">
                                        {{ $item->created_at->translatedFormat('d M Y') }}
                                    </span>
                                </div>
                            </div>

                            {{-- Clean Actions: 1 Primary CTA + 3 Utilities (NO DUPLICATES) --}}
                            <div class="space-y-2 pt-2 border-t border-slate-100 dark:border-white/5">
                                @if($item->isPaid() && $hasPdf)
                                    <a 
                                        href="{{ route('generator.download', ['order' => $item->order_number]) }}"
                                        class="w-full py-2.5 px-4 rounded-xl bg-[#C6FF33] hover:bg-[#bbf028] text-slate-950 font-bold text-xs tracking-tight shadow-sm hover:shadow flex items-center justify-center gap-2 transition-all cursor-pointer"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                        </svg>
                                        <span>Скачать PDF для печати (A4)</span>
                                    </a>
                                @elseif($item->status === 'generating')
                                    <div class="w-full py-2.5 px-4 rounded-xl bg-amber-50 dark:bg-amber-500/10 text-amber-700 dark:text-amber-300 font-bold text-xs flex items-center justify-center gap-2 border border-amber-200 dark:border-amber-500/30">
                                        <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        <span>Идёт генерация вариантов...</span>
                                    </div>
                                @else
                                    <a 
                                        href="{{ route('generator.checkout.hosted', ['order' => $item->order_number]) }}"
                                        class="w-full py-2.5 px-4 rounded-xl bg-[#7D39EB] hover:bg-[#6827d6] text-white font-bold text-xs flex items-center justify-center gap-1.5 shadow-sm"
                                    >
                                        <span>Завершить заказ ({{ number_format($item->amountByn(), 2) }} BYN)</span>
                                    </a>
                                @endif

                                {{-- 3 Clean Utility Buttons --}}
                                <div class="grid grid-cols-3 gap-1.5 text-xs">
                                    <a 
                                        href="{{ route('generator.board', $item->order_number) }}" 
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="py-2 px-2.5 rounded-lg bg-slate-100 hover:bg-slate-200/80 dark:bg-white/5 dark:hover:bg-white/10 text-slate-700 dark:text-slate-300 border border-slate-200/80 dark:border-white/10 transition-all flex items-center justify-center gap-1.5 font-medium"
                                        title="Открыть матрицу ключей на проектор для урока"
                                    >
                                        <span>🖥️</span>
                                        <span>На доску</span>
                                    </a>

                                    <button 
                                        type="button"
                                        onclick="copyShareLink('{{ route('generator.share', $item->order_number) }}')"
                                        class="py-2 px-2.5 rounded-lg bg-slate-100 hover:bg-slate-200/80 dark:bg-white/5 dark:hover:bg-white/10 text-slate-700 dark:text-slate-300 border border-slate-200/80 dark:border-white/10 transition-all flex items-center justify-center gap-1.5 font-medium cursor-pointer"
                                        title="Поделиться тестом с коллегой в чате"
                                    >
                                        <span>🔗</span>
                                        <span>Коллегам</span>
                                    </button>

                                    <a 
                                        href="{{ route('generator') }}?subject={{ urlencode($item->subject) }}&topic={{ urlencode($item->topic) }}&grade={{ $grade }}"
                                        class="py-2 px-2.5 rounded-lg bg-slate-100 hover:bg-slate-200/80 dark:bg-white/5 dark:hover:bg-white/10 text-slate-700 dark:text-slate-300 border border-slate-200/80 dark:border-white/10 transition-all flex items-center justify-center gap-1.5 font-medium"
                                        title="Составить еще новые варианты по этой теме"
                                    >
                                        <span>↺</span>
                                        <span>Повтор</span>
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- Empty state for active filters --}}
                <div id="no-filter-results" class="hidden p-8 rounded-2xl bg-white dark:bg-[#121214] border border-slate-200 dark:border-white/10 text-center space-y-3">
                    <p class="text-sm font-semibold text-slate-700 dark:text-slate-300">По выбранным фильтрам работ не найдено.</p>
                    <button 
                        type="button" 
                        onclick="resetFilters()"
                        class="px-4 py-2 rounded-xl text-xs font-bold bg-slate-100 hover:bg-slate-200 dark:bg-white/10 dark:hover:bg-white/15 text-slate-700 dark:text-white transition-all cursor-pointer"
                    >
                        Сбросить фильтры
                    </button>
                </div>
            @endif

        @endif

    </div>

    {{-- Switch Email Modal --}}
    <div id="switch-email-modal" class="hidden fixed inset-0 z-50 bg-black/60 dark:bg-black/85 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="w-full max-w-md p-6 sm:p-7 rounded-2xl bg-white dark:bg-[#121214] border border-slate-200 dark:border-white/20 shadow-2xl space-y-4">
            <div class="flex items-center justify-between">
                <h3 class="text-base font-bold text-slate-900 dark:text-white">Переключить на другой email</h3>
                <button type="button" onclick="document.getElementById('switch-email-modal').classList.add('hidden')" class="text-slate-400 hover:text-slate-900 dark:hover:text-white text-xl leading-none cursor-pointer">&times;</button>
            </div>
            <p class="text-xs text-slate-500 dark:text-slate-300 leading-relaxed">
                Введите другой адрес электронной почты, чтобы посмотреть привязанные к нему проверочные работы.
            </p>
            <form action="{{ route('generator.cabinet.access') }}" method="POST" class="space-y-3 pt-1">
                @csrf
                <input 
                    type="email" 
                    name="email" 
                    required 
                    placeholder="teacher@school.by"
                    class="w-full px-4 py-3 rounded-xl bg-slate-50 dark:bg-black/80 border border-slate-300 dark:border-white/25 text-slate-900 dark:text-white placeholder-slate-400 text-sm focus:outline-none focus:border-[#7D39EB]"
                >
                <div class="flex justify-end gap-2 pt-2">
                    <button 
                        type="button" 
                        onclick="document.getElementById('switch-email-modal').classList.add('hidden')"
                        class="px-4 py-2.5 rounded-xl text-xs font-medium text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white cursor-pointer"
                    >
                        Отмена
                    </button>
                    <button 
                        type="submit" 
                        class="px-5 py-2.5 rounded-xl bg-[#7D39EB] hover:bg-[#6827d6] text-white font-bold text-xs shadow-sm cursor-pointer"
                    >
                        Загрузить материалы →
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Toast Notification --}}
    <div id="toast" class="fixed bottom-6 right-6 z-50 transform translate-y-20 opacity-0 transition-all duration-300 px-5 py-3 rounded-xl bg-slate-900 dark:bg-black/95 text-white border border-slate-700 dark:border-[#C6FF33]/50 text-xs shadow-xl flex items-center gap-2.5">
        <svg class="w-4 h-4 text-[#C6FF33]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
        </svg>
        <span id="toast-message" class="font-medium">Ссылка скопирована</span>
    </div>

    <script>
        // State for filters
        let currentSubject = 'all';
        let currentGradeGroup = 'all';

        // Theme Toggle Functionality
        function toggleTheme() {
            const isDark = document.documentElement.classList.contains('dark');
            const newTheme = isDark ? 'light' : 'dark';
            
            if (newTheme === 'dark') {
                document.documentElement.classList.add('dark');
                document.documentElement.classList.remove('light');
                document.getElementById('theme-toggle-icon').textContent = '☀️';
                document.getElementById('theme-toggle-text').textContent = 'Светлая тема';
            } else {
                document.documentElement.classList.remove('dark');
                document.documentElement.classList.add('light');
                document.getElementById('theme-toggle-icon').textContent = '🌙';
                document.getElementById('theme-toggle-text').textContent = 'Тёмная тема';
            }
            localStorage.setItem('edusfera_theme', newTheme);
        }

        // Initialize Theme Toggle Button text
        document.addEventListener('DOMContentLoaded', () => {
            const isDark = document.documentElement.classList.contains('dark');
            const iconEl = document.getElementById('theme-toggle-icon');
            const textEl = document.getElementById('theme-toggle-text');
            if (iconEl && textEl) {
                iconEl.textContent = isDark ? '☀️' : '🌙';
                textEl.textContent = isDark ? 'Светлая тема' : 'Тёмная тема';
            }
        });

        // Grade Group Filter
        function filterByGradeGroup(group) {
            currentGradeGroup = group;
            document.querySelectorAll('.grade-tab').forEach(el => {
                const target = el.getAttribute('data-target-grade');
                if (target === group) {
                    el.className = 'grade-tab px-3.5 py-2 rounded-xl font-bold bg-[#7D39EB] text-white shadow-sm transition-all cursor-pointer';
                } else {
                    el.className = 'grade-tab px-3 py-2 rounded-xl bg-white dark:bg-[#121214] text-slate-700 dark:text-slate-300 border border-slate-200/90 dark:border-white/10 hover:border-slate-300 dark:hover:border-white/20 transition-all cursor-pointer whitespace-nowrap';
                }
            });
            applyAllFilters();
        }

        // Subject Filter
        function filterBySubject(subj) {
            currentSubject = subj.toLowerCase();
            document.querySelectorAll('.subject-tab').forEach(el => {
                const target = el.getAttribute('data-target-subject');
                if (target === currentSubject) {
                    el.className = 'subject-tab px-3 py-1.5 rounded-lg font-bold bg-slate-900 dark:bg-white text-white dark:text-slate-900 transition-all cursor-pointer';
                } else {
                    el.className = 'subject-tab px-3 py-1.5 rounded-lg bg-slate-100 dark:bg-white/5 text-slate-600 dark:text-slate-300 border border-slate-200/90 dark:border-white/10 hover:border-slate-300 dark:hover:border-white/20 transition-all cursor-pointer whitespace-nowrap';
                }
            });
            applyAllFilters();
        }

        // Search Filter
        function filterMaterials() {
            applyAllFilters();
        }

        // Combined Filter Engine
        function applyAllFilters() {
            const query = (document.getElementById('search-input')?.value || '').toLowerCase().trim();
            const cards = document.querySelectorAll('.material-card');
            let visibleCount = 0;

            cards.forEach(card => {
                const cardSubject = card.getAttribute('data-subject') || '';
                const cardGradeGroup = card.getAttribute('data-grade-group') || '';
                const cardSearch = card.getAttribute('data-search') || '';

                const matchesSubject = (currentSubject === 'all' || cardSubject === currentSubject);
                const matchesGrade = (currentGradeGroup === 'all' || cardGradeGroup === currentGradeGroup);
                const matchesQuery = (!query || cardSearch.includes(query));

                if (matchesSubject && matchesGrade && matchesQuery) {
                    card.classList.remove('hidden');
                    visibleCount++;
                } else {
                    card.classList.add('hidden');
                }
            });

            const noResEl = document.getElementById('no-filter-results');
            if (noResEl) {
                if (visibleCount === 0 && cards.length > 0) {
                    noResEl.classList.remove('hidden');
                } else {
                    noResEl.classList.add('hidden');
                }
            }
        }

        // Reset all filters
        function resetFilters() {
            const searchInput = document.getElementById('search-input');
            if (searchInput) searchInput.value = '';
            filterByGradeGroup('all');
            filterBySubject('all');
        }

        // Copy download link
        function copyDownloadLink(url) {
            navigator.clipboard.writeText(url).then(() => {
                showToast('Прямая ссылка на PDF скопирована в буфер');
            });
        }

        // Copy share link
        function copyShareLink(url) {
            navigator.clipboard.writeText(url).then(() => {
                showToast('Ссылка для коллег скопирована');
            });
        }

        // Copy magic link
        function copyMagicLink() {
            @if(isset($magicUrl))
                const magicUrl = '{{ $magicUrl }}';
            @else
                const magicUrl = window.location.href;
            @endif
            navigator.clipboard.writeText(magicUrl).then(() => {
                showToast('Ссылка быстрого входа без пароля скопирована');
            });
        }

        // Toast feedback
        function showToast(msg) {
            const toast = document.getElementById('toast');
            const toastMsg = document.getElementById('toast-message');
            if (!toast || !toastMsg) return;
            toastMsg.textContent = msg;
            toast.classList.remove('translate-y-20', 'opacity-0');
            toast.classList.add('translate-y-0', 'opacity-100');
            setTimeout(() => {
                toast.classList.remove('translate-y-0', 'opacity-100');
                toast.classList.add('translate-y-20', 'opacity-0');
            }, 3000);
        }
    </script>
</body>
</html>
