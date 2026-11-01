<x-filament-panels::page>
    @viteReactRefresh
    @vite(['resources/css/app.css', 'resources/js/app.jsx'])

    <style>
        .fi-header,
        .fi-page-header,
        .fi-breadcrumbs {
            display: none !important;
        }
        .fi-page {
            padding: 0.25rem 0.5rem !important;
            margin: 0 !important;
            max-width: 100% !important;
        }
        .fi-main {
            padding-bottom: 0.25rem !important;
        }
        #floating-ai-copilot-root {
            display: none !important;
        }
        @media (max-width: 767px) {
            .fi-topbar {
                display: none !important;
            }
            html, body {
                height: 100% !important;
                min-height: 100% !important;
                max-height: 100% !important;
                overflow: hidden !important;
                width: 100% !important;
                margin: 0 !important;
                padding: 0 !important;
            }
            .fi-layout, .fi-main-ctn, .fi-main, .fi-page, .fi-content {
                padding: 0 !important;
                margin: 0 !important;
                max-width: 100vw !important;
                width: 100vw !important;
                height: 100% !important;
                overflow: hidden !important;
            }

            /* Сброс перекрывающего padding-bottom из нижней панели навигации */
            body:not(.ed-chat-open) .fi-main,
            body:not(.ed-chat-open) .fi-page,
            body:not(.ed-chat-open) .fi-layout,
            body:not(.ed-chat-open) .fi-main-ctn,
            body:not(.ed-chat-open) .fi-content {
                padding-bottom: 0 !important;
            }

            /* Режим списка диалогов: занимает экран строго над фиксированной нижней панелью */
            body:not(.ed-chat-open) #edusfera-messenger-root {
                position: fixed !important;
                top: 0 !important;
                left: 0 !important;
                right: 0 !important;
                bottom: calc(64px + env(safe-area-inset-bottom, 0px)) !important;
                width: 100vw !important;
                height: auto !important;
                max-height: none !important;
                z-index: 40 !important;
                overflow: hidden !important;
            }

            /* Режим активного диалога: полноэкранный нативный мессенджер с клавиатурой */
            body.ed-chat-open #edusfera-messenger-root {
                position: fixed !important;
                top: 0 !important;
                left: 0 !important;
                right: 0 !important;
                bottom: 0 !important;
                width: 100vw !important;
                height: 100% !important;
                max-height: 100% !important;
                z-index: 50 !important;
                overflow: hidden !important;
            }
            body.ed-chat-open .ed-mobile-bottom-nav {
                display: none !important;
            }
            body.ed-chat-open,
            body.ed-chat-open .fi-main,
            body.ed-chat-open .fi-page {
                padding-bottom: 0 !important;
            }
        }
    </style>

    <div
        id="edusfera-messenger-root"
        data-initial-conversation-id="{{ $conversationId }}"
        class="w-full"
    ></div>

    <script>
        window.EDUSFERA_CHAT_INITIAL = {
            conversationId: {{ $conversationId ? (int) $conversationId : 'null' }},
            csrfToken: "{{ csrf_token() }}",
            user: {
                id: {{ (int) auth()->id() }},
                role: '{{ auth()->user()?->role?->value ?? auth()->user()?->role }}',
                name: @json(auth()->user()?->name),
                avatar: @json(auth()->user()?->getFilamentAvatarUrl())
            }
        };

        // На мобильных экранах сразу закрываем боковое меню Filament для полного экрана чата
        document.addEventListener('DOMContentLoaded', () => {
            if (window.innerWidth < 768 && window.Alpine && window.Alpine.store('sidebar')) {
                window.Alpine.store('sidebar').close();
            }
        });
        document.addEventListener('livewire:navigated', () => {
            if (window.innerWidth < 768 && window.Alpine && window.Alpine.store('sidebar')) {
                window.Alpine.store('sidebar').close();
            }
        });
    </script>
</x-filament-panels::page>
