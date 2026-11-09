import React, { useState, useEffect, useRef } from 'react';
import { motion, AnimatePresence } from 'motion/react';
import { 
    Lock, 
    ArrowRight, 
    Check, 
    Download, 
    FileText, 
    ShieldCheck, 
    Clock,
    Printer,
    Sparkles,
    AlertCircle,
    Loader2,
    Calculator,
    Atom,
    BookOpen,
    Languages,
    Globe,
    FlaskConical,
    Dna,
    Landmark,
    Scale,
    Binary,
    CheckCircle2,
    Menu,
    X,
    ChevronDown,
    LogOut,
    UserPlus,
    MessageSquare,
    Calendar,
    GraduationCap,
    FileCheck,
    DollarSign,
    Users,
    Share2,
    Copy,
    Maximize2,
    Eye,
    Tag,
    Percent,
    SlidersHorizontal,
    Layers,
    Award
} from 'lucide-react';

import PainSolutionSection from './generator/PainSolutionSection';
import TimeSavedCalculator from './generator/TimeSavedCalculator';
import BundleBreakdownSection from './generator/BundleBreakdownSection';
import PricingTiersSection from './generator/PricingTiersSection';
import GuaranteeSection from './generator/GuaranteeSection';
import TestimonialsSection from './generator/TestimonialsSection';
import FaqSection from './generator/FaqSection';
import Footer from './Footer';

const SUBJECTS = [
    { id: 'math', name: 'Математика', icon: Calculator },
    { id: 'physics', name: 'Физика', icon: Atom },
    { id: 'russian', name: 'Русский язык', icon: BookOpen },
    { id: 'belarusian', name: 'Белорусский язык', icon: Languages },
    { id: 'english', name: 'Английский язык', icon: Globe },
    { id: 'chemistry', name: 'Химия', icon: FlaskConical },
    { id: 'biology', name: 'Биология', icon: Dna },
    { id: 'history', name: 'История Беларуси', icon: Landmark },
    { id: 'social', name: 'Обществоведение', icon: Scale },
    { id: 'it', name: 'Информатика', icon: Binary }
];

export const GRADES = [
    { id: '5', label: '5 класс', stage: 'basic' },
    { id: '6', label: '6 класс', stage: 'basic' },
    { id: '7', label: '7 класс', stage: 'basic' },
    { id: '8', label: '8 класс', stage: 'basic' },
    { id: '9', label: '9 класс', stage: 'basic_exam' },
    { id: '10', label: '10 класс', stage: 'senior' },
    { id: '11', label: '11 класс', stage: 'senior_exam' },
    { id: 'abiturient', label: 'Абитуриент', stage: 'entrant' },
];

export const WORK_TYPES = [
    { 
        id: 'independent', 
        name: 'Самостоятельная работа', 
        shortLabel: 'Самостоятельная',
        icon: FileText, 
        duration: '15–20 мин',
        desc: 'Отработка и первичное закрепление алгоритма темы',
        minGrade: 5,
        defaultScore: 10
    },
    { 
        id: 'control', 
        name: 'Контрольная работа', 
        shortLabel: 'Контрольная',
        icon: CheckCircle2, 
        duration: '45 мин (1 урок)',
        desc: 'Тематический контроль усвоения раздела программы РБ',
        minGrade: 5,
        defaultScore: 10
    },
    { 
        id: 'diagnostic', 
        name: 'Диагностический срез', 
        shortLabel: 'Диагностика',
        icon: AlertCircle, 
        duration: '30–45 мин',
        desc: 'Входной/рубежный срез, карта пробелов ученика',
        minGrade: 5,
        defaultScore: 10
    },
    { 
        id: 'test', 
        name: 'Проверочный экспресс-тест', 
        shortLabel: 'Экспресс-тест',
        icon: Tag, 
        duration: '10–15 мин',
        desc: 'Оперативный опрос базовых понятий с выбором ответа',
        minGrade: 5,
        defaultScore: 10
    },
    { 
        id: 'ct_ce', 
        name: 'Пробный экзамен ЦЭ / ЦТ', 
        shortLabel: 'ЦЭ / ЦТ',
        icon: GraduationCap, 
        duration: '90–180 мин',
        desc: 'Спецификации экзамена 2026, бланк ответов и 100-балльная шкала',
        minGrade: 10,
        defaultScore: 100
    },
    { 
        id: 'olympiad', 
        name: 'Олимпиадные задания', 
        shortLabel: 'Олимпиада',
        icon: Sparkles, 
        duration: '45–60 мин',
        desc: 'Углубленный уровень и нестандартная логика для гимназий',
        minGrade: 5,
        defaultScore: 10
    },
];

export const DIFFICULTY_LEVELS = [
    { id: 'base', label: 'Базовый', desc: 'Стандарт школы (5–7 баллов)' },
    { id: 'advanced', label: 'Повышенный', desc: 'Гимназия и профиль (8–10 баллов)' },
    { id: 'olympiad', label: 'Олимпиадный', desc: 'Нестандартные задачи (9–10+)' },
];

export const GRADE_PRESETS = {
    '5': {
        math: [
            'Сложение и вычитание десятичных дробей',
            'Периметр и площадь прямоугольника',
            'Простые уравнения с натуральными числами'
        ],
        russian: [
            'Правописание безударных гласных в корне слова',
            'Члены предложения: главные и второстепенные',
            'Мягкий знак после шипящих на конце слов'
        ],
        history: [
            'Первые поселения на территории Беларуси',
            'Полоцкое княжество и князь Всеслав Чародей',
            'Жизнь и культура восточных славян'
        ]
    },
    '6': {
        math: [
            'Действия с обыкновенными дробями и смешанными числами',
            'Пропорции, масштаб и проценты',
            'Положительные и отрицательные числа: координатная прямая'
        ],
        biology: [
            'Строение растительной клетки и ткани растений',
            'Корень и побег: строение и функции',
            'Цветок и плод: размножение цветковых растений'
        ]
    },
    '7': {
        math: [
            'Линейные уравнения с одной переменной',
            'Формулы сокращенного умножения (квадрат суммы и разности)',
            'Признаки равенства треугольников и параллельные прямые'
        ],
        physics: [
            'Плотность вещества и расчет массы тела',
            'Сила тяжести, сила упругости и закон Гука',
            'Давление твердых тел, жидкостей и газов'
        ],
        history: [
            'Образование Великого Княжества Литовского (князь Миндовг)',
            'Кревская уния 1385 г. и ее исторические последствия',
            'Грюнвальдская битва 1410 г. и разгром Тевтонского ордена'
        ],
        belarusian: [
            'Правапіс прыстаўных і ўстаўных галосных',
            'Правапіс вялікай і малой літар',
            'Словазлучэнне: віды сувязі'
        ]
    },
    '8': {
        math: [
            'Квадратные корни и арифметический квадратный корень',
            'Теорема Пифагора и метрические соотношения в треугольнике',
            'Неполные и полные квадратные уравнения'
        ],
        chemistry: [
            'Периодический закон и строение атома',
            'Типы химических связей: ковалентная и ионная',
            'Расчеты по химическим уравнениям (количество вещества)'
        ],
        physics: [
            'Тепловые явления: удельная теплоемкость и уравнение теплового баланса',
            'Закон Ома для участка цепи и расчет электрического сопротивления',
            'Параллельное и последовательное соединение проводников'
        ]
    },
    '9': {
        math: [
            'Квадратичная функция и ее график (парабола)',
            'Теорема Виета и разложение квадратного трехчлена на множители',
            'Арифметическая и геометрическая прогрессии'
        ],
        physics: [
            'Законы Ньютона и прямолинейное равноускоренное движение',
            'Закон всемирного тяготения и свободное падение',
            'Импульс тела и закон сохранения импульса'
        ],
        history: [
            'Белорусские земли в составе Российской империи (конец XVIII — XIX в.)',
            'Восстание 1863–1864 гг. и Кастусь Калиновский',
            'Отмена крепостного права 1861 г. в Беларуси'
        ]
    },
    '10': {
        math: [
            'Тригонометрические формулы и простейшие уравнения',
            'Степени с действительным показателем и логарифмы',
            'Взаимное расположение прямых и плоскостей в пространстве'
        ],
        chemistry: [
            'Углеводороды: алканы, алкены, алкины и их свойства',
            'Окислительно-восстановительные реакции (электронный баланс)',
            'Скорость химических реакций и химическое равновесие'
        ]
    },
    '11': {
        math: [
            'Иррациональные уравнения (часть Б ЦТ/ЦЭ)',
            'Тригонометрический отбор корней на окружности',
            'Стереометрия: углы и расстояния в пирамидах'
        ],
        physics: [
            'Законы постоянного тока и расчет смешанных цепей',
            'Закон сохранения импульса и механической энергии',
            'Идеальный газ: изопроцессы и уравнение Клапейрона'
        ],
        history: [
            'Важнейшие битвы и договоры ВКЛ (Грюнвальд, Статуты)',
            'Полоцкое и Туровское княжества: политический строй',
            'Освобождение Беларуси в 1944 г. (операция Багратион)'
        ]
    },
    'abiturient': {
        math: [
            'Иррациональные уравнения (часть Б ЦТ/ЦЭ)',
            'Тригонометрический отбор корней на окружности',
            'Стереометрия: углы и расстояния в пирамидах'
        ],
        physics: [
            'Законы постоянного тока и расчет смешанных цепей',
            'Закон сохранения импульса и механической энергии',
            'Идеальный газ: изопроцессы и уравнение Клапейрона'
        ],
        history: [
            'Важнейшие битвы и договоры ВКЛ (Грюнвальд, Статуты)',
            'Полоцкое и Туровское княжества: политический строй',
            'Освобождение Беларуси в 1944 г. (операция Багратион)'
        ]
    }
};

const PRESETS = {
    math: [
        'Иррациональные уравнения (часть Б ЦТ/ЦЭ)',
        'Тригонометрический отбор корней на окружности',
        'Стереометрия: углы и расстояния в пирамидах'
    ],
    physics: [
        'Законы постоянного тока и расчет смешанных цепей',
        'Закон сохранения импульса и механической энергии',
        'Идеальный газ: изопроцессы и уравнение Клапейрона'
    ],
    russian: [
        'Правописание Н и НН в причастиях и отглагольных прилагательных',
        'Слитное и раздельное написание НЕ с разными частями речи',
        'Пунктуация в сложноподчиненных предложениях'
    ],
    belarusian: [
        'Правапіс галосных О, Э, А, Я і пераход у ненаціскным становішчы',
        'Правапіс мяккага знака і апострафа',
        'Знакі прыпынку ў складаназалежных сказах з некалькімі даданымі'
    ],
    english: [
        'Mixed Conditionals and Wish / If only structures',
        'Phrasal Verbs & Dependent Prepositions in Exam Tasks',
        'Passive Voice & Complex Object transformations'
    ],
    chemistry: [
        'Окислительно-восстановительные реакции (электронный баланс)',
        'Электролиз водных растворов солей и закон Фарадея',
        'Генетическая цепочка превращений органических веществ'
    ],
    biology: [
        'Задачи по молекулярной биологии: биосинтез белка',
        'Генетические задачи: группы крови и сцепленное наследование',
        'Строение и органоиды эукариотической клетки'
    ],
    history: [
        'Важнейшие битвы и договоры ВКЛ (Грюнвальд, Статуты)',
        'Полоцкое и Туровское княжества: политический строй',
        'Освобождение Беларуси в 1944 г. (операция Багратион)'
    ],
    social: [
        'Формы правления, политические режимы и разделение властей',
        'Гражданское и уголовное право: дееспособность',
        'Экономические циклы, инфляция и функции денег'
    ],
    it: [
        'Трассировка алгоритмов и циклов (анализ вывода кода)',
        'Системы счисления: двоичная, восьмеричная, шестнадцатеричная',
        'Логические таблицы истинности и булевы выражения'
    ]
};

