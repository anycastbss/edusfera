@php
    $isDark = ($theme ?? null) === 'dark';
@endphp

<footer class="site-footer {{ $isDark ? 'site-footer--dark' : 'site-footer--light' }}">
    <div class="site-footer__container">
        
        {{-- Main Navigation Grid --}}
        <div class="site-footer__grid">
            
            {{-- Column 1: Brand & Identity --}}
            <div class="site-footer__col-brand">
                <a href="{{ route('home') }}" class="site-footer__brand-link">
                    <div class="site-footer__brand-icon">
                        <svg width="15" height="15" viewBox="0 0 64 64">
                            <path d="M32 10L54 32L32 54L10 32L32 10Z" fill="none" stroke="#C6FF33" stroke-width="6" stroke-linejoin="round" />
                            <path d="M32 22L42 32L32 42L22 32L32 22Z" fill="#C6FF33" />
                        </svg>
                    </div>
                    <span class="site-footer__brand-name">EDUSFERA</span>
                </a>
                
                <p class="site-footer__brand-desc">
                    Белорусская цифровая платформа (SaaS) для репетиторов, учеников и родителей. Виртуальный класс, чеки для самозанятых (НПД) и 0% комиссии с уроков.
                </p>

                <div class="site-footer__socials">
                    <a href="https://t.me/edusfera" target="_blank" rel="noreferrer" class="site-footer__social-btn" aria-label="Telegram">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="22" y1="2" x2="11" y2="13"></line>
                            <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
                        </svg>
                    </a>
                    <a href="https://instagram.com" target="_blank" rel="noreferrer" class="site-footer__social-btn" aria-label="Instagram">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="2" y="2" width="20" height="20" rx="5" ry="5"></rect>
                            <path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"></path>
                            <line x1="17.5" y1="6.5" x2="17.51" y2="6.5"></line>
                        </svg>
                    </a>
                    <a href="https://youtube.com" target="_blank" rel="noreferrer" class="site-footer__social-btn" aria-label="YouTube">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M22.54 6.42a2.78 2.78 0 0 0-1.94-2C18.88 4 12 4 12 4s-6.88 0-8.6.46a2.78 2.78 0 0 0-1.94 2A29 29 0 0 0 1 11.75a29 29 0 0 0 .46 5.33A2.78 2.78 0 0 0 3.4 19c1.72.46 8.6.46 8.6.46s6.88 0 8.6-.46a2.78 2.78 0 0 0 1.94-2 29 29 0 0 0 .46-5.25 29 29 0 0 0-.46-5.33z"></path>
                            <polygon points="9.75 15.02 15.5 11.75 9.75 8.48 9.75 15.02"></polygon>
                        </svg>
                    </a>
                </div>
            </div>

            {{-- Column 2: Платформа --}}
            <div class="site-footer__col">
                <h4 class="site-footer__col-title">Платформа</h4>
                <ul class="site-footer__links">
                    <li><a href="{{ route('tutors.index') }}">Каталог репетиторов</a></li>
                    <li>
                        <a href="{{ route('generator') }}" class="site-footer__badge-link">
                            <span>Генератор вариантов</span>
                            <span class="site-footer__tag" style="background: #C6FF33; color: #000; font-weight: 800;">5 BYN</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('diagnostic.show') }}" class="site-footer__badge-link">
                            <span>ИИ-Диагностика знаний</span>
                            <span class="site-footer__tag">ЦТ/ЦЭ</span>
                        </a>
                    </li>
                    <li><a href="{{ route('for-tutors') }}">Преподавателям</a></li>
                    <li><a href="{{ route('news.index') }}">Новости и статьи</a></li>
                    <li><a href="{{ route('about') }}">О компании</a></li>
                </ul>
            </div>

            {{-- Column 3: Документы --}}
            <div class="site-footer__col">
                <h4 class="site-footer__col-title">Документы</h4>
                <ul class="site-footer__links">
                    <li><a href="{{ route('legal.offer') }}">Публичная оферта и тарифы</a></li>
                    <li><a href="{{ route('legal.privacy') }}">Конфиденциальность (Закон 99-З)</a></li>
                    <li><a href="{{ route('legal.cookie') }}">Политика файлов cookie</a></li>
                    <li><a href="{{ route('legal.payment-security') }}">Безопасность платежей</a></li>
                    <li><a href="{{ route('legal.refund') }}">Правила возврата и отмены</a></li>
                </ul>
            </div>

            {{-- Column 4: Связь и поддержка --}}
            <div class="site-footer__col">
                <h4 class="site-footer__col-title">Поддержка</h4>
                <ul class="site-footer__links">
                    <li>
                        <a href="tel:+375295190821" class="site-footer__phone">
                            +375 (29) 519-08-21
                        </a>
                    </li>
                    <li>
                        <a href="mailto:edusferaby@gmail.com" class="site-footer__email">
                            edusferaby@gmail.com
                        </a>
                    </li>
                    <li class="site-footer__hours">
                        Пн–Пт 09:00 – 18:00 (Минск)
                    </li>
                    <li class="site-footer__contacts-li">
                        <a href="{{ route('contacts') }}" class="site-footer__requisites-link">
                            Контакты и реквизиты →
                        </a>
                    </li>
                </ul>
            </div>

        </div>

        {{-- Single-Row Compact Payment Badges --}}
        <div class="site-footer__payments-section">
            <span class="site-footer__payments-title">
                Эквайринг ЗАО «Альфа-Банк»:
            </span>
            <div class="site-footer__payments-grid">
                <div class="site-footer__pay-badge" title="ЗАО «Альфа-Банк»">
                    <img src="/logo/alfa-bank.svg" alt="ЗАО «Альфа-Банк»" class="site-footer__pay-img site-footer__pay-img--alfa">
                </div>
                <div class="site-footer__pay-badge" title="БЕЛКАРТ">
                    <img src="/logo/belkart.png" alt="БЕЛКАРТ" class="site-footer__pay-img site-footer__pay-img--belkart">
                </div>
                <div class="site-footer__pay-badge" title="Белкарт ИнтернетПароль">
                    <img src="/logo/belkart-internetparol.svg" alt="Белкарт ИнтернетПароль" class="site-footer__pay-img site-footer__pay-img--belkart-ip">
                </div>
                <div class="site-footer__pay-badge" title="VISA">
                    <img src="/logo/visa.svg" alt="VISA" class="site-footer__pay-img site-footer__pay-img--visa">
                </div>
                <div class="site-footer__pay-badge" title="Visa Secure">
                    <img src="/logo/visa-secure.svg" alt="Visa Secure" class="site-footer__pay-img site-footer__pay-img--visa-sec">
                </div>
                <div class="site-footer__pay-badge" title="MasterCard">
                    <img src="/logo/mastercard.svg" alt="MasterCard" class="site-footer__pay-img site-footer__pay-img--mc">
                </div>
                <div class="site-footer__pay-badge" title="Mastercard Identity Check">
                    <img src="/logo/mastercard-id-check.svg" alt="Mastercard Identity Check" class="site-footer__pay-img site-footer__pay-img--mc-id">
                </div>
                <div class="site-footer__pay-badge" title="Apple Pay">
                    <img src="/logo/apple-pay.svg" alt="Apple Pay" class="site-footer__pay-img site-footer__pay-img--apple">
                </div>
                <div class="site-footer__pay-badge" title="Samsung Pay">
                    <img src="/logo/samsung-pay.svg" alt="Samsung Pay" class="site-footer__pay-img site-footer__pay-img--samsung">
                </div>
            </div>
        </div>

        {{-- Legal & Compliance text --}}
        <div class="site-footer__legal-section">
            <p>
                <strong>ООО «Эдусфера»</strong> · УНП 192854899 · Зарегистрировано Минским горисполкомом 04.05.2026 г. Юридический адрес: 220100, Республика Беларусь, г. Минск, ул. Веры Хоружей, д. 6А, пом. 29. Режим работы: Пн–Пт 09:00 – 18:00.
            </p>
            <p>
                ООО «Эдусфера» предоставляет программную платформу (SaaS) для репетиторов и не является учреждением образования. Образовательные услуги оказываются непосредственно преподавателями. Передача данных защищена TLS/SSL и 3D-Secure 2.0 (PCI DSS) через эквайринг ЗАО «Альфа-Банк».
            </p>
        </div>

        {{-- Bottom Copyright Line --}}
        <div class="site-footer__bottom-bar">
            <p class="site-footer__copyright">
                © {{ date('Y') }} ООО «Эдусфера». Все права защищены.
            </p>
            <div class="site-footer__bottom-links">
                <a href="{{ route('legal.offer') }}">Оферта</a>
                <span class="site-footer__dot-sep">·</span>
                <a href="{{ route('legal.privacy') }}">Конфиденциальность</a>
                <span class="site-footer__dot-sep">·</span>
                <a href="{{ route('legal.refund') }}">Возвраты</a>
                <span class="site-footer__dot-sep">·</span>
                <span class="site-footer__country">Беларусь</span>
            </div>
        </div>
    </div>
