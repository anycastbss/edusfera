import React from 'react';
import { Star, MapPin, CheckCircle, Quote, Sparkles } from 'lucide-react';

export default function TestimonialsSection() {
    const testimonials = [
        {
            name: 'Ольга Николаевна В.',
            role: 'Учитель математики высшей категории',
            institution: 'Гимназия №1, г. Минск',
            city: 'Минск',
            subject: 'Математика • 10–11 классы',
            experience: 'Стаж 18 лет',
            avatar: 'ОН',
            quote: 'В 10 классе списывали всё подряд через камеры телефонов и Photomath. Раздала 15 вариантов Edusfera — в кабинете стояла идеальная тишина, каждый смотрел только в свой лист. А стопку из 28 тетрадей проверила за 10 минут на перемене по мастер-ключу!'
        },
        {
            name: 'Дмитрий С.',
            role: 'Репетитор по физике, подготовка к ЦТ/ЦЭ',
            institution: 'Частная практика, г. Гродно',
            city: 'Гродно',
            subject: 'Физика (ЦТ / ЦЭ 2026)',
            experience: 'Стаж 9 лет',
            avatar: 'ДС',
            quote: 'Экономлю минимум 6 часов в неделю. Раньше сам ночами пересчитывал цифры в задачах по кинематике и постоянному току, чтобы ученики не списывали. Теперь беру готовый PDF за 5 рублей, распечатываю — и сразу вижу реальные пробелы каждого.'
        },
        {
            name: 'Елена Павловна К.',
            role: 'Учитель химии и биологии',
            institution: 'СШ №14, г. Брест',
            city: 'Брест',
            subject: 'Химия и биология',
            experience: 'Стаж 14 лет',
            avatar: 'ЕП',
            quote: 'Распечатываю на обычном школьном чёрно-белом лазерном принтере. Шрифты чёткие, рамки аккуратные, поля не съезжают и тонер не расходуется впустую. Оформила абонемент на четверть — закрыла все проверочные на два месяца вперёд.'
        }
    ];

    return (
        <section className="w-full max-w-[1240px] mx-auto px-4 sm:px-6 py-12 sm:py-16">
            {/* Header */}
            <div className="text-center max-w-3xl mx-auto mb-10 sm:mb-14 space-y-3">
                <div className="inline-flex items-center gap-2 px-3.5 py-1 rounded-full bg-white/[0.04] border border-white/10 text-xs font-mono text-[#C6FF33]">
                    <Sparkles className="w-3.5 h-3.5" />
                    <span>ОТЗЫВЫ ПРЕПОДАВАТЕЛЕЙ БЕЛАРУСИ</span>
                </div>
                <h2 className="text-2xl sm:text-3xl lg:text-4xl font-extrabold text-white tracking-tight">
                    Проверено в гимназиях и школах по всей стране
                </h2>
                <p className="text-sm sm:text-base text-slate-400 font-normal leading-relaxed">
                    Реальный опыт учителей, которые перестали тратить ночи на вёрстку тестов и борьбу со списыванием.
                </p>
            </div>

            {/* Testimonial Cards Grid */}
            <div className="grid grid-cols-1 md:grid-cols-3 gap-6 sm:gap-8">
                {testimonials.map((item, index) => (
                    <div
                        key={index}
                        className="relative rounded-3xl bg-[#121214]/95 border border-white/10 p-6 sm:p-7 backdrop-blur-md shadow-lg flex flex-col justify-between hover:border-violet-500/30 transition-all group"
                    >
                        <div>
                            {/* Card Top: Stars & City */}
                            <div className="flex items-center justify-between mb-4">
                                <div className="flex items-center gap-1 text-[#C6FF33]">
                                    {[...Array(5)].map((_, i) => (
                                        <Star key={i} className="w-3.5 h-3.5 fill-current" />
                                    ))}
                                </div>
                                <div className="flex items-center gap-1 text-[11px] font-mono text-slate-400">
                                    <MapPin className="w-3 h-3 text-[#7D39EB]" />
                                    <span>{item.city}</span>
                                </div>
                            </div>

                            {/* Quote Text */}
                            <p className="text-xs sm:text-sm text-slate-300 font-normal leading-relaxed italic mb-6">
                                «{item.quote}»
                            </p>
                        </div>

                        {/* Author Info */}
                        <div className="pt-4 border-t border-white/5 flex items-center gap-3">
                            <div className="w-10 h-10 rounded-full bg-[#7D39EB]/20 border border-[#7D39EB]/40 flex items-center justify-center text-xs font-bold font-mono text-[#C6FF33] shrink-0">
                                {item.avatar}
                            </div>
                            <div className="overflow-hidden">
                                <div className="flex items-center gap-1.5">
                                    <span className="text-xs font-bold text-white truncate">
                                        {item.name}
                                    </span>
                                    <CheckCircle className="w-3.5 h-3.5 text-emerald-400 shrink-0" />
                                </div>
                                <div className="text-[11px] text-slate-400 truncate">
                                    {item.role}
                                </div>
                                <div className="text-[10px] text-slate-500 font-mono truncate">
                                    {item.institution} • {item.experience}
                                </div>
                            </div>
                        </div>
                    </div>
                ))}
            </div>
        </section>
    );
}
