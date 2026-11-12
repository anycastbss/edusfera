import React, { useState } from 'react';
import { Calculator, Moon, Clock, Check, ArrowRight } from 'lucide-react';

export default function TimeSavedCalculator({ onOpenModal }) {
    const [classesCount, setClassesCount] = useState(2);
    const [testsPerMonth, setTestsPerMonth] = useState(3);

    // Calculated metrics
    const prepSaved = Math.round(classesCount * testsPerMonth * 2.5);
    const gradingSaved = Math.round(classesCount * testsPerMonth * 1.5);
    const totalHoursSaved = prepSaved + gradingSaved;
    const eveningsRecovered = Math.max(1, Math.round(totalHoursSaved / 3.5));

    return (
        <section className="w-full max-w-[1240px] mx-auto px-4 sm:px-6 py-12 sm:py-16">
            <div className="relative rounded-3xl bg-[#121214]/95 border border-white/10 p-6 sm:p-10 backdrop-blur-md shadow-[0_20px_50px_rgba(0,0,0,0.6)] overflow-hidden">
                <div className="relative z-10 grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-12 items-center">
                    
                    {/* Left Column: Sliders & Controls (7 cols) */}
                    <div className="lg:col-span-7 space-y-6 sm:space-y-8">
                        <div>
                            <div className="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/[0.04] border border-white/10 text-xs font-mono text-[#C6FF33] mb-3">
                                <Calculator className="w-3.5 h-3.5" />
                                <span>Калькулятор экономии времени</span>
                            </div>
                            <h2 className="text-2xl sm:text-3xl font-extrabold text-white tracking-tight">
                                Сколько часов жизни вы спасёте за 1 месяц?
                            </h2>
                            <p className="text-xs sm:text-sm text-slate-400 mt-2 font-normal leading-relaxed">
                                Передвиньте ползунки под вашу реальную педагогическую нагрузку и узнайте, сколько часов рутины можно превратить в отдых и общение с близкими.
                            </p>
                        </div>

                        {/* Sliders Container */}
                        <div className="space-y-6 p-5 sm:p-6 rounded-2xl bg-[#18181C] border border-white/5">
                            {/* Slider 1: Classes Count */}
                            <div className="space-y-2.5">
                                <div className="flex items-center justify-between">
                                    <label className="text-xs sm:text-sm font-bold text-slate-300">
                                        Количество ваших классов или групп:
                                    </label>
                                    <span className="px-3 py-1 rounded-lg bg-[#7D39EB]/20 border border-[#7D39EB]/40 text-[#C6FF33] font-mono font-extrabold text-sm">
                                        {classesCount} {classesCount === 1 ? 'класс' : (classesCount < 5 ? 'класса' : 'классов')}
                                    </span>
                                </div>
                                <input
                                    type="range"
                                    min="1"
                                    max="5"
                                    step="1"
                                    value={classesCount}
                                    onChange={(e) => setClassesCount(Number(e.target.value))}
                                    className="w-full h-2.5 bg-white/10 rounded-lg appearance-none cursor-pointer accent-[#7D39EB] focus:outline-none"
                                />
                                <div className="flex justify-between text-[11px] font-mono text-slate-500">
                                    <span>1 класс</span>
                                    <span>2</span>
                                    <span>3</span>
                                    <span>4</span>
                                    <span>5 классов</span>
                                </div>
                            </div>

                            {/* Slider 2: Tests Per Month */}
                            <div className="space-y-2.5 pt-4 border-t border-white/5">
                                <div className="flex items-center justify-between">
                                    <label className="text-xs sm:text-sm font-bold text-slate-300">
                                        Проверочных и самостоятельных в месяц (на класс):
                                    </label>
                                    <span className="px-3 py-1 rounded-lg bg-[#7D39EB]/20 border border-[#7D39EB]/40 text-[#C6FF33] font-mono font-extrabold text-sm">
                                        {testsPerMonth} {testsPerMonth === 1 ? 'работа' : (testsPerMonth < 5 ? 'работы' : 'работ')}
                                    </span>
                                </div>
                                <input
                                    type="range"
                                    min="1"
                                    max="8"
                                    step="1"
                                    value={testsPerMonth}
                                    onChange={(e) => setTestsPerMonth(Number(e.target.value))}
                                    className="w-full h-2.5 bg-white/10 rounded-lg appearance-none cursor-pointer accent-[#7D39EB] focus:outline-none"
                                />
                                <div className="flex justify-between text-[11px] font-mono text-slate-500">
                                    <span>1 работа</span>
                                    <span>2</span>
                                    <span>4</span>
                                    <span>6</span>
                                    <span>8 работ</span>
                                </div>
                            </div>
                        </div>

                        {/* Quick Math Breakdown Line */}
                        <div className="grid grid-cols-2 gap-3 text-xs text-slate-400 font-mono">
                            <div className="p-3 rounded-xl bg-white/[0.02] border border-white/5">
                                <span className="block text-slate-500 text-[10px] uppercase">На вёрстку тестов:</span>
                                <span className="font-bold text-white text-sm">~{prepSaved} ч</span> сэкономлено
                            </div>
                            <div className="p-3 rounded-xl bg-white/[0.02] border border-white/5">
                                <span className="block text-slate-500 text-[10px] uppercase">На проверку стопок:</span>
                                <span className="font-bold text-white text-sm">~{gradingSaved} ч</span> сэкономлено
                            </div>
                        </div>
                    </div>

                    {/* Right Column: Dynamic Visual Results Card (5 cols) */}
                    <div className="lg:col-span-5 bg-[#121214]/95 border border-white/10 rounded-3xl p-6 sm:p-8 backdrop-blur-md shadow-xl relative flex flex-col justify-between">
                        <div className="space-y-6">
                            <div className="flex items-center justify-between pb-4 border-b border-white/10">
                                <span className="text-xs font-mono uppercase tracking-wider text-slate-400">
                                    Ежемесячная экономия
                                </span>
                                <span className="px-2 py-0.5 rounded-full bg-[#C6FF33] text-black text-[10px] font-extrabold uppercase">
                                    Ваш ресурс
                                </span>
                            </div>

                            {/* Main Giant Stat */}
                            <div>
                                <div className="text-4xl sm:text-5xl lg:text-6xl font-black text-white font-mono tracking-tight flex items-baseline gap-2">
                                    <span>{totalHoursSaved}</span>
                                    <span className="text-xl sm:text-2xl font-bold text-[#C6FF33]">часов</span>
                                </div>
                                <p className="text-xs text-slate-400 mt-1 font-medium">
                                    чистого времени вы освобождаете каждый месяц
                                </p>
                            </div>

                            {/* Sleep & Family Metric */}
                            <div className="p-4 rounded-2xl bg-white/[0.04] border border-white/10 space-y-2">
                                <div className="flex items-center gap-2.5 text-white font-bold text-sm">
                                    <Moon className="w-4 h-4 text-[#C6FF33]" />
                                    <span>+{eveningsRecovered} спокойных вечеров дома</span>
                                </div>
                                <p className="text-xs text-slate-400 leading-relaxed font-normal">
                                    Без проверки тетрадей до полуночи, без стресса перед уроками и без ручного составления заданий в выходные.
                                </p>
                            </div>

                            {/* Monetary equivalent anchor */}
                            <div className="text-xs text-slate-400 font-mono flex items-center justify-between pt-1">
                                <span>Стоимость 1 комплекта:</span>
                                <span className="text-white font-bold">всего 5.00 BYN</span>
                            </div>
                        </div>

                        {/* CTA button */}
                        <div className="pt-6">
                            <button
                                type="button"
                                onClick={onOpenModal}
                                className="w-full py-3.5 px-5 rounded-xl bg-[#7D39EB] hover:bg-[#6D28D9] text-white font-bold text-xs uppercase tracking-wide transition-all shadow-md flex items-center justify-center gap-2 cursor-pointer active:scale-[0.99]"
                            >
                                <span>Сэкономить время за 5 BYN</span>
                                <ArrowRight className="w-4 h-4" />
                            </button>
                        </div>
                    </div>

                </div>
            </div>
        </section>
    );
}
