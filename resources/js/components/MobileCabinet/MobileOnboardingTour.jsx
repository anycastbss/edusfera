import React, { useState, useEffect } from 'react';
import { motion, AnimatePresence } from 'motion/react';
import { 
  Calendar, Clock, Sparkles, Share2, Plus, 
  Wallet, Video, CheckCircle2, ChevronRight, ChevronLeft, 
  X, Zap, BookOpen, ShieldCheck, ArrowRight, Smartphone, TrendingUp
} from 'lucide-react';

export default function MobileOnboardingTour({
  isOpen,
  onClose,
  isTutor = true,
  userName = '',
  onNavigateTab
}) {
  const [currentStep, setCurrentStep] = useState(0);

  // Steps configuration tailored to role
  const tutorSteps = [
    {
      id: 'welcome',
      tag: 'Новый кабинет',
      tagColor: 'from-violet-500 to-indigo-500',
      title: 'Добро пожаловать в новый мобильный кабинет!',
      subtitle: `Рады видеть вас, ${userName || 'преподаватель'}! Мы полностью обновили кабинет со смартфона: он работает со скоростью нативного приложения даже при слабом интернете.`,
      icon: Smartphone,
      accentColor: '#7D39EB',
      preview: (
        <div className="relative w-full max-w-[280px] p-4 rounded-3xl bg-gradient-to-br from-violet-600/20 via-purple-900/20 to-black/40 border border-violet-500/30 backdrop-blur-xl shadow-2xl text-center space-y-2.5">
          <div className="w-14 h-14 mx-auto rounded-2xl bg-gradient-to-tr from-[#7D39EB] to-[#C6FF33] p-0.5 shadow-lg shadow-violet-500/30 flex items-center justify-center">
            <div className="w-full h-full bg-slate-950 rounded-[14px] flex items-center justify-center">
              <Zap className="w-7 h-7 text-[#C6FF33] animate-pulse" />
            </div>
          </div>
          <div>
            <h4 className="text-sm font-extrabold text-white">Управление уроками в кармане</h4>
            <p className="text-[11px] text-slate-300 mt-1">График, ученики, доска, чаты и ИИ-методист — теперь всегда под рукой</p>
          </div>
          <div className="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-white/10 text-[10px] font-bold text-white/90">
            <span className="w-1.5 h-1.5 rounded-full bg-[#C6FF33] animate-ping" />
            PWA Offline-Ready 2026
          </div>
        </div>
      )
    },
    {
      id: 'schedule',
      tag: 'Расписание & Часы',
      tagColor: 'from-purple-500 to-pink-500',
      title: 'Умный график и свободные окна',
      subtitle: 'Листайте недели стрелочками, а кнопка «Часы» позволяет в пару касаний настроить постоянные часы для онлайн-записи.',
      icon: Calendar,
      accentColor: '#C6FF33',
      targetTab: 'lessons',
      preview: (
        <div className="relative w-full max-w-[280px] p-3.5 rounded-3xl bg-slate-900/80 border border-white/15 backdrop-blur-xl shadow-2xl space-y-2.5">
          <div className="flex items-center justify-between">
            <span className="text-xs font-black text-white">Октябрь 2026</span>
            <div className="px-2 py-0.5 rounded-lg bg-purple-500/20 text-[#C6FF33] border border-purple-500/30 text-[10px] font-bold flex items-center gap-1">
              <Clock className="w-3 h-3" />
              <span>Часы</span>
            </div>
          </div>
          <div className="grid grid-cols-7 gap-1">
            {['Пн 26', 'Вт 27', 'Ср 28', 'Чт 29', 'Пт 30', 'Сб 31', 'Вс 1'].map((d, i) => (
              <div 
                key={d} 
                className={`py-1.5 rounded-lg text-center text-[9px] font-bold ${
                  i === 1 ? 'bg-white text-black shadow-md scale-105' : 'bg-white/5 text-slate-400'
                }`}
              >
                {d.split(' ')[0]}<br/>{d.split(' ')[1]}
              </div>
            ))}
          </div>
          <div className="p-2 rounded-xl bg-purple-500/10 border border-purple-500/20 flex items-center gap-2">
            <span className="w-2 h-2 rounded-full bg-emerald-400" />
            <span className="text-[10px] font-medium text-slate-200">Свободные часы: Пн-Пт 10:00 — 19:00</span>
          </div>
        </div>
      )
    },
    {
      id: 'booking',
      tag: 'Онлайн-запись',
      tagColor: 'from-emerald-500 to-teal-500',
      title: 'Ссылка для прямой записи учеников',
      subtitle: 'Отправляйте персональную ссылку в Telegram или WhatsApp. Ученик сам выберет свободное окно, а вам придет уведомление для подтверждения в 1 клик.',
      icon: Share2,
      accentColor: '#10B981',
      targetTab: 'lessons',
      preview: (
        <div className="relative w-full max-w-[280px] p-4 rounded-3xl bg-slate-900/90 border border-white/15 backdrop-blur-xl shadow-2xl space-y-3">
          <div className="text-[10px] font-bold uppercase tracking-wider text-emerald-400">Персональная ссылка</div>
          <div className="p-2.5 rounded-xl bg-white/5 border border-white/10 font-mono text-xs text-white truncate">
            edusfera.by/tutors/25
          </div>
          <div className="flex gap-2">
            <div className="flex-1 py-2 rounded-xl bg-[#7D39EB] text-white text-center text-xs font-bold shadow-md shadow-purple-500/30">
              Копировать
            </div>
            <div className="p-2 rounded-xl bg-white/10 text-white flex items-center justify-center">
              <Share2 className="w-4 h-4" />
            </div>
          </div>
        </div>
      )
    },
    {
      id: 'quick_create',
      tag: 'Уроки без регистрации',
      tagColor: 'from-amber-500 to-orange-500',
      title: 'Уроки для новых учеников без регистрации',
      subtitle: 'Через кнопку «+» можно быстро запланировать урок даже для нового ученика, которого ещё нет на платформе. После сохранения появится всплывающее окно со ссылкой на виртуальный класс: отправьте её ученику, и он сразу подключится без регистрации! При этом прогресс учеников в базе сохраняться не будет, пока они не зарегистрируются в Edusfera.',
      icon: Plus,
      accentColor: '#F59E0B',
      targetTab: 'lessons',
      preview: (
        <div className="relative w-full max-w-[280px] p-3.5 rounded-3xl bg-slate-900/90 border border-amber-500/30 backdrop-blur-xl shadow-2xl space-y-2.5">
          <div className="flex items-center justify-between">
            <div className="flex items-center gap-1.5 text-amber-400 font-extrabold text-[11px]">
              <Sparkles className="w-3.5 h-3.5" />
              <span>Ссылка в класс готова</span>
            </div>
            <span className="text-[9px] font-bold px-2 py-0.5 rounded-full bg-purple-500/20 text-[#C6FF33]">
              Без регистрации
            </span>
          </div>

          <div className="p-2 rounded-xl bg-white/5 border border-white/10 font-mono text-[10px] text-slate-200 truncate select-all">
            edusfera.by/classroom/42/join?token=a8f9...
          </div>

          <div className="py-2 rounded-xl bg-gradient-to-r from-[#7D39EB] to-[#9333EA] text-white text-center text-xs font-bold shadow-md shadow-purple-500/30 flex items-center justify-center gap-1.5">
            <CheckCircle2 className="w-3.5 h-3.5" />
            <span>Скопировать ссылку для ученика</span>
          </div>

          <div className="p-2 rounded-xl bg-amber-500/10 border border-amber-500/20 text-[9px] text-amber-200 leading-tight">
            ⚠️ <strong>Обратите внимание:</strong> для сохранения прогресса и ДЗ ученику потребуется регистрация.
          </div>
        </div>
      )
    },
    {
      id: 'ai_copilot',
      tag: 'ИИ-Методист',
      tagColor: 'from-cyan-500 to-blue-500',
      title: 'Персональный ИИ-Ассистент',
      subtitle: 'Вкладка «ИИ-Агент» генерирует конспекты, проверочные тесты, домашние задания и помогает подготовиться к любому уроку за 30 секунд.',
      icon: Sparkles,
      accentColor: '#38BDF8',
      targetTab: 'ai_agent',
      preview: (
        <div className="relative w-full max-w-[280px] p-4 rounded-3xl bg-gradient-to-b from-cyan-950/40 to-slate-900 border border-cyan-500/30 backdrop-blur-xl shadow-2xl space-y-2.5">
          <div className="flex items-center gap-2 text-cyan-400">
            <Sparkles className="w-4 h-4" />
            <span className="text-xs font-black uppercase tracking-wider">Edusfera AI Методист</span>
          </div>
          <div className="p-2.5 rounded-xl bg-cyan-500/10 border border-cyan-500/20 text-[11px] text-slate-200">
            «Подготовь 5 тестовых заданий по теме Квадратные уравнения для 8 класса»
          </div>
          <div className="flex items-center gap-1.5 text-[10px] text-emerald-400 font-bold">
            <CheckCircle2 className="w-3.5 h-3.5" />
            <span>Готово за 2.4 сек</span>
          </div>
        </div>
      )
    },
    {
      id: 'finance',
      tag: 'Доход & Уроки',
      tagColor: 'from-violet-500 to-purple-600',
      title: 'Учёт доходов и статистика уроков',
      subtitle: 'В разделе «Финансы» отображается примерный расчёт вашего дохода за месяц на основе проведённых уроков и часовой ставки. Ученики рассчитываются с вами напрямую — без комиссий платформы.',
      icon: TrendingUp,
      accentColor: '#A855F7',
      targetTab: 'finance',
      preview: (
        <div className="relative w-full max-w-[280px] p-4 rounded-3xl bg-slate-900/90 border border-white/15 backdrop-blur-xl shadow-2xl space-y-2">
          <span className="text-[10px] font-bold uppercase tracking-wider text-slate-400">Примерный доход за месяц</span>
          <div className="text-2xl font-black text-white font-mono">
            ~350.00 <span className="text-xs font-bold text-[#C6FF33]">BYN</span>
          </div>
          <div className="pt-2 border-t border-white/10 flex items-center justify-between text-[10px]">
            <span className="text-slate-400">10 проведённых уроков</span>
            <span className="text-emerald-400 font-bold">Прямой расчёт</span>
          </div>
        </div>
      )
    },
    {
      id: 'profile_verification',
      tag: 'Анкета и диплом',
      tagColor: 'from-blue-500 to-indigo-500',
      title: 'Расширенная анкета и подтверждение диплома',
      subtitle: 'В профиле вы можете подробно заполнить образование, методику занятий, стаж и прикрепить фото диплома. Документ конфиденциально отправляется техническому администратору на модерацию. После подтверждения ваша анкета получает высокий статус «✓ Проверенный специалист» и приоритет в каталоге!',
      icon: ShieldCheck,
      accentColor: '#38BDF8',
      targetTab: 'profile',
      preview: (
        <div className="relative w-full max-w-[280px] p-4 rounded-3xl bg-slate-900/90 border border-blue-500/30 backdrop-blur-xl shadow-2xl space-y-2.5">
          <div className="flex items-center gap-2">
            <div className="w-8 h-8 rounded-xl bg-blue-500/20 text-blue-400 flex items-center justify-center font-bold">
              <ShieldCheck className="w-4 h-4" />
            </div>
            <div>
              <h5 className="text-xs font-bold text-white">Верификация профиля</h5>
              <p className="text-[10px] text-emerald-400 font-bold">✓ Проверенный специалист</p>
            </div>
          </div>
          <div className="p-2.5 rounded-xl bg-white/5 border border-white/5 text-[10px] text-slate-300 space-y-1 text-left">
            <p>🎓 Фото диплома на проверке у тех. админа</p>
            <p>🌟 Выше позиция в каталоге репетиторов</p>
            <p>🔒 Документы строго конфиденциальны</p>
          </div>
        </div>
      )
    }
  ];

  const studentSteps = [
    {
      id: 'welcome',
      tag: 'Новый кабинет',
      tagColor: 'from-violet-500 to-indigo-500',
      title: 'Добро пожаловать в Edusfera!',
      subtitle: `Привет, ${userName || 'ученик'}! Твой личный кабинет теперь работает прямо со смартфона без установки из App Store.`,
      icon: Smartphone,
      accentColor: '#7D39EB',
      preview: (
        <div className="relative w-full max-w-[280px] p-4 rounded-3xl bg-gradient-to-br from-violet-600/20 via-purple-900/20 to-black/40 border border-violet-500/30 backdrop-blur-xl shadow-2xl text-center space-y-2.5">
          <div className="w-14 h-14 mx-auto rounded-2xl bg-gradient-to-tr from-[#7D39EB] to-[#C6FF33] p-0.5 shadow-lg shadow-violet-500/30 flex items-center justify-center">
            <div className="w-full h-full bg-slate-950 rounded-[14px] flex items-center justify-center">
              <Zap className="w-7 h-7 text-[#C6FF33] animate-pulse" />
            </div>
          </div>
          <div>
            <h4 className="text-sm font-extrabold text-white">Все уроки в твоем кармане</h4>
            <p className="text-[11px] text-slate-300 mt-1">Видеокласс, домашка и ИИ-помощник всегда с тобой</p>
          </div>
        </div>
      )
    },
    {
      id: 'classroom',
      tag: 'Видеокласс в 1 клик',
      tagColor: 'from-emerald-500 to-teal-500',
      title: 'Вход на занятие без ссылок',
      subtitle: 'Перед началом урока вверху экрана появится зеленая кнопка «Войти в класс». Никаких сложных паролей и сторонних программ — только браузер.',
      icon: Video,
      accentColor: '#10B981',
      targetTab: 'lessons',
      preview: (
        <div className="relative w-full max-w-[280px] p-4 rounded-3xl bg-slate-900/90 border border-emerald-500/30 backdrop-blur-xl shadow-2xl space-y-3">
          <div className="flex items-center justify-between text-xs font-bold text-slate-300">
            <span>Математика • 18:00</span>
            <span className="text-emerald-400 font-mono">Через 10 мин</span>
          </div>
          <div className="py-3 rounded-2xl bg-gradient-to-r from-emerald-600 to-teal-500 text-white font-extrabold text-xs text-center flex items-center justify-center gap-2 shadow-lg shadow-emerald-500/30 animate-pulse">
            <Video className="w-4 h-4" />
            <span>Войти в видеокласс</span>
          </div>
        </div>
      )
    },
    {
      id: 'homework',
      tag: 'Домашние задания',
      tagColor: 'from-purple-500 to-indigo-500',
      title: 'Домашка и материалы уроков',
      subtitle: 'Во вкладке «Домашка» сохраняются все задания от преподавателя, прикрепленные файлы и конспекты с интерактивной доски.',
      icon: BookOpen,
      accentColor: '#8B5CF6',
      targetTab: 'homework',
      preview: (
        <div className="relative w-full max-w-[280px] p-3.5 rounded-3xl bg-slate-900/90 border border-white/15 backdrop-blur-xl shadow-2xl space-y-2">
          <div className="text-[10px] font-bold uppercase tracking-wider text-purple-400">Задание на среду</div>
          <div className="text-xs font-bold text-white">Решить тест №12 и разобрать ошибки</div>
          <div className="flex items-center gap-1.5 text-[10px] text-slate-400">
            <CheckCircle2 className="w-3.5 h-3.5 text-emerald-400" />
            <span>Доска урока сохранена в PDF</span>
          </div>
        </div>
      )
    },
    {
      id: 'ai_tutor',
      tag: 'ИИ-Тьютор 24/7',
      tagColor: 'from-cyan-500 to-blue-500',
      title: 'Персональный ИИ-помощник',
      subtitle: 'Непонятна задача поздно вечером? Открой «ИИ-Тьютор» — он объяснит правило простыми словами и поможет найти верное решение.',
      icon: Sparkles,
      accentColor: '#38BDF8',
      targetTab: 'ai_tutor',
      preview: (
        <div className="relative w-full max-w-[280px] p-4 rounded-3xl bg-gradient-to-b from-cyan-950/40 to-slate-900 border border-cyan-500/30 backdrop-blur-xl shadow-2xl space-y-2">
          <div className="flex items-center gap-1.5 text-cyan-400 text-xs font-extrabold">
            <Sparkles className="w-4 h-4" />
            <span>ИИ-Тьютор Edusfera</span>
          </div>
          <p className="text-[11px] text-slate-300">
            «Объясни формулу дискриминанта на жизненном примере»
          </p>
        </div>
      )
    }
  ];

  const steps = isTutor ? tutorSteps : studentSteps;
  const step = steps[currentStep] || steps[0];

  const handleNext = () => {
    if (currentStep < steps.length - 1) {
      const nextIdx = currentStep + 1;
      setCurrentStep(nextIdx);
      if (onNavigateTab && steps[nextIdx].targetTab) {
        onNavigateTab(steps[nextIdx].targetTab);
      }
    } else {
      handleComplete();
    }
  };

  const handlePrev = () => {
    if (currentStep > 0) {
      const prevIdx = currentStep - 1;
      setCurrentStep(prevIdx);
      if (onNavigateTab && steps[prevIdx].targetTab) {
        onNavigateTab(steps[prevIdx].targetTab);
      }
    }
  };

  const handleComplete = () => {
    try {
      localStorage.setItem('edusfera_mobile_tour_completed_v1', 'true');
    } catch (e) {}
    if (onClose) onClose();
  };

  // Keyboard navigation (arrows + Escape)
  useEffect(() => {
    if (!isOpen) return;
    const handleKeyDown = (e) => {
      if (e.key === 'ArrowRight' || e.key === 'Enter') handleNext();
      else if (e.key === 'ArrowLeft') handlePrev();
      else if (e.key === 'Escape') handleComplete();
    };
    window.addEventListener('keydown', handleKeyDown);
    return () => window.removeEventListener('keydown', handleKeyDown);
  }, [isOpen, currentStep]);

  if (!isOpen) return null;

  return (
    <AnimatePresence>
      <motion.div
        key="mobile-onboarding-overlay"
        initial={{ opacity: 0 }}
        animate={{ opacity: 1 }}
        exit={{ opacity: 0 }}
        transition={{ duration: 0.25 }}
        className="fixed inset-0 z-50 flex flex-col justify-between bg-black/85 backdrop-blur-2xl text-white select-none overflow-hidden"
        style={{ paddingBottom: 'calc(env(safe-area-inset-bottom, 16px) + 16px)', paddingTop: 'calc(env(safe-area-inset-top, 16px) + 12px)' }}
      >
        {/* ─── TOP BAR: SEGMENTED STORY BARS & SKIP BUTTON ─── */}
        <div className="px-5 space-y-3">
          <div className="flex items-center justify-between gap-3">
            {/* Segmented Story Progress Bars */}
            <div className="flex-1 flex items-center gap-1.5">
              {steps.map((_, idx) => (
                <div
                  key={idx}
                  onClick={() => setCurrentStep(idx)}
                  className="h-1 flex-1 rounded-full overflow-hidden bg-white/20 cursor-pointer transition-all"
                >
                  <div
                    className={`h-full transition-all duration-300 ${
                      idx < currentStep
                        ? 'bg-[#C6FF33]'
                        : idx === currentStep
                        ? 'bg-gradient-to-r from-[#7D39EB] to-[#C6FF33]'
                        : 'bg-transparent'
                    }`}
                    style={{ width: idx <= currentStep ? '100%' : '0%' }}
                  />
                </div>
              ))}
            </div>

            {/* Skip Button */}
            <button
              type="button"
              onClick={handleComplete}
              className="p-1.5 -mr-1 rounded-full text-slate-400 hover:text-white hover:bg-white/10 active:scale-95 transition"
              title="Пропустить обучение"
            >
              <X className="w-5 h-5" />
            </button>
          </div>

          <div className="flex items-center justify-between text-[11px] font-bold text-slate-400">
            <span className={`inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-extrabold uppercase tracking-wider bg-gradient-to-r ${step.tagColor} text-white shadow-sm`}>
              {step.tag}
            </span>
            <span>
              {currentStep + 1} из {steps.length}
            </span>
          </div>
        </div>

        {/* ─── CENTER HERO SHOWCASE AREA WITH ANIMATED TRANSITIONS ─── */}
        <div className="flex-1 flex flex-col items-center justify-center px-6 py-4 my-auto">
          <AnimatePresence mode="wait">
            <motion.div
              key={step.id}
              initial={{ opacity: 0, y: 15, scale: 0.95 }}
              animate={{ opacity: 1, y: 0, scale: 1 }}
              exit={{ opacity: 0, y: -15, scale: 0.95 }}
              transition={{ duration: 0.25, ease: 'easeOut' }}
              className="w-full flex flex-col items-center text-center space-y-5"
            >
              {/* Interactive Mock Preview */}
              <div className="w-full flex justify-center py-2">
                {step.preview}
              </div>

              {/* Title & Description */}
              <div className="space-y-2 max-w-sm">
                <h2 className="text-xl sm:text-2xl font-black text-white tracking-tight font-rimma leading-snug">
                  {step.title}
                </h2>
                <p className="text-xs sm:text-sm text-slate-300 leading-relaxed font-normal">
                  {step.subtitle}
                </p>
              </div>
            </motion.div>
          </AnimatePresence>
        </div>

        {/* ─── BOTTOM CONTROLS: PREV / NEXT / START ─── */}
        <div className="px-5 space-y-2.5">
          <div className="flex items-center gap-2.5">
            {currentStep > 0 && (
              <button
                type="button"
                onClick={handlePrev}
                className="w-12 h-12 rounded-2xl bg-white/10 hover:bg-white/15 active:scale-95 text-white flex items-center justify-center transition border border-white/10 shrink-0"
                title="Назад"
              >
                <ChevronLeft className="w-5 h-5" />
              </button>
            )}

            <button
              type="button"
              onClick={handleNext}
              className={`flex-1 h-12 rounded-2xl font-extrabold text-sm flex items-center justify-center gap-2 shadow-xl active:scale-[0.98] transition-all duration-200 ${
                currentStep === steps.length - 1
                  ? 'bg-gradient-to-r from-[#7D39EB] to-[#9F5BF5] text-white shadow-purple-500/30 hover:brightness-110'
                  : 'bg-white text-slate-950 hover:bg-slate-100 shadow-white/10'
              }`}
            >
              <span>
                {currentStep === steps.length - 1 ? 'Всё понятно, поехали! 🚀' : 'Далее'}
              </span>
              {currentStep < steps.length - 1 && <ArrowRight className="w-4 h-4" />}
            </button>
          </div>

          <p className="text-[10px] text-center text-slate-500">
            Вы всегда сможете повторить этот тур во вкладке «Профиль»
          </p>
        </div>
      </motion.div>
    </AnimatePresence>
  );
}
