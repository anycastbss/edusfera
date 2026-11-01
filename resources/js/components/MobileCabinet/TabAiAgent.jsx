import React, { useState, useEffect, useRef } from 'react';
import { motion, AnimatePresence } from 'motion/react';
import axios from 'axios';
import { 
  Sparkles, 
  Send, 
  Bot, 
  User as UserIcon, 
  Check, 
  Copy, 
  Target, 
  Lightbulb, 
  GraduationCap, 
  AlertTriangle,
  ChevronDown,
  BookOpen,
  RotateCcw
} from 'lucide-react';

// Понятные роли для методического ассистента
const TUTOR_PERSONAS = [
  { 
    id: 'methodologist', 
    shortLabel: 'К уроку',
    label: 'Помощник к уроку', 
    icon: GraduationCap, 
    desc: 'План занятия, объяснение тем и наглядные примеры' 
  },
  { 
    id: 'rikz_expert', 
    shortLabel: 'ЦТ 2026',
    label: 'Подготовка к экзаменам', 
    icon: Target, 
    desc: 'Тесты, спецификация ЦТ/ЦЭ 2026 и разбор заданий' 
  },
  { 
    id: 'simple_analogy', 
    shortLabel: 'На пальцах',
    label: 'Объяснить на пальцах', 
    icon: Lightbulb, 
    desc: 'Жизненные метафоры и простое понятное объяснение' 
  },
];

const STUDENT_PERSONAS = [
  { id: 'default', shortLabel: 'Помощник', label: 'ИИ-Помощник', icon: Bot, desc: 'Помощь с пониманием тем и поддержка' },
  { id: 'simple_analogy', shortLabel: 'На пальцах', label: 'Объясни на пальцах', icon: Lightbulb, desc: 'Простое понятное объяснение' },
  { id: 'rikz_expert', shortLabel: 'ЦТ / Экзамен', label: 'Подготовка к ЦТ', icon: Target, desc: 'Разбор заданий и формул' },
];

const SUBJECTS = [
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
];

const QUICK_PROMPTS_TUTOR = [
  '📋 План урока на 45 мин',
  '✍️ 3 проверочных задания',
  '💡 Объяснить сложную тему',
  '❓ Экспресс-тест на 10 мин',
];

const QUICK_PROMPTS_STUDENT = [
  '💡 Объясни простыми словами',
  '📝 Решить задание по шагам',
  '⚠️ Ловушки на ЦТ по теме',
  '❓ 3 проверочных вопроса',
];

/**
 * Парсер инлайн-разметки: заменяет спецсимволы Markdown на React-компоненты
 */
function parseInlineMarkdown(text) {
  if (!text) return null;

  const tokens = [];
  let remaining = text;
  let keyIndex = 0;

  while (remaining.length > 0) {
    // 1. Жирный шрифт: **текст** или __текст__
    const boldMatch = remaining.match(/^(\*\*|__)(.+?)\1/);
    if (boldMatch) {
      tokens.push(
        <strong key={`b-${keyIndex++}`} className="font-extrabold text-slate-900 dark:text-white">
          {boldMatch[2]}
        </strong>
      );
      remaining = remaining.slice(boldMatch[0].length);
      continue;
    }

    // 2. Инлайн-код: `код`
    const codeMatch = remaining.match(/^`([^`]+)`/);
    if (codeMatch) {
      tokens.push(
        <code 
          key={`c-${keyIndex++}`} 
          className="px-1.5 py-0.5 rounded-md bg-violet-500/10 text-violet-700 dark:text-violet-300 font-mono text-[13px] font-semibold"
        >
          {codeMatch[1]}
        </code>
      );
      remaining = remaining.slice(codeMatch[0].length);
      continue;
    }

    // 3. Курсив: *текст* или _текст_
    const italicMatch = remaining.match(/^(\*|_)(.+?)\1/);
    if (italicMatch) {
      tokens.push(
        <em key={`i-${keyIndex++}`} className="italic font-medium text-slate-800 dark:text-slate-200">
          {italicMatch[2]}
        </em>
      );
      remaining = remaining.slice(italicMatch[0].length);
      continue;
    }

    // Обычный текст до следующего спецсимвола
    const nextSpecialIndex = remaining.search(/[\*_`]/);
    if (nextSpecialIndex === -1) {
      tokens.push(remaining);
      break;
    } else if (nextSpecialIndex > 0) {
      tokens.push(remaining.slice(0, nextSpecialIndex));
      remaining = remaining.slice(nextSpecialIndex);
    } else {
      tokens.push(remaining[0]);
      remaining = remaining.slice(1);
    }
  }

  return tokens;
}

