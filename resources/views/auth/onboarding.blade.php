<!DOCTYPE html>
<html lang="ru" class="h-full w-full bg-black">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    @include('partials.pwa-meta')
    <title>{{ ($initialData['role'] ?? '') === 'student' ? 'Настройка профиля ученика — Edusfera' : 'Анкета преподавателя — Edusfera' }}</title>

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
            height: 100%;
            height: 100dvh;
            overflow: hidden;
            margin: 0;
            padding: 0;
            background-color: #08090C;
        }
        #aurora-onboarding {
            height: 100%;
            height: 100dvh;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
    </style>

    <script>
        window.EDUSFERA_CSRF_TOKEN = "{{ csrf_token() }}";
        window.EDUSFERA_ONBOARDING_INITIAL = @json($initialData ?? null);
    </script>

    @viteReactRefresh
    @vite(['resources/css/app.css', 'resources/js/app.jsx'])
</head>
<body class="aurora-body bg-[#08090C] text-white antialiased font-sans h-full overflow-hidden selection:bg-[#C6FF33] selection:text-black">
    <div id="aurora-onboarding" class="w-full h-full"></div>
</body>
</html>
