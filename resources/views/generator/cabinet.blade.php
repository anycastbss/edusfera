<!DOCTYPE html>
<html lang="ru" class="dark" style="background-color: #000000 !important; color-scheme: dark;">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>Мои материалы • Личный кабинет генератора тестов | Edusfera</title>
    <meta name="description" content="История сгенерированных контрольных и проверочных работ, скачивание вариантов и ключей ответов.">
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
                    <div class="w-11 h-11 rounded-xl bg-black border border-white/20 flex items-center justify-center shadow-[0_0_15px_rgba(198,255,51,0.2)] group-hover:border-[#C6FF33] transition-colors">
                        <svg class="w-6 h-6 text-[#C6FF33]" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M12 2L2 12l10 10 10-10L12 2zm0 4.5l5.5 5.5-5.5 5.5-5.5-5.5L12 6.5z"/>
                        </svg>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="font-extrabold text-base tracking-tight text-white font-mono">EDUSFERA</span>
                            <span class="px-2 py-0.5 rounded-md text-[10px] font-bold uppercase tracking-wider bg-[#C6FF33]/15 text-[#C6FF33] border border-[#C6FF33]/30">Кабинет</span>
                        </div>
                        <p class="text-xs text-slate-400 font-medium">Архив проверочных работ и ключей ответов</p>
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
                    <button 
                        type="button"
                        onclick="document.getElementById('switch-email-modal').classList.remove('hidden')"
                        class="text-[11px] font-medium text-[#C6FF33] hover:underline cursor-pointer pt-0.5 block"
                    >
                        Сменить email ↺
                    </button>
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
            <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3 pt-2">
                {{-- Subject Filters --}}
                <div class="flex items-center gap-2 overflow-x-auto pb-1 sm:pb-0 scrollbar-none text-xs">
                    <button 
                        type="button"
                        onclick="filterBySubject('all')" 
                        id="filter-tab-all"
                        class="subject-tab px-4 py-2 rounded-xl font-bold bg-[#C6FF33] text-black cursor-pointer transition-all shadow-md"
                    >
                        Все предметы ({{ $orders->count() }})
                    </button>
                    @foreach($distinctSubjects as $subj)
                        <button 
                            type="button"
                            onclick="filterBySubject('{{ addslashes($subj) }}')" 
                            class="subject-tab px-3.5 py-2 rounded-xl font-semibold bg-white/5 hover:bg-white/10 text-slate-300 hover:text-white border border-white/15 cursor-pointer transition-all whitespace-nowrap"
                        >
                            {{ $subj }}
                        </button>
                    @endforeach
                </div>

                {{-- Live Search Input --}}
                <div class="relative w-full sm:w-72">
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
                            class="material-card p-6 rounded-2xl cabinet-card space-y-4 shadow-xl relative"
                            data-subject="{{ mb_strtolower($item->subject) }}"
                            data-search="{{ mb_strtolower($item->subject . ' ' . $item->topic . ' ' . $gradeLabel . ' ' . $workTypeTitle) }}"
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

                            {{-- Actions Bar --}}
                            <div class="flex items-center gap-2 pt-1">
                                @if($item->isPaid() && $hasPdf)
                                    <a 
                                        href="{{ route('generator.download', ['order' => $item->order_number]) }}"
                                        class="flex-1 py-3 px-4 rounded-xl bg-[#C6FF33] hover:bg-[#d4ff59] text-black font-extrabold text-xs tracking-tight shadow-[0_0_20px_rgba(198,255,51,0.25)] flex items-center justify-center gap-2 transition-all cursor-pointer"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.4" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                        </svg>
                                        <span>Скачать PDF файл</span>
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

                                {{-- Copy Download Link --}}
                                <button 
                                    type="button"
                                    onclick="copyDownloadLink('{{ route('generator.download', ['order' => $item->order_number]) }}')"
                                    title="Скопировать прямую ссылку на скачивание"
                                    class="p-3 rounded-xl bg-white/10 hover:bg-white/15 text-slate-200 hover:text-white border border-white/15 transition-all flex items-center justify-center cursor-pointer"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"/>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    @endforeach
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
        function filterBySubject(subj) {
            document.querySelectorAll('.subject-tab').forEach(el => {
                el.classList.remove('bg-[#C6FF33]', 'text-black', 'shadow-md');
                el.classList.add('bg-white/5', 'text-slate-300');
            });
            event.target.classList.remove('bg-white/5', 'text-slate-300');
            event.target.classList.add('bg-[#C6FF33]', 'text-black', 'shadow-md');

            const lower = subj.toLowerCase();
            document.querySelectorAll('.material-card').forEach(card => {
                if (subj === 'all' || card.getAttribute('data-subject') === lower) {
                    card.style.display = '';
                } else {
                    card.style.display = 'none';
                }
            });
        }

        function filterMaterials() {
            const query = document.getElementById('search-input').value.toLowerCase().trim();
            document.querySelectorAll('.material-card').forEach(card => {
                const text = card.getAttribute('data-search') || '';
                if (!query || text.includes(query)) {
                    card.style.display = '';
                } else {
                    card.style.display = 'none';
                }
            });
        }

        function copyDownloadLink(url) {
            if (navigator.clipboard) {
                navigator.clipboard.writeText(url).then(() => {
                    showToast('Прямая ссылка на PDF скопирована в буфер');
                });
            } else {
                showToast('Ссылка: ' + url);
            }
        }

        function showToast(msg) {
            const t = document.getElementById('toast');
            document.getElementById('toast-message').textContent = msg;
            t.classList.remove('translate-y-20', 'opacity-0');
            setTimeout(() => {
                t.classList.add('translate-y-20', 'opacity-0');
            }, 3000);
        }
    </script>
</body>
</html>
