<!DOCTYPE html>
<html lang="ru" class="h-full w-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#0A0A0C" media="(prefers-color-scheme: dark)">
    <meta name="theme-color" content="#F8FAFC" media="(prefers-color-scheme: light)">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    
    @include('partials.pwa-meta')
    <script>
        if (localStorage.getItem('edusfera_theme') === 'light') {
            document.documentElement.classList.add('light');
        } else {
            document.documentElement.classList.add('dark');
        }
        if (localStorage.getItem('comfort_vision') === 'true') {
            document.documentElement.classList.add('comfort-vision', 'ed-large-font');
        }
    </script>
    <title>Личный кабинет — Edusfera</title>

    {{-- Fonts identical to Aurora design system --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Geist:wght@300;400;500;600;700;800;900&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="preload" href="/fonts/Rimma_sans-Bold.ttf" as="font" type="font/ttf" crossorigin>

    <style>
        @font-face {
            font-family: 'Rimma Sans';
            src: url('/fonts/Rimma_sans-Bold.ttf') format('truetype');
            font-weight: 700;
            font-style: normal;
            font-display: swap;
        }
        .font-rimma {
            font-family: 'Rimma Sans', sans-serif !important;
        }
        body, input, textarea, select, button, .font-geist, .font-sans {
            font-family: 'Geist', 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif !important;
        }
        html, body {
            overflow-x: clip !important;
            -webkit-tap-highlight-color: transparent;
        }
    </style>

    <script>
        window.EDUSFERA_CSRF_TOKEN = "{{ csrf_token() }}";
        
        try {
            var serverData = {!! json_encode($initialData, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) !!};
            if (serverData && Object.keys(serverData).length > 0) {
                window.EDUSFERA_MOBILE_INITIAL_DATA = serverData;
                localStorage.setItem('edusfera_mobile_cache_v1', JSON.stringify(serverData));
                localStorage.setItem('edusfera_mobile_cache_time', Date.now().toString());
            } else {
                var cached = localStorage.getItem('edusfera_mobile_cache_v1');
                if (cached) {
                    window.EDUSFERA_MOBILE_INITIAL_DATA = JSON.parse(cached);
                    window.EDUSFERA_LOADED_FROM_OFFLINE_CACHE = true;
                }
            }
        } catch(e) {
            console.warn('[Cache Hydration Warning]', e);
        }

        // Service Worker Registration for Offline & Low-Bandwidth Resilience
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function() {
                navigator.serviceWorker.register('/sw.js')
                    .then(function(registration) {
                        registration.update();
                    })
                    .catch(function(err) {
                        console.warn('[Edusfera PWA SW] Registration failed:', err);
                    });
            });
        }
    </script>

    @viteReactRefresh
    @vite(['resources/css/app.css', 'resources/js/app.jsx'])
</head>
<body class="h-full w-full bg-[#0A0A0C] text-white antialiased font-sans selection:bg-[#C6FF33] selection:text-black">
    <div id="cabinet-app" class="w-full min-h-screen">
        {{-- High-prestige Aurora Skeleton Loader before React hydration --}}
        <div class="fixed inset-0 flex flex-col items-center justify-center bg-[#0A0A0C] text-white z-50">
            <div class="relative flex items-center justify-center">
                <div class="absolute w-32 h-32 rounded-full bg-[#7D39EB]/20 blur-2xl animate-pulse"></div>
                <div class="w-16 h-16 rounded-2xl bg-[#121214] border border-white/10 flex items-center justify-center shadow-2xl relative z-10">
                    <svg width="32" height="32" viewBox="0 0 64 64" fill="none" class="animate-bounce">
                        <path d="M32 10L54 32L32 54L10 32L32 10Z" stroke="#C6FF33" stroke-width="6" stroke-linejoin="round" />
                        <path d="M32 22L42 32L32 42L22 32L32 22Z" fill="#C6FF33" />
                    </svg>
                </div>
            </div>
            <span class="mt-4 text-xs font-mono tracking-widest text-white/50 uppercase">Edusfera Личный Кабинет</span>
        </div>
    </div>
</body>
</html>
