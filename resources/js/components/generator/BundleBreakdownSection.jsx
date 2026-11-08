import React from 'react';
import { 
    FileText, 
    Printer, 
    CheckCircle2, 
    AlertTriangle, 
    Mail, 
    GraduationCap, 
    Sparkles, 
    ArrowRight 
} from 'lucide-react';

export default function BundleBreakdownSection({ onOpenModal }) {
    const items = [
        {
            icon: FileText,
            tag: 'Защита от списывания',
            title: '15 индивидуальных вариантов (Части А и Б)',
            description: 'Каждый ученик получает уникальный числовой набор задач с одинаковой алгоритмической сложностью. Готовых ответов в интернете и ГДЗ нет.'
        },
        {
            icon: Printer,
            tag: 'Экономия картриджа',
            title: 'Готовый PDF формат А4 для моментальной печати',
            description: 'Чистый чёрно-белый эко-дизайн с выверенными полями, контрастным шрифтом и рамками. Идеально ложится на любой стандартный школьный принтер.'
        },
        {
            icon: CheckCircle2,
            tag: '2 минуты на класс',
            title: 'Сводный мастер-лист ответов для учителя',
            description: 'Все правильные ключи к части А и числовые ответы части Б сведены на одну компактную страницу. Проверка всей стопки тетрадей за перемену.'
        },
        {
            icon: AlertTriangle,
            tag: 'Методический анализ',
            title: 'Карта типовых ошибок и экзаменационных ловушек',
            description: 'Таблица характерных заблуждений учащихся по теме: потеря корней при ОДЗ, ошибки знаков и ложные формулы для быстрого разбора на уроке.'
        },
        {
            icon: Mail,
            tag: 'Бессрочный доступ',
            title: 'Мгновенная копия на Email и в кабинет',
            description: 'Файл формируется за 30 секунд. Скачивайте прямо со страницы подтверждения либо открывайте из входящего письма в любое удобное время.'
        },
        {
            icon: GraduationCap,
            tag: 'Госспецификации 2026',
            title: 'Стандарты ЦТ / ЦЭ 2026 и школьная программа',
            description: 'Все формулировки и типы заданий строго соответствуют актуальным государственным спецификациям экзаменационной программы Беларуси 2026 года.'
        }
    ];

    return (
        <section className="w-full max-w-[1240px] mx-auto px-4 sm:px-6 py-12 sm:py-16">
            {/* Header */}
            <div className="text-center max-w-3xl mx-auto mb-10 sm:mb-14 space-y-3">
                <div className="inline-flex items-center gap-2 px-3.5 py-1 rounded-full bg-white/[0.04] border border-white/10 text-xs font-mono text-[#C6FF33]">
                    <Sparkles className="w-3.5 h-3.5" />
                    <span>ЧТО ВХОДИТ В КОМПЛЕКТ ЗА 5.00 BYN</span>
                </div>
                <h2 className="text-2xl sm:text-3xl lg:text-4xl font-extrabold text-white tracking-tight">
                    Полный методический арсенал в одном комплекте
                </h2>
                <p className="text-sm sm:text-base text-slate-400 font-normal leading-relaxed">
                    Вы получаете не просто набор задач, а готовую систему объективной аттестации и моментальной проверки без лишних хлопот.
                </p>
            </div>

            {/* 6-Item Visual Grid */}
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 sm:gap-7">
                {items.map((item, index) => {
                    const IconComponent = item.icon;
                    return (
                        <div
                            key={index}
                            className="relative rounded-3xl bg-[#121214]/95 border border-white/10 p-6 sm:p-7 backdrop-blur-md shadow-lg hover:border-violet-500/40 hover:shadow-[0_10px_30px_rgba(125,57,235,0.15)] transition-all flex flex-col justify-between group"
                        >
                            <div>
                                <div className="flex items-center justify-between mb-5">
                                    <div className="w-11 h-11 rounded-2xl bg-white/[0.04] border border-white/10 flex items-center justify-center text-[#C6FF33] group-hover:scale-105 group-hover:bg-[#7D39EB]/20 group-hover:border-[#7D39EB]/50 transition-all">
                                        <IconComponent className="w-5 h-5" />
                                    </div>
                                    <span className="text-[10px] font-mono uppercase tracking-wider text-slate-400 px-2 py-0.5 rounded-full bg-white/[0.03] border border-white/5">
                                        {item.tag}
                                    </span>
                                </div>

                                <h3 className="text-base font-bold text-white mb-2 leading-snug group-hover:text-white transition-colors">
                                    {item.title}
                                </h3>

                                <p className="text-xs sm:text-sm text-slate-400 font-normal leading-relaxed">
                                    {item.description}
                                </p>
                            </div>

                            <div className="mt-5 pt-4 border-t border-white/5 flex items-center justify-between text-[11px] font-mono text-slate-500">
                                <span>Элемент {index + 1} из 6</span>
                                <span className="text-emerald-400 font-semibold">Включено</span>
                            </div>
                        </div>
                    );
                })}
            </div>

            {/* Bottom Callout Banner */}
            <div className="mt-10 sm:mt-12 p-6 sm:p-8 rounded-3xl bg-gradient-to-r from-violet-950/40 via-[#18181C] to-black border border-violet-500/30 flex flex-col sm:flex-row items-center justify-between gap-5">
                <div className="space-y-1 text-center sm:text-left">
                    <div className="text-white font-bold text-base sm:text-lg">
                        Готовы провести идеальную проверочную уже на следующем уроке?
                    </div>
                    <p className="text-xs sm:text-sm text-slate-400">
                        Генерация занимает 30 секунд. Оплата картой любого белорусского банка.
                    </p>
                </div>
                <button
                    type="button"
                    onClick={onOpenModal}
                    className="w-full sm:w-auto px-6 py-3.5 rounded-xl bg-[#7D39EB] hover:bg-[#6D28D9] text-white font-bold text-xs uppercase tracking-wide transition-all shadow-[0_0_20px_rgba(125,57,235,0.35)] flex items-center justify-center gap-2 cursor-pointer shrink-0 active:scale-[0.99]"
                >
                    <span>Сформировать за 5 BYN</span>
                    <ArrowRight className="w-4 h-4" />
                </button>
            </div>
        </section>
    );
}
