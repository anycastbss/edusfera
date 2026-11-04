import React, { useState, useEffect, useRef } from 'react';
import { motion, AnimatePresence, useMotionValue, useTransform, useSpring } from 'motion/react';
import { 
    Sparkles, 
    MessageSquare, 
    Send, 
    X, 
    Copy, 
    Check, 
    Trash2, 
    ChevronDown, 
    ChevronUp, 
    SlidersHorizontal, 
    BookOpen, 
    Target, 
    Lightbulb, 
    CheckSquare, 
    Zap, 
    Clock, 
    ShieldCheck, 
    CornerDownLeft,
    GraduationCap,
    Bot,
    ArrowRight,
    Maximize2,
    Minimize2,
    PlusCircle,
    Download,
    CheckCircle2,
    Users,
    User,
    FileText
} from 'lucide-react';
import { formatAiMarkdown } from '../TutorAiAssistant/AiFormattedOutput';

const ALL_SUBJECTS = [
    '🌟 Общий диалог (Edusfera AI)',
    'Математика',
    'Физика',
    'Русский язык',
    'Белорусский язык',
    'Химия',
    'Биология',
    'Английский язык',
    'История Беларуси',
    'Обществоведение',
    'Информатика'
];

const PERSONAS = [
    { id: 'methodologist', label: '🎓 ИИ-Методист', desc: 'Техкарты занятий, дидактика и педагогические сценарии' },
    { id: 'rikz_expert', label: '🎯 Эксперт экзаменов', desc: 'Спецификации, критерии и разбор ловушек' },
    { id: 'express_solver', label: '⚡ Экспресс-решатель', desc: 'Строгое пошаговое аналитическое решение задач' },
    { id: 'simple_analogy', label: '💡 Наглядные аналогии', desc: 'Объяснение сложных концепций «на пальцах»' },
    { id: 'homework_gen', label: '📋 Генератор ДЗ', desc: 'Дифференцированные задания с ключами' },
    { id: 'free_chat', label: '💬 Свободный диалог', desc: 'Открытый брейншторм и педагогические идеи' },
];

