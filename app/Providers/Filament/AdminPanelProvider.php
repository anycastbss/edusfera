<?php

declare(strict_types=1);

namespace App\Providers\Filament;

use App\Filament\Pages\Auth\EditProfile;
use App\Filament\Pages\Auth\Login;
use App\Filament\Pages\Auth\Register;
use App\Filament\Pages\Dashboard;
use App\Filament\Pages\MessagesPage;
use App\Filament\SiteAdmin\Widgets\AdminOverviewStatsWidget;
use App\Filament\Widgets\AdminWelcomeWidget;
use App\Filament\Widgets\StudentTutorsWidget;
use App\Filament\Widgets\StudentUpcomingLessonsWidget;
use App\Filament\Widgets\StudentWelcomeWidget;
use App\Filament\Widgets\TutorActionCenterWidget;
use App\Filament\Widgets\TutorFinanceOverview;
use App\Filament\Widgets\TutorHeroWidget;
use App\Filament\Widgets\TutorOnboardingWidget;
use App\Http\Middleware\EnsureTutorHasActiveSubscription;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationItem;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\MaxWidth;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Blade;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->homeUrl('/')
            ->brandName('Edusfera')
            ->favicon(asset('favicon.svg'))
            ->login(Login::class)
            ->registration(Register::class)
            ->profile(EditProfile::class)
            ->databaseNotifications()
            ->databaseNotificationsPolling('10s')
            ->maxContentWidth(MaxWidth::Full)
            ->simplePageMaxContentWidth(MaxWidth::Full)
            ->colors([
                'primary' => Color::Hex('#7D39EB'),
                'secondary' => Color::Hex('#C6FF33'),
                'success' => Color::Hex('#10B981'),
                'warning' => Color::Hex('#F59E0B'),
                'danger' => Color::Hex('#EF4444'),
                'info' => Color::Hex('#06B6D4'),
            ])
            ->navigationGroups([
                'Мониторинг и карта системы',
                'Логи и безопасность',
                'Управление платформой',
                'Инструменты инженера',
                'Пользователи и модерация',
                'Финансы и SaaS',
                'Контент',
                'Уроки и ученики',
                'Учебные материалы',
                'Профиль и документы',
                'Занятия',
                'Подготовка',
                'Связь',
                'Финансы',
                'Профиль',
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->discoverPages(in: app_path('Filament/SiteAdmin/Pages'), for: 'App\\Filament\\SiteAdmin\\Pages')
            ->pages([
                Dashboard::class,
                MessagesPage::class,
            ])
            ->renderHook(
                PanelsRenderHook::SIDEBAR_NAV_START,
                fn (): string => (auth()->user()?->isTutor() ? Blade::render('@include("partials.filament-tutor-help-button")') : ''),
            )
            ->renderHook(
                PanelsRenderHook::HEAD_START,
                function (): string {
                    $isComfortFromProfile = (bool) (auth()->user()?->tutorProfile?->comfort_vision_mode ?? false);
                    $profileJsCondition = $isComfortFromProfile ? 'true' : 'false';

                    return '<meta name="csrf-token" content="' . csrf_token() . '"><script>' .
                        '(function(){' .
                            'try{' .
                                '// 0. Отключение Filament на смартфонах и принудительный мгновенный переход в PWA /m' .
                                'var isChMobile = (navigator.userAgentData && navigator.userAgentData.mobile === true);' .
                                'var isMobileUA = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini|Mobile|Silk|HarmonyOS/i.test(navigator.userAgent);' .
                                'var isIpadTouch = /Macintosh/i.test(navigator.userAgent) && navigator.maxTouchPoints > 1 && (screen.width <= 1024 || screen.height <= 1024);' .
                                'var isMobileDevice = isChMobile || isMobileUA || isIpadTouch;' .
                                'if (isMobileDevice) {' .
                                    'document.cookie = "edusfera_device=mobile; path=/; max-age=31536000; SameSite=Lax";' .
                                    'try { sessionStorage.removeItem("edusfera_prefer_desktop"); } catch(e){}' .
                                    'if (!window.location.pathname.startsWith("/site-admin") && !window.location.pathname.includes("/print") && !window.location.pathname.includes("/certificate") && !window.location.pathname.includes("/logout")) {' .
                                        'var tab = "";' .
                                        'if (window.location.pathname.indexOf("/messages") !== -1) tab = "?tab=chat";' .
                                        'else if (window.location.pathname.indexOf("/wallet") !== -1 || window.location.pathname.indexOf("/subscription") !== -1 || window.location.pathname.indexOf("/npd") !== -1) tab = "?tab=finance";' .
                                        'else if (window.location.pathname.indexOf("/lessons") !== -1 || window.location.pathname.indexOf("/availability") !== -1 || window.location.pathname.indexOf("/schedule") !== -1 || window.location.pathname.indexOf("/homework") !== -1 || window.location.pathname.indexOf("/diagnostic") !== -1) tab = "?tab=lessons";' .
                                        'else if (window.location.pathname.indexOf("/profile") !== -1) tab = "?tab=profile";' .
                                        'window.location.replace("/m" + tab);' .
                                        'return;' .
                                    '}' .
                                '} else {' .
                                    'document.cookie = "edusfera_device=desktop; path=/; max-age=31536000; SameSite=Lax";' .
                                '}' .
                                '// 1. На экранах смартфонов и планшетов (< 1024px) боковое меню всегда закрыто по умолчанию,' .
                                '// чтобы не перекрывать экран кабинета подложкой и не блокировать клики и скролл' .
                                'if (window.innerWidth < 1024) {' .
                                    'try { localStorage.setItem("isOpen", "false"); } catch(e){}' .
                                '}' .
                                '// 2. Сброс устаревшего принудительного крупного шрифта по умолчанию (стандартный шрифт по умолчанию)' .
                                'if (localStorage.getItem("comfort_vision_v2") === null) {' .
                                    'localStorage.removeItem("comfort_vision");' .
                                    'localStorage.removeItem("edusfera_large_font");' .
                                    'localStorage.setItem("comfort_vision_v2", "true");' .
                                '}' .
                                'var stored = localStorage.getItem("comfort_vision");' .
                                'var isComfort = stored === "true" || (stored === null && ' . $profileJsCondition . ' === true);' .
                                'if(isComfort){' .
                                    'document.documentElement.classList.add("comfort-vision", "ed-large-font");' .
                                    'if(document.body){ document.body.classList.add("comfort-vision", "ed-large-font"); }' .
                                    'else { document.addEventListener("DOMContentLoaded", function(){ if(document.body) document.body.classList.add("comfort-vision", "ed-large-font"); }); }' .
                                '} else {' .
                                    'document.documentElement.classList.remove("comfort-vision", "ed-large-font");' .
                                    'if(document.body){ document.body.classList.remove("comfort-vision", "ed-large-font"); }' .
                                    'else { document.addEventListener("DOMContentLoaded", function(){ if(document.body) document.body.classList.remove("comfort-vision", "ed-large-font"); }); }' .
                                '}' .
                            '}catch(e){}' .
                            'function closeMobileSidebar(){' .
                                'if (window.innerWidth < 1024 && window.Alpine && window.Alpine.store && window.Alpine.store("sidebar")) {' .
                                    'window.Alpine.store("sidebar").close();' .
                                '}' .
                            '}' .
                            'document.addEventListener("DOMContentLoaded", closeMobileSidebar);' .
                            'document.addEventListener("livewire:navigated", closeMobileSidebar);' .
                            'document.addEventListener("alpine:init", function(){' .
                                'if (window.innerWidth < 1024) {' .
                                    'setTimeout(closeMobileSidebar, 0);' .
                                '}' .
                            '});' .
                            'window.addEventListener("livewire:error", function(e){' .
                                'if(e && e.detail && e.detail.status === 419){' .
                                    'console.warn("[Livewire] Session expired or 419, refreshing softly...");' .
                                '}' .
                            '});' .
                        '})();' .
                    '</script>';
                },
            )
            ->renderHook(
                PanelsRenderHook::USER_MENU_BEFORE,
                fn (): string => Blade::render('@include("partials.accessibility-font-toggle")'),
            )
            ->renderHook(
                PanelsRenderHook::STYLES_AFTER,
                fn (): string => Blade::render('@include("partials.filament-mobile-ergonomics")'),
            )
            ->renderHook(
                PanelsRenderHook::HEAD_END,
                fn (): string => Blade::render('@include("partials.pwa-meta")'),
            )
            ->renderHook(
                PanelsRenderHook::BODY_END,
                fn (): string => Blade::render('@include("partials.filament-mobile-bottom-nav")') . (
                    (request()->is('admin/tutor-ai-assistant*') || request()->is('admin/messages*') || (auth()->user()?->isAdmin()))
                        ? Blade::render(' @include("partials.pwa-prompt")')
                        : Blade::render(' @include("partials.pwa-prompt") @include("partials.ai-copilot-widget")')
                ),
            )
            ->renderHook(
                PanelsRenderHook::PAGE_START,
                fn (): string => (request()->routeIs('filament.admin.pages.dashboard') || request()->path() === 'admin')
                    ? (auth()->user()?->isTutor()
                        ? view('filament.widgets.partials.tutor-dashboard-styles')->render()
                        : ((auth()->user()?->isStudent() || auth()->user()?->isParent())
                            ? view('filament.widgets.partials.student-dashboard-styles')->render()
                            : ''))
                    : '',
            )
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                AdminOverviewStatsWidget::class,
                AdminWelcomeWidget::class,
                TutorHeroWidget::class,
                TutorFinanceOverview::class,
                TutorActionCenterWidget::class,
                TutorOnboardingWidget::class,
                StudentWelcomeWidget::class,
                StudentUpcomingLessonsWidget::class,
                StudentTutorsWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
                \App\Http\Middleware\TrackActiveSession::class,
                \App\Http\Middleware\RedirectMobileToCabinetMiddleware::class,
            ])
            ->authMiddleware([
                Authenticate::class,
                \App\Http\Middleware\RedirectMobileToCabinetMiddleware::class,
                EnsureTutorHasActiveSubscription::class,
            ]);
    }
}
