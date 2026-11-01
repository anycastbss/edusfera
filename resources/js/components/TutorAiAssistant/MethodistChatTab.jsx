import React, { useState, useRef, useEffect } from 'react';
import { 
    MessageSquare, 
    Send, 
    Sparkles, 
    Trash2, 
    Bot, 
    User, 
    SlidersHorizontal, 
    ChevronDown, 
    CornerDownLeft, 
    Copy, 
    Check, 
    BookOpen,
    GraduationCap,
    RotateCcw,
    ArrowUpRight,
    Lightbulb,
    HelpCircle
} from 'lucide-react';
import { formatAiMarkdown } from './AiFormattedOutput';

const PERSONAS = [
    { id: 'methodologist', label: '🎓 ИИ-Методист', desc: 'Технологические карты занятий и дидактика' },
    { id: 'rikz_expert', label: '🎯 Эксперт экзаменов', desc: 'Спецификации, критерии и разбор ловушек' },
    { id: 'express_solver', label: '⚡ Решатель задач', desc: 'Строгое пошаговое аналитическое решение' },
    { id: 'simple_analogy', label: '💡 Наглядные аналогии', desc: 'Объяснение сложных тем «на пальцах»' },
    { id: 'homework_gen', label: '📋 Генератор ДЗ', desc: 'Дифференцированные задания с ключами' },
    { id: 'free_chat', label: '💬 Свободный диалог', desc: 'Открытый брейншторм и педагогические идеи' },
];

const TONES = [
    { id: 'friendly', label: 'Дружелюбный' },
    { id: 'academic', label: 'Академический' },
    { id: 'concise', label: 'Краткий' },
    { id: 'step_by_step', label: 'Пошаговый' },
];

const LENGTHS = [
    { id: 'balanced', label: 'Сбалансированный' },
    { id: 'short', label: 'Краткий' },
    { id: 'detailed', label: 'Развернутый' },
];

const SUBJECTS_LIST = [
    'Математика',
    'Физика',
    'Русский язык',
    'Белорусский язык',
    'Английский язык',
    'Химия',
    'Биология',
    'История Беларуси',
    'Обществоведение',
    'Информатика',
    'Общий диалог'
];

