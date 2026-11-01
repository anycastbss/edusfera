@php
    $tutorUser = auth()->user();
@endphp

@if ($tutorUser && $tutorUser->isTutor())
<div class="px-3 pt-2 pb-3">
    <!-- Кнопка перехода в официальный Telegram-канал Edusfera -->
    <a 
        href="https://t.me/edusferaby" 
        target="_blank" 
        rel="noopener noreferrer"
        class="group relative block w-full text-left overflow-hidden rounded-xl p-3 border transition-all duration-200 shadow-sm
               bg-gradient-to-br from-sky-500/15 via-sky-500/5 to-purple-600/10 
               hover:from-sky-500/25 hover:to-purple-600/20
               border-sky-400/40 hover:border-sky-400/70 dark:border-sky-500/30 dark:hover:border-sky-400/60"
        style="cursor: pointer; text-decoration: none;"
        title="Официальный Telegram-канал Edusfera.by"
    >
        <div class="flex items-center gap-2.5">
            <div class="shrink-0 w-8 h-8 rounded-lg bg-[#229ED9] text-white flex items-center justify-center font-bold text-base shadow-sm group-hover:scale-105 transition-transform">
                <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24">
                    <path d="M12 0C5.373 0 0 5.373 0 12s5.373 12 12 12 12-5.373 12-12S18.627 0 12 0zm5.894 8.221-1.97 9.28c-.145.658-.537.818-1.084.508l-3-2.21-1.446 1.394c-.16.16-.295.295-.605.295l.213-3.053 5.56-5.023c.242-.213-.054-.333-.373-.121l-6.871 4.326-2.962-.924c-.643-.204-.657-.643.136-.953l11.57-4.461c.537-.197 1.006.128.832.942z"/>
                </svg>
            </div>
            <div class="min-w-0 flex-1">
                <div class="flex items-center justify-between">
                    <span class="text-[12px] font-bold text-sky-900 dark:text-sky-300 leading-snug">
                        Канал Edusfera
                    </span>
                    <span class="text-[10px] font-extrabold px-1.5 py-0.5 rounded-full bg-sky-500/20 text-sky-600 dark:text-sky-400">
                        Telegram
                    </span>
                </div>
                <div class="text-[11px] text-gray-700 dark:text-gray-300 leading-tight mt-0.5 truncate">
                    @edusferaby · Новости и помощь ➔
                </div>
            </div>
        </div>
    </a>
</div>
@endif
