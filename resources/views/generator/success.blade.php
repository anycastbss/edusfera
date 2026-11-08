<!DOCTYPE html>
<html lang="ru" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>Заказ № {{ $order->order_number }} оплачен | Edusfera</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    @vite(['resources/css/app.css'])
</head>
<body class="bg-[#000000] text-white min-h-screen flex items-center justify-center p-4 font-sans selection:bg-[#C6FF33] selection:text-black">
    <div class="w-full max-w-lg bg-[#08080A] border border-white/15 rounded-3xl p-7 sm:p-10 shadow-2xl space-y-6 text-center">
        
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
                Ваш комплект готов к скачиванию
            </h1>
            <p class="text-sm text-slate-400">
                Пакет из {{ $order->variant_count }} вариантов с единой мастер-таблицей ответов для быстрой проверки.
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
                href="{{ route('generator.download', ['order' => $order->order_number]) }}"
                class="w-full py-4 px-6 rounded-2xl bg-[#C6FF33] hover:bg-[#d4ff59] text-black font-extrabold text-base tracking-tight shadow-[0_0_30px_rgba(198,255,51,0.35)] active:scale-[0.98] transition-all flex items-center justify-center gap-2 cursor-pointer"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                </svg>
                <span>Скачать PDF файл с вариантами</span>
            </a>

            {{-- VIRAL TEACHER REFERRAL CARD --}}
            <div class="p-4 rounded-2xl bg-gradient-to-r from-purple-900/30 via-white/[0.04] to-[#C6FF33]/15 border border-white/10 text-left space-y-2.5">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-white flex items-center gap-1.5">
                        <span>🎁</span>
                        <span>Поделитесь с коллегами в учительской</span>
                    </span>
                    <span class="text-[10px] font-mono font-bold bg-[#C6FF33] text-black px-2 py-0.5 rounded-full">
                        Скидка 20%
                    </span>
                </div>
                <p class="text-[11px] text-slate-300 leading-snug">
                    Отправьте ссылку коллегам в школьный чат: промокод <strong class="text-white">EDUSFERA2026</strong> даёт скидку 20% на любой комплект проверочных работ.
                </p>
                <div class="flex items-center gap-2 pt-1">
                    <button 
                        type="button" 
                        onclick="copySuccessShareLink()" 
                        id="share-btn"
                        class="flex-1 py-2.5 px-3 rounded-xl bg-white/10 hover:bg-white/15 border border-white/10 text-white font-semibold text-xs transition-all flex items-center justify-center gap-1.5 cursor-pointer"
                    >
                        <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <rect x="9" y="9" width="13" height="13" rx="2" ry="2" stroke-width="2"></rect>
                            <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1" stroke-width="2"></path>
                        </svg>
                        <span id="share-btn-text">Скопировать для Telegram / Viber</span>
                    </button>
                </div>
            </div>

            {{-- UPSELL CARD TO PLATFORM (B2B SaaS Conversion) --}}
            <div class="p-5 rounded-2xl bg-gradient-to-br from-[#7D39EB]/15 via-white/[0.03] to-[#C6FF33]/10 border border-white/10 text-left space-y-3">
                <div class="flex items-center gap-2">
                    <span class="px-2 py-0.5 rounded bg-[#7D39EB] text-white text-[10px] font-bold uppercase tracking-wider">Для педагогов</span>
                    <span class="text-xs font-semibold text-white">Ведёте уроки онлайн?</span>
                </div>
                <p class="text-xs text-slate-300 leading-relaxed">
                    Проводите занятия в защищенном видеоклассе Edusfera с интерактивной доской, методистом и автоматическими чеками для налога на профдоход (НПД МНС РБ). Ученикам не нужно регистрироваться — вход по ссылке в 1 клик.
                </p>
                <a 
                    href="{{ route('for-tutors') }}" 
                    class="inline-flex items-center gap-2 text-xs font-bold text-[#C6FF33] hover:text-[#d4ff59] transition-colors"
                >
                    <span>Узнать о возможностях личного кабинета</span>
                    <span>→</span>
                </a>
            </div>

            <a 
                href="{{ route('generator') }}" 
                class="block text-center text-xs text-slate-400 hover:text-white transition-colors pt-2"
            >
                ← Составить ещё один комплект
            </a>
        </div>

    </div>

    <script>
        function copySuccessShareLink() {
            const text = 'Коллеги, рекомендую сервис проверочных работ без списания на Edusfera.by: 15 вариантов с разными числами + сводная ведомость ответов для проверки за 2 минуты. Держите промокод на скидку 20%: EDUSFERA2026. Ссылка: https://edusfera.by/generator?promo=EDUSFERA2026';
            if (navigator.clipboard) {
                navigator.clipboard.writeText(text);
            }
            const btnText = document.getElementById('share-btn-text');
            if (btnText) {
                btnText.textContent = 'Ссылка и промокод скопированы!';
                btnText.parentElement.style.borderColor = '#C6FF33';
                btnText.parentElement.style.color = '#C6FF33';
                setTimeout(() => {
                    btnText.textContent = 'Скопировать для Telegram / Viber';
                    btnText.parentElement.style.borderColor = 'rgba(255,255,255,0.1)';
                    btnText.parentElement.style.color = '#ffffff';
                }, 3000);
            }
        }
    </script>
</body>
</html>
