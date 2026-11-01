{{--
    Дизайн-система кабинета ученика Edusfera (UI_RULES.md / .agents/AGENTS.md):
    - Единый акцент #7D39EB (фиолетовый), неоновый статус #C6FF33 / #10B981 для онлайн-класса;
    - Glassmorphism, мягкие тени, 8pt сетка, 20px скругления;
    - Адаптивные тач-таргеты >= 44px на смартфонах;
    - Полная поддержка светлой и темной (.dark) темы;
    - Автономный CSS, не зависящий от сборки Tailwind админки.
--}}

<style>
    /* ═══ Базовые переменные и сброс для кабинета ученика (MAYA + Telegram/Viber UX) ══════════ */
    :root {
        --stu-accent: #7D39EB;
        --stu-accent-hover: #6C2BD9;
        --stu-accent-light: rgba(125, 57, 235, 0.08);
        --stu-accent-glow: rgba(125, 57, 235, 0.22);
        --stu-live-green: #10B981;
        --stu-live-lime: #C6FF33;
        --stu-card-bg: rgba(255, 255, 255, 0.90);
        --stu-card-border: rgba(226, 232, 240, 0.85);
        --stu-card-shadow: 0 8px 28px -4px rgba(16, 10, 31, 0.05), 0 2px 8px -2px rgba(16, 10, 31, 0.02), inset 0 1px 1px rgba(255, 255, 255, 0.8);
        --stu-text-main: #0F172A;
        --stu-text-muted: #64748B;
        --stu-surface-subtle: #F8FAFC;
    }

    .dark {
        --stu-card-bg: rgba(17, 24, 39, 0.88);
        --stu-card-border: rgba(31, 41, 55, 0.85);
        --stu-card-shadow: 0 10px 32px -4px rgba(0, 0, 0, 0.45), inset 0 1px 1px rgba(255, 255, 255, 0.08);
        --stu-text-main: #F8FAFC;
        --stu-text-muted: #94A3B8;
        --stu-surface-subtle: rgba(30, 41, 59, 0.5);
        --stu-accent-light: rgba(125, 57, 235, 0.18);
    }

    .student-dashboard-root [x-cloak] { display: none !important; }

    /* ═══ Общий контейнер и карточки ═══════════════════════════════ */
    .student-card {
        background: var(--stu-card-bg);
        border: 1px solid var(--stu-card-border);
        border-radius: 22px;
        padding: 24px;
        box-shadow: var(--stu-card-shadow);
        -webkit-backdrop-filter: blur(14px);
        backdrop-filter: blur(14px);
        transition: transform 0.2s cubic-bezier(0.16, 1, 0.3, 1), box-shadow 0.2s ease, border-color 0.2s ease;
        position: relative;
        overflow: hidden;
    }

    @media (max-width: 640px) {
        .student-card { padding: 18px; border-radius: 18px; }
    }

    /* ═══ 1. Hero-шапка ученика ════════════════════════════════════ */
    .student-hero-header {
        display: flex;
        flex-direction: column;
        gap: 16px;
        margin-bottom: 24px;
    }

    .student-hero-top {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 12px 24px;
    }

    .student-hero-title-box h1 {
        margin: 0;
        font-size: clamp(1.6rem, 1.2rem + 1.6vw, 2.35rem);
        font-weight: 900;
        letter-spacing: -0.03em;
        line-height: 1.15;
        color: var(--stu-text-main);
    }

    .student-hero-subtitle {
        margin: 6px 0 0;
        font-size: 0.95rem;
        color: var(--stu-text-muted);
        display: flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
    }

    .student-role-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 4px 12px;
        border-radius: 9999px;
        background: var(--stu-accent-light);
        color: var(--stu-accent);
        font-size: 12px;
        font-weight: 700;
        letter-spacing: 0.02em;
        border: 1px solid rgba(125, 57, 235, 0.2);
    }

    .student-hero-actions {
        display: flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
    }

    .student-chip-btn {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 8px 14px;
        border-radius: 12px;
        font-size: 13px;
        font-weight: 600;
        text-decoration: none;
        transition: all 0.15s ease;
        border: 1px solid var(--stu-card-border);
        background: var(--stu-card-bg);
        color: var(--stu-text-main);
        cursor: pointer;
    }

    .student-chip-btn:hover {
        border-color: var(--stu-accent);
        color: var(--stu-accent);
        transform: translateY(-1px);
        box-shadow: 0 4px 12px var(--stu-accent-glow);
    }

    .student-chip-btn--primary {
        background: linear-gradient(135deg, #7D39EB, #6320D6);
        color: #FFFFFF !important;
        border-color: transparent;
        box-shadow: 0 4px 14px var(--stu-accent-glow);
    }

    .student-chip-btn--primary:hover {
        background: linear-gradient(135deg, #6C2BD9, #5214C4);
        transform: translateY(-2px);
    }

    .student-chip-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 18px;
        height: 18px;
        padding: 0 5px;
        border-radius: 9999px;
        background: #EF4444;
        color: #FFFFFF;
        font-size: 11px;
        font-weight: 800;
        line-height: 1;
    }

    /* ═══ 2. Spotlight-карточка урока (Живой урок / Ближайший в стиле звонка Telegram) ═══ */
    .student-spotlight-card {
        border-radius: 22px;
        padding: 24px;
        position: relative;
        overflow: hidden;
        margin-bottom: 24px;
        transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
        -webkit-backdrop-filter: blur(16px);
        backdrop-filter: blur(16px);
    }

    /* Состояние: Урок прямо сейчас (LIVE / Telegram Video Call banner) */
    .student-spotlight-card--live {
        background: linear-gradient(135deg, rgba(16, 185, 129, 0.12) 0%, rgba(125, 57, 235, 0.08) 100%), var(--stu-card-bg);
        border: 2px solid #10B981;
        box-shadow: 0 10px 32px rgba(16, 185, 129, 0.22), inset 0 1px 1px rgba(255, 255, 255, 0.6);
    }

    .dark .student-spotlight-card--live {
        background: linear-gradient(135deg, rgba(16, 185, 129, 0.16) 0%, rgba(125, 57, 235, 0.14) 100%), var(--stu-card-bg);
        border-color: rgba(16, 185, 129, 0.85);
        box-shadow: 0 12px 36px rgba(16, 185, 129, 0.28), inset 0 1px 1px rgba(255, 255, 255, 0.1);
    }

    /* Состояние: Урок скоро (запланирован / Pinned Telegram chat) */
    .student-spotlight-card--upcoming {
        background: linear-gradient(135deg, rgba(125, 57, 235, 0.05) 0%, rgba(248, 250, 252, 0.8) 100%), var(--stu-card-bg);
        border: 1px solid rgba(125, 57, 235, 0.24);
        box-shadow: 0 8px 24px -4px rgba(125, 57, 235, 0.08), inset 0 1px 1px rgba(255, 255, 255, 0.7);
    }
    .dark .student-spotlight-card--upcoming {
        background: linear-gradient(135deg, rgba(125, 57, 235, 0.12) 0%, rgba(30, 41, 59, 0.6) 100%), var(--stu-card-bg);
        border-color: rgba(125, 57, 235, 0.35);
        box-shadow: 0 8px 28px -4px rgba(0, 0, 0, 0.35), inset 0 1px 1px rgba(255, 255, 255, 0.06);
    }

    /* Состояние: Спокойно / Нет уроков */
    .student-spotlight-card--empty {
        background: var(--stu-surface-subtle);
        border: 1.5px dashed var(--stu-card-border);
        text-align: center;
        padding: 32px 24px;
        border-radius: 22px;
    }

    .student-spotlight-inner {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 20px;
    }

    .student-spotlight-status {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 4px 12px;
        border-radius: 9999px;
        background: rgba(125, 57, 235, 0.08);
        font-size: 11px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.06em;
    }
    .dark .student-spotlight-status { background: rgba(125, 57, 235, 0.18); }

    .student-pulse-dot {
        width: 10px;
        height: 10px;
        border-radius: 9999px;
        background: #10B981;
        box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7);
        animation: stuPulse 1.6s infinite;
    }

    @keyframes stuPulse {
        0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7); }
        70% { transform: scale(1.1); box-shadow: 0 0 0 10px rgba(16, 185, 129, 0); }
        100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
    }

    .student-spotlight-details {
        display: flex;
        align-items: center;
        gap: 16px;
        margin-top: 12px;
    }

    /* Telegram-style Circular Avatar with active online dot */
    .student-tutor-avatar-wrap {
        position: relative;
        flex-shrink: 0;
    }

    .student-tutor-avatar-lg {
        width: 58px;
        height: 58px;
        border-radius: 9999px;
        background: linear-gradient(135deg, #7D39EB, #9047FF);
        color: #FFFFFF;
        font-size: 22px;
        font-weight: 800;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        box-shadow: 0 4px 14px var(--stu-accent-glow);
        border: 2px solid rgba(255, 255, 255, 0.8);
    }
    .dark .student-tutor-avatar-lg {
        border-color: rgba(255, 255, 255, 0.15);
    }

    .student-tutor-online-badge {
        position: absolute;
        bottom: 1px;
        right: 1px;
        width: 14px;
        height: 14px;
        border-radius: 9999px;
        background: #10B981;
        border: 2.5px solid var(--stu-card-bg);
    }

    .student-spotlight-info h3 {
        margin: 0;
        font-size: 1.25rem;
        font-weight: 800;
        color: var(--stu-text-main);
        line-height: 1.2;
    }

    .student-spotlight-meta {
        margin-top: 4px;
        font-size: 0.9rem;
        color: var(--stu-text-muted);
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
    }

    .student-spotlight-cta {
        display: inline-flex;
        align-items: center;
        gap: 10px;
        padding: 12px 24px;
        border-radius: 14px;
        font-size: 15px;
        font-weight: 800;
        text-decoration: none;
        cursor: pointer;
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        white-space: nowrap;
    }
    @media (max-width: 640px) {
        .student-spotlight-cta {
            width: 100%;
            min-height: 52px;
            font-size: 1rem;
            justify-content: center;
            border-radius: 16px;
        }
    }

    .student-spotlight-cta--live {
        background: linear-gradient(135deg, #10B981, #059669);
        color: #FFFFFF !important;
        box-shadow: 0 6px 20px rgba(16, 185, 129, 0.35);
        animation: liveBounce 2.5s infinite;
    }

    @keyframes liveBounce {
        0%, 100% { transform: translateY(0); }
        50% { transform: translateY(-3px); }
    }

    .student-spotlight-cta--live:hover {
        background: linear-gradient(135deg, #059669, #047857);
        transform: translateY(-2px) scale(1.02);
        box-shadow: 0 8px 25px rgba(16, 185, 129, 0.45);
    }

    .student-spotlight-cta--upcoming {
        background: var(--stu-accent);
        color: #FFFFFF !important;
        box-shadow: 0 4px 15px var(--stu-accent-glow);
    }

    .student-spotlight-cta--upcoming:hover {
        background: var(--stu-accent-hover);
        transform: translateY(-2px);
    }

    /* ═══ 3. Сетка интерактивных смарт-метрик (Telegram / Viber Mini App cards) ═══ */
    .student-metrics-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 16px;
        margin-bottom: 24px;
    }

    @media (max-width: 1024px) {
        .student-metrics-grid { grid-template-columns: repeat(2, 1fr); }
    }
    @media (max-width: 640px) {
        .student-metrics-grid { grid-template-columns: 1fr; gap: 12px; }
    }

    .student-metric-card {
        background: var(--stu-card-bg);
        border: 1px solid var(--stu-card-border);
        border-radius: 20px;
        padding: 20px;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        gap: 14px;
        transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
        position: relative;
        overflow: hidden;
        -webkit-backdrop-filter: blur(14px);
        backdrop-filter: blur(14px);
        box-shadow: 0 4px 18px -2px rgba(16, 10, 31, 0.04), inset 0 1px 1px rgba(255, 255, 255, 0.8);
    }
    .dark .student-metric-card {
        box-shadow: 0 4px 20px -2px rgba(0, 0, 0, 0.3), inset 0 1px 1px rgba(255, 255, 255, 0.06);
    }

    .student-metric-card:hover {
        border-color: rgba(125, 57, 235, 0.45);
        transform: translateY(-3px);
        box-shadow: 0 12px 28px -4px rgba(125, 57, 235, 0.16), inset 0 1px 1px rgba(255, 255, 255, 0.9);
    }
    .dark .student-metric-card:hover {
        box-shadow: 0 12px 30px -4px rgba(125, 57, 235, 0.3), inset 0 1px 1px rgba(255, 255, 255, 0.1);
    }

    .student-metric-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .student-metric-label {
        font-size: 11px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        color: var(--stu-text-muted);
    }

    /* Telegram squircle icon container */
    .student-metric-icon-wrap {
        width: 42px;
        height: 42px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
        border: 1px solid rgba(255, 255, 255, 0.6);
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
        flex-shrink: 0;
    }
    .dark .student-metric-icon-wrap {
        border-color: rgba(255, 255, 255, 0.08);
    }

    .student-metric-value {
        font-size: 1.85rem;
        font-weight: 900;
        letter-spacing: -0.03em;
        line-height: 1.1;
        font-variant-numeric: tabular-nums;
        color: var(--stu-text-main);
        margin-top: 4px;
    }

    .student-metric-subtext {
        font-size: 13px;
        font-weight: 500;
        color: var(--stu-text-muted);
        display: flex;
        align-items: center;
        gap: 6px;
        line-height: 1.4;
    }

    .student-metric-action {
        font-size: 12px;
        font-weight: 800;
        color: var(--stu-accent);
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 12px;
        border-radius: 10px;
        background: var(--stu-accent-light);
        transition: all 0.15s ease;
        margin-top: 6px;
    }

    .student-metric-action:hover {
        background: var(--stu-accent);
        color: #FFFFFF !important;
        transform: translateX(2px);
    }

    /* ═══ 4. Траектория подготовки и слабые темы ═══════════════════ */
    .student-progress-card {
        background: var(--stu-card-bg);
        border: 1px solid var(--stu-card-border);
        border-radius: 20px;
        padding: 24px;
        margin-bottom: 24px;
    }

    .student-progress-header {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        margin-bottom: 16px;
    }

    .student-progress-title-wrap h2 {
        margin: 0;
        font-size: 1.15rem;
        font-weight: 800;
        color: var(--stu-text-main);
    }

    .student-progress-bar-outer {
        height: 12px;
        width: 100%;
        background: var(--stu-surface-subtle);
        border: 1px solid var(--stu-card-border);
        border-radius: 9999px;
        overflow: hidden;
        position: relative;
        margin-top: 8px;
    }

    .student-progress-bar-fill {
        height: 100%;
        border-radius: 9999px;
        background: linear-gradient(90deg, #7D39EB 0%, #10B981 100%);
        transition: width 0.8s cubic-bezier(0.4, 0, 0.2, 1);
        box-shadow: 0 0 12px rgba(125, 57, 235, 0.4);
    }

    .student-gaps-list {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-top: 14px;
    }

    .student-gap-pill {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 12px;
        border-radius: 10px;
        font-size: 12px;
        font-weight: 600;
        background: rgba(245, 158, 11, 0.1);
        color: #D97706;
        border: 1px solid rgba(245, 158, 11, 0.25);
    }

    .dark .student-gap-pill {
        background: rgba(245, 158, 11, 0.15);
        color: #FBBF24;
    }

    /* ═══ 5. Карточки уроков и репетиторов в стиле диалогов Telegram/Viber ══════ */
    .student-two-col-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 24px;
        margin-bottom: 24px;
    }

    @media (max-width: 900px) {
        .student-two-col-grid { grid-template-columns: 1fr; gap: 16px; }
    }

    .student-list-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 14px;
        padding: 14px 18px;
        border-radius: 18px;
        border: 1px solid var(--stu-card-border);
        background: var(--stu-card-bg);
        transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
        -webkit-backdrop-filter: blur(12px);
        backdrop-filter: blur(12px);
        position: relative;
    }

    .student-list-item:hover {
        border-color: rgba(125, 57, 235, 0.4);
        background: rgba(125, 57, 235, 0.03);
        transform: translateX(3px);
        box-shadow: 0 4px 16px -2px rgba(125, 57, 235, 0.1);
    }
    .dark .student-list-item:hover {
        background: rgba(125, 57, 235, 0.1);
        box-shadow: 0 4px 20px -2px rgba(0, 0, 0, 0.3);
    }

    .student-status-badge {
        font-size: 11px;
        font-weight: 800;
        padding: 3px 10px;
        border-radius: 9999px;
        display: inline-flex;
        align-items: center;
        gap: 4px;
        letter-spacing: 0.02em;
    }

    .student-status-badge--paid {
        background: rgba(16, 185, 129, 0.12);
        color: #059669;
        border: 1px solid rgba(16, 185, 129, 0.25);
    }
    .dark .student-status-badge--paid {
        background: rgba(16, 185, 129, 0.2);
        color: #34D399;
        border-color: rgba(16, 185, 129, 0.35);
    }

    .student-status-badge--unpaid {
        background: rgba(245, 158, 11, 0.12);
        color: #D97706;
        border: 1px solid rgba(245, 158, 11, 0.25);
    }
    .dark .student-status-badge--unpaid {
        background: rgba(245, 158, 11, 0.2);
        color: #FBBF24;
        border-color: rgba(245, 158, 11, 0.35);
    }

    /* ═══ Адаптивность и мобильный тюнинг ═════════════════════════ */
    @media (max-width: 640px) {
        .student-spotlight-inner {
            flex-direction: column;
            align-items: stretch;
        }
        .student-spotlight-cta {
            width: 100%;
            justify-content: center;
        }
        .student-hero-actions {
            width: 100%;
            overflow-x: auto;
            padding-bottom: 4px;
        }
        .student-chip-btn {
            flex-shrink: 0;
        }
    }
</style>
