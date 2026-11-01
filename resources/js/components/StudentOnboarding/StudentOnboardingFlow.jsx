import React, { useState, useRef } from 'react';
import { motion, AnimatePresence } from 'motion/react';
import {
  Camera, Upload, Check, ArrowRight, ArrowLeft,
  Loader2, Sparkles, BookOpen, GraduationCap, Target,
  LogOut, AlertCircle, ShieldCheck, Flame, Compass, HelpCircle
} from 'lucide-react';
import axios from 'axios';

// Настройка CSRF-токена
if (typeof window !== 'undefined') {
  const token = window.EDUSFERA_CSRF_TOKEN || document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
  if (token) {
    axios.defaults.headers.common['X-CSRF-TOKEN'] = token;
  }
  axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
}

const DEFAULT_GRADES = [
  '1–4 класс',
  '5–8 класс',
  '9 класс',
  '10 класс',
  '11 класс',
  'Студент / Взрослый',
];

const DEFAULT_SUBJECTS = [
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

const DEFAULT_TOPICS = {
  'Математика': [
    'Алгебра и формулы',
    'Геометрия и планиметрия',
    'Уравнения и неравенства',
    'Текстовые задачи',
    'Тестовые ловушки ЦТ',
  ],
  'Физика': [
    'Механика и кинематика',
    'Термодинамика и МКТ',
    'Электродинамика',
    'Оптика и волны',
    'Квантовая физика',
  ],
  'Русский язык': [
    'Орфография (корни/суффиксы)',
    'Пунктуация в сложных предложениях',
    'Синтаксис и нормы речи',
    'Слитное и раздельное НЕ/НИ',
    'Тестовые формулировки ЦТ',
  ],
  'Белорусский язык': [
    'Правапіс галосных і зычных',
    'Сінтаксіс і пунктуацыя',
    'Марфалогія і часціны мовы',
    'Фразеалогія і лексіка',
    'Тэставыя пасткі ЦЭ/ЦТ',
  ],
  'Английский язык': [
    'Времена глаголов (Tenses)',
    'Грамматические тесты',
    'Словарный запас (Vocabulary)',
    'Аудирование',
    'Чтение и анализ текста',
  ],
  'Химия': [
    'Неорганическая химия',
    'Органическая химия',
    'Расчетные задачи по формулам',
    'Реакции и электронный баланс',
  ],
  'Биология': [
    'Анатомия человека',
    'Общая биология и генетика',
    'Ботаника и зоология',
    'Эволюция и экология',
  ],
  'История Беларуси': [
    'Даты и ключевые периоды',
    'Исторические деятели',
    'Культура и терминология',
    'Карты и схемы битв',
  ],
  'Обществоведение': [
    'Человек и общество',
    'Право и Конституция',
    'Экономика и финансы',
    'Политика и государство',
  ],
  'Информатика': [
    'Программирование (Python/C++)',
    'Алгоритмы и структуры',
    'Системы счисления и логика',
    'Базы данных и таблицы',
  ],
};

const DEFAULT_CURRENT_SCORES = [
  { score: 35, label: '< 40 б.', sub: 'С нуля' },
  { score: 50, label: '40–60 б.', sub: 'Базовый' },
  { score: 70, label: '60–75 б.', sub: 'Средний' },
  { score: 85, label: '75+ б.', sub: 'Уверенный' },
];

const DEFAULT_TARGET_SCORES = [
  { score: 75, label: '75+ б.' },
  { score: 85, label: '85+ б.' },
  { score: 95, label: '95+ б.' },
  { score: 100, label: '100 б.' },
];

export default function StudentOnboardingFlow({ initialData }) {
  // 4 линейных компактных экрана (Принцип 80/20):
  // 1: Фотография и Имя
  // 2: Класс и Предметы
  // 3: Стартовая экспресс-диагностика (Текущий уровень, Цель, Зоны роста)
  // 4: Финал (Траектория построена)
  const [step, setStep] = useState(1);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [errorMessage, setErrorMessage] = useState('');

  // Шаг 1: Фото и Имя
  const [userName, setUserName] = useState(initialData?.user?.name || '');
  const [avatarUrl, setAvatarUrl] = useState(initialData?.user?.avatar || '');
  const [avatarUploading, setAvatarUploading] = useState(false);
  const fileInputRef = useRef(null);

  // Шаг 2: Класс и Предметы
  const [selectedGrade, setSelectedGrade] = useState(
    initialData?.profile?.grade || '11 класс'
  );
  const [selectedSubjects, setSelectedSubjects] = useState(
    initialData?.profile?.subjects?.length > 0
      ? initialData.profile.subjects
      : ['Математика']
  );

  // Шаг 3: Диагностика (Текущий балл, Цель, Слабые темы, Заметки)
  const [currentScore, setCurrentScore] = useState(
    initialData?.profile?.current_score || 50
  );
  const [targetScore, setTargetScore] = useState(
    initialData?.profile?.target_score || 85
  );
  const [weakTopics, setWeakTopics] = useState(
    initialData?.profile?.weak_topics?.length > 0
      ? initialData.profile.weak_topics
      : ['Алгебра и формулы', 'Геометрия и планиметрия']
  );
  const [bio, setBio] = useState(initialData?.profile?.bio || '');

  const popularSubjects = initialData?.popular_subjects || DEFAULT_SUBJECTS;
  const gradesList = initialData?.grades || DEFAULT_GRADES;
  const topicsMap = initialData?.topics_by_subject || DEFAULT_TOPICS;
  const currentScoresList = initialData?.current_score_options || DEFAULT_CURRENT_SCORES;
  const targetScoresList = initialData?.target_score_options || DEFAULT_TARGET_SCORES;

  const primarySubject = selectedSubjects[0] || 'Математика';
  const availableTopicsForSubject = topicsMap[primarySubject] || [
    'Теория и правила',
    'Практика заданий',
    'Темп решения',
    'Сложные задания части B',
    'Тестовая стратегия',
  ];

  // Загрузка фото
  const handlePhotoUpload = async (e) => {
    const file = e.target.files?.[0];
    if (!file) return;

    if (file.size > 5 * 1024 * 1024) {
      setErrorMessage('Размер фото не должен превышать 5 МБ');
      return;
    }

    const localPreview = URL.createObjectURL(file);
    setAvatarUrl(localPreview);
    setAvatarUploading(true);
    setErrorMessage('');

    try {
      const formData = new FormData();
      formData.append('photo', file);

      const res = await axios.post('/api/student/onboarding/upload-photo', formData, {
        headers: { 'Content-Type': 'multipart/form-data' },
      });

      if (res.data?.success && res.data?.avatar_url) {
        setAvatarUrl(res.data.avatar_url);
      }
    } catch (err) {
      setErrorMessage(
        err.response?.data?.message || 'Не удалось загрузить фото. Попробуйте еще раз.'
      );
    } finally {
      setAvatarUploading(false);
    }
  };

  // Переключение предмета
  const toggleSubject = (subj) => {
    setErrorMessage('');
    if (selectedSubjects.includes(subj)) {
      if (selectedSubjects.length === 1) {
        setErrorMessage('Выберите хотя бы один предмет для занятий');
        return;
      }
      setSelectedSubjects(selectedSubjects.filter((s) => s !== subj));
    } else {
      setSelectedSubjects([...selectedSubjects, subj]);
    }
  };

  // Переключение слабой темы
  const toggleWeakTopic = (topic) => {
    setErrorMessage('');
    if (weakTopics.includes(topic)) {
      setWeakTopics(weakTopics.filter((t) => t !== topic));
    } else {
      if (weakTopics.length >= 4) {
        setErrorMessage('Рекомендуем выбрать до 3-4 ключевых тем для фокуса');
        return;
      }
      setWeakTopics([...weakTopics, topic]);
    }
  };

  // Переход с Шага 1
  const handleStep1Next = () => {
    setErrorMessage('');
    const trimmed = userName.trim();
    if (!trimmed || trimmed.length < 2) {
      setErrorMessage('Пожалуйста, укажите ваше имя (минимум 2 буквы)');
      return;
    }
    setStep(2);
  };

  // Переход с Шага 2
  const handleStep2Next = () => {
    setErrorMessage('');
    if (!selectedGrade) {
      setErrorMessage('Пожалуйста, выберите ваш класс');
      return;
    }
    if (selectedSubjects.length === 0) {
      setErrorMessage('Выберите хотя бы один предмет');
      return;
    }
    setStep(3);
  };

  // Сохранение и завершение с Шага 3 (Диагностика)
  const handleSaveAndComplete = async () => {
    setErrorMessage('');
    if (targetScore < currentScore) {
      setErrorMessage('Целевой балл должен быть не ниже текущего ориентира');
      return;
    }

    setIsSubmitting(true);
    try {
      await axios.post('/api/student/onboarding/save-profile', {
        name: userName.trim(),
        grade: selectedGrade,
        goal_type: 'ct_2026',
        subjects: selectedSubjects,
        current_score: currentScore,
        target_score: targetScore,
        weak_topics: weakTopics,
        bio: bio.trim(),
      });

      await axios.post('/api/student/onboarding/complete');
      setStep(4);
    } catch (err) {
      setErrorMessage(
        err.response?.data?.message || 'Ошибка сохранения данных профиля. Попробуйте снова.'
      );
    } finally {
      setIsSubmitting(false);
    }
  };

  const handleFinishAndRedirect = () => {
    window.location.href = '/admin';
  };

  const handleLogout = async () => {
    try {
      await axios.post('/logout');
    } catch (e) {
      // ignore
    }
    window.location.href = '/login';
  };

  // Расчет процента готовности
  const readinessPercent = Math.min(100, Math.round((currentScore / Math.max(1, targetScore)) * 100));

  return (
    <div className="h-full h-[100dvh] w-full flex flex-col bg-[#08090C] text-white overflow-hidden select-none">
      {/* ── ВЕРХНЯЯ ШАПКА ── */}
      <header className="shrink-0 h-14 sm:h-16 px-4 sm:px-6 border-b border-white/10 flex items-center justify-between bg-[#08090C]/80 backdrop-blur-md z-20">
        <div className="flex items-center gap-3">
          <a href="/" className="flex items-center gap-2 group cursor-pointer">
            <svg
              className="w-7 h-7 sm:w-8 sm:h-8"
              viewBox="0 0 100 100"
              fill="none"
              xmlns="http://www.w3.org/2000/svg"
            >
              <rect width="100" height="100" rx="22" fill="#08090C" />
              <path
                d="M50 15L82 50L50 85L18 50L50 15Z"
                fill="url(#student_logo_grad2)"
              />
              <path
                d="M50 28L70 50L50 72L30 50L50 28Z"
                fill="#C6FF33"
              />
              <defs>
                <linearGradient id="student_logo_grad2" x1="18" y1="15" x2="82" y2="85" gradientUnits="userSpaceOnUse">
                  <stop stopColor="#7D39EB" />
                  <stop offset="1" stopColor="#C6FF33" />
                </linearGradient>
              </defs>
            </svg>
            <div className="flex flex-col">
              <span className="font-rimma text-sm sm:text-base tracking-wider text-white uppercase font-bold leading-none">
                EDUSFERA
              </span>
              <span className="text-[10px] text-white/50 tracking-wider">
                Кабинет ученика
              </span>
            </div>
          </a>
        </div>

        {/* Прогресс шагов */}
        {step <= 3 && (
          <div className="flex items-center gap-2">
            <span className="text-xs font-semibold text-white/70">
              Шаг <span className="text-[#C6FF33] font-bold">{step}</span> из 3
            </span>
            <div className="flex gap-1">
              {[1, 2, 3].map((s) => (
                <div
                  key={s}
                  className={`h-1.5 rounded-full transition-all duration-300 ${
                    s === step
                      ? 'w-6 bg-[#C6FF33] shadow-[0_0_8px_rgba(198,255,51,0.6)]'
                      : s < step
                        ? 'w-3 bg-[#C6FF33]/60'
                        : 'w-3 bg-white/20'
                  }`}
                />
              ))}
            </div>
          </div>
        )}

        <button
          type="button"
          onClick={handleLogout}
          title="Выйти"
          className="p-2 rounded-xl text-white/50 hover:text-white hover:bg-white/10 transition-colors cursor-pointer"
        >
          <LogOut className="w-4 h-4" />
        </button>
      </header>

      {/* ── ОСНОВНАЯ ЧАСТЬ ЭКРАНА ── */}
      <main className="flex-1 w-full max-w-lg mx-auto px-4 py-3 sm:py-4 flex flex-col justify-center overflow-y-auto no-scrollbar">
        <AnimatePresence mode="wait">
          {/* ══════════════════════════════════════════════════════════ */}
          {/* ШАГ 1: ФОТОГРАФИЯ И ИМЯ                                    */}
          {/* ══════════════════════════════════════════════════════════ */}
          {step === 1 && (
            <motion.div
              key="step-1"
              initial={{ opacity: 0, y: 12 }}
              animate={{ opacity: 1, y: 0 }}
              exit={{ opacity: 0, y: -12 }}
              transition={{ duration: 0.18 }}
              className="space-y-4"
            >
              <div className="text-center sm:text-left">
                <div className="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-[#7D39EB]/20 border border-[#7D39EB]/40 text-[#d8b4fe] text-[11px] font-semibold mb-2">
                  <Sparkles className="w-3.5 h-3.5 text-[#C6FF33]" />
                  <span>Профиль и стартовая диагностика за 1 минуту</span>
                </div>
                <h1 className="text-xl sm:text-2xl font-bold tracking-tight text-white">
                  Давайте познакомимся 👋
                </h1>
                <p className="text-xs sm:text-sm text-white/65 mt-1 leading-relaxed">
                  Загрузите фото и проверьте имя. Репетиторы увидят ваш аватар на занятиях в онлайн-классе.
                </p>
              </div>

              {/* Загрузка фото */}
              <div className="p-3.5 sm:p-4 rounded-2xl bg-neutral-900/80 border border-white/10 flex items-center gap-4">
                <div className="relative group shrink-0">
                  <div className="w-20 h-20 rounded-full overflow-hidden bg-neutral-800 border-2 border-[#C6FF33]/40 flex items-center justify-center shadow-[0_0_20px_rgba(198,255,51,0.15)]">
                    {avatarUrl ? (
                      <img
                        src={avatarUrl}
                        alt="Avatar"
                        className="w-full h-full object-cover"
                      />
                    ) : (
                      <span className="text-3xl">🧑‍🎓</span>
                    )}

                    {avatarUploading && (
                      <div className="absolute inset-0 bg-black/70 flex items-center justify-center">
                        <Loader2 className="w-6 h-6 animate-spin text-[#C6FF33]" />
                      </div>
                    )}
                  </div>

                  <button
                    type="button"
                    onClick={() => fileInputRef.current?.click()}
                    disabled={avatarUploading}
                    className="absolute -bottom-1 -right-1 p-2 rounded-full bg-[#C6FF33] text-black hover:bg-[#d5ff5e] transition-all shadow-[0_0_10px_rgba(198,255,51,0.4)] cursor-pointer"
                    title="Загрузить фото"
                  >
                    <Camera className="w-3.5 h-3.5 stroke-[2.5]" />
                  </button>

                  <input
                    ref={fileInputRef}
                    type="file"
                    accept="image/jpeg,image/png,image/webp"
                    className="hidden"
                    onChange={handlePhotoUpload}
                  />
                </div>

                <div className="space-y-1">
                  <div className="text-xs font-bold text-white">
                    {avatarUrl ? 'Фото загружено' : 'Ваша фотография'}
                  </div>
                  <p className="text-[11px] text-white/55 leading-tight">
                    JPG, PNG или WebP до 5 МБ. С фото репетиторам проще общаться в классе.
                  </p>
                  <button
                    type="button"
                    onClick={() => fileInputRef.current?.click()}
                    className="text-[11px] font-semibold text-[#C6FF33] hover:underline flex items-center gap-1 cursor-pointer pt-0.5"
                  >
                    <Upload className="w-3 h-3" />
                    <span>{avatarUrl ? 'Заменить фото' : 'Выбрать файл'}</span>
                  </button>
                </div>
              </div>

              {/* Поле Имени */}
              <div className="space-y-1.5">
                <label className="text-xs font-semibold text-white/80">
                  Ваше имя и фамилия <span className="text-[#C6FF33]">*</span>
                </label>
                <input
                  type="text"
                  value={userName}
                  onChange={(e) => setUserName(e.target.value)}
                  placeholder="Например, Алексей Смирнов"
                  className="w-full h-12 px-3.5 rounded-xl bg-neutral-900 border border-white/20 text-white text-sm focus:border-[#C6FF33] focus:ring-1 focus:ring-[#C6FF33] outline-none transition-all"
                />
                <div className="flex items-center gap-1.5 text-[11px] text-white/45 pt-0.5">
                  <ShieldCheck className="w-3.5 h-3.5 text-[#C6FF33] shrink-0" />
                  <span>Имя отображается в расписании и чатах с преподавателями</span>
                </div>
              </div>
            </motion.div>
          )}

          {/* ══════════════════════════════════════════════════════════ */}
          {/* ШАГ 2: КЛАСС И ПРЕДМЕТЫ                                    */}
          {/* ══════════════════════════════════════════════════════════ */}
          {step === 2 && (
            <motion.div
              key="step-2"
              initial={{ opacity: 0, y: 12 }}
              animate={{ opacity: 1, y: 0 }}
              exit={{ opacity: 0, y: -12 }}
              transition={{ duration: 0.18 }}
              className="space-y-3.5"
            >
              <div>
                <h1 className="text-xl sm:text-2xl font-bold tracking-tight text-white">
                  Класс и предметы 📚
                </h1>
                <p className="text-xs sm:text-sm text-white/65 mt-1 leading-relaxed">
                  Укажите ваш класс и предметы, по которым планируете заниматься.
                </p>
              </div>

              {/* Выбор класса */}
              <div className="space-y-1.5">
                <div className="text-xs font-semibold text-white/80 flex items-center gap-1.5">
                  <GraduationCap className="w-3.5 h-3.5 text-[#C6FF33]" />
                  <span>Ваш класс:</span>
                </div>
                <div className="grid grid-cols-3 gap-1.5">
                  {gradesList.map((grade) => {
                    const isSelected = selectedGrade === grade;
                    return (
                      <button
                        key={grade}
                        type="button"
                        onClick={() => setSelectedGrade(grade)}
                        className={`h-9 px-2 rounded-xl text-xs font-semibold transition-all cursor-pointer flex items-center justify-center text-center ${
                          isSelected
                            ? 'bg-[#C6FF33] text-black shadow-[0_0_12px_rgba(198,255,51,0.3)] font-bold'
                            : 'bg-neutral-900 text-white/75 border border-white/15 hover:border-white/30'
                        }`}
                      >
                        {grade}
                      </button>
                    );
                  })}
                </div>
              </div>

              {/* Выбор предметов */}
              <div className="space-y-1.5 pt-1">
                <div className="flex items-center justify-between">
                  <span className="text-xs font-semibold text-white/80 flex items-center gap-1.5">
                    <BookOpen className="w-3.5 h-3.5 text-[#C6FF33]" />
                    <span>Нужные предметы:</span>
                  </span>
                  <span className="text-[11px] text-[#C6FF33] font-bold">
                    {selectedSubjects.length} выбрано
                  </span>
                </div>
                <div className="flex flex-wrap gap-1.5">
                  {popularSubjects.map((subj) => {
                    const isSelected = selectedSubjects.includes(subj);
                    return (
                      <button
                        key={subj}
                        type="button"
                        onClick={() => toggleSubject(subj)}
                        className={`px-3 py-2 rounded-xl text-xs font-semibold transition-all cursor-pointer flex items-center gap-1.5 ${
                          isSelected
                            ? 'bg-[#C6FF33] text-black shadow-[0_0_12px_rgba(198,255,51,0.25)] font-bold'
                            : 'bg-neutral-900 text-white/80 border border-white/15 hover:border-white/30'
                        }`}
                      >
                        {isSelected && <Check className="w-3.5 h-3.5 stroke-[3]" />}
                        <span>{subj}</span>
                      </button>
                    );
                  })}
                </div>
              </div>
            </motion.div>
          )}

          {/* ══════════════════════════════════════════════════════════ */}
          {/* ШАГ 3: ПЕРВИЧНАЯ ЭКСПРЕСС-ДИАГНОСТИКА                      */}
          {/* ══════════════════════════════════════════════════════════ */}
          {step === 3 && (
            <motion.div
              key="step-3"
              initial={{ opacity: 0, y: 12 }}
              animate={{ opacity: 1, y: 0 }}
              exit={{ opacity: 0, y: -12 }}
              transition={{ duration: 0.18 }}
              className="space-y-3"
            >
              <div>
                <div className="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-[#C6FF33]/15 text-[#C6FF33] text-[10px] font-bold mb-1 uppercase tracking-wider">
                  <Compass className="w-3 h-3" />
                  <span>Стартовый срез знаний</span>
                </div>
                <h1 className="text-xl sm:text-2xl font-bold tracking-tight text-white">
                  Диагностика: {primarySubject} 🎯
                </h1>
                <p className="text-xs sm:text-sm text-white/65 mt-0.5 leading-relaxed">
                  Оцените ориентир и выберите темы, где чаще всего теряете баллы.
                </p>
              </div>

              {/* Блок 1: Текущий балл и Цель (2 компактные колонки) */}
              <div className="grid grid-cols-2 gap-2.5">
                {/* Текущий балл */}
                <div className="p-3 rounded-2xl bg-neutral-900/90 border border-white/15 space-y-1.5">
                  <div className="text-[11px] font-bold text-white/70 flex items-center gap-1">
                    <span>Текущий уровень:</span>
                  </div>
                  <div className="grid grid-cols-2 gap-1">
                    {currentScoresList.map((item) => {
                      const isSel = currentScore === item.score;
                      return (
                        <button
                          key={item.score}
                          type="button"
                          onClick={() => setCurrentScore(item.score)}
                          className={`p-1.5 rounded-lg text-center transition-all cursor-pointer ${
                            isSel
                              ? 'bg-[#C6FF33] text-black font-bold'
                              : 'bg-black/40 text-white/70 border border-white/10 hover:border-white/20'
                          }`}
                        >
                          <div className="text-xs leading-none">{item.label}</div>
                          <div className="text-[9px] opacity-75 mt-0.5">{item.sub}</div>
                        </button>
                      );
                    })}
                  </div>
                </div>

                {/* Целевой балл */}
                <div className="p-3 rounded-2xl bg-neutral-900/90 border border-[#C6FF33]/30 space-y-1.5">
                  <div className="text-[11px] font-bold text-[#C6FF33] flex items-center gap-1">
                    <Target className="w-3 h-3" />
                    <span>Желаемая цель:</span>
                  </div>
                  <div className="grid grid-cols-2 gap-1">
                    {targetScoresList.map((item) => {
                      const isSel = targetScore === item.score;
                      return (
                        <button
                          key={item.score}
                          type="button"
                          onClick={() => setTargetScore(item.score)}
                          className={`p-1.5 rounded-lg text-center transition-all cursor-pointer ${
                            isSel
                              ? 'bg-[#C6FF33] text-black font-bold'
                              : 'bg-black/40 text-white/70 border border-white/10 hover:border-white/20'
                          }`}
                        >
                          <div className="text-xs leading-none">{item.label}</div>
                          <div className="text-[9px] opacity-75 mt-0.5">Экзамен</div>
                        </button>
                      );
                    })}
                  </div>
                </div>
              </div>

              {/* Блок 2: Проблемные темы (Skill Gaps) */}
              <div className="space-y-1.5 pt-0.5">
                <div className="flex items-center justify-between">
                  <span className="text-xs font-semibold text-white/85 flex items-center gap-1.5">
                    <Flame className="w-3.5 h-3.5 text-amber-400" />
                    <span>Темы, требующие отработки:</span>
                  </span>
                  <span className="text-[10px] text-white/50">
                    {weakTopics.length} выбрано
                  </span>
                </div>
                <div className="flex flex-wrap gap-1.5">
                  {availableTopicsForSubject.map((topic) => {
                    const isSelected = weakTopics.includes(topic);
                    return (
                      <button
                        key={topic}
                        type="button"
                        onClick={() => toggleWeakTopic(topic)}
                        className={`px-2.5 py-1.5 rounded-xl text-xs font-semibold transition-all cursor-pointer flex items-center gap-1 ${
                          isSelected
                            ? 'bg-amber-400 text-black shadow-[0_0_10px_rgba(251,191,36,0.3)] font-bold'
                            : 'bg-neutral-900 text-white/75 border border-white/15 hover:border-white/30'
                        }`}
                      >
                        {isSelected && <Check className="w-3 h-3 stroke-[3]" />}
                        <span>{topic}</span>
                      </button>
                    );
                  })}
                </div>
              </div>

              {/* Комментарий / пожелания */}
              <div className="pt-0.5">
                <input
                  type="text"
                  value={bio}
                  maxLength={300}
                  onChange={(e) => setBio(e.target.value)}
                  placeholder="Пожелания (например: упор на практику части B)"
                  className="w-full h-10 px-3 rounded-xl bg-neutral-900 border border-white/20 text-white text-xs focus:border-[#C6FF33] outline-none transition-all"
                />
              </div>
            </motion.div>
          )}

          {/* ══════════════════════════════════════════════════════════ */}
          {/* ШАГ 4: УСПЕШНОЕ ЗАВЕРШЕНИЕ                                 */}
          {/* ══════════════════════════════════════════════════════════ */}
          {step === 4 && (
            <motion.div
              key="step-4"
              initial={{ opacity: 0, scale: 0.96 }}
              animate={{ opacity: 1, scale: 1 }}
              transition={{ duration: 0.2 }}
              className="text-center py-6 space-y-4"
            >
              <div className="w-16 h-16 mx-auto rounded-full bg-[#C6FF33]/20 border-2 border-[#C6FF33] flex items-center justify-center text-[#C6FF33] shadow-[0_0_25px_rgba(198,255,51,0.3)]">
                <Check className="w-8 h-8 stroke-[3]" />
              </div>

              <div className="space-y-1.5">
                <h1 className="text-xl sm:text-2xl font-black text-white tracking-tight">
                  Траектория построена! 🎉
                </h1>
                <p className="text-xs sm:text-sm text-white/75 max-w-sm mx-auto leading-relaxed">
                  Добро пожаловать в Edusfera, <strong className="text-white">{userName}</strong>! Ваш стартовый срез знаний и цель сохранены в личном кабинете.
                </p>
              </div>

              {/* Карточка-сводка траектории */}
              <div className="p-3.5 rounded-2xl bg-white/[0.04] border border-[#C6FF33]/30 text-xs text-left space-y-2.5 max-w-sm mx-auto">
                <div className="flex items-center justify-between text-[11px]">
                  <span className="text-white/60">Предмет и цель:</span>
                  <span className="text-[#C6FF33] font-bold">{primarySubject} • {selectedGrade}</span>
                </div>

                <div className="flex items-center justify-between py-1 border-y border-white/10">
                  <span className="text-white/80">Ориентир ➔ Цель:</span>
                  <span className="font-bold text-white text-sm">
                    {currentScore} б. ➔ <span className="text-[#C6FF33]">{targetScore} б.</span>
                  </span>
                </div>

                {weakTopics.length > 0 && (
                  <div className="space-y-1">
                    <div className="text-white/60 text-[10px] uppercase font-bold tracking-wider">
                      Темы в фокусе (Skill Gaps):
                    </div>
                    <div className="flex flex-wrap gap-1">
                      {weakTopics.map((t) => (
                        <span
                          key={t}
                          className="px-2 py-0.5 rounded-md bg-amber-400/20 text-amber-300 font-semibold text-[10px]"
                        >
                          ⚠️ {t}
                        </span>
                      ))}
                    </div>
                  </div>
                )}
              </div>

              <button
                type="button"
                onClick={handleFinishAndRedirect}
                className="w-full h-13 rounded-xl bg-[#C6FF33] text-black font-bold text-sm uppercase tracking-wider hover:bg-[#d5ff5e] transition-all cursor-pointer shadow-[0_0_20px_rgba(198,255,51,0.4)] flex items-center justify-center gap-2"
              >
                <span>Перейти в личный кабинет</span>
                <ArrowRight className="w-4 h-4 stroke-[2.5]" />
              </button>
            </motion.div>
          )}
        </AnimatePresence>
      </main>

      {/* ── НИЖНЯЯ ПАНЕЛЬ ДЕЙСТВИЙ (ФИКСИРОВАННАЯ, БЕЗ СКРОЛЛА) ── */}
      {step <= 3 && (
        <div
          className="shrink-0 bg-[#08090C]/95 backdrop-blur-md border-t border-white/10 px-4 pt-3"
          style={{
            paddingBottom: 'calc(max(1.25rem, env(safe-area-inset-bottom, 1.25rem)) + 0.75rem)',
          }}
        >
          <div className="max-w-lg mx-auto space-y-2">
            {/* Ошибка прямо над кнопкой */}
            {errorMessage && (
              <div className="p-2.5 rounded-xl bg-rose-500/20 border border-rose-500/40 text-rose-300 text-xs flex items-center gap-2">
                <AlertCircle className="w-4 h-4 shrink-0" />
                <span>{errorMessage}</span>
              </div>
            )}

            <div className="flex items-center gap-2.5">
              {step > 1 && (
                <button
                  type="button"
                  onClick={() => {
                    setErrorMessage('');
                    setStep((prev) => Math.max(1, prev - 1));
                  }}
                  className="h-12 px-4 rounded-xl bg-neutral-900 border border-white/20 text-white font-semibold text-xs sm:text-sm hover:bg-neutral-800 transition-colors flex items-center justify-center gap-1 cursor-pointer shrink-0"
                >
                  <ArrowLeft className="w-4 h-4" />
                  <span>Назад</span>
                </button>
              )}

              <button
                type="button"
                onClick={
                  step === 1
                    ? handleStep1Next
                    : step === 2
                      ? handleStep2Next
                      : handleSaveAndComplete
                }
                disabled={isSubmitting}
                className="flex-1 h-12 px-5 rounded-xl bg-[#C6FF33] text-black hover:bg-[#d5ff5e] font-bold text-sm tracking-wide transition-all flex items-center justify-center gap-2 cursor-pointer shadow-[0_0_15px_rgba(198,255,51,0.3)] disabled:opacity-50"
              >
                {isSubmitting ? (
                  <>
                    <Loader2 className="w-4 h-4 animate-spin text-black" />
                    <span>Построение траектории...</span>
                  </>
                ) : (
                  <>
                    <span>{step === 3 ? 'Зафиксировать траекторию' : 'Продолжить'}</span>
                    <ArrowRight className="w-4 h-4 stroke-[2.5]" />
                  </>
                )}
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
