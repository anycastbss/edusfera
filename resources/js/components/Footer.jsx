import React from 'react';
import { 
  ArrowUpRight, 
  Mail, 
  Phone, 
  Clock
} from 'lucide-react';

/* ─── Compact Modern Footer ─── */
export default function Footer() {
  const platformLinks = [
    { label: 'Каталог репетиторов', href: '/tutors' },
    { label: 'Генератор вариантов ЦТ/ЦЭ', href: '/generator', badge: '5 BYN', isHighlight: true },
    { label: 'ИИ-Диагностика знаний', href: '/diagnostic' },
    { label: 'Преподавателям', href: '/for-tutors' },
    { label: 'Новости и статьи', href: '/news' },
  ];

  const legalLinks = [
    { label: 'Публичная оферта', href: '/offer' },
    { label: 'Конфиденциальность (Закон 99-З)', href: '/privacy-policy' },
    { label: 'Политика файлов cookie', href: '/cookie-policy' },
    { label: 'Безопасность платежей', href: '/payment-security' },
    { label: 'Правила возврата', href: '/refund-policy' },
  ];

  const socialLinks = [
    { 
      name: 'Telegram', 
      href: 'https://t.me/edusfera', 
      svg: (
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
          <line x1="22" y1="2" x2="11" y2="13"></line>
          <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
        </svg>
      )
    },
    { 
      name: 'Instagram', 
      href: 'https://instagram.com', 
      svg: (
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
          <rect x="2" y="2" width="20" height="20" rx="5" ry="5"></rect>
          <path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"></path>
          <line x1="17.5" y1="6.5" x2="17.51" y2="6.5"></line>
        </svg>
      )
    },
    { 
      name: 'YouTube', 
      href: 'https://youtube.com', 
      svg: (
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
          <path d="M22.54 6.42a2.78 2.78 0 0 0-1.94-2C18.88 4 12 4 12 4s-6.88 0-8.6.46a2.78 2.78 0 0 0-1.94 2A29 29 0 0 0 1 11.75a29 29 0 0 0 .46 5.33A2.78 2.78 0 0 0 3.4 19c1.72.46 8.6.46 8.6.46s6.88 0 8.6-.46a2.78 2.78 0 0 0 1.94-2 29 29 0 0 0 .46-5.25 29 29 0 0 0-.46-5.33z"></path>
          <polygon points="9.75 15.02 15.5 11.75 9.75 8.48 9.75 15.02"></polygon>
        </svg>
      )
    },
  ];

  const paymentLogos = [
    { src: '/logo/alfa-bank.svg', alt: 'ЗАО «Альфа-Банк»', h: 'h-4', maxW: 'max-w-[85px]' },
    { src: '/logo/belkart.png', alt: 'БЕЛКАРТ', h: 'h-4', maxW: 'max-w-[70px]' },
    { src: '/logo/belkart-internetparol.svg', alt: 'Белкарт ИнтернетПароль', h: 'h-4', maxW: 'max-w-[70px]' },
    { src: '/logo/visa.svg', alt: 'VISA', h: 'h-3.5', maxW: 'max-w-[55px]' },
    { src: '/logo/visa-secure.svg', alt: 'Visa Secure', h: 'h-3.5', maxW: 'max-w-[65px]' },
    { src: '/logo/mastercard.svg', alt: 'MasterCard', h: 'h-4', maxW: 'max-w-[45px]' },
    { src: '/logo/mastercard-id-check.svg', alt: 'Mastercard Identity Check', h: 'h-4', maxW: 'max-w-[65px]' },
    { src: '/logo/apple-pay.svg', alt: 'Apple Pay', h: 'h-3.5', maxW: 'max-w-[45px]' },
    { src: '/logo/samsung-pay.svg', alt: 'Samsung Pay', h: 'h-3.5', maxW: 'max-w-[55px]' },
  ];

  return (
    <footer 
      className="w-full bg-[#050507] border-t border-white/[0.08] text-[#86868b] font-sans antialiased"
      style={{ contentVisibility: 'auto', containIntrinsicSize: '1px 320px' }}
    >
      <div className="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 pt-10 pb-8">
        
        {/* ─── Top 4-Column Directory Grid ─── */}
        <div className="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-12 gap-7 pb-8 border-b border-white/[0.08]">
          
          {/* Column 1: Brand & Identity (lg:col-span-4) */}
          <div className="col-span-2 md:col-span-4 lg:col-span-4 space-y-3">
            <a href="/" className="inline-flex items-center gap-2 text-white hover:opacity-90 transition-opacity">
              <div className="w-6 h-6 rounded-md bg-[#7D39EB] flex items-center justify-center shrink-0 shadow-sm">
                <svg width="15" height="15" viewBox="0 0 64 64">
                  <path d="M32 10L54 32L32 54L10 32L32 10Z" fill="none" stroke="#C6FF33" strokeWidth="6" strokeLinejoin="round" />
                  <path d="M32 22L42 32L32 42L22 32L32 22Z" fill="#C6FF33" />
                </svg>
              </div>
              <span className="text-base font-bold tracking-tight text-white uppercase font-rimma">
                EDUSFERA
              </span>
            </a>

            <p className="text-xs text-[#86868b] leading-relaxed max-w-sm">
              Белорусская платформа для репетиторов, учеников и родителей. Видеокласс без ограничений, чеки для самозанятых (НПД) и 0% комиссии с уроков.
            </p>

            {/* Social Icons */}
            <div className="pt-0.5 flex items-center gap-1.5">
              {socialLinks.map((item) => (
                <a
                  key={item.name}
                  href={item.href}
                  target="_blank"
                  rel="noreferrer"
                  className="w-7 h-7 flex items-center justify-center rounded-md bg-white/[0.04] hover:bg-white/[0.1] border border-white/[0.08] text-[#86868b] hover:text-white transition-all"
                  aria-label={item.name}
                >
                  {item.svg}
                </a>
              ))}
            </div>
          </div>

          {/* Column 2: Платформа (lg:col-span-3) */}
          <div className="col-span-1 md:col-span-2 lg:col-span-3 space-y-2.5">
            <h4 className="text-[11px] font-bold text-white uppercase tracking-wider">
              Платформа
            </h4>
            <ul className="space-y-2 text-xs">
              {platformLinks.map((item) => (
                <li key={item.label}>
                  <a
                    href={item.href}
                    className={`transition-colors inline-flex items-center gap-1.5 ${item.isHighlight ? 'text-white font-medium hover:text-[#C6FF33]' : 'text-[#86868b] hover:text-white'}`}
                  >
                    <span>{item.label}</span>
                    {item.badge && (
                      <span className="text-[9px] font-black px-1.5 py-0.2 rounded bg-[#C6FF33] text-black">
                        {item.badge}
                      </span>
                    )}
                  </a>
                </li>
              ))}
            </ul>
          </div>

          {/* Column 3: Документы (lg:col-span-2) */}
          <div className="col-span-1 md:col-span-2 lg:col-span-2 space-y-2.5">
            <h4 className="text-[11px] font-bold text-white uppercase tracking-wider">
              Документы
            </h4>
            <ul className="space-y-2 text-xs">
              {legalLinks.map((item) => (
                <li key={item.label}>
                  <a
                    href={item.href}
                    className="text-[#86868b] hover:text-white transition-colors"
                  >
                    {item.label}
                  </a>
                </li>
              ))}
            </ul>
          </div>

          {/* Column 4: Связь и поддержка (lg:col-span-3) */}
          <div className="col-span-2 md:col-span-4 lg:col-span-3 space-y-2.5">
            <h4 className="text-[11px] font-bold text-white uppercase tracking-wider">
              Поддержка
            </h4>
            <ul className="space-y-2 text-xs">
              <li>
                <a 
                  href="tel:+375295190821" 
                  className="text-white font-medium hover:text-[#C6FF33] transition-colors inline-flex items-center gap-1.5"
                >
                  <Phone className="w-3.5 h-3.5 text-[#C6FF33] shrink-0" />
                  <span>+375 (29) 519-08-21</span>
                </a>
              </li>
              <li>
                <a 
                  href="mailto:edusferaby@gmail.com" 
                  className="text-[#86868b] hover:text-white transition-colors inline-flex items-center gap-1.5"
                >
                  <Mail className="w-3.5 h-3.5 text-[#86868b] shrink-0" />
                  <span>edusferaby@gmail.com</span>
                </a>
              </li>
              <li className="text-[#6e6e73] inline-flex items-center gap-1.5">
                <Clock className="w-3.5 h-3.5 text-[#6e6e73] shrink-0" />
                <span>Пн–Пт 09:00 – 18:00 (Минск)</span>
              </li>
              <li className="pt-0.5">
                <a 
                  href="/contacts" 
                  className="text-[11px] text-[#C6FF33] hover:underline transition-all inline-flex items-center gap-1"
                >
                  <span>Контакты и реквизиты</span>
                  <span>→</span>
                </a>
              </li>
            </ul>
          </div>

        </div>

        {/* ─── Compact Payment Badges (Single Row) ─── */}
        <div className="py-5 border-b border-white/[0.08] flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
          <span className="text-[11px] text-[#86868b] shrink-0">
            Эквайринг ЗАО «Альфа-Банк»:
          </span>
          <div className="flex flex-wrap items-center gap-1.5">
            {paymentLogos.map((p, idx) => (
              <div 
                key={idx} 
                className="bg-white/95 rounded-md px-2 py-1 flex items-center justify-center h-6 border border-white/20 shadow-sm"
              >
                <img 
                  src={p.src} 
                  alt={p.alt} 
                  loading="lazy" 
                  decoding="async" 
                  className={`${p.h} ${p.maxW} object-contain`} 
                />
              </div>
            ))}
          </div>
        </div>

        {/* ─── Compact Legal Footnote ─── */}
        <div className="py-4 border-b border-white/[0.08] text-[10px] leading-relaxed text-[#6e6e73] space-y-1">
          <p>
            <strong>ООО «Эдусфера»</strong> · УНП 192854899 · Зарегистрировано Минским горисполкомом 04.05.2026 г. Юридический адрес: 220100, Республика Беларусь, г. Минск, ул. В. Хоружей, д. 6А, пом. 29. Режим работы: Пн–Пт 09:00 – 18:00.
          </p>
          <p>
            ООО «Эдусфера» предоставляет программную платформу (SaaS) для репетиторов и не является учреждением образования. Образовательные услуги оказываются непосредственно преподавателями. Передача данных защищена TLS/SSL и технологиями 3D-Secure 2.0 (PCI DSS) через эквайринг ЗАО «Альфа-Банк».
          </p>
        </div>

        {/* ─── Bottom Bar: Copyright & Location ─── */}
        <div className="pt-4 flex flex-col sm:flex-row items-center justify-between gap-2.5 text-[11px] text-[#6e6e73]">
          <div>
            © 2026 ООО «Эдусфера». Все права защищены.
          </div>
          <div className="flex items-center gap-3 text-[#86868b]">
            <a href="/offer" className="hover:text-white transition-colors">Оферта</a>
            <span>·</span>
            <a href="/privacy-policy" className="hover:text-white transition-colors">Конфиденциальность</a>
            <span>·</span>
            <a href="/refund-policy" className="hover:text-white transition-colors">Возвраты</a>
            <span>·</span>
            <span className="text-white/70">Беларусь</span>
          </div>
        </div>

      </div>
    </footer>
  );
}
