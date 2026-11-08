import React from 'react';
import { Check, Sparkles, ArrowRight, ShieldCheck, Zap } from 'lucide-react';

export default function PricingTiersSection({ onSelectPlan }) {
    const singleFeatures = [
        '15 индивидуальных вариантов (Части А и Б)',
        'Готовый чёрно-белый PDF формата А4 для печати',
        'Сводный мастер-лист правильных ответов',
        'Таблица экзаменационных ловушек и ошибок',
        'Мгновенная доставка на Email и в кабинет',
        'Разовый платёж без скрытых подписок'
    ];

    const passFeatures = [
        '10 любых тем по вашей дисциплине (всего 150 вариантов)',
        'Всего 1.90 BYN за тему вместо 5.00 BYN (выгода 62%)',
        'Все мастер-листы ответов и таблицы ловушек',
        'Готовые PDF под стандартную печать А4',
        'Бессрочный доступ в личном кабинете',
        'Закрывает потребность в проверочных на всю четверть',
        'Чек плательщика налога на профдоход (НПД МНС РБ)'
    ];

    return (
        <section className="w-full max-w-[1240px] mx-auto px-4 sm:px-6 py-12 sm:py-16">
            {/* Header */}
            <div className="text-center max-w-3xl mx-auto mb-10 sm:mb-14 space-y-3">
                <div className="inline-flex items-center gap-2 px-3.5 py-1 rounded-full bg-white/[0.04] border border-white/10 text-xs font-mono text-[#C6FF33]">
                    <Sparkles className="w-3.5 h-3.5" />
                    <span>ТАРИФЫ И ВАРИАНТЫ ОПЛАТЫ</span>
                </div>
                <h2 className="text-2xl sm:text-3xl lg:text-4xl font-extrabold text-white tracking-tight">
                    Прозрачная стоимость. Без скрытых подписок
                </h2>
                <p className="text-sm sm:text-base text-slate-400 font-normal leading-relaxed">
                    Платите только за то, что используете. Никаких ежемесячных автосписаний с карты — вы контролируете каждый рубль.
                </p>
            </div>

            {/* Side-by-Side Pricing Cards */}
            <div className="grid grid-cols-1 lg:grid-cols-2 gap-8 items-stretch max-w-4xl mx-auto">
                
                {/* Tier 1: Single Topic */}
                <div className="relative rounded-3xl bg-[#121214]/95 border border-white/10 p-7 sm:p-8 backdrop-blur-md shadow-lg flex flex-col justify-between hover:border-white/20 transition-all">
                    <div>
                        <div className="flex items-center justify-between mb-4">
                            <span className="text-xs font-mono uppercase tracking-wider text-slate-400 font-semibold">
                                Разовый комплект
                            </span>
                            <span className="text-[11px] font-mono px-2 py-0.5 rounded-full bg-white/[0.04] text-slate-400 border border-white/5">
                                На 1 урок
                            </span>
                        </div>

                        <h3 className="text-xl font-bold text-white mb-2">
                            1 проверочная работа
                        </h3>

                        <div className="flex items-baseline gap-2 mb-6">
                            <span className="text-4xl sm:text-5xl font-black text-white font-mono tracking-tight">
                                5.00
                            </span>
                            <span className="text-slate-400 text-sm font-semibold">
                                BYN
                            </span>
                            <span className="text-slate-500 text-xs font-mono ml-auto">
                                разовый платёж
                            </span>
                        </div>

                        <div className="space-y-3 pt-4 border-t border-white/5">
                            {singleFeatures.map((feat, i) => (
                                <div key={i} className="flex items-start gap-2.5 text-xs sm:text-sm text-slate-300">
                                    <div className="mt-0.5 w-4 h-4 rounded-full bg-white/[0.06] border border-white/10 flex items-center justify-center shrink-0">
                                        <Check className="w-2.5 h-2.5 text-white" />
                                    </div>
                                    <span>{feat}</span>
                                </div>
                            ))}
                        </div>
                    </div>

                    <div className="mt-8 pt-6 border-t border-white/5">
                        <button
                            type="button"
                            onClick={() => onSelectPlan(false)}
                            className="w-full py-4 px-6 rounded-xl bg-white/[0.08] hover:bg-white/[0.14] border border-white/10 text-white font-bold text-xs uppercase tracking-wide transition-all flex items-center justify-center gap-2 cursor-pointer active:scale-[0.99]"
                        >
                            <span>Выбрать 1 комплект — 5 BYN</span>
                            <ArrowRight className="w-4 h-4" />
                        </button>
                    </div>
                </div>

                {/* Tier 2: Quarter Pass (Featured) */}
                <div className="relative rounded-3xl bg-gradient-to-b from-[#18181C] to-[#121214] border-2 border-[#7D39EB] p-7 sm:p-8 backdrop-blur-md shadow-[0_20px_50px_rgba(125,57,235,0.25)] flex flex-col justify-between">
                    {/* Top discount ribbon */}
                    <div className="absolute -top-3.5 right-6 px-3 py-1 rounded-full bg-gradient-to-r from-[#7D39EB] to-violet-600 text-white text-[10px] font-black uppercase tracking-wider shadow-md flex items-center gap-1.5">
                        <Zap className="w-3 h-3 text-[#C6FF33]" />
                        <span>ХИТ ВЫБОРА • СКИДКА 62%</span>
                    </div>

                    <div>
                        <div className="flex items-center justify-between mb-4">
                            <span className="text-xs font-mono uppercase tracking-wider text-[#C6FF33] font-bold">
                                Абонемент на четверть
                            </span>
                            <span className="text-[11px] font-mono px-2 py-0.5 rounded-full bg-[#7D39EB]/20 text-[#C6FF33] border border-[#7D39EB]/40 font-bold">
                                10 тем
                            </span>
                        </div>

                        <h3 className="text-xl font-bold text-white mb-2">
                            Пакет «Вся четверть»
                        </h3>

                        <div className="flex items-baseline gap-2 mb-1">
                            <span className="text-4xl sm:text-5xl font-black text-[#C6FF33] font-mono tracking-tight">
                                19.00
                            </span>
                            <span className="text-slate-300 text-sm font-semibold">
                                BYN
                            </span>
                            <span className="text-slate-500 line-through text-sm font-mono ml-2">
                                50.00 BYN
                            </span>
                        </div>
                        <div className="text-xs text-slate-400 font-mono mb-6">
                            всего <strong>1.90 BYN</strong> за тему • экономия 31.00 BYN
                        </div>

                        <div className="space-y-3 pt-4 border-t border-white/10">
                            {passFeatures.map((feat, i) => (
                                <div key={i} className="flex items-start gap-2.5 text-xs sm:text-sm text-slate-200">
                                    <div className="mt-0.5 w-4 h-4 rounded-full bg-[#7D39EB]/20 border border-[#7D39EB]/50 flex items-center justify-center shrink-0">
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
                            onClick={() => onSelectPlan(true)}
                            className="w-full py-4 px-6 rounded-xl bg-[#7D39EB] hover:bg-[#6D28D9] text-white font-bold text-xs uppercase tracking-wide transition-all shadow-[0_0_25px_rgba(125,57,235,0.4)] flex items-center justify-center gap-2 cursor-pointer active:scale-[0.99]"
                        >
                            <span>Оформить абонемент — 19 BYN</span>
                            <ArrowRight className="w-4 h-4" />
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
