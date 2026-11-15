<!DOCTYPE html>
<html lang="ru" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="referrer" content="no-referrer">
    <title>Заказ № {{ $order->order_number }} | Edusfera</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @vite(['resources/css/app.css'])
    <style>
        .glass-panel {
            background: rgba(14, 14, 18, 0.75);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.12);
            box-shadow: 0 16px 40px rgba(0, 0, 0, 0.4);
        }
    </style>
</head>
<body class="bg-[#000000] text-white min-h-screen py-8 px-4 sm:px-6 font-sans selection:bg-[#7D39EB] selection:text-white">
    @php
        $isReady = ! empty($order->generated_content);
        $isPaid = $order->isPaid();
        $variants = $parsedData['variants'] ?? [];
        $var1 = $variants[0] ?? null;
    @endphp

    <div class="max-w-4xl mx-auto space-y-6 relative">
        {{-- Ambient backlight --}}
        <div class="absolute -top-20 left-1/2 -translate-x-1/2 w-96 h-96 rounded-full bg-[#7D39EB]/15 blur-3xl pointer-events-none"></div>

        {{-- Top Navigation Bar --}}
        <div class="flex items-center justify-between pb-2 border-b border-white/10">
            <a href="{{ route('generator') }}" class="flex items-center gap-2 text-slate-300 hover:text-white transition-colors">
                <span class="w-7 h-7 rounded-lg bg-white/5 border border-white/10 flex items-center justify-center text-xs">←</span>
                <span class="text-xs sm:text-sm font-medium">Конструктор проверочных</span>
            </a>
            <div class="flex items-center gap-2 text-xs font-mono text-slate-400">
                <span>Заказ:</span>
                <span class="text-white font-bold">{{ $order->order_number }}</span>
            </div>
        </div>

        {{-- Flash alerts --}}
        @if (session('status'))
            <div class="p-4 rounded-2xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 text-sm flex items-center gap-3">
                <span class="text-lg">✓</span>
                <span>{{ session('status') }}</span>
            </div>
        @endif
        @if (session('error'))
            <div class="p-4 rounded-2xl bg-rose-500/10 border border-rose-500/30 text-rose-400 text-sm flex items-center gap-3">
                <span class="text-lg">⚠️</span>
                <span>{{ session('error') }}</span>
            </div>
        @endif

        {{-- STATE 1: In-Progress Generator Loader --}}
        <div id="loading-card" class="{{ $isReady ? 'hidden' : 'block' }} glass-panel rounded-3xl p-8 sm:p-12 text-center space-y-6 max-w-xl mx-auto">
            <div class="relative w-20 h-20 mx-auto flex items-center justify-center">
                <div class="absolute inset-0 rounded-full border-4 border-[#7D39EB]/20"></div>
                <div class="absolute inset-0 rounded-full border-4 border-transparent border-t-[#C6FF33] border-r-[#7D39EB] animate-spin"></div>
                <div class="w-10 h-10 rounded-full bg-[#7D39EB]/20 flex items-center justify-center text-[#C6FF33]">
                    <svg class="w-5 h-5 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" />
                    </svg>
                </div>
            </div>

            <div class="space-y-2">
                <div class="text-xs font-mono uppercase tracking-widest text-[#C6FF33] flex items-center justify-center gap-1.5">
                    <span class="w-2 h-2 rounded-full bg-[#C6FF33] animate-ping"></span>
                    <span>ИИ-генерация по стандартам Беларуси 2026</span>
                </div>
                <h1 class="text-2xl sm:text-3xl font-bold tracking-tight text-white font-rimma uppercase">
                    Формируем {{ $order->variant_count }} {{ $order->variant_count > 4 ? 'вариантов' : 'варианта' }}
                </h1>
                <p id="loading-stage-text" class="text-sm text-slate-300">
                    Педагогический анализ спецификаций программы и составление заданий...
                </p>
            </div>

            <div class="w-full bg-white/10 rounded-full h-2 overflow-hidden">
                <div id="progress-bar" class="h-full bg-gradient-to-r from-[#7D39EB] to-[#C6FF33] transition-all duration-700 ease-out" style="width: 25%"></div>
            </div>

            <div class="p-4 rounded-2xl bg-white/[0.03] border border-white/10 text-left text-xs space-y-2.5 font-mono">
                <div class="flex items-center gap-2 text-slate-300">
                    <span class="text-[#C6FF33] font-bold">✓</span>
                    <span>Проверка темы: {{ $order->topic }}</span>
                </div>
                <div id="step-variants" class="flex items-center gap-2 text-slate-400">
                    <span class="text-[#7D39EB] animate-spin font-bold">◐</span>
                    <span>Генерация параллельных вариантов без списывания</span>
                </div>
                <div id="step-pdf" class="flex items-center gap-2 text-slate-500">
                    <span class="text-slate-600">○</span>
                    <span>Подготовка академических бланков A4 и Word (.docx)</span>
                </div>
            </div>

            <div class="text-[11px] text-slate-500 font-mono">
                Обычно занимает от 5 до 15 секунд. Пожалуйста, не закрывайте страницу.
            </div>
        </div>

        {{-- STATE 2: Ready & Paid (Download PDF + Word) --}}
        @if ($isPaid)
        <div id="ready-card" class="{{ $isReady ? 'block' : 'hidden' }} space-y-6">
            <div class="glass-panel rounded-3xl p-6 sm:p-10 text-center space-y-6">
                <div class="w-16 h-16 rounded-full bg-[#C6FF33]/20 border border-[#C6FF33] text-[#C6FF33] flex items-center justify-center mx-auto shadow-[0_0_30px_rgba(198,255,51,0.25)]">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                    </svg>
                </div>

                <div class="space-y-2">
                    <div class="text-xs font-mono uppercase tracking-widest text-[#C6FF33]">
                        ✓ Комплект проверен и готов к печати
                    </div>
                    <h1 class="text-2xl sm:text-3xl font-bold tracking-tight text-white font-rimma uppercase">
                        Материалы успешно сформированы
                    </h1>
                    <p class="text-sm text-slate-400 max-w-lg mx-auto">
                        Пакет из {{ $order->variant_count }} вариантов для школьных парт, лист ответов и ключи для быстрой проверки за 2 минуты.
                    </p>
                </div>

                {{-- Order Passport --}}
                <div class="p-4 rounded-2xl bg-white/[0.03] border border-white/10 text-left text-xs space-y-2 font-mono max-w-md mx-auto">
                    <div class="flex justify-between text-slate-400">
                        <span>Предмет:</span>
                        <span class="text-white font-semibold">{{ $order->subject }}</span>
                    </div>
                    <div class="flex justify-between text-slate-400">
                        <span>Тема:</span>
                        <span class="text-white truncate max-w-[200px]" title="{{ $order->topic }}">{{ $order->topic }}</span>
                    </div>
                    <div class="flex justify-between text-slate-400">
                        <span>Количество:</span>
                        <span class="text-[#C6FF33] font-bold">{{ $order->variant_count }} вариантов</span>
                    </div>
                    <div class="flex justify-between text-slate-400">
                        <span>Статус доступа:</span>
                        <span class="text-[#C6FF33] font-bold">Полный доступ (PDF + Word)</span>
                    </div>
                </div>

                {{-- PRIMARY DOWNLOAD BUTTONS --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 max-w-lg mx-auto pt-2">
                    {{-- Download PDF Button --}}
                    <a 
                        href="{{ route('generator.download', ['order' => $order->order_number, 'token' => $order->access_token]) }}"
                        class="py-4 px-6 rounded-2xl bg-[#7D39EB] hover:bg-[#6D28D9] text-white font-bold text-sm tracking-tight shadow-[0_0_30px_rgba(125,57,235,0.4)] active:scale-[0.98] transition-all flex items-center justify-center gap-2 cursor-pointer border border-[#9d63ff]/30"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                        </svg>
                        <span>Скачать PDF (A4 для печати)</span>
                    </a>

                    {{-- Download Word DOCX Button --}}
                    <a 
                        href="{{ route('generator.download_docx', ['order' => $order->order_number, 'token' => $order->access_token]) }}"
                        class="py-4 px-6 rounded-2xl bg-white/[0.08] hover:bg-white/[0.14] text-white font-bold text-sm tracking-tight border border-white/20 active:scale-[0.98] transition-all flex items-center justify-center gap-2 cursor-pointer hover:border-[#38bdf8]/50"
                    >
                        <svg class="w-5 h-5 text-[#38bdf8]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                        <span>Скачать Word (.docx)</span>
                    </a>
                </div>

                <div class="pt-2">
                    <a 
                        href="{{ route('generator') }}" 
                        class="inline-block py-2.5 px-6 rounded-xl bg-white/[0.04] hover:bg-white/[0.08] text-slate-400 hover:text-white font-medium text-xs transition-colors border border-white/10"
                    >
                        ← Создать ещё одну работу
                    </a>
                </div>
            </div>
        </div>
        @endif

        {{-- STATE 3: Ready but Unpaid -> FREE PREVIEW OF VARIANT 1 + PAYWALL --}}
        @if ($isReady && ! $isPaid)
        <div id="preview-container" class="space-y-6">
            {{-- Free Preview Banner --}}
            <div class="glass-panel rounded-3xl p-6 sm:p-8 border-[#7D39EB]/40 relative overflow-hidden">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-white/10">
                    <div>
                        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-[#7D39EB]/20 border border-[#7D39EB]/40 text-[#C6FF33] text-xs font-mono mb-2">
                            <span>👁️ БЕСПЛАТНЫЙ ПРЕДПРОСМОТР</span>
                            <span>•</span>
                            <span>ВАРИАНТ 1</span>
                        </div>
                        <h2 class="text-xl sm:text-2xl font-bold font-rimma uppercase text-white">
                            {{ $order->subject }} • {{ $order->topic }}
                        </h2>
                        <p class="text-xs text-slate-400 mt-1">
                            Оцените качество и формулировки заданий Варианта 1. Ответы, остальные варианты и Word-файл доступны в полном комплекте.
                        </p>
                    </div>
                    <div class="shrink-0">
                        <a href="#paywall-section" class="py-2.5 px-5 rounded-xl bg-[#C6FF33] hover:bg-[#b0ec22] text-[#0f172a] font-bold text-xs uppercase tracking-wider transition-all inline-block shadow-[0_0_20px_rgba(198,255,51,0.3)]">
                            Скачать комплект (5 BYN) ↓
                        </a>
                    </div>
                </div>

                {{-- Interactive Variant 1 Paper Mockup --}}
                <div class="mt-6 p-6 sm:p-8 rounded-2xl bg-white text-[#0f172a] shadow-2xl space-y-6 font-serif select-none">
                    {{-- Sheet Header --}}
                    <div class="flex items-center justify-between border-b-2 border-slate-900 pb-3 font-sans">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 bg-black rounded-lg flex items-center justify-center text-white font-bold text-xs">
                                ◆
                            </div>
                            <div>
                                <div class="font-bold text-sm tracking-tight text-black font-rimma">EDUSFERA</div>
                                <div class="text-[10px] text-slate-500 uppercase">{{ $order->subject }} • Самостоятельная работа</div>
                            </div>
                        </div>
                        <div class="px-3 py-1 bg-slate-100 rounded border border-slate-300 font-bold text-xs">
                            Вариант 1
                        </div>
                    </div>

                    {{-- Student fields --}}
                    <div class="flex flex-wrap gap-4 text-xs font-sans text-slate-600 border-b border-slate-200 pb-3">
                        <div>Учащийся: ___________________________</div>
                        <div>Класс: _____</div>
                        <div>Дата: ___________</div>
                    </div>

                    {{-- Tasks list --}}
                    <div class="space-y-5 text-sm leading-relaxed">
                        @if ($var1 && (! empty($var1['part_a']) || ! empty($var1['part_b'])))
                            {{-- Part A Tasks --}}
                            @foreach ($var1['part_a'] ?? [] as $t)
                                <div class="space-y-2">
                                    <div class="font-semibold text-slate-900 flex items-start gap-2">
                                        <span class="inline-block px-1.5 py-0.5 rounded bg-slate-100 border border-slate-300 text-xs font-sans font-bold">
                                            {{ $t['number'] ?? ('А' . ($loop->index + 1)) }}
                                        </span>
                                        <span>{!! $t['prompt'] ?? ($t['condition_html'] ?? '') !!}</span>
                                    </div>

                                    @if (! empty($t['options']))
                                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 pt-1 font-sans text-xs">
                                            @foreach ($t['options'] as $oIdx => $opt)
                                                @php $letter = chr(65 + $oIdx); @endphp
                                                <div class="p-2 rounded bg-slate-50 border border-slate-200 flex items-center gap-1.5">
                                                    <span class="font-bold text-slate-700">[{{ $letter }}]</span>
                                                    <span class="truncate">{!! $opt['text'] ?? '' !!}</span>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            @endforeach

                            {{-- Part B Tasks --}}
                            @foreach ($var1['part_b'] ?? [] as $t)
                                <div class="space-y-2 pt-2 border-t border-dashed border-slate-200">
                                    <div class="font-semibold text-slate-900 flex items-start gap-2">
                                        <span class="inline-block px-1.5 py-0.5 rounded bg-slate-100 border border-slate-300 text-xs font-sans font-bold">
                                            {{ $t['number'] ?? ('Б' . ($loop->index + 1)) }}
                                        </span>
                                        <span>{!! $t['prompt'] ?? ($t['condition_html'] ?? '') !!}</span>
                                    </div>
                                    <div class="font-sans text-xs text-slate-500 flex items-center gap-2">
                                        <span>Ответ ученика: [ &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; ]</span>
                                    </div>
                                </div>
                            @endforeach
                        @else
                            <div class="text-slate-500 italic text-center py-4">
                                Задания Варианта 1 сформированы и готовы к выгрузке.
                            </div>
                        @endif
                    </div>

                    {{-- Answer sheet sample --}}
                    <div class="pt-4 border-t-2 border-dashed border-slate-400 font-sans text-[11px] flex flex-wrap justify-between items-center text-slate-500">
                        <div>БЛАНК ОТВЕТОВ: №1: [ &nbsp; ] &nbsp; №2: [ &nbsp; ] &nbsp; №3: [ &nbsp; ]</div>
                        <div>Отметка: [ &nbsp; &nbsp; / 10 ]</div>
                    </div>
                </div>

                {{-- Blurred / Locked Variants 2+ Notice --}}
                <div class="mt-4 p-5 rounded-2xl bg-white/[0.02] border border-white/10 relative overflow-hidden text-center space-y-2">
                    <div class="flex items-center justify-center gap-2 text-amber-400 text-xs font-mono uppercase tracking-wider">
                        <span>🔒</span>
                        <span>Вариант 2 (для соседа по парте), Лист с ключами и шкалой перевода скрыты</span>
                    </div>
                    <p class="text-xs text-slate-400 max-w-md mx-auto">
                        При покупке вы мгновенно получаете полный комплект из {{ $order->variant_count }} вариантов, чистые бланки для распечатки и файл в формате Word (.docx) для любых правок.
                    </p>
                </div>
            </div>

            {{-- PAYWALL & UNLOCK SECTION --}}
            <div id="paywall-section" class="glass-panel rounded-3xl p-6 sm:p-10 space-y-8">
                <div class="text-center space-y-2 max-w-xl mx-auto">
                    <h3 class="text-2xl font-bold font-rimma uppercase text-white">
                        Разблокировать полный комплект
                    </h3>
                    <p class="text-sm text-slate-400">
                        Выберите удобный способ получения материалов: разовая оплата картой, абонемент или доступ по подписке для репетиторов.
                    </p>
                </div>

                {{-- Tariff Cards Grid --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    {{-- Option 1: Single Pack (5 BYN) --}}
                    <div class="p-6 rounded-2xl bg-white/[0.04] border-2 border-[#7D39EB] relative flex flex-col justify-between space-y-4">
                        <div class="space-y-3">
                            <div class="flex justify-between items-center">
                                <span class="text-xs font-mono text-[#C6FF33] uppercase">Разовый комплект</span>
                                <span class="px-2 py-0.5 rounded bg-[#7D39EB]/30 text-[#C6FF33] text-[10px] font-bold">Хит</span>
                            </div>
                            <div class="flex items-baseline gap-1.5">
                                <span class="text-3xl font-extrabold text-white">5.00</span>
                                <span class="text-sm text-slate-400 font-mono">BYN</span>
                            </div>
                            <ul class="text-xs text-slate-300 space-y-2 font-mono">
                                <li class="flex items-center gap-2">
                                    <span class="text-[#C6FF33]">✓</span>
                                    <span>Все {{ $order->variant_count }} вариантов проверочной работы</span>
                                </li>
                                <li class="flex items-center gap-2">
                                    <span class="text-[#C6FF33]">✓</span>
                                    <span>PDF A4 для печати (по 2 варианта на лист)</span>
                                </li>
                                <li class="flex items-center gap-2">
                                    <span class="text-[#C6FF33]">✓</span>
                                    <span>Редактируемый файл Word (.docx)</span>
                                </li>
                                <li class="flex items-center gap-2">
                                    <span class="text-[#C6FF33]">✓</span>
                                    <span>Мастер-таблица ответов за 2 минуты</span>
                                </li>
                            </ul>
                        </div>

                        <form method="POST" action="{{ route('generator.pay', $order->order_number) }}?token={{ $order->access_token }}">
                            @csrf
                            <input type="hidden" name="plan_type" value="single">
                            <button type="submit" class="w-full py-3.5 px-4 rounded-xl bg-[#7D39EB] hover:bg-[#6D28D9] text-white font-bold text-sm tracking-tight transition-all shadow-[0_0_20px_rgba(125,57,235,0.4)] active:scale-[0.98] cursor-pointer">
                                Оплатить 5 BYN картой
                            </button>
                        </form>
                    </div>

                    {{-- Option 2: Quarter Pass (19 BYN) --}}
                    <div class="p-6 rounded-2xl bg-white/[0.02] border border-white/10 relative flex flex-col justify-between space-y-4 hover:border-white/20 transition-all">
                        <div class="space-y-3">
                            <div class="flex justify-between items-center">
                                <span class="text-xs font-mono text-slate-400 uppercase">Абонемент на четверть</span>
                                <span class="px-2 py-0.5 rounded bg-emerald-500/20 text-emerald-400 text-[10px] font-bold">Выгода 70%</span>
                            </div>
                            <div class="flex items-baseline gap-1.5">
                                <span class="text-3xl font-extrabold text-white">19.00</span>
                                <span class="text-sm text-slate-400 font-mono">BYN</span>
                                <span class="text-xs text-slate-500 ml-1">/ вся четверть</span>
                            </div>
                            <ul class="text-xs text-slate-300 space-y-2 font-mono">
                                <li class="flex items-center gap-2">
                                    <span class="text-emerald-400">✓</span>
                                    <span>Безлимитная генерация всех предметов</span>
                                </li>
                                <li class="flex items-center gap-2">
                                    <span class="text-emerald-400">✓</span>
                                    <span>Все форматы: самостоятельные, контрольные, ЦТ</span>
                                </li>
                                <li class="flex items-center gap-2">
                                    <span class="text-emerald-400">✓</span>
                                    <span>Мгновенный экспорт в Word (.docx) и PDF</span>
                                </li>
                                <li class="flex items-center gap-2">
                                    <span class="text-emerald-400">✓</span>
                                    <span>Экономия до 60 часов на составлении работ</span>
                                </li>
                            </ul>
                        </div>

                        <form method="POST" action="{{ route('generator.pay', $order->order_number) }}?token={{ $order->access_token }}">
                            @csrf
                            <input type="hidden" name="plan_type" value="quarter_pass">
                            <button type="submit" class="w-full py-3.5 px-4 rounded-xl bg-white/10 hover:bg-white/20 text-white font-bold text-sm tracking-tight transition-all active:scale-[0.98] cursor-pointer">
                                Оформить абонемент за 19 BYN
                            </button>
                        </form>
                    </div>
                </div>

                {{-- Tutor Subscription & Promo Code Bar --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-4 border-t border-white/10">
                    {{-- Tutor Subscription Check --}}
                    <div class="p-4 rounded-2xl bg-[#7D39EB]/10 border border-[#7D39EB]/30 flex flex-col justify-between gap-3">
                        <div>
                            <div class="text-xs font-bold text-[#C6FF33] flex items-center gap-1.5 mb-1">
                                <span>🎓</span>
                                <span>Репетиторам Edusfera</span>
                            </div>
                            <p class="text-xs text-slate-300">
                                @if ($isTutorWithQuota)
                                    У вас есть активная ежемесячная квота генератора проверочных.
                                @else
                                    Репетиторы платформы получают 15 генераций тестов каждый месяц бесплатно.
                                @endif
                            </p>
                        </div>
                        <div>
                            @if ($isTutorWithQuota)
                                <form method="POST" action="{{ route('generator.unlock_tutor', $order->order_number) }}?token={{ $order->access_token }}">
                                    @csrf
                                    <button type="submit" class="py-2.5 px-4 rounded-xl bg-[#C6FF33] hover:bg-[#b0ec22] text-[#0f172a] font-bold text-xs transition-all w-full cursor-pointer">
                                        🔓 Разблокировать бесплатно по подписке
                                    </button>
                                </form>
                            @else
                                <a href="{{ route('login') }}" class="inline-block py-2.5 px-4 rounded-xl bg-white/10 hover:bg-white/20 text-white font-bold text-xs transition-all w-full text-center">
                                    Войти в аккаунт репетитора →
                                </a>
                            @endif
                        </div>
                    </div>

                    {{-- Promo Code Input --}}
                    <div class="p-4 rounded-2xl bg-white/[0.03] border border-white/10 flex flex-col justify-between gap-3">
                        <div>
                            <div class="text-xs font-bold text-slate-200 flex items-center gap-1.5 mb-1">
                                <span>🎟️</span>
                                <span>Есть промокод?</span>
                            </div>
                            <p class="text-xs text-slate-400">
                                Введите специальный промокод репетитора или школы для мгновенного доступа.
                            </p>
                        </div>
                        <form method="POST" action="{{ route('generator.apply_promo', $order->order_number) }}?token={{ $order->access_token }}" class="flex gap-2">
                            @csrf
                            <input 
                                type="text" 
                                name="promo_code" 
                                placeholder="EDUSFERA2026" 
                                class="flex-1 py-2 px-3 rounded-xl bg-black/50 border border-white/15 text-xs text-white uppercase placeholder:text-slate-600 focus:outline-none focus:border-[#7D39EB]"
                                required
                            >
                            <button type="submit" class="py-2 px-4 rounded-xl bg-white/10 hover:bg-white/20 text-white font-bold text-xs transition-all cursor-pointer">
                                Применить
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        @endif

    </div>

    @if (! $isReady)
    <script>
        (function() {
            const orderNumber = @js($order->order_number);
            const accessToken = @js($order->access_token);
            const query = "?token=" + encodeURIComponent(accessToken);
            const statusUrl = "/generator/status/" + orderNumber + query;
            const processUrl = "/generator/process/" + orderNumber + query;
            let progress = 30;
            let checkCount = 0;
            let isDone = false;

            const progressBar = document.getElementById('progress-bar');
            const stageText = document.getElementById('loading-stage-text');
            const stepVariants = document.getElementById('step-variants');
            const stepPdf = document.getElementById('step-pdf');

            function triggerReload() {
                if (isDone) return;
                isDone = true;
                if (progressBar) progressBar.style.width = '100%';
                setTimeout(() => {
                    window.location.reload();
                }, 400);
            }

            // Kick off background job if needed
            fetch(processUrl, {
                method: 'GET',
                headers: { 'Accept': 'application/json' }
            })
            .then(res => res.json())
            .then(data => {
                if (data && data.is_ready) {
                    triggerReload();
                }
            })
            .catch(() => {});

            const timer = setInterval(async () => {
                if (isDone) return;
                checkCount++;
                progress = Math.min(95, progress + 7);
                if (progressBar) progressBar.style.width = progress + '%';

                if (checkCount === 3 && stageText) {
                    stageText.textContent = "Составление вариантов и защита от списывания...";
                } else if (checkCount === 6 && stageText) {
                    stageText.textContent = "Формирование бланков ответов и чистовая верстка...";
                    if (stepVariants) {
                        stepVariants.className = "flex items-center gap-2 text-slate-300";
                        stepVariants.innerHTML = '<span class="text-[#C6FF33] font-bold">✓</span><span>Генерация параллельных вариантов (без списывания)</span>';
                    }
                    if (stepPdf) {
                        stepPdf.className = "flex items-center gap-2 text-slate-300";
                        stepPdf.innerHTML = '<span class="text-[#7D39EB] animate-spin font-bold">◐</span><span>Подготовка академических бланков A4 и Word (.docx)</span>';
                    }
                }

                try {
                    const res = await fetch(statusUrl, { headers: { 'Accept': 'application/json' } });
                    if (!res.ok) return;
                    const data = await res.json();
                    if (data.is_ready) {
                        clearInterval(timer);
                        triggerReload();
                    }
                } catch (e) {}

                if (checkCount > 40) {
                    clearInterval(timer);
                    window.location.reload();
                }
            }, 1800);
        })();
    </script>
    @endif
</body>
</html>