</footer>

@include('partials.cookie-banner')

<style>
/* ========================================================
   ENCAPSULATED COMPACT MODERN FOOTER (Clean Apple / Linear)
   ======================================================== */
.site-footer {
    width: 100%;
    font-family: -apple-system, BlinkMacSystemFont, 'Inter', 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
    -webkit-font-smoothing: antialiased;
    box-sizing: border-box;
}

/* Light Theme */
.site-footer--light {
    background-color: #0b0f17;
    color: #86868b;
    border-top: 1px solid rgba(255,255,255,0.08);
}
.site-footer--light a {
    color: #86868b;
    text-decoration: none;
    transition: color 0.15s ease;
}
.site-footer--light a:hover {
    color: #ffffff;
}

/* Dark Theme */
.site-footer--dark {
    background-color: #050507;
    color: #86868b;
    border-top: 1px solid rgba(255,255,255,0.08);
}
.site-footer--dark a {
    color: #86868b;
    text-decoration: none;
    transition: color 0.15s ease;
}
.site-footer--dark a:hover {
    color: #ffffff;
}

/* Layout Core */
.site-footer__container {
    max-width: 1120px;
    margin: 0 auto;
    padding: 2.5rem 1.5rem 2rem;
    box-sizing: border-box;
}

.site-footer__grid {
    display: grid;
    grid-template-columns: 2.2fr 1fr 1.2fr 1.1fr;
    gap: 2rem;
    align-items: start;
    box-sizing: border-box;
    padding-bottom: 2rem;
    border-bottom: 1px solid rgba(255,255,255,0.08);
}

