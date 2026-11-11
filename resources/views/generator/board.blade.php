<!DOCTYPE html>
<html lang="ru" id="html-root" class="dark" style="color-scheme: dark;">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>Интерактивная доска • {{ $order->subject }} • Edusfera Учительская</title>
    <meta name="description" content="Режим отображения матрицы ключей ответов и таймер урока для интерактивной доски и проектора.">
    <meta name="robots" content="noindex, nofollow">
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    @vite(['resources/css/app.css'])

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Geist:wght@300;400;500;600;700;800;900&family=JetBrains+Mono:wght@400;600;700;800&family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

    <style>
        :root {
            --bg-canvas: #07090E;
            --bg-card: rgba(15, 20, 32, 0.95);
            --bg-card-hover: rgba(22, 30, 48, 0.98);
            --border-card: rgba(255, 255, 255, 0.12);
            --text-primary: #FFFFFF;
            --text-secondary: #94A3B8;
            --text-accent: #C6FF33;
            --badge-bg: rgba(198, 255, 51, 0.12);
            --badge-border: rgba(198, 255, 51, 0.35);
            --key-revealed-bg: #C6FF33;
            --key-revealed-text: #000000;
            --font-scale: 1;
        }

        /* Classroom White Projector Mode */
        html.theme-projector {
            --bg-canvas: #F8FAFC !important;
            --bg-card: #FFFFFF !important;
            --bg-card-hover: #F1F5F9 !important;
            --border-card: #CBD5E1 !important;
            --text-primary: #0F172A !important;
            --text-secondary: #475569 !important;
            --text-accent: #16A34A !important;
            --badge-bg: #DCFCE7 !important;
            --badge-border: #86EFAC !important;
            --key-revealed-bg: #0F172A !important;
            --key-revealed-text: #FFFFFF !important;
            color-scheme: light !important;
        }

        body {
            background-color: var(--bg-canvas) !important;
            color: var(--text-primary) !important;
            transition: background-color 0.2s ease, color 0.2s ease;
        }

        .board-card {
            background: var(--bg-card);
            border: 1px solid var(--border-card);
            transition: all 0.2s ease;
        }

        .key-badge {
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            user-select: none;
        }

        .key-badge.is-hidden-key {
            filter: blur(7px);
            opacity: 0.25;
            cursor: pointer;
        }

        .key-badge.is-hidden-key:hover {
            filter: blur(3px);
            opacity: 0.6;
        }

        .key-badge.is-revealed-key {
            filter: none !important;
            opacity: 1 !important;
        }

        /* Pulse animation when timer finishes */
        @keyframes timerAlarmPulse {
            0%, 100% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.7); }
            50% { box-shadow: 0 0 0 15px rgba(239, 68, 68, 0); }
        }

        .timer-alarm {
            animation: timerAlarmPulse 1.2s infinite;
            border-color: #EF4444 !important;
        }
    </style>