const SAMPLE_PREVIEWS = {
    math: {
        title: 'ПРОВЕРОЧНАЯ РАБОТА ПО МАТЕМАТИКЕ',
        v1: {
            variantNum: 1,
            taskA1: 'Укажите корень уравнения √(2x + 7) = x - 4:',
            options: ['1) 1', '2) 9', '3) 1 и 9', '4) -1'],
            correctIndex: 1,
            taskB1: 'Решите уравнение √(x² - 5x + 6) · √(x - 1) = 0. В бланк ответов запишите произведение всех действительных корней уравнения:',
            answer: '6'
        },
        v2: {
            variantNum: 2,
            taskA1: 'Укажите корень уравнения √(3x + 4) = x - 2:',
            options: ['1) -1', '2) 7', '3) 0 и 7', '4) 2'],
            correctIndex: 1,
            taskB1: 'Решите уравнение √(x² - 7x + 12) · √(x - 2) = 0. В бланк ответов запишите произведение всех действительных корней уравнения:',
            answer: '24'
        },
        keys: [
            { varNum: 1, a1: '2 (9)', b1: '6', note: 'Посторонний корень x=1 отсекается условием x - 4 ≥ 0. В Б1 корни 1, 2, 3.' },
            { varNum: 2, a1: '2 (7)', b1: '24', note: 'Посторонний корень x=0 отсекается условием x - 2 ≥ 0. В Б1 корни 2, 3, 4.' },
            { varNum: 3, a1: '4 (5)', b1: '10', note: 'Ловушка знака при возведении в квадрат (x-a)². Проверка ОДЗ подкоренного выражения.' },
            { varNum: 4, a1: '1 (8)', b1: '15', note: 'Ложное сокращение на корень без фиксации равенства нулю.' }
        ]
    },
    physics: {
        title: 'ПРОВЕРОЧНАЯ РАБОТА ПО ФИЗИКЕ',
        v1: {
            variantNum: 1,
            taskA1: 'Источник тока с ЭДС 12 В и внутренним сопротивлением r = 1 Ом замкнут на резистор сопротивлением R = 3 Ом. Определите силу тока в электрической цепи:',
            options: ['1) 48 А', '2) 3 А', '3) 4 А', '4) 12 А'],
            correctIndex: 1,
            taskB1: 'Тело массой m = 2 кг движется со скоростью v = 10 м/с. Под действием постоянной силы торможения тело остановилось, пройдя путь s = 25 м. Определите модуль силы торможения (в Н):',
            answer: '4'
        },
        v2: {
            variantNum: 2,
            taskA1: 'Источник тока с ЭДС 18 В и внутренним сопротивлением r = 1 Ом замкнут на резистор сопротивлением R = 5 Ом. Определите силу тока в электрической цепи:',
            options: ['1) 3 А', '2) 18 А', '3) 3.6 А', '4) 9 А'],
            correctIndex: 0,
            taskB1: 'Тело массой m = 3 кг движется со скоростью v = 8 м/с. Под действием постоянной силы торможения тело остановилось, пройдя путь s = 16 м. Определите модуль силы торможения (в Н):',
            answer: '6'
        },
        keys: [
            { varNum: 1, a1: '2 (3 А)', b1: '4', note: 'Игнорирование внутреннего сопротивления r дает ошибочный ток 12 / 3 = 4 А (вар. 3).' },
            { varNum: 2, a1: '1 (3 А)', b1: '6', note: 'Деление только на внешнее сопротивление 18 / 5 = 3.6 А (вар. 3). Забывают квадрат скорости.' },
            { varNum: 3, a1: '3 (2 А)', b1: '8', note: 'Путаница закона Ома для участка цепи и для полной цепи.' },
            { varNum: 4, a1: '4 (5 А)', b1: '5', note: 'Расчет тока короткого замыкания вместо рабочего тока цепи.' }
        ]
    },
    russian: {
        title: 'ПРОВЕРОЧНАЯ РАБОТА ПО РУССКОМУ ЯЗЫКУ',
        v1: {
            variantNum: 1,
            taskA1: 'Укажите номер слова, в котором на месте пропуска пишется НН:',
            options: ['1) кова_ый', '2) решё_ая задача', '3) ветре_ый день', '4) кожа_ый'],
            correctIndex: 1,
            taskB1: 'Найдите номер предложения с пунктуационной ошибкой при обособленном определении:',
            answer: '4'
        },
        v2: {
            variantNum: 2,
            taskA1: 'Укажите номер слова, в котором на месте пропуска пишется НН:',
            options: ['1) смышлё_ый', '2) вспаха_ое поле', '3) масля_ый', '4) песча_ый'],
            correctIndex: 1,
            taskB1: 'Найдите номер предложения с пунктуационной ошибкой при деепричастном обороте:',
            answer: '3'
        },
        keys: [
            { varNum: 1, a1: '2 (решённая)', b1: '4', note: 'Исключения: кованый, жеваный, ветреный' },
            { varNum: 2, a1: '2 (вспаханное)', b1: '3', note: 'Наличие приставки образует причастие с НН' },
            { varNum: 3, a1: '1 (священный)', b1: '2', note: 'Слова-исключения со скрытым НН' },
            { varNum: 4, a1: '3 (купленный)', b1: '5', note: 'Глаголы совершенного вида' }
        ]
    },
    belarusian: {
        title: 'ПРАВЕРАЧНАЯ РАБОТА ПА БЕЛАРУСКАЙ МОВЕ',
        v1: {
            variantNum: 1,
            taskA1: 'Адзначце слова, у якім на месцы пропуску трэба пісаць літару Я:',
            options: ['1) в_снавы', '2) з_мляроб', '3) ц_гнік', '4) м_довы'],
            correctIndex: 1,
            taskB1: 'Вызначце колькасць граматычных асноў у складаным сказе:',
            answer: '3'
        },
        v2: {
            variantNum: 2,
            taskA1: 'Адзначце слова, у якім на месцы пропуску трэба пісаць літару Я:',
            options: ['1) з_ленаваты', '2) с_мнаццаць', '3) м_сяцовы', '4) б_ззубы'],
            correctIndex: 1,
            taskB1: 'Вызначце колькасць граматычных асноў у бяззлучнікавым сказе:',
            answer: '2'
        },
        keys: [
            { varNum: 1, a1: '2 (зямляроб)', b1: '3', note: 'Пераход Е, Ё ў Я ў першым складзе перад націскам' },
            { varNum: 2, a1: '2 (сямнаццаць)', b1: '2', note: 'Напісанне лічэбнікаў з коранем сем-' },
            { varNum: 3, a1: '4 (ляснік)', b1: '4', note: 'Адрозненне націскных і ненаціскных складоў' },
            { varNum: 4, a1: '1 (цярплівы)', b1: '3', note: 'Правапіс галосных пасля зацвярдзелых' }
        ]
    },
    english: {
        title: 'TEST PAPER IN ENGLISH',
        v1: {
            variantNum: 1,
            taskA1: 'Choose the correct form: If he had trained harder, he ___ the race yesterday.',
            options: ['1) will win', '2) would win', '3) would have won', '4) wins'],
            correctIndex: 2,
            taskB1: 'Fill in the correct preposition: We must look [ ... ] all unknown words in the dictionary.',
            answer: 'up'
        },
        v2: {
            variantNum: 2,
            taskA1: 'Choose the correct form: If she had left earlier, she ___ the morning flight.',
            options: ['1) wouldn\'t miss', '2) wouldn\'t have missed', '3) won\'t miss', '4) didn\'t miss'],
            correctIndex: 1,
            taskB1: 'Fill in the correct preposition: He gave [ ... ] smoking last spring after the doctor\'s advice.',
            answer: 'up'
        },
        keys: [
            { varNum: 1, a1: '3 (would have won)', b1: 'up', note: 'Third Conditional (unreal past situation)' },
            { varNum: 2, a1: '2 (wouldn\'t have missed)', b1: 'up', note: 'Phrasal verb: look up / give up' },
            { varNum: 3, a1: '1 (had known)', b1: 'forward', note: 'Look forward to + V-ing form' },
            { varNum: 4, a1: '4 (would travel)', b1: 'in', note: 'Second Conditional vs Third Conditional' }
        ]
    },
    chemistry: {
        title: 'ПРОВЕРОЧНАЯ РАБОТА ПО ХИМИИ',
        v1: {
            variantNum: 1,
            taskA1: 'Укажите формулу окислителя в реакции 2Mg + O₂ → 2MgO:',
            options: ['1) Mg', '2) O₂', '3) MgO', '4) Mg²⁺'],
            correctIndex: 1,
            taskB1: 'Вычислите массу осадка (г) при сливании 100 г 5% раствора NaCl с избытком AgNO₃:',
            answer: '12'
        },
        v2: {
            variantNum: 2,
            taskA1: 'Укажите формулу восстановителя в реакции CuO + H₂ → Cu + H₂O:',
            options: ['1) CuO', '2) H₂', '3) Cu', '4) H₂O'],
            correctIndex: 1,
            taskB1: 'Вычислите объем газа (л, н.у.) при растворении 6.5 г цинка в соляной кислоте:',
            answer: '2.24'
        },
        keys: [
            { varNum: 1, a1: '2 (O₂)', b1: '12 г', note: 'Окислитель понижает степень окисления' },
            { varNum: 2, a1: '2 (H₂)', b1: '2.24 л', note: 'Молярный объем газов 22.4 л/моль' },
            { varNum: 3, a1: '3 (Cl₂)', b1: '14.2 г', note: 'Учет коэффициентов уравнения реакции' },
            { varNum: 4, a1: '1 (Fe)', b1: '5.6 л', note: 'Расчет через количество вещества (моль)' }
        ]
    },
    biology: {
        title: 'ПРОВЕРОЧНАЯ РАБОТА ПО БИОЛОГИИ',
        v1: {
            variantNum: 1,
            taskA1: 'Какой органоид клетки отвечает за синтез молекул АТФ?',
            options: ['1) Рибосома', '2) Митохондрия', '3) Комплекс Гольджи', '4) Лизосома'],
            correctIndex: 1,
            taskB1: 'Определите вероятность (%) рождения детей с I группой крови при генотипах I^A I^0 и I^B I^0:',
            answer: '25'
        },
        v2: {
            variantNum: 2,
            taskA1: 'Какой органоид клетки обеспечивает первичный синтез полипептидных цепей?',
            options: ['1) Митохондрия', '2) Рибосома', '3) Вакуоль', '4) Центриоль'],
            correctIndex: 1,
            taskB1: 'Определите число аутосом в соматической клетке человека:',
            answer: '44'
        },
        keys: [
            { varNum: 1, a1: '2 (Митохондрия)', b1: '25%', note: 'Расщепление 1:1:1:1 по группам крови' },
            { varNum: 2, a1: '2 (Рибосома)', b1: '44', note: 'Аутосомы vs половые хромосомы (46 - 2 = 44)' },
            { varNum: 3, a1: '4 (Хлоропласт)', b1: '50%', note: 'Моногибридное скрещивание с анализирующим' },
            { varNum: 4, a1: '1 (Ядро)', b1: '12', note: 'Гаплоидный и диплоидный хромосомный набор' }
        ]
    },
    history: {
        title: 'ПРОВЕРОЧНАЯ РАБОТА ПО ИСТОРИИ БЕЛАРУСИ',
        v1: {
            variantNum: 1,
            taskA1: 'В каком году состоялась Грюнвальдская битва объединенных войск ВКЛ и Польши?',
            options: ['1) 1385', '2) 1410', '3) 1514', '4) 1569'],
            correctIndex: 1,
            taskB1: 'Запишите имя великого князя, при котором был издан Статут ВКЛ 1588 года:',
            answer: 'Сигизмунд'
        },
        v2: {
            variantNum: 2,
            taskA1: 'В каком году была подписана Кревская уния между ВКЛ и Королевством Польским?',
            options: ['1) 1385', '2) 1410', '3) 1569', '4) 1596'],
            correctIndex: 0,
            taskB1: 'Запишите имя полководца ВКЛ, одержавшего победу в битве под Оршей 1514 года:',
            answer: 'Острожский'
        },
        keys: [
            { varNum: 1, a1: '2 (1410)', b1: 'Сигизмунд III', note: 'Не путать три редакции Статутов (1529, 1566, 1588)' },
            { varNum: 2, a1: '1 (1385)', b1: 'Константин Острожский', note: 'Унии: Кревская (1385), Люблинская (1569)' },
            { varNum: 3, a1: '3 (1569)', b1: 'Витовт', note: 'Хронология княжения Ольгерда и Витовта' },
            { varNum: 4, a1: '4 (1596)', b1: 'Лев Сапега', note: 'Брестская церковная уния 1596 года' }
        ]
    },
    social: {
        title: 'ПРОВЕРОЧНАЯ РАБОТА ПО ОБЩЕСТВОВЕДЕНИЮ',
        v1: {
            variantNum: 1,
            taskA1: 'К какой форме территориального устройства относится унитарное государство?',
            options: ['1) Форма правления', '2) Форма гос. устройства', '3) Политический режим', '4) Гражданское общество'],
            correctIndex: 1,
            taskB1: 'Укажите возраст полной гражданской дееспособности гражданина Республики Беларусь (лет):',
            answer: '18'
        },
        v2: {
            variantNum: 2,
            taskA1: 'К какому элементу формы государства относится республика и монархия?',
            options: ['1) Форма правления', '2) Форма устройства', '3) Политический режим', '4) Избирательная система'],
            correctIndex: 0,
            taskB1: 'Укажите возраст наступления частичной дееспособности несовершеннолетних в РБ (лет):',
            answer: '14'
        },
        keys: [
            { varNum: 1, a1: '2 (Форма устройства)', b1: '18', note: 'Элементы формы государства: правление, устройство, режим' },
            { varNum: 2, a1: '1 (Форма правления)', b1: '14', note: 'Частичная дееспособность (14-18 лет)' },
            { varNum: 3, a1: '3 (Демократия)', b1: '21', note: 'Ценз для избрания депутатом Палаты представителей' },
            { varNum: 4, a1: '4 (Правовое гос-во)', b1: '35', note: 'Президентский возрастной ценз Конституции РБ' }
        ]
    },
    it: {
        title: 'ПРОВЕРОЧНАЯ РАБОТА ПО ИНФОРМАТИКЕ',
        v1: {
            variantNum: 1,
            taskA1: 'Какое число соответствует двоичной записи 1011 в десятичной системе счисления?',
            options: ['1) 9', '2) 11', '3) 13', '4) 15'],
            correctIndex: 1,
            taskB1: 'Определите результат выполнения выражения print(2 ** 3 + 10 // 3):',
            answer: '11'
        },
        v2: {
            variantNum: 2,
            taskA1: 'Какое число соответствует двоичной записи 1101 в десятичной системе счисления?',
            options: ['1) 11', '2) 13', '3) 14', '4) 15'],
            correctIndex: 1,
            taskB1: 'Определите результат выполнения выражения print(3 ** 2 + 14 // 4):',
            answer: '12'
        },
        keys: [
            { varNum: 1, a1: '2 (11)', b1: '11', note: 'Оператор // выполняет целочисленное деление' },
            { varNum: 2, a1: '2 (13)', b1: '12', note: 'Приоритет операций возведения в степень **' },
            { varNum: 3, a1: '1 (7)', b1: '8', note: 'Степени двойки 2^0, 2^1, 2^2, 2^3' },
            { varNum: 4, a1: '4 (15)', b1: '14', note: 'Остаток от деления % vs целочисленное деление //' }
        ]
    }
};