/**
 * Блочный рендерер: выводит списки, заголовки, врезки без артефактов
 */
function MarkdownMessageView({ content }) {
  if (!content) return null;

  const rawLines = content.split('\n');
  const blocks = [];
  let currentList = null;

  rawLines.forEach((line, index) => {
    const trimmed = line.trim();

    if (!trimmed) {
      if (currentList) {
        blocks.push(currentList);
        currentList = null;
      }
      return;
    }

    // 1. Заголовки: #, ##, ###
    if (/^#{1,4}\s+/.test(trimmed)) {
      if (currentList) {
        blocks.push(currentList);
        currentList = null;
      }
      const titleText = trimmed.replace(/^#{1,4}\s+/, '');
      blocks.push(
        <h4 
          key={`h-${index}`} 
          className="text-[15px] font-extrabold text-slate-900 dark:text-white pt-2 pb-0.5 tracking-tight"
        >
          {parseInlineMarkdown(titleText)}
        </h4>
      );
      return;
    }

    // 2. Врезка [!TIP]
    if (trimmed.startsWith('> [!TIP]')) {
      if (currentList) {
        blocks.push(currentList);
        currentList = null;
      }
      const tipText = trimmed.replace('> [!TIP]', '').trim();
      blocks.push(
        <div 
          key={`tip-${index}`} 
          className="my-2 p-3 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-950 dark:text-emerald-200 flex items-start gap-2.5"
        >
          <Lightbulb className="w-4 h-4 text-emerald-600 shrink-0 mt-0.5" />
          <div className="text-xs font-medium leading-relaxed">
            <strong className="block text-[10px] uppercase font-extrabold text-emerald-700 dark:text-emerald-400 mb-0.5">
              Совет методиста
            </strong>
            {parseInlineMarkdown(tipText)}
          </div>
        </div>
      );
      return;
    }

    // 3. Врезка [!WARNING]
    if (trimmed.startsWith('> [!WARNING]')) {
      if (currentList) {
        blocks.push(currentList);
        currentList = null;
      }
      const warnText = trimmed.replace('> [!WARNING]', '').trim();
      blocks.push(
        <div 
          key={`warn-${index}`} 
          className="my-2 p-3 rounded-2xl bg-amber-500/10 border border-amber-500/25 text-amber-950 dark:text-amber-200 flex items-start gap-2.5"
        >
          <AlertTriangle className="w-4 h-4 text-amber-600 shrink-0 mt-0.5" />
          <div className="text-xs font-medium leading-relaxed">
            <strong className="block text-[10px] uppercase font-extrabold text-amber-700 dark:text-amber-400 mb-0.5">
              Важно обратить внимание
            </strong>
            {parseInlineMarkdown(warnText)}
          </div>
        </div>
      );
      return;
    }

    // 4. Обычная цитата
    if (trimmed.startsWith('>')) {
      if (currentList) {
        blocks.push(currentList);
        currentList = null;
      }
      const quoteText = trimmed.replace(/^>\s*/, '');
      blocks.push(
        <div 
          key={`quote-${index}`} 
          className="my-1.5 pl-3 py-0.5 border-l-2 border-violet-500 text-slate-700 dark:text-slate-300 italic text-xs leading-relaxed"
        >
          {parseInlineMarkdown(quoteText)}
        </div>
      );
      return;
    }

    // 5. Маркированный список: - , * , •
    const bulletMatch = trimmed.match(/^[-*•]\s+(.+)/);
    if (bulletMatch) {
      if (!currentList || currentList.type !== 'ul') {
        if (currentList) blocks.push(currentList);
        currentList = { type: 'ul', key: `ul-${index}`, items: [] };
      }
      currentList.items.push(bulletMatch[1]);
      return;
    }

    // 6. Нумерованный список: 1. , 2. 
    const numberMatch = trimmed.match(/^(\d+)[.)]\s+(.+)/);
    if (numberMatch) {
      if (!currentList || currentList.type !== 'ol') {
        if (currentList) blocks.push(currentList);
        currentList = { type: 'ol', key: `ol-${index}`, items: [] };
      }
      currentList.items.push({ num: numberMatch[1], text: numberMatch[2] });
      return;
    }

    // 7. Обычный абзац
    if (currentList) {
      blocks.push(currentList);
      currentList = null;
    }

    blocks.push(
      <p 
        key={`p-${index}`} 
        className="text-[14px] leading-relaxed text-slate-800 dark:text-slate-200 font-normal my-1"
      >
        {parseInlineMarkdown(trimmed)}
      </p>
    );
  });

  if (currentList) {
    blocks.push(currentList);
  }

  return (
    <div className="space-y-1.5">
      {blocks.map((block) => {
        if (block.type === 'ul') {
          return (
            <ul key={block.key} className="space-y-1.5 my-1.5 pl-0.5">
              {block.items.map((item, idx) => (
                <li key={idx} className="flex items-start gap-2 text-[14px] leading-snug text-slate-800 dark:text-slate-200">
                  <span className="w-1.5 h-1.5 rounded-full bg-violet-600 dark:bg-[#C6FF33] mt-2 shrink-0" />
                  <span>{parseInlineMarkdown(item)}</span>
                </li>
              ))}
            </ul>
          );
        }

        if (block.type === 'ol') {
          return (
            <ol key={block.key} className="space-y-1.5 my-1.5 pl-0.5">
              {block.items.map((item, idx) => (
                <li key={idx} className="flex items-start gap-2 text-[14px] leading-snug text-slate-800 dark:text-slate-200">
                  <span className="w-4 h-4 rounded-md bg-violet-500/10 dark:bg-white/10 text-violet-700 dark:text-violet-300 font-bold text-[10px] flex items-center justify-center shrink-0 mt-0.5">
                    {item.num}
                  </span>
                  <span>{parseInlineMarkdown(item.text)}</span>
                </li>
              ))}
            </ol>
          );
        }

        return block;
      })}
    </div>
  );
}

