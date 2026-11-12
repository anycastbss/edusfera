import React from 'react';
import { ShieldCheck, RotateCcw, Lock, FileCheck } from 'lucide-react';

export default function GuaranteeSection() {
    const pillars = [
        {
            icon: RotateCcw,
            title: 'Бесплатная замена или 100% возврат',
            description: 'Если вы найдёте опечатку, методическую неточность или несоответствие спецификации — мы мгновенно предоставим комплект по любой другой теме или вернём деньги на вашу карту.'
        },
        {
            icon: Lock,
            title: 'Эквайринг ЗАО «Альфа-Банк»',
            description: 'Платежи обрабатываются по международному стандарту безопасности PCI DSS с протоколами 3D-Secure 2.0 и Белкарт-ИнтернетПароль. Edusfera не хранит данные карт.'
        },
        {
            icon: FileCheck,
            title: 'Электронный чек НПД МНС РБ',
            description: 'Каждая транзакция официально регистрируется в налоговых органах Беларуси. Вы получаете электронный чек на Email для спокойной отчётности.'
        }
    ];

    return (
        <section className="w-full max-w-[1240px] mx-auto px-4 sm:px-6 py-12 sm:py-16">
            <div className="relative rounded-3xl bg-[#121214]/95 border border-white/10 p-7 sm:p-10 backdrop-blur-md shadow-2xl overflow-hidden">
                <div className="relative z-10">
                    {/* Header */}
                    <div className="text-center max-w-2xl mx-auto mb-8 sm:mb-12 space-y-3">
                        <div className="inline-flex items-center gap-2 px-3.5 py-1 rounded-full bg-[#C6FF33]/10 border border-[#C6FF33]/30 text-xs font-mono text-[#C6FF33] font-bold">
                            <ShieldCheck className="w-3.5 h-3.5" />
                            <span>Методическая гарантия и безопасность</span>
                        </div>
                        <h2 className="text-2xl sm:text-3xl font-extrabold text-white tracking-tight">
                            Ноль риска: гарантия точности заданий и возврата средств
                        </h2>
                        <p className="text-xs sm:text-sm text-slate-400 font-normal leading-relaxed">
                            Мы уверены в качестве наших заданий, потому что каждое проходит двухэтапную верификацию по стандартам экзаменационной программы Беларуси 2026 года.
                        </p>
                    </div>

                    {/* 3 Pillars Grid */}
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-6 sm:gap-8">
                        {pillars.map((pil, idx) => {
                            const IconComponent = pil.icon;
                            return (
                                <div
                                    key={idx}
                                    className="p-6 rounded-2xl bg-white/[0.03] border border-white/5 space-y-3 flex flex-col justify-between hover:border-white/15 transition-all"
                                >
                                    <div className="space-y-3">
                                        <div className="w-10 h-10 rounded-xl bg-white/[0.05] border border-white/10 flex items-center justify-center text-[#C6FF33]">
                                            <IconComponent className="w-5 h-5" />
                                        </div>
                                        <h3 className="text-sm sm:text-base font-bold text-white">
                                            {pil.title}
                                        </h3>
                                        <p className="text-xs text-slate-400 font-normal leading-relaxed">
                                            {pil.description}
                                        </p>
                                    </div>
                                    <div className="pt-2 text-[10px] font-mono text-emerald-400 font-semibold flex items-center gap-1.5">
                                        <span>Гарантировано договором оферты</span>
                                    </div>
                                </div>
                            );
                        })}
                    </div>
                </div>
            </div>
        </section>
    );
}
