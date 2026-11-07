<!DOCTYPE html>
<html lang="ru" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>Оплата заказа № {{ $order->order_number }} — Альфа-Банк | Edusfera</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    @vite(['resources/css/app.css'])
</head>
<body class="bg-[#000000] text-white min-h-screen flex items-center justify-center p-4 font-sans selection:bg-[#C6FF33] selection:text-black">
    <div class="w-full max-w-md bg-[#0D111A] border border-white/10 rounded-3xl p-6 sm:p-8 shadow-2xl space-y-6">
        
        {{-- Header --}}
        <div class="flex items-center justify-between border-b border-white/10 pb-4">
            <div class="flex items-center gap-2.5">
                <div class="w-7 h-7 rounded-lg bg-[#EF3124] text-white flex items-center justify-center font-bold text-xs">
                    A
                </div>
                <div>
                    <div class="text-sm font-bold text-white leading-tight">Альфа-Банк</div>
                    <div class="text-[10px] text-slate-400">Защищённый платёжный шлюз</div>
                </div>
            </div>
            <span class="text-xs font-mono font-bold px-2.5 py-1 rounded bg-[#C6FF33]/15 text-[#C6FF33]">
                {{ number_format($order->amountByn(), 2) }} BYN
            </span>
        </div>

        {{-- Order Details --}}
        <div class="p-4 rounded-2xl bg-white/[0.03] border border-white/5 space-y-2 text-xs">
            <div class="flex justify-between text-slate-400">
                <span>Заказ:</span>
                <span class="font-mono text-white">{{ $order->order_number }}</span>
            </div>
            <div class="flex justify-between text-slate-400">
                <span>Предмет:</span>
                <span class="text-white">{{ $order->subject }}</span>
            </div>
            <div class="flex justify-between text-slate-400">
                <span>Тема:</span>
                <span class="text-white truncate max-w-[180px]">{{ $order->topic }}</span>
            </div>
            <div class="flex justify-between text-slate-400">
                <span>Формат:</span>
                <span class="text-white">
                    {{ ($order->payload['is_quarter_pass'] ?? false) ? 'Абонемент на четверть (10 тем)' : ($order->variant_count . ' вариантов + ключи') }}
                </span>
            </div>
            <div class="flex justify-between text-slate-400">
                <span>Получатель:</span>
                <span class="text-white font-mono">
                    {{ \Illuminate\Support\Str::mask($order->email, '*', 2, max(1, strpos($order->email, '@') - 3)) }}
                </span>
            </div>
        </div>

        {{-- Payment Action --}}
        <div class="space-y-3 pt-2">
            <a 
                href="{{ route('generator.checkout.callback', ['order' => $order->order_number, 'status' => 'success', 'simulated_success' => 1]) }}"
                class="w-full py-4 px-6 rounded-2xl bg-[#EF3124] hover:bg-[#d6281b] text-white font-bold text-sm shadow-[0_0_25px_rgba(239,49,36,0.35)] active:scale-[0.98] transition-all flex items-center justify-center gap-2 cursor-pointer"
            >
                <span>Подтвердить оплату {{ number_format($order->amountByn(), 2) }} BYN (Карта / ЕРИП)</span>
            </a>

            <a 
                href="{{ route('generator') }}" 
                class="block text-center text-xs text-slate-400 hover:text-white transition-colors"
            >
                ← Вернуться назад
            </a>
        </div>

        <div class="text-[11px] text-slate-500 text-center font-mono">
            Безопасный эквайринг ЗАО «Альфа-Банк» (Беларусь)
        </div>
    </div>
</body>
</html>