export default function FloatingAiCopilot({ user = {}, csrfToken = '', endpoint = '/admin/ai-copilot/chat' }) {
    const isTutor = user?.isTutor ?? true;
    const initialSubject = user?.subject || (user?.subjects && user?.subjects[0]) || 'Математика';
    const [selectedSubject, setSelectedSubject] = useState(initialSubject);
    const [showSubjectMenu, setShowSubjectMenu] = useState(false);
    const [showStudentMenu, setShowStudentMenu] = useState(false);

    const [isOpen, setIsOpen] = useState(false);
    const [viewMode, setViewMode] = useState('standard'); // 'standard' | 'expanded' | 'docked'
    const [messages, setMessages] = useState([]);
    const [inputPrompt, setInputPrompt] = useState('');
    const [isLoading, setIsLoading] = useState(false);
    const [copiedIdx, setCopiedIdx] = useState(null);

    // Tutor's students for 1-click homework creation
    const [students, setStudents] = useState([]);
    const [selectedStudentId, setSelectedStudentId] = useState(null);
    const [toast, setToast] = useState(null);

    // Persona settings
    const [persona, setPersona] = useState('methodologist');
    const [showSettings, setShowSettings] = useState(false);

    // Visual feedback for prompt insertion
    const [insertNotice, setInsertNotice] = useState(null);
    const [isInputPulsing, setIsInputPulsing] = useState(false);

    const textareaRef = useRef(null);
    const messagesEndRef = useRef(null);
    const panelRef = useRef(null);
    const fabRef = useRef(null);
    const subjectMenuRef = useRef(null);
    const studentMenuRef = useRef(null);

    // Click outside to close menus
    useEffect(() => {
        const handleClickOutside = (e) => {
            if (subjectMenuRef.current && !subjectMenuRef.current.contains(e.target)) {
                setShowSubjectMenu(false);
            }
            if (studentMenuRef.current && !studentMenuRef.current.contains(e.target)) {
                setShowStudentMenu(false);
            }
        };
        document.addEventListener('mousedown', handleClickOutside);
        return () => document.removeEventListener('mousedown', handleClickOutside);
    }, []);

    // 3D Tilt state for Floating Action Button
    const mouseX = useMotionValue(0);
    const mouseY = useMotionValue(0);
    const rotateX = useSpring(useTransform(mouseY, [-35, 35], [18, -18]), { stiffness: 350, damping: 25 });
    const rotateY = useSpring(useTransform(mouseX, [-35, 35], [-18, 18]), { stiffness: 350, damping: 25 });

    const storageKey = `edusfera_copilot_msgs_${user?.id || 'guest'}_${selectedSubject}`;

    const showToast = (type, text) => {
        setToast({ type, text });
        if (type !== 'loading') {
            setTimeout(() => setToast(null), 3500);
        }
    };

    // Load active students for tutors
    useEffect(() => {
        if (isOpen && isTutor && students.length === 0) {
            fetch('/admin/ai-copilot/students')
                .then((res) => res.json())
                .then((data) => {
                    if (data.students && data.students.length > 0) {
                        setStudents(data.students);
                        // Do NOT force-select the first student: allow general free dialog mode
                    }
                })
                .catch(() => {});
        }
    }, [isOpen, isTutor]);

    // Restore chat history from localStorage
    useEffect(() => {
        try {
            const saved = localStorage.getItem(storageKey);
            if (saved) {
                setMessages(JSON.parse(saved));
            } else {
                setMessages([]);
            }
        } catch (e) {}
    }, [selectedSubject]);

    const saveMessages = (msgs) => {
        setMessages(msgs);
        try {
            localStorage.setItem(storageKey, JSON.stringify(msgs.slice(-30)));
        } catch (e) {}
    };

    // Scroll to bottom of chat
    const scrollToBottom = (smooth = true) => {
        messagesEndRef.current?.scrollIntoView({ behavior: smooth ? 'smooth' : 'auto' });
    };

    useEffect(() => {
        if (isOpen) {
            scrollToBottom(false);
        }
    }, [isOpen, messages, isLoading]);

    // Handle mouse movement for 3D FAB tilt
    const handleFabMouseMove = (e) => {
        if (!fabRef.current) return;
        const rect = fabRef.current.getBoundingClientRect();
        const centerX = rect.left + rect.width / 2;
        const centerY = rect.top + rect.height / 2;
        mouseX.set(e.clientX - centerX);
        mouseY.set(e.clientY - centerY);
    };

    const handleFabMouseLeave = () => {
        mouseX.set(0);
        mouseY.set(0);
    };

    // Toggle drawer
    const toggleDrawer = () => {
        setIsOpen((prev) => !prev);
        if (!isOpen) {
            setTimeout(() => {
                textareaRef.current?.focus();
            }, 250);
        }
    };

    // Clear conversation
    const handleClearHistory = () => {
        saveMessages([]);
        showToast('info', 'История текущего диалога очищена');
    };

    // Start fresh thread
    const handleNewChat = () => {
        saveMessages([]);
        setInsertNotice(null);
        setInputPrompt('');
        showToast('info', 'Начат новый диалог');
    };

    // Subject-tailored presets with rich context and example topics
    const SUBJECT_PRESETS = {
        'Математика': [
            { id: 'm_plan', icon: '📚', title: 'План урока ЦТ/ЦЭ', subtitle: 'Техкарта 60 мин с таймингом', template: 'Составь подробный 60-минутный технологический план урока подготовки к ЦТ по математике на тему: ', example: 'Производная функции и её применение' },
            { id: 'm_traps', icon: '🎯', title: 'Ловушки экзамена (Часть Б)', subtitle: 'Капканы с ОДЗ и вычислениями', template: 'Придумай 3 тестовые задачи формата ЦТ 2026 части Б по математике с типичными ловушками составителей тестов, разбором капкана и ключом решения на тему: ', example: 'Тригонометрические уравнения с отбором корней' },
            { id: 'm_analogy', icon: '💡', title: 'Объяснение на пальцах', subtitle: 'Интуитивная метафора', template: 'Как наглядно и понятно объяснить ученику без сложной теории на простых жизненных примерах тему по математике: ', example: 'Геометрический смысл производной' },
            { id: 'm_hw', icon: '📋', title: 'Дифференцированное ДЗ', subtitle: '3 уровня: База / ЦТ / Олимпиада', template: 'Составь разноуровневое домашнее задание (Базовый уровень, Уровень ЦТ/ЦЭ 2026, Олимпиадная задача) с критериями быстрой проверки по математике на тему: ', example: 'Логарифмические неравенства' },
            { id: 'm_express', icon: '⚡', title: 'Экспресс-алгоритм', subtitle: 'Пошаговый чек-лист решения', template: 'Сформируй пошаговый алгоритм-памятку для быстрого решения типовых заданий ЦТ по математике на тему: ', example: 'Площади фигур через определенный интеграл' },
            { id: 'm_mnemonic', icon: '🧠', title: 'Мнемоника формул', subtitle: 'Ассоциации и правила', template: 'Придумай яркие мнемонические правила и ассоциации для надежного запоминания формул по математике на тему: ', example: 'Формулы приведения тригонометрических функций' }
        ],
        'Физика': [
            { id: 'p_plan', icon: '📚', title: 'План урока и опыт', subtitle: 'Качественные задачи и опыты', template: 'Составь подробный 60-минутный план урока по физике для подготовки к ЦТ/ЦЭ с качественными задачами и демонстрациями на тему: ', example: 'Закон сохранения импульса и реактивное движение' },
            { id: 'p_traps', icon: '🎯', title: 'Ловушки ЦТ по физике', subtitle: 'Знаки проекций и единицы СИ', template: 'Назови 3 коварные задачи ЦТ по физике с типичными ловушками на знаки проекций, единицы СИ и скрытые условия на тему: ', example: 'Законы Ньютона и движение связанных тел' },
            { id: 'p_analogy', icon: '💡', title: 'Физика на пальцах', subtitle: 'Бытовые аналогии', template: 'Как наглядно объяснить физический смысл законов простыми бытовыми аналогиями на тему: ', example: 'Электромагнитная индукция и правило Ленца' },
            { id: 'p_graphs', icon: '📈', title: 'Задачи с графиками', subtitle: 'Анализ зависимостей процессов', template: 'Составь тренировочный блок задач ЦТ на чтение и анализ графиков с подробным разбором на тему: ', example: 'Изопроцессы в идеальном газе' },
            { id: 'p_hw', icon: '📋', title: 'Задачи ЦТ (Часть А и Б)', subtitle: 'С ключами проверки', template: 'Составь блок авторских расчетных задач формата ЦТ 2026 части А и Б с решениями по физике на тему: ', example: 'Закон Джоуля-Ленца и смешанное соединение' },
            { id: 'p_mnemonic', icon: '🧠', title: 'Мнемоника правил', subtitle: 'Правило буравчика и левой руки', template: 'Придумай наглядные мнемонические подсказки для правил и формул по физике на тему: ', example: 'Сила Лоренца и сила Ампера' }
        ],
        'Русский язык': [
            { id: 'r_plan', icon: '📚', title: 'Техкарта урока ЦТ/ЦЭ', subtitle: 'Орфографический практикум', template: 'Составь технологическую карту 60-минутного занятия по русскому языку к ЦТ/ЦЭ с алгоритмом рассуждения на тему: ', example: 'Правописание Н и НН в причастиях и отглагольных прилагательных' },
            { id: 'r_traps', icon: '🎯', title: 'Экзамен: исключения и капканы', subtitle: 'Редкие слова и капканы', template: 'Собери тест из 5 заданий формата ЦТ по русскому языку с редкими исключениями и экзаменационными дистракторами 2026 на тему: ', example: 'Правописание приставок ПРЕ- и ПРИ-' },
            { id: 'r_mnemonic', icon: '🧠', title: 'Мнемоника для правил', subtitle: 'Рифмовки и ассоциации', template: 'Придумай запоминающиеся мнемонические фразы, рифмовки и ассоциации для правил русского языка на тему: ', example: 'Суффиксы -ОВА-/-ЕВА- и -ЫВА-/-ИВА-' },
            { id: 'r_dict', icon: '📖', title: 'Словарный минимум ЦТ', subtitle: 'Орфоэпия и капканы', template: 'Выдели ключевые слова из орфоэпического и орфографического экзаменационного минимума для экспресс-тренировки на тему: ', example: 'Ударения в именах существительных и глаголах' },
            { id: 'r_syntax', icon: '✒️', title: 'Пунктуационный разбор', subtitle: 'Сложные предложения', template: 'Составь тренажер по пунктуации с объяснением каждого знака препинания по русскому языку на тему: ', example: 'Запятая на стыке союзов в сложных предложениях' },
            { id: 'r_hw', icon: '📋', title: 'Карточка самопроверки', subtitle: 'С ключами и комментариями', template: 'Составь карточку для самостоятельной работы ученика с самопроверкой по критериям экзамена на тему: ', example: 'Слитное, раздельное и дефисное написание наречий' }
        ],
        'Химия': [
            { id: 'c_plan', icon: '📚', title: 'План урока и реакции', subtitle: 'Уравнения и электронный баланс', template: 'Составь технологическую карту 60-минутного урока по химии для подготовки к ЦТ/ЦЭ на тему: ', example: 'Окислительно-восстановительные реакции и метод электронного баланса' },
            { id: 'c_traps', icon: '🎯', title: 'Ловушки ЦТ по химии', subtitle: 'Расчетные задачи части Б', template: 'Придумай 3 расчетные задачи части Б формата ЦТ 2026 по химии с ловушками на избыток/недостаток и примеси на тему: ', example: 'Растворы и массовая доля растворенного вещества' },
            { id: 'c_analogy', icon: '💡', title: 'Химия на пальцах', subtitle: 'Наглядные механизмы', template: 'Как наглядно объяснить протекание реакций и строение молекул простыми бытовыми примерами на тему: ', example: 'Гидролиз солей и среда раствора' },
            { id: 'c_hw', icon: '📋', title: 'Цепочки превращений', subtitle: 'Генетическая связь', template: 'Составь цепочку органических и неорганических превращений с уравнениями реакций по химии на тему: ', example: 'Генетическая связь классов углеводородов' }
        ],
        'Биология': [
            { id: 'b_plan', icon: '📚', title: 'План урока ЦТ/ЦЭ', subtitle: 'Схемы и таблицы', template: 'Составь конспект и интерактивный план занятия по биологии для подготовки к ЦТ/ЦЭ на тему: ', example: 'Митоз и мейоз: фазы, набор хромосом и ДНК' },
            { id: 'b_traps', icon: '🎯', title: 'Ловушки ЦТ по биологии', subtitle: 'Схемы, рисунки и термины', template: 'Сформулируй 5 каверзных вопросов ЦТ по биологии с рисунками и неочевидными формулировками на тему: ', example: 'Анатомия человека: кровеносная система и круги кровообращения' },
            { id: 'b_mnemonic', icon: '🧠', title: 'Мнемоника в биологии', subtitle: 'Запоминание фаз и циклов', template: 'Придумай мнемонические правила и ассоциативные образы для биологии на тему: ', example: 'Систематика растений и фазы деления клетки' }
        ],
        'Английский язык': [
            { id: 'e_plan', icon: '📚', title: 'Lesson Plan & Grammar', subtitle: 'Коммуникативный практикум', template: 'Составь 60-минутный технологический план урока английского языка к ЦТ/ЦЭ на тему: ', example: 'Conditionals (0, 1, 2, 3 and Mixed types)' },
            { id: 'e_traps', icon: '🎯', title: 'Ловушки ЦТ: Prepositions', subtitle: 'Предлоги и фразовые глаголы', template: 'Составь блок заданий формата ЦТ по английскому языку на предлоги, артикли и устойчивые словосочетания на тему: ', example: 'Dependent Prepositions and Phrasal Verbs' },
            { id: 'e_lexis', icon: '💡', title: 'Word Formation Drill', subtitle: 'Словообразование ЦТ', template: 'Составь тренажер по словообразованию (Word Formation) формата ЦТ с дистракторами на тему: ', example: 'Prefixes and Suffixes for Nouns and Adjectives' }
        ],
        'История Беларуси': [
            { id: 'h_plan', icon: '📚', title: 'План урока и таймлайн', subtitle: 'Хронология и термины', template: 'Составь конспект и план интенсивного урока по истории Беларуси к ЦТ/ЦЭ с ключевыми датами на тему: ', example: 'Великое Княжество Литовское в XIV-XVI веках' },
            { id: 'h_traps', icon: '🎯', title: 'Ловушки ЦТ по истории', subtitle: 'Путаница в картах и датах', template: 'Назови типичные ловушки и частые ошибки абитуриентов на ЦТ/ЦЭ по истории Беларуси на тему: ', example: 'Люблинская и Брестская церковная унии' },
            { id: 'h_mnemonic', icon: '🧠', title: 'Мнемоника дат и имен', subtitle: 'Ассоциативный ряд', template: 'Придумай ассоциативные подсказки и мнемонические цепочки для легкого запоминания дат по истории на тему: ', example: 'События и реформы второй половины XIX века' }
        ],
        'Обществоведение': [
            { id: 's_plan', icon: '📚', title: 'Техкарта ЦТ/ЦЭ', subtitle: 'Понятия и классификации', template: 'Составь план интенсивного урока по обществоведению для сдачи ЦТ/ЦЭ на 90+ баллов на тему: ', example: 'Политическая система общества и формы правления' },
            { id: 's_traps', icon: '🎯', title: 'Ловушки ЦТ: термины', subtitle: 'Тонкие смысловые различия', template: 'Составь 5 тестовых заданий по обществоведению с тонкими смысловыми ловушками составителей тестов на тему: ', example: 'Отрасли права и юридическая ответственность' }
        ],
        'Информатика': [
            { id: 'i_plan', icon: '📚', title: 'План урока и кодинг', subtitle: 'Алгоритмы и структуры данных', template: 'Составь план практического занятия по программированию и информатике на тему: ', example: 'Двумерные массивы и базовые алгоритмы сортировки' },
            { id: 'i_traps', icon: '🎯', title: 'Ловушки в коде и тестах', subtitle: 'Граничные случаи и сложность', template: 'Придумай задачи по программированию с коварными граничными случаями и оценкой сложности O(N) на тему: ', example: 'Рекурсия и динамическое программирование' }
        ]
    };

    // Personalized prompts generator based on active subject & role
    const getStandardPrompts = () => {
        if (isTutor) {
            if (SUBJECT_PRESETS[selectedSubject]) {
                return SUBJECT_PRESETS[selectedSubject];
            }
            return [
                {
                    id: 'custom_plan',
                    icon: '📚',
                    title: 'План урока ЦТ/ЦЭ',
                    subtitle: 'Техкарта на 60 мин',
                    template: `Составь подробный 60-минутный технологический план урока подготовки к ЦТ по предмету «${selectedSubject}» на тему: `,
                    example: 'Основы темы и ключевые формулы'
                },
                {
                    id: 'custom_traps',
                    icon: '🎯',
                    title: 'Экзаменационные ловушки',
                    subtitle: 'Часть Б с капканами',
                    template: `Придумай 3 тестовые задачи формата ЦТ/ЦЭ 2026 части Б по предмету «${selectedSubject}» с типичными ловушками составителей тестов и подробным решением на тему: `,
                    example: 'Типовые ошибки абитуриентов'
                },
                {
                    id: 'custom_analogy',
                    icon: '💡',
                    title: 'Как объяснить тему',
                    subtitle: 'Наглядная аналогия',
                    template: `Как наглядно и понятно объяснить ученику без зубрежки на простых жизненных примерах тему по предмету «${selectedSubject}»: `,
                    example: 'Ключевое правило на пальцах'
                },
                {
                    id: 'custom_hw',
                    icon: '📋',
                    title: 'Генератор ДЗ',
                    subtitle: '3 уровня сложности',
                    template: `Составь дифференцированное домашнее задание (база, повышенный уровень, олимпиадная задача) с ключами проверки по предмету «${selectedSubject}» на тему: `,
                    example: 'Практические задания с ответами'
                },
                {
                    id: 'custom_express',
                    icon: '⚡',
                    title: 'Экспресс-разбор',
                    subtitle: 'Пошаговый алгоритм',
                    template: `Дай строгое пошаговое аналитическое решение и методическое оформление задачи по предмету «${selectedSubject}» на тему: `,
                    example: 'Сложная задача из РТ/ЦТ'
                },
                {
                    id: 'custom_timing',
                    icon: '⏱️',
                    title: 'Тайминг на ЦТ',
                    subtitle: 'Стратегия 85+ баллов',
                    template: `Дай практические советы для абитуриента, как распределить время на централизованном экзамене по предмету «${selectedSubject}» при решении темы: `,
                    example: 'Оптимальный тайм-менеджмент'
                }
            ];
        }

        // Student / Parent prompts
        return [
            {
                id: 'explain',
                icon: '❓',
                title: 'Объясни формулу',
                subtitle: 'Просто и понятно',
                template: `Объясни мне простыми словами формулу и правило по предмету «${selectedSubject}» на тему: `,
                example: 'Суть формулы простыми словами'
            },
            {
                id: 'task_step',
                icon: '📐',
                title: 'Разбор задачи',
                subtitle: 'Пошагово с формулами',
                template: `Помоги разобрать по шагам решение типового задания по предмету «${selectedSubject}» на тему: `,
                example: 'Сложный номер из домашнего задания'
            },
            {
                id: 'traps_ct',
                icon: '🎯',
                title: 'Ловушки ЦТ',
                subtitle: 'Где теряют баллы',
                template: `Назови топ-5 типичных ошибок и ловушек в тестах ЦТ/ЦЭ по предмету «${selectedSubject}» на тему: `,
                example: 'Самые опасные вопросы теста'
            },
            {
                id: 'strategy',
                icon: '⏱️',
                title: 'Тайм-менеджмент',
                subtitle: 'Как успеть всё',
                template: `Дай совет, как быстрее всего решать и не допускать обидных ошибок по теме: `,
                example: 'Распределение времени на экзамене'
            }
        ];
    };

    // Core Interaction: Click prompt button -> Insert into input field -> Focus & Position cursor
    const handleInsertPrompt = (template, title, example = '') => {
        setInputPrompt(template);
        setIsInputPulsing(true);
        setInsertNotice({
            title: title,
            example: example || 'например: Производная функции'
        });

        // Focus & position cursor at the very end of template
        setTimeout(() => {
            if (textareaRef.current) {
                textareaRef.current.focus();
                const pos = template.length;
                textareaRef.current.setSelectionRange(pos, pos);
                textareaRef.current.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }
        }, 50);

        setTimeout(() => {
            setIsInputPulsing(false);
        }, 1500);
    };

    // Send Message API with real-time SSE Streaming
    const handleSendMessage = async (textToSend = null) => {
        const text = (textToSend || inputPrompt).trim();
        if (!text || isLoading) return;

        setInputPrompt('');
        setInsertNotice(null);
        setIsLoading(true);

        const newMsg = {
            id: 'user_' + Date.now(),
            role: 'user',
            content: text,
            timestamp: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
        };

        const updatedHistory = [...messages, newMsg];
        saveMessages(updatedHistory);

        const token = csrfToken || document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        const historyPayload = updatedHistory.slice(-8).map(m => ({
            role: m.role === 'assistant' ? 'assistant' : 'user',
            content: m.content
        }));

        const aiMsgId = 'ai_' + (Date.now() + 1);
        const initialAiMsg = {
            id: aiMsgId,
            role: 'assistant',
            content: '',
            timestamp: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
        };

        let currentConversation = [...updatedHistory, initialAiMsg];
        saveMessages(currentConversation);

        try {
            // Attempt 1: Real-time SSE Stream (Time-To-First-Token < 400ms)
            const streamRes = await fetch('/admin/ai-copilot/stream', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'text/event-stream',
                    'X-CSRF-TOKEN': token
                },
                body: JSON.stringify({
                    prompt: text,
                    history: historyPayload,
                    persona: persona,
                    tone: 'friendly',
                    length: 'balanced',
                    subject: selectedSubject.replace('🌟 ', ''),
                    student_id: selectedStudentId || null,
                    custom_prompt: `Выбранный предмет: ${selectedSubject}`
                })
            });

            if (streamRes.ok && streamRes.body) {
                const reader = streamRes.body.getReader();
                const decoder = new TextDecoder('utf-8');
                let accumulated = '';
                let buffer = '';

                while (true) {
                    const { value, done } = await reader.read();
                    if (done) break;

                    buffer += decoder.decode(value, { stream: true });
                    const lines = buffer.split('\n');
                    buffer = lines.pop() || '';

                    for (const line of lines) {
                        const trimmed = line.trim();
                        if (trimmed.startsWith('data:')) {
                            const jsonStr = trimmed.slice(5).trim();
                            if (jsonStr) {
                                try {
                                    const parsed = JSON.parse(jsonStr);
                                    if (parsed.text) {
                                        accumulated += parsed.text;
                                        setMessages(prev => prev.map(m => m.id === aiMsgId ? { ...m, content: accumulated } : m));
                                    }
                                } catch (e) {}
                            }
                        }
                    }
                }

                if (accumulated.trim().length > 0) {
                    const finalHistory = updatedHistory.concat({
                        id: aiMsgId,
                        role: 'assistant',
                        content: accumulated,
                        timestamp: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
                    });
                    saveMessages(finalHistory);
                    setIsLoading(false);
                    return;
                }
            }

            // Attempt 2: Fallback to synchronous chat endpoint
            const syncRes = await fetch(endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': token
                },
                body: JSON.stringify({
                    prompt: text,
                    history: historyPayload,
                    persona: persona,
                    tone: 'friendly',
                    length: 'balanced',
                    subject: selectedSubject.replace('🌟 ', ''),
                    student_id: selectedStudentId || null,
                    custom_prompt: `Выбранный предмет: ${selectedSubject}`
                })
            });

            const data = await syncRes.json();
            if (syncRes.ok && data.success) {
                const finalHistory = updatedHistory.concat({
                    id: aiMsgId,
                    role: 'assistant',
                    content: data.reply || 'Ответ сформирован.',
                    timestamp: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
                });
                saveMessages(finalHistory);
            } else {
                throw new Error(data.error || 'Сервер не смог вернуть ответ.');
            }
        } catch (err) {
            setMessages(prev => prev.map(m => m.id === aiMsgId ? {
                ...m,
                content: `⚠️ **Ошибка связи:** ${err.message || 'Проверьте соединение или обновите страницу.'}`,
                isError: true
            } : m));
        } finally {
            setIsLoading(false);
            scrollToBottom();
        }
    };

    const handleCopy = (content, idx) => {
        if (navigator.clipboard) {
            navigator.clipboard.writeText(content).then(() => {
                setCopiedIdx(idx);
                setTimeout(() => setCopiedIdx(null), 2000);
            });
        }
    };

    // Export conversation or specific message as Markdown file
    const handleExportMarkdown = (content) => {
        try {
            const blob = new Blob([content], { type: 'text/markdown;charset=utf-8' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `Edusfera_${selectedSubject}_${new Date().toISOString().slice(0, 10)}.md`;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
            showToast('success', 'Конспект сохранён в файл .md');
        } catch (e) {
            showToast('error', 'Не удалось скачать файл');
        }
    };

    // Agent Action: 1-click Homework Assignment creation in system DB
    const handleCreateHomework = async (content) => {
        if (!selectedStudentId) {
            showToast('error', 'Выберите ученика в выпадающем списке под шапкой для назначения ДЗ.');
            return;
        }

        const token = csrfToken || document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        showToast('loading', 'Назначаем домашнее задание в системе...');

        try {
            const res = await fetch('/admin/ai-copilot/action', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': token
                },
                body: JSON.stringify({
                    action: 'homework.create',
                    payload: {
                        title: `Задания по теме «${selectedSubject}»`,
                        instructions: content,
                        student_id: selectedStudentId,
                        due_days: 3
                    }
                })
            });

            const data = await res.json();
            if (res.ok && data.success) {
                showToast('success', data.message || '✓ ДЗ успешно создано в системе Edusfera!');
            } else {
                throw new Error(data.error || 'Не удалось создать задание');
            }
        } catch (e) {
            showToast('error', e.message);
        }
    };

    // Render mathematical expressions, KaTeX, and GFM markdown
    const renderMarkdownContent = (text) => {
        if (!text) return '';
        try {
            return formatAiMarkdown(text);
        } catch (e) {
            return String(text);
        }
    };

    const standardPrompts = getStandardPrompts();

    return (
        <div className={`ed-floating-copilot-container ${isOpen ? 'is-open' : ''}`}>
            {/* ═══ 1. МОБИЛЬНЫЙ BACKDROP OVERLAY ═══ */}
            <AnimatePresence>
                {isOpen && (
                    <motion.div
                        key="backdrop"
                        initial={{ opacity: 0 }}
                        animate={{ opacity: 1 }}
                        exit={{ opacity: 0 }}
                        transition={{ duration: 0.2 }}
                        className="ed-copilot-backdrop"
                        onClick={() => setIsOpen(false)}
                    />
                )}
            </AnimatePresence>

            {/* ═══ 2. ПЛАВАЮЩАЯ КНОПКА (FAB) ═══ */}
            <div className="ed-copilot-fab-wrapper">
                <button
                    type="button"
                    onClick={toggleDrawer}
                    className={`ed-copilot-3d-fab ${isOpen ? 'ed-fab-open' : ''}`}
                    aria-label="ИИ-Методист Edusfera"
                >
                    <div className="ed-fab-orb-stage">
                        {isOpen ? (
                            <X className="w-5 h-5 text-white" />
                        ) : (
                            <Sparkles className="w-5 h-5 text-[#C6FF33]" />
                        )}
                    </div>

                    <div className="ed-fab-label-pill">
                        <span className="ed-fab-pulse-dot" />
                        <span className="ed-fab-title-text">
                            {isOpen ? 'Закрыть' : 'ИИ-Методист'}
                        </span>
                        {!isOpen && (
                            <span className="ed-fab-badge-sub">ЦТ/ЦЭ 2026</span>
                        )}
                    </div>
                </button>
            </div>

            {/* ═══ 4. 3D GLASSO-DRAWER ПАНЕЛЬ ДИАЛОГА ═══ */}
            <AnimatePresence>
                {isOpen && (
                    <motion.div
                        ref={panelRef}
                        key="copilot-panel"
                        initial={{ opacity: 0, scale: 0.92, y: 30, rotateX: 6 }}
                        animate={{ opacity: 1, scale: 1, y: 0, rotateX: 0 }}
                        exit={{ opacity: 0, scale: 0.92, y: 30, rotateX: 4 }}
                        transition={{ type: 'spring', stiffness: 380, damping: 28 }}
                        drag="y"
                        dragConstraints={{ top: 0 }}
                        dragElastic={0.2}
                        onDragEnd={(e, info) => {
                            // Mobile swipe-to-dismiss gesture
                            if (info.offset.y > 110 || info.velocity.y > 400) {
                                setIsOpen(false);
                            }
                        }}
                        className={`ed-copilot-glass-panel ${viewMode === 'expanded' ? 'ed-panel--expanded' : (viewMode === 'docked' ? 'ed-panel--docked' : '')}`}
                    >
                        {/* ТОСТ УВЕДОМЛЕНИЙ АГЕНТА */}
                        {toast && (
                            <div className={`ed-copilot-toast ${toast.type}`}>
                                {toast.type === 'success' && <CheckCircle2 className="w-4 h-4 shrink-0" />}
                                {toast.type === 'loading' && <span className="ed-dot-pulse shrink-0" />}
                                <span>{toast.text}</span>
                            </div>
                        )}

                        {/* МОБИЛЬНЫЙ ДРАГ-ХЭНДЛ ДЛЯ СВАЙПА ВНИЗ */}
                        <div className="ed-panel-drag-bar" title="Потяните вниз, чтобы свернуть">
                            <span className="ed-panel-drag-pill" />
                        </div>

                        {/* ШАПКА ДИАЛОГА (TIER 1) */}
                        <div className="ed-panel-header">
                            <div className="ed-header-left">
                                <div className="ed-header-avatar">
                                    <svg width="20" height="20" viewBox="0 0 64 64" fill="none">
                                        <path d="M32 10L54 32L32 54L10 32L32 10Z" stroke="#C6FF33" strokeWidth="6" strokeLinejoin="round" />
                                        <path d="M32 22L42 32L32 42L22 32L32 22Z" fill="#C6FF33" />
                                    </svg>
                                </div>
                                <div className="ed-header-title-box">
                                    <div className="flex items-center gap-1.5">
                                        <h3 className="ed-header-title">Edusfera AI</h3>
                                        <span className="ed-header-role-badge">
                                            {isTutor ? 'Методист' : 'Тьютор'}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            {/* Кнопки управления шапкой */}
                            <div className="ed-header-right-actions">
                                <button
                                    type="button"
                                    onClick={handleNewChat}
                                    className="ed-icon-btn text-[#7D39EB] dark:text-[#C6FF33]"
                                    title="Начать новый диалог"
                                >
                                    <PlusCircle className="w-4 h-4" />
                                </button>
                                <button
                                    type="button"
                                    onClick={() => setViewMode(prev => prev === 'expanded' ? 'standard' : 'expanded')}
                                    className={`ed-icon-btn ed-desktop-only ${viewMode === 'expanded' ? 'active' : ''}`}
                                    title={viewMode === 'expanded' ? 'Обычный вид' : 'Развернуть на широкий экран'}
                                >
                                    {viewMode === 'expanded' ? <Minimize2 className="w-4 h-4" /> : <Maximize2 className="w-4 h-4" />}
                                </button>
                                <button
                                    type="button"
                                    onClick={() => setShowSettings(!showSettings)}
                                    className={`ed-icon-btn ${showSettings ? 'active' : ''}`}
                                    title="Настройки роли ИИ"
                                >
                                    <SlidersHorizontal className="w-4 h-4" />
                                </button>
                                {messages.length > 0 && (
                                    <button
                                        type="button"
                                        onClick={handleClearHistory}
                                        className="ed-icon-btn text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-950/40"
                                        title="Очистить историю"
                                    >
                                        <Trash2 className="w-4 h-4" />
                                    </button>
                                )}
                                <button
                                    type="button"
                                    onClick={() => setIsOpen(false)}
                                    className="ed-icon-btn"
                                    title="Свернуть"
                                >
                                    <X className="w-4 h-4" />
                                </button>
                            </div>
                        </div>

                        {/* САББАР ПРЕДМЕТА И УЧЕНИКА (TIER 2) */}
                        <div className="ed-panel-subbar">
                            {/* Выбор предмета */}
                            <div className="relative" ref={subjectMenuRef}>
                                <button
                                    type="button"
                                    onClick={() => {
                                        setShowSubjectMenu(!showSubjectMenu);
                                        setShowStudentMenu(false);
                                    }}
                                    className="ed-subbar-pill-btn"
                                    title="Сменить предмет для персонализации"
                                >
                                    <GraduationCap className="w-3.5 h-3.5 text-[#7D39EB] dark:text-[#C6FF33] shrink-0" />
                                    <span className="truncate">{selectedSubject}</span>
                                    <ChevronDown className={`w-3 h-3 text-slate-400 transition-transform ${showSubjectMenu ? 'rotate-180' : ''}`} />
                                </button>

                                {showSubjectMenu && (
                                    <div className="ed-subject-dropdown-menu">
                                        <div className="ed-subject-menu-title">Выберите предмет:</div>
                                        <div className="ed-subject-menu-grid">
                                            {ALL_SUBJECTS.map((subj) => (
                                                <button
                                                    key={subj}
                                                    type="button"
                                                    onClick={() => {
                                                        setSelectedSubject(subj);
                                                        setShowSubjectMenu(false);
                                                    }}
                                                    className={`ed-subject-item ${selectedSubject === subj ? 'active' : ''}`}
                                                >
                                                    <span>{subj}</span>
                                                    {selectedSubject === subj && <Check className="w-3 h-3 text-[#7D39EB]" />}
                                                </button>
                                            ))}
                                        </div>
                                    </div>
                                )}
                            </div>

                            {/* Выбор ученика (Кастомный дропдаун без бага дублирования стрелок) */}
                            {isTutor && (
                                <div className="relative" ref={studentMenuRef}>
                                    <button
                                        type="button"
                                        onClick={() => {
                                            setShowStudentMenu(!showStudentMenu);
                                            setShowSubjectMenu(false);
                                        }}
                                        className="ed-subbar-pill-btn"
                                        title="Контекст диалога: ученик или общий режим"
                                    >
                                        {selectedStudentId ? (
                                            <User className="w-3.5 h-3.5 text-[#7D39EB] dark:text-[#C6FF33] shrink-0" />
                                        ) : (
                                            <MessageSquare className="w-3.5 h-3.5 text-[#7D39EB] dark:text-[#C6FF33] shrink-0" />
                                        )}
                                        <span className="truncate max-w-[125px]">
                                            {students.find(s => s.id === selectedStudentId)?.name || 'Без ученика'}
                                        </span>
                                        <ChevronDown className={`w-3 h-3 text-slate-400 transition-transform ${showStudentMenu ? 'rotate-180' : ''}`} />
                                    </button>

                                    {showStudentMenu && (
                                        <div className="ed-subject-dropdown-menu">
                                            <div className="ed-subject-menu-title">Контекст диалога:</div>
                                            <div className="ed-subject-menu-grid">
                                                <button
                                                    type="button"
                                                    onClick={() => {
                                                        setSelectedStudentId(null);
                                                        setShowStudentMenu(false);
                                                    }}
                                                    className={`ed-subject-item ${!selectedStudentId ? 'active' : ''}`}
                                                >
                                                    <div className="flex items-center gap-2 truncate">
                                                        <span>💬</span>
                                                        <span>Без ученика (Общий диалог)</span>
                                                    </div>
                                                    {!selectedStudentId && <Check className="w-3.5 h-3.5 text-[#7D39EB] dark:text-[#C6FF33] shrink-0" />}
                                                </button>
                                                {students.map((st) => (
                                                    <button
                                                        key={st.id}
                                                        type="button"
                                                        onClick={() => {
                                                            setSelectedStudentId(st.id);
                                                            setShowStudentMenu(false);
                                                        }}
                                                        className={`ed-subject-item ${selectedStudentId === st.id ? 'active' : ''}`}
                                                    >
                                                        <div className="flex items-center gap-2 truncate">
                                                            <span>👤</span>
                                                            <span className="truncate max-w-[170px]">{st.name}</span>
                                                        </div>
                                                        {selectedStudentId === st.id && <Check className="w-3.5 h-3.5 text-[#7D39EB] dark:text-[#C6FF33] shrink-0" />}
                                                    </button>
                                                ))}
                                            </div>
                                        </div>
                                    )}
                                </div>
                            )}
                        </div>

                        {/* НАСТРОЙКИ ПЕРСОНЫ / ТОНАЛЬНОСТИ */}
                        <AnimatePresence>
                            {showSettings && (
                                <motion.div
                                    initial={{ height: 0, opacity: 0 }}
                                    animate={{ height: 'auto', opacity: 1 }}
                                    exit={{ height: 0, opacity: 0 }}
                                    className="ed-panel-settings-drawer"
                                >
                                    <div className="ed-settings-label">Педагогическая роль:</div>
                                    <div className="ed-settings-personas-list">
                                        {PERSONAS.map((p) => (
                                            <button
                                                key={p.id}
                                                type="button"
                                                onClick={() => setPersona(p.id)}
                                                className={`ed-persona-badge ${persona === p.id ? 'active' : ''}`}
                                            >
                                                <span>{p.label}</span>
                                            </button>
                                        ))}
                                    </div>
                                </motion.div>
                            )}
                        </AnimatePresence>

                        {/* БЫСТРЫЕ ЧИПСЫ С ПЕРСОНАЛИЗАЦИЕЙ ПО ПРЕДМЕТУ (ТОЛЬКО ПРИ АКТИВНОМ ДИАЛОГЕ) */}
                        {messages.length > 0 && (
                            <div className="ed-chips-scroll-lane">
                                <div className="ed-chips-container">
                                    {standardPrompts.map((p) => (
                                        <button
                                            key={p.id}
                                            type="button"
                                            onClick={() => handleInsertPrompt(p.template, p.title, p.example)}
                                            className="ed-prompt-chip-btn"
                                            title={`Вставить шаблон «${p.title}» в поле ввода`}
                                        >
                                            <span className="ed-chip-icon">{p.icon}</span>
                                            <span className="ed-chip-title">{p.title}</span>
                                            <CornerDownLeft className="w-3 h-3 text-[#7D39EB] opacity-60 ml-0.5" />
                                        </button>
                                    ))}
                                </div>
                            </div>
                        )}

                        {/* ОКНО СООБЩЕНИЙ */}
                        <div className="ed-panel-messages-body">
                            {messages.length === 0 ? (
                                <div className="ed-empty-state-canvas">
                                    <div className="ed-empty-spark-badge">
                                        <Sparkles className="w-3.5 h-3.5 text-[#7D39EB] dark:text-[#C6FF33] shrink-0" />
                                        <span>ЦТ/ЦЭ 2026 • Умный ассистент</span>
                                    </div>

                                    <h4 className="ed-empty-title">
                                        Чем помочь по предмету «{selectedSubject}»?
                                    </h4>
                                    <p className="ed-empty-desc">
                                        Выберите готовый сценарий для быстрой вставки или задайте вопрос своими словами:
                                    </p>

                                    {/* СЕТКА ПЕРСОНАЛИЗИРОВАННЫХ КАРТОЧЕК */}
                                    <div className="ed-feature-cards-grid">
                                        {standardPrompts.map((card) => (
                                            <motion.div
                                                key={card.id}
                                                whileHover={{ scale: 1.02, translateY: -2 }}
                                                whileTap={{ scale: 0.98 }}
                                                onClick={() => handleInsertPrompt(card.template, card.title, card.example)}
                                                className="ed-feature-action-card"
                                            >
                                                <span className="ed-card-icon">{card.icon}</span>
                                                <div className="ed-card-texts">
                                                    <div className="ed-card-title">{card.title}</div>
                                                    <div className="ed-card-sub">{card.subtitle}</div>
                                                </div>
                                                <CornerDownLeft className="ed-card-corner-arrow" />
                                            </motion.div>
                                        ))}
                                    </div>
                                </div>
                            ) : (
                                <div className="space-y-3.5">
                                    {messages.map((msg, idx) => {
                                        const isUser = msg.role === 'user';
                                        return (
                                            <div
                                                key={msg.id || idx}
                                                className={`ed-msg-row ${isUser ? 'ed-msg-user' : 'ed-msg-ai'}`}
                                            >
                                                {!isUser && (
                                                    <div className="ed-msg-avatar">
                                                        <svg width="18" height="18" viewBox="0 0 64 64" fill="none">
                                                            <path d="M32 10L54 32L32 54L10 32L32 10Z" stroke="#C6FF33" strokeWidth="6" strokeLinejoin="round" />
                                                            <path d="M32 22L42 32L32 42L22 32L32 22Z" fill="#C6FF33" />
                                                        </svg>
                                                    </div>
                                                )}

                                                <div className="ed-msg-bubble">
                                                    <div className="ed-msg-top-meta">
                                                        <span className="ed-msg-author">
                                                            {isUser ? (user?.name || 'Вы') : 'Edusfera AI'}
                                                        </span>
                                                        <span className="ed-msg-time">{msg.timestamp}</span>
                                                    </div>

                                                    <div 
                                                        className="ed-msg-body"
                                                        dangerouslySetInnerHTML={{ __html: renderMarkdownContent(msg.content) }}
                                                    />

                                                    {!isUser && (
                                                        <div className="ed-msg-footer-bar">
                                                            <div className="text-[11px] opacity-75 font-semibold text-slate-500 dark:text-slate-400">
                                                                ЦТ/ЦЭ 2026 • {selectedSubject}
                                                            </div>
                                                            <div className="ed-msg-actions-row">
                                                                <button
                                                                    type="button"
                                                                    onClick={() => handleCopy(msg.content, idx)}
                                                                    className="ed-action-btn"
                                                                    title="Скопировать текст ответа"
                                                                >
                                                                    {copiedIdx === idx ? (
                                                                        <>
                                                                            <Check className="w-3.5 h-3.5 text-emerald-500" />
                                                                            <span>Скопировано!</span>
                                                                        </>
                                                                    ) : (
                                                                        <>
                                                                            <Copy className="w-3.5 h-3.5" />
                                                                            <span>Копировать</span>
                                                                        </>
                                                                    )}
                                                                </button>
                                                                <button
                                                                    type="button"
                                                                    onClick={() => handleExportMarkdown(msg.content)}
                                                                    className="ed-action-btn"
                                                                    title="Скачать конспект в файл .md"
                                                                >
                                                                    <Download className="w-3.5 h-3.5" />
                                                                    <span>Скачать .md</span>
                                                                </button>
                                                                {isTutor && (
                                                                    <button
                                                                        type="button"
                                                                        onClick={() => handleCreateHomework(msg.content)}
                                                                        className="ed-action-btn ed-action-primary"
                                                                        title="Назначить ДЗ ученику в базу данных Edusfera"
                                                                    >
                                                                        <FileText className="w-3.5 h-3.5 text-[#7D39EB] dark:text-[#C6FF33]" />
                                                                        <span>Создать ДЗ</span>
                                                                    </button>
                                                                )}
                                                            </div>
                                                        </div>
                                                    )}
                                                </div>
                                            </div>
                                        );
                                    })}

                                    {/* Индикатор генерации ответа */}
                                    {isLoading && (
                                        <div className="ed-msg-row ed-msg-ai">
                                            <div className="ed-msg-avatar animate-pulse">
                                                <svg width="18" height="18" viewBox="0 0 64 64" fill="none">
                                                    <path d="M32 10L54 32L32 54L10 32L32 10Z" stroke="#C6FF33" strokeWidth="6" strokeLinejoin="round" />
                                                    <path d="M32 22L42 32L32 42L22 32L32 22Z" fill="#C6FF33" />
                                                </svg>
                                            </div>
                                            <div className="ed-msg-bubble ed-typing-bubble">
                                                <span className="ed-dot-pulse" />
                                                <span className="ed-dot-pulse delay-100" />
                                                <span className="ed-dot-pulse delay-200" />
                                                <span className="text-xs font-semibold text-slate-500 dark:text-slate-400 ml-1">
                                                    ИИ-Методист формулирует ответ...
                                                </span>
                                            </div>
                                        </div>
                                    )}

                                    <div ref={messagesEndRef} />
                                </div>
                            )}
                        </div>

                        {/* НИЖНЯЯ ПАНЕЛЬ ВВОДА С ТИЗЕРОМ ВСТАВКИ ПРОМПТА */}
                        <div className="ed-panel-bottom-deck">
                            {/* Информационный баннер при вставке шаблона */}
                            <AnimatePresence>
                                {insertNotice && (
                                    <motion.div
                                        initial={{ opacity: 0, y: 8, scale: 0.95 }}
                                        animate={{ opacity: 1, y: 0, scale: 1 }}
                                        exit={{ opacity: 0, y: 5, scale: 0.95 }}
                                        className="ed-insert-notice-banner"
                                    >
                                        <Sparkles className="w-3.5 h-3.5 text-[#7D39EB] shrink-0 animate-spin-slow" />
                                        <span className="truncate">
                                            Шаблон «{insertNotice.title}» вставлен! Введите тему (<strong>{insertNotice.example}</strong>) и нажмите Отправить ⏎
                                        </span>
                                        <button
                                            type="button"
                                            onClick={() => setInsertNotice(null)}
                                            className="ed-notice-close"
                                            title="Скрыть подсказку"
                                        >
                                            <X className="w-3 h-3" />
                                        </button>
                                    </motion.div>
                                )}
                            </AnimatePresence>

                            {/* Форма ввода сообщения */}
                            <div className={`ed-input-capsule ${isInputPulsing ? 'pulse-glow' : ''}`}>
                                <textarea
                                    ref={textareaRef}
                                    value={inputPrompt}
                                    onChange={(e) => setInputPrompt(e.target.value)}
                                    onKeyDown={(e) => {
                                        if (e.key === 'Enter' && !e.shiftKey) {
                                            e.preventDefault();
                                            handleSendMessage();
                                        }
                                    }}
                                    placeholder={`Введите тему урока по ${selectedSubject} или вопрос...`}
                                    rows={1}
                                    className="ed-copilot-textarea"
                                    disabled={isLoading}
                                />

                                <motion.button
                                    type="button"
                                    onClick={handleSendMessage}
                                    disabled={isLoading || !inputPrompt.trim()}
                                    whileHover={{ scale: 1.05 }}
                                    whileTap={{ scale: 0.92 }}
                                    className="ed-send-action-btn"
                                    title="Отправить (Enter)"
                                >
                                    <Send className="w-4 h-4 text-white" />
                                </motion.button>
                            </div>

                            <div className="ed-bottom-disclaimer">
                                Edusfera AI • Моделирование уроков по спецификациям ЦТ/ЦЭ 2026
                            </div>
                        </div>
                    </motion.div>
                )}
            </AnimatePresence>
        </div>
    );
}
