<!DOCTYPE html>
<html lang="ru" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>Edusfera Вариант — Контрольная работа для всего класса по стандартам ЦТ/ЦЭ 2026</title>
    <meta name="description" content="Edusfera Вариант: 15 индивидуальных вариантов, ответы и готовый PDF для печати по стандартам экзаменационной программы Беларуси.">
    <meta name="keywords" content="edusfera вариант, контрольная работа для класса, проверочные работы цт цэ 2026, самостоятельная работа беларусь, задания без списывания">
    <meta name="robots" content="index, follow, max-image-preview:large">
    <link rel="canonical" href="https://edusfera.by/generator">

    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:locale" content="ru_BY">
    <meta property="og:site_name" content="Edusfera">
    <meta property="og:title" content="Edusfera Вариант — Контрольная работа для всего класса">
    <meta property="og:description" content="15 индивидуальных вариантов, ответы и готовый PDF для печати по стандартам Беларуси.">
    <meta property="og:url" content="https://edusfera.by/generator">
    <meta property="og:image" content="https://edusfera.by/og-image.png">

    <!-- Twitter -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Edusfera Вариант — Контрольная работа для всего класса">
    <meta name="twitter:description" content="15 индивидуальных вариантов, ответы и готовый PDF для печати.">
    <meta name="twitter:image" content="https://edusfera.by/og-image.png">

    <!-- Favicon & Icons -->
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    @include('partials.pwa-meta')
    @include('partials.analytics')

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preload" href="https://fonts.googleapis.com/css2?family=Geist:wght@300;400;500;600;700&family=Inter:wght@400;500;600;700;800&family=Silkscreen:wght@400;700&display=swap" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript>
        <link href="https://fonts.googleapis.com/css2?family=Geist:wght@300;400;500;600;700&family=Inter:wght@400;500;600;700;800&family=Silkscreen:wght@400;700&display=swap" rel="stylesheet">
    </noscript>

    @php
        $isMobileDevice = (new class { use \App\Traits\DetectsMobileDevice; })->isMobileDevice();
        $authUser = auth()->user() ? [
            'id' => auth()->user()->id,
            'name' => auth()->user()->name,
            'email' => auth()->user()->email,
            'role' => is_object(auth()->user()->role) ? auth()->user()->role->value : auth()->user()->role,
            'role_label' => \App\Services\MultiAccountService::roleLabel(auth()->user()->role),
        ] : null;
        $linkedAccounts = $authUser ? app(\App\Services\MultiAccountService::class)->getLinkedAccounts() : [];
        $paymentStatus = request()->query('payment');
    @endphp
    <script>
        window.EDUSFERA_USER = @js($authUser);
        window.EDUSFERA_LINKED_ACCOUNTS = @js($linkedAccounts);
        window.EDUSFERA_CSRF_TOKEN = "{{ csrf_token() }}";
        window.EDUSFERA_IS_MOBILE = @js($isMobileDevice);
        window.EDUSFERA_PAYMENT_STATUS = @js($paymentStatus);
    </script>

    @viteReactRefresh
    @vite(['resources/css/app.css', 'resources/js/app.jsx'])
</head>
<body class="min-h-screen bg-[#0B0B0E] text-white selection:bg-[#C6FF33] selection:text-[#0B0B0E] font-sans antialiased overflow-x-hidden">
    <!-- React Island Mount Point -->
    <div id="generator-app-root" class="w-full min-h-screen flex flex-col justify-between"></div>

    @include('partials.pwa-prompt')
    @include('partials.cookie-banner')
</body>
</html>
