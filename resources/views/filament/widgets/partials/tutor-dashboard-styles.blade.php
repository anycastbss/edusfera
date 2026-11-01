{{--
    Дизайн-система кабинета репетитора (UI_RULES.md / .agents/AGENTS.md):
    - один акцент #7D39EB; лайм #C6FF33 — только для живой кнопки «Войти в класс»;
    - вся раскладка на собственном CSS: сборка Tailwind панели не содержит
      произвольных утилит (grid-cols-3 и т.п.), полагаться на них нельзя;
    - сетка 8pt, тач-таргеты >= 44px на смартфоне, 40px от 640px;
    - dark mode через селектор .dark (переключатель Filament).
    Подключается один раз (@once) из верхнего виджета кабинета.
--}}

<style>
    /* ═══ Базовые примитивы ═══════════════════════════════════════ */

    .tutor-hero [x-cloak] { display: none !important; }

    .tutor-card {
        background: rgba(255, 255, 255, 0.90);
        border: 1px solid rgba(226, 232, 240, 0.85);
        border-radius: 22px;
        padding: 22px;
        box-shadow: 0 8px 28px -4px rgba(16, 10, 31, 0.05), inset 0 1px 1px rgba(255, 255, 255, 0.8);
        -webkit-backdrop-filter: blur(14px);
        backdrop-filter: blur(14px);
        transition: transform 0.2s cubic-bezier(0.16, 1, 0.3, 1), box-shadow 0.2s ease, border-color 0.2s ease;
    }
    .dark .tutor-card {
        background: rgba(17, 24, 39, 0.88);
        border-color: rgba(31, 41, 55, 0.85);
        box-shadow: 0 10px 32px -4px rgba(0, 0, 0, 0.45), inset 0 1px 1px rgba(255, 255, 255, 0.08);
    }

    .tutor-card-label {
        font-size: 11px; font-weight: 800; letter-spacing: .08em;
        text-transform: uppercase; color: #9CA3AF;
    }

    /* ═══ Hero-блок (MAYA + Telegram/Viber) ═══════════════════════════ */

    .tutor-hero { display: flex; flex-direction: column; gap: 16px; }

    .tutor-hero-top {
        display: flex; flex-wrap: wrap; align-items: baseline;
        justify-content: space-between; gap: 4px 24px;
    }

    .tutor-hero-title {
        margin: 0;
        font-size: clamp(1.5rem, 1.1rem + 1.8vw, 2.25rem);
        font-weight: 900; letter-spacing: -.02em; line-height: 1.1;
        color: #0C0A14;
    }
    .dark .tutor-hero-title { color: #fff; }

    .tutor-hero-date {
        font-size: 11px; font-weight: 700; text-transform: uppercase;
        letter-spacing: .14em; color: #9CA3AF;
    }

    .tutor-hero-card {
        margin-top: 4px; padding: 22px;
        border-radius: 22px;
        background: linear-gradient(135deg, rgba(125, 57, 235, 0.05) 0%, rgba(248, 250, 252, 0.85) 100%), rgba(255, 255, 255, 0.90);
        border: 1px solid rgba(125, 57, 235, 0.22);
        box-shadow: 0 8px 24px -4px rgba(125, 57, 235, 0.08), inset 0 1px 1px rgba(255, 255, 255, 0.8);
        -webkit-backdrop-filter: blur(16px);
        backdrop-filter: blur(16px);
    }
    .dark .tutor-hero-card {
        background: linear-gradient(135deg, rgba(125, 57, 235, 0.12) 0%, rgba(30, 41, 59, 0.6) 100%), rgba(17, 24, 39, 0.88);
        border-color: rgba(125, 57, 235, 0.35);
        box-shadow: 0 8px 28px -4px rgba(0, 0, 0, 0.35), inset 0 1px 1px rgba(255, 255, 255, 0.06);
    }

    .tutor-hero-card--live {
        background: linear-gradient(135deg, rgba(16, 185, 129, 0.12) 0%, rgba(125, 57, 235, 0.08) 100%), rgba(255, 255, 255, 0.95);
        border: 2px solid #C6FF33;
        box-shadow: 0 10px 32px rgba(198, 255, 51, 0.25), inset 0 1px 1px rgba(255, 255, 255, 0.8);
    }
    .dark .tutor-hero-card--live {
        background: linear-gradient(135deg, rgba(16, 185, 129, 0.16) 0%, rgba(125, 57, 235, 0.14) 100%), rgba(17, 24, 39, 0.92);
        border-color: rgba(198, 255, 51, .9);
        box-shadow: 0 12px 36px rgba(198, 255, 51, 0.2);
    }

    .tutor-hero-card--calm {
        background: var(--stu-surface-subtle, #F9FAFB);
        border: 1.5px dashed #E5E7EB;
        box-shadow: none;
    }
    .dark .tutor-hero-card--calm { background: rgba(31, 41, 55, .35); border-color: #374151; }

    .tutor-hero-status {
        display: flex; align-items: center; gap: 8px;
        font-size: 11px; font-weight: 800; text-transform: uppercase;
        letter-spacing: .1em; color: #6B7280;
    }
    .dark .tutor-hero-status { color: #9CA3AF; }

    .tutor-hero-dot {
        width: 9px; height: 9px; border-radius: 9999px; flex: none;
        background: #65C400;
        animation: tutor-dot-pulse 1.6s ease-in-out infinite;
    }
    @keyframes tutor-dot-pulse {
        0%, 100% { opacity: 1; transform: scale(1); }
        50% { opacity: .45; transform: scale(.7); }
    }

    .tutor-hero-info {
        margin-top: 12px;
        display: flex; flex-wrap: wrap; align-items: baseline;
        justify-content: space-between; gap: 4px 24px;
    }

    .tutor-hero-name {
        margin: 0; font-size: 1.125rem; font-weight: 800; line-height: 1.2;
        color: #0C0A14;
    }
    @media (min-width: 640px) { .tutor-hero-name { font-size: 1.25rem; } }
    .dark .tutor-hero-name { color: #fff; }

    .tutor-hero-sub { margin: 4px 0 0; font-size: .875rem; color: #6B7280; }
    .dark .tutor-hero-sub { color: #9CA3AF; }

    .tutor-hero-time {
        margin: 0; font-size: 1rem; font-weight: 700;
        font-variant-numeric: tabular-nums; color: #1F2937;
    }
    @media (min-width: 640px) { .tutor-hero-time { font-size: 1.125rem; } }
    .dark .tutor-hero-time { color: #E5E7EB; }

    .tutor-hero-text { margin: 8px 0 0; font-size: .875rem; line-height: 1.5; color: #6B7280; }
    .dark .tutor-hero-text { color: #9CA3AF; }

    /* ═══ Кнопки действий ═════════════════════════════════════════ */

    .tutor-cta-row {
        margin-top: 20px; display: flex; flex-direction: column; gap: 12px;
    }
    @media (min-width: 640px) { .tutor-cta-row { flex-direction: row; flex-wrap: wrap; } }

    .tutor-cta {
        display: inline-flex; align-items: center; justify-content: center; gap: 8px;
        min-height: 52px; padding: 0 22px;
        border-radius: 16px;
        font-size: 1rem; font-weight: 800; line-height: 1;
        text-decoration: none; white-space: nowrap;
        transition: transform .12s ease, background-color .2s ease, border-color .2s ease, color .2s ease;
        -webkit-tap-highlight-color: transparent;
    }
    @media (min-width: 640px) { .tutor-cta { min-height: 44px; font-size: .9375rem; border-radius: 14px; } }
    .tutor-cta:active { transform: scale(.97); }
    .tutor-cta svg { width: 20px; height: 20px; flex: none; }

    .tutor-cta--lime {
        background: #C6FF33; color: #0A0A0A;
        animation: tutor-pulse-glow 2.2s ease-out infinite;
    }
    .tutor-cta--lime:hover { background: #d4ff5e; }
    @keyframes tutor-pulse-glow {
        0%   { box-shadow: 0 0 0 0 rgba(198, 255, 51, .5); }
        70%  { box-shadow: 0 0 0 14px rgba(198, 255, 51, 0); }
        100% { box-shadow: 0 0 0 0 rgba(198, 255, 51, 0); }
    }

    .tutor-cta--primary { background: #7D39EB; color: #fff; }
    .tutor-cta--primary:hover { background: #6B2FD0; }

    .tutor-cta--ghost {
        background: transparent; color: #4B5563;
        border: 1.5px solid #E5E7EB;
    }
    .dark .tutor-cta--ghost { color: #D1D5DB; border-color: #374151; }
    .tutor-cta--ghost:hover { border-color: #7D39EB; color: #7D39EB; }
    .dark .tutor-cta--ghost:hover { color: #A78BFA; border-color: #A78BFA; }

    /* ═══ Строка «новые заявки» ═══════════════════════════════════ */

    .tutor-hero-alert {
        display: flex; align-items: center; gap: 10px;
        min-height: 52px; padding: 12px 16px;
        border-radius: 16px;
        background: rgba(125, 57, 235, .08);
        color: #4B5563; font-size: .9375rem; text-decoration: none;
        transition: background-color .2s ease;
        -webkit-tap-highlight-color: transparent;
    }
    .tutor-hero-alert:hover { background: rgba(125, 57, 235, .15); }
    .tutor-hero-alert svg { width: 20px; height: 20px; flex: none; color: #7D39EB; }
    .tutor-hero-alert .tutor-alert-note { color: #6B7280; }
    .dark .tutor-hero-alert { background: rgba(125, 57, 235, .16); color: #E5E7EB; }
    .dark .tutor-hero-alert .tutor-alert-note { color: #9CA3AF; }
    .tutor-hero-alert .tutor-alert-chevron { margin-left: auto; width: 16px; height: 16px; color: #9CA3AF; }

    /* ═══ Subscription Pill in Hero ═══════════════════════════════ */
    .tutor-hero-sub-pill {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 5px 12px;
        border-radius: 9999px;
        font-size: 11px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: .06em;
        line-height: 1;
        text-decoration: none;
        transition: opacity .15s ease, transform .12s ease;
    }
    .tutor-hero-sub-pill:hover { opacity: .88; transform: translateY(-1px); }
    .tutor-hero-sub-pill--trial {
        background: rgba(5, 150, 105, .12);
        color: #059669;
        border: 1px solid rgba(5, 150, 105, .25);
    }
    .dark .tutor-hero-sub-pill--trial {
        background: rgba(5, 150, 105, .22);
        color: #34D399;
        border-color: rgba(5, 150, 105, .4);
    }
    .tutor-hero-sub-pill--active {
        background: rgba(125, 57, 235, .1);
        color: #7D39EB;
        border: 1px solid rgba(125, 57, 235, .25);
    }
    .dark .tutor-hero-sub-pill--active {
        background: rgba(125, 57, 235, .2);
        color: #A78BFA;
        border-color: rgba(125, 57, 235, .4);
    }
    .tutor-hero-sub-pill--grace {
        background: rgba(220, 38, 38, .12);
        color: #DC2626;
        border: 1px solid rgba(220, 38, 38, .3);
        animation: tutor-dot-pulse 1.6s ease-in-out infinite;
    }
    .dark .tutor-hero-sub-pill--grace {
        background: rgba(220, 38, 38, .2);
        color: #F87171;
    }

    /* ═══ Privacy Status Bar (ПРИВАТНЫЙ / ОТКРЫТАЯ ВИТРИНА) ══════════════ */
    .tutor-privacy-status-bar {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        padding: 12px 18px;
        margin-bottom: 12px;
        background: rgba(255, 255, 255, 0.85);
        border: 1.5px solid rgba(16, 185, 129, 0.35);
        border-radius: 16px;
        box-shadow: 0 2px 10px rgba(16, 185, 129, 0.05);
        backdrop-filter: blur(14px);
        -webkit-backdrop-filter: blur(14px);
    }
    .dark .tutor-privacy-status-bar {
        background: rgba(17, 24, 39, 0.75);
        border-color: rgba(16, 185, 129, 0.3);
        box-shadow: 0 4px 14px rgba(0, 0, 0, 0.25);
    }
    .tutor-privacy-status-content {
        display: flex;
        align-items: center;
        gap: 10px;
        min-width: 0;
    }
    .tutor-privacy-badge {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        font-size: 0.95rem;
        line-height: 1.4;
    }
    .tutor-privacy-dot {
        font-size: 1.1rem;
        line-height: 1;
    }
    .tutor-privacy-text strong {
        font-weight: 700;
        color: #0F172A;
    }
    .dark .tutor-privacy-text strong {
        color: #FFFFFF;
    }
    .tutor-privacy-hint {
        color: #64748B;
        font-size: 0.88rem;
    }
    .dark .tutor-privacy-hint {
        color: #94A3B8;
    }
    .tutor-privacy-toggle-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 6px 14px;
        font-size: 0.85rem;
        font-weight: 700;
        color: #7D39EB;
        background: rgba(125, 57, 235, 0.08);
        border: 1px solid rgba(125, 57, 235, 0.25);
        border-radius: 10px;
        cursor: pointer;
        transition: all 0.15s ease;
    }
    .dark .tutor-privacy-toggle-btn {
        color: #C6FF33;
        background: rgba(198, 255, 51, 0.1);
        border-color: rgba(198, 255, 51, 0.25);
    }
    .tutor-privacy-toggle-btn:hover {
        background: rgba(125, 57, 235, 0.16);
        transform: translateY(-1px);
    }
    .dark .tutor-privacy-toggle-btn:hover {
        background: rgba(198, 255, 51, 0.2);
    }

    /* ═══ Methodical Council Elite Academic Box (ЗОЛОТАЯ СОТНЯ / 30 ПЕДАГОГОВ) ═══ */
    .tutor-council-card {
        padding: 16px 20px;
        background: linear-gradient(135deg, rgba(245, 158, 11, 0.08) 0%, rgba(217, 119, 6, 0.03) 100%), rgba(255, 255, 255, 0.95);
        border: 1.5px solid rgba(245, 158, 11, 0.4);
        border-radius: 18px;
        box-shadow: 0 4px 18px rgba(245, 158, 11, 0.08), inset 0 1px 1px rgba(255, 255, 255, 0.8);
        margin-bottom: 8px;
    }
    .dark .tutor-council-card {
        background: linear-gradient(135deg, rgba(245, 158, 11, 0.15) 0%, rgba(217, 119, 6, 0.06) 100%), rgba(17, 24, 39, 0.9);
        border-color: rgba(245, 158, 11, 0.5);
        box-shadow: 0 6px 22px rgba(0, 0, 0, 0.35);
    }
    .tutor-council-card-content {
        display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 12px;
    }
    .tutor-council-card-badge {
        display: flex; align-items: center; gap: 12px;
    }
    .tutor-council-medal {
        font-size: 26px; line-height: 1; filter: drop-shadow(0 2px 6px rgba(245, 158, 11, 0.4));
    }
    .tutor-council-title {
        font-size: 14px; font-weight: 800; color: #92400E; letter-spacing: -0.01em;
    }
    .dark .tutor-council-title { color: #FCD34D; }
    .tutor-council-sub {
        font-size: 12px; color: #B45309; margin-top: 1px;
    }
    .dark .tutor-council-sub { color: #FBBF24; }
    .tutor-council-cert-btn {
        display: inline-flex; align-items: center; gap: 6px;
        height: 38px; padding: 0 14px; border-radius: 10px;
        font-size: 12px; font-weight: 700; color: #fff;
        background: linear-gradient(135deg, #D97706 0%, #B45309 100%);
        border: none; cursor: pointer; box-shadow: 0 3px 12px rgba(217, 119, 6, 0.3);
        transition: all 0.15s ease;
    }
    .tutor-council-cert-btn:hover {
        transform: translateY(-1px); box-shadow: 0 5px 16px rgba(217, 119, 6, 0.4);
    }

    /* Invite box for tutors not yet in council */
    .tutor-council-invite-card {
        padding: 18px 22px;
        background: linear-gradient(135deg, rgba(125, 57, 235, 0.05) 0%, rgba(245, 158, 11, 0.06) 100%), rgba(255, 255, 255, 0.95);
        border: 1.5px solid rgba(217, 119, 6, 0.3);
        border-radius: 18px;
        box-shadow: 0 4px 18px rgba(125, 57, 235, 0.06);
        margin-bottom: 8px;
    }
    .dark .tutor-council-invite-card {
        background: linear-gradient(135deg, rgba(125, 57, 235, 0.12) 0%, rgba(245, 158, 11, 0.1) 100%), rgba(17, 24, 39, 0.9);
        border-color: rgba(245, 158, 11, 0.4);
    }
    .tutor-council-invite-badge {
        display: inline-flex; align-items: center; gap: 8px; flex-wrap: wrap;
        font-size: 11px; font-weight: 800; text-transform: uppercase;
        letter-spacing: .08em; color: #B45309; margin-bottom: 8px;
    }
    .dark .tutor-council-invite-badge { color: #FCD34D; }
    .tutor-council-quota-pill {
        padding: 2px 8px; border-radius: 999px;
        background: #FEF3C7; color: #92400E; font-weight: 800;
        border: 1px solid rgba(217, 119, 6, 0.3); font-size: 10px;
    }
    .dark .tutor-council-quota-pill {
        background: rgba(217, 119, 6, 0.25); color: #FDE68A;
    }
    .tutor-council-invite-title {
        font-size: 16px; font-weight: 900; color: #0C0A14; margin: 0 0 6px;
    }
    .dark .tutor-council-invite-title { color: #fff; }
    .tutor-council-invite-desc {
        font-size: 13px; line-height: 1.55; color: #4B5563; margin: 0 0 14px; max-width: 820px;
    }
    .dark .tutor-council-invite-desc { color: #D1D5DB; }
    .tutor-council-invite-actions {
        display: flex; flex-wrap: wrap; align-items: center; gap: 12px;
    }
    .tutor-council-accept-btn {
        display: inline-flex; align-items: center; gap: 8px;
        height: 42px; padding: 0 18px; border-radius: 12px;
        font-size: 13px; font-weight: 800; color: #fff;
        background: linear-gradient(135deg, #7D39EB 0%, #6D28D9 100%);
        border: none; cursor: pointer;
        box-shadow: 0 4px 16px rgba(125, 57, 235, 0.35);
        transition: all 0.15s ease;
    }
    .tutor-council-accept-btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 6px 20px rgba(125, 57, 235, 0.45);
        background: linear-gradient(135deg, #8B5CF6 0%, #7D39EB 100%);
    }
    .tutor-council-guarantee-note {
        font-size: 12px; font-weight: 600; color: #059669;
    }
    .dark .tutor-council-guarantee-note { color: #34D399; }

    /* Modal Certificate for Portfolio & Category */
    .tutor-cert-modal-backdrop {
        position: fixed; inset: 0; z-index: 99999;
        background: rgba(15, 23, 42, 0.75);
        backdrop-filter: blur(8px);
        display: flex; align-items: center; justify-content: center;
        padding: 20px;
    }
    .tutor-cert-modal {
        background: #fff; border-radius: 20px; max-width: 800px; width: 100%;
        max-height: 90vh; overflow-y: auto; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.5);
    }
    .tutor-cert-modal-header {
        display: flex; align-items: center; justify-content: space-between;
        padding: 16px 24px; border-bottom: 1px solid #E2E8F0; background: #F8FAFC;
        border-radius: 20px 20px 0 0;
    }
    .tutor-cert-print-btn {
        display: inline-flex; align-items: center; gap: 8px;
        height: 38px; padding: 0 18px; border-radius: 10px; font-size: 13px; font-weight: 800;
        background: linear-gradient(135deg, #10B981, #059669); color: #fff; border: none; cursor: pointer;
        box-shadow: 0 2px 8px rgba(16, 185, 129, 0.25);
        transition: transform 0.15s ease, box-shadow 0.15s ease;
    }
    .tutor-cert-print-btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(16, 185, 129, 0.35);
    }
    .tutor-cert-close-btn {
        width: 38px; height: 38px; border-radius: 10px; border: 1px solid #CBD5E1;
        background: #fff; font-size: 16px; cursor: pointer; color: #64748B;
        display: inline-flex; align-items: center; justify-content: center;
        transition: background 0.15s ease, color 0.15s ease;
    }
    .tutor-cert-close-btn:hover {
        background: #F1F5F9; color: #0F172A;
    }

    /* ═══ ВЫСОКОСОРТНЫЙ АКАДЕМИЧЕСКИЙ БЛАНК СВИДЕТЕЛЬСТВА А4 (LUXURY GOLD) ═══ */
    .tutor-certificate-sheet {
        position: relative;
        padding: 32px;
        background: #FFFDF9;
        color: #1E293B;
        box-sizing: border-box;
        font-family: 'Cormorant Garamond', 'Times New Roman', serif;
        overflow: hidden;
    }

    /* Внешняя золотая защитная рамка */
    .tutor-cert-outer-frame {
        position: relative;
        border: 2px solid #C59B27;
        padding: 6px;
        background: linear-gradient(135deg, #FFFDF9 0%, #FAF6EE 50%, #FFFDF9 100%);
        box-shadow: inset 0 0 0 1px #8C6D1F, 0 10px 30px -5px rgba(140, 109, 31, 0.15);
    }

    /* Внутренняя гильоширная рамка с микро-узором */
    .tutor-cert-inner-frame {
        position: relative;
        border: 1px solid rgba(197, 155, 39, 0.5);
        padding: 30px 36px 28px;
        text-align: center;
        background: #FFFFFF;
        box-shadow: inset 0 0 0 3px rgba(255, 253, 249, 0.9), inset 0 0 0 4px rgba(197, 155, 39, 0.25);
    }

    /* Угловые виньетки / филигранные орнаменты */
    .tutor-cert-corner {
        position: absolute;
        width: 44px;
        height: 44px;
        pointer-events: none;
        z-index: 2;
    }
    .tutor-cert-corner--tl { top: 6px; left: 6px; }
    .tutor-cert-corner--tr { top: 6px; right: 6px; transform: scaleX(-1); }
    .tutor-cert-corner--bl { bottom: 6px; left: 6px; transform: scaleY(-1); }
    .tutor-cert-corner--br { bottom: 6px; right: 6px; transform: scale(-1); }

    /* Центральный полупрозрачный защитный водяной знак */
    .tutor-cert-watermark {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        width: 320px;
        height: 320px;
        opacity: 0.04;
        pointer-events: none;
        z-index: 1;
    }

    /* Шапка гербовая */
    .tutor-cert-header {
        position: relative;
        z-index: 2;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 4px;
    }
    .tutor-cert-crest-wrap {
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 2px;
    }
    .tutor-cert-state-line {
        font-family: 'Montserrat', sans-serif;
        font-size: 11px;
        font-weight: 800;
        letter-spacing: 0.26em;
        text-transform: uppercase;
        color: #78350F;
    }
    .tutor-cert-committee-line {
        font-family: 'Cinzel', serif;
        font-size: 13px;
        font-weight: 800;
        letter-spacing: 0.12em;
        text-transform: uppercase;
        color: #92400E;
        margin-top: 2px;
    }
    .tutor-cert-divider-ornament {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 12px;
        width: 100%;
        max-width: 460px;
        margin: 8px auto 14px;
    }
    .tutor-cert-divider-line {
        flex: 1;
        height: 1px;
        background: linear-gradient(90deg, transparent, #C59B27, transparent);
    }
    .tutor-cert-divider-star {
        color: #C59B27;
        font-size: 11px;
    }

    /* Заголовок документа */
    .tutor-cert-main-title {
        font-family: 'Cormorant Garamond', serif;
        font-size: 38px;
        font-weight: 700;
        letter-spacing: 0.14em;
        text-transform: uppercase;
        color: #0F172A;
        margin: 0;
        line-height: 1.1;
        text-shadow: 0 1px 0 rgba(255, 255, 255, 0.8);
    }
    .tutor-cert-main-subtitle {
        font-family: 'Montserrat', sans-serif;
        font-size: 11px;
        font-weight: 800;
        letter-spacing: 0.22em;
        text-transform: uppercase;
        color: #B45309;
        margin-top: 6px;
    }

    /* Регистрационные метаданные */
    .tutor-cert-meta-strip {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 16px;
        margin: 14px auto 18px;
        padding: 4px 18px;
        border-radius: 9999px;
        background: rgba(197, 155, 39, 0.08);
        border: 1px solid rgba(197, 155, 39, 0.25);
        font-family: 'Montserrat', sans-serif;
        font-size: 11px;
        font-weight: 600;
        color: #475569;
    }
    .tutor-cert-meta-item b {
        color: #0F172A;
        font-weight: 800;
        letter-spacing: 0.04em;
    }

    /* Тело свидетельства */
    .tutor-cert-content-body {
        position: relative;
        z-index: 2;
        max-width: 680px;
        margin: 0 auto;
        font-size: 15px;
        line-height: 1.65;
        color: #334155;
        text-align: center;
    }
    .tutor-cert-intro-phrase {
        font-size: 15px;
        color: #475569;
        font-style: italic;
    }
    .tutor-cert-recipient-name {
        font-family: 'Cormorant Garamond', serif;
        font-size: 32px;
        font-weight: 700;
        color: #0F172A;
        margin: 8px 0 6px;
        letter-spacing: 0.02em;
        line-height: 1.2;
    }
    .tutor-cert-recipient-underline {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        margin: 0 auto 12px;
        max-width: 280px;
    }
    .tutor-cert-recipient-line {
        flex: 1;
        height: 1px;
        background: #C59B27;
    }
    .tutor-cert-recipient-diamond {
        width: 5px;
        height: 5px;
        background: #C59B27;
        transform: rotate(45deg);
    }

    .tutor-cert-status-callout {
        margin: 14px auto 16px;
        padding: 10px 24px;
        border-radius: 8px;
        background: linear-gradient(135deg, rgba(254, 243, 199, 0.5) 0%, rgba(253, 230, 138, 0.3) 100%);
        border-top: 1px solid rgba(217, 119, 6, 0.35);
        border-bottom: 1px solid rgba(217, 119, 6, 0.35);
        display: inline-block;
    }
    .tutor-cert-status-callout-text {
        font-family: 'Montserrat', sans-serif;
        font-size: 13.5px;
        font-weight: 800;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        color: #92400E;
    }

    .tutor-cert-legal-desc {
        font-size: 13.5px;
        line-height: 1.6;
        color: #475569;
        margin: 0 auto 12px;
        text-align: justify;
        text-align-last: center;
    }
    .tutor-cert-portfolio-notice {
        font-family: 'Montserrat', sans-serif;
        font-size: 11px;
        font-weight: 700;
        letter-spacing: 0.02em;
        color: #1E3A8A;
        background: rgba(239, 246, 255, 0.8);
        border: 1px dashed rgba(59, 130, 246, 0.35);
        padding: 6px 14px;
        border-radius: 6px;
        display: inline-block;
        margin-top: 4px;
    }

    /* Подвальная секция с подписями, золотой медалью и QR */
    .tutor-cert-credentials-grid {
        position: relative;
        z-index: 2;
        display: grid;
        grid-template-columns: 1fr auto 1fr;
        align-items: center;
        gap: 20px;
        margin-top: 32px;
        padding: 16px 12px 6px;
        border-top: 1px solid rgba(197, 155, 39, 0.25);
    }

    /* Левая колонка: подпись председателя */
    .tutor-cert-sign-col {
        text-align: left;
    }
    .tutor-cert-sign-role {
        font-family: 'Montserrat', sans-serif;
        font-size: 10px;
        font-weight: 700;
        letter-spacing: 0.06em;
        text-transform: uppercase;
        color: #64748B;
        line-height: 1.3;
    }
    .tutor-cert-facsimile-box {
        position: relative;
        height: 48px;
        margin: 2px 0 2px;
        display: flex;
        align-items: center;
    }
    .tutor-cert-sign-printed-name {
        font-family: 'Cormorant Garamond', serif;
        font-size: 17px;
        font-weight: 700;
        color: #0F172A;
        letter-spacing: 0.03em;
        border-top: 1px solid #CBD5E1;
        padding-top: 3px;
        display: inline-block;
        min-width: 170px;
    }

    /* Центральная колонка: 3D рельефная золотая медаль с лентой */
    .tutor-cert-seal-col {
        display: flex;
        flex-direction: column;
        align-items: center;
        position: relative;
    }
    .tutor-cert-gold-medal {
        position: relative;
        width: 102px;
        height: 102px;
        border-radius: 50%;
        background: radial-gradient(circle at 35% 35%, #FFF6CC 0%, #E6C25B 45%, #C59B27 75%, #8C6D1F 100%);
        box-shadow: 0 8px 20px -4px rgba(140, 109, 31, 0.45), inset 0 2px 4px rgba(255, 255, 255, 0.9), inset 0 -2px 4px rgba(0, 0, 0, 0.35);
        display: flex;
        align-items: center;
        justify-content: center;
        border: 2px solid #8C6D1F;
        z-index: 2;
    }
    .tutor-cert-gold-medal-inner {
        width: 86px;
        height: 86px;
        border-radius: 50%;
        border: 1px dashed rgba(120, 53, 15, 0.6);
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        text-align: center;
        padding: 4px;
        box-sizing: border-box;
        box-shadow: inset 0 1px 2px rgba(255, 255, 255, 0.6);
    }
    .tutor-cert-medal-star {
        font-size: 14px;
        color: #78350F;
        line-height: 1;
        text-shadow: 0 1px 1px rgba(255, 255, 255, 0.6);
    }
    .tutor-cert-medal-txt-top {
        font-family: 'Montserrat', sans-serif;
        font-size: 7px;
        font-weight: 800;
        letter-spacing: 0.1em;
        text-transform: uppercase;
        color: #78350F;
        margin-top: 1px;
    }
    .tutor-cert-medal-txt-main {
        font-family: 'Cinzel', serif;
        font-size: 8.5px;
        font-weight: 900;
        letter-spacing: 0.12em;
        color: #451A03;
        margin: 1px 0;
    }
    .tutor-cert-medal-txt-sub {
        font-family: 'Montserrat', sans-serif;
        font-size: 6.5px;
        font-weight: 800;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        color: #78350F;
    }

    /* Ленты медали */
    .tutor-cert-ribbons {
        position: absolute;
        bottom: -18px;
        display: flex;
        gap: 6px;
        z-index: 1;
    }
    .tutor-cert-ribbon-tail {
        width: 18px;
        height: 30px;
        background: linear-gradient(180deg, #DC2626 0%, #991B1B 100%);
        clip-path: polygon(0 0, 100% 0, 100% 100%, 50% 80%, 0 100%);
        box-shadow: 0 3px 6px rgba(0, 0, 0, 0.2);
    }
    .tutor-cert-ribbon-tail--green {
        background: linear-gradient(180deg, #16A34A 0%, #166534 100%);
    }

    /* Правая колонка: дата и место выдачи */
    .tutor-cert-verify-col {
        text-align: right;
        display: flex;
        flex-direction: column;
        align-items: flex-end;
        justify-content: flex-end;
        padding-bottom: 4px;
    }

    .tutor-cert-issue-info {
        font-family: 'Montserrat', sans-serif;
        font-size: 10px;
        font-weight: 600;
        color: #475569;
        line-height: 1.5;
    }

    /* Нижняя защитная лента микротекста */
    .tutor-cert-bottom-security-bar {
        position: relative;
        z-index: 2;
        margin-top: 16px;
        padding-top: 8px;
        border-top: 1px solid rgba(197, 155, 39, 0.2);
        display: flex;
        align-items: center;
        justify-content: space-between;
        font-family: 'Montserrat', sans-serif;
        font-size: 8px;
        font-weight: 700;
        letter-spacing: 0.16em;
        text-transform: uppercase;
        color: #94A3B8;
    }

    @page {
        size: A4 portrait;
        margin: 0;
    }
    @media print {
        body, html {
            margin: 0 !important;
            padding: 0 !important;
            background: #fff !important;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }
        body * {
            visibility: hidden !important;
        }
        .tutor-cert-modal-backdrop,
        .tutor-cert-modal {
            position: static !important;
            background: transparent !important;
            padding: 0 !important;
            margin: 0 !important;
            box-shadow: none !important;
            border: none !important;
            display: block !important;
            max-width: 100% !important;
            width: 100% !important;
        }
        .tutor-certificate-sheet,
        .tutor-certificate-sheet * {
            visibility: visible !important;
        }
        .tutor-certificate-sheet {
            position: absolute !important;
            left: 0 !important;
            top: 0 !important;
            width: 210mm !important;
            min-height: 297mm !important;
            margin: 0 auto !important;
            padding: 10mm !important;
            box-sizing: border-box !important;
            box-shadow: none !important;
            background: #fff !important;
        }
        .no-print {
            display: none !important;
        }
    }

    /* ═══ Personal Booking Link Bar (Telegram Invite Link style) ══════════ */
    .tutor-hero-booking-bar {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        padding: 14px 20px;
        background: var(--stu-surface-subtle, #F9FAFB);
        border: 1px solid rgba(125, 57, 235, 0.2);
        border-radius: 18px;
        box-shadow: 0 2px 10px rgba(125, 57, 235, 0.04), inset 0 1px 1px rgba(255, 255, 255, 0.8);
    }
    .dark .tutor-hero-booking-bar {
        background: rgba(17, 24, 39, 0.8);
        border-color: rgba(125, 57, 235, 0.3);
        box-shadow: 0 4px 14px rgba(0, 0, 0, 0.25), inset 0 1px 1px rgba(255, 255, 255, 0.05);
    }
    .tutor-hero-booking-left {
        display: flex;
        align-items: center;
        gap: 12px;
        min-width: 0;
    }
    .tutor-hero-booking-icon {
        width: 20px;
        height: 20px;
        color: #7D39EB;
        flex-shrink: 0;
    }
    .dark .tutor-hero-booking-icon { color: #A78BFA; }
    .tutor-hero-booking-label {
        font-size: 11px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: .08em;
        color: #9CA3AF;
        margin-right: 6px;
    }
    .tutor-hero-booking-url {
        font-size: .875rem;
        font-weight: 700;
        color: #1F2937;
        text-decoration: none;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .dark .tutor-hero-booking-url { color: #E5E7EB; }
    .tutor-hero-booking-url:hover { text-decoration: underline; color: #7D39EB; }

    .tutor-hero-copy-btn {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        height: 38px;
        padding: 0 16px;
        border-radius: 12px;
        font-size: .8125rem;
        font-weight: 800;
        border: 1px solid rgba(125, 57, 235, 0.25);
        background: #FFFFFF;
        color: #7D39EB;
        cursor: pointer;
        transition: all .2s cubic-bezier(0.16, 1, 0.3, 1);
        user-select: none;
        box-shadow: 0 2px 6px rgba(125, 57, 235, 0.08);
    }
    .dark .tutor-hero-copy-btn {
        background: rgba(125, 57, 235, 0.15);
        border-color: rgba(125, 57, 235, 0.4);
        color: #A78BFA;
    }
    .tutor-hero-copy-btn:hover {
        background: #7D39EB;
        color: #FFFFFF !important;
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(125, 57, 235, 0.25);
    }
    .tutor-hero-copy-btn--copied {
        border-color: #059669 !important;
        background: #10B981 !important;
        color: #FFFFFF !important;
    }

    /* Messenger Share Buttons (1-click for 50+) */
    .tutor-hero-messenger-mini {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        height: 38px;
        padding: 0 13px;
        border-radius: 12px;
        font-size: 12px;
        font-weight: 800;
        text-decoration: none !important;
        transition: all .15s ease;
        user-select: none;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.08);
    }
    .tutor-hero-messenger-mini--viber {
        background: #7360F2;
        color: #FFFFFF !important;
    }
    .tutor-hero-messenger-mini--viber:hover {
        background: #5E4AE3;
        transform: translateY(-1px);
    }
    .tutor-hero-messenger-mini--telegram {
        background: #0284C7;
        color: #FFFFFF !important;
    }
    .tutor-hero-messenger-mini--telegram:hover {
        background: #0369A1;
        transform: translateY(-1px);
    }
    .tutor-hero-messenger-mini:active {
        transform: scale(0.96);
    }

    /* ═══ Subscription Alerts in Hero ═════════════════════════════ */
    .tutor-hero-sub-alert {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        padding: 12px 18px;
        border-radius: 16px;
        font-size: .875rem;
        line-height: 1.4;
    }
    .tutor-hero-sub-alert--grace {
        background: #FEF2F2;
        border: 1.5px solid #FCA5A5;
        color: #991B1B;
    }
    .dark .tutor-hero-sub-alert--grace {
        background: rgba(220, 38, 38, .12);
        border-color: rgba(220, 38, 38, .35);
        color: #FCA5A5;
    }
    .tutor-hero-sub-alert--expiring {
        background: #FFFBEB;
        border: 1.5px solid #FCD34D;
        color: #92400E;
    }
    .dark .tutor-hero-sub-alert--expiring {
        background: rgba(245, 158, 11, .12);
        border-color: rgba(245, 158, 11, .35);
        color: #FCD34D;
    }
    .tutor-hero-sub-alert-content {
        display: flex;
        align-items: center;
        gap: 10px;
        flex: 1;
        min-width: 240px;
    }
    .tutor-hero-sub-alert-icon {
        width: 20px;
        height: 20px;
        flex-shrink: 0;
    }
    .tutor-hero-sub-alert-btn {
        display: inline-flex;
        align-items: center;
        height: 34px;
        padding: 0 14px;
        border-radius: 10px;
        font-size: .8125rem;
        font-weight: 800;
        text-decoration: none;
        background: #1F2937;
        color: #FFFFFF;
        transition: opacity .15s ease;
        white-space: nowrap;
    }
    .dark .tutor-hero-sub-alert-btn {
        background: #FFFFFF;
        color: #111827;
    }
    .tutor-hero-sub-alert-btn--danger {
        background: #DC2626 !important;
        color: #FFFFFF !important;
    }
    .tutor-hero-sub-alert-btn:hover { opacity: .9; }

    /* ═══ Финансовая лента ════════════════════════════════════════ */

    .tutor-finance { display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; }
    @media (max-width: 639px) {
        .tutor-finance {
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }
        .tutor-finance .tutor-stat:first-child {
            grid-column: span 2;
            min-height: 104px;
            padding: 16px 18px;
        }
        .tutor-finance .tutor-stat:first-child .tutor-stat-value {
            font-size: 1.75rem;
        }
    }
    @media (min-width: 640px) { .tutor-finance { gap: 16px; } }

    .tutor-stat {
        display: flex; flex-direction: column; justify-content: space-between; gap: 10px;
        min-height: 100px; padding: 16px 18px;
        background: rgba(255, 255, 255, 0.90);
        border: 1px solid rgba(226, 232, 240, 0.85);
        border-radius: 18px;
        text-decoration: none;
        transition: all .25s cubic-bezier(0.16, 1, 0.3, 1);
        -webkit-tap-highlight-color: transparent;
        -webkit-backdrop-filter: blur(12px);
        backdrop-filter: blur(12px);
        box-shadow: 0 4px 16px -2px rgba(16, 10, 31, 0.04), inset 0 1px 1px rgba(255, 255, 255, 0.8);
    }
    @media (max-width: 639px) {
        .tutor-stat {
            min-height: 100px;
            padding: 14px 16px;
            border-radius: 16px;
        }
        .tutor-stat-label {
            font-size: 11px;
            letter-spacing: .08em;
        }
        .tutor-stat-value {
            font-size: 1.45rem;
        }
    }
    @media (min-width: 640px) { .tutor-stat { padding: 18px 20px; border-radius: 20px; } }
    .dark .tutor-stat {
        background: rgba(17, 24, 39, 0.88);
        border-color: rgba(31, 41, 55, 0.85);
        box-shadow: 0 6px 20px -2px rgba(0, 0, 0, 0.35), inset 0 1px 1px rgba(255, 255, 255, 0.06);
    }
    .tutor-stat:hover {
        border-color: rgba(125, 57, 235, 0.45);
        transform: translateY(-2px);
        box-shadow: 0 10px 24px -4px rgba(125, 57, 235, 0.12);
    }
    .tutor-stat:active { transform: scale(.98); }
    .tutor-stat--accent {
        border-color: rgba(125, 57, 235, 0.4);
        background: linear-gradient(135deg, rgba(125, 57, 235, 0.04) 0%, rgba(255, 255, 255, 0.95) 100%);
    }
    .dark .tutor-stat--accent {
        background: linear-gradient(135deg, rgba(125, 57, 235, 0.15) 0%, rgba(17, 24, 39, 0.95) 100%);
        border-color: rgba(125, 57, 235, 0.5);
    }

    .tutor-stat-label {
        font-size: 11px; font-weight: 800; letter-spacing: .08em;
        text-transform: uppercase; color: #9CA3AF; line-height: 1.25;
    }

    .tutor-stat-value {
        display: block;
        font-size: 1.25rem; font-weight: 900; letter-spacing: -.02em;
        font-variant-numeric: tabular-nums; line-height: 1.1;
        color: #0C0A14;
    }
    @media (min-width: 640px) { .tutor-stat-value { font-size: 1.65rem; } }
    .dark .tutor-stat-value { color: #fff; }
    .tutor-stat--accent .tutor-stat-value { color: #7D39EB; }
    .dark .tutor-stat--accent .tutor-stat-value { color: #A78BFA; }

    .tutor-stat-hint { display: block; margin-top: 4px; font-size: 11px; font-weight: 600; color: #9CA3AF; }
    @media (min-width: 640px) { .tutor-stat-hint { font-size: 12px; } }

    /* ═══ Центр действий (Telegram dialog notification) ══════════════ */

    .tutor-actions { display: grid; grid-template-columns: 1fr; gap: 16px; }
    @media (min-width: 1024px) { .tutor-actions { grid-template-columns: 3fr 2fr; gap: 24px; } }

    .tutor-card-head {
        display: flex; flex-wrap: wrap; align-items: center;
        justify-content: space-between; gap: 8px 12px;
    }

    .tutor-request { margin-top: 16px; display: flex; flex-direction: column; gap: 16px; flex: 1; }
    .tutor-request-row {
        display: flex; flex-wrap: wrap; align-items: baseline;
        justify-content: space-between; gap: 4px 16px;
    }
    .tutor-request-name {
        margin: 0; font-size: 1.05rem; font-weight: 800; line-height: 1.25; color: #0C0A14;
    }
    @media (min-width: 640px) { .tutor-request-name { font-size: 1.15rem; } }
    .dark .tutor-request-name { color: #fff; }
    .tutor-request-time {
        margin: 0; font-size: .875rem; font-weight: 700;
        font-variant-numeric: tabular-nums; color: #7D39EB;
        background: rgba(125, 57, 235, 0.08);
        padding: 2px 8px; border-radius: 6px;
    }
    .dark .tutor-request-time { color: #A78BFA; background: rgba(125, 57, 235, 0.2); }
    .tutor-request-note { margin: 0; font-size: .875rem; color: #6B7280; }
    .dark .tutor-request-note { color: #9CA3AF; }

    .tutor-empty {
        margin-top: 16px; flex: 1;
        display: flex; flex-direction: column; align-items: flex-start;
        justify-content: center; gap: 8px;
        border: 1.5px dashed #E5E7EB; border-radius: 18px; padding: 22px;
    }
    .dark .tutor-empty { border-color: #374151; }
    .tutor-empty-title { margin: 0; font-size: 1rem; font-weight: 800; color: #0C0A14; }
    .dark .tutor-empty-title { color: #fff; }
    .tutor-empty-text { margin: 0; font-size: .875rem; line-height: 1.5; color: #6B7280; }
    .dark .tutor-empty-text { color: #9CA3AF; }

    /* ═══ Плитки быстрых действий (Telegram/Viber 4-tile Feature Grid) ═══ */

    .tutor-tiles { margin-top: 16px; display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
    @media (min-width: 640px) { .tutor-tiles { gap: 14px; } }

    .tutor-tile {
        position: relative;
        display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 10px;
        min-height: 100px; padding: 18px 12px;
        border-radius: 20px;
        background: var(--stu-surface-subtle, #F9FAFB);
        border: 1px solid rgba(226, 232, 240, 0.85);
        text-decoration: none;
        transition: all .2s cubic-bezier(0.16, 1, 0.3, 1);
        -webkit-tap-highlight-color: transparent;
        box-shadow: 0 2px 8px rgba(16, 10, 31, 0.02);
    }
    @media (min-width: 640px) { .tutor-tile { min-height: 92px; padding: 16px 10px; border-radius: 18px; } }
    .dark .tutor-tile { background: rgba(17, 24, 39, 0.85); border-color: rgba(31, 41, 55, 0.85); }
    .tutor-tile:hover {
        border-color: rgba(125, 57, 235, 0.4);
        transform: translateY(-2px);
        box-shadow: 0 8px 20px -2px rgba(125, 57, 235, 0.12);
    }
    .tutor-tile:active { transform: scale(.97); }
    .tutor-tile svg { width: 28px; height: 28px; color: #7D39EB; transition: transform .2s ease; }
    .tutor-tile:hover svg { transform: scale(1.08); }
    @media (min-width: 640px) { .tutor-tile svg { width: 26px; height: 26px; } }
    .tutor-tile span { font-size: .875rem; font-weight: 800; color: #1F2937; }
    .dark .tutor-tile span { color: #F3F4F6; }

    .tutor-tile-badge {
        position: absolute; top: 10px; right: 10px;
        min-width: 22px; height: 22px; padding: 0 6px;
        display: inline-flex; align-items: center; justify-content: center;
        border-radius: 9999px;
        background: #EF4444; color: #fff;
        font-size: 11px; font-weight: 900; line-height: 1;
        box-shadow: 0 2px 8px rgba(239, 68, 68, 0.4);
    }

    /* ═══ Прогресс-бары ═══════════════════════════════════════════ */

    .tutor-progress {
        height: 8px; border-radius: 9999px; overflow: hidden;
        background: #F3F4F6;
    }
    .dark .tutor-progress { background: #1F2937; }
    .tutor-progress > div {
        height: 100%; border-radius: 9999px;
        background: #7D39EB;
        transition: width .5s ease;
    }

    /* ═══ SaaS подписка ═══════════════════════════════════════ */

    .tutor-comm-top {
        display: flex; flex-wrap: wrap; align-items: flex-start;
        justify-content: space-between; gap: 12px 24px;
    }
    .tutor-comm-rate {
        display: flex; flex-wrap: wrap; align-items: center; gap: 8px 12px;
    }
    .tutor-comm-plan-price {
        display: inline-flex; align-items: baseline; gap: 8px; flex-shrink: 0;
    }
    .tutor-comm-rate-value {
        font-size: clamp(1.75rem, 1.3rem + 2vw, 2.25rem);
        font-weight: 900; letter-spacing: -.02em; line-height: 1;
        font-variant-numeric: tabular-nums; color: #0C0A14;
    }
    .dark .tutor-comm-rate-value { color: #fff; }
    .tutor-comm-rate-unit {
        font-size: 0.95rem; font-weight: 700; color: #6B7280;
        white-space: nowrap; flex-shrink: 0;
    }
    .dark .tutor-comm-rate-unit { color: #9CA3AF; }
    .tutor-comm-status-chip {
        font-size: 11px; padding: 4px 10px;
        white-space: nowrap; max-width: 100%;
        overflow: hidden; text-overflow: ellipsis;
        border-radius: 9999px;
    }
    .tutor-comm-revenue { text-align: right; }
    @media (max-width: 639px) {
        .tutor-comm-top {
            flex-direction: column;
            gap: 12px;
        }
        .tutor-comm-revenue {
            text-align: left;
            width: 100%;
            padding-top: 12px;
            margin-top: 4px;
            border-top: 1px dashed #E5E7EB;
        }
        .dark .tutor-comm-revenue { border-top-color: #374151; }
        .tutor-chips {
            flex-direction: column;
        }
        .tutor-chips .tutor-chip {
            white-space: normal;
            line-height: 1.35;
            padding: 8px 12px;
            justify-content: flex-start;
        }
    }
    .tutor-comm-revenue-value {
        margin: 2px 0 0; font-size: 1.375rem; font-weight: 900; letter-spacing: -.01em;
        font-variant-numeric: tabular-nums; color: #7D39EB; line-height: 1.1;
    }
    @media (min-width: 640px) { .tutor-comm-revenue-value { font-size: 1.5rem; } }
    .dark .tutor-comm-revenue-value { color: #A78BFA; }
    .tutor-comm-revenue-unit { font-size: 12px; font-weight: 700; color: #9CA3AF; }

    .tutor-comm-progress { margin-top: 20px; }
    .tutor-comm-progress-head {
        display: flex; flex-wrap: wrap; align-items: baseline;
        justify-content: space-between; gap: 4px 16px;
        font-size: 12px; font-weight: 700; color: #6B7280;
    }
    .dark .tutor-comm-progress-head { color: #9CA3AF; }
    .tutor-comm-progress-head strong { color: #0C0A14; font-weight: 800; }
    .dark .tutor-comm-progress-head strong { color: #fff; }
    .tutor-comm-progress-head .tutor-comm-next { color: #7D39EB; font-weight: 800; }
    .dark .tutor-comm-progress-head .tutor-comm-next { color: #A78BFA; }
    .tutor-comm-progress .tutor-progress { margin-top: 8px; }

    .tutor-chips { margin-top: 16px; display: flex; flex-wrap: wrap; gap: 8px; }

    .tutor-chip {
        display: inline-flex; align-items: center; gap: 6px;
        padding: 7px 12px; border-radius: 9999px;
        font-size: 12px; font-weight: 700; line-height: 1;
        border: 1px solid #E5E7EB; color: #6B7280;
        background: #fff; white-space: nowrap;
    }
    .dark .tutor-chip { background: #111827; border-color: #374151; color: #9CA3AF; }
    .tutor-chip svg { width: 14px; height: 14px; }
    .tutor-chip--active {
        border-color: #7D39EB; color: #7D39EB;
        background: rgba(125, 57, 235, .07);
    }
    .dark .tutor-chip--active { color: #A78BFA; border-color: #A78BFA; background: rgba(125, 57, 235, .15); }
    .tutor-chip--done { color: #059669; border-color: #A7F3D0; }
    .dark .tutor-chip--done { color: #34D399; border-color: rgba(52, 211, 153, .3); }
    .tutor-chip-dot { width: 6px; height: 6px; border-radius: 9999px; background: #7D39EB; }

    .tutor-link {
        display: inline-flex; align-items: center; gap: 6px;
        margin-top: 20px;
        font-size: .875rem; font-weight: 700; text-decoration: none;
        color: #7D39EB;
    }
    .tutor-link:hover { text-decoration: underline; }
    .dark .tutor-link { color: #A78BFA; }
    .tutor-link svg { width: 16px; height: 16px; }

    /* ═══ Онбординг-чеклист ═══════════════════════════════════════ */

    .tutor-onb-head {
        display: flex; flex-wrap: wrap; align-items: center;
        justify-content: space-between; gap: 12px 24px;
    }
    .tutor-onb-title { margin: 4px 0 0; font-size: 1.125rem; font-weight: 800; line-height: 1.25; color: #0C0A14; }
    .dark .tutor-onb-title { color: #fff; }
    .tutor-onb-rank { margin: 2px 0 0; font-size: 12px; color: #9CA3AF; }
    .tutor-onb-head .tutor-progress { margin-top: 16px; width: 100%; order: 3; }

    .tutor-check-list { margin-top: 16px; display: grid; grid-template-columns: 1fr; gap: 10px; }
    @media (min-width: 640px) { .tutor-check-list { grid-template-columns: 1fr 1fr; gap: 10px; } }

    .tutor-check-row {
        display: flex; align-items: center; gap: 12px;
        min-height: 56px; padding: 12px 16px;
        border-radius: 18px;
        border: 1px solid #ECEEF1; background: #fff;
        text-decoration: none;
        transition: border-color .2s ease;
    }
    .dark .tutor-check-row { background: #111827; border-color: #1F2937; }
    .tutor-check-row:hover { border-color: #7D39EB; }
    .tutor-check-row--done { opacity: .62; }
    .tutor-check-row--done:hover { border-color: #ECEEF1; }

    .tutor-check-icon {
        width: 28px; height: 28px; flex: none;
        display: inline-flex; align-items: center; justify-content: center;
        border-radius: 9999px;
    }
    .tutor-check-icon svg { width: 16px; height: 16px; }
    .tutor-check-icon--done { background: rgba(16, 185, 129, .14); color: #059669; }
    .tutor-check-icon--todo { background: rgba(245, 158, 11, .14); color: #D97706; }
    .dark .tutor-check-icon--todo { color: #FBBF24; }

    .tutor-check-label { font-size: .9375rem; font-weight: 800; color: #1F2937; line-height: 1.3; }
    .dark .tutor-check-label { color: #E5E7EB; }
    .tutor-check-note { display: block; margin-top: 2px; font-size: 13px; font-weight: 500; color: #64748B; }
    .tutor-check-chevron { margin-left: auto; width: 18px; height: 18px; flex: none; color: #94A3B8; }

    .tutor-onb-welcome .tutor-onb-lead {
        margin: 8px 0 0; max-width: 560px;
        font-size: .875rem; line-height: 1.55; color: #6B7280;
    }
    .dark .tutor-onb-welcome .tutor-onb-lead { color: #9CA3AF; }
</style>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('tutorLessonCountdown', ({ startsAt, endsAt, joinFrom, joinUntil, joinAvailable }) => ({
            now: Math.floor(Date.now() / 1000),
            hydrated: false,
            started: false,
            finished: false,
            joinAvailable: joinAvailable,

            init() {
                this.tick();
                setInterval(() => { this.now = Math.floor(Date.now() / 1000); this.tick(); }, 30000);
                this.hydrated = true;
            },

            tick() {
                this.started = this.now >= startsAt;
                this.finished = this.now > endsAt;
                this.joinAvailable = this.now >= joinFrom && this.now <= joinUntil;
            },

            get countdown() {
                const diff = startsAt - this.now;
                if (diff <= 0) return '';
                const days = Math.floor(diff / 86400);
                const hours = Math.floor((diff % 86400) / 3600);
                const minutes = Math.ceil((diff % 3600) / 60);
                if (days >= 1) return `через ${days} ${this.plural(days, 'день', 'дня', 'дней')} ${hours} ${this.plural(hours, 'час', 'часа', 'часов')}`;
                if (hours >= 1) return `через ${hours} ${this.plural(hours, 'час', 'часа', 'часов')} ${minutes} ${this.plural(minutes, 'мин', 'мин', 'мин')}`;
                if (minutes > 1) return `через ${minutes} ${this.plural(minutes, 'минуту', 'минуты', 'минут')}`;
                return 'начнётся с минуты на минуту';
            },

            plural(n, one, few, many) {
                const mod10 = n % 10, mod100 = n % 100;
                if (mod10 === 1 && mod100 !== 11) return one;
                if (mod10 >= 2 && mod10 <= 4 && (mod100 < 12 || mod100 > 14)) return few;
                return many;
            },
        }));
    });
</script>