const VARIANT_COUNTS = [
    { count: 10, label: '10 вариантов' },
    { count: 15, label: '15 вариантов', popular: true },
    { count: 25, label: '25 вариантов' }
];

const EMAIL_DOMAINS = ['@gmail.com', '@mail.ru', '@yandex.by'];

export default function AntiCheatGeneratorLanding() {
    const [user, setUser] = useState(typeof window !== 'undefined' ? window.EDUSFERA_USER : null);
    const [linkedAccounts, setLinkedAccounts] = useState(typeof window !== 'undefined' ? (window.EDUSFERA_LINKED_ACCOUNTS || []) : []);
    const [userMenuOpen, setUserMenuOpen] = useState(false);
    const [mobileMenuOpen, setMobileMenuOpen] = useState(false);
    const [isMobileDevice, setIsMobileDevice] = useState(false);
    const userMenuRef = useRef(null);
    const csrfToken = typeof window !== 'undefined' ? window.EDUSFERA_CSRF_TOKEN : '';

    const [selectedSubject, setSelectedSubject] = useState('math');
    const [topic, setTopic] = useState('Линейные уравнения с одной переменной');
    const [variantCount, setVariantCount] = useState(15);
    const [tasksCountA, setTasksCountA] = useState(2);
    const [tasksCountB, setTasksCountB] = useState(1);
    const [showAdvancedSettings, setShowAdvancedSettings] = useState(false);
    const [selectedGrade, setSelectedGrade] = useState('7');
    const [selectedWorkType, setSelectedWorkType] = useState('independent');
    const [selectedDifficulty, setSelectedDifficulty] = useState('base');
    const [email, setEmail] = useState(user?.email || '');
    const [showPayModal, setShowPayModal] = useState(false);
    const [isQuarterPass, setIsQuarterPass] = useState(false);
    const [previewTab, setPreviewTab] = useState('v1'); // 'v1' | 'v2' | 'keys'
    const [isSubmitting, setIsSubmitting] = useState(false);
    const [errorMessage, setErrorMessage] = useState('');
    const [showZoomModal, setShowZoomModal] = useState(false);

    useEffect(() => {
        if (typeof window !== 'undefined') {
            const isChMobile = typeof navigator !== 'undefined' && navigator.userAgentData && navigator.userAgentData.mobile === true;
            const isMobileUA = typeof navigator !== 'undefined' && /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini|Mobile|Silk|HarmonyOS/i.test(navigator.userAgent);
            const isIpadTouch = typeof navigator !== 'undefined' && /Macintosh/i.test(navigator.userAgent) && navigator.maxTouchPoints > 1 && typeof screen !== 'undefined' && (screen.width <= 1024 || screen.height <= 1024);
            const detected = Boolean(window.EDUSFERA_IS_MOBILE) || Boolean(isChMobile || isMobileUA || isIpadTouch);
            setIsMobileDevice(detected);

            if (window.EDUSFERA_USER) {
                setUser(window.EDUSFERA_USER);
            }
            if (window.EDUSFERA_LINKED_ACCOUNTS) {
                setLinkedAccounts(window.EDUSFERA_LINKED_ACCOUNTS);
            }
        }
    }, []);

    const cabinetUrl = isMobileDevice ? '/m' : '/admin';
    const financeUrl = isMobileDevice ? '/m?tab=finance' : '/admin/tutor-subscription-page';
    const scheduleUrl = isMobileDevice ? '/m?tab=lessons' : '/admin/tutor-availability-page';
    const lessonsUrl = isMobileDevice ? '/m?tab=lessons' : '/admin/lessons';
    const messagesUrl = isMobileDevice ? '/m?tab=chat' : '/admin/messages';
    const diagnosticUrl = isMobileDevice ? '/m?tab=lessons' : '/admin/diagnostic';
    const homeworkUrl = isMobileDevice ? '/m?tab=lessons' : '/admin/homework';
    const walletUrl = isMobileDevice ? '/m?tab=finance' : '/admin/wallet';

    useEffect(() => {
        const handleClickOutside = (event) => {
            if (userMenuRef.current && !userMenuRef.current.contains(event.target)) {
                setUserMenuOpen(false);
            }
        };
        document.addEventListener('mousedown', handleClickOutside);
        return () => {
            document.removeEventListener('mousedown', handleClickOutside);
        };
    }, []);

    const handleLogout = () => {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '/logout';
        
        const csrfInput = document.createElement('input');
        csrfInput.type = 'hidden';
        csrfInput.name = '_token';
        csrfInput.value = csrfToken;
        
        form.appendChild(csrfInput);
        document.body.appendChild(form);
        form.submit();
    };

    const handleSwitchAccount = (accId) => {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = `/account/switch/${accId}`;
        
        const csrfInput = document.createElement('input');
        csrfInput.type = 'hidden';
        csrfInput.name = '_token';
        csrfInput.value = csrfToken;
        
        form.appendChild(csrfInput);
        document.body.appendChild(form);
        form.submit();
    };

    useEffect(() => {
        if (mobileMenuOpen) {
            document.body.style.overflow = 'hidden';
        } else {
            document.body.style.overflow = '';
        }
        return () => {
            document.body.style.overflow = '';
        };
    }, [mobileMenuOpen]);

    const handleNavClick = (target) => {
        setMobileMenuOpen(false);
        if (target.startsWith('#')) {
            window.location.href = `/${target}`;
            return;
        }
        window.location.href = target;
    };

    const navItems = [
        { label: 'Каталог', href: '/tutors' },
        { label: 'Генератор ЦТ', href: '/generator', badge: '5 BYN' },
        { label: 'ИИ-Диагностика', href: '/diagnostic' },
        { label: 'Новости', href: '/news' },
        { label: 'Репетиторам', href: '/for-tutors', isSpecial: true },
    ];

    const handleSubjectChange = (subId) => {
        setSelectedSubject(subId);
        const gradeTopic = GRADE_PRESETS[selectedGrade]?.[subId]?.[0];
        if (gradeTopic) {
            setTopic(gradeTopic);
        } else if (PRESETS[subId] && PRESETS[subId][0]) {
            setTopic(PRESETS[subId][0]);
        }
    };

    const handleGradeChange = (gradeId) => {
        setSelectedGrade(gradeId);
        const gradeNum = parseInt(gradeId, 10);
        // Pedagogical auto-adaptation: grades 5-9 cannot take CT/CE
        if (gradeNum < 10 && selectedWorkType === 'ct_ce') {
            setSelectedWorkType('independent');
        }
        const gradeTopic = GRADE_PRESETS[gradeId]?.[selectedSubject]?.[0];
        if (gradeTopic) {
            setTopic(gradeTopic);
        }
    };

    const handleWorkTypeChange = (typeId) => {
        const gradeNum = parseInt(selectedGrade, 10);
        // If CT/CE is picked for middle school, switch grade to 11
        if (typeId === 'ct_ce' && gradeNum < 10) {
            setSelectedGrade('11');
            const gradeTopic = GRADE_PRESETS['11']?.[selectedSubject]?.[0];
            if (gradeTopic) {
                setTopic(gradeTopic);
            }
        }
        setSelectedWorkType(typeId);
    };

    useEffect(() => {
        const handleKeyDown = (e) => {
            if (e.key === 'Escape') {
                setShowPayModal(false);
            }
        };
        window.addEventListener('keydown', handleKeyDown);
        return () => window.removeEventListener('keydown', handleKeyDown);
    }, []);

    const appendEmailDomain = (domain) => {
        const prefix = email.split('@')[0].trim();
        if (prefix) {
            setEmail(prefix + domain);
            if (errorMessage) setErrorMessage('');
        }
    };

    const handleInitiateCheckout = async (e) => {
        e.preventDefault();
        setErrorMessage('');

        const cleanEmail = email.trim();
        if (!cleanEmail || !cleanEmail.includes('@')) {
            setErrorMessage('Пожалуйста, введите корректный адрес электронной почты.');
            return;
        }

        const currentSubjectObj = SUBJECTS.find(s => s.id === selectedSubject);
        const subjectName = currentSubjectObj ? currentSubjectObj.name : selectedSubject;
        const planType = isQuarterPass ? 'quarter_pass' : 'single';
        const isAdmin = Boolean(user?.is_admin || user?.role === 'admin');

        setIsSubmitting(true);

        try {
            const response = await fetch('/checkout/generator', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({
                    email: cleanEmail,
                    subject: subjectName,
                    topic: topic,
                    count: variantCount,
                    count_a: tasksCountA,
                    count_b: tasksCountB,
                    grade: selectedGrade,
                    work_type: selectedWorkType,
                    difficulty: selectedDifficulty,
                    plan_type: planType,
                    test_bypass: isAdmin ? 1 : 0,
                }),
            });

            const data = await response.json();

            if (data.redirect_url) {
                window.location.href = data.redirect_url;
            } else if (data.errors) {
                const firstError = Object.values(data.errors)[0];
                setErrorMessage(Array.isArray(firstError) ? firstError[0] : String(firstError));
                setIsSubmitting(false);
            } else {
                setErrorMessage('Не удалось связаться с платёжным шлюзом. Пожалуйста, повторите попытку.');
                setIsSubmitting(false);
            }
        } catch (err) {
            setErrorMessage('Ошибка соединения с платёжным сервисом. Пожалуйста, попробуйте еще раз.');
            setIsSubmitting(false);
        }
    };

    const currentSample = SAMPLE_PREVIEWS[selectedSubject] || SAMPLE_PREVIEWS.math;
    const currentSubjectName = (SUBJECTS.find(s => s.id === selectedSubject) || {}).name || 'Математика';
    const selectedWorkTypeObj = WORK_TYPES.find(w => w.id === selectedWorkType) || WORK_TYPES[0];
    const selectedGradeLabel = selectedGrade === 'abiturient' ? 'Абитуриент / Выпускник' : `${selectedGrade} класс`;
    const selectedDifficultyObj = DIFFICULTY_LEVELS.find(d => d.id === selectedDifficulty) || DIFFICULTY_LEVELS[0];

    return (
        <div className="min-h-screen bg-[#0B0B0E] text-white font-sans selection:bg-[#7D39EB] selection:text-white antialiased flex flex-col justify-between relative overflow-x-hidden">
            
            {/* Ambient Background Glow (High-performance gradient mesh - Zero blur filter overhead) */}
            <div className="absolute top-0 left-1/2 -translate-x-1/2 w-full max-w-[1200px] h-[500px] pointer-events-none -z-10" style={{ background: 'radial-gradient(ellipse 65% 50% at 50% 0%, rgba(125,57,235,0.2) 0%, transparent 70%)', transform: 'translateZ(0)' }} />

            {/* ─── 1. STANDARD EDUSFERA PLATFORM HEADER (IDENTICAL TO HOMEPAGE) ─── */}
            <nav className="flex items-center justify-between px-5 py-5 sm:px-8 sm:py-6 lg:px-12 w-full max-w-[1400px] mx-auto relative z-50">
                <a href="/" className="flex items-center gap-2.5 text-white transition-colors">
                    <svg width="28" height="28" viewBox="0 0 64 64" className="w-7 h-7 rounded-lg shadow-sm">
                        <rect width="64" height="64" rx="14" fill="#7D39EB" />
                        <path d="M32 10L54 32L32 54L10 32L32 10Z" fill="none" stroke="#C6FF33" strokeWidth="6" strokeLinejoin="round" />
                        <path d="M32 22L42 32L32 42L22 32L32 22Z" fill="#C6FF33" />
                    </svg>
                    <span className="text-xl font-bold tracking-tight text-white font-rimma uppercase">edusfera</span>
                </a>

                {/* Desktop Nav */}
                <div className="hidden md:flex items-center gap-3">
                    <div className="rounded-full bg-white/[0.06] p-1 backdrop-blur-xl flex items-center gap-0.5 border border-white/10 shadow-sm">
                        {navItems.map((item) => (
                            <button
                                key={item.label}
                                onClick={() => handleNavClick(item.href)}
                                className={`rounded-full px-3.5 py-1.5 text-xs sm:text-sm font-medium transition-all flex items-center gap-1.5 cursor-pointer ${
                                    item.isSpecial 
                                        ? 'bg-[#C6FF33] text-black font-extrabold shadow-sm hover:bg-[#d4ff59]' 
                                        : item.href === '/generator'
                                            ? 'bg-white/15 text-white font-semibold shadow-xs'
                                            : 'text-neutral-300 hover:bg-white/10 hover:text-white'
                                }`}
                            >
                                <span>{item.label}</span>
                                {item.badge && (
                                    <span className="text-[10px] bg-[#C6FF33] text-black font-extrabold px-1.5 py-0.5 rounded-full leading-none tracking-tight">
                                        {item.badge}
                                    </span>
                                )}
                                {item.isSpecial && (
                                    <span className="text-[10px] bg-black text-[#C6FF33] px-1.5 py-0.5 rounded-full uppercase font-black leading-none">
                                        0 BYN
                                    </span>
                                )}
                            </button>
                        ))}
                    </div>

                    {user ? (
                        <div className="relative" ref={userMenuRef}>
                            <button
                                type="button"
                                onClick={() => setUserMenuOpen(!userMenuOpen)}
                                className="rounded-full bg-emerald-500/20 text-emerald-300 border border-emerald-500/40 px-4 py-2 text-sm font-semibold hover:bg-emerald-500/30 transition-all flex items-center gap-2.5 cursor-pointer backdrop-blur-md shadow-sm focus:outline-none"
                                aria-expanded={userMenuOpen}
                            >
                                <span className="relative flex h-2.5 w-2.5">
                                    <span className="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                    <span className="relative inline-flex rounded-full h-2.5 w-2.5 bg-emerald-500"></span>
                                </span>
                                <span className="truncate max-w-[120px]">{user.name || 'Кабинет'}</span>
                                <span className="text-[10px] px-2 py-0.5 rounded-full bg-white/10 text-emerald-200 font-bold border border-white/10">
                                    {user.role_label || (user.role === 'tutor' ? 'Репетитор' : user.role === 'admin' ? 'Администратор' : user.role === 'parent' ? 'Родитель' : 'Ученик')}
                                </span>
                                <ChevronDown className={`w-3.5 h-3.5 text-emerald-400 transition-transform ${userMenuOpen ? 'rotate-180' : ''}`} />
                            </button>

                            {/* Dropdown Menu */}
                            {userMenuOpen && (
                                <div className="absolute right-0 top-full mt-2 w-80 rounded-2xl bg-[#121214]/95 border border-white/10 p-2 text-white shadow-2xl backdrop-blur-xl z-50 animate-in fade-in slide-in-from-top-2 duration-150">
                                    {/* Current User Card */}
                                    <div className="flex items-center gap-3 p-2.5 rounded-xl bg-white/5 border border-white/5 mb-1.5">
                                        <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-emerald-500/20 border border-emerald-500/30 text-emerald-400 font-bold text-sm">
                                            {user.name ? user.name.charAt(0).toUpperCase() : 'U'}
                                        </div>
                                        <div className="min-w-0 flex-1">
                                            <p className="text-sm font-bold text-white truncate">{user.name}</p>
                                            <div className="flex items-center gap-1.5 mt-0.5">
                                                <span className="inline-block text-[10px] font-bold px-2 py-0.5 rounded-md bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">
                                                    {user.role_label || (user.role === 'tutor' ? 'Репетитор' : user.role === 'admin' ? 'Администратор' : user.role === 'parent' ? 'Родитель' : 'Ученик')}
                                                </span>
                                                <span className="text-xs text-neutral-400 truncate">{user.email}</span>
                                            </div>
                                        </div>
                                    </div>

                                    {/* Unified Dashboard Link */}
                                    <div className="space-y-0.5">
                                        <a
                                            href={cabinetUrl}
                                            className="flex items-center gap-2.5 px-3 py-2 text-sm font-semibold text-white rounded-xl bg-gradient-to-r from-[#7D39EB]/30 to-[#C6FF33]/20 border border-white/10 hover:from-[#7D39EB]/50 hover:to-[#C6FF33]/30 transition-all"
                                        >
                                            <span className="text-base">🚀</span>
                                            <span>Личный кабинет</span>
                                        </a>

                                        {/* Role-Specific Quick Actions */}
                                        {user.role === 'tutor' && (
                                            <>
                                                <a
                                                    href={financeUrl}
                                                    className="flex items-center gap-2.5 px-3 py-2 text-sm font-medium text-neutral-200 hover:text-white rounded-xl hover:bg-white/10 transition-colors"
                                                >
                                                    <Sparkles className="w-4 h-4 text-amber-400" />
                                                    <span>Управление тарифом</span>
                                                </a>
                                                <a
                                                    href={scheduleUrl}
                                                    className="flex items-center gap-2.5 px-3 py-2 text-sm font-medium text-neutral-200 hover:text-white rounded-xl hover:bg-white/10 transition-colors"
                                                >
                                                    <Calendar className="w-4 h-4 text-emerald-400" />
                                                    <span>Расписание</span>
                                                </a>
                                                <a
                                                    href={lessonsUrl}
                                                    className="flex items-center gap-2.5 px-3 py-2 text-sm font-medium text-neutral-200 hover:text-white rounded-xl hover:bg-white/10 transition-colors"
                                                >
                                                    <BookOpen className="w-4 h-4 text-blue-400" />
                                                    <span>Мои занятия</span>
                                                </a>
                                            </>
                                        )}

                                        {(user.role === 'student' || user.role === 'parent') && (
                                            <>
                                                <a
                                                    href={lessonsUrl}
                                                    className="flex items-center gap-2.5 px-3 py-2 text-sm font-medium text-neutral-200 hover:text-white rounded-xl hover:bg-white/10 transition-colors"
                                                >
                                                    <BookOpen className="w-4 h-4 text-blue-400" />
                                                    <span>Мои занятия</span>
                                                </a>
                                                <a
                                                    href={diagnosticUrl}
                                                    className="flex items-center gap-2.5 px-3 py-2 text-sm font-medium text-neutral-200 hover:text-white rounded-xl hover:bg-white/10 transition-colors"
                                                >
                                                    <GraduationCap className="w-4 h-4 text-emerald-400" />
                                                    <span>ИИ-Диагностика</span>
                                                </a>
                                                <a
                                                    href={homeworkUrl}
                                                    className="flex items-center gap-2.5 px-3 py-2 text-sm font-medium text-neutral-200 hover:text-white rounded-xl hover:bg-white/10 transition-colors"
                                                >
                                                    <FileCheck className="w-4 h-4 text-amber-400" />
                                                    <span>Домашние задания</span>
                                                </a>
                                                <a
                                                    href={walletUrl}
                                                    className="flex items-center gap-2.5 px-3 py-2 text-sm font-medium text-neutral-200 hover:text-white rounded-xl hover:bg-white/10 transition-colors"
                                                >
                                                    <DollarSign className="w-4 h-4 text-[#C6FF33]" />
                                                    <span>Учёт денег</span>
                                                </a>
                                            </>
                                        )}

                                        {user.role === 'admin' && (
                                            <>
                                                <a
                                                    href="/admin/users"
                                                    className="flex items-center gap-2.5 px-3 py-2 text-sm font-medium text-neutral-200 hover:text-white rounded-xl hover:bg-white/10 transition-colors"
                                                >
                                                    <Users className="w-4 h-4 text-blue-400" />
                                                    <span>Пользователи</span>
                                                </a>
                                                <a
                                                    href="/admin/saa-s-management"
                                                    className="flex items-center gap-2.5 px-3 py-2 text-sm font-medium text-neutral-200 hover:text-white rounded-xl hover:bg-white/10 transition-colors"
                                                >
                                                    <Sparkles className="w-4 h-4 text-violet-400" />
                                                    <span>Управление SaaS</span>
                                                </a>
                                            </>
                                        )}

                                        <a
                                            href={messagesUrl}
                                            className="flex items-center gap-2.5 px-3 py-2 text-sm font-medium text-neutral-200 hover:text-white rounded-xl hover:bg-white/10 transition-colors"
                                        >
                                            <MessageSquare className="w-4 h-4 text-violet-400" />
                                            <span>Сообщения</span>
                                        </a>
                                    </div>

                                    {/* Linked Accounts (Switching) */}
                                    {linkedAccounts && linkedAccounts.length > 0 && (
                                        <div className="mt-2 pt-2 border-t border-white/10">
                                            <p className="px-3 py-1 text-[11px] font-bold uppercase tracking-wider text-neutral-400">
                                                Переключить аккаунт
                                            </p>
                                            <div className="space-y-0.5 mt-1">
                                                {linkedAccounts.map((acc) => (
                                                    <button
                                                        type="button"
                                                        key={acc.id}
                                                        onClick={() => handleSwitchAccount(acc.id)}
                                                        className="w-full flex items-center justify-between px-3 py-2 text-sm font-medium text-neutral-200 hover:text-white rounded-xl hover:bg-white/10 transition-colors cursor-pointer text-left"
                                                    >
                                                        <div className="flex items-center gap-2.5 min-w-0">
                                                            <div className="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-white/10 text-xs font-bold text-neutral-300">
                                                                {acc.name ? acc.name.charAt(0).toUpperCase() : 'U'}
                                                            </div>
                                                            <span className="truncate text-xs font-semibold">{acc.name}</span>
                                                        </div>
                                                        <span className="text-[10px] px-2 py-0.5 rounded-md bg-white/10 text-neutral-400 font-medium shrink-0">
                                                            {acc.role === 'tutor' ? 'Репетитор' : acc.role === 'admin' ? 'Администратор' : acc.role === 'parent' ? 'Родитель' : 'Ученик'}
                                                        </span>
                                                    </button>
                                                ))}
                                            </div>
                                        </div>
                                    )}

                                    {/* Add Account & Logout */}
                                    <div className="mt-2 pt-2 border-t border-white/10 space-y-0.5">
                                        <a
                                            href="/account/add"
                                            className="flex items-center gap-2.5 px-3 py-2 text-sm font-medium text-neutral-200 hover:text-white rounded-xl hover:bg-white/10 transition-colors"
                                        >
                                            <UserPlus className="w-4 h-4 text-blue-400" />
                                            <span>+ Добавить аккаунт</span>
                                        </a>

                                        <button
                                            type="button"
                                            onClick={handleLogout}
                                            className="w-full flex items-center gap-2.5 px-3 py-2 text-sm font-medium text-red-400 hover:text-red-300 rounded-xl hover:bg-red-500/10 transition-colors cursor-pointer text-left"
                                        >
                                            <LogOut className="w-4 h-4 text-red-400" />
                                            <span>Выйти</span>
                                        </button>
                                    </div>
                                </div>
                            )}
                        </div>
                    ) : (
                        <div className="flex items-center gap-2">
                            <a
                                href="/login"
                                className="rounded-full px-4 py-2 text-sm font-medium text-white/80 hover:text-white hover:bg-white/10 transition-all"
                            >
                                Войти
                            </a>
                            <a
                                href="/register"
                                className="rounded-full bg-gradient-to-r from-violet-600 to-indigo-600 text-white font-bold px-4 py-2 text-sm hover:from-violet-500 hover:to-indigo-500 transition-all shadow-md"
                            >
                                Регистрация
                            </a>
                            <a
                                href="/diagnostic"
                                className="rounded-full bg-[#C6FF33] text-black font-extrabold px-4 py-2 text-sm hover:bg-[#d4ff59] transition-all shadow-md"
                            >
                                ИИ-Диагностика
                            </a>
                        </div>
                    )}
                </div>

                {/* Mobile Hamburger */}
                <button
                    onClick={() => setMobileMenuOpen(!mobileMenuOpen)}
                    aria-label="Главное меню"
                    className="md:hidden relative z-50 h-10 w-10 rounded-full bg-white/10 backdrop-blur-lg flex items-center justify-center text-white transition-colors focus:outline-none cursor-pointer border border-white/10"
                >
                    {mobileMenuOpen ? <X className="w-5 h-5" /> : <Menu className="w-5 h-5" />}
                </button>
            </nav>

            {/* Mobile Drawer */}
            {mobileMenuOpen && (
                <div className="fixed inset-0 z-50 bg-black/95 backdrop-blur-2xl flex flex-col px-6 pt-24 pb-8 space-y-4 md:hidden overflow-y-auto overscroll-contain safe-top safe-bottom" style={{ WebkitOverflowScrolling: 'touch' }}>
                    {navItems.map((item) => (
                        <button
                            key={item.label}
                            onClick={() => handleNavClick(item.href)}
                            className="text-left text-lg font-medium text-white/90 py-2.5 border-b border-white/10 active:text-[#C6FF33] transition-colors"
                        >
                            {item.label}
                        </button>
                    ))}

                    {user ? (
                        <div className="mt-4 pt-4 border-t border-white/10 space-y-2.5">
                            <div className="flex items-center gap-3 p-3 rounded-xl bg-white/5 border border-white/10">
                                <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-emerald-500/20 text-emerald-400 font-bold text-sm">
                                    {user.name ? user.name.charAt(0).toUpperCase() : 'U'}
                                </div>
                                <div className="min-w-0 flex-1">
                                    <p className="text-sm font-bold text-white truncate">{user.name}</p>
                                    <span className="inline-block text-[10px] font-bold px-2 py-0.5 rounded-md bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">
                                        {user.role_label || (user.role === 'tutor' ? 'Репетитор' : user.role === 'admin' ? 'Администратор' : user.role === 'parent' ? 'Родитель' : 'Ученик')}
                                    </span>
                                </div>
                            </div>

                            <a
                                href={cabinetUrl}
                                className="flex items-center justify-center gap-2.5 rounded-xl bg-gradient-to-r from-[#7D39EB]/40 to-[#C6FF33]/30 border border-white/20 px-4 py-3 text-sm font-semibold text-white"
                            >
                                <span>🚀 Личный кабинет</span>
                            </a>

                            {user.role === 'tutor' && (
                                <div className="space-y-1">
                                    <a
                                        href={financeUrl}
                                        className="flex items-center gap-2.5 rounded-xl bg-white/5 px-4 py-2.5 text-sm font-medium text-white"
                                    >
                                        <Sparkles className="w-4 h-4 text-amber-400" />
                                        <span>Управление тарифом</span>
                                    </a>
                                    <a
                                        href={scheduleUrl}
                                        className="flex items-center gap-2.5 rounded-xl bg-white/5 px-4 py-2.5 text-sm font-medium text-white"
                                    >
                                        <Calendar className="w-4 h-4 text-emerald-400" />
                                        <span>Расписание</span>
                                    </a>
                                    <a
                                        href={lessonsUrl}
                                        className="flex items-center gap-2.5 rounded-xl bg-white/5 px-4 py-2.5 text-sm font-medium text-white"
                                    >
                                        <BookOpen className="w-4 h-4 text-blue-400" />
                                        <span>Мои занятия</span>
                                    </a>
                                </div>
                            )}

                            {(user.role === 'student' || user.role === 'parent') && (
                                <div className="space-y-1">
                                    <a
                                        href={lessonsUrl}
                                        className="flex items-center gap-2.5 rounded-xl bg-white/5 px-4 py-2.5 text-sm font-medium text-white"
                                    >
                                        <BookOpen className="w-4 h-4 text-blue-400" />
                                        <span>Мои занятия</span>
                                    </a>
                                    <a
                                        href={diagnosticUrl}
                                        className="flex items-center gap-2.5 rounded-xl bg-white/5 px-4 py-2.5 text-sm font-medium text-white"
                                    >
                                        <GraduationCap className="w-4 h-4 text-emerald-400" />
                                        <span>ИИ-Диагностика</span>
                                    </a>
                                    <a
                                        href={homeworkUrl}
                                        className="flex items-center gap-2.5 rounded-xl bg-white/5 px-4 py-2.5 text-sm font-medium text-white"
                                    >
                                        <FileCheck className="w-4 h-4 text-amber-400" />
                                        <span>Домашние задания</span>
                                    </a>
                                </div>
                            )}

                            <a
                                href={messagesUrl}
                                className="flex items-center gap-2.5 rounded-xl bg-white/5 px-4 py-2.5 text-sm font-semibold text-white"
                            >
                                <MessageSquare className="w-4 h-4 text-violet-400" />
                                <span>Сообщения</span>
                            </a>

                            {linkedAccounts && linkedAccounts.length > 0 && (
                                <div className="pt-2 border-t border-white/10">
                                    <p className="text-xs font-semibold text-neutral-400 px-1 py-1">Переключить аккаунт</p>
                                    {linkedAccounts.map((acc) => (
                                        <button
                                            key={acc.id}
                                            onClick={() => handleSwitchAccount(acc.id)}
                                            className="w-full text-left flex items-center justify-between p-2.5 rounded-xl bg-white/5 text-sm font-medium text-white"
                                        >
                                            <span>{acc.name}</span>
                                            <span className="text-xs text-neutral-400">
                                                {acc.role === 'tutor' ? 'Репетитор' : acc.role === 'admin' ? 'Администратор' : acc.role === 'parent' ? 'Родитель' : 'Ученик'}
                                            </span>
                                        </button>
                                    ))}
                                </div>
                            )}

                            <button
                                onClick={handleLogout}
                                className="w-full flex items-center justify-center gap-2 rounded-xl bg-red-500/20 text-red-300 border border-red-500/30 px-4 py-2.5 text-sm font-semibold"
                            >
                                <LogOut className="w-4 h-4" />
                                <span>Выйти</span>
                            </button>
                        </div>
                    ) : (
                        <div className="mt-4 pt-4 border-t border-white/10 space-y-3">
                            <a
                                href="/login"
                                className="block text-center rounded-xl bg-white/10 px-4 py-3 text-base font-medium text-white"
                            >
                                Войти
                            </a>
                            <a
                                href="/register"
                                className="block text-center rounded-xl bg-gradient-to-r from-violet-600 to-indigo-600 px-4 py-3 text-base font-bold text-white shadow-md"
                            >
                                Регистрация
                            </a>
                            <a
                                href="/diagnostic"
                                className="block text-center rounded-xl bg-[#C6FF33] px-4 py-3 text-base font-extrabold text-black shadow-md"
                            >
                                ИИ-Диагностика
                            </a>
                        </div>
                    )}
                </div>
            )}

            {/* Payment Failed Amber Alert Banner */}
            {typeof window !== 'undefined' && window.EDUSFERA_PAYMENT_STATUS === 'failed' && (
                <div className="w-full max-w-[1240px] mx-auto px-4 sm:px-6 pt-4 sm:pt-6">
                    <div className="p-4 sm:p-5 rounded-2xl bg-amber-500/10 border border-amber-500/30 text-amber-200 text-xs sm:text-sm flex items-start gap-3 shadow-lg backdrop-blur-md">
                        <AlertCircle className="w-5 h-5 text-amber-400 shrink-0 mt-0.5" />
                        <div className="space-y-1">
                            <div className="font-bold text-amber-300">
                                Оплата не была завершена
                            </div>
                            <div className="text-amber-200/90 leading-relaxed font-normal">
                                Банк отклонил транзакцию или операция была отменена. Пожалуйста, проверьте баланс карты, лимиты на интернет-платежи или повторите попытку оформления заказа.
                            </div>
                        </div>
                    </div>
                </div>
            )}

            {/* ─── 2. HERO & MAIN CONFIGURATOR (ONE-SCREEN SAAS 5fr / 7fr) ─── */}
            <main className="flex-1 w-full max-w-[1240px] mx-auto px-4 sm:px-6 pt-2 pb-12 sm:pt-4 sm:pb-16">
                
                {/* ELEGANT HERO WITH DESKTOP 3D VISUAL */}
                <div className="grid grid-cols-1 lg:grid-cols-12 gap-6 lg:gap-8 items-center mb-6 sm:mb-8">
                    {/* Left: Text & Key Highlights (7-8 cols) */}
                    <div className="lg:col-span-7 xl:col-span-8 space-y-3.5">
                        <div className="inline-flex items-center gap-2.5 px-3 py-1 rounded-full bg-white/[0.05] border border-white/10 text-xs font-medium text-neutral-300 backdrop-blur-md shadow-xs">
                            <span className="relative flex h-2 w-2">
                                <span className="animate-ping absolute inline-flex h-full w-full rounded-full bg-[#7D39EB] opacity-75"></span>
                                <span className="relative inline-flex rounded-full h-2 w-2 bg-[#7D39EB]"></span>
                            </span>
                            <span className="tracking-wide">Стандарты ЦТ и ЦЭ 2026 • Экзаменационная программа Беларуси</span>
                        </div>

                        <h1 className="text-2xl sm:text-4xl lg:text-[40px] xl:text-[42px] font-extrabold tracking-tight text-white leading-[1.18]">
                            Контрольные работы на класс. <br className="hidden sm:inline" />
                            <span className="text-transparent bg-clip-text bg-gradient-to-r from-white via-white/95 to-white/60">
                                Без единого шанса списать.
                            </span>
                        </h1>

                        <p className="text-sm sm:text-base text-neutral-400 leading-relaxed font-normal max-w-2xl">
                            15 индивидуальных вариантов с разными числами в заданиях. Готовый к печати PDF-комплект на урок за 30 секунд — и сводная ведомость ответов для проверки стопки тетрадей за 2 минуты.
                        </p>

                        <div className="flex flex-wrap items-center gap-x-6 gap-y-2 pt-1 text-xs text-neutral-400">
                            <span className="flex items-center gap-1.5">
                                <CheckCircle2 className="w-3.5 h-3.5 text-[#C6FF33]" />
                                <span>Разные числа у каждого варианта</span>
                            </span>
                            <span className="flex items-center gap-1.5">
                                <CheckCircle2 className="w-3.5 h-3.5 text-[#C6FF33]" />
                                <span>ГДЗ и Photomath бессильны</span>
                            </span>
                            <span className="flex items-center gap-1.5">
                                <CheckCircle2 className="w-3.5 h-3.5 text-[#C6FF33]" />
                                <span>Печать на стандартном листе А4</span>
                            </span>
                        </div>
                    </div>

                    {/* Right: Floating 3D Graphic (PC/Desktop only, 4-5 cols) */}
                    <div className="hidden lg:flex lg:col-span-5 xl:col-span-4 justify-center xl:justify-end items-center pointer-events-none select-none">
                        <div className="relative w-full max-w-[280px] xl:max-w-[320px]">
                            {/* Subtle Ambient Backlight */}
                            <div 
                                className="absolute inset-0 bg-gradient-to-tr from-violet-600/30 via-[#C6FF33]/15 to-transparent blur-3xl rounded-full opacity-60"
                                aria-hidden="true"
                            />
                            <img
                                src="/images/generator-hero-graphic.png"
                                alt="Онлайн-урок и интерактивный класс Edusfera"
                                width="1199"
                                height="1312"
                                className="relative z-10 w-full h-auto object-contain drop-shadow-[0_12px_32px_rgba(125,57,235,0.25)]"
                                loading="eager"
                                fetchPriority="high"
                            />
                        </div>
                    </div>
                </div>

                {/* TWO-COLUMN ARCHITECTURE: 5 COLS CONFIGURATOR + 7 COLS REAL WHITE A4 SHEET */}
                <div className="grid grid-cols-1 lg:grid-cols-12 gap-7 items-start">
                    
                    {/* LEFT COLUMN: Controls Surface (5 cols, Aurora Glass) */}
                    <div className="lg:col-span-5 bg-[#121214]/95 backdrop-blur-md border border-white/10 rounded-3xl p-5 sm:p-6 shadow-[0_16px_36px_-4px_rgba(0,0,0,0.6)] space-y-5">
                        
                        {/* Step 1: Subject Selection (Lucide Monochromatic SVG Icons) */}
                        <div>
                            <label className="text-xs font-bold text-slate-400 uppercase tracking-wider block mb-2">
                                1. ВЫБЕРИТЕ ДИСЦИПЛИНУ
                            </label>
                            <div className="flex flex-wrap gap-2">
                                {SUBJECTS.map((sub) => {
                                    const isSelected = selectedSubject === sub.id;
                                    const IconComponent = sub.icon;
                                    return (
                                        <button
                                            key={sub.id}
                                            type="button"
                                            onClick={() => handleSubjectChange(sub.id)}
                                            className={`px-3 py-2 rounded-xl text-xs font-medium transition-all cursor-pointer flex items-center gap-2 ${
                                                isSelected 
                                                    ? 'bg-[#7D39EB] text-white font-bold shadow-[0_0_15px_rgba(125,57,235,0.4)]' 
                                                    : 'bg-white/[0.04] text-slate-300 hover:bg-white/[0.08] border border-white/5'
                                            }`}
                                        >
                                            <IconComponent className={`w-3.5 h-3.5 ${isSelected ? 'text-white' : 'text-slate-400'}`} />
                                            <span>{sub.name}</span>
                                        </button>
                                    );
                                })}
                            </div>
                        </div>

                        {/* Step 2: Topic Selection */}
                        <div className="pt-2 border-t border-white/5">
                            <div className="flex items-center justify-between mb-2">
                                <label className="text-xs font-bold text-slate-400 uppercase tracking-wider">
                                    2. ТЕМА ПРОВЕРОЧНОЙ РАБОТЫ
                                </label>
                                <span className="text-xs font-mono font-bold text-[#7D39EB]">
                                    {currentSubjectName} • Стандарт 2026
                                </span>
                            </div>
                            <input
                                type="text"
                                value={topic}
                                onChange={(e) => setTopic(e.target.value)}
                                placeholder="Введите тему проверочной работы..."
                                className="w-full bg-[#18181C] border border-white/10 focus:border-[#7D39EB] focus:ring-2 focus:ring-[#7D39EB]/20 rounded-xl px-4 py-2.5 text-sm text-white placeholder-slate-500 focus:outline-none transition-all font-medium"
                            />

                            {/* Clean Text Presets */}
                            {(() => {
                                const currentPresets = GRADE_PRESETS[selectedGrade]?.[selectedSubject] || PRESETS[selectedSubject];
                                if (!currentPresets) return null;
                                return (
                                    <div className="space-y-1 pt-2.5">
                                        <div className="text-[11px] font-semibold text-slate-400 uppercase tracking-wide flex items-center justify-between">
                                            <span>Темы для {selectedGrade === 'abiturient' ? 'абитуриентов' : `${selectedGrade} класса`}:</span>
                                            <span className="text-[10px] text-[#C6FF33] font-normal">клик для выбора</span>
                                        </div>
                                        <div className="flex flex-col gap-1">
                                            {currentPresets.map((preset, idx) => (
                                                <button
                                                    key={idx}
                                                    type="button"
                                                    onClick={() => setTopic(preset)}
                                                    className="w-full text-left text-xs py-1 px-2 rounded-lg text-slate-400 hover:text-white hover:bg-white/[0.05] transition-colors truncate block cursor-pointer font-normal"
                                                >
                                                    • {preset}
                                                </button>
                                            ))}
                                        </div>
                                    </div>
                                );
                            })()}
                        </div>

                        {/* Step 3: Tasks in Variant (Part A & Part B) */}
                        <div className="pt-2 border-t border-white/5 space-y-2">
                            <div className="flex items-center justify-between">
                                <label className="text-xs font-bold text-slate-400 uppercase tracking-wider">
                                    3. ЗАДАНИЙ В ОДНОМ ВАРИАНТЕ
                                </label>
                                <span className="text-[10px] font-mono font-bold text-[#C6FF33] bg-[#C6FF33]/10 px-2 py-0.5 rounded-full border border-[#C6FF33]/25">
                                    Итого: {tasksCountA + tasksCountB} зад. (A1{tasksCountA > 1 ? `..A${tasksCountA}` : ''}{tasksCountB > 0 ? `, Б1${tasksCountB > 1 ? `..Б${tasksCountB}` : ''}` : ''})
                                </span>
                            </div>

                            <div className="grid grid-cols-2 gap-2">
                                {/* Part A Selector */}
                                <div className="p-2.5 rounded-xl bg-[#141418] border border-white/10 space-y-1.5">
                                    <div className="flex items-center justify-between text-xs">
                                        <span className="font-semibold text-slate-300">
                                            Часть А <span className="text-[10px] text-slate-500 font-normal">(тест)</span>
                                        </span>
                                        <span className="font-bold text-[#C6FF33] font-mono text-[11px]">{tasksCountA} зад.</span>
                                    </div>
                                    <div className="grid grid-cols-5 gap-1 p-0.5 rounded-lg bg-[#0B0B0E]">
                                        {[1, 2, 3, 4, 5].map((cnt) => (
                                            <button
                                                key={cnt}
                                                type="button"
                                                onClick={() => setTasksCountA(cnt)}
                                                className={`py-1 rounded text-xs font-semibold transition-all text-center cursor-pointer ${
                                                    tasksCountA === cnt
                                                        ? 'bg-[#7D39EB] text-white font-bold shadow-xs'
                                                        : 'text-slate-400 hover:text-white'
                                                }`}
                                            >
                                                {cnt}
                                            </button>
                                        ))}
                                    </div>
                                </div>

                                {/* Part B Selector */}
                                <div className="p-2.5 rounded-xl bg-[#141418] border border-white/10 space-y-1.5">
                                    <div className="flex items-center justify-between text-xs">
                                        <span className="font-semibold text-slate-300">
                                            Часть Б <span className="text-[10px] text-slate-500 font-normal">(число)</span>
                                        </span>
                                        <span className="font-bold text-[#C6FF33] font-mono text-[11px]">{tasksCountB} зад.</span>
                                    </div>
                                    <div className="grid grid-cols-5 gap-1 p-0.5 rounded-lg bg-[#0B0B0E]">
                                        {[1, 2, 3, 4, 5].map((cnt) => (
                                            <button
                                                key={cnt}
                                                type="button"
                                                onClick={() => setTasksCountB(cnt)}
                                                className={`py-1 rounded text-xs font-semibold transition-all text-center cursor-pointer ${
                                                    tasksCountB === cnt
                                                        ? 'bg-[#7D39EB] text-white font-bold shadow-xs'
                                                        : 'text-slate-400 hover:text-white'
                                                }`}
                                            >
                                                {cnt}
                                            </button>
                                        ))}
                                    </div>
                                </div>
                            </div>
                        </div>

                        {/* Step 3.5: Advanced Pedagogical Settings */}
                        <div className="pt-2 border-t border-white/5 space-y-2">
                            <button
                                type="button"
                                onClick={() => setShowAdvancedSettings(!showAdvancedSettings)}
                                className={`w-full flex items-center justify-between p-3 rounded-2xl border transition-all text-left cursor-pointer group ${
                                    showAdvancedSettings
                                        ? 'bg-[#7D39EB]/15 border-[#7D39EB] shadow-[0_0_20px_rgba(125,57,235,0.2)]'
                                        : 'bg-white/[0.03] hover:bg-white/[0.06] border-white/10 hover:border-white/20'
                                }`}
                            >
                                <div className="flex items-center gap-2.5 min-w-0">
                                    <div className="w-8 h-8 rounded-xl bg-[#7D39EB]/20 border border-[#7D39EB]/40 flex items-center justify-center text-[#C6FF33] shrink-0">
                                        <SlidersHorizontal className="w-4 h-4" />
                                    </div>
                                    <div className="min-w-0">
                                        <div className="text-xs font-bold text-white flex items-center gap-2 flex-wrap">
                                            <span>Расширенные настройки</span>
                                            <span className="text-[10px] font-mono font-bold text-[#C6FF33] bg-[#C6FF33]/15 px-2 py-0.5 rounded-full border border-[#C6FF33]/30">
                                                {selectedGrade === 'abiturient' ? 'Абитуриент' : `${selectedGrade} класс`} • {WORK_TYPES.find(w => w.id === selectedWorkType)?.shortLabel || 'Самостоятельная'}
                                            </span>
                                        </div>
                                        <div className="text-[11px] text-slate-400 mt-0.5 truncate">
                                            Класс обучения, формат работы, сложность и шкала
                                        </div>
                                    </div>
                                </div>
                                <div className="text-slate-400 group-hover:text-white transition-transform shrink-0 ml-2">
                                    <ChevronDown className={`w-4 h-4 transition-transform duration-200 ${showAdvancedSettings ? 'rotate-180 text-[#C6FF33]' : ''}`} />
                                </div>
                            </button>

                            <AnimatePresence>
                                {showAdvancedSettings && (
                                    <motion.div
                                        initial={{ opacity: 0, height: 0 }}
                                        animate={{ opacity: 1, height: 'auto' }}
                                        exit={{ opacity: 0, height: 0 }}
                                        transition={{ duration: 0.2 }}
                                        className="overflow-hidden space-y-3 pt-1"
                                    >
                                        <div className="p-3.5 rounded-2xl bg-[#141418] border border-white/10 space-y-3.5">
                                            {/* Sub-step A: Grade Selector */}
                                            <div>
                                                <div className="flex items-center justify-between mb-1.5">
                                                    <label className="text-[11px] font-bold text-slate-300 uppercase tracking-wider">
                                                        Класс обучения:
                                                    </label>
                                                    <span className="text-[11px] text-slate-400">
                                                        {selectedGrade === 'abiturient' ? 'Подготовка в вуз' : parseInt(selectedGrade) >= 10 ? 'Старшая школа' : 'Базовая школа'}
                                                    </span>
                                                </div>
                                                <div className="grid grid-cols-4 sm:grid-cols-8 gap-1 p-1 rounded-xl bg-[#0B0B0E] border border-white/5">
                                                    {GRADES.map((g) => (
                                                        <button
                                                            key={g.id}
                                                            type="button"
                                                            onClick={() => handleGradeChange(g.id)}
                                                            className={`py-1.5 px-1 rounded-lg text-xs font-bold transition-all text-center cursor-pointer ${
                                                                selectedGrade === g.id
                                                                    ? 'bg-[#7D39EB] text-white shadow-xs'
                                                                    : 'text-slate-400 hover:text-white hover:bg-white/5'
                                                            }`}
                                                        >
                                                            {g.label.replace(' класс', ' кл.')}
                                                        </button>
                                                    ))}
                                                </div>
                                            </div>

                                            {/* Sub-step B: Work Type Selector */}
                                            <div>
                                                <div className="flex items-center justify-between mb-1.5">
                                                    <label className="text-[11px] font-bold text-slate-300 uppercase tracking-wider">
                                                        Тип учебной работы:
                                                    </label>
                                                    <span className="text-[11px] font-mono text-[#C6FF33]">
                                                        {WORK_TYPES.find(w => w.id === selectedWorkType)?.duration}
                                                    </span>
                                                </div>
                                                <div className="grid grid-cols-2 sm:grid-cols-3 gap-1.5">
                                                    {WORK_TYPES.map((wt) => {
                                                        const isForbidden = parseInt(selectedGrade) < 10 && wt.id === 'ct_ce';
                                                        const isSelected = selectedWorkType === wt.id;
                                                        return (
                                                            <button
                                                                key={wt.id}
                                                                type="button"
                                                                disabled={isForbidden}
                                                                onClick={() => handleWorkTypeChange(wt.id)}
                                                                className={`p-2.5 rounded-xl border text-left transition-all cursor-pointer relative ${
                                                                    isSelected
                                                                        ? 'bg-[#7D39EB]/20 border-[#7D39EB] text-white shadow-xs'
                                                                        : isForbidden
                                                                        ? 'bg-white/[0.01] border-white/5 text-slate-600 opacity-40 cursor-not-allowed'
                                                                        : 'bg-white/[0.03] border-white/10 text-slate-300 hover:border-white/25 hover:text-white'
                                                                }`}
                                                            >
                                                                <div className="flex items-center gap-1.5 mb-1">
                                                                    <wt.icon className={`w-3.5 h-3.5 ${isSelected ? 'text-[#C6FF33]' : 'text-slate-400'}`} />
                                                                    <span className="text-xs font-bold truncate">{wt.name}</span>
                                                                </div>
                                                                <div className="text-[10px] text-slate-400 leading-tight line-clamp-2">
                                                                    {wt.desc}
                                                                </div>
                                                                <div className="mt-1.5 flex items-center gap-1 text-[9px] font-mono text-slate-400">
                                                                    <Clock className="w-2.5 h-2.5" /> {wt.duration}
                                                                </div>
                                                                {isForbidden && (
                                                                    <span className="absolute top-1 right-1 text-[8px] bg-red-500/20 text-red-300 px-1 py-0.5 rounded font-mono">
                                                                        10–11 кл
                                                                    </span>
                                                                )}
                                                            </button>
                                                        );
                                                    })}
                                                </div>
                                            </div>

                                            {/* Sub-step C: Difficulty Level */}
                                            <div>
                                                <label className="text-[11px] font-bold text-slate-300 uppercase tracking-wider block mb-1.5">
                                                    Уровень сложности:
                                                </label>
                                                <div className="grid grid-cols-3 gap-1.5">
                                                    {DIFFICULTY_LEVELS.map((diff) => (
                                                        <button
                                                            key={diff.id}
                                                            type="button"
                                                            onClick={() => setSelectedDifficulty(diff.id)}
                                                            className={`p-2 rounded-xl border text-left transition-all cursor-pointer ${
                                                                selectedDifficulty === diff.id
                                                                    ? 'bg-white text-[#0B0B0E] font-bold shadow-xs'
                                                                    : 'bg-white/[0.03] border-white/10 text-slate-400 hover:text-white'
                                                            }`}
                                                        >
                                                            <div className="text-xs font-bold">{diff.label}</div>
                                                            <div className={`text-[10px] ${selectedDifficulty === diff.id ? 'text-slate-700' : 'text-slate-500'}`}>
                                                                {diff.desc}
                                                            </div>
                                                        </button>
                                                    ))}
                                                </div>
                                            </div>

                                            {/* Pedagogical Note Banner */}
                                            <div className="p-2.5 rounded-xl bg-[#7D39EB]/10 border border-[#7D39EB]/25 text-slate-300 text-xs flex items-start gap-2">
                                                <Sparkles className="w-4 h-4 text-[#C6FF33] shrink-0 mt-0.5" />
                                                <div className="text-[11px] leading-relaxed">
                                                    {parseInt(selectedGrade) < 10 ? (
                                                        <>
                                                            <span className="font-bold text-white">Педагогическая адаптация ({selectedGrade} класс):</span> Задания, дидактическая цель и формулировки соответствуют действующей учебной программе базовой школы РБ. Оценивание по государственной 10-балльной шкале.
                                                        </>
                                                    ) : (
                                                        <>
                                                            <span className="font-bold text-white">Экзаменационный стандарт (10–11 класс):</span> Полная спецификация стандартов ЦЭ/ЦТ 2026 с отрывным бланком ответов и переводной шкалой.
                                                        </>
                                                    )}
                                                </div>
                                            </div>
                                        </div>
                                    </motion.div>
                                )}
                            </AnimatePresence>
                        </div>

                        {/* Step 4: Variant Count (Segmented Control) */}
                        <div className="pt-2 border-t border-white/5">
                            <label className="text-xs font-bold text-slate-400 uppercase tracking-wider block mb-2">
                                4. КОЛИЧЕСТВО ВАРИАНТОВ НА КЛАСС
                            </label>
                            <div className="grid grid-cols-3 gap-1.5 p-1 rounded-xl bg-[#18181C] border border-white/10">
                                {VARIANT_COUNTS.map((item) => (
                                    <button
                                        key={item.count}
                                        type="button"
                                        onClick={() => setVariantCount(item.count)}
                                        className={`py-2 rounded-lg text-xs font-semibold transition-all text-center cursor-pointer relative ${
                                            variantCount === item.count 
                                                ? 'bg-white text-[#0B0B0E] font-bold shadow-xs' 
                                                : 'text-slate-400 hover:text-white'
                                        }`}
                                    >
                                        <span>{item.label}</span>
                                        {item.popular && (
                                            <span className={`ml-1 text-[9px] px-1 py-0.2 rounded font-bold ${variantCount === item.count ? 'bg-[#7D39EB] text-white' : 'text-slate-500'}`}>
                                                класс
                                            </span>
                                        )}
                                    </button>
                                ))}
                            </div>
                        </div>

                        {/* Step 5: Pricing Format (Single vs Quarter Pass) */}
                        <div className="pt-2 border-t border-white/5">
                            <label className="text-xs font-bold text-slate-400 uppercase tracking-wider block mb-2">
                                5. ФОРМАТ ПОКУПКИ
                            </label>
                            <div className="grid grid-cols-2 gap-2">
                                <button
                                    type="button"
                                    onClick={() => setIsQuarterPass(false)}
                                    className={`p-3 rounded-2xl border text-left transition-all cursor-pointer relative ${
                                        !isQuarterPass
                                            ? 'bg-white/[0.08] border-white/40 shadow-xs'
                                            : 'bg-white/[0.03] border-white/10 hover:border-white/20'
                                    }`}
                                >
                                    <div className="text-xs font-bold text-white">1 комплект</div>
                                    <div className="text-sm font-extrabold text-white mt-0.5">5.00 BYN</div>
                                    <div className="text-[10px] text-slate-400 mt-1">Разово на урок</div>
                                </button>

                                <button
                                    type="button"
                                    onClick={() => setIsQuarterPass(true)}
                                    className={`p-3 rounded-2xl border text-left transition-all cursor-pointer relative ${
                                        isQuarterPass
                                            ? 'bg-[#7D39EB]/20 border-[#7D39EB] shadow-[0_0_20px_rgba(125,57,235,0.25)]'
                                            : 'bg-white/[0.03] border-white/10 hover:border-white/20'
                                    }`}
                                >
                                    <span className="absolute -top-2 right-2 px-1.5 py-0.5 rounded-full bg-[#7D39EB] text-white text-[9px] font-bold">
                                        ВЫГОДА 62%
                                    </span>
                                    <div className="text-xs font-bold text-white">Абонемент</div>
                                    <div className="text-sm font-extrabold text-[#C6FF33] mt-0.5">19.00 BYN</div>
                                    <div className="text-[10px] text-slate-300 mt-1">10 любых тем (по 1.90)</div>
                                </button>
                            </div>
                        </div>

                        {/* Primary Action Button (Brand Purple #7D39EB with White Text) */}
                        <div className="pt-2 space-y-2">
                            <button
                                type="button"
                                onClick={() => setShowPayModal(true)}
                                className="w-full py-4 px-6 rounded-xl bg-[#7D39EB] hover:bg-[#6D28D9] text-white font-bold text-base tracking-tight active:scale-[0.99] transition-all flex items-center justify-center gap-2 cursor-pointer shadow-[0_0_25px_rgba(125,57,235,0.35)]"
                            >
                                <span>
                                    {isQuarterPass 
                                        ? 'Оформить абонемент на четверть — 19 BYN' 
                                        : `Сформировать комплект (${variantCount} вар.) — 5 BYN`}
                                </span>
                                <ArrowRight className="w-5 h-5 text-white" />
                            </button>

                            <div className="flex items-center justify-center gap-2 text-xs text-slate-400 font-mono text-center">
                                <span>Альфа-Банк</span>
                                <span>•</span>
                                <span>Разовый платёж без подписок</span>
                                <span>•</span>
                                <span>Чек НПД МНС РБ</span>
                            </div>
                        </div>

                    </div>

                    {/* RIGHT COLUMN: Realistic Authentic White A4 Sheet Preview (7 cols) */}
                    <div className="lg:col-span-7 bg-[#121214]/95 backdrop-blur-md border border-white/10 rounded-3xl p-5 sm:p-6 shadow-[0_16px_36px_-4px_rgba(0,0,0,0.6)] space-y-3.5">
                        
                        {/* Segmented preview tabs */}
                        <div className="flex items-center justify-between border-b border-white/10 pb-3 gap-2">
                            <div className="flex items-center gap-1.5 p-1 rounded-xl bg-[#18181C] border border-white/10 text-xs">
                                <button
                                    type="button"
                                    onClick={() => setPreviewTab('v1')}
                                    className={`px-3 py-1.5 rounded-lg font-bold transition-all cursor-pointer ${
                                        previewTab === 'v1' ? 'bg-white text-[#0B0B0E] shadow-xs' : 'text-slate-400 hover:text-white'
                                    }`}
                                >
                                    Вариант 1
                                </button>
                                <button
                                    type="button"
                                    onClick={() => setPreviewTab('v2')}
                                    className={`px-3 py-1.5 rounded-lg font-bold transition-all cursor-pointer ${
                                        previewTab === 'v2' ? 'bg-white text-[#0B0B0E] shadow-xs' : 'text-slate-400 hover:text-white'
                                    }`}
                                >
                                    Вариант 2
                                </button>
                                <button
                                    type="button"
                                    onClick={() => setPreviewTab('keys')}
                                    className={`px-3 py-1.5 rounded-lg font-bold transition-all cursor-pointer ${
                                        previewTab === 'keys' ? 'bg-[#7D39EB] text-white shadow-xs' : 'text-slate-400 hover:text-white'
                                    }`}
                                >
                                    ★ Ключи ответов (2 мин)
                                </button>
                            </div>

                            <button
                                type="button"
                                onClick={() => setShowZoomModal(true)}
                                className="inline-flex items-center gap-1.5 text-xs text-slate-400 hover:text-white px-2.5 py-1.5 rounded-lg bg-white/5 hover:bg-white/10 border border-white/10 transition-colors cursor-pointer shrink-0"
                                title="Рассмотреть реальный образец А4"
                            >
                                <Maximize2 className="w-3.5 h-3.5 text-[#C6FF33]" />
                                <span className="hidden sm:inline">Во весь экран</span>
                            </button>
                        </div>

                        {/* Physical Paper Sheet Mockup (AUTHENTIC CRISP WHITE A4 PAPER SHEET) */}
                        <div className="relative rounded-xl bg-white border border-slate-200 p-6 sm:p-7 text-slate-900 font-serif text-xs leading-relaxed shadow-[0_20px_50px_rgba(0,0,0,0.4)] min-h-[460px] flex flex-col justify-between select-none">
                            
                            {/* Faint watermark */}
                            <div className="absolute inset-0 pointer-events-none flex items-center justify-center -rotate-12 select-none opacity-[0.035]">
                                <span className="text-5xl font-black font-sans uppercase tracking-widest text-slate-900 text-center">
                                    ОБРАЗЕЦ EDUSFERA
                                </span>
                            </div>

                            {/* Header of authentic test */}
                            <div>
                                <div className="border-b-2 border-slate-900 pb-2 mb-2 flex items-start justify-between font-sans">
                                    <div>
                                        <div className="text-xs font-bold uppercase tracking-wider text-slate-900">
                                            {selectedWorkTypeObj.title} • {currentSubjectName}
                                        </div>
                                        <div className="text-[10px] text-slate-500 mt-0.5">
                                            Учебная программа Беларуси • {selectedGradeLabel} • {selectedDifficultyObj.title}
                                        </div>
                                    </div>
                                    <div className="text-right">
                                        <span className="inline-block px-2 py-0.5 rounded bg-slate-100 text-slate-800 font-mono text-xs font-bold">
                                            {previewTab === 'keys' ? 'Ключи для учителя' : `Вариант № ${previewTab === 'v2' ? '2' : '1'}`}
                                        </span>
                                        <div className="text-slate-500 text-[10px] mt-0.5 font-mono">Стр. 1 из {variantCount + 1}</div>
                                    </div>
                                </div>

                                {/* Pedagogical passport bar */}
                                <div className="flex items-center justify-between text-[10px] font-sans text-slate-600 bg-slate-50 px-2.5 py-1 rounded border border-slate-200 mb-2">
                                    <span>⏱ Время: <b>{selectedWorkTypeObj.duration}</b></span>
                                    <span>📊 Заданий: <b>{tasksCountA + tasksCountB}</b></span>
                                    <span>🎯 Шкала: <b>{selectedWorkType === 'ct_ce' ? '100-балльная (ЦТ)' : '10-балльная (школа)'}</b></span>
                                </div>

                                {/* Student fields for test sheets */}
                                {previewTab !== 'keys' && (
                                    <div className="grid grid-cols-2 gap-2 pb-2 mb-2 border-b border-dashed border-slate-300 text-xs font-sans text-slate-600">
                                        <div>Учащийся: ___________________________</div>
                                        <div className="text-right">Класс: <b>{selectedGrade === 'abiturient' ? 'Абит.' : `${selectedGrade}-____`}</b> &nbsp; Дата: ____________</div>
                                    </div>
                                )}

                                {/* Topic line */}
                                <div className="text-xs font-bold font-sans text-slate-900 mb-3 truncate" title={topic}>
                                    Тема: {topic}
                                </div>

                                {/* Tab 1: Variant 1 Content */}
                                {previewTab === 'v1' && (
                                    <div className="space-y-3 font-sans text-slate-900">
                                        <div className="p-3 rounded-lg bg-slate-50 border border-slate-200 space-y-1.5">
                                            <div className="text-xs font-semibold text-[#7D39EB] flex items-center justify-between">
                                                <span>Часть А. Задание А1 (с выбором ответа):</span>
                                                <span className="text-slate-500 font-normal">1 балл</span>
                                            </div>
                                            <div className="text-xs text-slate-900 leading-snug font-medium">
                                                {currentSample.v1.taskA1}
                                            </div>
                                            <div className="grid grid-cols-2 gap-1.5 text-xs text-slate-700 pt-1">
                                                {currentSample.v1.options.map((opt, i) => (
                                                    <span key={i} className={`p-1.5 rounded ${i === currentSample.v1.correctIndex ? 'bg-white text-slate-900 font-bold border border-slate-300 shadow-2xs' : 'bg-transparent'}`}>
                                                        {opt}
                                                    </span>
                                                ))}
                                            </div>
                                        </div>

                                        <div className="p-3 rounded-lg bg-slate-50 border border-slate-200 space-y-1.5">
                                            <div className="text-xs font-semibold text-[#7D39EB] flex items-center justify-between">
                                                <span>Часть Б. Задание Б1 (краткий числовой ответ):</span>
                                                <span className="text-slate-500 font-normal">2 балла</span>
                                            </div>
                                            <div className="text-xs text-slate-900 leading-snug font-medium">
                                                {currentSample.v1.taskB1}
                                            </div>
                                            <div className="text-xs text-slate-500 pt-1 font-mono">
                                                Ответ ученика: [ &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; ]
                                            </div>
                                        </div>
                                    </div>
                                )}

                                {/* Tab 2: Variant 2 Content */}
                                {previewTab === 'v2' && (
                                    <div className="space-y-3 font-sans text-slate-900">
                                        <div className="p-3 rounded-lg bg-slate-50 border border-slate-200 space-y-1.5">
                                            <div className="text-xs font-semibold text-slate-900 flex items-center justify-between">
                                                <span>Вариант 2 • Задание А1 (другие числа, нет в ГДЗ):</span>
                                                <span className="text-slate-500 font-normal">1 балл</span>
                                            </div>
                                            <div className="text-xs text-slate-900 leading-snug font-medium">
                                                {currentSample.v2.taskA1}
                                            </div>
                                            <div className="grid grid-cols-2 gap-1.5 text-xs text-slate-700 pt-1">
                                                {currentSample.v2.options.map((opt, i) => (
                                                    <span key={i} className={`p-1.5 rounded ${i === currentSample.v2.correctIndex ? 'bg-white text-slate-900 font-bold border border-slate-300 shadow-2xs' : 'bg-transparent'}`}>
                                                        {opt}
                                                    </span>
                                                ))}
                                            </div>
                                        </div>

                                        <div className="p-3 rounded-lg bg-slate-50 border border-slate-200 space-y-1.5">
                                            <div className="text-xs font-semibold text-slate-900 flex items-center justify-between">
                                                <span>Вариант 2 • Задание Б1:</span>
                                                <span className="text-slate-500 font-normal">2 балла</span>
                                            </div>
                                            <div className="text-xs text-slate-900 leading-snug font-medium">
                                                {currentSample.v2.taskB1}
                                            </div>
                                            <div className="text-xs text-slate-500 pt-1 font-mono">
                                                Ответ ученика: [ &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; ]
                                            </div>
                                        </div>
                                    </div>
                                )}

                                {/* Tab 3: Master Table of Answers */}
                                {previewTab === 'keys' && (
                                    <div className="space-y-2 font-sans">
                                        <div className="text-xs text-emerald-700 font-bold pb-1 flex items-center gap-1.5">
                                            <CheckCircle2 className="w-4 h-4 text-emerald-600" />
                                            <span>Сводная ведомость правильных ответов (проверка стопки за 2 мин):</span>
                                        </div>
                                        <div className="rounded-lg border border-slate-200 overflow-hidden text-xs">
                                            <div className="grid grid-cols-12 bg-slate-100 p-2 font-bold text-slate-900 border-b border-slate-200">
                                                <div className="col-span-2 text-center">Вар.</div>
                                                <div className="col-span-3 text-center">Ответ А1</div>
                                                <div className="col-span-3 text-center">Ответ Б1</div>
                                                <div className="col-span-4">Ловушка темы</div>
                                            </div>
                                            {currentSample.keys.map((k, i) => (
                                                <div key={i} className="grid grid-cols-12 p-2 border-b border-slate-100 text-slate-600 items-center last:border-none">
                                                    <div className="col-span-2 text-center font-bold text-slate-900">№ {k.varNum}</div>
                                                    <div className="col-span-3 text-center text-emerald-600 font-bold">{k.a1}</div>
                                                    <div className="col-span-3 text-center font-bold text-slate-900">{k.b1}</div>
                                                    <div className="col-span-4 text-[10px] text-slate-500 truncate" title={k.note}>{k.note}</div>
                                                </div>
                                            ))}
                                        </div>
                                        <p className="text-[10px] text-slate-500 italic pt-1">
                                            Сводная таблица охватывает все {variantCount} вариантов для быстрой проверки стопки тетрадей.
                                        </p>
                                    </div>
                                )}
                            </div>

                            {/* Sheet bottom bar */}
                            <div className="pt-2.5 border-t border-slate-200 flex items-center justify-between text-xs text-slate-500 font-sans mt-3">
                                <span>Электронный учебно-методический комплекс</span>
                                <span className="font-semibold text-slate-900">Готово к печати А4</span>
                            </div>

                        </div>

                    </div>

                </div>

            </main>

            {/* ─── HIGH-CONVERTING MODULAR SECTIONS (Deferred Rendering with content-visibility) ─── */}
            <div style={{ contentVisibility: 'auto', containIntrinsicSize: '1px 600px' }}>
                <PainSolutionSection />
            </div>

            <div style={{ contentVisibility: 'auto', containIntrinsicSize: '1px 600px' }}>
                <TimeSavedCalculator onOpenModal={() => setShowPayModal(true)} />
            </div>

            <div style={{ contentVisibility: 'auto', containIntrinsicSize: '1px 600px' }}>
                <BundleBreakdownSection onOpenModal={() => setShowPayModal(true)} />
            </div>

            <div style={{ contentVisibility: 'auto', containIntrinsicSize: '1px 600px' }}>
                <PricingTiersSection onSelectPlan={(quarterPass) => {
                    setIsQuarterPass(quarterPass);
                    setShowPayModal(true);
                }} />
            </div>

            <div style={{ contentVisibility: 'auto', containIntrinsicSize: '1px 500px' }}>
                <GuaranteeSection />
            </div>

            <div style={{ contentVisibility: 'auto', containIntrinsicSize: '1px 500px' }}>
                <TestimonialsSection />
            </div>

            <div style={{ contentVisibility: 'auto', containIntrinsicSize: '1px 500px' }}>
                <FaqSection />
            </div>

            {/* ─── 3. REFINED CHECKOUT MODAL (AURORA GLASS) ─── */}
            <AnimatePresence>
                {showPayModal && (
                    <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
                        <motion.div
                            initial={{ opacity: 0 }}
                            animate={{ opacity: 1 }}
                            exit={{ opacity: 0 }}
                            onClick={() => !isSubmitting && setShowPayModal(false)}
                            className="absolute inset-0 bg-[#0B0B0E]/70 backdrop-blur-md"
                        />

                        <motion.div
                            initial={{ scale: 0.98, opacity: 0, y: 8 }}
                            animate={{ scale: 1, opacity: 1, y: 0 }}
                            exit={{ scale: 0.98, opacity: 0, y: 8 }}
                            className="relative w-full max-w-sm bg-[#121214] border border-white/15 rounded-3xl p-6 shadow-2xl z-10 space-y-4 font-sans text-white"
                        >
                            <div className="flex items-center justify-between">
                                <div>
                                    <h3 className="text-base font-bold text-white tracking-tight">
                                        Комплект проверочной работы
                                    </h3>
                                    <p className="text-xs text-slate-400">
                                        Официальный эквайринг Альфа-Банк
                                    </p>
                                </div>
                                <button
                                    onClick={() => !isSubmitting && setShowPayModal(false)}
                                    disabled={isSubmitting}
                                    className="text-slate-400 hover:text-white text-sm font-mono cursor-pointer disabled:opacity-30"
                                >
                                    ✕
                                </button>
                            </div>

                            {/* Plan Summary */}
                            <div className="p-3.5 rounded-2xl bg-white/[0.04] border border-white/10 flex items-center justify-between">
                                <div>
                                    <div className="text-xs font-bold text-white">
                                        {isQuarterPass ? 'Абонемент на четверть (10 тем)' : `${currentSubjectName}: ${variantCount} вар.`}
                                    </div>
                                    <div className="text-[11px] text-slate-400 mt-0.5 truncate max-w-[220px]" title={topic}>
                                        {topic} • {tasksCountA + tasksCountB} зад. (A:{tasksCountA}, Б:{tasksCountB})
                                    </div>
                                </div>
                                <div className="text-base font-mono font-bold text-[#C6FF33]">
                                    {isQuarterPass ? '19.00 BYN' : '5.00 BYN'}
                                </div>
                            </div>

                            {/* Checkout Form */}
                            <form onSubmit={handleInitiateCheckout} className="space-y-4">
                                <div className="space-y-1.5">
                                    <label className="text-xs font-semibold text-slate-300 block">
                                        Куда отправить готовый PDF:
                                    </label>
                                    <input
                                        type="email"
                                        value={email}
                                        onChange={(e) => {
                                             setEmail(e.target.value);
                                             if (errorMessage) setErrorMessage('');
                                        }}
                                        placeholder="teacher@school.by"
                                        required
                                        disabled={isSubmitting}
                                        className="w-full bg-[#18181C] border border-white/10 focus:border-[#7D39EB] focus:ring-2 focus:ring-[#7D39EB]/20 rounded-xl px-3.5 py-2.5 text-xs text-white placeholder-slate-500 focus:outline-none transition-all font-medium"
                                    />

                                    {/* Domain quick chips */}
                                    <div className="flex items-center gap-1.5 pt-1">
                                        <span className="text-[10px] text-slate-400">Быстро:</span>
                                        {EMAIL_DOMAINS.map((dom) => (
                                            <button
                                                key={dom}
                                                type="button"
                                                onClick={() => appendEmailDomain(dom)}
                                                className="text-[10px] font-mono px-2 py-0.5 rounded-md bg-white/[0.06] hover:bg-white/[0.12] text-slate-300 transition-colors cursor-pointer"
                                            >
                                                {dom}
                                            </button>
                                        ))}
                                    </div>

                                    {errorMessage && (
                                        <div className="text-xs text-rose-400 flex items-center gap-1.5 pt-1">
                                            <AlertCircle className="w-3.5 h-3.5 shrink-0" />
                                            <span>{errorMessage}</span>
                                        </div>
                                    )}
                                </div>

                                {/* Order Bump / Pass Toggle */}
                                <div 
                                    onClick={() => !isSubmitting && setIsQuarterPass(!isQuarterPass)}
                                    className={`p-3 rounded-2xl border transition-all cursor-pointer flex items-start gap-2.5 select-none ${
                                        isQuarterPass 
                                            ? 'bg-[#7D39EB]/20 border-[#7D39EB]' 
                                            : 'bg-white/[0.03] border-white/10 hover:border-white/20'
                                    }`}
                                >
                                    <div className={`mt-0.5 w-4 h-4 rounded border flex items-center justify-center shrink-0 transition-colors ${
                                        isQuarterPass ? 'bg-[#7D39EB] border-[#7D39EB] text-white' : 'border-white/30 bg-white/5'
                                    }`}>
                                        {isQuarterPass && <Check className="w-3 h-3 stroke-[3]" />}
                                    </div>
                                    
                                    <div className="space-y-0.5">
                                        <div className="flex items-center gap-1.5">
                                            <span className="text-xs font-bold text-white">Абонемент на четверть</span>
                                            <span className="px-1.5 py-0.2 rounded bg-[#7D39EB] text-white text-[9px] font-bold">
                                                ВЫГОДНО
                                            </span>
                                        </div>
                                        <p className="text-[11px] text-slate-400 leading-tight">
                                            10 любых тем за <strong>19 BYN</strong> (по 1.90 BYN за тему вместо 5 BYN). Разовый платёж без автосписаний.
                                        </p>
                                    </div>
                                </div>

                                {/* 100% Risk Reversal Guarantee */}
                                <div className="p-2.5 rounded-xl bg-white/[0.03] border border-white/10 text-[11px] text-slate-300 flex items-center gap-2">
                                    <ShieldCheck className="w-4 h-4 text-[#C6FF33] shrink-0" />
                                    <span><strong>Методическая гарантия:</strong> если найдете неточность в задании — бесплатно заменим тему или вернем деньги.</span>
                                </div>

                                {/* Admin Mode Notification */}
                                {Boolean(user?.is_admin || user?.role === 'admin') && (
                                    <div className="p-3 rounded-xl bg-[#7D39EB]/15 border border-[#7D39EB]/40 text-xs text-white space-y-1">
                                        <div className="flex items-center gap-1.5 font-bold text-[#C6FF33]">
                                            <Sparkles className="w-4 h-4 text-[#C6FF33] shrink-0" />
                                            <span>Режим администратора активен</span>
                                        </div>
                                        <p className="text-[11px] text-slate-300 leading-tight">
                                            Тестовая генерация без списания средств в Альфа-Банке. ИИ составит варианты и сразу откроет готовый PDF.
                                        </p>
                                    </div>
                                )}

                                {/* Submit button */}
                                <div className="space-y-2 pt-1">
                                    <button
                                        type="submit"
                                        disabled={isSubmitting}
                                        className="w-full py-3.5 px-4 rounded-xl bg-[#7D39EB] hover:bg-[#6D28D9] text-white font-bold text-xs shadow-[0_0_20px_rgba(125,57,235,0.35)] active:scale-[0.99] transition-all flex items-center justify-center gap-2 cursor-pointer disabled:opacity-60 disabled:cursor-not-allowed"
                                    >
                                        {isSubmitting ? (
                                            <>
                                                <Loader2 className="w-4 h-4 animate-spin text-white" />
                                                <span>{Boolean(user?.is_admin || user?.role === 'admin') ? 'Составление вариантов и верстка PDF (~10-15 сек)...' : 'Подготовка платежа...'}</span>
                                            </>
                                        ) : (
                                            <>
                                                {Boolean(user?.is_admin || user?.role === 'admin') ? (
                                                    <>
                                                        <Sparkles className="w-4 h-4 text-[#C6FF33]" />
                                                        <span>Сгенерировать тест (Администратор — 0 BYN)</span>
                                                    </>
                                                ) : (
                                                    <>
                                                        <Lock className="w-3.5 h-3.5 text-white" />
                                                        <span>
                                                            {isQuarterPass ? 'Оплатить абонемент 19.00 BYN' : 'Оплатить картой 5.00 BYN'}
                                                        </span>
                                                    </>
                                                )}
                                            </>
                                        )}
                                    </button>

                                    <div className="text-[10px] text-slate-400 text-center flex items-center justify-center gap-2 font-mono">
                                        <span>Альфа-Банк</span>
                                        <span>•</span>
                                        <span>Без автосписаний</span>
                                        <span>•</span>
                                        <span>Чек НПД МНС РБ</span>
                                    </div>
                                </div>
                            </form>
                        </motion.div>
                    </div>
                )}
            </AnimatePresence>

            {/* ─── 3.1 FULLSCREEN A4 ZOOM PREVIEW MODAL ─── */}
            <AnimatePresence>
                {showZoomModal && (
                    <div className="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-6 overflow-y-auto">
                        <motion.div
                            initial={{ opacity: 0 }}
                            animate={{ opacity: 1 }}
                            exit={{ opacity: 0 }}
                            onClick={() => setShowZoomModal(false)}
                            className="fixed inset-0 bg-black/80 backdrop-blur-md"
                        />

                        <motion.div
                            initial={{ scale: 0.95, opacity: 0 }}
                            animate={{ scale: 1, opacity: 1 }}
                            exit={{ scale: 0.95, opacity: 0 }}
                            className="relative w-full max-w-2xl bg-white text-slate-900 rounded-3xl p-6 sm:p-10 shadow-2xl z-10 space-y-6 max-h-[90vh] overflow-y-auto font-sans"
                        >
                            <div className="flex items-center justify-between border-b pb-4">
                                <div>
                                    <span className="text-[10px] uppercase font-bold tracking-wider text-[#7D39EB]">
                                        Точный образец печати (формат А4)
                                    </span>
                                    <h3 className="text-lg font-bold text-slate-900 mt-0.5">
                                        {selectedWorkTypeObj.title} • {currentSubjectName}
                                    </h3>
                                    <p className="text-xs text-slate-500">
                                        Учебная программа Беларуси • {selectedGradeLabel} • {selectedDifficultyObj.title} • {selectedWorkTypeObj.duration}
                                    </p>
                                </div>
                                <button
                                    onClick={() => setShowZoomModal(false)}
                                    className="w-8 h-8 rounded-full bg-slate-100 hover:bg-slate-200 flex items-center justify-center text-slate-600 font-bold text-sm cursor-pointer"
                                >
                                    ✕
                                </button>
                            </div>

                            {/* Full A4 Sample Content */}
                            <div className="p-4 sm:p-6 rounded-2xl bg-slate-50 border border-slate-200 space-y-4 text-xs font-serif leading-relaxed">
                                <div className="border-b-2 border-slate-900 pb-2 flex items-center justify-between font-sans">
                                    <span className="font-bold text-sm text-slate-900">ВАРИАНТ № 1</span>
                                    <span className="font-mono text-slate-600 text-xs">Anti-Cheat Seed: #9482-BY</span>
                                </div>
                                
                                <div className="grid grid-cols-2 gap-2 text-[11px] font-sans text-slate-600 border-b pb-2 border-dashed">
                                    <span>Учащийся: _______________________</span>
                                    <span className="text-right">Класс: <b>{selectedGrade === 'abiturient' ? 'Абит.' : `${selectedGrade}-____`}</b> &nbsp; Дата: _________</span>
                                </div>

                                <div className="font-sans font-bold text-xs text-slate-900">
                                    Тема: {topic}
                                </div>

                                <div className="p-3 bg-white rounded-xl border border-slate-200 space-y-2 font-sans">
                                    <div className="font-bold text-[#7D39EB] text-xs">
                                        Задание А1 (с выбором ответа • 1 балл):
                                    </div>
                                    <p className="text-slate-800 font-medium">
                                        {currentSample.v1.taskA1}
                                    </p>
                                    <div className="grid grid-cols-2 gap-1.5 pt-1 text-slate-700">
                                        {currentSample.v1.options.map((opt, i) => (
                                            <span key={i} className={`p-1.5 rounded ${i === currentSample.v1.correctIndex ? 'bg-emerald-50 text-emerald-900 font-bold border border-emerald-300' : 'bg-slate-50'}`}>
                                                {opt}
                                            </span>
                                        ))}
                                    </div>
                                </div>

                                <div className="p-3 bg-white rounded-xl border border-slate-200 space-y-2 font-sans">
                                    <div className="font-bold text-[#7D39EB] text-xs">
                                        Задание Б1 (краткий числовой ответ • 2 балла):
                                    </div>
                                    <p className="text-slate-800 font-medium">
                                        {currentSample.v1.taskB1}
                                    </p>
                                    <div className="font-mono text-slate-500 pt-1">
                                        Ответ учащегося: [ &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; ]
                                    </div>
                                </div>

                                <div className="p-3 bg-amber-50 rounded-xl border border-amber-200 text-amber-900 font-sans space-y-1">
                                    <div className="font-bold text-xs flex items-center gap-1.5">
                                        <CheckCircle2 className="w-4 h-4 text-emerald-600" />
                                        <span>Сводная таблица ответов для учителя (в комплекте):</span>
                                    </div>
                                    <p className="text-[11px] text-amber-800 leading-snug">
                                        Правильные ответы ко всем 15 вариантам сведены в одну страницу. Проверка стопки тетрадей занимает не более 2 минут.
                                    </p>
                                </div>
                            </div>

                            <div className="flex flex-col sm:flex-row items-center justify-between gap-3 pt-2">
                                <span className="text-xs text-slate-500">
                                    Готовый PDF генерируется за 30 секунд
                                </span>
                                <button
                                    onClick={() => {
                                        setShowZoomModal(false);
                                        setShowPayModal(true);
                                    }}
                                    className="w-full sm:w-auto px-6 py-3 rounded-xl bg-[#7D39EB] hover:bg-[#6D28D9] text-white font-bold text-xs shadow-md transition-all cursor-pointer"
                                >
                                    Сформировать комплект (5.00 BYN) →
                                </button>
                            </div>
                        </motion.div>
                    </div>
                )}
            </AnimatePresence>

            {/* ─── 4. OFFICIAL PLATFORM FOOTER ─── */}
            <Footer />

        </div>
    );
}