const SUBJECT_STARTER_PROMPTS = {
    'Математика': [
        'Как наглядно объяснить формулу Бернулли для 10 класса без зубрежки?',
        'Составь подборку из 3 сложных логарифмических неравенств с разбором ловушек ОДЗ.',
        'Предложи план дифференцированного ДЗ по стереометрии на 3 уровня сложности.',
        'Дай пошаговый алгоритм решения тригонометрических уравнений с отбором корней.',
    ],
    'Физика': [
        'Как объяснить закон сохранения импульса и неупругий удар на примерах из жизни?',
        'Составь технологическую карту 60-минутного урока по кинематике равноускоренного движения.',
        'Какие типичные ошибки допускают школьники в законах постоянного тока и расчете смешанных цепей?',
        'Мнемоника для правила буравчика и правила левой руки (силы Ампера и Лоренца).',
    ],
    'Русский язык': [
        'Сформируй экспресс-шпаргалку по правилу Н и НН в различных частях речи с мнемоникой.',
        'Какие 5 главных ловушек составителей экзаменов в блоке орфографии и пунктуации?',
        'Разработай план практикума по теме «Слитное и раздельное написание НЕ с разными частями речи».',
        'Как просто научить ученика отличать деепричастный оборот от причастного?',
    ],
    'Белорусский язык': [
        'Як лёгка растлумачыць правіла пераходу О, Э ў А, Я пры змене націску?',
        'Складзі практыкум з 6 заданняў на правапіс мяккага знака і апострафа.',
        'Асаблівасці пастаноўкі знакаў прыпынку ў складаназалежных сказах з некалькімі даданымі.',
        'Тыповыя памылкі ў правапісе прыстаўных і ўстаўных зычных.',
    ],
    'Английский язык': [
        'Разработай сравнительную таблицу Conditionals (0, 1, 2, 3 и Mixed) с живыми примерами.',
        'Как эффективно отрабатывать фразовые глаголы (Phrasal Verbs) в контексте без зубрежки?',
        'Интерактивный сценарий урока на тему Passive Voice & Impersonal Structures на 60 мин.',
        'Топ-10 «ложных друзей переводчика» в экзаменационных текстах.',
    ],
    'Химия': [
        'Педагогическая метафора для метода электронного баланса в окислительно-восстановительных реакциях.',
        'Как объяснить электролиз водных растворов солей на катоде и аноде по ступеням?',
        'Составь расчетную задачу по химии на избыток/недостаток с подробным решением.',
        'Качественные реакции на катионы и анионы: удобная мнемоническая схема.',
    ],
    'Биология': [
        'Как за 10 минут объяснить разницу между транскрипцией и трансляцией биосинтеза белка?',
        'Алгоритм решения генетических задач на группы крови и сцепленное с полом наследование.',
        'Сравнительная таблица световой и темновой фаз фотосинтеза для старшеклассников.',
        'Схема строения кровеносной и иммунной систем человека для экспресс-повторения.',
    ],
    'История Беларуси': [
        'Составь тезисный конспект по теме «Статуты ВКЛ 1529, 1566 и 1588 гг.» в сравнении.',
        'Хронологический разбор ключевых событий Полоцкого княжества с датами и терминами.',
        'Как структурировать повторение темы Великой Отечественной войны и партизанского движения?',
        'Топ-5 сложных вопросов по культуре Беларуси эпохи Возрождения.',
    ],
    'Обществоведение': [
        'Сравнительная таблица форм правления и политических режимов с современными примерами.',
        'Как объяснить школьнику разницу между правоспособностью и дееспособностью граждан?',
        'Инфляция, виды безработицы и функции денег: понятный конспект для урока.',
        'Основные этапы социализации личности и институты гражданского общества.',
    ],
    'Информатика': [
        'Как наглядно объяснить переход от рекурсии к динамическому программированию на Python?',
        'Подборка задач на анализ и трассировку циклов с массивами для подготовки к экзамену.',
        'Таблицы истинности и законы де Моргана: интерактивный сценарий объяснения.',
        'Основы синтаксиса SQL: простые метафоры для SELECT, WHERE, GROUP BY и JOIN.',
    ],
    'Общий диалог': [
        'Как повысить мотивацию ученика, если у него пропал интерес к занятиям?',
        'Помоги спланировать индивидуальную образовательную траекторию на 3 месяца.',
        'Какие техники активного слушания и вовлечения лучше всего работают в онлайн-уроках?',
        'Как экологично давать обратную связь родителям о прогрессе ученика?',
    ],
};

