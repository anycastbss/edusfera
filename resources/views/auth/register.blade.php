<!DOCTYPE html>
<html lang="ru" class="h-full w-full bg-black">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    @include('partials.pwa-meta')
    <title>Вход и Регистрация — Edusfera</title>

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
    </style>

    <script>
        window.EDUSFERA_CSRF_TOKEN = "{{ csrf_token() }}";
    </script>

    @viteReactRefresh
    @vite(['resources/css/app.css', 'resources/js/app.jsx'])
</head>
<body class="aurora-body bg-black text-white antialiased font-sans min-h-screen selection:bg-[#C6FF33] selection:text-black">
    <div id="aurora-auth" class="w-full min-h-screen"></div>
    @include('partials.pwa-prompt')
</body>
</html>