.site-footer__col-brand {
    box-sizing: border-box;
}

.site-footer__brand-link {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    text-decoration: none;
    margin-bottom: 0.75rem;
}

.site-footer__brand-icon {
    width: 24px;
    height: 24px;
    border-radius: 6px;
    background: #7D39EB;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.site-footer__brand-name {
    font-size: 1rem;
    font-weight: 800;
    letter-spacing: -0.02em;
    text-transform: uppercase;
    color: #ffffff;
}

.site-footer__brand-desc {
    font-size: 12px;
    line-height: 1.55;
    max-width: 340px;
    margin: 0 0 0.85rem 0;
    color: #86868b;
}

.site-footer__socials {
    display: flex;
    align-items: center;
    gap: 6px;
}

.site-footer__social-btn {
    width: 28px;
    height: 28px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 6px;
    background: rgba(255,255,255,0.04);
    border: 1px solid rgba(255,255,255,0.08);
    color: #86868b;
    transition: all 0.15s ease;
}
.site-footer__social-btn:hover {
    background: rgba(255,255,255,0.1);
    color: #ffffff;
}

.site-footer__col {
    box-sizing: border-box;
}

.site-footer__col-title {
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    margin: 0 0 0.85rem 0;
    color: #ffffff;
}

.site-footer__links {
    list-style: none;
    margin: 0;
    padding: 0;
    display: flex;
    flex-direction: column;
    gap: 0.55rem;
    font-size: 12px;
    line-height: 1.35;
}

