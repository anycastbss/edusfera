<!DOCTYPE html>
<html lang="ru" class="dark" style="background-color: #000000 !important; color-scheme: dark;">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>EDUSFERA • Учительская (Teacher Studio) — Проверочные работы, ключи и интерактивная доска | Edusfera</title>
    <meta name="description" content="Рабочее пространство школьного учителя: проверочные работы, ключи и интерактивная доска для уроков на Edusfera.by.">
    <meta name="robots" content="noindex, nofollow">
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    @vite(['resources/css/app.css'])

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Geist:wght@300;400;500;600;700&family=Inter:wght@400;500;600;700;800&family=Silkscreen:wght@400;700&display=swap" rel="stylesheet">

    <style>
        /* Force dark obsidian ground to completely neutralize any light mode CSS inheritance */
        html, body.aurora-body {
            background-color: #040508 !important;
            background: #040508 !important;
            color: #ffffff !important;
        }
        .cabinet-card {
            background: rgba(14, 18, 27, 0.85) !important;
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.12) !important;
        }
        .cabinet-card:hover {
            border-color: rgba(255, 255, 255, 0.22) !important;
            background: rgba(18, 23, 35, 0.95) !important;
        }
    </style>
</head>
<body class="aurora-body min-h-screen font-sans selection:bg-[#C6FF33] selection:text-black antialiased" style="background-color: #040508 !important; color: #ffffff !important;">
    
    {{-- Glow background aura --}}
    <div class="fixed inset-0 overflow-hidden pointer-events-none z-0">
        <div class="absolute -top-32 left-1/2 -translate-x-1/2 w-[800px] h-[450px] bg-gradient-to-b from-[#7D39EB]/25 via-[#38BDF8]/10 to-transparent blur-[120px] opacity-70"></div>
        <div class="absolute bottom-10 right-10 w-[600px] h-[350px] bg-gradient-to-t from-[#C6FF33]/15 to-transparent blur-[140px] opacity-50"></div>
        <div class="absolute top-1/2 left-0 w-[400px] h-[400px] bg-gradient-to-r from-[#7D39EB]/10 to-transparent blur-[100px] opacity-40"></div>
    </div>

    {{-- Main Container --}}
    <div class="relative z-10 max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-10 space-y-8">
        
        {{-- Navigation Header --}}
        <header class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 p-4 sm:p-5 rounded-2xl cabinet-card shadow-[0_8px_32px_0_rgba(0,0,0,0.6)]">
            <div class="flex items-center gap-3">
                <a href="{{ route('generator') }}" class="flex items-center gap-3 group">
                    <div class="w-11 h-11 rounded-xl bg-black border border-white/20 flex items-center justify-center shadow-[0_0_15px_rgba(198,255,51,0.2)] group-hover:border-[#C6FF33] transition-colors shrink-0">
                        <svg class="w-6 h-6 text-[#C6FF33]" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M12 2L2 12l10 10 10-10L12 2zm0 4.5l5.5 5.5-5.5 5.5-5.5-5.5L12 6.5z"/>
                        </svg>
                    </div>
                    <div class="space-y-0.5">
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="font-extrabold text-base sm:text-lg tracking-tight text-white font-mono">EDUSFERA • Учительская</span>
                            <span class="px-2 py-0.5 rounded-md text-[10px] font-bold uppercase tracking-wider bg-gradient-to-r from-[#7D39EB]/30 to-[#C6FF33]/20 text-[#C6FF33] border border-[#C6FF33]/30 shadow-[0_0_10px_rgba(198,255,51,0.12)]">Teacher Studio</span>
                        </div>
                        <p class="text-xs text-slate-300 font-medium leading-relaxed">Рабочее пространство школьного учителя: проверочные работы, ключи и интерактивная доска</p>
                    </div>
                </a>
            </div>

            <div class="flex items-center gap-2 sm:gap-3 w-full sm:w-auto justify-end">
                @if($user)
                    <a 
                        href="{{ auth()->user()->isTutor() ? (request()->is('m*') ? '/m' : '/admin') : '/' }}"
                        class="px-4 py-2.5 rounded-xl text-xs font-semibold bg-white/10 hover:bg-white/15 text-slate-200 hover:text-white border border-white/15 transition-all shadow-sm"
                    >
                        Панель репетитора →
                    </a>
                @endif
                <a 
                    href="{{ route('generator') }}" 
                    class="px-5 py-2.5 rounded-xl text-xs font-extrabold bg-[#C6FF33] hover:bg-[#d4ff59] text-black shadow-[0_0_25px_rgba(198,255,51,0.35)] active:scale-95 transition-all flex items-center gap-1.5 cursor-pointer"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                    </svg>
                    <span>Создать новый тест</span>
                </a>
            </div>
        </header>

        {{-- Flash Status Message --}}
        @if(session('status'))
            <div class="p-4 rounded-xl bg-emerald-500/15 border border-emerald-500/40 text-emerald-300 text-xs flex items-center justify-between shadow-lg">
                <span class="font-medium">{{ session('status') }}</span>
                <button onclick="this.parentElement.remove()" class="text-emerald-300 hover:text-white text-base leading-none">&times;</button>
            </div>
        @endif

        {{-- Teacher Metric Cards (KPI) --}}
        @if($email && $totalOrders > 0)
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 sm:gap-4">
                <div class="p-5 rounded-2xl cabinet-card space-y-1.5 shadow-lg">
                    <span class="text-[11px] font-mono uppercase tracking-wider text-slate-400 font-semibold">Проверочных работ</span>
                    <div class="text-3xl font-extrabold text-white font-mono tracking-tight">{{ $totalOrders }}</div>
                    <span class="text-[11px] text-slate-500 block">сохранено в архиве</span>
                </div>

                <div class="p-5 rounded-2xl cabinet-card space-y-1.5 shadow-lg border-l-2 border-l-[#C6FF33]/60">
                    <span class="text-[11px] font-mono uppercase tracking-wider text-slate-400 font-semibold">Вариантов заданий</span>
                    <div class="text-3xl font-extrabold text-[#C6FF33] font-mono tracking-tight">{{ $totalVariants }}</div>
                    <span class="text-[11px] text-slate-500 block">с готовыми ключами</span>
                </div>

                <div class="p-5 rounded-2xl cabinet-card space-y-1.5 shadow-lg border-l-2 border-l-[#38BDF8]/60">
                    <span class="text-[11px] font-mono uppercase tracking-wider text-slate-400 font-semibold">Сэкономлено времени</span>
                    <div class="text-3xl font-extrabold text-[#38BDF8] font-mono tracking-tight">~{{ $savedHours }} ч</div>
                    <span class="text-[11px] text-slate-500 block">ручного составления</span>
                </div>

                <div class="p-5 rounded-2xl cabinet-card space-y-1.5 shadow-lg relative">
                    <span class="text-[11px] font-mono uppercase tracking-wider text-slate-400 font-semibold">Привязан к email</span>
                    <div class="text-xs font-mono font-bold text-white truncate max-w-[170px]" title="{{ $email }}">
                        {{ $email }}
                    </div>
                    <div class="flex items-center gap-2 pt-0.5">
                        @if(! empty($magicLink))
                            <button 
                                type="button"
                                onclick="copyDownloadLink('{{ $magicLink }}')"
                                class="text-[11px] font-semibold text-sky-400 hover:text-sky-300 hover:underline cursor-pointer"
                                title="Скопировать Magic Link для мгновенного входа без пароля на любом устройстве"
                            >
                                Magic Link 🔗
                            </button>
                            <span class="text-slate-600 text-xs">•</span>
                        @endif
                        <button 
                            type="button"
                            onclick="document.getElementById('switch-email-modal').classList.remove('hidden')"
                            class="text-[11px] font-medium text-[#C6FF33] hover:underline cursor-pointer"
                        >
                            Сменить ↺
                        </button>
                    </div>
                </div>
            </div>
        @endif

        {{-- Email Identification Prompt (if no email found) --}}
        @if(! $email)
            <div class="p-8 sm:p-12 rounded-3xl cabinet-card text-center max-w-xl mx-auto space-y-6 shadow-2xl border border-white/20">
                <div class="w-16 h-16 rounded-2xl bg-white/5 border border-white/15 flex items-center justify-center mx-auto text-[#C6FF33] shadow-[0_0_20px_rgba(198,255,51,0.2)]">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                </div>
                <div class="space-y-2">
                    <h2 class="text-2xl font-bold text-white tracking-tight">Открыть архив моих материалов</h2>
                    <p class="text-xs sm:text-sm text-slate-300 max-w-md mx-auto leading-relaxed">
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
                        class="flex-1 px-4 py-3 rounded-xl bg-black/80 border border-white/25 text-white placeholder-slate-500 text-sm focus:outline-none focus:border-[#C6FF33] transition-colors shadow-inner"
                    >
                    <button 
                        type="submit" 
                        class="px-6 py-3 rounded-xl bg-[#C6FF33] hover:bg-[#d4ff59] text-black font-extrabold text-sm shadow-[0_0_20px_rgba(198,255,51,0.3)] transition-all cursor-pointer whitespace-nowrap"
                    >
                        Найти мои тесты →
                    </button>
                </form>
            </div>
        @else

            {{-- Controls & Search Bar --}}
            <div class="space-y-3 pt-2">
                {{-- Grade & Subject Filters Bar --}}
                <div class="flex flex-col lg:flex-row items-stretch lg:items-center justify-between gap-3">
                    {{-- Grade / Class Filters --}}
                    <div class="flex items-center gap-1.5 p-1 rounded-2xl bg-white/[0.04] border border-white/10 overflow-x-auto scrollbar-none shadow-sm">
                        <span class="text-[11px] font-mono uppercase tracking-wider text-slate-400 px-2.5 font-bold shrink-0">Классы:</span>
                        <button 
                            type="button" 
                            onclick="filterByGrade('all')" 
                            id="grade-tab-all"
                            class="grade-tab px-3.5 py-1.5 rounded-xl text-xs font-bold bg-[#C6FF33] text-black shadow-sm transition-all cursor-pointer whitespace-nowrap"
                        >
                            Все классы
                        </button>
                        <button 
                            type="button" 
                            onclick="filterByGrade('5-6')" 
                            id="grade-tab-5-6"
                            class="grade-tab px-3.5 py-1.5 rounded-xl text-xs font-semibold bg-white/5 hover:bg-white/10 text-slate-300 hover:text-white border border-white/10 transition-all cursor-pointer whitespace-nowrap"
                        >
                            5–6 классы
                        </button>
                        <button 
                            type="button" 
                            onclick="filterByGrade('7-9')" 
                            id="grade-tab-7-9"
                            class="grade-tab px-3.5 py-1.5 rounded-xl text-xs font-semibold bg-white/5 hover:bg-white/10 text-slate-300 hover:text-white border border-white/10 transition-all cursor-pointer whitespace-nowrap"
                        >
                            7–9 классы
                        </button>
                        <button 
                            type="button" 
                            onclick="filterByGrade('10-11')" 
                            id="grade-tab-10-11"
                            class="grade-tab px-3.5 py-1.5 rounded-xl text-xs font-semibold bg-white/5 hover:bg-white/10 text-slate-300 hover:text-white border border-white/10 transition-all cursor-pointer whitespace-nowrap"
                        >
                            10–11 классы
                        </button>
                    </div>

                    {{-- Live Search Input --}}
                    <div class="relative w-full lg:w-72 shrink-0">
                        <input 
                            type="text" 
                            id="search-input" 
                            oninput="filterMaterials()" 
                            placeholder="Поиск по теме, классу..." 
                            class="w-full pl-10 pr-4 py-2.5 rounded-xl bg-black/60 border border-white/20 text-xs text-white placeholder-slate-400 focus:outline-none focus:border-[#C6FF33] transition-colors shadow-inner"
                        >
                        <svg class="w-4 h-4 text-slate-400 absolute left-3.5 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                </div>

                {{-- Subject Filters (Chips) --}}
                <div class="flex items-center gap-1.5 overflow-x-auto pb-1 scrollbar-none text-xs">
                    <span class="text-[11px] font-mono uppercase tracking-wider text-slate-400 pr-1 font-bold shrink-0">Предмет:</span>
                    <button 
                        type="button"
                        onclick="filterBySubject('all')" 
                        id="filter-tab-all"
                        class="subject-tab px-3 py-1.5 rounded-xl font-bold bg-[#7D39EB]/30 text-white border border-[#7D39EB]/50 cursor-pointer transition-all shadow-sm shrink-0"
                    >
                        Все предметы ({{ $orders->count() }})
                    </button>
                    @foreach($distinctSubjects as $subj)
                        <button 
                            type="button"
                            onclick="filterBySubject('{{ addslashes($subj) }}')" 
                            class="subject-tab px-3 py-1.5 rounded-xl font-medium bg-white/5 hover:bg-white/10 text-slate-300 hover:text-white border border-white/10 cursor-pointer transition-all whitespace-nowrap shrink-0"
                        >
                            {{ $subj }}
                        </button>
                    @endforeach
                </div>
            </div>

            {{-- Materials Grid --}}
            @if($orders->isEmpty())
                <div class="p-12 rounded-3xl cabinet-card text-center space-y-4 shadow-xl">
                    <div class="w-14 h-14 rounded-full bg-white/5 border border-white/15 text-slate-400 flex items-center justify-center mx-auto shadow-inner">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                    </div>
                    <div class="space-y-1">
                        <p class="text-base text-white font-bold">Для адреса {{ $email }} пока нет сохраненных работ.</p>
                        <p class="text-xs text-slate-400 max-w-md mx-auto">Сгенерируйте первую контрольную — она моментально появится здесь для повторной печати и копирования ключей.</p>
                    </div>
                    <a href="{{ route('generator') }}" class="inline-flex items-center gap-1.5 px-5 py-2.5 rounded-xl bg-[#C6FF33] hover:bg-[#d4ff59] text-black font-extrabold text-xs shadow-[0_0_20px_rgba(198,255,51,0.3)] transition-all">
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
                            $gradeGroup = match($grade) {
                                '5', '6' => '5-6',
                                '7', '8', '9' => '7-9',
                                '10', '11', 'abiturient' => '10-11',
                                default => 'other'
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
                            class="material-card p-6 rounded-2xl cabinet-card space-y-4 shadow-xl relative flex flex-col justify-between"
                            data-subject="{{ mb_strtolower($item->subject) }}"
                            data-grade-group="{{ $gradeGroup }}"
                            data-search="{{ mb_strtolower($item->subject . ' ' . $item->topic . ' ' . $gradeLabel . ' ' . $workTypeTitle . ' ' . $grade . ' класс') }}"
                        >
                            {{-- Card Header: Badges & Variants --}}
                            <div class="flex items-start justify-between gap-3">
                                <div class="space-y-2 flex-1 min-w-0">
                                    <div class="flex flex-wrap items-center gap-1.5">
                                        <span class="px-2.5 py-1 rounded-lg text-xs font-mono font-bold bg-[#7D39EB]/25 text-[#c084fc] border border-[#7D39EB]/40">
                                            {{ $item->subject }}
                                        </span>
                                        <span class="px-2.5 py-1 rounded-lg text-xs font-semibold bg-sky-500/20 text-sky-300 border border-sky-500/35">
                                            {{ $gradeLabel }}
                                        </span>
                                        <span class="px-2.5 py-1 rounded-lg text-xs font-medium bg-white/10 text-slate-200 border border-white/15">
                                            {{ $workTypeTitle }}
                                        </span>
                                    </div>
                                    <h3 class="text-lg font-extrabold text-white tracking-tight leading-snug line-clamp-2" title="{{ $item->topic }}">
                                        {{ $item->topic }}
                                    </h3>
                                </div>

                                {{-- Variant Count Badge --}}
                                <div class="text-right shrink-0">
                                    <span class="inline-flex items-center px-3 py-1 rounded-xl bg-[#C6FF33]/15 text-[#C6FF33] border border-[#C6FF33]/30 font-mono text-xs font-extrabold shadow-sm">
                                        {{ $item->variant_count }} вар.
                                    </span>
                                </div>
                            </div>

                            {{-- Task Details Info --}}
                            <div class="text-xs text-slate-300 flex items-center justify-between font-mono pt-2 border-t border-white/10">
                                <div class="flex items-center gap-3 text-xs">
                                    <span>Часть А: <b class="text-white">{{ $countA }}</b></span>
                                    <span>Часть Б: <b class="text-white">{{ $countB }}</b></span>
                                    <span>Всего: <b class="text-[#C6FF33]">{{ $countA + $countB }} зад.</b></span>
                                </div>
                                <span class="text-[11px] text-slate-400">
                                    {{ $item->created_at->translatedFormat('d M Y, H:i') }}
                                </span>
                            </div>

                            {{-- Teacher Dedicated Action Buttons (Teacher Studio) --}}
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 pt-3 border-t border-white/10">
                                {{-- 1. Projector / Interactive Whiteboard Matrix --}}
                                <a 
                                    href="{{ route('generator.board', $item->order_number) }}" 
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    title="Полноэкранная матрица вариантов для проектора и интерактивной доски"
                                    class="py-2.5 px-3 rounded-xl bg-sky-500/15 hover:bg-sky-500/25 text-sky-300 hover:text-white border border-sky-500/30 hover:border-sky-400/50 transition-all flex items-center justify-center gap-2 text-xs font-bold shadow-sm active:scale-[0.98]"
                                >
                                    <span class="text-sm">🖥️</span>
                                    <span>На доску / Проектор</span>
                                </a>

                                {{-- 2. Share with Teacher Colleague --}}
                                <button 
                                    type="button"
                                    onclick="copyShareLink('{{ route('generator.share', $item->order_number) }}')"
                                    title="Скопировать ссылку на карточку работы для коллег-учителей"
                                    class="py-2.5 px-3 rounded-xl bg-[#7D39EB]/20 hover:bg-[#7D39EB]/35 text-[#d8b4fe] hover:text-white border border-[#7D39EB]/35 hover:border-[#a855f7]/60 transition-all flex items-center justify-center gap-2 text-xs font-bold cursor-pointer shadow-sm active:scale-[0.98]"
                                >
                                    <span class="text-sm">🔗</span>
                                    <span>Поделиться с коллегой</span>
                                </button>
                            </div>

                            {{-- Actions Bar --}}
                            <div class="flex flex-wrap items-center gap-2 pt-1">
                                @if($item->isPaid() && $hasPdf)
                                    <a 
                                        href="{{ route('generator.download', ['order' => $item->order_number]) }}"
                                        class="flex-1 py-3 px-4 rounded-xl bg-[#C6FF33] hover:bg-[#d4ff59] text-black font-extrabold text-xs tracking-tight shadow-[0_0_20px_rgba(198,255,51,0.25)] flex items-center justify-center gap-2 transition-all cursor-pointer min-w-[130px]"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.4" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                        </svg>
                                        <span>Скачать PDF</span>
                                    </a>

                                    {{-- Interactive Board Mode --}}
                                    <a 
                                        href="{{ route('generator.board', ['order' => $item->order_number]) }}"
                                        title="Режим интерактивной доски / проектора"
                                        class="py-3 px-3 rounded-xl bg-white/10 hover:bg-white/15 text-slate-200 hover:text-white border border-white/15 hover:border-[#38BDF8]/60 transition-all flex items-center justify-center gap-1.5 text-xs font-bold"
                                    >
                                        <svg class="w-4 h-4 text-[#38BDF8]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                        </svg>
                                        <span class="hidden sm:inline">Доска</span>
                                    </a>

                                    {{-- Share Page --}}
                                    <a 
                                        href="{{ route('generator.share', ['order' => $item->order_number]) }}"
                                        title="Страница шеринга для коллег"
                                        class="py-3 px-3 rounded-xl bg-white/10 hover:bg-white/15 text-slate-200 hover:text-white border border-white/15 hover:border-emerald-400/60 transition-all flex items-center justify-center gap-1.5 text-xs font-bold"
                                    >
                                        <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z" />
                                        </svg>
                                        <span class="hidden sm:inline">Шеринг</span>
                                    </a>
                                @elseif($item->status === 'generating')
                                    <div class="flex-1 py-3 px-4 rounded-xl bg-amber-500/20 text-amber-300 font-bold text-xs flex items-center justify-center gap-2 border border-amber-500/35">
                                        <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        <span>Генерация вариантов...</span>
                                    </div>
                                @else
                                    <a 
                                        href="{{ route('generator.checkout.hosted', ['order' => $item->order_number]) }}"
                                        class="flex-1 py-3 px-4 rounded-xl bg-blue-600 hover:bg-blue-500 text-white font-extrabold text-xs flex items-center justify-center gap-1.5 shadow-md"
                                    >
                                        <span>Завершить заказ ({{ number_format($item->amountByn(), 2) }} BYN)</span>
                                    </a>
                                @endif

                                {{-- Quick repeat / new variants button --}}
                                <a 
                                    href="{{ route('generator') }}?subject={{ urlencode($item->subject) }}&topic={{ urlencode($item->topic) }}&grade={{ $grade }}"
                                    title="Составить еще варианты по этой теме"
                                    class="p-3 rounded-xl bg-white/10 hover:bg-white/15 text-slate-200 hover:text-white border border-white/15 transition-all flex items-center justify-center"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                    </svg>
                                </a>

                                {{-- Copy Direct PDF Download Link --}}
                                <button 
                                    type="button"
                                    onclick="copyDownloadLink('{{ route('generator.download', ['order' => $item->order_number]) }}')"
                                    title="Скопировать прямую ссылку на скачивание PDF"
                                    class="p-3 rounded-xl bg-white/10 hover:bg-white/15 text-slate-200 hover:text-white border border-white/15 transition-all flex items-center justify-center cursor-pointer"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"/>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    @endforeach

                    {{-- No Filter Results Fallback --}}
                    <div id="no-filter-results" class="hidden p-10 rounded-2xl cabinet-card text-center space-y-2 col-span-full">
                        <div class="w-12 h-12 rounded-full bg-white/5 border border-white/15 text-slate-400 flex items-center justify-center mx-auto text-xl">
                            🔍
                        </div>
                        <p class="text-sm font-bold text-white">В выбранной категории материалов не найдено</p>
                        <p class="text-xs text-slate-400">Попробуйте переключить фильтр классов или очистить строку поиска.</p>
                    </div>
                </div>
            @endif

        @endif

    </div>

    {{-- Switch Email Modal --}}
    <div id="switch-email-modal" class="hidden fixed inset-0 z-50 bg-black/85 backdrop-blur-md flex items-center justify-center p-4">
        <div class="w-full max-w-md p-7 rounded-3xl bg-[#0c101c] border border-white/25 shadow-2xl space-y-4">
            <div class="flex items-center justify-between">
                <h3 class="text-base font-bold text-white">Переключить архив на другой email</h3>
                <button type="button" onclick="document.getElementById('switch-email-modal').classList.add('hidden')" class="text-slate-400 hover:text-white text-xl leading-none cursor-pointer">&times;</button>
            </div>
            <p class="text-xs text-slate-300 leading-relaxed">
                Введите другой адрес электронной почты, чтобы посмотреть привязанные к нему проверочные работы.
            </p>
            <form action="{{ route('generator.cabinet.access') }}" method="POST" class="space-y-3 pt-1">
                @csrf
                <input 
                    type="email" 
                    name="email" 
                    required 
                    placeholder="teacher@school.by"
                    class="w-full px-4 py-3 rounded-xl bg-black/80 border border-white/25 text-white placeholder-slate-500 text-sm focus:outline-none focus:border-[#C6FF33] shadow-inner"
                >
                <div class="flex justify-end gap-2 pt-2">
                    <button 
                        type="button" 
                        onclick="document.getElementById('switch-email-modal').classList.add('hidden')"
                        class="px-4 py-2.5 rounded-xl text-xs font-medium text-slate-300 hover:text-white"
                    >
                        Отмена
                    </button>
                    <button 
                        type="submit" 
                        class="px-5 py-2.5 rounded-xl bg-[#C6FF33] text-black font-extrabold text-xs shadow-md cursor-pointer"
                    >
                        Загрузить материалы →
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Quick Toast Feedback --}}
    <div id="toast" class="fixed bottom-6 right-6 z-50 transform translate-y-20 opacity-0 transition-all duration-300 px-5 py-3.5 rounded-2xl bg-black/95 border border-[#C6FF33]/50 text-[#C6FF33] text-xs font-mono shadow-[0_0_30px_rgba(0,0,0,0.8)] flex items-center gap-2.5">
        <svg class="w-4 h-4 text-[#C6FF33]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
        </svg>
        <span id="toast-message" class="font-bold">Ссылка скопирована</span>
    </div>

    <script>
        let currentSubject = 'all';
        let currentGradeGroup = 'all';

        function filterBySubject(subj) {
            currentSubject = subj.toLowerCase();
            document.querySelectorAll('.subject-tab').forEach(el => {
                el.classList.remove('bg-[#7D39EB]/30', 'text-white', 'border-[#7D39EB]/50', 'font-bold');
                el.classList.add('bg-white/5', 'text-slate-300', 'border-white/10', 'font-medium');
            });
            if (event && event.currentTarget) {
                event.currentTarget.classList.remove('bg-white/5', 'text-slate-300', 'border-white/10', 'font-medium');
                event.currentTarget.classList.add('bg-[#7D39EB]/30', 'text-white', 'border-[#7D39EB]/50', 'font-bold');
            }
            applyFilters();
        }

        function filterByGrade(gradeGroup) {
            currentGradeGroup = gradeGroup;
            document.querySelectorAll('.grade-tab').forEach(el => {
                el.classList.remove('bg-[#C6FF33]', 'text-black', 'font-bold', 'shadow-sm');
                el.classList.add('bg-white/5', 'text-slate-300', 'border-white/10', 'font-semibold');
            });
            if (event && event.currentTarget) {
                event.currentTarget.classList.remove('bg-white/5', 'text-slate-300', 'border-white/10', 'font-semibold');
                event.currentTarget.classList.add('bg-[#C6FF33]', 'text-black', 'font-bold', 'shadow-sm');
            }
            applyFilters();
        }

        function filterMaterials() {
            applyFilters();
        }

        function applyFilters() {
            const query = (document.getElementById('search-input')?.value || '').toLowerCase().trim();
            let visibleCount = 0;

            document.querySelectorAll('.material-card').forEach(card => {
                const cardSubj = (card.getAttribute('data-subject') || '').toLowerCase();
                const cardGradeGroup = card.getAttribute('data-grade-group') || '';
                const cardSearch = (card.getAttribute('data-search') || '').toLowerCase();

                const matchSubj = (currentSubject === 'all' || cardSubj === currentSubject);
                const matchGrade = (currentGradeGroup === 'all' || cardGradeGroup === currentGradeGroup);
                const matchSearch = (!query || cardSearch.includes(query));

                if (matchSubj && matchGrade && matchSearch) {
                    card.style.display = '';
                    visibleCount++;
                } else {
                    card.style.display = 'none';
                }
            });

            const noResults = document.getElementById('no-filter-results');
            if (noResults) {
                noResults.style.display = visibleCount === 0 ? 'block' : 'none';
            }
        }

        function copyShareLink(url) {
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(url).then(() => {
                    showToast('Ссылка для коллег скопирована');
                }).catch(() => {
                    promptFallback(url, 'Ссылка для коллег скопирована');
                });
            } else {
                promptFallback(url, 'Ссылка для коллег скопирована');
            }
        }

        function copyDownloadLink(url) {
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(url).then(() => {
                    showToast('Прямая ссылка на PDF скопирована в буфер');
                }).catch(() => {
                    promptFallback(url, 'Ссылка на PDF скопирована');
                });
            } else {
                promptFallback(url, 'Ссылка на PDF скопирована');
            }
        }

        function promptFallback(url, msg) {
            window.prompt('Скопируйте ссылку вручную:', url);
            showToast(msg);
        }

        function showToast(msg) {
            const t = document.getElementById('toast');
            if (!t) return;
            document.getElementById('toast-message').textContent = msg;
            t.classList.remove('translate-y-20', 'opacity-0');
            setTimeout(() => {
                t.classList.add('translate-y-20', 'opacity-0');
            }, 3000);
        }
    </script>
</body>
</html>