</head>
<body class="min-h-screen font-sans antialiased selection:bg-[#C6FF33] selection:text-black">

    {{-- Top App Bar --}}
    <header class="sticky top-0 z-40 border-b border-white/10 backdrop-blur-xl px-4 sm:px-6 py-3.5 flex flex-wrap items-center justify-between gap-3 board-card">
        {{-- Left: Back and Title --}}
        <div class="flex items-center gap-3 sm:gap-4 min-w-0">
            <a 
                href="{{ $cabinetUrl }}" 
                class="p-2 sm:px-3 sm:py-2 rounded-xl bg-white/5 hover:bg-white/10 border border-white/15 text-xs font-bold transition-all flex items-center gap-1.5 shrink-0"
                title="Вернуться в Учительскую"
            >
                <svg class="w-4 h-4 text-[#C6FF33]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                <span class="hidden sm:inline">Учительская</span>
            </a>

            <div class="min-w-0">
                <div class="flex items-center gap-2 flex-wrap">
                    <span class="px-2.5 py-0.5 rounded-md text-[11px] font-mono font-extrabold uppercase tracking-wider bg-[#7D39EB]/20 text-[#c084fc] border border-[#7D39EB]/35">
                        {{ $order->subject }}
                    </span>
                    <span class="px-2.5 py-0.5 rounded-md text-[11px] font-semibold bg-sky-500/15 text-sky-400 border border-sky-500/30">
                        {{ $gradeLabel }}
                    </span>
                    <span class="px-2.5 py-0.5 rounded-md text-[11px] font-medium bg-white/10 text-slate-300 border border-white/15">
                        {{ $workTypeTitle }}
                    </span>
                </div>
                <h1 class="text-sm sm:text-base font-extrabold truncate text-white tracking-tight mt-0.5" title="{{ $order->topic }}">
                    {{ $order->topic }}
                </h1>
            </div>
        </div>

        {{-- Right Controls: Theme, Font Scale, Fullscreen, Actions --}}
        <div class="flex items-center gap-2 sm:gap-3 flex-wrap justify-end">
            {{-- Font Scaler --}}
            <div class="hidden sm:flex items-center rounded-xl bg-white/5 border border-white/15 p-1 gap-1">
                <button 
                    type="button" 
                    onclick="adjustFont(-1)" 
                    class="px-2 py-1 rounded-lg text-xs font-mono font-bold hover:bg-white/10 transition-colors"
                    title="Уменьшить шрифт"
                >
                    A-
                </button>
                <button 
                    type="button" 
                    onclick="adjustFont(1)" 
                    class="px-2 py-1 rounded-lg text-xs font-mono font-bold hover:bg-white/10 transition-colors text-[#C6FF33]"
                    title="Увеличить шрифт для проектора"
                >
                    A+
                </button>
            </div>

            {{-- Theme Toggle: Dark Obsidian vs White Projector --}}
            <button 
                type="button" 
                onclick="toggleTheme()" 
                id="theme-btn"
                class="px-3 py-2 rounded-xl bg-white/5 hover:bg-white/10 border border-white/15 text-xs font-semibold flex items-center gap-1.5 transition-all cursor-pointer"
                title="Переключить контраст фона для проектора"
            >
                <span id="theme-icon">☀️</span>
                <span id="theme-label" class="hidden md:inline">Проектор</span>
            </button>

            {{-- Fullscreen Toggle --}}
            <button 
                type="button" 
                onclick="toggleFullScreen()" 
                class="px-3 py-2 rounded-xl bg-white/5 hover:bg-white/10 border border-white/15 text-xs font-semibold flex items-center gap-1.5 transition-all cursor-pointer"
                title="Во весь экран (F11)"
            >
                <svg class="w-4 h-4 text-[#38BDF8]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4" />
                </svg>
                <span class="hidden md:inline">Экран</span>
            </button>

            {{-- Share Link --}}
            <a 
                href="{{ $shareUrl }}" 
                target="_blank"
                class="px-3 py-2 rounded-xl bg-white/5 hover:bg-white/10 border border-white/15 text-xs font-semibold flex items-center gap-1.5 transition-all text-slate-200"
                title="Страница шеринга для коллег"
            >
                <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z" />
                </svg>
                <span class="hidden lg:inline">Коллегам</span>
            </a>

            {{-- Download PDF --}}
            <a 
                href="{{ $downloadUrl }}" 
                class="px-3.5 py-2 rounded-xl bg-[#C6FF33] hover:bg-[#d4ff59] text-black font-extrabold text-xs shadow-[0_0_15px_rgba(198,255,51,0.25)] flex items-center gap-1.5 transition-all shrink-0 cursor-pointer"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.4" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                </svg>
                <span>Печать PDF</span>
            </a>
        </div>
    </header>

    {{-- Main Board Workspace --}}
    <main class="max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8 py-5 sm:py-7 space-y-6">

        {{-- Classroom Interactive Dock: Timer + Global Key Controls --}}
        <section class="grid grid-cols-1 lg:grid-cols-12 gap-4 items-stretch">
            
            {{-- Big Lesson Countdown Timer (7 cols on lg) --}}
            <div id="timer-box" class="lg:col-span-7 board-card rounded-2xl p-4 sm:p-5 flex flex-col sm:flex-row items-center justify-between gap-4 shadow-xl border border-white/15 relative overflow-hidden">
                {{-- Progress Bar line on top of timer --}}
                <div class="absolute top-0 left-0 right-0 h-1 bg-white/10">
                    <div id="timer-progress" class="h-full bg-[#C6FF33] transition-all duration-300" style="width: 100%;"></div>
                </div>

                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-white/5 border border-white/15 flex items-center justify-center text-[#C6FF33] shrink-0">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <div class="text-[11px] font-mono uppercase tracking-wider text-slate-400 font-semibold flex items-center gap-2">
                            <span>Таймер урока</span>
                            <span id="timer-status-badge" class="px-2 py-0.5 rounded text-[10px] bg-white/10 text-slate-300">Готов</span>
                        </div>
                        <div id="timer-display" class="text-4xl sm:text-5xl font-mono font-black tracking-tight text-white mt-0.5 leading-none">
                            {{ sprintf('%02d:00', $timeMinutes) }}
                        </div>
                    </div>
                </div>

                {{-- Timer Presets & Controls --}}
                <div class="flex flex-wrap items-center gap-2 justify-center sm:justify-end">
                    {{-- Quick Presets --}}
                    <div class="flex items-center gap-1 bg-white/5 p-1 rounded-xl border border-white/10 text-xs font-mono">
                        <button type="button" onclick="setTimerDuration(15)" class="px-2.5 py-1 rounded-lg hover:bg-white/10 text-slate-300 hover:text-white transition-colors">15м</button>
                        <button type="button" onclick="setTimerDuration(20)" class="px-2.5 py-1 rounded-lg hover:bg-white/10 text-slate-300 hover:text-white transition-colors">20м</button>
                        <button type="button" onclick="setTimerDuration(45)" class="px-2.5 py-1 rounded-lg hover:bg-white/10 text-slate-300 hover:text-white transition-colors font-bold text-[#C6FF33]">45м</button>
                    </div>

                    {{-- Play / Pause / Reset --}}
                    <button 
                        type="button" 
                        id="timer-start-btn" 
                        onclick="toggleTimer()" 
                        class="px-5 py-2.5 rounded-xl bg-[#C6FF33] hover:bg-[#d4ff59] text-black font-mono font-extrabold text-xs shadow-md transition-all flex items-center gap-1.5 cursor-pointer"
                    >
                        <svg id="timer-play-icon" class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M8 5v14l11-7z" />
                        </svg>
                        <span id="timer-btn-text">Старт</span>
                    </button>
                    <button 
                        type="button" 
                        onclick="resetTimer()" 
                        class="p-2.5 rounded-xl bg-white/5 hover:bg-white/10 border border-white/15 text-slate-300 hover:text-white transition-colors cursor-pointer"
                        title="Сбросить таймер"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                    </button>
                </div>
            </div>

            {{-- Privacy / Answer Visibility Controls (5 cols on lg) --}}
            <div class="lg:col-span-5 board-card rounded-2xl p-4 sm:p-5 flex flex-col justify-between gap-3 shadow-xl border border-white/15">
                <div class="flex items-center justify-between">
                    <div>
                        <span class="text-[11px] font-mono uppercase tracking-wider text-slate-400 font-semibold block">Видимость ответов</span>
                        <p class="text-xs text-slate-300 mt-0.5">Клик по ячейке открывает отдельный ответ</p>
                    </div>
                    <span id="visibility-indicator" class="px-2.5 py-1 rounded-lg text-xs font-mono font-bold bg-amber-500/15 text-amber-400 border border-amber-500/30">
                        🔒 Скрыты
                    </span>
                </div>

                <div class="grid grid-cols-2 gap-2 pt-1">
                    <button 
                        type="button" 
                        onclick="revealAllAnswers()" 
                        class="py-2.5 px-3 rounded-xl bg-white/10 hover:bg-white/15 border border-white/20 text-white font-bold text-xs flex items-center justify-center gap-1.5 transition-all cursor-pointer"
                    >
                        <svg class="w-4 h-4 text-[#C6FF33]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                        </svg>
                        <span>Показать все</span>
                    </button>
                    <button 
                        type="button" 
                        onclick="hideAllAnswers()" 
                        class="py-2.5 px-3 rounded-xl bg-white/5 hover:bg-white/10 border border-white/15 text-slate-300 hover:text-white font-semibold text-xs flex items-center justify-center gap-1.5 transition-all cursor-pointer"
                    >
                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" />
                        </svg>
                        <span>Скрыть ответы</span>
                    </button>
                </div>
            </div>

        </section>

        {{-- Variant Navigation Tabs --}}
        <section class="flex items-center gap-2 overflow-x-auto pb-2 scrollbar-none">
            <button 
                type="button" 
                onclick="switchView('matrix')" 
                id="tab-btn-matrix"
                class="variant-tab-btn px-4 sm:px-5 py-2.5 rounded-xl font-extrabold text-xs sm:text-sm tracking-tight bg-[#C6FF33] text-black shadow-md flex items-center gap-2 cursor-pointer transition-all whitespace-nowrap"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                </svg>
                <span>Сводная матрица (Все варианты)</span>
            </button>

            @foreach($variants as $v)
                <button 
                    type="button" 
                    onclick="switchView('variant-{{ $v['number'] }}')" 
                    id="tab-btn-variant-{{ $v['number'] }}"
                    class="variant-tab-btn px-4 py-2.5 rounded-xl font-bold text-xs sm:text-sm bg-white/5 hover:bg-white/10 text-slate-300 hover:text-white border border-white/15 flex items-center gap-1.5 cursor-pointer transition-all whitespace-nowrap"
                >
                    <span>Вариант {{ $v['number'] }}</span>
                </button>
            @endforeach
        </section>

        {{-- VIEW 1: MASTER MATRIX TABLE (Default) --}}
        <section id="view-matrix" class="view-panel space-y-4">
            <div class="board-card rounded-2xl overflow-hidden shadow-2xl border border-white/15">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-white/10 bg-white/[0.03] text-slate-400 text-xs font-mono uppercase tracking-wider">
                                <th class="py-4 px-4 sm:px-6 w-28 text-center font-bold">Вариант</th>
                                <th class="py-4 px-4 sm:px-6 font-bold">Часть А (Выбор ответа)</th>
                                <th class="py-4 px-4 sm:px-6 font-bold">Часть Б (Краткий ответ)</th>
                                <th class="py-4 px-4 sm:px-6 font-bold">Методическая ловушка</th>
                                <th class="py-4 px-4 sm:px-6 w-32 text-center font-bold">Разбор</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/10">
                            @foreach($variants as $v)
                                @php
                                    $vNum = $v['number'];
                                    $trapText = $masterTable[$loop->index]['trap'] ?? 'Внимание к вычислениям и ОДЗ';
                                @endphp
                                <tr class="hover:bg-white/[0.02] transition-colors">
                                    {{-- Variant Number Badge --}}
                                    <td class="py-5 px-4 sm:px-6 text-center align-top">
                                        <div class="inline-flex flex-col items-center justify-center w-14 h-14 rounded-2xl bg-white/5 border border-white/15">
                                            <span class="text-[10px] uppercase font-mono text-slate-400 font-bold">ВАР</span>
                                            <span class="text-xl font-mono font-black text-white leading-none mt-0.5">{{ $vNum }}</span>
                                        </div>
                                    </td>

                                    {{-- Part A Keys --}}
                                    <td class="py-5 px-4 sm:px-6 align-top">
                                        @if(empty($v['part_a']))
                                            <span class="text-slate-500 text-xs font-mono">—</span>
                                        @else
                                            <div class="flex flex-wrap gap-2">
                                                @foreach($v['part_a'] as $task)
                                                    <div 
                                                        onclick="toggleSingleKey(this)" 
                                                        class="key-cell key-badge is-hidden-key flex items-center rounded-xl bg-white/5 hover:bg-white/10 border border-white/15 px-3 py-2 cursor-pointer shadow-sm"
                                                        data-answer="{{ $task['correct'] ?? '—' }}"
                                                        title="Кликните, чтобы показать/скрыть ответ"
                                                    >
                                                        <span class="text-xs font-mono font-semibold text-slate-400 mr-2">А{{ $task['num'] }}:</span>
                                                        <span class="key-value text-base sm:text-lg font-mono font-black text-[#C6FF33]">
                                                            {{ $task['correct'] ?? '—' }}
                                                        </span>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endif
                                    </td>

                                    {{-- Part B Keys --}}
                                    <td class="py-5 px-4 sm:px-6 align-top">
                                        @if(empty($v['part_b']))
                                            <span class="text-slate-500 text-xs font-mono">—</span>
                                        @else
                                            <div class="flex flex-wrap gap-2">
                                                @foreach($v['part_b'] as $task)
                                                    <div 
                                                        onclick="toggleSingleKey(this)" 
                                                        class="key-cell key-badge is-hidden-key flex items-center rounded-xl bg-white/5 hover:bg-white/10 border border-white/15 px-3 py-2 cursor-pointer shadow-sm"
                                                        data-answer="{{ $task['correct'] ?? '—' }}"
                                                        title="Кликните, чтобы показать/скрыть ответ"
                                                    >
                                                        <span class="text-xs font-mono font-semibold text-sky-400 mr-2">Б{{ $task['num'] }}:</span>
                                                        <span class="key-value text-base sm:text-lg font-mono font-black text-white">
                                                            {{ $task['correct'] ?? '—' }}
                                                        </span>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endif
                                    </td>

                                    {{-- Trap / Pedagogical Note --}}
                                    <td class="py-5 px-4 sm:px-6 align-top max-w-xs">
                                        <p class="text-xs sm:text-sm text-slate-300 leading-relaxed font-medium">
                                            {{ $trapText }}
                                        </p>
                                    </td>

                                    {{-- Action: Open full variant --}}
                                    <td class="py-5 px-4 sm:px-6 text-center align-top">
                                        <button 
                                            type="button" 
                                            onclick="switchView('variant-{{ $vNum }}')"
                                            class="px-3.5 py-2 rounded-xl bg-white/5 hover:bg-white/15 border border-white/15 text-xs font-bold text-slate-200 hover:text-white transition-all cursor-pointer whitespace-nowrap"
                                        >
                                            Задания →
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        {{-- VIEW 2+: SINGLE VARIANT DRILLDOWNS --}}
        @foreach($variants as $v)
            @php $vNum = $v['number']; @endphp
            <section id="view-variant-{{ $vNum }}" class="view-panel hidden space-y-6">
                
                {{-- Variant Header Banner --}}
                <div class="board-card rounded-2xl p-5 sm:p-6 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 border border-white/15 shadow-xl">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="px-3 py-1 rounded-lg text-xs font-mono font-extrabold uppercase bg-[#C6FF33] text-black">
                                Вариант {{ $vNum }}
                            </span>
                            <span class="text-xs text-slate-400 font-mono">
                                Заданий: {{ count($v['part_a']) + count($v['part_b']) }}
                            </span>
                        </div>
                        <h2 class="text-xl sm:text-2xl font-black text-white tracking-tight mt-1.5">
                            Разбор заданий Варианта {{ $vNum }}
                        </h2>
                    </div>

                    <div class="flex items-center gap-2">
                        <button 
                            type="button" 
                            onclick="switchView('matrix')" 
                            class="px-4 py-2.5 rounded-xl bg-white/10 hover:bg-white/15 text-xs font-bold text-white border border-white/20 transition-all flex items-center gap-1.5 cursor-pointer"
                        >
                            <span>← Сводная матрица</span>
                        </button>
                    </div>
                </div>

                {{-- Part A Section --}}
                @if(! empty($v['part_a']))
                    <div class="space-y-4">
                        <div class="flex items-center gap-2 border-b border-white/10 pb-2">
                            <span class="text-xs font-mono uppercase tracking-wider text-slate-400 font-extrabold">
                                Часть А • Задания с выбором ответа
                            </span>
                        </div>

                        <div class="grid grid-cols-1 gap-4">
                            @foreach($v['part_a'] as $task)
                                <div class="board-card rounded-2xl p-5 sm:p-6 space-y-4 border border-white/15 shadow-lg relative">
                                    <div class="flex items-start justify-between gap-4">
                                        <div class="flex items-center gap-2.5">
                                            <span class="w-10 h-10 rounded-xl bg-[#7D39EB]/20 border border-[#7D39EB]/40 text-[#c084fc] font-mono font-extrabold text-sm flex items-center justify-center shrink-0">
                                                А{{ $task['num'] }}
                                            </span>
                                            <span class="text-xs font-mono uppercase text-slate-400 font-semibold">1 балл</span>
                                        </div>

                                        {{-- Answer Badge --}}
                                        <div 
                                            onclick="toggleSingleKey(this)" 
                                            class="key-cell key-badge is-hidden-key px-4 py-2 rounded-xl bg-white/10 border border-white/20 text-center cursor-pointer shadow-sm"
                                            title="Нажмите, чтобы открыть правильный ответ"
                                        >
                                            <span class="text-[10px] font-mono uppercase text-slate-400 block">Ответ</span>
                                            <span class="text-lg font-mono font-black text-[#C6FF33]">{{ $task['correct'] ?? '—' }}</span>
                                        </div>
                                    </div>

                                    {{-- Task Condition --}}
                                    <div class="text-base sm:text-lg text-white font-medium leading-relaxed pl-1">
                                        {!! $task['condition_html'] !!}
                                    </div>

                                    {{-- Options Grid --}}
                                    @if(! empty($task['options']))
                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 pt-2">
                                            @foreach($task['options'] as $opt)
                                                @php $isCorrect = ($task['correct'] === $opt['letter']); @endphp
                                                <div 
                                                    class="option-card p-3.5 rounded-xl bg-white/[0.03] border border-white/10 flex items-start gap-3 text-sm {{ $isCorrect ? 'correct-option' : '' }}"
                                                    data-correct="{{ $isCorrect ? 'true' : 'false' }}"
                                                >
                                                    <span class="w-7 h-7 rounded-lg bg-white/10 font-mono font-bold text-xs flex items-center justify-center shrink-0 text-slate-300">
                                                        {{ $opt['letter'] }}
                                                    </span>
                                                    <span class="text-slate-200 font-medium leading-snug pt-0.5">
                                                        {{ $opt['text'] }}
                                                    </span>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif

                                    {{-- Explanation Box --}}
                                    @if(! empty($task['explanation']))
                                        <div class="p-4 rounded-xl bg-[#7D39EB]/10 border border-[#7D39EB]/25 text-xs sm:text-sm text-slate-200 space-y-1">
                                            <span class="font-mono text-[11px] font-bold uppercase text-[#c084fc] block">Пояснение и разбор решения:</span>
                                            <p class="leading-relaxed">{{ $task['explanation'] }}</p>
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Part B Section --}}
                @if(! empty($v['part_b']))
                    <div class="space-y-4 pt-4">
                        <div class="flex items-center gap-2 border-b border-white/10 pb-2">
                            <span class="text-xs font-mono uppercase tracking-wider text-slate-400 font-extrabold">
                                Часть Б • Задания с кратким ответом
                            </span>
                        </div>

                        <div class="grid grid-cols-1 gap-4">
                            @foreach($v['part_b'] as $task)
                                <div class="board-card rounded-2xl p-5 sm:p-6 space-y-4 border border-white/15 shadow-lg relative">
                                    <div class="flex items-start justify-between gap-4">
                                        <div class="flex items-center gap-2.5">
                                            <span class="w-10 h-10 rounded-xl bg-sky-500/20 border border-sky-500/40 text-sky-300 font-mono font-extrabold text-sm flex items-center justify-center shrink-0">
                                                Б{{ $task['num'] }}
                                            </span>
                                            <span class="text-xs font-mono uppercase text-slate-400 font-semibold">2 балла</span>
                                        </div>

                                        {{-- Answer Badge --}}
                                        <div 
                                            onclick="toggleSingleKey(this)" 
                                            class="key-cell key-badge is-hidden-key px-5 py-2 rounded-xl bg-white/10 border border-white/20 text-center cursor-pointer shadow-sm"
                                            title="Нажмите, чтобы открыть правильный ответ"
                                        >
                                            <span class="text-[10px] font-mono uppercase text-slate-400 block">Ответ</span>
                                            <span class="text-lg font-mono font-black text-[#C6FF33]">{{ $task['correct'] ?? '—' }}</span>
                                        </div>
                                    </div>

                                    {{-- Task Condition --}}
                                    <div class="text-base sm:text-lg text-white font-medium leading-relaxed pl-1">
                                        {!! $task['condition_html'] !!}
                                    </div>

                                    {{-- Explanation Box --}}
                                    @if(! empty($task['explanation']))
                                        <div class="p-4 rounded-xl bg-sky-500/10 border border-sky-500/25 text-xs sm:text-sm text-slate-200 space-y-1">
                                            <span class="font-mono text-[11px] font-bold uppercase text-sky-400 block">Методический разбор:</span>
                                            <p class="leading-relaxed">{{ $task['explanation'] }}</p>
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

            </section>
        @endforeach

    </main>

    {{-- Interactive Audio Alarm (Web Audio API - Zero External Assets) --}}
    <script>
        function playChime() {
            try {
                const ctx = new (window.AudioContext || window.webkitAudioContext)();
                const now = ctx.currentTime;
                
                // Pleasant school bell chime chords (E5, G#5, B5)
                [659.25, 830.61, 987.77].forEach((freq, i) => {
                    const osc = ctx.createOscillator();
                    const gain = ctx.createGain();
                    osc.type = 'sine';
                    osc.frequency.setValueAtTime(freq, now + i * 0.12);
                    gain.gain.setValueAtTime(0.2, now + i * 0.12);
                    gain.gain.exponentialRampToValueAtTime(0.001, now + i * 0.12 + 1.2);
                    osc.connect(gain);
                    gain.connect(ctx.destination);
                    osc.start(now + i * 0.12);
                    osc.stop(now + i * 0.12 + 1.3);
                });
            } catch(e) {
                console.log('Audio chime not allowed yet');
            }
        }

        // ================= TIMER LOGIC =================
        let totalSeconds = {{ $timeMinutes * 60 }};
        let remainingSeconds = totalSeconds;
        let timerInterval = null;
        let isRunning = false;

        function updateTimerDisplay() {
            const m = Math.floor(remainingSeconds / 60);
            const s = remainingSeconds % 60;
            const str = `${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`;
            document.getElementById('timer-display').textContent = str;

            const progress = (remainingSeconds / totalSeconds) * 100;
            document.getElementById('timer-progress').style.width = `${progress}%`;
        }

        function toggleTimer() {
            if (isRunning) {
                pauseTimer();
            } else {
                startTimer();
            }
        }

        function startTimer() {
            if (remainingSeconds <= 0) return;
            isRunning = true;
            document.getElementById('timer-btn-text').textContent = 'Пауза';
            document.getElementById('timer-status-badge').textContent = 'Идёт урок';
            document.getElementById('timer-status-badge').className = 'px-2 py-0.5 rounded text-[10px] bg-emerald-500/20 text-emerald-300 border border-emerald-500/40';
            document.getElementById('timer-play-icon').innerHTML = '<path d="M6 19h4V5H6v14zm8-14v14h4V5h-4z"/>';

            timerInterval = setInterval(() => {
                remainingSeconds--;
                updateTimerDisplay();

                if (remainingSeconds <= 0) {
                    clearInterval(timerInterval);
                    isRunning = false;
                    document.getElementById('timer-box').classList.add('timer-alarm');
                    document.getElementById('timer-status-badge').textContent = 'Время вышло!';
                    document.getElementById('timer-status-badge').className = 'px-2 py-0.5 rounded text-[10px] bg-red-500 text-white font-bold animate-pulse';
                    document.getElementById('timer-btn-text').textContent = 'Старт';
                    playChime();
                }
            }, 1000);
        }

        function pauseTimer() {
            isRunning = false;
            clearInterval(timerInterval);
            document.getElementById('timer-btn-text').textContent = 'Продолжить';
            document.getElementById('timer-status-badge').textContent = 'Пауза';
            document.getElementById('timer-status-badge').className = 'px-2 py-0.5 rounded text-[10px] bg-amber-500/20 text-amber-300 border border-amber-500/40';
            document.getElementById('timer-play-icon').innerHTML = '<path d="M8 5v14l11-7z"/>';
        }

        function resetTimer() {
            pauseTimer();
            remainingSeconds = totalSeconds;
            document.getElementById('timer-box').classList.remove('timer-alarm');
            document.getElementById('timer-btn-text').textContent = 'Старт';
            document.getElementById('timer-status-badge').textContent = 'Готов';
            document.getElementById('timer-status-badge').className = 'px-2 py-0.5 rounded text-[10px] bg-white/10 text-slate-300';
            updateTimerDisplay();
        }

        function setTimerDuration(minutes) {
            pauseTimer();
            totalSeconds = minutes * 60;
            remainingSeconds = totalSeconds;
            document.getElementById('timer-box').classList.remove('timer-alarm');
            updateTimerDisplay();
        }

        // ================= ANSWER VISIBILITY TOGGLE =================
        function toggleSingleKey(element) {
            if (element.classList.contains('is-hidden-key')) {
                element.classList.remove('is-hidden-key');
                element.classList.add('is-revealed-key');
            } else {
                element.classList.remove('is-revealed-key');
                element.classList.add('is-hidden-key');
            }
        }

        function revealAllAnswers() {
            document.querySelectorAll('.key-cell').forEach(el => {
                el.classList.remove('is-hidden-key');
                el.classList.add('is-revealed-key');
            });
            const ind = document.getElementById('visibility-indicator');
            ind.textContent = '👁️ Все открыты';
            ind.className = 'px-2.5 py-1 rounded-lg text-xs font-mono font-bold bg-emerald-500/20 text-emerald-300 border border-emerald-500/40';
        }

        function hideAllAnswers() {
            document.querySelectorAll('.key-cell').forEach(el => {
                el.classList.remove('is-revealed-key');
                el.classList.add('is-hidden-key');
            });
            const ind = document.getElementById('visibility-indicator');
            ind.textContent = '🔒 Скрыты';
            ind.className = 'px-2.5 py-1 rounded-lg text-xs font-mono font-bold bg-amber-500/15 text-amber-400 border border-amber-500/30';
        }

        // ================= VIEW SWITCHER =================
        function switchView(viewId) {
            document.querySelectorAll('.view-panel').forEach(panel => {
                panel.classList.add('hidden');
            });
            const activePanel = document.getElementById('view-' + viewId);
            if (activePanel) {
                activePanel.classList.remove('hidden');
            }

            document.querySelectorAll('.variant-tab-btn').forEach(btn => {
                btn.classList.remove('bg-[#C6FF33]', 'text-black', 'shadow-md');
                btn.classList.add('bg-white/5', 'text-slate-300');
            });
            const activeBtn = document.getElementById('tab-btn-' + viewId);
            if (activeBtn) {
                activeBtn.classList.remove('bg-white/5', 'text-slate-300');
                activeBtn.classList.add('bg-[#C6FF33]', 'text-black', 'shadow-md');
            }
        }

        // ================= FULLSCREEN & THEME TOGGLE =================
        function toggleFullScreen() {
            if (!document.fullscreenElement) {
                document.documentElement.requestFullscreen().catch(err => {
                    console.log('Fullscreen error:', err);
                });
            } else {
                if (document.exitFullscreen) {
                    document.exitFullscreen();
                }
            }
        }

        let isProjectorMode = false;
        function toggleTheme() {
            isProjectorMode = !isProjectorMode;
            const root = document.getElementById('html-root');
            const icon = document.getElementById('theme-icon');
            const label = document.getElementById('theme-label');

            if (isProjectorMode) {
                root.classList.add('theme-projector');
                root.classList.remove('dark');
                icon.textContent = '🌙';
                label.textContent = 'Тёмный зал';
            } else {
                root.classList.remove('theme-projector');
                root.classList.add('dark');
                icon.textContent = '☀️';
                label.textContent = 'Проектор';
            }
        }

        // Font scaling for giant projector readability
        let currentScale = 1;
        function adjustFont(delta) {
            currentScale += delta * 0.1;
            if (currentScale < 0.8) currentScale = 0.8;
            if (currentScale > 1.6) currentScale = 1.6;
            document.documentElement.style.fontSize = `${currentScale * 16}px`;
        }
    </script>
</body>
</html>