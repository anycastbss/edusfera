<x-filament-panels::page>
    <div class="ed-settings-container" 
         x-data="{
             largeFont: false,
             init() {
                 try {
                     const stored = localStorage.getItem('comfort_vision') || localStorage.getItem('edusfera_large_font');
                     this.largeFont = stored === 'true' || document.documentElement.classList.contains('comfort-vision') || document.documentElement.classList.contains('ed-large-font');
                 } catch(e) {
                     this.largeFont = document.documentElement.classList.contains('comfort-vision') || document.documentElement.classList.contains('ed-large-font');
                 }
                 window.addEventListener('edusfera:large-font-changed', (e) => {
                     this.largeFont = e.detail.isLarge;
                 });
             },
             setLargeFont(val) {
                 this.largeFont = val;
                 document.documentElement.classList.toggle('comfort-vision', val);
                 document.documentElement.classList.toggle('ed-large-font', val);
                 if (document.body) {
                     document.body.classList.toggle('comfort-vision', val);
                     document.body.classList.toggle('ed-large-font', val);
                 }
                 try {
                     localStorage.setItem('comfort_vision', val ? 'true' : 'false');
                     localStorage.setItem('edusfera_large_font', val ? 'true' : 'false');
                 } catch(e) {}
                 $wire.toggleLargeFont(val);
             }
         }">

        <style>
            .ed-settings-container {
                max-width: 900px;
                margin: 0 auto;
                display: flex;
                flex-direction: column;
                gap: 24px;
            }

            .ed-settings-card {
                background: #FFFFFF;
                border: 1px solid #E2E8F0;
                border-radius: 20px;
                padding: 28px;
                box-shadow: 0 4px 20px rgba(0, 0, 0, 0.03);
                transition: border-color 0.2s ease, box-shadow 0.2s ease;
            }

            .dark .ed-settings-card {
                background: #1E293B;
                border-color: #334155;
                box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
            }

            .ed-settings-header {
                display: flex;
                align-items: center;
                gap: 16px;
                margin-bottom: 20px;
            }

            .ed-settings-icon {
                width: 48px;
                height: 48px;
                border-radius: 14px;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 24px;
                flex-shrink: 0;
            }

            .ed-settings-title {
                font-size: 1.25rem;
                font-weight: 800;
                color: #0F172A;
                margin: 0 0 4px 0;
                line-height: 1.3;
            }

            .dark .ed-settings-title {
                color: #F8FAFC;
            }

            .ed-settings-desc {
                font-size: 0.95rem;
                color: #334155;
                margin: 0;
                line-height: 1.5;
            }

            .dark .ed-settings-desc {
                color: #94A3B8;
            }

            /* Live switch block */
            .ed-toggle-row {
                display: flex;
                align-items: center;
                justify-content: space-between;
                padding: 20px;
                background: #F8FAFC;
                border: 1px solid #E2E8F0;
                border-radius: 16px;
                gap: 16px;
            }

            .dark .ed-toggle-row {
                background: #0F172A;
                border-color: #334155;
            }

            .ed-toggle-info {
                display: flex;
                flex-direction: column;
                gap: 4px;
            }

            .ed-toggle-label {
                font-size: 1.05rem;
                font-weight: 700;
                color: #0F172A;
            }

            .dark .ed-toggle-label {
                color: #FFFFFF;
            }

            .ed-toggle-sub {
                font-size: 0.9rem;
                color: #475569;
            }

            .dark .ed-toggle-sub {
                color: #94A3B8;
            }

            /* Big tactile switch */
            .ed-switch {
                position: relative;
                width: 68px;
                height: 38px;
                border-radius: 20px;
                background: #CBD5E1;
                cursor: pointer;
                transition: background 0.25s ease;
                border: 2px solid transparent;
                flex-shrink: 0;
            }

            .ed-switch--active {
                background: #10B981;
            }

            .ed-switch-thumb {
                position: absolute;
                top: 3px;
                left: 3px;
                width: 28px;
                height: 28px;
                border-radius: 50%;
                background: #FFFFFF;
                box-shadow: 0 2px 6px rgba(0,0,0,0.25);
                transition: transform 0.25s ease;
            }

            .ed-switch--active .ed-switch-thumb {
                transform: translateX(30px);
            }

            /* Preview frame */
            .ed-preview-box {
                margin-top: 20px;
                padding: 20px;
                border-radius: 14px;
                border: 2px dashed #CBD5E1;
                background: #FFFFFF;
                transition: all 0.2s ease;
            }

            .dark .ed-preview-box {
                background: #0F172A;
                border-color: #475569;
            }

            .ed-preview-badge {
                display: inline-flex;
                align-items: center;
                gap: 6px;
                padding: 4px 10px;
                border-radius: 8px;
                font-size: 0.8rem;
                font-weight: 700;
                background: #E0E7FF;
                color: #4338CA;
                margin-bottom: 12px;
            }

            /* Radio cards for privacy */
            .ed-privacy-grid {
                display: grid;
                grid-template-columns: 1fr;
                gap: 16px;
            }

            @media (min-width: 640px) {
                .ed-privacy-grid {
                    grid-template-columns: 1fr 1fr;
                }
            }

            .ed-privacy-card {
                padding: 22px;
                border-radius: 16px;
                border: 2px solid #E2E8F0;
                background: #F8FAFC;
                cursor: pointer;
                transition: all 0.2s ease;
                display: flex;
                flex-direction: column;
                gap: 10px;
            }

            .dark .ed-privacy-card {
                background: #0F172A;
                border-color: #334155;
            }

            .ed-privacy-card--active {
                border-color: #7D39EB;
                background: rgba(125, 57, 235, 0.04);
                box-shadow: 0 0 0 1px #7D39EB;
            }

            .dark .ed-privacy-card--active {
                background: rgba(125, 57, 235, 0.15);
            }

            .ed-privacy-title {
                font-size: 1.05rem;
                font-weight: 700;
                color: #0F172A;
                display: flex;
                align-items: center;
                gap: 8px;
            }

            .dark .ed-privacy-title {
                color: #FFFFFF;
            }

            .ed-privacy-text {
                font-size: 0.88rem;
                color: #475569;
                line-height: 1.45;
                margin: 0;
            }

            .dark .ed-privacy-text {
                color: #94A3B8;
            }

            /* Template textarea */
            .ed-textarea {
                width: 100%;
                padding: 14px 16px;
                border-radius: 12px;
                border: 1px solid #CBD5E1;
                background: #FFFFFF;
                color: #0F172A;
                font-size: 0.95rem;
                line-height: 1.5;
                box-sizing: border-box;
                font-family: inherit;
                resize: vertical;
            }

            .dark .ed-textarea {
                background: #0F172A;
                border-color: #475569;
                color: #FFFFFF;
            }

            .ed-save-btn {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                gap: 8px;
                padding: 12px 24px;
                background: #7D39EB;
                color: #FFFFFF;
                font-weight: 700;
                font-size: 0.95rem;
                border-radius: 12px;
                border: none;
                cursor: pointer;
                transition: background 0.15s ease;
            }

            .ed-save-btn:hover {
                background: #6D28D9;
            }

            /* Links to guides */
            .ed-guide-item {
                display: flex;
                align-items: center;
                justify-content: space-between;
                padding: 14px 18px;
                background: #F8FAFC;
                border: 1px solid #E2E8F0;
                border-radius: 12px;
                color: #0F172A;
                font-weight: 600;
                font-size: 0.95rem;
                text-decoration: none;
                transition: all 0.2s ease;
            }

            .dark .ed-guide-item {
                background: #0F172A;
                border-color: #334155;
                color: #F8FAFC;
            }

            .ed-guide-item:hover {
                border-color: #7D39EB;
                background: rgba(125, 57, 235, 0.05);
                transform: translateX(4px);
            }
        </style>

        {{-- ── БЛОК 1. ВИЗУАЛЬНЫЙ КОМФОРТ (ШРИФТ И ЗРЕНИЕ 50+) ───────── --}}
        <div class="ed-settings-card">
            <div class="ed-settings-header">
                <div class="ed-settings-icon" style="background: rgba(16, 185, 129, 0.15); color: #059669;">
                    👁️
                </div>
                <div>
                    <h2 class="ed-settings-title">Визуальный комфорт и зрение</h2>
                    <p class="ed-settings-desc">Специальные настройки для удобного чтения без усталости глаз, оптимизированные для преподавателей 50+.</p>
                </div>
            </div>

            <div class="ed-toggle-row">
                <div class="ed-toggle-info">
                    <span class="ed-toggle-label">Режим крупного шрифта и высокой контрастности</span>
                    <span class="ed-toggle-sub">
                        Увеличивает текст до 18–20px, делает все подписи контрастными (глубокий графит вместо серого) и расширяет кнопки для удобного нажатия с телефона или планшета.
                    </span>
                </div>
                <div class="ed-switch" 
                     :class="{ 'ed-switch--active': largeFont }" 
                     @click="setLargeFont(!largeFont)" 
                     role="switch" 
                     aria-label="Переключить крупный шрифт" 
                     tabindex="0"
                     @keydown.space.prevent="setLargeFont(!largeFont)">
                    <div class="ed-switch-thumb"></div>
                </div>
            </div>

            {{-- Live interactive preview --}}
            <div class="ed-preview-box">
                <span class="ed-preview-badge">Образец отображения</span>
                <div style="display: flex; flex-direction: column; gap: 8px;">
                    <div style="font-weight: 800; font-size: 1.15rem; color: #0F172A;" class="dark:text-white">
                        Ближайший онлайн-урок: Математика (Подготовка к ЦТ)
                    </div>
                    <div style="font-size: 0.95rem; color: #334155;" class="dark:text-slate-300">
                        Ученик: Анна Смирнова · Сегодня в 16:00 (длительность 60 минут)
                    </div>
                    <div style="display: flex; gap: 10px; margin-top: 6px; flex-wrap: wrap;">
                        <span style="display: inline-flex; align-items: center; gap: 6px; padding: 8px 16px; border-radius: 10px; background: #C6FF33; color: #000; font-weight: 800; font-size: 0.9rem;">
                            ✓ Доступ в класс открыт
                        </span>
                        <span style="display: inline-flex; align-items: center; gap: 6px; padding: 8px 16px; border-radius: 10px; background: #F1F5F9; color: #334155; font-weight: 700; font-size: 0.9rem;" class="dark:bg-slate-800 dark:text-slate-200">
                            💬 Написать в чат
                        </span>
                    </div>
                </div>
            </div>
        </div>

        {{-- ── БЛОК 2. ПРИВАТНОСТЬ И РЕЖИМ РАБОТЫ ─────────────────────── --}}
        <div class="ed-settings-card">
            <div class="ed-settings-header">
                <div class="ed-settings-icon" style="background: rgba(125, 57, 235, 0.15); color: #7D39EB;">
                    🔒
                </div>
                <div>
                    <h2 class="ed-settings-title">Режим работы и видимость анкеты</h2>
                    <p class="ed-settings-desc">Выберите, как вы хотите преподавать на платформе: только со своими учениками или принимать новые заявки.</p>
                </div>
            </div>

            <div class="ed-privacy-grid">
                <div class="ed-privacy-card {{ ! $isPublic ? 'ed-privacy-card--active' : '' }}"
                     wire:click="togglePrivacy(false)">
                    <div class="ed-privacy-title">
                        <span>🔒</span>
                        <span>Приватный кабинет</span>
                        @if(! $isPublic)
                            <span style="font-size: 0.75rem; background: #10B981; color: #fff; padding: 2px 8px; border-radius: 6px; margin-left: auto;">Активен</span>
                        @endif
                    </div>
                    <p class="ed-privacy-text">
                        <strong>Режим личной школы.</strong> Ваша анкета и расценки скрыты из каталога и поисковиков. Ученики заходят строго по вашей личной ссылке и не видят чужих репетиторов.
                    </p>
                </div>

                <div class="ed-privacy-card {{ $isPublic ? 'ed-privacy-card--active' : '' }}"
                     wire:click="togglePrivacy(true)">
                    <div class="ed-privacy-title">
                        <span>🌐</span>
                        <span>Открытая витрина</span>
                        @if($isPublic)
                            <span style="font-size: 0.75rem; background: #7D39EB; color: #fff; padding: 2px 8px; border-radius: 6px; margin-left: auto;">Активен</span>
                        @endif
                    </div>
                    <p class="ed-privacy-text">
                        <strong>Поиск новых учеников.</strong> Ваша анкета опубликована в общем каталоге Edusfera. Родители могут находить вас по предмету и оставлять заявки.
                    </p>
                </div>
            </div>
        </div>

        {{-- ── БЛОК 3. ШАБЛОН ДЛЯ VIBER И TELEGRAM ─────────────────────── --}}
        <div class="ed-settings-card">
            <div class="ed-settings-header">
                <div class="ed-settings-icon" style="background: rgba(14, 165, 233, 0.15); color: #0284C7;">
                    📲
                </div>
                <div>
                    <h2 class="ed-settings-title">Шаблон приглашения для учеников (в 1 клик)</h2>
                    <p class="ed-settings-desc">Этот текст автоматически подставляется при нажатии кнопок «Отправить в Viber» и «Отправить в Telegram» рядом со ссылкой на урок.</p>
                </div>
            </div>

            <div style="display: flex; flex-direction: column; gap: 12px;">
                <label style="font-size: 0.88rem; font-weight: 700; color: #334155;" class="dark:text-slate-300">
                    Текст сообщения (используйте <code>{link}</code> в месте, где должна быть ссылка на класс):
                </label>

                <textarea wire:model.defer="customMessageTemplate" 
                          rows="4" 
                          class="ed-textarea" 
                          placeholder="Здравствуйте! Ссылка на наш онлайн-урок в Edusfera: {link}. Вход в один клик без регистрации."></textarea>

                <div style="display: flex; justify-content: flex-end; margin-top: 4px;">
                    <button type="button" wire:click="saveTemplate" class="ed-save-btn">
                        <svg style="width: 16px; height: 16px;" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                        <span>Сохранить шаблон</span>
                    </button>
                </div>
            </div>
        </div>

        {{-- ── БЛОК 4. ПАМЯТКИ И ОБУЧЕНИЕ ДЛЯ ПРЕПОДАВАТЕЛЯ ───────────── --}}
        <div class="ed-settings-card">
            <div class="ed-settings-header">
                <div class="ed-settings-icon" style="background: rgba(245, 158, 11, 0.15); color: #D97706;">
                    📖
                </div>
                <div>
                    <h2 class="ed-settings-title">Памятки и обучение работе на платформе</h2>
                    <p class="ed-settings-desc">Простые пошаговые инструкции крупным шрифтом без технических сложностей:</p>
                </div>
            </div>

            <div style="display: flex; flex-direction: column; gap: 10px;">
                <a href="/news/kak-provesti-pervyj-urok-za-3-shaga" target="_blank" class="ed-guide-item">
                    <span>1. Как провести свой первый онлайн-урок за 3 простых шага</span>
                    <span>→</span>
                </a>

                <a href="/news/shablony-priglashenij-viber-telegram" target="_blank" class="ed-guide-item">
                    <span>2. Готовые вежливые шаблоны сообщений для родителей в Viber и Telegram</span>
                    <span>→</span>
                </a>

                <a href="/news/chto-delat-esli-propal-zvuk-ili-kamera" target="_blank" class="ed-guide-item">
                    <span>3. Что делать, если пропал звук или камера (решение за 30 секунд)</span>
                    <span>→</span>
                </a>

                <a href="/news/rezhim-lichnoj-shkoly-privatnyj-kabinet" target="_blank" class="ed-guide-item">
                    <span>4. Как работать приватно только со своими учениками без посторонних</span>
                    <span>→</span>
                </a>

                <a href="/news/kak-nastroit-raspisanie-i-raspechatat-a4" target="_blank" class="ed-guide-item">
                    <span>5. Как настроить расписание и распечатать его на бумагу (А4)</span>
                    <span>→</span>
                </a>
            </div>

            <div style="margin-top: 18px; padding-top: 16px; border-top: 1px solid #E2E8F0; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px;" class="dark:border-slate-700">
                <span style="font-size: 0.9rem; color: #475569;" class="dark:text-slate-400">
                    💬 Если возникла трудность — дежурный методист на связи:
                </span>
                <a href="https://t.me/edusferaby" target="_blank" style="display: inline-flex; align-items: center; gap: 6px; padding: 8px 16px; border-radius: 10px; background: #0284C7; color: #fff; font-weight: 700; font-size: 0.88rem; text-decoration: none;">
                    <span>✈️ Чат поддержки в Telegram</span>
                </a>
            </div>
        </div>
    </div>
</x-filament-panels::page>
