<!DOCTYPE html>
<html lang="ru" class="h-full w-full bg-black">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    @include('partials.pwa-meta')
    <title>Выберите вашу роль — Edusfera</title>

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
        .role-card {
            background: rgba(18, 18, 22, 0.65);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.10);
            box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.37), inset 0 1px 1px rgba(255, 255, 255, 0.15);
            transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .role-card:hover {
            border-color: rgba(198, 255, 51, 0.45);
            transform: translateY(-2px);
            box-shadow: 0 12px 40px 0 rgba(0, 0, 0, 0.5), inset 0 1px 1px rgba(255, 255, 255, 0.25);
        }
        .role-card.student-card:hover {
            border-color: rgba(125, 57, 235, 0.55);
        }
    </style>

    @viteReactRefresh
    @vite(['resources/css/app.css', 'resources/js/app.jsx'])
</head>
<body class="bg-[#08090C] text-white antialiased font-sans min-h-screen flex flex-col justify-between selection:bg-[#C6FF33] selection:text-black relative overflow-x-hidden">

    {{-- Ambient Ambient Lights --}}
    <div class="fixed inset-0 pointer-events-none z-0 overflow-hidden">
        <div class="absolute -top-32 left-1/4 w-96 h-96 bg-[#7D39EB]/15 rounded-full blur-[120px]"></div>
        <div class="absolute top-1/3 -right-24 w-96 h-96 bg-[#C6FF33]/10 rounded-full blur-[140px]"></div>
        <div class="absolute -bottom-32 left-1/3 w-96 h-96 bg-[#38BDF8]/10 rounded-full blur-[130px]"></div>
    </div>

    {{-- Top Header / Brand --}}
    <header class="relative z-10 w-full px-6 py-6 max-w-5xl mx-auto flex items-center justify-between">
        <a href="/" class="flex items-center gap-2 group">
            <span class="font-rimma text-2xl font-bold tracking-tight text-white group-hover:text-[#C6FF33] transition-colors">
                EDUSFERA<span class="text-[#C6FF33]">.BY</span>
            </span>
        </a>

        @if(!empty($pending['email']))
            <div class="flex items-center gap-2.5 px-3 py-1.5 rounded-full bg-white/5 border border-white/10 backdrop-blur-md">
                @if(!empty($pending['avatar']))
                    <img src="{{ $pending['avatar'] }}" alt="{{ $pending['name'] ?? '' }}" class="w-6 h-6 rounded-full object-cover border border-white/20">
                @else
                    <div class="w-6 h-6 rounded-full bg-gradient-to-tr from-[#7D39EB] to-[#C6FF33] flex items-center justify-center text-[11px] font-bold text-black">
                        {{ mb_strtoupper(mb_substr($pending['name'] ?? 'U', 0, 1)) }}
                    </div>
                @endif
                <span class="text-xs text-white/80 max-w-[160px] truncate">{{ $pending['name'] ?? $pending['email'] }}</span>
            </div>
        @endif
    </header>

    {{-- Main Role Selection Body --}}
    <main class="relative z-10 flex-1 flex flex-col items-center justify-center px-4 py-8 max-w-4xl mx-auto w-full">
        
        {{-- Hero Header --}}
        <div class="text-center max-w-xl mx-auto mb-8 sm:mb-10 space-y-2.5">
            <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-[#C6FF33]/10 border border-[#C6FF33]/25 text-[#C6FF33] text-xs font-semibold mb-1">
                <span>✨ Завершение быстрой регистрации через Google</span>
            </div>
            
            <h1 class="font-rimma text-3xl sm:text-4xl lg:text-5xl font-bold tracking-tight text-white leading-tight">
                Выберите вашу роль
            </h1>
            
            <p class="text-sm sm:text-base text-white/60 leading-relaxed">
                Это настроит ваш рабочий кабинет, доступ к виртуальному классу и необходимые инструменты под ваши задачи.
            </p>
        </div>

        {{-- Cards Grid (Tutor vs Student) --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5 w-full">

            {{-- ─── CARD 1: TUTOR (РЕПЕТИТОР) ─── --}}
            <form action="{{ route('social.process_select_role') }}" method="POST" class="w-full">
                @csrf
                <input type="hidden" name="role" value="tutor">
                <input type="hidden" name="plan" value="{{ $pending['plan'] ?? 'pro' }}">

                <button type="submit" class="role-card w-full text-left p-6 sm:p-7 rounded-3xl flex flex-col justify-between h-full group cursor-pointer active:scale-[0.99] transition-transform">
                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <div class="w-12 h-12 rounded-2xl bg-[#C6FF33]/15 border border-[#C6FF33]/30 flex items-center justify-center text-[#C6FF33] shadow-[0_0_20px_rgba(198,255,51,0.2)]">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <rect width="20" height="14" x="2" y="7" rx="2" ry="2"/>
                                    <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/>
                                </svg>
                            </div>
                            <span class="px-2.5 py-1 rounded-full text-[11px] font-bold uppercase tracking-wider bg-[#C6FF33]/15 text-[#C6FF33] border border-[#C6FF33]/25">
                                Для работы
                            </span>
                        </div>

                        <div>
                            <h2 class="text-xl sm:text-2xl font-bold text-white group-hover:text-[#C6FF33] transition-colors">
                                Я репетитор
                            </h2>
                            <p class="text-xs sm:text-sm text-white/60 mt-1.5 leading-relaxed">
                                Для проведения занятий, ведения базы учеников и использования интерактивной доски.
                            </p>
                        </div>

                        {{-- Features bullet list --}}
                        <ul class="space-y-2 pt-2 border-t border-white/5 text-xs text-white/70">
                            <li class="flex items-center gap-2">
                                <svg class="w-4 h-4 text-[#C6FF33] shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                <span>Виртуальный класс и интерактивная доска</span>
                            </li>
                            <li class="flex items-center gap-2">
                                <svg class="w-4 h-4 text-[#C6FF33] shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                <span>CRM расписание и база учеников</span>
                            </li>
                            <li class="flex items-center gap-2">
                                <svg class="w-4 h-4 text-[#C6FF33] shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                <span>AI-ассистент конспектов и авто-чеки НПД</span>
                            </li>
                            <li class="flex items-center gap-2 font-semibold text-white/90">
                                <svg class="w-4 h-4 text-[#C6FF33] shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                <span>14 дней PRO периода в подарок</span>
                            </li>
                        </ul>
                    </div>

                    <div class="mt-6 pt-4 border-t border-white/5">
                        <div class="w-full py-3.5 px-4 rounded-xl bg-[#C6FF33] text-black font-extrabold text-sm flex items-center justify-center gap-2 shadow-[0_0_20px_rgba(198,255,51,0.25)] group-hover:bg-[#d4ff59] transition-all">
                            <span>Продолжить как репетитор</span>
                            <svg class="w-4 h-4 group-hover:translate-x-1 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                            </svg>
                        </div>
                    </div>
                </button>
            </form>

            {{-- ─── CARD 2: STUDENT (УЧЕНИК / РОДИТЕЛЬ) ─── --}}
            <form action="{{ route('social.process_select_role') }}" method="POST" class="w-full">
                @csrf
                <input type="hidden" name="role" value="student">

                <button type="submit" class="role-card student-card w-full text-left p-6 sm:p-7 rounded-3xl flex flex-col justify-between h-full group cursor-pointer active:scale-[0.99] transition-transform">
                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <div class="w-12 h-12 rounded-2xl bg-[#7D39EB]/15 border border-[#7D39EB]/30 flex items-center justify-center text-[#7D39EB] shadow-[0_0_20px_rgba(125,57,235,0.2)]">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M22 10v6M2 10l10-5 10 5-10 5z"/>
                                    <path d="M6 12v5c3 3 9 3 12 0v-5"/>
                                </svg>
                            </div>
                            <span class="px-2.5 py-1 rounded-full text-[11px] font-bold uppercase tracking-wider bg-white/5 text-white/60 border border-white/10">
                                Для занятий
                            </span>
                        </div>

                        <div>
                            <h2 class="text-xl sm:text-2xl font-bold text-white group-hover:text-[#a77aff] transition-colors">
                                Я ученик или родитель
                            </h2>
                            <p class="text-xs sm:text-sm text-white/60 mt-1.5 leading-relaxed">
                                Для подготовки к ЦТ / ЦЭ, занятий с преподавателем и домашних заданий.
                            </p>
                        </div>

                        {{-- Features bullet list --}}
                        <ul class="space-y-2 pt-2 border-t border-white/5 text-xs text-white/70">
                            <li class="flex items-center gap-2">
                                <svg class="w-4 h-4 text-[#7D39EB] shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                <span>Уроки в виртуальном онлайн-классе</span>
                            </li>
                            <li class="flex items-center gap-2">
                                <svg class="w-4 h-4 text-[#7D39EB] shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                <span>Конспекты, материалы и домашние задания</span>
                            </li>
                            <li class="flex items-center gap-2">
                                <svg class="w-4 h-4 text-[#7D39EB] shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                <span>Прямая связь с вашим репетитором</span>
                            </li>
                            <li class="flex items-center gap-2">
                                <svg class="w-4 h-4 text-[#7D39EB] shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                <span>Бесплатный личный кабинет ученика</span>
                            </li>
                        </ul>
                    </div>

                    <div class="mt-6 pt-4 border-t border-white/5">
                        <div class="w-full py-3.5 px-4 rounded-xl bg-white/10 hover:bg-white/15 text-white font-bold text-sm flex items-center justify-center gap-2 border border-white/15 transition-all">
                            <span>Продолжить как ученик</span>
                            <svg class="w-4 h-4 group-hover:translate-x-1 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                            </svg>
                        </div>
                    </div>
                </button>
            </form>

        </div>

        {{-- Helper footer note --}}
        <div class="mt-8 text-center text-xs text-white/40">
            Входя в систему, вы соглашаетесь с <a href="/offer" target="_blank" class="text-white/60 hover:text-white underline">Публичной офертой</a> и <a href="/privacy-policy" target="_blank" class="text-white/60 hover:text-white underline">Политикой конфиденциальности</a> Edusfera.by
        </div>

    </main>

    {{-- Bottom Footer --}}
    <footer class="relative z-10 w-full py-4 text-center text-[11px] text-white/30">
        © {{ date('Y') }} Edusfera.by — Платформа для репетиторов и учеников Беларуси
    </footer>

</body>
</html>
