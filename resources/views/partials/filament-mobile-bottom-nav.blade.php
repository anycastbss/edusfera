@auth
    @php
        $user = auth()->user();
        $unreadCount = 0;
        try {
            $unreadCount = app(\App\Services\ChatUnreadCounter::class)->countForUser($user);
        } catch (\Throwable $e) {
            $unreadCount = 0;
        }

        $isHome = request()->is('admin') && ! request()->is('admin/*');
        $isLessons = request()->is('admin/lessons*');
        $isMessages = request()->is('admin/messages*');
        $isProfile = request()->is('admin/profile*') || request()->is('admin/tutor-profiles*');

        $profileUrl = filament()->getProfileUrl() ?? url('/admin/profile');
    @endphp

    <style>
        /* ═══ АДАПТИВНАЯ НИЖНЯЯ НАВИГАЦИЯ ДЛЯ СМАРТФОНОВ (WCAG 2.2 / 50+ ACCESSIBILITY) ═══ */
        @media (min-width: 768px) {
            .ed-mobile-bottom-nav {
                display: none !important;
            }
        }

        @media (max-width: 767px) {
            /* Добавляем безопасный нижний отступ для основного контента, чтобы навбар ничего не перекрывал */
            body:not(.ed-chat-open), 
            body:not(.ed-chat-open) .fi-main, 
            body:not(.ed-chat-open) .fi-page, 
            body:not(.ed-chat-open) .fi-resource-list-records-page {
                padding-bottom: calc(74px + env(safe-area-inset-bottom, 0px)) !important;
            }

            body.ed-chat-open,
            body.ed-chat-open .fi-main,
            body.ed-chat-open .fi-page {
                padding-bottom: 0 !important;
            }

            body.ed-chat-open .ed-mobile-bottom-nav {
                display: none !important;
            }

            /* Смещаем плавающий ИИ-виджет (FAB) выше навбара на мобильных устройствах */
            .ed-floating-copilot-container {
                bottom: calc(80px + env(safe-area-inset-bottom, 12px)) !important;
            }

            .ed-mobile-bottom-nav {
                position: fixed;
                bottom: 0;
                left: 0;
                right: 0;
                z-index: 45;
                display: flex;
                align-items: center;
                justify-content: space-around;
                height: calc(64px + env(safe-area-inset-bottom, 0px));
                padding-bottom: env(safe-area-inset-bottom, 0px);
                background: rgba(255, 255, 255, 0.98);
                backdrop-filter: blur(16px);
                -webkit-backdrop-filter: blur(16px);
                border-top: 1.5px solid #E2E8F0;
                box-shadow: 0 -4px 20px -2px rgba(15, 23, 42, 0.08);
                -webkit-tap-highlight-color: transparent;
                font-family: inherit;
            }

            .dark .ed-mobile-bottom-nav {
                background: rgba(15, 23, 42, 0.98);
                border-top-color: #1E293B;
                box-shadow: 0 -4px 24px -2px rgba(0, 0, 0, 0.5);
            }

            .ed-nav-tab {
                position: relative;
                flex: 1;
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                height: 100%;
                min-height: 56px;
                padding: 6px 2px 4px;
                color: #64748B;
                text-decoration: none;
                background: transparent;
                border: none;
                cursor: pointer;
                transition: color 0.15s ease, transform 0.1s ease;
                user-select: none;
            }

            .dark .ed-nav-tab {
                color: #94A3B8;
            }

            .ed-nav-tab:active {
                transform: scale(0.93);
            }

            .ed-nav-icon-box {
                position: relative;
                display: flex;
                align-items: center;
                justify-content: center;
                width: 28px;
                height: 28px;
            }

            .ed-nav-icon {
                width: 24px;
                height: 24px;
                stroke-width: 2.2px;
                transition: transform 0.15s ease;
            }

            .ed-nav-label {
                margin-top: 3px;
                font-size: 11px;
                font-weight: 700;
                line-height: 1.1;
                letter-spacing: 0.01em;
                white-space: nowrap;
            }

            .ed-nav-pill {
                display: block;
                width: 14px;
                height: 3px;
                border-radius: 9999px;
                background: transparent;
                margin-top: 2px;
                transition: background-color 0.2s ease, width 0.2s ease;
            }

            /* Активная вкладка (Четкий контрастный фокус) */
            .ed-nav-tab--active {
                color: #7D39EB !important;
                font-weight: 800;
            }

            .dark .ed-nav-tab--active {
                color: #A78BFA !important;
            }

            .ed-nav-tab--active .ed-nav-icon {
                transform: translateY(-1px);
            }

            .ed-nav-tab--active .ed-nav-pill {
                background: #7D39EB;
                width: 18px;
            }

            .dark .ed-nav-tab--active .ed-nav-pill {
                background: #A78BFA;
            }

            /* Бейдж непрочитанных сообщений */
            .ed-nav-badge {
                position: absolute;
                top: -3px;
                right: -8px;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                min-width: 18px;
                height: 18px;
                padding: 0 4px;
                border-radius: 9999px;
                background: #EF4444;
                color: #ffffff;
                font-size: 10px;
                font-weight: 800;
                line-height: 1;
                box-shadow: 0 2px 5px rgba(239, 68, 68, 0.4);
                border: 2px solid #ffffff;
            }

            .dark .ed-nav-badge {
                border-color: #0F172A;
            }
        }
    </style>

    <nav class="ed-mobile-bottom-nav" aria-label="Мобильная навигация">
        {{-- 1. Главная --}}
        <a href="{{ url('/admin') }}"
           x-on:click="if (window.Alpine && window.Alpine.store('sidebar') && window.Alpine.store('sidebar').isOpen) { window.Alpine.store('sidebar').close(); }"
           class="ed-nav-tab {{ $isHome ? 'ed-nav-tab--active' : '' }}"
           title="Главная панель">
            <div class="ed-nav-icon-box">
                <svg class="ed-nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
                </svg>
            </div>
            <span class="ed-nav-label">Главная</span>
            <span class="ed-nav-pill"></span>
        </a>

        {{-- 2. Уроки / Расписание --}}
        <a href="{{ url('/admin/lessons') }}"
           x-on:click="if (window.Alpine && window.Alpine.store('sidebar') && window.Alpine.store('sidebar').isOpen) { window.Alpine.store('sidebar').close(); }"
           class="ed-nav-tab {{ $isLessons ? 'ed-nav-tab--active' : '' }}"
           title="Расписание занятий">
            <div class="ed-nav-icon-box">
                <svg class="ed-nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5m-9-6h.008v.008H12v-.008zM12 15h.008v.008H12V15zm0 2.25h.008v.008H12v-.008zM9.75 15h.008v.008H9.75V15zm0 2.25h.008v.008H9.75v-.008zM7.5 15h.008v.008H7.5V15zm0 2.25h.008v.008H7.5v-.008zm6.75-4.5h.008v.008h-.008v-.008zm0 2.25h.008v.008h-.008V15zm0 2.25h.008v.008h-.008v-.008zm2.25-4.5h.008v.008H16.5v-.008zm0 2.25h.008v.008H16.5V15z" />
                </svg>
            </div>
            <span class="ed-nav-label">Уроки</span>
            <span class="ed-nav-pill"></span>
        </a>

        {{-- 3. Сообщения / Чат --}}
        <a href="{{ url('/admin/messages') }}"
           x-on:click="if (window.Alpine && window.Alpine.store('sidebar') && window.Alpine.store('sidebar').isOpen) { window.Alpine.store('sidebar').close(); }"
           class="ed-nav-tab {{ $isMessages ? 'ed-nav-tab--active' : '' }}"
           title="Сообщения и чаты">
            <div class="ed-nav-icon-box">
                <svg class="ed-nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 01-2.555-.337A5.972 5.972 0 015.41 20.97a5.969 5.969 0 01-.474-.065 4.48 4.48 0 00.978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25z" />
                </svg>
                @if ($unreadCount > 0)
                    <span class="ed-nav-badge">{{ $unreadCount > 9 ? '9+' : $unreadCount }}</span>
                @endif
            </div>
            <span class="ed-nav-label">Чат</span>
            <span class="ed-nav-pill"></span>
        </a>

        {{-- 4. Профиль / Кабинет --}}
        <a href="{{ $profileUrl }}"
           x-on:click="if (window.Alpine && window.Alpine.store('sidebar') && window.Alpine.store('sidebar').isOpen) { window.Alpine.store('sidebar').close(); }"
           class="ed-nav-tab {{ $isProfile ? 'ed-nav-tab--active' : '' }}"
           title="Личный профиль">
            <div class="ed-nav-icon-box">
                <svg class="ed-nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17.982 18.725A7.488 7.488 0 0012 15.75a7.488 7.488 0 00-5.982 2.975m11.964 0a9 9 0 10-11.964 0m11.964 0A8.966 8.966 0 0112 21a8.966 8.966 0 01-5.982-2.275M15 9.75a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
            </div>
            <span class="ed-nav-label">Профиль</span>
            <span class="ed-nav-pill"></span>
        </a>

        {{-- 5. Меню (Ещё) — открывает нативный сайдбар Filament в зоне досягаемости большого пальца --}}
        <button type="button"
                x-data="{}"
                x-on:click="$store.sidebar.isOpen ? $store.sidebar.close() : $store.sidebar.open()"
                :class="{ 'ed-nav-tab--active': $store.sidebar && $store.sidebar.isOpen }"
                class="ed-nav-tab"
                title="Все разделы меню"
                aria-label="Все разделы меню">
            <div class="ed-nav-icon-box">
                <svg class="ed-nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                </svg>
            </div>
            <span class="ed-nav-label">Меню</span>
            <span class="ed-nav-pill"></span>
        </button>
    </nav>

    @if (! $user->isAdmin())
        <div class="ed-switch-pwa-dock" id="ed-pwa-return-dock" style="display: none;">
            <a href="{{ url('/m?mobile=1') }}" class="ed-switch-pwa-link">
                <span class="ed-switch-pwa-title">
                    <span class="ed-switch-pwa-icon">📱</span>
                    <span>Мобильный кабинет</span>
                </span>
                <span class="ed-switch-pwa-btn">Перейти &rarr;</span>
            </a>
        </div>
        <style>
            @media (max-width: 767px) {
                .ed-switch-pwa-dock {
                    position: fixed;
                    bottom: calc(72px + env(safe-area-inset-bottom, 0px));
                    left: 12px;
                    right: 12px;
                    z-index: 44;
                }
                .ed-switch-pwa-link {
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                    background: linear-gradient(135deg, #7D39EB 0%, #6366F1 100%);
                    color: #ffffff !important;
                    padding: 8px 14px;
                    border-radius: 9999px;
                    font-size: 12px;
                    font-weight: 700;
                    text-decoration: none !important;
                    box-shadow: 0 4px 16px rgba(125, 57, 235, 0.4);
                    border: 1px solid rgba(255, 255, 255, 0.2);
                    backdrop-filter: blur(8px);
                    -webkit-backdrop-filter: blur(8px);
                }
                .ed-switch-pwa-title {
                    display: flex;
                    align-items: center;
                    gap: 6px;
                }
                .ed-switch-pwa-btn {
                    background: rgba(255, 255, 255, 0.25);
                    padding: 2px 10px;
                    border-radius: 9999px;
                    font-size: 11px;
                }
            }
            @media (min-width: 768px) {
                .ed-switch-pwa-dock {
                    display: none !important;
                }
            }
        </style>
        <script>
            (function(){
                try {
                    var isSmall = window.innerWidth <= 768 || window.screen.width <= 768;
                    var isDesktopPref = sessionStorage.getItem('edusfera_prefer_desktop') === 'true' || window.location.search.indexOf('desktop=1') !== -1 || document.cookie.indexOf('edusfera_device=desktop') !== -1;
                    if (isSmall && isDesktopPref) {
                        var dock = document.getElementById('ed-pwa-return-dock');
                        if (dock) dock.style.display = 'block';
                    }
                } catch(e){}
            })();
        </script>
    @endif
@endauth
