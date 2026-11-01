{{--
    Переключатель режима доступности «Крупный шрифт» для пользователей 50+ / слабовидящих
    (WCAG 2.2 AAA / UI_RULES.md):
    - Мгновенное переключение размера шрифта на +18% без перезагрузки
    - Сохранение состояния в localStorage
    - Увеличенные тач-таргеты (>= 48-54px)
    - Повышенная контрастность текста и элементов управления
--}}

<div x-data="{
        isLarge: false,
        init() {
            this.isLarge = document.documentElement.classList.contains('comfort-vision') || document.documentElement.classList.contains('ed-large-font');
            window.addEventListener('edusfera:large-font-changed', (e) => {
                this.isLarge = e.detail.isLarge;
            });
        },
        toggle() {
            this.isLarge = !this.isLarge;
            document.documentElement.classList.toggle('comfort-vision', this.isLarge);
            document.documentElement.classList.toggle('ed-large-font', this.isLarge);
            if (document.body) {
                document.body.classList.toggle('comfort-vision', this.isLarge);
                document.body.classList.toggle('ed-large-font', this.isLarge);
            }
            try {
                localStorage.setItem('comfort_vision', this.isLarge ? 'true' : 'false');
                localStorage.setItem('edusfera_large_font', this.isLarge ? 'true' : 'false');
            } catch(e) {}
            window.dispatchEvent(new CustomEvent('edusfera:large-font-changed', { detail: { isLarge: this.isLarge } }));
        }
     }"
     class="ed-font-toggle-container flex items-center">

    <button type="button"
            x-on:click="toggle()"
            :aria-pressed="isLarge.toString()"
            :class="isLarge ? 'ed-font-toggle--active' : ''"
            class="ed-font-toggle-btn"
            title="Переключить режим «Комфортное зрение» (18px, высокая контрастность)"
            aria-label="Режим комфортного зрения">
        
        {{-- Иконка размеров шрифта А / А+ --}}
        <span class="ed-font-toggle-icon" aria-hidden="true">
            <span class="ed-font-letter-sm">А</span>
            <span class="ed-font-letter-lg">А</span>
        </span>

        <span class="ed-font-toggle-text">
            <span class="hidden sm:inline">Крупный шрифт</span>
            <span class="sm:hidden">Текст</span>
        </span>

        {{-- Индикатор состояния --}}
        <span class="ed-font-toggle-status" :class="isLarge ? 'ed-status--on' : 'ed-status--off'">
            <span x-text="isLarge ? 'ВКЛ' : 'ОБЫЧ'"></span>
        </span>
    </button>
</div>

<style>
    /* ═══ Кнопка переключателя ═══ */
    .ed-font-toggle-btn {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        height: 38px;
        padding: 0 10px;
        border-radius: 12px;
        background: #FFFFFF;
        border: 1.5px solid #E2E8F0;
        color: #334155;
        font-family: inherit;
        font-size: 13px;
        font-weight: 700;
        cursor: pointer;
        transition: all 0.15s ease;
        user-select: none;
        -webkit-tap-highlight-color: transparent;
    }

    .dark .ed-font-toggle-btn {
        background: #1E293B;
        border-color: #334155;
        color: #E2E8F0;
    }

    .ed-font-toggle-btn:hover {
        border-color: #7D39EB;
        color: #7D39EB;
        transform: translateY(-1px);
    }

    .ed-font-toggle-btn:active {
        transform: scale(0.96);
    }

    .ed-font-toggle--active {
        background: #FAF5FF !important;
        border-color: #7D39EB !important;
        color: #7D39EB !important;
        box-shadow: 0 0 0 2px rgba(125, 57, 235, 0.18);
    }

    .dark .ed-font-toggle--active {
        background: rgba(125, 57, 235, 0.22) !important;
        border-color: #A78BFA !important;
        color: #DDD6FE !important;
    }

    .ed-font-toggle-icon {
        display: inline-flex;
        align-items: baseline;
        gap: 1px;
        line-height: 1;
    }

    .ed-font-letter-sm {
        font-size: 11px;
        font-weight: 800;
        opacity: 0.75;
    }

    .ed-font-letter-lg {
        font-size: 15px;
        font-weight: 900;
    }

    .ed-font-toggle-text {
        font-size: 12px;
        font-weight: 700;
        line-height: 1;
    }

    .ed-font-toggle-status {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 2px 5px;
        border-radius: 6px;
        font-size: 10px;
        font-weight: 900;
        letter-spacing: 0.04em;
        line-height: 1;
    }

    .ed-status--off {
        background: #F1F5F9;
        color: #64748B;
    }

    .dark .ed-status--off {
        background: #334155;
        color: #94A3B8;
    }

    .ed-status--on {
        background: #7D39EB;
        color: #FFFFFF;
    }

    .dark .ed-status--on {
        background: #A78BFA;
        color: #0F172A;
    }

    /* ═══════════════════════════════════════════════════════════════════
       СТИЛИ РЕЖИМА «КРУПНЫЙ ШРИФТ» (html.ed-large-font)
       ═══════════════════════════════════════════════════════════════════ */
    html.ed-large-font {
        font-size: 118% !important; /* Базовое масштабирование всех rem-компонентов */
    }

    /* Повышенная четкость и контраст текста */
    html.ed-large-font body,
    html.ed-large-font .fi-main,
    html.ed-large-font .fi-page {
        letter-spacing: 0.01em;
        line-height: 1.55;
    }

    /* Увеличенные кнопки и тач-таргеты (минимум 48-54px) */
    html.ed-large-font .fi-btn,
    html.ed-large-font button:not(.ed-nav-tab):not(.ed-font-toggle-btn),
    html.ed-large-font .tutor-cta,
    html.ed-large-font .student-spotlight-cta,
    html.ed-large-font .tutor-plan-btn {
        min-height: 52px !important;
        font-size: 1.05rem !important;
        font-weight: 800 !important;
    }

    /* Поля ввода и формы: защита от зума на iOS + четкие подписи */
    html.ed-large-font input,
    html.ed-large-font select,
    html.ed-large-font textarea,
    html.ed-large-font .fi-input {
        font-size: 1.05rem !important;
        min-height: 48px !important;
    }

    html.ed-large-font label,
    html.ed-large-font .fi-fo-field-wrp-label {
        font-size: 1rem !important;
        font-weight: 800 !important;
        color: #1E293B !important;
    }

    html.ed-large-font.dark label,
    html.ed-large-font.dark .fi-fo-field-wrp-label {
        color: #F1F5F9 !important;
    }

    /* Увеличенные бейджи и статусы */
    html.ed-large-font .fi-badge,
    html.ed-large-font .tutor-card-label,
    html.ed-large-font .student-metric-label {
        font-size: 12px !important;
        font-weight: 900 !important;
        letter-spacing: 0.06em !important;
    }

    /* Увеличенная нижняя мобильная навигация */
    html.ed-large-font .ed-nav-label {
        font-size: 12px !important;
        font-weight: 800 !important;
    }

    html.ed-large-font .ed-nav-icon {
        width: 26px !important;
        height: 26px !important;
    }

    /* Карточки расписания */
    html.ed-large-font .ed-agenda-btn {
        min-height: 54px !important;
        font-size: 1.05rem !important;
        font-weight: 800 !important;
    }

    /* Увеличенные финансовые карточки */
    html.ed-large-font .tutor-stat-value,
    html.ed-large-font .student-metric-value {
        font-size: 1.85rem !important;
        font-weight: 900 !important;
    }
</style>
