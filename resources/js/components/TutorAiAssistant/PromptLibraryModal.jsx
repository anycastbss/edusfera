import React, { useState } from 'react';
import { Sparkles, X, Search, BookOpen, Target, Brain, Award, Check, Layers, Filter } from 'lucide-react';

const PROMPT_CATEGORIES = [
    { id: 'all', label: 'Все сценарии' },
    { id: 'lesson_plan', label: '📚 Планы занятий' },
    { id: 'quiz', label: '📝 Проверочные и ДЗ' },
    { id: 'pedagogy', label: '💡 Методика и мнемоника' },
    { id: 'exams', label: '🎯 Экзамены и тесты' },
    { id: 'olympiad', label: '🏆 Олимпиады' },
];

const PROMPT_SUBJECTS = [
    'Все предметы',
    'Математика',
    'Физика',
    'Русский язык',
    'Белорусский язык',
    'Английский язык',
    'Химия',
    'Биология',
    'История и Обществоведение',
    'Информатика',
];

const PRESET_PROMPTS = [
    // ─── МАТЕМАТИКА ───
    {
        id: 1,
        category: 'exams',
        title: 'Разбор ловушек в логарифмических неравенствах с переменным основанием',
        subject: 'Математика',
        desc: 'Помогает ученику не потерять ОДЗ и не ошибиться при переходе к переменному основанию.',
        prompt: 'Составь подборку из 4 сложных логарифмических неравенств с переменным основанием. Для каждого укажи главную ловушку (потеря ОДЗ, смена знака неравенства) и пошаговый алгоритм решения с формулами в LaTeX.',
        targetTab: 'quiz_gen',
    },
    {
        id: 2,
        category: 'quiz',
        title: 'Проверочный тест: Стереометрия, углы и расстояния в пирамидах',
        subject: 'Математика',
        desc: 'Банк экзаменационных задач повышенной сложности с числовыми ответами и ходом решения.',
        prompt: 'Сгенерируй проверочный тест из 5 задач по теме «Правильные пирамиды: угол между боковым ребром и основанием, сечения и расстояния». Для каждой задачи укажи точный числовой ответ и подробный ход решения.',
        targetTab: 'quiz_gen',
    },
    {
        id: 3,
        category: 'pedagogy',
        title: 'Объяснение формулы Бернулли на жизненном примере «на пальцах»',
        subject: 'Математика',
        desc: 'Позволяет за 5 минут объяснить сложную формулу комбинаторики и теории вероятностей.',
        prompt: 'Как объяснить формулу Бернулли для схемы независимых испытаний ученику старших классов на наглядном жизненном примере (броски монеты или серия пенальти)? Дай пошаговую педагогическую метафору, блок формул в LaTeX и мнемонику.',
        targetTab: 'methodist',
    },
    {
        id: 4,
        category: 'olympiad',
        title: 'Олимпиадная планиметрия: Лемма о трезубце и вневписанные окружности',
        subject: 'Математика',
        desc: 'Теорема Мансиона и доказательство через вписанные углы для городских этапов олимпиад.',
        prompt: 'Сформулируй лемму о трезубце (теорему Мансиона) в планиметрии. Приведи строгое доказательство через вписанные углы в LaTeX и разбери 1 красивую олимпиадную задачу на ее прямое применение.',
        targetTab: 'methodist',
    },

    // ─── ФИЗИКА ───
    {
        id: 5,
        category: 'lesson_plan',
        title: 'Урок-интенсив: Кинематика равноускоренного движения (60 мин)',
        subject: 'Физика',
        desc: 'Поминутный конспект с выводом уравнений проекций скорости и перемещения без времени.',
        prompt: 'Составь технологическую карту 60-минутного занятия по физике: «Равноускоренное прямолинейное движение. Формулы пути без времени». Включи разминку на 5 мин, вывод формул через LaTeX и 3 разноуровневые задачи.',
        targetTab: 'lesson_plan',
    },
    {
        id: 6,
        category: 'pedagogy',
        title: 'Правило буравчика и правило левой руки: наглядная мнемоника',
        subject: 'Физика',
        desc: 'Определение направлений вектора магнитной индукции, силы Лоренца и силы Ампера.',
        prompt: 'Предложи систему запоминания и физические аналогии для правила буравчика, правила правой руки (для соленоида) и правила левой руки (силы Ампера и Лоренца). Оформи мнемонические правила и типичные ошибки школьников в блоки > [!MNEMONIC] и > [!WARNING].',
        targetTab: 'methodist',
    },
    {
        id: 7,
        category: 'quiz',
        title: 'Дифференцированный тест: Законы постоянного тока и расчет цепей',
        subject: 'Физика',
        desc: 'Проверка закона Ома для полной цепи, смешанного соединения и работы тока.',
        prompt: 'Составь проверочный тест по физике из 6 задач на тему «Законы постоянного тока: смешанное соединение резисторов, закон Ома для замкнутой цепи, мощность и КПД источника». Раздели тест на уровни: База, Стандарт, Продвинутый.',
        targetTab: 'quiz_gen',
    },

    // ─── РУССКИЙ ЯЗЫК ───
    {
        id: 8,
        category: 'pedagogy',
        title: 'Шпаргалка-мнемоника: Правописание Н и НН в суффиксах частей речи',
        subject: 'Русский язык',
        desc: 'Концентрированный разбор исключений, отглагольных прилагательных и причастий.',
        prompt: 'Сформируй экспресс-шпаргалку по теме «Правописание Н и НН в различных частях речи». Выдели 5 типичных ловушек составителей экзаменов, дай мнемонические правила (блок > [!MNEMONIC]) и алгоритм выбора из 4 шагов.',
        targetTab: 'methodist',
    },
    {
        id: 9,
        category: 'exams',
        title: 'Топ-10 каверзных заданий на пунктуацию в сложных предложениях',
        subject: 'Русский язык',
        desc: 'Сложносочиненные предложения с общим второстепенным членом и бессоюзные связи.',
        prompt: 'Составь тренировочный комплекс из 10 сложных предложений на пунктуацию: стык союзов (что если, хотя но), общий второстепенный член в ССП, знаки в БСП (тире и двоеточие). Для каждого дай комментарий с графическим разбором.',
        targetTab: 'quiz_gen',
    },

    // ─── БЕЛОРУССКИЙ ЯЗЫК ───
    {
        id: 10,
        category: 'exams',
        title: 'Практыкум: Правапіс галосных О, Э, А, Я і пераход у ненаціскным становішчы',
        subject: 'Белорусский язык',
        desc: 'Аканне і яканне ў карнях, суфіксах і запазычаных словах.',
        prompt: 'Складзі дыферэнцыраваны тэст з 8 заданняў па беларускай мове на тэму «Правапіс галосных О, Э, А, Я». Уключы запазычаныя словы (дэталi, камп’ютар, медальён), выключэнні і падрабязны аналіз правілаў.',
        targetTab: 'quiz_gen',
    },

    // ─── АНГЛИЙСКИЙ ЯЗЫК ───
    {
        id: 11,
        category: 'lesson_plan',
        title: 'Интерактивный урок: Conditionals 0, 1, 2, 3 и Mixed Conditionals',
        subject: 'Английский язык',
        desc: 'Конспект занятия на 60 мин с коммуникативными ситуациями и разбором ошибок.',
        prompt: 'Разработай конспект индивидуального занятия на 60 минут: «Условные предложения в английском языке (Conditionals 0, 1, 2, 3 и Mixed Conditionals)». Добавь наглядную сравнительную таблицу, речевые сценарии и 5 упражнений на трансформацию предложений.',
        targetTab: 'lesson_plan',
    },
    {
        id: 12,
        category: 'quiz',
        title: 'Тест на Word Formation: Префиксы, суффиксы и негативные частицы',
        subject: 'Английский язык',
        desc: 'Экзаменационный формат словообразования с контекстными предложениями.',
        prompt: 'Составь тест из 10 экзаменационных предложений формата Word Formation (словообразование существительных, прилагательных с отрицательными приставками un-, in-, im-, dis- и наречий). Дай контекст, исходное опорное слово и верный ответ с комментарием.',
        targetTab: 'quiz_gen',
    },

    // ─── ХИМИЯ ───
    {
        id: 13,
        category: 'quiz',
        title: 'Трехуровневое дифференцированное ДЗ: Электролиз растворов солей',
        subject: 'Химия',
        desc: 'Уровень А (база), уровень Б (стандарт экзамена), уровень С (высокий балл / Фарадей).',
        prompt: 'Составь домашнее задание по химии по теме «Электролиз водных растворов солей»: 2 задания уровня База (составить уравнения полуреакций), 2 задания стандарта экзамена (продукты электролиза солей бескислородных и кислородсодержащих кислот) и 1 расчетную задачу по закону Фарадея.',
        targetTab: 'quiz_gen',
    },
    {
        id: 14,
        category: 'pedagogy',
        title: 'Педагогическая мнемоника: Электронный баланс и степени окисления',
        subject: 'Химия',
        desc: 'Окислитель (грабитель электронов) и восстановитель (щедрый донор).',
        prompt: 'Как объяснить ученику логику окислительно-восстановительных процессов (ОВР) и метода электронного баланса? Дай яркую метафору для ролей окислителя и восстановителя, мнемонический блок > [!MNEMONIC] и пошаговый алгоритм расстановки коэффициентов.',
        targetTab: 'methodist',
    },

    // ─── БИОЛОГИЯ ───
    {
        id: 15,
        category: 'lesson_plan',
        title: 'Техкарта урока: Биосинтез белка (транскрипция и трансляция)',
        subject: 'Биология',
        desc: 'Пошаговый разбор этапов, таблицы генетического кода и решение задач.',
        prompt: 'Составь подробную технологическую карту урока на 60 минут: «Молекулярные основы наследственности: транскрипция, процессинг и трансляция белка». Включи разминку, работу с таблицей генетического кода и разбор 2 задач на комплементарность ДНК-иРНК-тРНК.',
        targetTab: 'lesson_plan',
    },
    {
        id: 16,
        category: 'quiz',
        title: 'Задачи по генетике: Сцепленное с полом наследование и группы крови',
        subject: 'Биология',
        desc: 'Банк генетических задач с составлением схем скрещивания и вероятностями.',
        prompt: 'Сгенерируй 4 разноуровневые генетические задачи: 2 задачи на группы крови и резус-фактор (система AB0), 1 задачу на наследование дальтонизма/гемофилии (сцепление с X-хромосомой) и 1 задачу на закон Моргана со сцеплением генов. Приведи схемы скрещивания.',
        targetTab: 'quiz_gen',
    },

    // ─── ИСТОРИЯ И ОБЩЕСТВОВЕДЕНИЕ ───
    {
        id: 17,
        category: 'lesson_plan',
        title: 'Урок-обобщение: Статуты ВКЛ 1529, 1566 и 1588 годов в сравнении',
        subject: 'История и Обществоведение',
        desc: 'Сравнительная таблица, терминологический диктант и проблемные вопросы.',
        prompt: 'Разработай план урока-семинара: «Статуты Великого Княжества Литовского 1529, 1566 и 1588 гг. как памятники правовой мысли Европы». Включи сравнительную таблицу (источники, структура, статус шляхты, государственный язык) и 5 контрольных вопросов.',
        targetTab: 'lesson_plan',
    },
    {
        id: 18,
        category: 'quiz',
        title: 'Тест: Формы правления, политические режимы и ветви власти',
        subject: 'История и Обществоведение',
        desc: 'Тестовые задания с дистракторами по конституционному праву и политологии.',
        prompt: 'Составь проверочный тест по обществоведению из 8 вопросов: признаки правового государства, отличия президентской и парламентской республик, пропорциональная и мажоритарная избирательные системы. К каждому вопросу дай 4 варианта с дистракторами и пояснение.',
        targetTab: 'quiz_gen',
    },

    // ─── ИНФОРМАТИКА ───
    {
        id: 19,
        category: 'pedagogy',
        title: 'Интуитивное объяснение динамического программирования (Python)',
        subject: 'Информатика',
        desc: 'От наивной рекурсии к мемоизации и табличному методу на примере задачи о кузнечике/рюкзаке.',
        prompt: 'Как просто и наглядно объяснить принцип динамического программирования школьнику? Покажи переход от медленной рекурсии к табличному ДП на задаче «Кузнечик на числовой прямой» с чистым кодом на Python и блок-схемой логики.',
        targetTab: 'methodist',
    },
    {
        id: 20,
        category: 'quiz',
        title: 'Практикум: Трассировка алгоритмов и логические выражения',
        subject: 'Информатика',
        desc: 'Задания на чтение чужого кода, анализ циклов и упрощение булевых формул.',
        prompt: 'Сгенерируй подборку из 5 задач по информатике: 2 задания на трассировку вложенных циклов и массивов на Python, 2 задания на упрощение логических выражений (законы де Моргана) и 1 задание на перевод чисел между 2, 8, 10 и 16 системами счисления.',
        targetTab: 'quiz_gen',
    },
];

