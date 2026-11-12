import React from 'react';
import { Check, ArrowRight, ShieldCheck } from 'lucide-react';

export default function PricingTiersSection({ onSelectPlan }) {
    const singleFeatures = [
        '10, 15 или 25 индивидуальных вариантов под размер вашего класса',
        'Числа и формулы у всех разные — списать с ГДЗ и Photomath невозможно',
        'Экономный формат печати (2 варианта на лист А4 с пунктирной линией отреза ✂)',
        'Сводная таблица правильных ответов (проверка всей стопки тетрадей за 2 минуты)',
        'Разбор методических ловушек и шкала перевода первичных баллов',
        'Мгновенная ссылка на скачивание PDF + дублирование файла на указанный email',
        'Разовый платёж без подписок, скрытых условий и автосписаний'
    ];

    return (
        <section className="w-full max-w-[1240px] mx-auto px-4 sm:px-6 py-12 sm:py-16">
            {/* Header */}
            <div className="text-center max-w-2xl mx-auto mb-10 space-y-3">
                <div className="inline-flex items-center gap-2 px-3.5 py-1 rounded-full bg-white/[0.04] border border-white/10 text-xs font-mono text-[#C6FF33]">
                    <span className="w-1.5 h-1.5 rounded-full bg-[#C6FF33]" />
                    <span>Стоимость комплекта</span>
                </div>
                <h2 className="text-2xl sm:text-3xl lg:text-4xl font-extrabold text-white tracking-tight">
                    5 BYN за готовый комплект на урок
                </h2>
                <p className="text-sm sm:text-base text-slate-400 font-normal leading-relaxed">
                    Платите только за нужный проверочный материал. Никаких ежемесячных подписок и автосписаний с карты.
                </p>
            </div>

            {/* Single Centered Pricing Card */}
            <div className="max-w-xl mx-auto">
                <div className="relative rounded-3xl bg-[#121214]/95 border border-white/10 p-7 sm:p-9 backdrop-blur-md shadow-xl flex flex-col justify-between">
                    <div>
                        <div className="flex items-center justify-between mb-4">
                            <span className="text-xs font-mono uppercase tracking-wider text-[#C6FF33] font-bold">
                                Комплект проверочной работы
                            </span>
                            <span className="text-[11px] font-mono px-2.5 py-0.5 rounded-full bg-white/[0.06] text-slate-300 border border-white/10">
                                1 класс • 1 урок
                            </span>
                        </div>

                        <div className="flex items-baseline gap-2 mb-6">
                            <span className="text-5xl font-black text-white font-mono tracking-tight">
                                5.00
                            </span>
                            <span className="text-slate-400 text-base font-semibold">
                                BYN
                            </span>
                            <span className="text-slate-500 text-xs font-mono ml-auto">
                                разовый платёж
                            </span>
                        </div>

                        <div className="space-y-3 pt-5 border-t border-white/10">
                            {singleFeatures.map((feat, i) => (
                                <div key={i} className="flex items-start gap-2.5 text-xs sm:text-sm text-slate-300">
                                    <div className="mt-0.5 w-4 h-4 rounded-full bg-white/5 border border-white/10 flex items-center justify-center shrink-0">
                                        <Check className="w-2.5 h-2.5 text-[#C6FF33]" />
                                    </div>
                                    <span>{feat}</span>
                                </div>
                            ))}
                        </div>
                    </div>

                    <div className="mt-8 pt-6 border-t border-white/10">
                        <button
                            type="button"
                            onClick={() => onSelectPlan(false)}
                            className="w-full py-4 px-6 rounded-xl bg-[#7D39EB] hover:bg-[#6D28D9] text-white font-bold text-xs uppercase tracking-wide transition-all shadow-md flex items-center justify-center gap-2 cursor-pointer active:scale-[0.99]"
                        >
                            <span>Сформировать комплект на урок — 5 BYN</span>
                            <ArrowRight className="w-4 h-4 text-white" />
                        </button>
                    </div>
                </div>
            </div>

            {/* Bottom Security Assurance */}
            <div className="mt-8 text-center text-xs text-slate-400 font-mono flex items-center justify-center gap-3">
                <ShieldCheck className="w-4 h-4 text-[#C6FF33]" />
                <span>Официальный интернет-эквайринг ЗАО «Альфа-Банк» • Белкарт, Visa, MasterCard • Чек НПД МНС РБ</span>
            </div>
        </section>
    );
}
