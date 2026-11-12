<!DOCTYPE html>
<html lang="ru" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>Заказ № {{ $order->order_number }} оплачен | Edusfera</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    @vite(['resources/css/app.css'])
</head>
<body class="bg-[#000000] text-white min-h-screen flex items-center justify-center p-4 font-sans selection:bg-[#7D39EB] selection:text-white">
    <div class="w-full max-w-lg bg-[#0E0E12] border border-white/15 rounded-3xl p-7 sm:p-10 shadow-2xl space-y-6 text-center">
        
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
</body>
</html>