export default function MethodistChatTab({ csrfToken, endpoints, user, onOpenLibrary }) {
    const initialSubject = user?.subject || (user?.subjects && user?.subjects[0]) || 'Математика';
    const [selectedSubject, setSelectedSubject] = useState(initialSubject);
    const [showSubjectMenu, setShowSubjectMenu] = useState(false);

    const [messages, setMessages] = useState([]);
    const [input, setInput] = useState('');
    const [isLoading, setIsLoading] = useState(false);

    // Settings
    const [persona, setPersona] = useState('methodologist');
    const [tone, setTone] = useState('friendly');
    const [length, setLength] = useState('balanced');
    const [showSettings, setShowSettings] = useState(false);

    const [copiedIndex, setCopiedIndex] = useState(null);
    const messagesEndRef = useRef(null);
    const textareaRef = useRef(null);
    const subjectMenuRef = useRef(null);

    const storageKey = `edusfera_ai_chat_${user?.id || 'tutor'}_${selectedSubject}`;

    // Close subject menu on outside click
    useEffect(() => {
        const handleClickOutside = (e) => {
            if (subjectMenuRef.current && !subjectMenuRef.current.contains(e.target)) {
                setShowSubjectMenu(false);
            }
        };
        document.addEventListener('mousedown', handleClickOutside);
        return () => document.removeEventListener('mousedown', handleClickOutside);
    }, []);

    // Auto-scroll on new message
    const scrollToBottom = () => {
        messagesEndRef.current?.scrollIntoView({ behavior: 'smooth' });
    };

    useEffect(() => {
        scrollToBottom();
    }, [messages, isLoading]);

    // Auto-resize textarea
    useEffect(() => {
        if (textareaRef.current) {
            textareaRef.current.style.height = 'auto';
            textareaRef.current.style.height = `${Math.min(textareaRef.current.scrollHeight, 180)}px`;
        }
    }, [input]);

    // Load chat history from localStorage for active subject
    useEffect(() => {
        try {
            const saved = localStorage.getItem(storageKey);
            if (saved) {
                setMessages(JSON.parse(saved));
            } else {
                setMessages([]);
            }
        } catch (e) {
            console.warn('Failed to load chat history:', e);
        }
    }, [selectedSubject]);

    const saveMessages = (newMsgs) => {
        setMessages(newMsgs);
        try {
            localStorage.setItem(storageKey, JSON.stringify(newMsgs.slice(-30)));
        } catch (e) {
            console.warn('Failed to save chat history:', e);
        }
    };

    const clearChat = () => {
        setMessages([]);
        localStorage.removeItem(storageKey);
    };

    const handleSendMessage = async (textToSend = null) => {
        const text = (textToSend || input).trim();
        if (!text || isLoading) return;

        const userMsg = {
            id: Date.now(),
            role: 'user',
            content: text,
            timestamp: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }),
        };

        const updatedMessages = [...messages, userMsg];
        saveMessages(updatedMessages);
        setInput('');
        if (textareaRef.current) textareaRef.current.style.height = 'auto';
        setIsLoading(true);

        try {
            const endpoint = endpoints?.chat || '/admin/ai-copilot/chat';
            const historyPayload = messages.slice(-10).map((m) => ({
                role: m.role === 'assistant' ? 'assistant' : 'user',
                content: m.content,
            }));

            const res = await fetch(endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken || (document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? ''),
                },
                body: JSON.stringify({
                    prompt: text,
                    history: historyPayload,
                    persona,
                    tone,
                    length,
                    subject: selectedSubject,
                }),
            });

            const data = await res.json();

            if (!res.ok || !data.success) {
                throw new Error(data.error || 'Не удалось получить ответ ИИ.');
            }

            const aiMsg = {
                id: Date.now() + 1,
                role: 'assistant',
                content: data.reply,
                timestamp: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }),
                persona,
            };

            saveMessages([...updatedMessages, aiMsg]);
        } catch (err) {
            console.error('Chat error:', err);
            const errorMsg = {
                id: Date.now() + 1,
                role: 'assistant',
                content: `⚠️ **Ошибка генерации:** ${err.message || 'Сервер временно недоступен. Попробуйте еще раз через несколько секунд.'}`,
                timestamp: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }),
                isError: true,
            };
            saveMessages([...updatedMessages, errorMsg]);
        } finally {
            setIsLoading(false);
        }
    };

    const handleKeyDown = (e) => {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            handleSendMessage();
        }
    };

    const handleCopy = async (content, idx) => {
        try {
            await navigator.clipboard.writeText(content);
            setCopiedIndex(idx);
            setTimeout(() => setCopiedIndex(null), 2000);
        } catch (err) {
            console.error('Failed to copy message:', err);
        }
    };

    const currentStarterPrompts = SUBJECT_STARTER_PROMPTS[selectedSubject] || SUBJECT_STARTER_PROMPTS['Математика'] || [];

    return (
        <div className="ed-methodist-card flex flex-col h-[740px] max-h-[82vh] overflow-hidden p-0 shadow-xl border border-slate-200/80 dark:border-slate-800 bg-white dark:bg-slate-900">
            {/* ═══ 1. HEADER (GLASSMORPHISM WITH CONTROLS) ═══ */}
            <div className="px-5 py-3.5 border-b border-[#ECEEF1] dark:border-slate-800 bg-[#FAF8FF] dark:bg-slate-800/60 flex flex-wrap items-center justify-between gap-3 shrink-0">
                <div className="flex items-center gap-3">
                    <div className="w-10 h-10 rounded-2xl bg-gradient-to-tr from-[#7D39EB] to-[#632cd6] text-white flex items-center justify-center shadow-md shadow-violet-500/20">
                        <MessageSquare className="w-5 h-5 text-[#C6FF33]" />
                    </div>
                    <div>
                        <div className="flex items-center gap-2">
                            <h3 className="font-extrabold text-xs text-[#0C0A14] dark:text-white uppercase tracking-wider">
                                ИИ-Методист Edusfera
                            </h3>
                            <span className="relative flex h-2 w-2">
                                <span className="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                <span className="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                            </span>
                        </div>

                        {/* Current Persona Subtitle */}
                        <p className="text-[11px] text-slate-500 dark:text-slate-400 font-medium flex items-center gap-1.5 mt-0.5">
                            <span>Роль:</span>
                            <span className="font-bold text-[#7D39EB] dark:text-violet-400">
                                {PERSONAS.find(p => p.id === persona)?.label}
                            </span>
                        </p>
                    </div>
                </div>

                <div className="flex items-center gap-2 ml-auto">
                    {/* Subject Selector Pill with Dropdown */}
                    <div className="relative" ref={subjectMenuRef}>
                        <button
                            type="button"
                            onClick={() => setShowSubjectMenu(!showSubjectMenu)}
                            className="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl border border-violet-200 dark:border-violet-900/60 bg-white dark:bg-slate-800 text-xs font-bold text-slate-800 dark:text-slate-200 hover:border-[#7D39EB] transition-all cursor-pointer shadow-2xs"
                            title="Сменить предмет диалога"
                        >
                            <span className="text-[10px] text-violet-600 dark:text-violet-400">Предмет:</span>
                            <span>{selectedSubject}</span>
                            <ChevronDown className={`w-3 h-3 text-slate-400 transition-transform ${showSubjectMenu ? 'rotate-180' : ''}`} />
                        </button>

                        {showSubjectMenu && (
                            <div className="absolute right-0 top-full mt-1.5 w-56 p-1.5 rounded-2xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 shadow-xl z-50">
                                <div className="text-[10px] font-bold text-slate-400 uppercase tracking-wider px-2 py-1">
                                    Выберите предмет
                                </div>
                                <div className="max-h-60 overflow-y-auto space-y-0.5 no-scrollbar">
                                    {SUBJECTS_LIST.map((subj) => (
                                        <button
                                            key={subj}
                                            type="button"
                                            onClick={() => {
                                                setSelectedSubject(subj);
                                                setShowSubjectMenu(false);
                                            }}
                                            className={`w-full flex items-center justify-between px-2.5 py-1.5 rounded-xl text-xs font-semibold text-left transition-colors cursor-pointer ${
                                                selectedSubject === subj
                                                    ? 'bg-violet-50 dark:bg-violet-950/60 text-[#7D39EB] dark:text-violet-300 font-bold'
                                                    : 'text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700/60'
                                            }`}
                                        >
                                            <span>{subj}</span>
                                            {selectedSubject === subj && <Check className="w-3.5 h-3.5 text-[#7D39EB]" />}
                                        </button>
                                    ))}
                                </div>
                            </div>
                        )}
                    </div>

                    {/* Persona Settings Toggler */}
                    <button
                        type="button"
                        onClick={() => setShowSettings(!showSettings)}
                        className={`inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl border text-xs font-bold transition-all cursor-pointer ${
                            showSettings
                                ? 'bg-violet-100 dark:bg-violet-900/50 text-[#7D39EB] dark:text-violet-300 border-[#7D39EB]'
                                : 'border-[#ECEEF1] dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-50'
                        }`}
                        title="Настройки роли и тональности"
                    >
                        <SlidersHorizontal className="w-3.5 h-3.5" />
                        <span className="hidden sm:inline">Параметры</span>
                        <ChevronDown className={`w-3 h-3 transition-transform ${showSettings ? 'rotate-180' : ''}`} />
                    </button>

                    {/* Catalog Button */}
                    <button
                        type="button"
                        onClick={onOpenLibrary}
                        className="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-violet-50 dark:bg-violet-950/40 text-[#7D39EB] dark:text-violet-400 hover:bg-violet-100 dark:hover:bg-violet-900/60 text-xs font-extrabold transition-colors cursor-pointer"
                        title="Открыть готовые промпты"
                    >
                        <Sparkles className="w-3.5 h-3.5 text-violet-600 dark:text-violet-400" />
                        <span className="hidden sm:inline">Каталог</span>
                    </button>

                    {/* Clear Chat */}
                    {messages.length > 0 && (
                        <button
                            type="button"
                            onClick={clearChat}
                            className="p-1.5 rounded-xl border border-[#ECEEF1] dark:border-slate-700 text-slate-400 hover:text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-950/40 transition-colors cursor-pointer"
                            title="Очистить диалог по этому предмету"
                        >
                            <Trash2 className="w-3.5 h-3.5" />
                        </button>
                    )}
                </div>
            </div>

            {/* ═══ 2. EXPANDABLE SETTINGS DRAWER ═══ */}
            {showSettings && (
                <div className="p-4 border-b border-[#ECEEF1] dark:border-slate-800 bg-[#FAF8FF]/80 dark:bg-slate-800/80 space-y-3 shrink-0">
                    <div>
                        <label className="block text-[10.5px] font-extrabold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">
                            Педагогическая роль (Персона)
                        </label>
                        <div className="flex flex-wrap gap-1.5">
                            {PERSONAS.map((p) => (
                                <button
                                    key={p.id}
                                    type="button"
                                    onClick={() => setPersona(p.id)}
                                    className={`px-3 py-1.5 rounded-xl text-xs font-bold transition-all cursor-pointer ${
                                        persona === p.id
                                            ? 'bg-[#7D39EB] text-white shadow-xs'
                                            : 'bg-white dark:bg-slate-700/80 text-slate-700 dark:text-slate-300 border border-[#ECEEF1] dark:border-slate-600 hover:bg-slate-50'
                                    }`}
                                >
                                    {p.label}
                                </button>
                            ))}
                        </div>
                    </div>

                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-1">
                        <div>
                            <label className="block text-[10.5px] font-extrabold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1">
                                Тональность ответа
                            </label>
                            <div className="flex gap-1">
                                {TONES.map((t) => (
                                    <button
                                        key={t.id}
                                        type="button"
                                        onClick={() => setTone(t.id)}
                                        className={`flex-1 py-1.5 px-2 rounded-xl text-[11px] font-bold transition-all cursor-pointer ${
                                            tone === t.id
                                                ? 'bg-[#0C0A14] text-white dark:bg-white dark:text-slate-900 shadow-2xs'
                                                : 'bg-white dark:bg-slate-700/60 text-slate-600 dark:text-slate-300 border border-[#ECEEF1] dark:border-slate-600 hover:bg-slate-50'
                                        }`}
                                    >
                                        {t.label}
                                    </button>
                                ))}
                            </div>
                        </div>

                        <div>
                            <label className="block text-[10.5px] font-extrabold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1">
                                Объем ответа
                            </label>
                            <div className="flex gap-1">
                                {LENGTHS.map((l) => (
                                    <button
                                        key={l.id}
                                        type="button"
                                        onClick={() => setLength(l.id)}
                                        className={`flex-1 py-1.5 px-2 rounded-xl text-[11px] font-bold transition-all cursor-pointer ${
                                            length === l.id
                                                ? 'bg-[#0C0A14] text-white dark:bg-white dark:text-slate-900 shadow-2xs'
                                                : 'bg-white dark:bg-slate-700/60 text-slate-600 dark:text-slate-300 border border-[#ECEEF1] dark:border-slate-600 hover:bg-slate-50'
                                        }`}
                                    >
                                        {l.label}
                                    </button>
                                ))}
                            </div>
                        </div>
                    </div>
                </div>
            )}

            {/* ═══ 3. CHAT MESSAGES CANVAS ═══ */}
            <div className="flex-1 overflow-y-auto p-4 sm:p-6 space-y-4 bg-[#FAF8FF]/40 dark:bg-slate-900/50">
                {messages.length === 0 ? (
                    <div className="m-auto max-w-2xl text-center py-6 sm:py-8 space-y-6">
                        {/* Animated SVG Mascot */}
                        <div className="relative w-20 h-20 mx-auto flex items-center justify-center">
                            <svg className="absolute inset-0 w-full h-full ed-animate-spin-slow" viewBox="0 0 100 100">
                                <circle cx="50" cy="50" r="44" fill="none" stroke="#7D39EB" strokeWidth="1.5" strokeDasharray="5 7" strokeOpacity="0.4" />
                                <circle cx="94" cy="50" r="4.5" fill="#C6FF33" />
                            </svg>
                            <div className="ed-animate-float w-14 h-14 rounded-2xl bg-gradient-to-tr from-[#7D39EB] to-[#632cd6] text-white flex items-center justify-center shadow-lg shadow-violet-500/25">
                                <svg width="30" height="30" viewBox="0 0 64 64" fill="none">
                                    <path d="M32 10L54 32L32 54L10 32L32 10Z" stroke="#C6FF33" strokeWidth="6" strokeLinejoin="round" />
                                    <path d="M32 22L42 32L32 42L22 32L32 22Z" fill="#C6FF33" />
                                </svg>
                            </div>
                        </div>

                        {/* Title & Pedagogical Description */}
                        <div className="space-y-2">
                            <div className="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-violet-100/80 dark:bg-violet-950/80 text-[#7D39EB] dark:text-violet-300 text-xs font-bold uppercase tracking-wider">
                                <GraduationCap className="w-3.5 h-3.5" />
                                <span>Направление: {selectedSubject}</span>
                            </div>
                            <h4 className="font-extrabold text-base sm:text-lg text-slate-900 dark:text-white tracking-tight">
                                Интеллектуальный методический диалог
                            </h4>
                            <p className="text-xs sm:text-[13px] text-slate-500 dark:text-slate-400 font-medium max-w-lg mx-auto leading-relaxed">
                                Задайте любой методический вопрос, сформируйте конспект или выберите готовый сценарий для быстрого старта:
                            </p>
                        </div>

                        {/* Starter Prompt Cards (Redesigned 2-column grid with icons) */}
                        <div className="space-y-2.5 text-left">
                            <div className="text-[11px] font-bold text-slate-400 uppercase tracking-wider px-1 flex items-center justify-between">
                                <span>Быстрые сценарии по предмету</span>
                                <span className="text-[10.5px] text-[#7D39EB] dark:text-violet-400 font-bold hover:underline cursor-pointer flex items-center gap-1" onClick={onOpenLibrary}>
                                    <Sparkles className="w-3 h-3" />
                                    <span>Открыть каталог промптов →</span>
                                </span>
                            </div>
                            <div className="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                                {currentStarterPrompts.map((promptText, i) => (
                                    <button
                                        key={i}
                                        type="button"
                                        onClick={() => handleSendMessage(promptText)}
                                        className="p-3.5 rounded-2xl bg-white dark:bg-slate-800/90 hover:bg-[#FAF8FF] dark:hover:bg-violet-950/40 border border-slate-200/80 dark:border-slate-700/80 hover:border-[#7D39EB]/50 dark:hover:border-violet-600/50 text-left transition-all shadow-2xs hover:shadow-sm hover:-translate-y-0.5 flex items-start gap-3 group cursor-pointer"
                                    >
                                        <div className="w-7 h-7 rounded-xl bg-violet-50 dark:bg-violet-950/60 text-[#7D39EB] dark:text-violet-300 flex items-center justify-center shrink-0 mt-0.5 group-hover:scale-110 group-hover:bg-[#7D39EB] group-hover:text-white transition-all">
                                            <Sparkles className="w-3.5 h-3.5" />
                                        </div>
                                        <div className="flex-1 min-w-0">
                                            <span className="text-xs font-semibold text-slate-800 dark:text-slate-200 group-hover:text-[#7D39EB] dark:group-hover:text-violet-300 transition-colors line-clamp-2 leading-snug">
                                                {promptText}
                                            </span>
                                        </div>
                                        <ArrowUpRight className="w-4 h-4 text-slate-300 dark:text-slate-600 group-hover:text-[#7D39EB] dark:group-hover:text-violet-400 transition-all shrink-0 mt-0.5 group-hover:translate-x-0.5 group-hover:-translate-y-0.5" />
                                    </button>
                                ))}
                            </div>
                        </div>
                    </div>
                ) : (
                    messages.map((msg, idx) => {
                        const isUser = msg.role === 'user';
                        return (
                            <div
                                key={msg.id || idx}
                                className={`flex gap-3 ${isUser ? 'justify-end' : 'justify-start'}`}
                            >
                                {!isUser && (
                                    <div className="w-8 h-8 rounded-xl bg-gradient-to-tr from-[#7D39EB] to-[#632cd6] text-white flex items-center justify-center shrink-0 shadow-xs mt-1">
                                        <svg width="18" height="18" viewBox="0 0 64 64" fill="none">
                                            <path d="M32 10L54 32L32 54L10 32L32 10Z" stroke="#C6FF33" strokeWidth="6" strokeLinejoin="round" />
                                            <path d="M32 22L42 32L32 42L22 32L32 22Z" fill="#C6FF33" />
                                        </svg>
                                    </div>
                                )}

                                <div className={`relative group max-w-[92%] sm:max-w-[84%] rounded-2xl p-4 sm:p-5 text-xs leading-relaxed transition-all shadow-2xs ${
                                    isUser
                                        ? 'bg-gradient-to-tr from-[#7D39EB] to-[#6827db] text-white rounded-tr-xs'
                                        : 'bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 border border-[#ECEEF1] dark:border-slate-700/80 rounded-tl-xs'
                                }`}>
                                    {/* Author & Timestamp */}
                                    <div className="flex items-center justify-between gap-4 mb-2 pb-1.5 border-b border-black/5 dark:border-white/10">
                                        <div className="flex items-center gap-2">
                                            <span className={`font-extrabold text-[11px] ${isUser ? 'text-violet-100' : 'text-[#7D39EB] dark:text-violet-400'}`}>
                                                {isUser ? (user?.name || 'Вы') : 'Edusfera AI'}
                                            </span>
                                            {!isUser && (
                                                <span className="text-[10px] font-bold px-1.5 py-0.2 rounded bg-violet-50 dark:bg-violet-950/80 text-violet-600 dark:text-violet-300">
                                                    {selectedSubject}
                                                </span>
                                            )}
                                        </div>
                                        <span className={`text-[10px] ${isUser ? 'text-violet-200' : 'text-slate-400'}`}>
                                            {msg.timestamp}
                                        </span>
                                    </div>

                                    {/* Content */}
                                    {isUser ? (
                                        <div className="whitespace-pre-wrap font-sans text-[13px] leading-relaxed">
                                            {msg.content}
                                        </div>
                                    ) : (
                                        <div
                                            className="ai-content-canvas text-[13.5px] leading-relaxed text-slate-800 dark:text-slate-100"
                                            dangerouslySetInnerHTML={{ __html: formatAiMarkdown(msg.content) }}
                                        />
                                    )}

                                    {/* Action Bar */}
                                    {!isUser && (
                                        <div className="pt-2 mt-3 border-t border-slate-100 dark:border-slate-700/60 flex items-center justify-between text-[11px] text-slate-400">
                                            <span className="text-[10px]">Edusfera AI Engine</span>
                                            <button
                                                type="button"
                                                onClick={() => handleCopy(msg.content, idx)}
                                                className="inline-flex items-center gap-1 hover:text-[#7D39EB] dark:hover:text-violet-400 transition-colors cursor-pointer"
                                                title="Скопировать ответ"
                                            >
                                                {copiedIndex === idx ? (
                                                    <>
                                                        <Check className="w-3 h-3 text-emerald-500" />
                                                        <span className="text-emerald-500 font-semibold">Скопировано!</span>
                                                    </>
                                                ) : (
                                                    <>
                                                        <Copy className="w-3 h-3" />
                                                        <span>Копировать ответ</span>
                                                    </>
                                                )}
                                            </button>
                                        </div>
                                    )}
                                </div>

                                {isUser && (
                                    <div className="w-8 h-8 rounded-xl bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-200 flex items-center justify-center shrink-0 text-xs font-bold mt-1">
                                        <User className="w-4 h-4" />
                                    </div>
                                )}
                            </div>
                        );
                    })
                )}

                {/* Loading indicator */}
                {isLoading && (
                    <div className="flex gap-3 justify-start">
                        <div className="w-8 h-8 rounded-xl bg-[#7D39EB] text-white flex items-center justify-center shrink-0 shadow-xs animate-pulse">
                            <svg width="18" height="18" viewBox="0 0 64 64" fill="none">
                                <path d="M32 10L54 32L32 54L10 32L32 10Z" stroke="#C6FF33" strokeWidth="6" strokeLinejoin="round" />
                                <path d="M32 22L42 32L32 42L22 32L32 22Z" fill="#C6FF33" />
                            </svg>
                        </div>
                        <div className="p-4 rounded-2xl rounded-tl-xs bg-white dark:bg-slate-800 border border-[#ECEEF1] dark:border-slate-700/80 shadow-2xs flex items-center gap-2">
                            <span className="relative flex h-2 w-2">
                                <span className="animate-ping absolute inline-flex h-full w-full rounded-full bg-violet-400 opacity-75"></span>
                                <span className="relative inline-flex rounded-full h-2 w-2 bg-[#7D39EB]"></span>
                            </span>
                            <span className="text-xs font-medium text-slate-500 dark:text-slate-400">
                                Edusfera AI формирует методический ответ...
                            </span>
                        </div>
                    </div>
                )}

                <div ref={messagesEndRef} />
            </div>

            {/* ═══ 4. INPUT AREA ═══ */}
            <div className="p-3 sm:p-4 border-t border-[#ECEEF1] dark:border-slate-800 bg-white/95 dark:bg-slate-900/95 backdrop-blur-md shrink-0">
                <div className="relative rounded-2xl border border-slate-200/90 dark:border-slate-700/80 bg-slate-50/70 dark:bg-slate-800/60 focus-within:bg-white dark:focus-within:bg-slate-800 focus-within:border-[#7D39EB] focus-within:ring-2 focus-within:ring-[#7D39EB]/15 transition-all p-2.5 shadow-2xs">
                    <textarea
                        ref={textareaRef}
                        rows={1}
                        value={input}
                        onChange={(e) => setInput(e.target.value)}
                        onKeyDown={handleKeyDown}
                        placeholder={`Задайте вопрос ИИ-Методисту по направлению «${selectedSubject}»...`}
                        className="ed-methodist-textarea w-full bg-transparent text-xs sm:text-[13px] font-medium text-slate-900 dark:text-slate-100 placeholder-slate-400 outline-none resize-none px-2 py-1 max-h-40 leading-relaxed !border-none !ring-0 !shadow-none"
                    />

                    <div className="flex items-center justify-between pt-2 px-1">
                        <div className="flex items-center gap-2">
                            <button
                                type="button"
                                onClick={onOpenLibrary}
                                className="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-violet-100/70 hover:bg-violet-100 dark:bg-violet-950/60 dark:hover:bg-violet-900/60 text-[#7D39EB] dark:text-violet-300 text-xs font-bold transition-all shadow-2xs cursor-pointer"
                                title="Открыть библиотеку готовых педагогических сценариев"
                            >
                                <Sparkles className="w-3.5 h-3.5 text-[#7D39EB] dark:text-violet-300" />
                                <span>Каталог промптов</span>
                            </button>
                            <span className="hidden sm:inline text-[10.5px] text-slate-400 font-medium">
                                · Нажмите Enter ↵ для отправки
                            </span>
                        </div>

                        <button
                            type="button"
                            onClick={() => handleSendMessage()}
                            disabled={!input.trim() || isLoading}
                            className={`inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-bold transition-all cursor-pointer shadow-xs ${
                                input.trim() && !isLoading
                                    ? 'bg-gradient-to-r from-[#7D39EB] to-[#632cd6] hover:from-[#6b2fd4] hover:to-[#5524bc] text-white shadow-violet-500/25 active:scale-95'
                                    : 'bg-slate-200 dark:bg-slate-700 text-slate-400 cursor-not-allowed shadow-none'
                            }`}
                        >
                            <span>Отправить</span>
                            <Send className="w-3.5 h-3.5" />
                        </button>
                    </div>
                </div>
            </div>
        </div>
    );
}
