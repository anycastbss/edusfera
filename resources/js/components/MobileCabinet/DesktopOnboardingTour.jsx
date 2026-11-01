import React, { useState, useEffect } from 'react';
import { motion, AnimatePresence } from 'motion/react';
import { 
  Monitor, Calendar, Clock, Sparkles, Wallet, 
  Video, CheckCircle2, ChevronRight, ChevronLeft, 
  X, Zap, BookOpen, ShieldCheck, ArrowRight, TrendingUp, MessageSquare, ExternalLink
} from 'lucide-react';

export default function DesktopOnboardingTour({
  isOpen,
  onClose,
  isTutor = true,
  userName = '',
  onNavigateTab
}) {
  const [currentStep, setCurrentStep] = useState(0);

  // Desktop-specific steps tailored to big-screen workflows
  const tutorSteps = [
    {
      id: 'welcome',
      tag: 'Единый веб-кабинет',
      tagColor: 'from-violet-500 to-indigo-500',
      title: 'Добро пожаловать в веб-кабинет Edusfera!',
      subtitle: `Рады приветствовать вас, ${userName || 'преподаватель'}! Мы объединили мобильный и десктопный кабинеты в единую экосистему на React 19: мгновенный отклик, адаптивные диалоги и полный комфорт на большом мониторе.`,
      icon: Monitor,
      accentColor: '#7D39EB',
      preview: (
        <div className="relative w-full p-5 rounded-3xl bg-gradient-to-br from-violet-600/20 via-purple-900/20 to-black/50 border border-violet-500/30 backdrop-blur-xl shadow-2xl text-center space-y-3">
          <div className="w-16 h-16 mx-auto rounded-2xl bg-gradient-to-tr from-[#7D39EB] to-[#C6FF33] p-0.5 shadow-xl shadow-violet-500/30 flex items-center justify-center">
            <div className="w-full h-full bg-[#0E0E14] rounded-[14px] flex items-center justify-center">
              <Zap className="w-8 h-8 text-[#C6FF33]" />
            </div>
          </div>
          <div>
            <h4 className="text-sm font-extrabold text-white">Пространство 2026 для ПК</h4>
            <p className="text-[11px] text-slate-300 mt-1">
              Расписание, видеокласс, чаты, ИИ-методист и налоги НПД — в едином быстром интерфейсе
            </p>
          </div>
          <div className="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-white/10 text-[10px] font-bold text-white/90">
            <span className="w-1.5 h-1.5 rounded-full bg-[#C6FF33] animate-ping" />
            React 19 & Aurora UI
          </div>
        </div>
      )
    },
    {
      id: 'schedule',
      tag: 'Расписание уроков',
      tagColor: 'from-purple-500 to-pink-500',
      title: 'Недельный календарь и запуск класса',
      subtitle: 'Управляйте графиком на широком экране: переключайте недели стрелками, запускайте видеокласс в 1 клик и настраивайте постоянные свободные часы кнопкой «Часы».',
      icon: Calendar,
      accentColor: '#C6FF33',
      targetTab: 'lessons',
      preview: (
        <div className="relative w-full p-4 rounded-3xl bg-[#121216] border border-white/15 backdrop-blur-xl shadow-2xl space-y-2.5">
          <div className="flex items-center justify-between pb-2 border-b border-white/10">
            <span className="text-xs font-black text-white">Октябрь 2026</span>
            <div className="px-2 py-0.5 rounded-lg bg-purple-500/20 text-[#C6FF33] border border-purple-500/30 text-[10px] font-bold flex items-center gap-1">
              <Clock className="w-3 h-3" />
              <span>Часы занятий</span>
            </div>
          </div>
          <div className="grid grid-cols-7 gap-1 text-center">
            {['Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб', 'Вс'].map((d, i) => (
              <div key={d} className={`p-1.5 rounded-xl text-[10px] font-bold ${i === 2 ? 'bg-[#7D39EB] text-white shadow-md' : 'bg-white/5 text-slate-300'}`}>
                {d}
              </div>
            ))}
          </div>
          <div className="p-2.5 rounded-2xl bg-gradient-to-r from-purple-500/20 to-transparent border border-purple-500/30 flex items-center justify-between">
            <span className="text-[11px] font-bold text-white">14:49 • Математика</span>
            <span className="px-2 py-0.5 rounded-full bg-[#C6FF33] text-black text-[10px] font-extrabold flex items-center gap-1">
              <Video className="w-3 h-3 fill-current" />
              <span>1 клик</span>
            </span>
          </div>
        </div>
      )
    },
    {
      id: 'ai',
      tag: 'Искусственный интеллект',
      tagColor: 'from-amber-500 to-orange-500',
      title: 'ИИ-Методист Edusfera Copilot',
      subtitle: 'Ваш цифровой ассистент прямо в браузере: составляет планы уроков, генерирует экспресс-тесты по темам ЦТ и ЦЭ 2026 и готовит проверочные задания.',
      icon: Sparkles,
      accentColor: '#F59E0B',
      targetTab: 'ai_agent',
      preview: (
        <div className="relative w-full p-4 rounded-3xl bg-[#121216] border border-amber-500/30 shadow-2xl space-y-2.5">
          <div className="flex items-center gap-2">
            <div className="w-8 h-8 rounded-xl bg-amber-500/20 text-amber-400 flex items-center justify-center font-bold">
              <Sparkles className="w-4 h-4" />
            </div>
            <div>
              <h5 className="text-xs font-bold text-white">Edusfera AI Методист</h5>
              <p className="text-[10px] text-slate-400">Спецификации ЦТ / ЦЭ 2026</p>
            </div>
          </div>
          <div className="space-y-1.5">
            <div className="p-2 rounded-xl bg-white/5 text-[10px] text-slate-200 border border-white/5">
              ⚡ План урока по тригонометрии на 45 мин
            </div>
            <div className="p-2 rounded-xl bg-white/5 text-[10px] text-slate-200 border border-white/5">
              🎯 3 типовые ловушки в части «Б»
            </div>
          </div>
        </div>
      )
    },
    {
      id: 'finance',
      tag: 'Финансы и налоги',
      tagColor: 'from-emerald-500 to-teal-500',
      title: 'Расчётный доход и чеки НПД',
      subtitle: 'Ученики рассчитываются с вами напрямую (карта, наличные, ЕРИП). Платформа считает примерный доход за месяц и формирует чеки для налога на профдоход (МНС РБ) без рутины.',
      icon: Wallet,
      accentColor: '#10B981',
      targetTab: 'finance',
      preview: (
        <div className="relative w-full p-4 rounded-3xl bg-[#121216] border border-emerald-500/30 shadow-2xl space-y-2.5">
          <div className="flex items-center justify-between">
            <span className="text-[10px] uppercase font-bold text-slate-400">Примерный доход за месяц</span>
            <span className="px-2 py-0.5 rounded-full bg-emerald-500/20 text-emerald-400 text-[10px] font-bold">Прямой расчёт</span>
          </div>
          <div className="text-2xl font-black text-white">
            ~70.00 <span className="text-xs font-normal text-slate-400">BYN</span>
          </div>
          <div className="p-2.5 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-[10px] text-emerald-300 font-bold flex items-center gap-2">
            <ShieldCheck className="w-4 h-4 text-emerald-400 shrink-0" />
            <span>Формирование чеков в приложение «Налог на профдоход»</span>
          </div>
        </div>
      )
    },
    {
      id: 'chat',
      tag: 'Коммуникация',
      tagColor: 'from-cyan-500 to-blue-500',
      title: 'Сообщения и домашние задания',
      subtitle: 'Встроенный защищённый мессенджер с учениками и родителями: мгновенный обмен учебными материалами, вопросами и отправка 5 задач ДЗ в один клик.',
      icon: MessageSquare,
      accentColor: '#38BDF8',
      targetTab: 'chat',
      preview: (
        <div className="relative w-full p-4 rounded-3xl bg-[#121216] border border-cyan-500/30 shadow-2xl space-y-2">
          <div className="flex items-center gap-2 pb-2 border-b border-white/10">
            <div className="w-7 h-7 rounded-full bg-cyan-500/20 text-cyan-300 flex items-center justify-center text-xs font-bold">
              МУ
            </div>
            <div>
              <span className="text-xs font-bold text-white block">Максим Ученик</span>
              <span className="text-[10px] text-emerald-400">онлайн</span>
            </div>
          </div>
          <div className="space-y-1.5 text-[10px]">
            <div className="p-2 rounded-xl bg-cyan-500/15 border border-cyan-500/30 text-white w-fit ml-auto">
              Здравствуйте! Прикрепил решение задачи №4 👍
            </div>
            <div className="p-2 rounded-xl bg-white/5 border border-white/10 text-slate-300 w-fit">
              Отлично, разберем на уроке в 15:00!
            </div>
          </div>
        </div>
      )
    },
    {
      id: 'profile_verification',
      tag: 'Анкета и диплом',
      tagColor: 'from-blue-500 to-indigo-500',
      title: 'Расширенная анкета и статус проверенного репетитора',
      subtitle: 'В профиле вы можете подробно заполнить образование, методику занятий, стаж и достижения учеников, а также прикрепить фото диплома. Документ автоматически отправляется техническому администратору на конфиденциальную модерацию. После подтверждения ваша анкета получает знак «✓ Проверенный специалист» и наивысший приоритет в каталоге!',
      icon: ShieldCheck,
      accentColor: '#38BDF8',
      targetTab: 'profile',
      preview: (
        <div className="relative w-full p-4 rounded-3xl bg-[#121216] border border-blue-500/30 shadow-2xl space-y-2.5">
          <div className="flex items-center gap-2">
            <div className="w-8 h-8 rounded-xl bg-blue-500/20 text-blue-400 flex items-center justify-center font-bold">
              <ShieldCheck className="w-4 h-4" />
            </div>
            <div>
              <h5 className="text-xs font-bold text-white">Верификация диплома</h5>
              <p className="text-[10px] text-emerald-400 font-bold">✓ Проверенный специалист</p>
            </div>
          </div>
          <div className="p-2.5 rounded-xl bg-white/5 border border-white/5 text-[10px] text-slate-300 space-y-1 text-left">
            <p>🎓 <strong>Образование:</strong> БГУ, мехмат</p>
            <p>⏳ <strong>Стаж:</strong> 7+ лет • Подготовка к ЦТ/ЦЭ</p>
            <p>🔒 <strong>Модерация:</strong> Конфиденциальная проверка тех. админом</p>
          </div>
        </div>
      )
    },
    {
      id: 'filament',
      tag: 'Два режима работы',
      tagColor: 'from-fuchsia-500 to-purple-500',
      title: 'Классический режим Filament всегда рядом',
      subtitle: 'Если вам понадобятся глубокие настройки системы, в левом сайдбаре внизу всегда доступна прямая ссылка «Режим Filament (/admin)» для быстрого перехода.',
      icon: ExternalLink,
      accentColor: '#A855F7',
      preview: (
        <div className="relative w-full p-4 rounded-3xl bg-[#121216] border border-purple-500/30 shadow-2xl space-y-3 text-center">
          <div className="w-12 h-12 mx-auto rounded-2xl bg-purple-500/20 text-[#C6FF33] border border-purple-500/40 flex items-center justify-center">
            <ExternalLink className="w-6 h-6" />
          </div>
          <div>
            <h5 className="text-xs font-bold text-white">Режим Filament (/admin)</h5>
            <p className="text-[10px] text-slate-400 mt-0.5">
              Сохраняется в неизменном виде для расширенного администрирования
            </p>
          </div>
          <div className="px-3 py-1.5 rounded-xl bg-white/5 border border-white/10 text-[10px] font-mono text-slate-300">
            Ссылка внизу левого сайдбара
          </div>
        </div>
      )
    }
  ];

  // Student steps for desktop
  const studentSteps = [
    {
      id: 'welcome',
      tag: 'Веб-кабинет ученика',
      tagColor: 'from-violet-500 to-indigo-500',
      title: 'Добро пожаловать в веб-кабинет Edusfera!',
      subtitle: `Привет, ${userName || 'ученик'}! Твой личный кабинет для комфортных занятий на большом экране: расписание, онлайн-класс с интерактивной доской, ДЗ и ИИ-тьютор.`,
      icon: Monitor,
      accentColor: '#7D39EB',
      preview: (
        <div className="relative w-full p-5 rounded-3xl bg-gradient-to-br from-violet-600/20 to-black/50 border border-violet-500/30 text-center space-y-3">
          <div className="w-14 h-14 mx-auto rounded-2xl bg-[#7D39EB] flex items-center justify-center shadow-lg">
            <Zap className="w-7 h-7 text-[#C6FF33]" />
          </div>
          <h4 className="text-sm font-extrabold text-white">Всё для подготовки к ЦТ и ЦЭ</h4>
          <p className="text-[11px] text-slate-300">Индивидуальные уроки с лучшими преподавателями</p>
        </div>
      )
    },
    {
      id: 'classroom',
      tag: 'Виртуальный класс',
      tagColor: 'from-purple-500 to-pink-500',
      title: 'Подключение к уроку в 1 клик',
      subtitle: 'Не нужно устанавливать Zoom или сторонние программы. Нажми «Войти в класс» и ты сразу на уроке с HD-видеосвязью и онлайн-доской.',
      icon: Video,
      accentColor: '#C6FF33',
      targetTab: 'lessons',
      preview: (
        <div className="p-4 rounded-3xl bg-[#121216] border border-white/10 space-y-2">
          <div className="text-xs font-bold text-white">Индивидуальный урок</div>
          <div className="p-3 rounded-2xl bg-[#C6FF33] text-black font-extrabold text-xs flex items-center justify-center gap-2">
            <Video className="w-4 h-4 fill-current" />
            <span>Войти в виртуальный класс</span>
          </div>
        </div>
      )
    },
    {
      id: 'homework',
      tag: 'Домашние задания',
      tagColor: 'from-emerald-500 to-teal-500',
      title: 'Задания и разборы от репетитора',
      subtitle: 'Все материалы и домашние задания сохраняются в твоем кабинете. Отправляй решения и отслеживай статус проверки.',
      icon: BookOpen,
      accentColor: '#10B981',
      targetTab: 'homework',
      preview: (
        <div className="p-4 rounded-3xl bg-[#121216] border border-emerald-500/30 space-y-2">
          <div className="text-xs font-bold text-white">Домашнее задание</div>
          <div className="p-2.5 rounded-xl bg-white/5 border border-white/10 text-[11px] text-slate-200">
            📝 Тема: Квадратные уравнения и теорема Виета
          </div>
        </div>
      )
    },
    {
      id: 'ai',
      tag: 'ИИ-Репетитор',
      tagColor: 'from-amber-500 to-orange-500',
      title: 'ИИ-Репетитор 24/7',
      subtitle: 'Застрял на сложной задаче? Наш ИИ-помощник объяснит логику решения и подберет похожие примеры в любое время.',
      icon: Sparkles,
      accentColor: '#F59E0B',
      targetTab: 'ai_tutor',
      preview: (
        <div className="p-4 rounded-3xl bg-[#121216] border border-amber-500/30 space-y-2">
          <div className="flex items-center gap-2">
            <Sparkles className="w-4 h-4 text-amber-400" />
            <span className="text-xs font-bold text-white">Помощник в подготовке</span>
          </div>
          <p className="text-[11px] text-slate-300">Объяснение сложных тем понятным языком</p>
        </div>
      )
    }
  ];

  const steps = isTutor ? tutorSteps : studentSteps;
  const current = steps[currentStep] || steps[0];
  const isFinalStep = currentStep === steps.length - 1;

  const handleNext = () => {
    if (isFinalStep) {
      handleComplete();
    } else {
      setCurrentStep(prev => prev + 1);
      if (current.targetTab && onNavigateTab) {
        onNavigateTab(current.targetTab);
      }
    }
  };

  const handlePrev = () => {
    if (currentStep > 0) {
      setCurrentStep(prev => prev - 1);
    }
  };

  const handleComplete = () => {
    try {
      localStorage.setItem('edusfera_desktop_tour_completed_v1', 'true');
    } catch (e) {}
    if (onClose) onClose();
  };

  // Keyboard navigation
  useEffect(() => {
    if (!isOpen) return;
    const handleKeyDown = (e) => {
      if (e.key === 'ArrowRight' || e.key === 'Enter') {
        e.preventDefault();
        handleNext();
      } else if (e.key === 'ArrowLeft') {
        e.preventDefault();
        handlePrev();
      } else if (e.key === 'Escape') {
        e.preventDefault();
        handleComplete();
      }
    };
    window.addEventListener('keydown', handleKeyDown);
    return () => window.removeEventListener('keydown', handleKeyDown);
  }, [isOpen, currentStep, isFinalStep]);

  if (!isOpen) return null;

  return (
    <AnimatePresence>
      <div className="fixed inset-0 z-[100] flex items-center justify-center p-4">
        {/* Backdrop with frosted dark glass */}
        <motion.div
          initial={{ opacity: 0 }}
          animate={{ opacity: 1 }}
          exit={{ opacity: 0 }}
          transition={{ duration: 0.2 }}
          onClick={handleComplete}
          className="fixed inset-0 bg-black/75 backdrop-blur-md"
        />

        {/* Desktop Tour Modal Card */}
        <motion.div
          initial={{ opacity: 0, scale: 0.95, y: 10 }}
          animate={{ opacity: 1, scale: 1, y: 0 }}
          exit={{ opacity: 0, scale: 0.95, y: 10 }}
          transition={{ duration: 0.22, ease: [0.16, 1, 0.3, 1] }}
          className="relative w-full max-w-2xl rounded-[32px] bg-[#0E0E14] border border-white/15 text-white shadow-2xl p-7 z-10 overflow-hidden"
        >
          {/* Subtle Ambient Radial Glow */}
          <div className="absolute -top-24 -right-24 w-72 h-72 rounded-full bg-[#7D39EB]/25 blur-[90px] pointer-events-none" />
          <div className="absolute -bottom-24 -left-24 w-72 h-72 rounded-full bg-[#C6FF33]/15 blur-[90px] pointer-events-none" />

          {/* ── Top Bar: Segmented Progress & Close ── */}
          <div className="relative z-10 mb-6">
            <div className="flex items-center justify-between mb-3">
              <div className="flex items-center gap-2">
                <span className="text-[10px] font-mono font-bold uppercase tracking-widest text-[#C6FF33]">
                  Обучение по кабинету ПК
                </span>
                <span className="text-xs text-white/40">•</span>
                <span className="text-xs font-mono text-white/50">
                  {currentStep + 1} из {steps.length}
                </span>
              </div>

              <div className="flex items-center gap-2">
                <button
                  type="button"
                  onClick={handleComplete}
                  className="text-xs text-slate-400 hover:text-white transition px-2 py-1 rounded-lg hover:bg-white/5"
                >
                  Пропустить
                </button>
                <button
                  type="button"
                  onClick={handleComplete}
                  className="w-8 h-8 rounded-full bg-white/10 hover:bg-white/20 flex items-center justify-center text-white/80 hover:text-white transition"
                  title="Закрыть (Esc)"
                >
                  <X className="w-4 h-4" />
                </button>
              </div>
            </div>

            {/* Segmented Story Bars */}
            <div className="grid grid-cols-6 gap-1.5 w-full">
              {steps.map((_, i) => (
                <div
                  key={i}
                  className="h-1.5 rounded-full overflow-hidden bg-white/10 cursor-pointer transition-all"
                  onClick={() => setCurrentStep(i)}
                >
                  <div
                    className={`h-full transition-all duration-300 ${
                      i <= currentStep ? 'bg-gradient-to-r from-[#7D39EB] to-[#C6FF33]' : 'w-0'
                    }`}
                    style={{ width: i <= currentStep ? '100%' : '0%' }}
                  />
                </div>
              ))}
            </div>
          </div>

          {/* ── Main Step Content: 2-Column Responsive Layout ── */}
          <div className="relative z-10 grid grid-cols-1 md:grid-cols-12 gap-6 items-center min-h-[260px]">
            {/* Left Preview Card (5 cols) */}
            <div className="md:col-span-5 flex items-center justify-center">
              <AnimatePresence mode="wait">
                <motion.div
                  key={current.id}
                  initial={{ opacity: 0, scale: 0.92 }}
                  animate={{ opacity: 1, scale: 1 }}
                  exit={{ opacity: 0, scale: 0.92 }}
                  transition={{ duration: 0.2 }}
                  className="w-full flex items-center justify-center"
                >
                  {current.preview}
                </motion.div>
              </AnimatePresence>
            </div>

            {/* Right Information (7 cols) */}
            <div className="md:col-span-7 space-y-3">
              <AnimatePresence mode="wait">
                <motion.div
                  key={current.id}
                  initial={{ opacity: 0, y: 6 }}
                  animate={{ opacity: 1, y: 0 }}
                  exit={{ opacity: 0, y: -6 }}
                  transition={{ duration: 0.2 }}
                  className="space-y-3"
                >
                  <span className={`inline-block px-3 py-1 rounded-full text-[11px] font-extrabold uppercase tracking-wider bg-gradient-to-r ${current.tagColor} text-white shadow-sm`}>
                    {current.tag}
                  </span>

                  <h3 className="text-xl font-black text-white tracking-tight leading-snug">
                    {current.title}
                  </h3>

                  <p className="text-xs text-slate-300 leading-relaxed font-normal">
                    {current.subtitle}
                  </p>
                </motion.div>
              </AnimatePresence>
            </div>
          </div>

          {/* ── Footer Navigation Controls ── */}
          <div className="relative z-10 mt-6 pt-4 border-t border-white/10 flex items-center justify-between">
            <button
              type="button"
              onClick={handlePrev}
              disabled={currentStep === 0}
              className={`flex items-center gap-1.5 px-4 py-2.5 rounded-xl text-xs font-bold transition ${
                currentStep === 0
                  ? 'opacity-30 cursor-not-allowed text-slate-500'
                  : 'text-slate-300 hover:text-white hover:bg-white/10'
              }`}
            >
              <ChevronLeft className="w-4 h-4" />
              <span>Назад</span>
            </button>

            <div className="flex items-center gap-1.5">
              {steps.map((_, i) => (
                <button
                  key={i}
                  type="button"
                  onClick={() => setCurrentStep(i)}
                  className={`w-2 h-2 rounded-full transition-all ${
                    i === currentStep
                      ? 'w-6 bg-[#C6FF33]'
                      : 'bg-white/20 hover:bg-white/40'
                  }`}
                />
              ))}
            </div>

            <button
              type="button"
              onClick={handleNext}
              className="flex items-center gap-2 px-5 py-2.5 rounded-xl bg-[#C6FF33] hover:bg-[#b8f026] text-black font-extrabold text-xs shadow-lg shadow-[#C6FF33]/20 active:opacity-90 transition cursor-pointer"
            >
              <span>{isFinalStep ? 'Начать работу в кабинете' : 'Далее'}</span>
              <ArrowRight className="w-4 h-4" />
            </button>
          </div>
        </motion.div>
      </div>
    </AnimatePresence>
  );
}
