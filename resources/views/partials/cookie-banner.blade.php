@once('edusfera-cookie-banner')
<div id="edusfera-cookie-banner" 
     class="fixed bottom-4 left-4 right-4 sm:left-auto sm:right-6 sm:bottom-6 sm:max-w-md z-[9999] transition-all duration-300 transform translate-y-8 opacity-0 pointer-events-none"
     role="dialog"
     aria-labelledby="cookie-banner-title"
     aria-describedby="cookie-banner-desc"
     style="display: none;">
    <div class="relative overflow-hidden rounded-2xl bg-[#0f172a]/95 text-white p-5 sm:p-6 shadow-[0_20px_50px_rgba(0,0,0,0.5)] border border-white/15 backdrop-blur-xl">
        {{-- Subtle ambient glow --}}
        <div class="absolute -top-12 -right-12 w-28 h-28 bg-[#7D39EB]/30 rounded-full blur-2xl pointer-events-none"></div>
        <div class="absolute -bottom-12 -left-12 w-28 h-28 bg-[#C6FF33]/20 rounded-full blur-2xl pointer-events-none"></div>

        <div class="relative space-y-3">
            <div class="flex items-start justify-between gap-3">
                <div class="flex items-center gap-2.5">
                    <span class="w-8 h-8 rounded-xl bg-white/10 border border-white/10 flex items-center justify-center text-base shrink-0">
                        🍪
                    </span>
                    <div>
                        <h2 id="cookie-banner-title" class="text-sm font-bold text-white tracking-tight">
                            Файлы cookie и конфиденциальность
                        </h2>
                        <span class="text-[10px] text-white/50 font-medium">Закон РБ № 99-З</span>
                    </div>
                </div>

                <button type="button" 
                        onclick="window.__closeCookieBanner('essential')"
                        class="text-white/40 hover:text-white transition-colors p-1 -mr-1 rounded-lg hover:bg-white/10"
                        title="Закрыть (только обязательные)">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>

            <p id="cookie-banner-desc" class="text-xs text-slate-300 leading-relaxed">
                Мы используем технические файлы cookie для авторизации и проведения уроков, а также аналитические — для оптимизации скорости платформы. Вы можете принять все или оставить только обязательные.
            </p>

            <div class="pt-1 flex flex-col sm:flex-row items-stretch sm:items-center gap-2">
                <button type="button"
                        onclick="window.__closeCookieBanner('all')"
                        class="px-4 py-2.5 rounded-xl bg-[#C6FF33] hover:bg-[#b8f520] text-black font-extrabold text-xs shadow-sm transition-all text-center cursor-pointer active:scale-95">
                    Принять все
                </button>
                <button type="button"
                        onclick="window.__closeCookieBanner('essential')"
                        class="px-4 py-2.5 rounded-xl bg-white/10 hover:bg-white/15 text-white font-semibold text-xs border border-white/10 transition-all text-center cursor-pointer active:scale-95">
                    Только обязательные
                </button>
                <a href="{{ route('legal.cookie') }}"
                   class="px-2 py-2 text-[11px] text-white/60 hover:text-white underline underline-offset-2 transition-colors text-center sm:ml-auto">
                    Подробнее
                </a>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    var banner = document.getElementById('edusfera-cookie-banner');
    if (!banner) return;

    var STORAGE_KEY = 'edusfera_cookie_consent';

    window.__openCookieBanner = function() {
        banner.style.display = 'block';
        requestAnimationFrame(function() {
            banner.classList.remove('translate-y-8', 'opacity-0', 'pointer-events-none');
            banner.classList.add('translate-y-0', 'opacity-100');
        });
    };

    window.__closeCookieBanner = function(consentType) {
        consentType = consentType || 'essential';
        try {
            localStorage.setItem(STORAGE_KEY, consentType);
        } catch (_) {}

        banner.classList.remove('translate-y-0', 'opacity-100');
        banner.classList.add('translate-y-8', 'opacity-0', 'pointer-events-none');
        setTimeout(function() {
            banner.style.display = 'none';
        }, 300);

        try {
            window.dispatchEvent(new CustomEvent('edusfera:cookie-consent', {
                detail: { consent: consentType }
            }));
        } catch (_) {}
    };

    var currentConsent = null;
    try {
        currentConsent = localStorage.getItem(STORAGE_KEY);
    } catch (_) {}

    if (!currentConsent) {
        // Show banner with small delay for smooth page entrance
        setTimeout(function() {
            window.__openCookieBanner();
        }, 800);
    }
})();
</script>
@endonce
