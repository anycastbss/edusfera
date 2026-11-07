import React, { useState } from 'react';
import { ChevronDown, HelpCircle, Sparkles, MessageCircle } from 'lucide-react';

export default function FaqSection() {
    const [openIndex, setOpenIndex] = useState(0);

    const faqs = [
        {
            question: 'В каком формате я получу материалы и как быстро?',
            answer: 'Материалы формируются мгновенно (в течение 30 секунд). Вы получаете готовый к печати векторный PDF-файл формата А4 со всеми вариантами и мастер-ключом. Файл доступен для прямого скачивания на странице оплаты, а также автоматически дублируется на ваш Email и сохраняется в личном кабинете Edusfera.'
        },
        {
            question: 'Подойдёт ли файл для обычного школьного чёрно-белого принтера?',
            answer: 'Да, на 100%. Мы специально разработали строгий типографический эко-дизайн: чёткие черные шрифты, выверенные поля под степлер, никаких лишних серых плашек и темных заливок. Ваш школьный лазерный картридж расходуется минимально, а распечатка выглядит как профессиональное типографское издание.'
        },
        {
            question: 'Действительно ли ученики не смогут списать через Photomath или ГДЗ?',
            answer: 'Да. В каждом из 15 вариантов используются уникальные числовые коэффициенты, разные корни уравнений и физические данные при сохранении абсолютно одинаковой математической модели и сложности. В базах готовых домашних заданий (ГДЗ) этих задач нет, а соседа по парте списать не получится — у него совершенно другие ответы.'
        },
        {
            question: 'Как происходит оплата и безопасна ли она?',
            answer: 'Оплата осуществляется через официальный интернет-эквайринг ЗАО «Альфа-Банк». Принимаются карты любых белорусских банков: БЕЛКАРТ, Visa, Mastercard, Apple Pay, Samsung Pay. Передача данных защищена 256-битным шифрованием TLS/SSL и технологией 3D-Secure 2.0. Это строго разовый платёж без автопродлений и скрытых подписок.'
        },
        {
            question: 'Выдаётся ли официальный чек для бухгалтерии или отчётности?',
            answer: 'Да. Деятельность полностью легальна и прозрачна. После завершения платежа на ваш электронный адрес поступает фискальный чек плательщика налога на профессиональный доход (НПД Министерства по налогам и сборам Республики Беларусь) с QR-кодом для проверки.'
        },
        {
            question: 'Что делать, если письмо задержалось или возник вопрос?',
            answer: 'PDF-файл открывается сразу на экране после оплаты. Если вы случайно ошиблись в адресе почты или письмо попало в папку «Спам», просто напишите в нашу службу поддержки в Telegram (@edusfera) — оператор продублирует ваш методический комплект в течение 1–2 минут.'
        }
    ];

    const toggleFaq = (index) => {
        setOpenIndex(openIndex === index ? -1 : index);
    };

    return (
        <section className="w-full max-w-[1240px] mx-auto px-4 sm:px-6 py-12 sm:py-16">
            {/* Header */}
            <div className="text-center max-w-3xl mx-auto mb-10 sm:mb-14 space-y-3">
                <div className="inline-flex items-center gap-2 px-3.5 py-1 rounded-full bg-white/[0.04] border border-white/10 text-xs font-mono text-[#C6FF33]">
                    <HelpCircle className="w-3.5 h-3.5" />
                    <span>ОТВЕТЫ НА ЧАСТЫЕ ВОПРОСЫ ПРЕПОДАВАТЕЛЕЙ</span>
                </div>
                <h2 className="text-2xl sm:text-3xl lg:text-4xl font-extrabold text-white tracking-tight">
                    Всё, что важно знать перед покупкой
                </h2>
                <p className="text-sm sm:text-base text-slate-400 font-normal leading-relaxed">
                    Отвечаем на главные вопросы об оплате, формате материалов и юридических гарантиях в Беларуси.
                </p>
            </div>

            {/* Accordion Container */}
            <div className="max-w-3xl mx-auto space-y-3 sm:space-y-4">
                {faqs.map((faq, index) => {
                    const isOpen = openIndex === index;
                    return (
                        <div
                            key={index}
                            className={`rounded-2xl border transition-all overflow-hidden ${
                                isOpen
                                    ? 'bg-[#18181C] border-[#7D39EB]/50 shadow-[0_10px_30px_rgba(125,57,235,0.15)]'
                                    : 'bg-[#121214]/90 border-white/10 hover:border-white/20'
                            }`}
                        >
                            <button
                                type="button"
                                onClick={() => toggleFaq(index)}
                                aria-expanded={isOpen}
                                className="w-full p-5 sm:p-6 text-left flex items-center justify-between gap-4 cursor-pointer focus:outline-none"
                            >
                                <span className={`text-sm sm:text-base font-bold transition-colors ${isOpen ? 'text-white' : 'text-slate-200'}`}>
                                    {faq.question}
                                </span>
                                <div className={`w-8 h-8 rounded-xl flex items-center justify-center shrink-0 transition-transform duration-300 ${isOpen ? 'bg-[#7D39EB] text-white rotate-180' : 'bg-white/5 text-slate-400'}`}>
                                    <ChevronDown className="w-4 h-4" />
                                </div>
                            </button>

                            {isOpen && (
                                <div className="px-5 pb-5 sm:px-6 sm:pb-6 pt-0 text-xs sm:text-sm text-slate-300 leading-relaxed font-normal border-t border-white/5 mt-1">
                                    <p className="pt-3">{faq.answer}</p>
                                </div>
                            )}
                        </div>
                    );
                })}
            </div>

            {/* Telegram Support prompt */}
            <div className="mt-8 text-center">
                <a
                    href="https://t.me/edusfera"
                    target="_blank"
                    rel="noreferrer"
                    className="inline-flex items-center gap-2 text-xs text-slate-400 hover:text-white transition-colors font-medium"
                >
                    <MessageCircle className="w-4 h-4 text-[#38BDF8]" />
                    <span>Остался вопрос? Напишите методисту в Telegram @edusfera</span>
                </a>
            </div>
        </section>
    );
}
