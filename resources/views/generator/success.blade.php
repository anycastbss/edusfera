<!DOCTYPE html>
<html lang="ru" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>Заказ № {{ $order->order_number }} | Edusfera</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @vite(['resources/css/app.css'])
</head>
<body class="bg-[#000000] text-white min-h-screen flex items-center justify-center p-4 font-sans selection:bg-[#7D39EB] selection:text-white">
    @php
        $isReady = ! empty($order->generated_content) && $order->status === 'completed';
    @endphp

    <div class="w-full max-w-lg bg-[#0E0E12] border border-white/15 rounded-3xl p-7 sm:p-10 shadow-2xl space-y-6 text-center relative overflow-hidden">
        
        {{-- Ambient backlight --}}
        <div class="absolute -top-24 left-1/2 -translate-x-1/2 w-64 h-64 rounded-full bg-[#7D39EB]/20 blur-3xl pointer-events-none"></div>

        {{-- STATE 1: Ready to Download --}}
        <div id="ready-card" class="{{ $isReady ? 'block' : 'hidden' }} space-y-6">
            {{-- Success Icon --}}
            <div class="w-16 h-16 rounded-full bg-[#C6FF33]/20 border border-[#C6FF33] text-[#C6FF33] flex items-center justify-center mx-auto shadow-[0_0_30px_rgba(198,255,51,0.25)]">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                </svg>
            </div>

            <div class="space-y-2">
                <div class="text-xs font-mono uppercase tracking-widest text-[#C6FF33]">
                    Оплата успешно завершена
                </div>
                <h1 class="text-2xl sm:text-3xl font-bold tracking-tight text-white font-rimma uppercase">
                    Ваш комплект готов
                </h1>
                <p class="text-sm text-slate-400">
                    Пакет из {{ $order->variant_count }} вариантов с единой мастер-таблицей ответов для быстрой проверки за 2 минуты.
                </p>
            </div>

            {{-- Order Details Card --}}
            <div class="p-4 rounded-2xl bg-white/[0.03] border border-white/10 text-left text-xs space-y-2 font-mono">
                <div class="flex justify-between text-slate-400">
                    <span>Номер заказа:</span>
                    <span class="text-white font-bold">{{ $order->order_number }}</span>
                </div>
                <div class="flex justify-between text-slate-400">
                    <span>Предмет:</span>
                    <span class="text-white font-semibold">{{ $order->subject }}</span>
                </div>
                <div class="flex justify-between text-slate-400">
                    <span>Тема:</span>
                    <span class="text-white truncate max-w-[200px]" title="{{ $order->topic }}">{{ $order->topic }}</span>
                </div>
                <div class="flex justify-between text-slate-400">
                    <span>Сумма:</span>
                    <span class="text-[#C6FF33] font-bold">{{ number_format($order->amountByn(), 2) }} BYN (Оплачено)</span>
                </div>
                <div class="flex justify-between text-slate-400 pt-1 border-t border-white/5">
                    <span>Отправлено на email:</span>
                    <span class="text-slate-300 font-mono">
                        {{ \Illuminate\Support\Str::mask($order->email, '*', 2, max(1, strpos($order->email, '@') - 3)) }}
                    </span>
                </div>
            </div>

            {{-- Action Buttons --}}
            <div class="space-y-3 pt-2">
                <a 
                    id="download-btn"
                    href="{{ route('generator.download', ['order' => $order->order_number]) }}"
                    class="w-full py-4 px-6 rounded-2xl bg-[#7D39EB] hover:bg-[#6D28D9] text-white font-bold text-base tracking-tight shadow-[0_0_30px_rgba(125,57,235,0.4)] active:scale-[0.98] transition-all flex items-center justify-center gap-2 cursor-pointer"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                    </svg>
                    <span>Скачать PDF файл с вариантами и ключами</span>
                </a>

                <a 
                    href="{{ route('generator') }}" 
                    class="block w-full py-3 px-4 rounded-xl bg-white/[0.05] hover:bg-white/[0.1] text-slate-300 hover:text-white font-medium text-xs transition-colors border border-white/10"
                >
                    ← Составить ещё один комплект проверочной
                </a>
            </div>

            <div class="text-[11px] text-slate-500 font-mono">
                Ссылка на скачивание также продублирована на указанный email.
            </div>
        </div>

        {{-- STATE 2: In-Progress Generator Loader --}}
        <div id="loading-card" class="{{ $isReady ? 'hidden' : 'block' }} space-y-6">
            {{-- Animated Loader Spinner --}}
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
                    <span>Составление проверочной работы</span>
                </div>
                <h1 class="text-2xl sm:text-3xl font-bold tracking-tight text-white font-rimma uppercase">
                    ИИ формирует {{ $order->variant_count }} вариантов
                </h1>
                <p id="loading-stage-text" class="text-sm text-slate-300">
                    Педагогический анализ спецификаций ЦТ/ЦЭ 2026 и составление заданий...
                </p>
            </div>

            {{-- Progress bar --}}
            <div class="w-full bg-white/10 rounded-full h-2 overflow-hidden">
                <div id="progress-bar" class="h-full bg-gradient-to-r from-[#7D39EB] to-[#C6FF33] transition-all duration-700 ease-out" style="width: 25%"></div>
            </div>

            {{-- Live steps checklist --}}
            <div class="p-4 rounded-2xl bg-white/[0.03] border border-white/10 text-left text-xs space-y-2.5 font-mono">
                <div class="flex items-center gap-2 text-slate-300">
                    <span class="text-[#C6FF33] font-bold">✓</span>
                    <span>Проверка параметров и калибровка сложности</span>
                </div>
                <div id="step-variants" class="flex items-center gap-2 text-slate-400">
                    <span class="text-[#7D39EB] animate-spin font-bold">◐</span>
                    <span>Генерация параллельных вариантов (без списывания)</span>
                </div>
                <div id="step-pdf" class="flex items-center gap-2 text-slate-500">
                    <span class="text-slate-600">○</span>
                    <span>Верстка академического PDF A4 со STIX формулами</span>
                </div>
            </div>

            <div class="text-[11px] text-slate-500 font-mono">
                Обычно занимает от 10 до 25 секунд. Пожалуйста, не закрывайте вкладку.
            </div>
        </div>

    </div>

    @if (! $isReady)
    <script>
        (function() {
            const orderNumber = @js($order->order_number);
            const statusUrl = "/generator/status/" + orderNumber;
            const processUrl = "/generator/process/" + orderNumber;
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            let progress = 25;
            let checkCount = 0;
            let isProcessing = false;

            const progressBar = document.getElementById('progress-bar');
            const stageText = document.getElementById('loading-stage-text');
            const stepVariants = document.getElementById('step-variants');
            const stepPdf = document.getElementById('step-pdf');
            const loadingCard = document.getElementById('loading-card');
            const readyCard = document.getElementById('ready-card');

            // Trigger background processing if not already started
            fetch(processUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                }
            }).catch(() => {});

            const timer = setInterval(async () => {
                checkCount++;
                progress = Math.min(92, progress + 8);
                if (progressBar) progressBar.style.width = progress + '%';

                if (checkCount === 3 && stageText) {
                    stageText.textContent = "Составление вариантов и защита от списывания...";
                } else if (checkCount === 6 && stageText) {
                    stageText.textContent = "Сборка мастер-таблицы ключей и чистовая верстка A4...";
                    if (stepVariants) {
                        stepVariants.className = "flex items-center gap-2 text-slate-300";
                        stepVariants.innerHTML = '<span class="text-[#C6FF33] font-bold">✓</span><span>Генерация параллельных вариантов (без списывания)</span>';
                    }
                    if (stepPdf) {
                        stepPdf.className = "flex items-center gap-2 text-slate-300";
                        stepPdf.innerHTML = '<span class="text-[#7D39EB] animate-spin font-bold">◐</span><span>Верстка академического PDF A4 со STIX формулами</span>';
                    }
                }

                try {
                    const res = await fetch(statusUrl, { headers: { 'Accept': 'application/json' } });
                    if (!res.ok) return;
                    const data = await res.json();

                    if (data.is_ready) {
                        clearInterval(timer);
                        if (progressBar) progressBar.style.width = '100%';
                        setTimeout(() => {
                            if (loadingCard) loadingCard.classList.add('hidden');
                            if (readyCard) {
                                readyCard.classList.remove('hidden');
                                readyCard.classList.add('block');
                            }
                        }, 400);
                    }
                } catch (e) {
                    // Retrying quietly
                }

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