export default function TabAiAgent({ 
  user, 
  tutorStudents = [], 
  isStudent = false, 
  initialPrompt = '',
  onAssignHomework = null 
}) {
  const personas = isStudent ? STUDENT_PERSONAS : TUTOR_PERSONAS;
  const quickPrompts = isStudent ? QUICK_PROMPTS_STUDENT : QUICK_PROMPTS_TUTOR;

  const [activePersona, setActivePersona] = useState(personas[0].id);
  const [selectedSubject, setSelectedSubject] = useState('Математика');
  const [selectedStudentId, setSelectedStudentId] = useState(tutorStudents[0]?.id || '');
  const [inputMessage, setInputMessage] = useState(initialPrompt || '');
  const [isLoading, setIsLoading] = useState(false);
  const [copiedIndex, setCopiedIndex] = useState(null);
  const [actionSuccessMessage, setActionSuccessMessage] = useState(null);

  const getInitialMessage = () => ({
    role: 'assistant',
    content: isStudent 
      ? `Здравствуйте! 👋 Я ваш персональный ИИ-Тьютор Edusfera.\n\nЗадайте любой вопрос по школьной программе или экзаменам. Разберём материал шаг за шагом, простым языком!`
      : `Здравствуйте, ${user?.name || 'коллега'}! 🎓 Я ваш методический помощник Edusfera AI.\n\nПомогу подготовить структурированный план урока, составить проверочные задания или объяснить сложную тему простыми примерами. Напишите ваш вопрос ниже или нажмите одну из быстрых подсказок!`,
  });

  const [messages, setMessages] = useState(() => [getInitialMessage()]);

  const messagesEndRef = useRef(null);

  const scrollToBottom = () => {
    messagesEndRef.current?.scrollIntoView({ behavior: 'smooth' });
  };

  useEffect(() => {
    scrollToBottom();
  }, [messages, isLoading]);

  useEffect(() => {
    if (initialPrompt) {
      setInputMessage(initialPrompt);
    }
  }, [initialPrompt]);

  const handleSendMessage = async (customPrompt = null) => {
    const textToSend = customPrompt || inputMessage.trim();
    if (!textToSend || isLoading) return;

    const userMessage = { role: 'user', content: textToSend };
    const newHistory = [...messages, userMessage];
    setMessages(newHistory);
    setInputMessage('');
    setIsLoading(true);

    try {
      const response = await axios.post('/admin/ai-copilot/chat', {
        prompt: textToSend,
        persona: activePersona,
        subject: selectedSubject,
        student_id: selectedStudentId ? parseInt(selectedStudentId, 10) : null,
        history: messages.slice(-8),
      });

      if (response.data?.reply) {
        setMessages((prev) => [...prev, { role: 'assistant', content: response.data.reply }]);
      } else {
        throw new Error('Пустой ответ от ИИ');
      }
    } catch (err) {
      console.error('[AI Agent Error]', err);
      let errorText = 'Произошла небольшая задержка связи. Пожалуйста, попробуйте отправить ещё раз.';
      if (err.response?.status === 429) {
        errorText = '⏳ Слишком много запросов за короткое время. Подождите несколько секунд и попробуйте снова.';
      }
      setMessages((prev) => [
        ...prev, 
        { 
          role: 'assistant', 
          content: errorText
        }
      ]);
    } finally {
      setIsLoading(false);
    }
  };

  const handleResetChat = () => {
    setMessages([getInitialMessage()]);
  };

  const handleCopy = (text, idx) => {
    const plainText = text
      .replace(/\*\*(.*?)\*\*/g, '$1')
      .replace(/\*(.*?)\*/g, '$1')
      .replace(/`(.*?)`/g, '$1')
      .replace(/^#{1,4}\s+/gm, '')
      .replace(/^>\s*/gm, '');

    navigator.clipboard.writeText(plainText);
    setCopiedIndex(idx);
    setTimeout(() => setCopiedIndex(null), 2500);
  };

  const handleCreateHomeworkFromReply = async (replyText) => {
    if (isStudent || !replyText) return;
    setIsLoading(true);
    try {
      const cleanInstructions = replyText
        .replace(/\*\*(.*?)\*\*/g, '$1')
        .replace(/\*(.*?)\*/g, '$1')
        .replace(/`(.*?)`/g, '$1')
        .slice(0, 1500);

      const response = await axios.post('/admin/ai-copilot/action', {
        action: 'homework.create',
        payload: {
          title: `Задание по теме: ${selectedSubject}`,
          instructions: cleanInstructions,
          student_id: selectedStudentId ? parseInt(selectedStudentId, 10) : null,
          due_days: 3,
        }
      });

      if (response.data?.success) {
        setActionSuccessMessage(response.data.message || 'Домашнее задание успешно назначено!');
        setTimeout(() => setActionSuccessMessage(null), 4000);
        if (onAssignHomework) onAssignHomework();
      } else {
        alert(response.data?.error || 'Не удалось назначить задание');
      }
    } catch (err) {
      console.error(err);
      alert('Ошибка при назначении задания');
    } finally {
      setIsLoading(false);
    }
  };

  const activePersonaObj = personas.find((p) => p.id === activePersona) || personas[0];

  return (
    <div className="flex flex-col h-[calc(100dvh-140px)] sm:h-[calc(100vh-140px)] -mx-4 -mt-4 bg-[#F8FAFC] dark:bg-[#0A0A0C] overflow-hidden select-none sm:select-auto">
      
      {/* ═══ 1. КОМПАКТНАЯ ВЕРХНЯЯ ПАНЕЛЬ УПРАВЛЕНИЯ ═══ */}
      <div className="shrink-0 px-3.5 pt-2 pb-2.5 bg-white/95 dark:bg-[#121118]/95 backdrop-blur-xl border-b border-slate-200/80 dark:border-white/10 shadow-sm z-10">
        
        {/* Переключатель режимов (Сегментированные таблетки) */}
        <div className="flex items-center gap-1 p-1 rounded-2xl bg-slate-100 dark:bg-white/5 border border-slate-200/60 dark:border-white/5">
          {personas.map((persona) => {
            const Icon = persona.icon;
            const isActive = activePersona === persona.id;

            return (
              <button
                key={persona.id}
                type="button"
                onClick={() => setActivePersona(persona.id)}
                className={`flex-1 py-1.5 px-2 rounded-xl text-xs font-bold transition-all flex items-center justify-center gap-1.5 relative ${
                  isActive
                    ? 'bg-[#7D39EB] text-white shadow-sm shadow-violet-500/20'
                    : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'
                }`}
              >
                <Icon className={`w-3.5 h-3.5 shrink-0 ${isActive ? 'text-[#C6FF33]' : ''}`} />
                <span className="truncate">{persona.shortLabel}</span>
              </button>
            );
          })}
        </div>

        {/* Быстрые фильтры: Предмет + Ученик + Сброс */}
        <div className="flex items-center justify-between gap-2 mt-2 px-0.5">
          <div className="flex items-center gap-1.5 flex-wrap min-w-0">
            {/* Предмет */}
            <div className="relative inline-flex items-center">
              <select
                value={selectedSubject}
                onChange={(e) => setSelectedSubject(e.target.value)}
                className="appearance-none text-[11px] font-bold pl-2.5 pr-6 py-1 rounded-xl border border-slate-200 dark:border-white/10 bg-white dark:bg-[#1A1924] text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-1 focus:ring-[#7D39EB] shadow-xs"
              >
                {SUBJECTS.map((s) => (
                  <option key={s} value={s}>{s}</option>
                ))}
              </select>
              <ChevronDown className="w-3 h-3 text-slate-400 absolute right-2 pointer-events-none" />
            </div>

            {/* Выбор ученика (для репетитора) */}
            {!isStudent && tutorStudents.length > 0 && (
              <div className="relative inline-flex items-center">
                <select
                  value={selectedStudentId}
                  onChange={(e) => setSelectedStudentId(e.target.value)}
                  className="appearance-none text-[11px] font-bold pl-2.5 pr-6 py-1 rounded-xl border border-slate-200 dark:border-white/10 bg-white dark:bg-[#1A1924] text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-1 focus:ring-[#7D39EB] shadow-xs max-w-[130px] truncate"
                >
                  <option value="">Все ученики</option>
                  {tutorStudents.map((st) => (
                    <option key={st.id} value={st.id}>{st.name}</option>
                  ))}
                </select>
                <ChevronDown className="w-3 h-3 text-slate-400 absolute right-2 pointer-events-none" />
              </div>
            )}
          </div>

          {/* Кнопка «Новый диалог» */}
          <button
            type="button"
            onClick={handleResetChat}
            className="text-[11px] font-bold px-2 py-1 rounded-xl text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-white transition flex items-center gap-1 shrink-0"
            title="Очистить и начать новый диалог"
          >
            <RotateCcw className="w-3 h-3" />
            <span className="hidden sm:inline">Сброс</span>
          </button>
        </div>
      </div>

      {/* Оповещение об успешном действии (напр. назначение ДЗ) */}
      <AnimatePresence>
        {actionSuccessMessage && (
          <motion.div
            initial={{ opacity: 0, height: 0 }}
            animate={{ opacity: 1, height: 'auto' }}
            exit={{ opacity: 0, height: 0 }}
            className="px-4 py-2 bg-emerald-500/15 border-b border-emerald-500/30 text-emerald-900 dark:text-emerald-200 text-xs font-bold flex items-center gap-2 shrink-0"
          >
            <Check className="w-4 h-4 text-emerald-600 shrink-0" />
            <span className="truncate">{actionSuccessMessage}</span>
          </motion.div>
        )}
      </AnimatePresence>

      {/* ═══ 2. ЛЕНТА СООБЩЕНИЙ (ПЛАВНЫЙ СКРОЛЛ) ═══ */}
      <div className="flex-1 overflow-y-auto px-4 py-3 space-y-3.5 overscroll-contain">
        
        {/* Приветственный багет (показывается только в начале диалога) */}
        {messages.length === 1 && (
          <div className="p-3.5 rounded-2xl bg-gradient-to-r from-violet-500/10 via-purple-500/5 to-transparent border border-violet-500/20 mb-2">
            <div className="flex items-center gap-2.5">
              <div className="w-8 h-8 rounded-xl bg-[#7D39EB] text-white flex items-center justify-center shrink-0 shadow-sm">
                <Sparkles className="w-4 h-4 text-[#C6FF33]" />
              </div>
              <div className="min-w-0">
                <h3 className="text-xs font-extrabold text-slate-900 dark:text-white tracking-tight">
                  {isStudent ? 'Персональный ИИ-Тьютор' : 'Методический помощник Edusfera'}
                </h3>
                <p className="text-[11px] text-slate-500 dark:text-slate-400 truncate">
                  {activePersonaObj.desc}
                </p>
              </div>
            </div>
          </div>
        )}

        {messages.map((msg, idx) => {
          const isUser = msg.role === 'user';
          return (
            <div
              key={idx}
              className={`flex items-start gap-2.5 ${isUser ? 'flex-row-reverse' : 'flex-row'}`}
            >
              <div
                className={`w-8 h-8 rounded-xl shrink-0 flex items-center justify-center shadow-sm ${
                  isUser
                    ? 'bg-slate-800 text-white dark:bg-white dark:text-black'
                    : 'bg-[#7D39EB] text-white shadow-violet-500/20'
                }`}
              >
                {isUser ? <UserIcon className="w-4 h-4" /> : <Bot className="w-4 h-4 text-[#C6FF33]" />}
              </div>

              <div
                className={`max-w-[86%] rounded-3xl p-3.5 shadow-xs ${
                  isUser
                    ? 'bg-gradient-to-r from-[#7D39EB] to-[#8B5CF6] text-white rounded-tr-xs shadow-md shadow-violet-500/15'
                    : 'bg-white dark:bg-[#15141C] border border-slate-200/90 dark:border-white/10 rounded-tl-xs shadow-sm'
                }`}
              >
                {isUser ? (
                  <p className="text-[14px] font-medium leading-relaxed whitespace-pre-wrap">
                    {msg.content}
                  </p>
                ) : (
                  <div>
                    {/* Рендеринг структурированного Markdown */}
                    <MarkdownMessageView content={msg.content} />

                    {/* Панель быстрых действий под ответом */}
                    <div className="mt-3 pt-2.5 border-t border-slate-100 dark:border-white/10 flex items-center justify-between gap-1.5 flex-wrap">
                      <button
                        type="button"
                        onClick={() => handleCopy(msg.content, idx)}
                        className="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-slate-100 dark:bg-white/5 hover:bg-slate-200 dark:hover:bg-white/10 text-[11px] font-bold text-slate-700 dark:text-slate-300 transition-colors"
                      >
                        {copiedIndex === idx ? (
                          <>
                            <Check className="w-3.5 h-3.5 text-emerald-500" />
                            <span className="text-emerald-600 font-extrabold">Скопировано</span>
                          </>
                        ) : (
                          <>
                            <Copy className="w-3.5 h-3.5 text-slate-400" />
                            <span>Копировать</span>
                          </>
                        )}
                      </button>

                      {!isStudent && idx > 0 && (
                        <button
                          type="button"
                          onClick={() => handleCreateHomeworkFromReply(msg.content)}
                          className="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-[#C6FF33] text-black hover:bg-[#b8f522] text-[11px] font-extrabold shadow-xs transition-all active:scale-95"
                        >
                          <BookOpen className="w-3.5 h-3.5" />
                          <span>Назначить ДЗ</span>
                        </button>
                      )}
                    </div>
                  </div>
                )}
              </div>
            </div>
          );
        })}

        {isLoading && (
          <div className="flex items-center gap-2.5 p-3 rounded-2xl bg-white dark:bg-[#15141C] border border-slate-200 dark:border-white/10 w-fit shadow-xs">
            <div className="w-2 h-2 rounded-full bg-[#7D39EB] animate-ping" />
            <span className="text-xs font-bold text-slate-600 dark:text-slate-400">
              Помощник готовит ответ...
            </span>
          </div>
        )}
        <div ref={messagesEndRef} className="h-1" />
      </div>

      {/* ═══ 3. ПОДВАЛ ЧАТА: КАРУСЕЛЬ ПОДСКАЗОК + СТРОКА ВВОДА ═══ */}
      <div className="shrink-0 border-t border-slate-200/80 dark:border-white/10 bg-white/95 dark:bg-[#0A0A0C]/95 backdrop-blur-2xl px-3 pt-2 pb-2.5 z-20">
        
        {/* Горизонтальные чипсы быстрых подсказок */}
        <div className="flex items-center gap-1.5 overflow-x-auto [&::-webkit-scrollbar]:hidden [-ms-overflow-style:none] [scrollbar-width:none] pb-2 pt-0.5 -mx-1 px-1">
          {quickPrompts.map((qp, qIdx) => (
            <button
              key={qIdx}
              type="button"
              onClick={() => handleSendMessage(qp)}
              className="shrink-0 px-3 py-1.5 rounded-full text-xs font-semibold bg-slate-100 hover:bg-slate-200 dark:bg-white/5 dark:hover:bg-white/10 text-slate-700 dark:text-slate-300 border border-slate-200/70 dark:border-white/10 transition-all active:scale-95 flex items-center gap-1"
            >
              <span>{qp}</span>
            </button>
          ))}
        </div>

        {/* Форма отправки сообщения */}
        <form
          onSubmit={(e) => {
            e.preventDefault();
            handleSendMessage();
          }}
          className="flex items-center gap-2"
        >
          <div className="relative flex-1">
            <input
              type="text"
              value={inputMessage}
              onChange={(e) => setInputMessage(e.target.value)}
              placeholder="Задайте вопрос помощнику..."
              disabled={isLoading}
              autoComplete="off"
              className="w-full h-11 pl-4 pr-3 rounded-2xl text-[15px] font-medium bg-slate-100 dark:bg-[#1A1924] border border-slate-300 dark:border-white/15 focus:border-[#7D39EB] focus:ring-2 focus:ring-[#7D39EB]/20 text-slate-900 dark:text-white placeholder:text-slate-400 focus:outline-none transition-all shadow-inner"
            />
          </div>

          <button
            type="submit"
            disabled={!inputMessage.trim() || isLoading}
            className={`w-11 h-11 rounded-2xl flex items-center justify-center shrink-0 transition-all ${
              inputMessage.trim() && !isLoading
                ? 'bg-[#7D39EB] text-white hover:bg-[#6c2ee0] shadow-md shadow-violet-500/30 active:scale-95'
                : 'bg-slate-200 dark:bg-white/10 text-slate-400 cursor-not-allowed'
            }`}
          >
            {isLoading ? (
              <div className="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin" />
            ) : (
              <Send className="w-4 h-4 ml-0.5" />
            )}
          </button>
        </form>
      </div>

    </div>
  );
}
