import React from 'react';
import { XCircle, CheckCircle2, Zap, ShieldAlert, Sparkles, Clock } from 'lucide-react';

export default function PainSolutionSection() {
    const painPoints = [
        {
            title: '2–3 часа на поиск и вёрстку в Word',
            description: 'Ручной подбор заданий из разных сборников, сбивающиеся формулы, кривые таблицы и мучительная подгонка под поля формата А4.'
        },
        {
            title: 'Списывание через Photomath и ГДЗ за 5 минут',
            description: 'Ученики фотографируют тест под партой, находят готовые решения в сети или мгновенно делятся ответами в закрытом чате класса.'
        },
        {
            title: 'Ночная проверка 30 тетрадей до 01:00 ночи',
            description: 'Повторный пересчёт каждого примера на черновике с калькулятором, усталость глаз, головная боль и сорванный отдых.'
        },
        {
            title: 'Споры с родителями и необъективные оценки',
            description: 'Сложно доказать факт списывания со смартфона; отличники получают незаслуженные баллы, а реальные пробелы остаются скрытыми.'
        }
    ];

    const solutionPoints = [
        {
            title: '1 клик и 30 секунд — комплект готов',
            description: 'Сформированный методический файл, строго соответствующий актуальным спецификациям экзаменационной программы Беларуси 2026 года.'
        },
        {
            title: '15 индивидуальных вариантов — Photomath бессилен',
            description: 'Одинаковая математическая модель и сложность, но уникальные корни, коэффициенты и числа. Списать у соседа по парте невозможно.'
        },
        {
            title: '2 минуты на проверку всей стопки класса',
            description: 'Единый мастер-лист правильных ответов для учителя с таблицей типовых ошибок и экзаменационных ловушек на одном листе.'
        },
        {
            title: '100% честный срез знаний и свободный вечер',
            description: 'Ученики мотивированы решать самостоятельно, вы видите реальную картину успеваемости, а вечер проводите с семьёй.'
        }
    ];

    return (
        <section className="w-full max-w-[1240px] mx-auto px-4 sm:px-6 py-12 sm:py-16">
            {/* Header */}
            <div className="text-center max-w-3xl mx-auto mb-10 sm:mb-14 space-y-3">
                <div className="inline-flex items-center gap-2 px-3.5 py-1 rounded-full bg-white/[0.04] border border-white/10 text-xs font-mono text-slate-300">
                    <Sparkles className="w-3.5 h-3.5 text-[#C6FF33]" />
                    <span>ПОЧЕМУ ПРЕПОДАВАТЕЛИ ВЫБИРАЮТ EDUSFERA</span>
                </div>
                <h2 className="text-2xl sm:text-3xl lg:text-4xl font-extrabold text-white tracking-tight">
                    Сравните: вечер учителя до и после Edusfera
                </h2>
                <p className="text-sm sm:text-base text-slate-400 font-normal leading-relaxed">
                    Один щелчок мыши отделяет изнурительную рутину от автоматизированной системы контроля знаний.
                </p>
            </div>

            {/* Side-by-Side Comparison */}
            <div className="grid grid-cols-1 lg:grid-cols-2 gap-6 sm:gap-8 items-stretch">
                {/* BEFORE: The Pain */}
                <div className="relative rounded-3xl bg-[#121214]/90 border border-rose-500/20 p-6 sm:p-8 backdrop-blur-md shadow-[0_16px_36px_-4px_rgba(0,0,0,0.5)] flex flex-col justify-between">
                    <div className="absolute top-0 right-0 w-48 h-48 pointer-events-none" style={{ background: 'radial-gradient(circle, rgba(244,63,94,0.08) 0%, transparent 70%)' }} />
                    <div>
                        <div className="flex items-center gap-3 mb-6 pb-4 border-b border-rose-500/15">
                            <div className="w-10 h-10 rounded-2xl bg-rose-500/10 border border-rose-500/30 flex items-center justify-center text-rose-400 shrink-0">
                                <ShieldAlert className="w-5 h-5" />
                            </div>
                            <div>
                                <span className="text-[10px] font-mono uppercase tracking-wider text-rose-400 font-semibold block">
                                    Традиционный подход
                                </span>
                                <h3 className="text-lg font-bold text-white">
                                    Было без Edusfera
                                </h3>
                            </div>
                        </div>

                        <div className="space-y-4">
                            {painPoints.map((item, index) => (
                                <div key={index} className="flex items-start gap-3.5 group">
                                    <div className="mt-0.5 w-5 h-5 rounded-full bg-rose-500/10 border border-rose-500/30 flex items-center justify-center shrink-0">
                                        <XCircle className="w-3.5 h-3.5 text-rose-400" />
                                    </div>
                                    <div className="space-y-1">
                                        <h4 className="text-xs sm:text-sm font-bold text-slate-200">
                                            {item.title}
                                        </h4>
                                        <p className="text-xs text-slate-400 leading-relaxed font-normal">
                                            {item.description}
                                        </p>
                                    </div>
                                </div>
                            ))}
                        </div>
                    </div>

                    <div className="mt-6 pt-4 border-t border-white/5 flex items-center justify-between text-xs text-rose-400/90 font-mono">
                        <span>Итог: 3+ часа рутины</span>
                        <span>Выгорание и недосып</span>
                    </div>
                </div>

                {/* AFTER: The Solution */}
                <div className="relative rounded-3xl bg-gradient-to-b from-[#18181C] to-[#121214] border border-[#7D39EB]/40 p-6 sm:p-8 backdrop-blur-md shadow-[0_16px_36px_-4px_rgba(125,57,235,0.2)] flex flex-col justify-between">
                    <div className="absolute top-0 right-0 w-56 h-56 pointer-events-none" style={{ background: 'radial-gradient(circle, rgba(125,57,235,0.18) 0%, transparent 70%)' }} />
                    <div>
                        <div className="flex items-center justify-between mb-6 pb-4 border-b border-[#7D39EB]/25">
                            <div className="flex items-center gap-3">
                                <div className="w-10 h-10 rounded-2xl bg-[#7D39EB]/20 border border-[#7D39EB]/50 flex items-center justify-center text-[#C6FF33] shrink-0 shadow-lg shadow-violet-500/25">
                                    <Zap className="w-5 h-5" />
                                </div>
                                <div>
                                    <span className="text-[10px] font-mono uppercase tracking-wider text-[#C6FF33] font-bold block">
                                        Современный стандарт 2026
                                    </span>
                                    <h3 className="text-lg font-bold text-white">
                                        Стало с Edusfera Вариант
                                    </h3>
                                </div>
                            </div>
                            <span className="hidden sm:inline-flex px-2.5 py-1 rounded-full bg-[#C6FF33] text-black font-extrabold text-[10px] uppercase tracking-wide">
                                100% защита
                            </span>
                        </div>

                        <div className="space-y-4">
                            {solutionPoints.map((item, index) => (
                                <div key={index} className="flex items-start gap-3.5 group">
                                    <div className="mt-0.5 w-5 h-5 rounded-full bg-[#7D39EB]/20 border border-[#7D39EB]/60 flex items-center justify-center shrink-0">
                                        <CheckCircle2 className="w-3.5 h-3.5 text-[#C6FF33]" />
                                    </div>
                                    <div className="space-y-1">
                                        <h4 className="text-xs sm:text-sm font-bold text-white">
                                            {item.title}
                                        </h4>
                                        <p className="text-xs text-slate-300 leading-relaxed font-normal">
                                            {item.description}
                                        </p>
                                    </div>
                                </div>
                            ))}
                        </div>
                    </div>

                    <div className="mt-6 pt-4 border-t border-white/10 flex items-center justify-between text-xs font-mono">
                        <span className="text-[#C6FF33] font-bold">Итог: 30 секунд на генерацию</span>
                        <span className="text-slate-300 flex items-center gap-1.5">
                            <Clock className="w-3.5 h-3.5 text-[#7D39EB]" />
                            2 мин на проверку класса
                        </span>
                    </div>
                </div>
            </div>
        </section>
    );
}
