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

            <div class="grid grid-cols-2 gap-2.5">
                <a 
                    href="{{ route('generator.board', ['order' => $order->order_number]) }}"
                    class="py-3 px-3 rounded-2xl bg-white/[0.07] hover:bg-white/[0.12] text-white font-bold text-xs tracking-tight border border-white/15 hover:border-[#38BDF8]/60 transition-all flex items-center justify-center gap-1.5 cursor-pointer shadow-sm"
                >
                    <svg class="w-4 h-4 text-[#38BDF8]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                    </svg>
                    <span>Доска для проектора</span>
                </a>

                <a 
                    href="{{ route('generator.share', ['order' => $order->order_number]) }}"
                    target="_blank"
                    class="py-3 px-3 rounded-2xl bg-white/[0.07] hover:bg-white/[0.12] text-white font-bold text-xs tracking-tight border border-white/15 hover:border-emerald-400/60 transition-all flex items-center justify-center gap-1.5 cursor-pointer shadow-sm"
                >
                    <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z" />
                    </svg>
                    <span>Поделиться с коллегой</span>
                </a>
            </div>

            {{-- Prominent Teacher Studio Card --}}
            <a 
                href="{{ route('generator.teacher', ['email' => $order->email, 'token' => \App\Http\Controllers\GeneratorCheckoutController::generateMagicToken($order->email)]) }}"
                class="group block p-4 sm:p-4.5 rounded-2xl bg-gradient-to-r from-[#7D39EB]/25 via-white/[0.04] to-[#C6FF33]/20 border border-[#C6FF33]/40 hover:border-[#C6FF33] transition-all shadow-[0_0_25px_rgba(198,255,51,0.15)] text-left cursor-pointer"
            >
                <div class="flex items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-black/70 border border-[#C6FF33]/40 flex items-center justify-center text-lg shrink-0 group-hover:scale-105 transition-transform shadow-inner">
                            🎒
                        </div>
                        <div class="space-y-0.5">
                            <div class="text-[10px] font-mono font-bold uppercase tracking-wider text-[#C6FF33]">
                                Учительская Edusfera
                            </div>
                            <div class="text-xs sm:text-sm font-extrabold text-white group-hover:text-[#C6FF33] transition-colors leading-snug">
                                Материалы сохранены в вашей Учительской. Открыть архив вариантов →
                            </div>
                        </div>
                    </div>
                    <svg class="w-5 h-5 text-[#C6FF33] group-hover:translate-x-1 transition-transform shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7" />
                    </svg>
                </div>
            </a>

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
</body>
</html>
