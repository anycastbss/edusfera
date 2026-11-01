<style>
    /* ═══════════════════════════════════════════════════════════════════
       ЭРГОНОМИКА СМАРТФОНОВ И ДОСТУПНОСТЬ ДЛЯ ВСЕХ ЭКРАНОВ КАБИНЕТА
       (UI_RULES.md / WCAG 2.2 AAA / 50+ Accessible Senior UX)
       ═══════════════════════════════════════════════════════════════════ */

    /* ── 0. Плавный вертикальный скролл и предотвращение блокировок на смартфонах (@media < 1024px) ── */
    @media (max-width: 1023px) {
        html, body.fi-body {
            overflow-x: clip !important;
            overflow-y: visible !important;
            -webkit-overflow-scrolling: touch !important;
            touch-action: pan-y pinch-zoom !important;
        }

        .fi-layout {
            overflow-x: clip !important;
            min-height: 100vh !important;
            min-height: 100dvh !important;
        }
    }

    /* ── 1. Детализированная мобильная верстка по умолчанию (@media < 768px) ── */
    @media (max-width: 767px) {
        /* Высокая четкость и читаемость системного шрифта */
        html {
            font-size: 16px !important;
            -webkit-text-size-adjust: 100% !important;
        }

        body.fi-body {
            font-size: 15px !important;
            line-height: 1.5 !important;
            color: #0F172A !important;
            background-color: #F8FAFC !important;
        }
        .dark body.fi-body {
            color: #F8FAFC !important;
            background-color: #0B0F19 !important;
        }

        /* Устранение горизонтального скролла и микро-отступы */
        .fi-main-ctn, .fi-main, .fi-page {
            padding-left: 10px !important;
            padding-right: 10px !important;
            max-width: 100vw !important;
            box-sizing: border-box !important;
            overflow-x: clip !important;
            overflow-y: visible !important;
        }

        /* Заголовки экранов крупнее и выразительнее */
        .fi-header-heading, 
        h1.fi-header-heading, 
        .fi-ta-header-heading {
            font-size: 1.6rem !important;
            font-weight: 900 !important;
            letter-spacing: -0.025em !important;
            line-height: 1.15 !important;
            color: #0F172A !important;
            margin-bottom: 8px !important;
        }
        .dark .fi-header-heading, 
        .dark h1.fi-header-heading {
            color: #FFFFFF !important;
        }

        /* Кнопки: крупный тач-таргет от 48-52px с тактильным откликом */
        .fi-btn, 
        button.fi-btn, 
        a.fi-btn {
            min-height: 48px !important;
            padding: 10px 18px !important;
            border-radius: 14px !important;
            font-size: 0.95rem !important;
            font-weight: 800 !important;
            letter-spacing: 0.01em !important;
            justify-content: center !important;
        }

        /* Формы и поля ввода: защита от авто-зума на iOS (строго 16px) */
        .fi-input, 
        input.fi-input, 
        select.fi-select-input, 
        textarea.fi-input,
        .fi-fo-field-wrp input, 
        .fi-fo-field-wrp select, 
        .fi-fo-field-wrp textarea {
            min-height: 48px !important;
            font-size: 16px !important;
            border-radius: 12px !important;
            padding-left: 14px !important;
            padding-right: 14px !important;
            border-color: #CBD5E1 !important;
        }
        .dark .fi-input, 
        .dark input.fi-input {
            border-color: #334155 !important;
        }

        /* Четкие подписи полей формы без бледности */
        .fi-fo-field-wrp-label, 
        label.fi-fo-field-wrp-label {
            font-size: 0.925rem !important;
            font-weight: 800 !important;
            color: #1E293B !important;
            margin-bottom: 6px !important;
        }
        .dark .fi-fo-field-wrp-label {
            color: #F1F5F9 !important;
        }

        /* Карточки Filament (Widgets, Sections) */
        .fi-section, 
        .fi-widget, 
        .fi-card {
            border-radius: 20px !important;
            border: 1.5px solid #E2E8F0 !important;
            box-shadow: 0 4px 18px -2px rgba(15, 23, 42, 0.05) !important;
        }
        .dark .fi-section, 
        .dark .fi-widget, 
        .dark .fi-card {
            border-color: #1F2937 !important;
            box-shadow: 0 4px 22px -2px rgba(0, 0, 0, 0.45) !important;
        }

        /* Статусные бейджи */
        .fi-badge {
            font-size: 11px !important;
            font-weight: 800 !important;
            padding: 4px 10px !important;
            border-radius: 9999px !important;
        }

        /* Персональная ссылка репетитора в Hero на смартфонах */
        .tutor-hero-booking-bar {
            flex-direction: column !important;
            align-items: stretch !important;
            gap: 10px !important;
            padding: 14px !important;
        }
        .tutor-hero-booking-left {
            width: 100% !important;
        }
        .tutor-hero-copy-btn {
            width: 100% !important;
            min-height: 44px !important;
            justify-content: center !important;
        }
    }

    /* ── 2. РЕЖИМ «КОМФОРТНОЕ ЗРЕНИЕ / КРУПНЫЙ ШРИФТ» (html.comfort-vision, html.ed-large-font, body.comfort-vision) ─────────────────── */
    html.comfort-vision,
    html.ed-large-font,
    body.comfort-vision,
    body.ed-large-font {
        font-size: 18px !important; /* Увеличение базового шрифта до 18px для преподавателей 50+ */
        -webkit-text-size-adjust: 100% !important;
    }

    html.comfort-vision body,
    html.ed-large-font body,
    body.comfort-vision,
    body.ed-large-font {
        line-height: 1.6 !important;
        color: #111827 !important; /* Глубокий темный цвет вместо бледного серого */
    }

    /* Принудительная светлая тема и высокая контрастность: никакого серого #9CA3AF на белом фоне */
    html.comfort-vision p, 
    html.comfort-vision span, 
    html.comfort-vision a, 
    html.comfort-vision li, 
    html.comfort-vision td,
    html.comfort-vision th,
    html.comfort-vision div,
    body.comfort-vision p,
    body.comfort-vision span,
    body.comfort-vision a,
    body.comfort-vision li,
    body.comfort-vision td,
    body.comfort-vision th,
    html.ed-large-font p, 
    html.ed-large-font span, 
    html.ed-large-font a, 
    html.ed-large-font li, 
    html.ed-large-font td,
    html.ed-large-font div {
        -webkit-font-smoothing: antialiased;
    }

    html.comfort-vision .text-slate-400,
    html.comfort-vision .text-slate-500,
    html.comfort-vision .text-zinc-400,
    html.comfort-vision .text-zinc-500,
    html.comfort-vision .text-gray-400,
    html.comfort-vision .text-gray-500,
    html.comfort-vision .text-gray-600,
    html.comfort-vision .tutor-stat-label,
    html.comfort-vision .tutor-hero-sub,
    html.comfort-vision .tutor-card-label,
    body.comfort-vision .text-slate-400,
    body.comfort-vision .text-slate-500,
    body.comfort-vision .text-gray-400,
    body.comfort-vision .text-gray-500,
    html.ed-large-font .text-slate-400,
    html.ed-large-font .text-slate-500,
    html.ed-large-font .text-zinc-400,
    html.ed-large-font .text-zinc-500,
    html.ed-large-font .text-gray-400,
    html.ed-large-font .text-gray-500,
    html.ed-large-font .tutor-stat-label,
    html.ed-large-font .tutor-hero-sub,
    html.ed-large-font .tutor-card-label {
        color: #111827 !important;
        font-weight: 700 !important;
    }

    html.comfort-vision.dark .text-slate-400,
    html.comfort-vision.dark .text-slate-500,
    html.comfort-vision.dark .text-zinc-400,
    html.comfort-vision.dark .text-zinc-500,
    html.comfort-vision.dark .tutor-stat-label,
    html.comfort-vision.dark .tutor-hero-sub,
    html.comfort-vision.dark .tutor-card-label,
    html.ed-large-font.dark .text-slate-400,
    html.ed-large-font.dark .text-slate-500,
    html.ed-large-font.dark .text-zinc-400,
    html.ed-large-font.dark .text-zinc-500,
    html.ed-large-font.dark .tutor-stat-label,
    html.ed-large-font.dark .tutor-hero-sub,
    html.ed-large-font.dark .tutor-card-label {
        color: #E2E8F0 !important;
    }

    /* Поля ввода: минимальная высота 48px, жирные рамки border-2 border-slate-400 */
    html.comfort-vision input,
    html.comfort-vision select,
    html.comfort-vision textarea,
    html.comfort-vision .fi-input,
    html.comfort-vision .fi-fo-field-wrp input,
    html.comfort-vision .fi-fo-field-wrp select,
    html.comfort-vision .fi-fo-field-wrp textarea,
    body.comfort-vision input,
    body.comfort-vision select,
    body.comfort-vision textarea,
    body.comfort-vision .fi-input,
    html.ed-large-font input,
    html.ed-large-font select,
    html.ed-large-font textarea,
    html.ed-large-font .fi-input {
        min-height: 48px !important;
        font-size: 1.05rem !important;
        font-weight: 600 !important;
        border-width: 2px !important;
        border-color: #94A3B8 !important; /* border-slate-400 */
        border-radius: 12px !important;
        color: #111827 !important;
        background-color: #FFFFFF !important;
    }

    html.comfort-vision label,
    html.comfort-vision .fi-fo-field-wrp-label,
    body.comfort-vision label,
    body.comfort-vision .fi-fo-field-wrp-label,
    html.ed-large-font label,
    html.ed-large-font .fi-fo-field-wrp-label {
        font-size: 1.05rem !important;
        font-weight: 800 !important;
        color: #0F172A !important;
        margin-bottom: 6px !important;
    }
    html.comfort-vision.dark label,
    html.comfort-vision.dark .fi-fo-field-wrp-label,
    html.ed-large-font.dark label,
    html.ed-large-font.dark .fi-fo-field-wrp-label {
        color: #FFFFFF !important;
    }

    /* Кнопки: минимальная высота кликабельных зон min-h-[48px] */
    html.comfort-vision .fi-btn,
    html.comfort-vision button:not(.ed-nav-tab):not(.ed-font-toggle-btn):not(.ed-a11y-float-btn),
    html.comfort-vision .tutor-cta,
    html.comfort-vision .student-spotlight-cta,
    html.comfort-vision .tutor-plan-btn,
    html.comfort-vision .ed-agenda-btn,
    html.comfort-vision a.btn,
    body.comfort-vision .fi-btn,
    body.comfort-vision button:not(.ed-nav-tab):not(.ed-font-toggle-btn):not(.ed-a11y-float-btn),
    html.ed-large-font .fi-btn,
    html.ed-large-font button:not(.ed-nav-tab):not(.ed-font-toggle-btn):not(.ed-a11y-float-btn),
    html.ed-large-font .tutor-cta,
    html.ed-large-font .student-spotlight-cta,
    html.ed-large-font .tutor-plan-btn,
    html.ed-large-font .ed-agenda-btn,
    html.ed-large-font a.btn {
        min-height: 48px !important;
        font-size: 1.05rem !important;
        font-weight: 800 !important;
        border-radius: 14px !important;
        padding: 10px 20px !important;
        letter-spacing: 0.01em !important;
    }

    /* Цифры баланса, времени и ключевых метрик */
    html.comfort-vision .tutor-stat-value,
    html.comfort-vision .student-metric-value,
    html.comfort-vision .tutor-hero-time,
    html.ed-large-font .tutor-stat-value,
    html.ed-large-font .student-metric-value,
    html.ed-large-font .tutor-hero-time {
        font-size: 1.9rem !important;
        font-weight: 900 !important;
    }

    /* Четкие контрастные границы карточек */
    html.comfort-vision .tutor-card,
    html.comfort-vision .student-card,
    html.comfort-vision .fi-section,
    html.comfort-vision .fi-widget,
    body.comfort-vision .tutor-card,
    body.comfort-vision .fi-section,
    html.ed-large-font .tutor-card,
    html.ed-large-font .student-card,
    html.ed-large-font .fi-section,
    html.ed-large-font .fi-widget {
        border: 2px solid #94A3B8 !important;
        box-shadow: 0 6px 24px -2px rgba(15, 23, 42, 0.12) !important;
    }
    html.comfort-vision.dark .tutor-card,
    html.comfort-vision.dark .student-card,
    html.comfort-vision.dark .fi-section,
    html.ed-large-font.dark .tutor-card,
    html.ed-large-font.dark .student-card,
    html.ed-large-font.dark .fi-section {
        border-color: #475569 !important;
    }

    /* Нижняя панель навигации в комфортном режиме */
    html.comfort-vision .ed-mobile-bottom-nav,
    html.ed-large-font .ed-mobile-bottom-nav {
        height: calc(72px + env(safe-area-inset-bottom, 0px)) !important;
    }
    html.comfort-vision .ed-nav-icon,
    html.ed-large-font .ed-nav-icon {
        width: 28px !important;
        height: 28px !important;
    }
    html.comfort-vision .ed-nav-label,
    html.ed-large-font .ed-nav-label {
        font-size: 13px !important;
        font-weight: 900 !important;
    }

    /* ── Предотвращение обрезания текста в боковом меню Filament ── */
    .fi-sidebar-item-label,
    .fi-sidebar-group-label,
    .fi-sidebar-item a span,
    .fi-sidebar-item-button span {
        white-space: normal !important;
        overflow: visible !important;
        text-overflow: clip !important;
        line-height: 1.3 !important;
        word-break: normal !important;
    }

    /* ── Полное скрытие плавающей кнопки ИИ (FAB со звёздочкой) на экранах смартфонов ── */
    @media (max-width: 768px) {
        .ed-floating-copilot-container,
        #floating-ai-copilot-root,
        .ed-copilot-fab-wrapper {
            display: none !important;
            visibility: hidden !important;
            pointer-events: none !important;
        }
    }
</style>