export default function PromptLibraryModal({ isOpen, onClose, onSelectPrompt }) {
    const [activeCat, setActiveCat] = useState('all');
    const [activeSubj, setActiveSubj] = useState('Все предметы');
    const [search, setSearch] = useState('');
    const [copiedId, setCopiedId] = useState(null);

    if (!isOpen) return null;

    const filteredPrompts = PRESET_PROMPTS.filter((p) => {
        const matchesCat = activeCat === 'all' || p.category === activeCat;
        const matchesSubj = activeSubj === 'Все предметы' || p.subject === activeSubj;
        const matchesSearch =
            p.title.toLowerCase().includes(search.toLowerCase()) ||
            p.desc.toLowerCase().includes(search.toLowerCase()) ||
            p.subject.toLowerCase().includes(search.toLowerCase()) ||
            p.prompt.toLowerCase().includes(search.toLowerCase());
        return matchesCat && matchesSubj && matchesSearch;
    });

    const handleApply = (item) => {
        onSelectPrompt(item);
        setCopiedId(item.id);
        setTimeout(() => {
            setCopiedId(null);
            onClose();
        }, 400);
    };

    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-4 bg-slate-950/70 backdrop-blur-sm animate-fadeIn">
            <div className="relative w-full max-w-4xl rounded-3xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-2xl overflow-hidden flex flex-col max-h-[90vh]">
                {/* Header */}
                <div className="px-6 py-4.5 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between bg-slate-50/70 dark:bg-slate-800/40">
                    <div className="flex items-center gap-3">
                        <div className="w-10 h-10 rounded-2xl bg-[#7D39EB] text-white flex items-center justify-center shadow-md shadow-violet-500/20">
                            <Sparkles className="w-5 h-5 text-[#C6FF33]" />
                        </div>
                        <div>
                            <h3 className="font-extrabold text-base text-slate-900 dark:text-white">
                                Каталог методических промптов
                            </h3>
                            <p className="text-xs text-slate-500 dark:text-slate-400">
                                Проверенные педагогические сценарии и запросы для преподавания любых предметов
                            </p>
                        </div>
                    </div>
                    <button
                        type="button"
                        onClick={onClose}
                        className="p-2 rounded-xl text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors cursor-pointer"
                        title="Закрыть"
                    >
                        <X className="w-5 h-5" />
                    </button>
                </div>

                {/* Search & Filters */}
                <div className="p-4 sm:p-5 border-b border-slate-100 dark:border-slate-800 space-y-3 bg-white dark:bg-slate-900 shrink-0">
                    {/* Search Input */}
                    <div className="relative">
                        <Search className="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400" />
                        <input
                            type="text"
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            placeholder="Поиск по теме, предмету, ключевым словам или тексту промпта..."
                            className="w-full pl-10 pr-10 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 dark:bg-slate-800/80 text-xs font-semibold text-slate-900 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-violet-500/30 focus:border-violet-500 transition-all"
                        />
                        {search && (
                            <button
                                type="button"
                                onClick={() => setSearch('')}
                                className="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 text-xs p-1"
                            >
                                ✕
                            </button>
                        )}
                    </div>

                    {/* Subject Filter Bar */}
                    <div className="flex items-center gap-1.5 overflow-x-auto pb-1 no-scrollbar">
                        <span className="text-[10px] font-bold text-slate-400 uppercase tracking-wider shrink-0 mr-1">
                            Предмет:
                        </span>
                        {PROMPT_SUBJECTS.map((subj) => (
                            <button
                                key={subj}
                                type="button"
                                onClick={() => setActiveSubj(subj)}
                                className={`px-2.5 py-1 rounded-lg text-[11px] font-bold whitespace-nowrap transition-all cursor-pointer ${
                                    activeSubj === subj
                                        ? 'bg-[#7D39EB] text-white shadow-xs'
                                        : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-700'
                                }`}
                            >
                                {subj}
                            </button>
                        ))}
                    </div>

                    {/* Category Filter Bar */}
                    <div className="flex items-center gap-1.5 overflow-x-auto pb-1 no-scrollbar">
                        <span className="text-[10px] font-bold text-slate-400 uppercase tracking-wider shrink-0 mr-1">
                            Формат:
                        </span>
                        {PROMPT_CATEGORIES.map((cat) => (
                            <button
                                key={cat.id}
                                type="button"
                                onClick={() => setActiveCat(cat.id)}
                                className={`px-2.5 py-1 rounded-lg text-[11px] font-bold whitespace-nowrap transition-all cursor-pointer ${
                                    activeCat === cat.id
                                        ? 'bg-[#0C0A14] text-white dark:bg-white dark:text-slate-900 shadow-2xs'
                                        : 'bg-slate-50 dark:bg-slate-800/60 border border-slate-200/80 dark:border-slate-700 text-slate-600 dark:text-slate-400 hover:bg-slate-100'
                                }`}
                            >
                                {cat.label}
                            </button>
                        ))}
                    </div>
                </div>

                {/* Prompts List */}
                <div className="flex-1 overflow-y-auto p-4 sm:p-6 space-y-3 bg-[#FAF8FF]/40 dark:bg-slate-950/40">
                    {filteredPrompts.length === 0 ? (
                        <div className="text-center py-12 text-slate-400 text-xs space-y-2">
                            <div className="text-2xl">🔍</div>
                            <div className="font-bold">Промпты по вашему фильтру не найдены</div>
                            <div className="text-[11px]">Попробуйте сбросить фильтры или изменить поисковый запрос</div>
                        </div>
                    ) : (
                        filteredPrompts.map((item) => (
                            <div
                                key={item.id}
                                className="group p-4 sm:p-4.5 rounded-2xl border border-slate-200/80 dark:border-slate-800 hover:border-violet-300 dark:hover:border-violet-700 bg-white dark:bg-slate-800/80 hover:bg-violet-50/20 dark:hover:bg-violet-950/20 transition-all flex flex-col sm:flex-row sm:items-center justify-between gap-4 shadow-2xs"
                            >
                                <div className="space-y-1.5 max-w-2xl">
                                    <div className="flex flex-wrap items-center gap-2">
                                        <span className="text-[10.5px] font-extrabold px-2 py-0.5 rounded-md bg-violet-100 dark:bg-violet-950/80 text-violet-700 dark:text-violet-300">
                                            {item.subject}
                                        </span>
                                        <span className="text-[10px] font-bold text-slate-400">
                                            {PROMPT_CATEGORIES.find(c => c.id === item.category)?.label}
                                        </span>
                                        <h4 className="font-bold text-xs sm:text-sm text-slate-900 dark:text-white group-hover:text-violet-600 dark:group-hover:text-violet-400 transition-colors">
                                            {item.title}
                                        </h4>
                                    </div>
                                    <p className="text-[11.5px] text-slate-600 dark:text-slate-300 leading-relaxed font-medium">
                                        {item.desc}
                                    </p>
                                    <div className="text-[10.5px] text-slate-400 dark:text-slate-500 line-clamp-2 italic bg-slate-50 dark:bg-slate-900/60 p-2 rounded-xl border border-slate-100 dark:border-slate-800/60 font-mono">
                                        «{item.prompt}»
                                    </div>
                                </div>

                                <button
                                    type="button"
                                    onClick={() => handleApply(item)}
                                    className={`shrink-0 inline-flex items-center justify-center gap-1.5 px-4 py-2.5 rounded-xl font-bold text-xs transition-all cursor-pointer shadow-2xs ${
                                        copiedId === item.id
                                            ? 'bg-emerald-600 text-white'
                                            : 'bg-[#7D39EB] hover:bg-violet-700 text-white'
                                    }`}
                                >
                                    {copiedId === item.id ? (
                                        <>
                                            <Check className="w-3.5 h-3.5" />
                                            <span>Применено!</span>
                                        </>
                                    ) : (
                                        <>
                                            <Sparkles className="w-3.5 h-3.5 text-[#C6FF33]" />
                                            <span>Использовать</span>
                                        </>
                                    )}
                                </button>
                            </div>
                        ))
                    )}
                </div>

                {/* Footer Count */}
                <div className="px-6 py-3 border-t border-slate-100 dark:border-slate-800 bg-slate-50/70 dark:bg-slate-800/40 flex items-center justify-between text-[11px] text-slate-500 dark:text-slate-400 shrink-0">
                    <span>Всего промптов: <strong className="text-slate-800 dark:text-white">{filteredPrompts.length}</strong> из {PRESET_PROMPTS.length}</span>
                    <span className="text-[10px] text-slate-400">Edusfera AI Engine · 2026</span>
                </div>
            </div>
        </div>
    );
}
