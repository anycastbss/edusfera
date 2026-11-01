@php
    $tz = config('booking.display_timezone', 'Europe/Minsk');
    $lesson = $upcomingLesson;
@endphp

<div class="tutor-hero" x-data="tutorLessonCountdown({
        startsAt: {{ (int) $lessonStartsAt }},
        endsAt: {{ (int) $lessonEndsAt }},
        joinFrom: {{ (int) $lessonJoinFrom }},
        joinUntil: {{ (int) $lessonJoinUntil }},
        joinAvailable: {{ $meetingJoinAvailable ? 'true' : 'false' }},
    })">

    {{-- Приветствие: один герой-заголовок экрана и бейдж подписки --}}
    <div class="tutor-hero-top">
        <div>
            <h2 class="tutor-hero-title">{{ $greeting }}, {{ $firstName }} 👋</h2>
            <p class="tutor-hero-date">{{ $dateLine }}</p>
        </div>
        <a href="/admin/tutor-subscription-page" class="tutor-hero-sub-pill {{ $isInGrace ? 'tutor-hero-sub-pill--grace' : ($isTrial ? 'tutor-hero-sub-pill--trial' : 'tutor-hero-sub-pill--active') }}" title="Управление подпиской">
            @if($isTrial)
                <span>🌱</span>
            @elseif($isInGrace)
                <span>⚠️</span>
            @else
                <span>⚡</span>
            @endif
            <span>{{ $subPillText }}</span>
        </a>
    </div>

    {{-- Ненавязчивое предупреждение об окончании / льготном периоде --}}
    @if ($isInGrace)
        <div class="tutor-hero-sub-alert tutor-hero-sub-alert--grace">
            <div class="tutor-hero-sub-alert-content">
                <svg class="tutor-hero-sub-alert-icon" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                </svg>
                <div>
                    <strong>Платёж за подписку не прошёл.</strong>
                    <span>Ваши уроки и учебные материалы в полной сохранности. Пожалуйста, проверьте баланс карты (осталось {{ $graceDaysRemaining }} {{ trans_choice('день|дня|дней', $graceDaysRemaining) }} льготного периода), чтобы занятия продолжались без перерыва.</span>
                </div>
            </div>
            <a href="/admin/tutor-subscription-page" class="tutor-hero-sub-alert-btn tutor-hero-sub-alert-btn--danger">
                Обновить карту
            </a>
        </div>
    @elseif ($isExpiringSoon)
        <div class="tutor-hero-sub-alert tutor-hero-sub-alert--expiring">
            <div class="tutor-hero-sub-alert-content">
                <svg class="tutor-hero-sub-alert-icon" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                </svg>
                <div>
                    @if ($isTrial)
                        <strong>Пробный период завершается через {{ $daysRemaining }} {{ trans_choice('день|дня|дней', $daysRemaining) }}.</strong>
                        <span>Подключите удобный тариф, чтобы продолжить преподавать.</span>
                    @else
                        <strong>Подписка истекает через {{ $daysRemaining }} {{ trans_choice('день|дня|дней', $daysRemaining) }}.</strong>
                        <span>Продлите тариф, чтобы продолжить комфортные занятия с учениками.</span>
                    @endif
                </div>
            </div>
            <a href="/admin/tutor-subscription-page" class="tutor-hero-sub-alert-btn">
                Перейти к тарифу
            </a>
        </div>
    @endif

    {{-- Крупный переключатель-индикатор статуса приватности кабинета --}}
    <div class="tutor-privacy-status-bar">
        <div class="tutor-privacy-status-content">
            @if (! $isPublic)
                <div class="tutor-privacy-badge tutor-privacy-badge--private">
                    <span class="tutor-privacy-dot">🟢</span>
                    <span class="tutor-privacy-text">
                        <strong>Статус кабинета: ПРИВАТНЫЙ</strong> <span class="tutor-privacy-hint">(Ваша анкета скрыта от посторонних)</span>
                    </span>
                </div>
            @else
                <div class="tutor-privacy-badge tutor-privacy-badge--public">
                    <span class="tutor-privacy-dot">🌐</span>
                    <span class="tutor-privacy-text">
                        <strong>Статус кабинета: ОТКРЫТАЯ ВИТРИНА</strong> <span class="tutor-privacy-hint">(Ваша анкета видна в каталоге Edusfera)</span>
                    </span>
                </div>
            @endif
        </div>

        <button 
            type="button" 
            wire:click="toggleProfileVisibility" 
            wire:loading.attr="disabled"
            class="tutor-privacy-toggle-btn"
            title="Переключить видимость анкеты в каталоге"
        >
            <span wire:loading.remove wire:target="toggleProfileVisibility" style="display:inline-flex; align-items:center; gap:6px;">
                @if (! $isPublic)
                    <svg style="width:14px; height:14px;" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 0 0 8.716-6.747M12 21a9.004 9.004 0 0 1-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 0 1 7.843 4.582M12 3a8.997 8.997 0 0 0-7.843 4.582m15.686 0A11.953 11.953 0 0 1 12 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0 1 21 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0 1 12 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 0 1 3 12c0-1.605.42-3.113 1.157-4.418"/></svg>
                    <span>Опубликовать в каталоге</span>
                @else
                    <svg style="width:14px; height:14px;" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z"/></svg>
                    <span>Скрыть в приватный</span>
                @endif
            </span>
            <span wire:loading wire:target="toggleProfileVisibility">Сохранение...</span>
        </button>
    </div>

    {{-- ═══ Блок Общественного методического совета Edusfera (Закрытая квота 30 педагогов) ═══ --}}
    @if ($isCouncilExpert)
        <div class="tutor-council-card" x-data="{ certOpen: false }">
            <div class="tutor-council-card-content">
                <div class="tutor-council-card-badge">
                    <span class="tutor-council-medal">🎖️</span>
                    <div>
                        <div class="tutor-council-title">Эксперт Общественного методического совета Edusfera</div>
                        <div class="tutor-council-sub">
                            Официальное свидетельство <strong>{{ $councilCertNumber }}</strong> (статус присвоен бессрочно).
                        </div>
                    </div>
                </div>
                <div class="tutor-council-actions">
                    <button type="button" @click="certOpen = true" class="tutor-council-cert-btn">
                        <span>📜 Свидетельство для портфолио (А4)</span>
                    </button>
                </div>
            </div>

            {{-- Модальное окно официального свидетельства для аттестационного портфолио --}}
            <div x-show="certOpen" x-cloak class="tutor-cert-modal-backdrop" @click.self="certOpen = false">
                <div class="tutor-cert-modal" @click.stop>
                    <div class="tutor-cert-modal-header no-print">
                        <span style="font-weight: 800; font-size: 14px; color: #1E293B;">Официальное свидетельство эксперта</span>
                        <div style="display:flex; align-items:center; gap:8px;">
                            <a href="{{ route('admin.council.certificate.print') }}" target="_blank" class="tutor-cert-print-btn" style="text-decoration: none;">
                                <span>🖨️ Распечатать на принтере (А4)</span>
                            </a>
                            <button type="button" @click="certOpen = false" class="tutor-cert-close-btn">✕</button>
                        </div>
                    </div>

                    {{-- Печатный бланк свидетельства А4 (High-Prestige Academic Diploma) --}}
                    <div class="tutor-certificate-sheet">
                        <div class="tutor-cert-outer-frame">
                            {{-- Угловые виньетки / филигранные орнаменты --}}
                            <svg class="tutor-cert-corner tutor-cert-corner--tl" viewBox="0 0 44 44" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M2 2H22C22 13.0457 13.0457 22 2 22V2Z" stroke="#C59B27" stroke-width="1.5" fill="none"/>
                                <path d="M4 4H18C18 11.732 11.732 18 4 18V4Z" stroke="#8C6D1F" stroke-width="0.75" fill="rgba(197, 155, 39, 0.08)"/>
                                <circle cx="10" cy="10" r="2.5" fill="#C59B27"/>
                                <path d="M2 30V42H14" stroke="#C59B27" stroke-width="1.5" stroke-linecap="round"/>
                                <path d="M30 2H42V14" stroke="#C59B27" stroke-width="1.5" stroke-linecap="round"/>
                            </svg>
                            <svg class="tutor-cert-corner tutor-cert-corner--tr" viewBox="0 0 44 44" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M2 2H22C22 13.0457 13.0457 22 2 22V2Z" stroke="#C59B27" stroke-width="1.5" fill="none"/>
                                <path d="M4 4H18C18 11.732 11.732 18 4 18V4Z" stroke="#8C6D1F" stroke-width="0.75" fill="rgba(197, 155, 39, 0.08)"/>
                                <circle cx="10" cy="10" r="2.5" fill="#C59B27"/>
                                <path d="M2 30V42H14" stroke="#C59B27" stroke-width="1.5" stroke-linecap="round"/>
                                <path d="M30 2H42V14" stroke="#C59B27" stroke-width="1.5" stroke-linecap="round"/>
                            </svg>
                            <svg class="tutor-cert-corner tutor-cert-corner--bl" viewBox="0 0 44 44" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M2 2H22C22 13.0457 13.0457 22 2 22V2Z" stroke="#C59B27" stroke-width="1.5" fill="none"/>
                                <path d="M4 4H18C18 11.732 11.732 18 4 18V4Z" stroke="#8C6D1F" stroke-width="0.75" fill="rgba(197, 155, 39, 0.08)"/>
                                <circle cx="10" cy="10" r="2.5" fill="#C59B27"/>
                                <path d="M2 30V42H14" stroke="#C59B27" stroke-width="1.5" stroke-linecap="round"/>
                                <path d="M30 2H42V14" stroke="#C59B27" stroke-width="1.5" stroke-linecap="round"/>
                            </svg>
                            <svg class="tutor-cert-corner tutor-cert-corner--br" viewBox="0 0 44 44" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M2 2H22C22 13.0457 13.0457 22 2 22V2Z" stroke="#C59B27" stroke-width="1.5" fill="none"/>
                                <path d="M4 4H18C18 11.732 11.732 18 4 18V4Z" stroke="#8C6D1F" stroke-width="0.75" fill="rgba(197, 155, 39, 0.08)"/>
                                <circle cx="10" cy="10" r="2.5" fill="#C59B27"/>
                                <path d="M2 30V42H14" stroke="#C59B27" stroke-width="1.5" stroke-linecap="round"/>
                                <path d="M30 2H42V14" stroke="#C59B27" stroke-width="1.5" stroke-linecap="round"/>
                            </svg>

                            <div class="tutor-cert-inner-frame">
                                {{-- Защитный водяной знак герба --}}
                                <svg class="tutor-cert-watermark" viewBox="0 0 200 200" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <circle cx="100" cy="100" r="94" stroke="#8C6D1F" stroke-width="2" stroke-dasharray="4 4"/>
                                    <circle cx="100" cy="100" r="82" stroke="#8C6D1F" stroke-width="1"/>
                                    <polygon points="100,28 118,72 166,74 128,104 142,150 100,124 58,150 72,104 34,74 82,72" fill="#C59B27"/>
                                </svg>

                                {{-- Верхняя гербовая часть --}}
                                <div class="tutor-cert-header">
                                    <div class="tutor-cert-crest-wrap">
                                        <svg width="48" height="48" viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            {{-- Лавровый венок и колоски --}}
                                            <path d="M14 44C11 36 12 24 20 16C23 12 28 8 32 6C36 8 41 12 44 16C52 24 53 36 50 44C46 51 39 55 32 56C25 55 18 51 14 44Z" stroke="#C59B27" stroke-width="2" fill="rgba(254, 243, 199, 0.4)"/>
                                            {{-- Лента цветов флага Беларуси --}}
                                            <path d="M20 50C24 52 28 53 32 53C36 53 40 52 44 50V54C40 56 36 57 32 57C28 57 24 56 20 54V50Z" fill="#DC2626"/>
                                            <path d="M20 54C24 56 28 57 32 57C36 57 40 56 44 54V56C40 58 36 59 32 59C28 59 24 58 20 56V54Z" fill="#16A34A"/>
                                            {{-- Книга мудрости и звезда --}}
                                            <path d="M24 38C28 36 32 37 32 37C32 37 36 36 40 38V27C36 25 32 26 32 26C32 26 28 25 24 27V38Z" fill="#C59B27"/>
                                            <polygon points="32,15 34,19 38,19 35,21 36,25 32,23 28,25 29,21 26,19 30,19" fill="#B45309"/>
                                        </svg>
                                    </div>
                                    <div class="tutor-cert-state-line">РЕСПУБЛИКА БЕЛАРУСЬ</div>
                                    <div class="tutor-cert-committee-line">ОБЩЕСТВЕННЫЙ МЕТОДИЧЕСКИЙ СОВЕТ EDUSFERA</div>

                                    <div class="tutor-cert-divider-ornament">
                                        <div class="tutor-cert-divider-line"></div>
                                        <div class="tutor-cert-divider-star">★ &nbsp; ★ &nbsp; ★</div>
                                        <div class="tutor-cert-divider-line"></div>
                                    </div>

                                    <h1 class="tutor-cert-main-title">СВИДЕТЕЛЬСТВО</h1>
                                    <div class="tutor-cert-main-subtitle">О ПРИСВОЕНИИ СТАТУСА ЭКСПЕРТА МЕТОДИЧЕСКОГО СОВЕТА</div>

                                    <div class="tutor-cert-meta-strip">
                                        <span class="tutor-cert-meta-item">СЕРИЯ: <b>ЭС-2026</b></span>
                                        <span style="color:#C59B27;">•</span>
                                        <span class="tutor-cert-meta-item">РЕГ. НОМЕР: <b>{{ $councilCertNumber }}</b></span>
                                        <span style="color:#C59B27;">•</span>
                                        <span class="tutor-cert-meta-item">РЕЕСТР: <b>АКТИВЕН (БЕССРОЧНО)</b></span>
                                    </div>
                                </div>

                                {{-- Содержание свидетельства --}}
                                <div class="tutor-cert-content-body">
                                    <div class="tutor-cert-intro-phrase">
                                        Настоящим решением Экспертно-методической коллегии удостоверяется, что
                                    </div>

                                    <div class="tutor-cert-recipient-name">
                                        {{ $tutorFullName }}
                                    </div>

                                    <div class="tutor-cert-recipient-underline">
                                        <div class="tutor-cert-recipient-line"></div>
                                        <div class="tutor-cert-recipient-diamond"></div>
                                        <div class="tutor-cert-recipient-line"></div>
                                    </div>

                                    <div style="font-size: 14.5px; color: #334155;">
                                        аккредитован(а) в качестве ведущего наставника и включен(а) в состав <strong>Общественного методического совета платформы Edusfera</strong> с наделением постоянным статусом:
                                    </div>

                                    <div class="tutor-cert-status-callout">
                                        <div class="tutor-cert-status-callout-text">
                                            «ВЕДУЩИЙ ЭКСПЕРТ ВЫСШЕЙ ПЕДАГОГИЧЕСКОЙ КВАЛИФИКАЦИИ»
                                        </div>
                                    </div>

                                    <div class="tutor-cert-legal-desc">
                                        Свидетельство удостоверяет высокий уровень профессионального педагогического мастерства, экспертное участие в апробации отечественного цифрового класса, стандартов конфиденциальности «Приватный кабинет», эргономического режима «Комфортное зрение 50+» и методическое руководство качеством дистанционного образования в Республике Беларусь.
                                    </div>


                                </div>

                                {{-- Блок подписи, гербовой печати и верификации --}}
                                <div class="tutor-cert-credentials-grid">
                                    {{-- Левая колонка: Подпись председателя оргкомитета --}}
                                    <div class="tutor-cert-sign-col">
                                        <div class="tutor-cert-sign-role">Председатель оргкомитета Edusfera:</div>
                                        <div class="tutor-cert-facsimile-box">
                                            {{-- Элегантное факсимиле подписи чернилами --}}
                                            <svg width="150" height="44" viewBox="0 0 180 50" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M12 38C22 18 36 12 44 24C50 32 45 44 38 42C30 40 28 28 38 18C48 8 62 14 70 28C74 35 80 40 88 38C96 36 102 26 108 22C114 18 126 28 135 24C142 21 148 16 162 14" stroke="#1D4ED8" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>
                                                <path d="M28 26C45 22 75 25 110 20C130 17 155 18 172 15" stroke="#1D4ED8" stroke-width="1.4" stroke-linecap="round"/>
                                                <path d="M72 16L78 34M84 15L90 32" stroke="#1D4ED8" stroke-width="1.6" stroke-linecap="round"/>
                                            </svg>
                                        </div>
                                        <div class="tutor-cert-sign-printed-name">
                                            С.С. Башмаков
                                        </div>
                                    </div>

                                    {{-- Центральная колонка: 3D рельефная золотая сургучная медаль --}}
                                    <div class="tutor-cert-seal-col">
                                        <div class="tutor-cert-gold-medal">
                                            <div class="tutor-cert-gold-medal-inner">
                                                <div class="tutor-cert-medal-star">★</div>
                                                <div class="tutor-cert-medal-txt-top">ОБЩЕСТВЕННЫЙ</div>
                                                <div class="tutor-cert-medal-txt-main">МЕТОДИЧЕСКИЙ СОВЕТ</div>
                                                <div class="tutor-cert-medal-txt-sub">★ EDUSFERA ★</div>
                                            </div>
                                        </div>
                                        {{-- Атласные ленты --}}
                                        <div class="tutor-cert-ribbons">
                                            <div class="tutor-cert-ribbon-tail"></div>
                                            <div class="tutor-cert-ribbon-tail tutor-cert-ribbon-tail--green"></div>
                                        </div>
                                    </div>

                                    {{-- Правая колонка: дата и место выдачи --}}
                                    <div class="tutor-cert-verify-col">
                                        <div class="tutor-cert-issue-info">
                                            <div>г. Минск, Республика Беларусь</div>
                                            <div>Дата выдачи: <b>{{ $councilJoinedAt ? $councilJoinedAt->translatedFormat('d F Y г.') : now()->translatedFormat('d F Y г.') }}</b></div>
                                            <div style="color:#059669; font-weight:700; margin-top:2px;">✓ Действительно бессрочно</div>
                                        </div>
                                    </div>
                                </div>

                                {{-- Нижняя полоса микрозащиты --}}
                                <div class="tutor-cert-bottom-security-bar">
                                    <span>EDUSFERA DIGITAL EDUCATION SYSTEM</span>
                                    <span>★ ОФИЦИАЛЬНЫЙ АТТЕСТАЦИОННЫЙ РЕЕСТР ПЕДАГОГОВ ★</span>
                                    <span>РЕСПУБЛИКА БЕЛАРУСЬ</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @else
        <div class="tutor-council-invite-card">
            <div class="tutor-council-invite-header">
                <div class="tutor-council-invite-badge">
                    <span>🏛️</span>
                    <span>Общественный методический совет Edusfera</span>
                    <span class="tutor-council-quota-pill">Закрытая квота: осталось {{ $quotaLeft }} из {{ $quotaTotal }} мест</span>
                </div>
            </div>
            <div class="tutor-council-invite-body">
                <h3 class="tutor-council-invite-title">Примите работу у белорусских разработчиков</h3>
                <p class="tutor-council-invite-desc">
                    Мы создали режим «Комфортное зрение 50+» и «Приватный кабинет» для спокойной работы со своими учениками без посторонних. Нам необходима профессиональная оценка педагогов высшей квалификации со стажем. Войдите в состав Экспертного совета: получите бессрочный статус с официальным свидетельством для аттестационного портфолио и 30 дней тарифа PRO (40 BYN) в подарок.
                </p>
                <div class="tutor-council-invite-actions">
                    <button 
                        type="button" 
                        wire:click="joinCouncil" 
                        wire:loading.attr="disabled"
                        class="tutor-council-accept-btn"
                    >
                        <span wire:loading.remove wire:target="joinCouncil" style="display:inline-flex; align-items:center; gap:8px;">
                            <span>🎖️</span>
                            <span>Войти в Методический совет и активировать 30 дней PRO</span>
                        </span>
                        <span wire:loading wire:target="joinCouncil">Оформление свидетельства...</span>
                    </button>
                    <span class="tutor-council-guarantee-note">✓ 1 клик · 0 BYN · Без автосписаний · Официальное свидетельство</span>
                </div>
            </div>
        </div>
    @endif

    {{-- Персональная ссылка для записи --}}
    <div class="tutor-hero-booking-bar" x-data="{ copied: false }">
        <div class="tutor-hero-booking-left">
            <svg class="tutor-hero-booking-icon" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 0 1 1.242 7.244l-4.5 4.5a4.5 4.5 0 0 1-6.364-6.364l1.757-1.757m13.35-.622 1.757-1.757a4.5 4.5 0 0 0-6.364-6.364l-4.5 4.5a4.5 4.5 0 0 0 1.242 7.244" />
            </svg>
            <span class="tutor-hero-booking-label">Ссылка для записи:</span>
            <a href="{{ $bookingUrl }}" target="_blank" class="tutor-hero-booking-url">{{ $bookingUrl }}</a>
        </div>
        <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
            <a href="viber://forward?text={{ rawurlencode("Здравствуйте! Вы можете записаться на мои занятия по ссылке: {$bookingUrl}") }}" 
               class="tutor-hero-messenger-mini tutor-hero-messenger-mini--viber" 
               title="Отправить ссылку в Viber в 1 клик">
                <span>📲 Viber</span>
            </a>
            <a href="https://t.me/share/url?url={{ rawurlencode($bookingUrl) }}&text={{ rawurlencode("Здравствуйте! Ссылка для записи на мои занятия в Edusfera:") }}" 
               target="_blank" 
               class="tutor-hero-messenger-mini tutor-hero-messenger-mini--telegram" 
               title="Отправить ссылку в Telegram в 1 клик">
                <span>✈️ Telegram</span>
            </a>
            <button 
                type="button" 
                class="tutor-hero-copy-btn" 
                :class="{ 'tutor-hero-copy-btn--copied': copied }"
                @click="navigator.clipboard.writeText('{{ $bookingUrl }}'); copied = true; setTimeout(() => copied = false, 2500)"
                title="Скопировать ссылку в буфер обмена"
            >
                <template x-if="!copied">
                    <span style="display:inline-flex; align-items:center; gap:6px;">
                        <svg style="width:15px; height:15px;" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 17.25v3.375c0 .621-.504 1.125-1.125 1.125h-9.75a1.125 1.125 0 0 1-1.125-1.125V7.875c0-.621.504-1.125 1.125-1.125H6.75a9.06 9.06 0 0 1 1.5.124m7.5 10.376h3.375c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9 9 9 0 0 0-9 9v.375c0 .621.504 1.125 1.125 1.125H6.75m9 6.75v-3.75A2.25 2.25 0 0 0 13.5 12h-3a2.25 2.25 0 0 0-2.25 2.25v3.75m9 0H7.5"/></svg>
                        <span>Скопировать</span>
                    </span>
                </template>
                <template x-if="copied">
                    <span style="display:inline-flex; align-items:center; gap:6px;">
                        <svg style="width:15px; height:15px;" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                        <span>Скопировано!</span>
                    </span>
                </template>
            </button>
        </div>
    </div>

    @if ($lesson)
        {{-- Карточка ближайшего урока: главный фокус экрана --}}
        <div class="tutor-hero-card {{ $meetingJoinAvailable ? 'tutor-hero-card--live' : '' }}">
            <div class="tutor-hero-status">
                <span class="tutor-hero-dot" x-show="joinAvailable" x-cloak></span>
                <span x-show="!hydrated">Ближайший урок · {{ $meetingJoinAvailable ? 'идёт сейчас' : 'скоро' }}</span>
                <span x-show="hydrated && !started" x-cloak>Ближайший урок · <span style="color:#7D39EB" x-text="countdown"></span></span>
                <span x-show="hydrated && started && !finished" x-cloak style="color:#059669">Урок идёт сейчас</span>
                <span x-show="hydrated && finished" x-cloak>Урок завершается</span>
            </div>

            @php
                $studentName = $lesson->student?->name ?? $lesson->parent?->name ?? 'Ученик';
                $studentInitial = mb_strtoupper(mb_substr($studentName, 0, 1));
                $studentUser = $lesson->student ?? $lesson->parent;
                $lessonInviteText = "Здравствуйте, {$studentName}! Ссылка на наш онлайн-урок в Edusfera: {$classroomUrl}. Вход в один клик без регистрации.";
            @endphp

            <div class="tutor-hero-info" style="align-items: center; gap: 16px;">
                <div style="display: flex; align-items: center; gap: 14px; min-width: 0;">
                    <div class="student-tutor-avatar-wrap">
                        <div class="student-tutor-avatar-lg" style="width: 50px; height: 50px; font-size: 19px; background: linear-gradient(135deg, #10B981, #059669);">
                            {{ $studentInitial }}
                        </div>
                        <span class="student-tutor-online-badge"></span>
                    </div>
                    <div style="min-width: 0;">
                        <p class="tutor-hero-name" style="margin: 0;">{{ $studentName }}</p>
                        <p class="tutor-hero-sub" style="margin: 3px 0 0;">{{ $lesson->package_label }} · {{ $lesson->duration_minutes ?? 60 }} мин</p>
                    </div>
                </div>

                <div style="text-align: right;">
                    <p class="tutor-hero-time" style="margin: 0; color: #7D39EB; font-weight: 800;">
                        🕒 {{ $lesson->start_time->timezone($tz)->isSameDay(now($tz)) ? 'сегодня' : $lesson->start_time->timezone($tz)->translatedFormat('j F') }},
                        {{ $lesson->start_time->timezone($tz)->translatedFormat('H:i') }}–{{ $lesson->end_time->timezone($tz)->translatedFormat('H:i') }}
                    </p>
                </div>
            </div>

            {{-- Быстрая отправка ссылки ученику в 1 клик для 50+ --}}
            @if ($classroomUrl)
                <div style="display: flex; align-items: center; justify-content: space-between; gap: 10px; margin-top: 14px; padding-top: 12px; border-top: 1px solid rgba(0,0,0,0.06); flex-wrap: wrap;" class="dark:border-white/10">
                    <span style="font-size: 0.8125rem; font-weight: 700; color: #475569;" class="dark:text-slate-300">
                        Отправить ссылку ученику в мессенджер:
                    </span>
                    <div style="display: flex; align-items: center; gap: 6px;">
                        <a href="viber://forward?text={{ rawurlencode($lessonInviteText) }}" 
                           class="tutor-hero-messenger-mini tutor-hero-messenger-mini--viber" 
                           title="Отправить готовое приглашение в Viber в 1 клик">
                            <span>📲 В Viber</span>
                        </a>
                        <a href="https://t.me/share/url?url={{ rawurlencode($classroomUrl) }}&text={{ rawurlencode("Здравствуйте, {$studentName}! Ссылка на наш онлайн-урок (вход без регистрации):") }}" 
                           target="_blank" 
                           class="tutor-hero-messenger-mini tutor-hero-messenger-mini--telegram" 
                           title="Отправить готовое приглашение в Telegram в 1 клик">
                            <span>✈️ В Telegram</span>
                        </a>
                    </div>
                </div>
            @endif

            <div class="tutor-cta-row">
                @if ($meetingJoinAvailable && $classroomUrl)
                    <a href="{{ $classroomUrl }}" target="_blank" class="tutor-cta tutor-cta--lime">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m15.75 10.5 4.72-4.72a.75.75 0 0 1 1.28.53v11.38a.75.75 0 0 1-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 0 0 2.25-2.25v-9a2.25 2.25 0 0 0-2.25-2.25h-9A2.25 2.25 0 0 0 2.25 7.5v9a2.25 2.25 0 0 0 2.25 2.25Z"/></svg>
                        Войти в класс
                    </a>
                @elseif ($classroomUrl)
                    <a href="{{ $classroomUrl }}" class="tutor-cta tutor-cta--primary">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5"/></svg>
                        Карточка урока
                    </a>
                @endif

                @if($studentUser)
                    <a href="/admin/messages?user={{ $studentUser->id }}" class="tutor-cta tutor-cta--ghost" style="display: inline-flex; align-items: center; gap: 6px;">
                        <span>💬</span>
                        <span>Чат</span>
                    </a>
                @endif

                <a href="/admin/lessons" class="tutor-cta tutor-cta--ghost">Все уроки</a>
            </div>
        </div>
    @else
        {{-- Свободный день --}}
        <div class="tutor-hero-card tutor-hero-card--calm">
            <p class="tutor-hero-name">У вас пока нет уроков на сегодня</p>
            <p class="tutor-hero-text">
                Предстоящих уроков нет. Откройте календарь и отметьте свободные часы — так ученики смогут записаться к вам на занятие.
            </p>
            <div class="tutor-cta-row">
                <a href="/admin/tutor-availability" class="tutor-cta tutor-cta--primary" style="display:inline-flex; align-items:center; gap:6px;">
                    <svg style="width:16px; height:16px;" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                    <span>Открыть свободные часы</span>
                </a>
                <a href="/admin/lessons" class="tutor-cta tutor-cta--ghost">Журнал уроков</a>
            </div>
        </div>
    @endif

    @if ($newRequestsCount > 0)
        <a href="/admin/lesson-requests" class="tutor-hero-alert">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0"/></svg>
            <span><strong>{{ $newRequestsCount }} {{ trans_choice('новая заявка|новые заявки|новых заявок', $newRequestsCount) }}</strong></span>
            <span class="tutor-alert-note">— ждёт вашего ответа</span>
            <svg class="tutor-alert-chevron" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
        </a>
    @endif
</div>
{{-- Дизайн-система кабинета подключается через renderHook PAGE_START
     (см. AdminPanelProvider): @once внутри Livewire-виджетов ненадёжен. --}}