.site-footer__badge-link {
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

.site-footer__tag {
    font-size: 9px;
    font-weight: 700;
    padding: 1px 5px;
    border-radius: 4px;
    letter-spacing: 0.03em;
    background: rgba(125,57,235,0.25);
    color: #c4b5fd;
}

.site-footer__phone {
    font-weight: 700;
    font-size: 12.5px;
    color: #ffffff !important;
}
.site-footer__phone:hover {
    color: #C6FF33 !important;
}

.site-footer__email {
    font-size: 12px;
}

.site-footer__hours {
    font-size: 11px;
    color: #6e6e73;
    padding-top: 1px;
}

.site-footer__contacts-li {
    padding-top: 4px;
}

.site-footer__requisites-link {
    font-size: 11.5px;
    font-weight: 600;
    color: #C6FF33 !important;
}
.site-footer__requisites-link:hover {
    text-decoration: underline;
}

/* Single-Row Compact Payment Badges */
.site-footer__payments-section {
    padding: 1.25rem 0;
    border-bottom: 1px solid rgba(255,255,255,0.08);
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
    box-sizing: border-box;
}

.site-footer__payments-title {
    font-size: 11px;
    color: #86868b;
    font-weight: 500;
    white-space: nowrap;
}

.site-footer__payments-grid {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 6px;
    box-sizing: border-box;
}

.site-footer__pay-badge {
    height: 24px;
    padding: 0 8px;
    border-radius: 6px;
    background: rgba(255,255,255,0.95);
    border: 1px solid rgba(255,255,255,0.2);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    box-sizing: border-box;
    cursor: default;
}

.site-footer__pay-img {
    display: block;
    object-fit: contain;
}
.site-footer__pay-img--alfa { height: 15px; max-width: 80px; }
.site-footer__pay-img--belkart { height: 14px; max-width: 65px; }
.site-footer__pay-img--belkart-ip { height: 14px; max-width: 68px; }
.site-footer__pay-img--visa { height: 13px; max-width: 48px; }
.site-footer__pay-img--visa-sec { height: 14px; max-width: 60px; }
.site-footer__pay-img--mc { height: 15px; max-width: 42px; }
.site-footer__pay-img--mc-id { height: 14px; max-width: 60px; }
.site-footer__pay-img--apple { height: 13px; max-width: 42px; }
.site-footer__pay-img--samsung { height: 13px; max-width: 52px; }

/* Legal & Compliance */
.site-footer__legal-section {
    padding: 1rem 0;
    border-bottom: 1px solid rgba(255,255,255,0.08);
    font-size: 10.5px;
    line-height: 1.5;
    color: #6e6e73;
    box-sizing: border-box;
}
.site-footer__legal-section p {
    margin: 0 0 0.35rem 0;
}
.site-footer__legal-section p:last-child {
    margin-bottom: 0;
}
.site-footer__legal-section strong {
    color: #86868b;
}

/* Bottom Bar */
.site-footer__bottom-bar {
    padding-top: 1rem;
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
    font-size: 11px;
    color: #6e6e73;
    box-sizing: border-box;
}

.site-footer__copyright {
    margin: 0;
}

.site-footer__bottom-links {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 0.65rem;
    color: #86868b;
}

.site-footer__dot-sep {
    color: rgba(255,255,255,0.15);
}

.site-footer__country {
    color: rgba(255,255,255,0.7);
}

/* Responsive adjustments */
@media (max-width: 960px) {
    .site-footer__grid {
        grid-template-columns: 1fr 1fr;
        gap: 1.75rem;
    }
    .site-footer__col-brand {
        grid-column: 1 / -1;
    }
}

@media (max-width: 640px) {
    .site-footer__container {
        padding: 2rem 1rem 5.5rem;
    }
    .site-footer__grid {
        grid-template-columns: 1fr;
        gap: 1.5rem;
    }
    .site-footer__payments-section {
        flex-direction: column;
        align-items: flex-start;
    }
    .site-footer__bottom-bar {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.5rem;
    }
}
</style>