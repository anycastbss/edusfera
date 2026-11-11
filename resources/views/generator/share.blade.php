<!DOCTYPE html>
<html lang="ru" class="dark" style="background-color: #040508 !important; color-scheme: dark;">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    
    {{-- Dynamic OpenGraph / Social Metadata for Telegram & Viber --}}
    <title>{{ $order->subject }} ({{ $gradeLabel }}) • {{ $order->topic }} • Комплект проверочной работы | Edusfera</title>
    <meta name="description" content="Готовый комплект проверочной работы по теме «{{ $order->topic }}» ({{ $order->subject }}, {{ $gradeLabel }}). {{ $order->variant_count }} вариантов с бланками и мастер-таблицей ключей ответов для учителя.">
    
    <meta property="og:type" content="article">
    <meta property="og:title" content="{{ $order->subject }} ({{ $gradeLabel }}): {{ $order->topic }}">
    <meta property="og:description" content="Пакет проверочных материалов: {{ $order->variant_count }} вариантов по стандартам 2026 года с бланками и готовыми ключами ответов. Скачайте в PDF или откройте на интерактивной доске.">
    <meta property="og:site_name" content="Edusfera Учительская">
    <meta property="og:url" content="{{ $shareUrl }}">

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $order->subject }} • {{ $order->topic }}">
    <meta name="twitter:description" content="{{ $order->variant_count }} параллельных вариантов с ключами для школьной проверки.">

    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    @vite(['resources/css/app.css'])

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com">
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
        html:not(.dark), html:not(.dark) body {
            background-color: #F8FAFC !important;
            background: #F8FAFC !important;
            color: #0F172A !important;
        }
        html.dark, html.dark body {
            background-color: #0B0B0E !important;
            background: #0B0B0E !important;
            color: #ffffff !important;
        }
    </style>

    <script>
        (function() {
            const savedTheme = localStorage.getItem('edusfera_theme') || 'light';
            if (savedTheme === 'dark') {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
        })();
    </script>
</head>
<body class="min-h-screen font-sans selection:bg-[#C6FF33] selection:text-black antialiased relative overflow-x-hidden">

    {{-- Ambient Aura Glow --}}
    <div class="fixed inset-0 overflow-hidden pointer-events-none z-0">
        <div class="absolute -top-32 left-1/2 -translate-x-1/2 w-[700px] h-[400px] bg-gradient-to-b from-[#7D39EB]/25 via-[#38BDF8]/10 to-transparent blur-[120px] opacity-70"></div>
        <div class="absolute bottom-10 right-10 w-[500px] h-[300px] bg-gradient-to-t from-[#C6FF33]/15 to-transparent blur-[130px] opacity-40"></div>
    </div>

    {{-- Main Container --}}
    <div class="relative z-10 max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-10 space-y-8">

        {{-- Top Brand Navigation --}}
        <header class="flex items-center justify-between gap-4 p-4 sm:p-5 rounded-2xl bg-white dark:bg-[#121214] border border-slate-200/90 dark:border-white/10 shadow-sm transition-colors">
            <div class="flex items-center gap-3">
                <a href="{{ route('home') }}" class="flex items-center gap-3 group">
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
                        <p class="text-xs text-slate-500 dark:text-slate-400">Материал методического объединения</p>
                    </div>
                </a>
            </div>

            <div class="flex items-center gap-2 sm:gap-3">
                <a 
                    href="{{ route('generator') }}"
                    class="px-4 py-2 sm:py-2.5 rounded-xl text-xs sm:text-sm font-bold bg-[#7D39EB] hover:bg-[#6827d6] text-white shadow-sm transition-all flex items-center gap-1.5 cursor-pointer whitespace-nowrap"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    <span>+ Создать свой тест</span>
                </a>
            </div>
        </header>

        {{-- Hero Card: Subject, Topic & Quick Stats --}}
        <section class="share-card rounded-3xl p-6 sm:p-10 space-y-6 shadow-2xl relative border border-white/15 overflow-hidden">
            <div class="space-y-3">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="px-3 py-1 rounded-xl text-xs font-mono font-extrabold uppercase bg-[#7D39EB]/25 text-[#c084fc] border border-[#7D39EB]/40">
                        {{ $order->subject }}
                    </span>
                    <span class="px-3 py-1 rounded-xl text-xs font-bold bg-sky-500/20 text-sky-300 border border-sky-500/35">
                        {{ $gradeLabel }}
                    </span>
                    <span class="px-3 py-1 rounded-xl text-xs font-medium bg-white/10 text-slate-200 border border-white/15">
                        {{ $workTypeTitle }}
                    </span>
                    <span class="px-3 py-1 rounded-xl text-xs font-mono font-semibold bg-emerald-500/15 text-emerald-300 border border-emerald-500/35">
                        Стандарты 2026
                    </span>
                </div>

                <h1 class="text-2xl sm:text-4xl font-extrabold text-white tracking-tight leading-tight">
                    {{ $order->topic }}
                </h1>
                <p class="text-xs sm:text-sm text-slate-300 max-w-2xl leading-relaxed">
                    Коллега поделился с вами полным комплектом проверочной работы. Пакет включает параллельные варианты для учеников с бланками ответов и сводную мастер-таблицу ключей для мгновенной проверки.
                </p>
            </div>

            {{-- Spec Grid (4 columns) --}}
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 sm:gap-4 pt-2 border-t border-white/10">
                <div class="p-4 rounded-2xl bg-white/[0.03] border border-white/10 space-y-1">
                    <span class="text-[11px] font-mono uppercase tracking-wider text-slate-400 font-semibold block">Вариантов</span>
                    <div class="text-2xl sm:text-3xl font-extrabold font-mono text-[#C6FF33]">{{ $order->variant_count }} шт.</div>
                    <span class="text-[10px] text-slate-500 block">параллельные</span>
                </div>

                <div class="p-4 rounded-2xl bg-white/[0.03] border border-white/10 space-y-1">
                    <span class="text-[11px] font-mono uppercase tracking-wider text-slate-400 font-semibold block">Всего заданий</span>
                    <div class="text-2xl sm:text-3xl font-extrabold font-mono text-white">{{ $totalTasksCount }}</div>
                    <span class="text-[10px] text-slate-500 block">в каждом варианте</span>
                </div>

                <div class="p-4 rounded-2xl bg-white/[0.03] border border-white/10 space-y-1">
                    <span class="text-[11px] font-mono uppercase tracking-wider text-slate-400 font-semibold block">Время работы</span>
                    <div class="text-2xl sm:text-3xl font-extrabold font-mono text-sky-400">{{ $timeMinutes }} мин</div>
                    <span class="text-[10px] text-slate-500 block">расчетное время</span>
                </div>

                <div class="p-4 rounded-2xl bg-white/[0.03] border border-white/10 space-y-1">
                    <span class="text-[11px] font-mono uppercase tracking-wider text-slate-400 font-semibold block">Макс. балл</span>
                    <div class="text-2xl sm:text-3xl font-extrabold font-mono text-white">{{ $maxScore }}</div>
                    <span class="text-[10px] text-slate-500 block">шкала оценки</span>
                </div>
            </div>

            {{-- Primary Action CTAs --}}
            <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 pt-2">
                {{-- Download Full PDF --}}
                <a 
                    href="{{ $downloadUrl }}" 
                    class="flex-1 py-4 px-6 rounded-2xl bg-[#C6FF33] hover:bg-[#d4ff59] text-black font-extrabold text-sm sm:text-base tracking-tight shadow-[0_0_25px_rgba(198,255,51,0.35)] flex items-center justify-center gap-2.5 transition-all cursor-pointer active:scale-[0.98]"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.4" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                    </svg>
                    <span>Скачать полный PDF (Варианты + Ключи)</span>
                </a>

                {{-- Open Interactive Board --}}
                <a 
                    href="{{ $boardUrl }}" 
                    class="py-4 px-6 rounded-2xl bg-white/10 hover:bg-white/15 text-white font-extrabold text-sm border border-white/20 hover:border-[#38BDF8]/60 transition-all flex items-center justify-center gap-2 cursor-pointer shadow-md"
                >
                    <svg class="w-5 h-5 text-[#38BDF8]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                    </svg>
                    <span>Интерактивная доска</span>
                </a>
            </div>

            {{-- Messenger Quick Share Bar (Viber, Telegram, Copy) --}}
            <div class="flex flex-wrap items-center justify-between gap-3 pt-4 border-t border-white/10 text-xs">
                <span class="text-slate-400 font-medium">Отправить коллегам по кафедре / в чат школы:</span>
                
                <div class="flex items-center gap-2 flex-wrap">
                    {{-- Telegram --}}
                    @php
                        $tgText = urlencode("Комплект проверочной работы ({$order->subject}, {$gradeLabel}): «{$order->topic}». {$order->variant_count} вариантов с ключами ответов: {$shareUrl}");
                        $viberText = urlencode("Проверочная работа ({$order->subject}): {$order->topic}. Скачать варианты и ключи: {$shareUrl}");
                    @endphp
                    <a 
                        href="https://t.me/share/url?url={{ urlencode($shareUrl) }}&text={{ $tgText }}" 
                        target="_blank"
                        class="px-3.5 py-2 rounded-xl bg-[#229ED9]/20 hover:bg-[#229ED9]/30 text-[#229ED9] border border-[#229ED9]/40 font-bold transition-all flex items-center gap-1.5"
                    >
                        <span>Telegram</span>
                    </a>

                    {{-- Viber --}}
                    <a 
                        href="viber://forward?text={{ $viberText }}" 
                        class="px-3.5 py-2 rounded-xl bg-[#7360F2]/20 hover:bg-[#7360F2]/30 text-[#9B8BF4] border border-[#7360F2]/40 font-bold transition-all flex items-center gap-1.5"
                    >
                        <span>Viber</span>
                    </a>

                    {{-- Copy Link --}}
                    <button 
                        type="button" 
                        onclick="copyShareLink('{{ $shareUrl }}')" 
                        class="px-3.5 py-2 rounded-xl bg-white/10 hover:bg-white/15 text-white border border-white/20 font-bold transition-all flex items-center gap-1.5 cursor-pointer"
                    >
                        <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"/>
                        </svg>
                        <span>Скопировать ссылку</span>
                    </button>
                </div>
            </div>
        </section>

        {{-- Material Preview Section --}}
        <section class="space-y-4">
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                <div class="space-y-1">
                    <h2 class="text-xl sm:text-2xl font-extrabold text-white tracking-tight">
                        Предпросмотр заданий
                    </h2>
                    <p class="text-xs text-slate-400">
                        Ознакомьтесь с формулировками заданий и сложностью перед скачиванием
                    </p>
                </div>

                {{-- Teacher Answer Key Toggle --}}
                <div class="flex items-center gap-2">
                    <button 
                        type="button" 
                        onclick="toggleAnswersVisibility()" 
                        id="toggle-answers-btn"
                        class="px-4 py-2 rounded-xl bg-white/10 hover:bg-white/15 text-xs font-mono font-bold text-slate-200 border border-white/20 transition-all flex items-center gap-1.5 cursor-pointer"
                    >
                        <span id="answers-eye-icon">👁️</span>
                        <span id="answers-toggle-text">Показать ключи ответов</span>
                    </button>
                </div>
            </div>

            {{-- Variant Tabs (Variant 1, Variant 2, Master Table) --}}
            <div class="flex items-center gap-2 overflow-x-auto pb-1 scrollbar-none text-xs">
                @foreach(array_slice($variants, 0, 3) as $v)
                    <button 
                        type="button" 
                        onclick="showPreviewVariant({{ $v['number'] }})" 
                        id="preview-tab-{{ $v['number'] }}"
                        class="preview-tab-btn px-4 py-2 rounded-xl font-bold {{ $loop->first ? 'bg-[#C6FF33] text-black shadow-md' : 'bg-white/5 text-slate-300 border border-white/15' }} cursor-pointer transition-all whitespace-nowrap"
                    >
                        Вариант {{ $v['number'] }}
                    </button>
                @endforeach
                <button 
                    type="button" 
                    onclick="showPreviewVariant('master')" 
                    id="preview-tab-master"
                    class="preview-tab-btn px-4 py-2 rounded-xl font-bold bg-white/5 text-slate-300 border border-white/15 cursor-pointer transition-all whitespace-nowrap"
                >
                    Сводная таблица ответов
                </button>
            </div>

            {{-- Variant Cards Container --}}
            @foreach(array_slice($variants, 0, 3) as $v)
                <div id="preview-panel-{{ $v['number'] }}" class="preview-panel {{ $loop->first ? '' : 'hidden' }} space-y-4">
                    
                    {{-- Part A --}}
                    @if(! empty($v['part_a']))
                        <div class="space-y-3">
                            <span class="text-xs font-mono uppercase tracking-wider text-slate-400 font-bold block">Часть А (Задания с выбором ответа):</span>
                            @foreach($v['part_a'] as $task)
                                <div class="share-card rounded-2xl p-5 space-y-3 border border-white/10 shadow-lg">
                                    <div class="flex items-center justify-between">
                                        <span class="px-2.5 py-1 rounded-lg bg-[#7D39EB]/20 text-[#c084fc] font-mono font-bold text-xs border border-[#7D39EB]/40">
                                            Задание А{{ $task['num'] }}
                                        </span>
                                        <div class="task-answer-badge hidden px-3 py-1 rounded-lg bg-[#C6FF33]/15 text-[#C6FF33] border border-[#C6FF33]/30 font-mono text-xs font-black">
                                            Ответ: {{ $task['correct'] ?? '—' }}
                                        </div>
                                    </div>
                                    <div class="text-sm sm:text-base text-slate-100 font-medium leading-relaxed">
                                        {!! $task['condition_html'] !!}
                                    </div>
                                    @if(! empty($task['options']))
                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 pt-1 text-xs">
                                            @foreach($task['options'] as $opt)
                                                <div class="p-2.5 rounded-xl bg-white/[0.03] border border-white/10 flex items-center gap-2.5">
                                                    <span class="w-6 h-6 rounded-md bg-white/10 font-mono font-bold flex items-center justify-center shrink-0 text-slate-300">
                                                        {{ $opt['letter'] }}
                                                    </span>
                                                    <span class="text-slate-300 font-medium">{{ $opt['text'] }}</span>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endif

                    {{-- Part B --}}
                    @if(! empty($v['part_b']))
                        <div class="space-y-3 pt-2">
                            <span class="text-xs font-mono uppercase tracking-wider text-slate-400 font-bold block">Часть Б (Краткий ответ):</span>
                            @foreach($v['part_b'] as $task)
                                <div class="share-card rounded-2xl p-5 space-y-3 border border-white/10 shadow-lg">
                                    <div class="flex items-center justify-between">
                                        <span class="px-2.5 py-1 rounded-lg bg-sky-500/20 text-sky-300 font-mono font-bold text-xs border border-sky-500/40">
                                            Задание Б{{ $task['num'] }}
                                        </span>
                                        <div class="task-answer-badge hidden px-3 py-1 rounded-lg bg-[#C6FF33]/15 text-[#C6FF33] border border-[#C6FF33]/30 font-mono text-xs font-black">
                                            Ответ: {{ $task['correct'] ?? '—' }}
                                        </div>
                                    </div>
                                    <div class="text-sm sm:text-base text-slate-100 font-medium leading-relaxed">
                                        {!! $task['condition_html'] !!}
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif

                </div>
            @endforeach

            {{-- Master Table Preview Panel --}}
            <div id="preview-panel-master" class="preview-panel hidden share-card rounded-2xl overflow-hidden shadow-xl border border-white/10">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead>
                            <tr class="bg-white/5 border-b border-white/10 text-slate-400 font-mono uppercase">
                                <th class="py-3 px-4 text-center font-bold">Вар.</th>
                                <th class="py-3 px-4 font-bold">Часть А</th>
                                <th class="py-3 px-4 font-bold">Часть Б</th>
                                <th class="py-3 px-4 font-bold">Методическая ловушка</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/10 font-mono">
                            @foreach($masterTable as $row)
                                <tr class="hover:bg-white/[0.02]">
                                    <td class="py-3 px-4 text-center font-bold text-[#C6FF33]">{{ $row['variant'] }}</td>
                                    <td class="py-3 px-4 text-slate-200">{{ $row['part_a'] }}</td>
                                    <td class="py-3 px-4 text-slate-200">{{ $row['part_b'] }}</td>
                                    <td class="py-3 px-4 font-sans text-slate-400">{{ $row['trap'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- More Variants Notice --}}
            @if(count($variants) > 3)
                <div class="p-4 rounded-2xl bg-white/[0.02] border border-white/10 text-center text-xs text-slate-400">
                    В полном PDF файле доступны ещё <b class="text-white">{{ count($variants) - 3 }}</b> параллельных вариантов с бланками ответов.
                </div>
            @endif
        </section>

        {{-- Viral CTA: Create Your Own Tests --}}
        <section class="share-card rounded-3xl p-6 sm:p-8 border border-white/15 bg-gradient-to-br from-[#7D39EB]/15 via-black to-[#C6FF33]/10 space-y-4 shadow-2xl">
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                <div class="space-y-1 max-w-xl">
                    <div class="inline-flex items-center gap-2 px-2.5 py-0.5 rounded-lg bg-[#C6FF33]/15 text-[#C6FF33] text-[10px] font-mono font-bold uppercase tracking-wider border border-[#C6FF33]/30">
                        Учительская студия Edusfera
                    </div>
                    <h3 class="text-lg sm:text-xl font-bold text-white tracking-tight">
                        Составляйте контрольные и тесты по своему предмету за 1 минуту
                    </h3>
                    <p class="text-xs text-slate-300 leading-relaxed">
                        Генератор автоматически формирует до 30 параллельных вариантов по спецификациям 2026 года с чистой математической версткой, клеточными бланками и ключами для быстрой проверки.
                    </p>
                </div>

                <a 
                    href="{{ route('generator') }}" 
                    class="px-6 py-3.5 rounded-2xl bg-[#C6FF33] hover:bg-[#d4ff59] text-black font-extrabold text-xs sm:text-sm tracking-tight shadow-[0_0_20px_rgba(198,255,51,0.3)] transition-all cursor-pointer whitespace-nowrap shrink-0"
                >
                    Составить свой тест →
                </a>
            </div>
        </section>

    </div>

    {{-- Toast Notification --}}
    <div id="toast" class="fixed bottom-6 right-6 z-50 transform translate-y-20 opacity-0 transition-all duration-300 px-5 py-3.5 rounded-2xl bg-black/95 border border-[#C6FF33]/50 text-[#C6FF33] text-xs font-mono shadow-[0_0_30px_rgba(0,0,0,0.8)] flex items-center gap-2.5">
        <svg class="w-4 h-4 text-[#C6FF33]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
        </svg>
        <span id="toast-message" class="font-bold">Ссылка скопирована</span>
    </div>

    <script>
        function showPreviewVariant(varId) {
            document.querySelectorAll('.preview-panel').forEach(p => p.classList.add('hidden'));
            const target = document.getElementById('preview-panel-' + varId);
            if (target) target.classList.remove('hidden');

            document.querySelectorAll('.preview-tab-btn').forEach(btn => {
                btn.classList.remove('bg-[#C6FF33]', 'text-black', 'shadow-md');
                btn.classList.add('bg-white/5', 'text-slate-300', 'border', 'border-white/15');
            });
            const active = document.getElementById('preview-tab-' + varId);
            if (active) {
                active.classList.remove('bg-white/5', 'text-slate-300', 'border', 'border-white/15');
                active.classList.add('bg-[#C6FF33]', 'text-black', 'shadow-md');
            }
        }

        let answersVisible = false;
        function toggleAnswersVisibility() {
            answersVisible = !answersVisible;
            const badges = document.querySelectorAll('.task-answer-badge');
            const btnText = document.getElementById('answers-toggle-text');
            const eyeIcon = document.getElementById('answers-eye-icon');

            if (answersVisible) {
                badges.forEach(b => b.classList.remove('hidden'));
                btnText.textContent = 'Скрыть ключи';
                eyeIcon.textContent = '🔒';
            } else {
                badges.forEach(b => b.classList.add('hidden'));
                btnText.textContent = 'Показать ключи ответов';
                eyeIcon.textContent = '👁️';
            }
        }

        function copyShareLink(url) {
            if (navigator.clipboard) {
                navigator.clipboard.writeText(url).then(() => {
                    showToast('Ссылка на материалы скопирована для отправки');
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