import React, { useState, useEffect, useRef } from 'react';
import { motion, AnimatePresence } from 'motion/react';
import {
  Camera, Upload, Check, CheckCircle2, Clock, Calendar,
  ArrowRight, ArrowLeft, ShieldCheck, Loader2, Tag, BookOpen, LogOut,
  AlertCircle, User, ChevronDown, CheckSquare, Square, X, PhoneCall
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

const POPULAR_SUBJECTS = [
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
  'Немецкий язык',
  'Начальная школа (1–4 классы)',
];

const PRESET_PRICES = [30, 35, 40, 45, 50, 60];

const SPECIALIZATION_OPTIONS = [
  'Подготовка к ЦЭ 2026',
  'Подготовка к ЦТ 2026',
  'Школьный курс (5–11 классы)',
  'Начальное образование (1–4 классы)',
  'Олимпиады и повышенный уровень',
  'Дошкольная подготовка',
  'Студенты и взрослые',
  'Международные экзамены (IELTS, TOEFL и др.)',
];

const SCHEDULE_PRESETS = {
  evenings: {
    label: 'Будни вечером',
    desc: 'Пн–Пт, 16:00 – 21:00',
    days: [1, 2, 3, 4, 5],
    start: '16:00',
    end: '21:00',
  },
  weekends: {
    label: 'Выходные дни',
    desc: 'Сб–Вс, 10:00 – 18:00',
    days: [6, 0],
    start: '10:00',
    end: '18:00',
  },
  everyday: {
    label: 'Каждый день',
    desc: 'Пн–Вс, 10:00 – 20:00',
    days: [1, 2, 3, 4, 5, 6, 0],
    start: '10:00',
    end: '20:00',
  },
};


const WEEKDAYS = [
  { id: 1, label: 'Пн', full: 'Понедельник' },
  { id: 2, label: 'Вт', full: 'Вторник' },
  { id: 3, label: 'Ср', full: 'Среда' },
  { id: 4, label: 'Чт', full: 'Четверг' },
  { id: 5, label: 'Пт', full: 'Пятница' },
  { id: 6, label: 'Сб', full: 'Суббота' },
  { id: 0, label: 'Вс', full: 'Воскресенье' },
];

const SUBSCRIPTION_PLANS = [
  {
    id: 'start',
    title: 'Стандарт',
    price: '20',
    period: 'BYN / мес',
    badge: 'Базовый',
    tagline: 'Для занятий со своими учениками',
    features: [
      'Виртуальный класс и интерактивная доска',
      'Расписание занятий и карточки учеников',
      'Свои ученики без комиссий платформы',
    ],
  },
  {
    id: 'pro',
    title: 'Pro',
    price: '40',
    period: 'BYN / мес',
    badge: '⭐ Хит продаж',
    tagline: 'Автоматизация, ИИ и новые ученики',
    features: [
      'Все возможности тарифа «Стандарт»',
      'ИИ-помощник (конспекты, тесты, ДЗ)',
      'Диагностика знаний (база тестов РИКЗ)',
      'Чеки НПД (МНС РБ) и до 10 заявок в месяц',
    ],
  },
  {
    id: 'premium',
    title: 'Премиум',
    price: '60',
    period: 'BYN / мес',
    badge: '👑 Топ-эксперт',
    tagline: 'Максимальный доход и VIP-статус',
    features: [
      'Все возможности тарифов Стандарт и Pro',
      'Золотой бейдж «Топ-эксперт» в каталоге',
      'Видео-визитка прямо в поисковой выдаче',
      'БЕЗЛИМИТНЫЕ отклики на заявки учеников',
    ],
  },
];

export default function TutorOnboardingFlow({ initialData }) {
  // 5 компактных шагов:
  // 1: Фотография
  // 2: Имя и предмет
  // 3: Стоимость урока
  // 4: График занятий
  // 5: Выбор тарифа и промокод
  // 6: Готово
  const [step, setStep] = useState(1);
  const [isLoading, setIsLoading] = useState(!initialData);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [errorMessage, setErrorMessage] = useState('');
  const [successToast, setSuccessToast] = useState('');

  // Выбор тарифа и модальное окно 14 дней бесплатно
  const [selectedPlan, setSelectedPlan] = useState(initialData?.subscription?.plan || 'pro');
  const [showTrialModal, setShowTrialModal] = useState(false);

  // Режим работы кабинета: Приватный (false, default для 50+) или Открытая витрина (true)
  const [isPublic, setIsPublic] = useState(
    initialData?.profile?.is_public !== undefined ? Boolean(initialData.profile.is_public) : false
  );

  // Кнопка и модалка: Настройка по звонку за 5 минут
  const [showHelpModal, setShowHelpModal] = useState(false);
  const [helpPhone, setHelpPhone] = useState(initialData?.user?.phone || '');
  const [helpSubmitting, setHelpSubmitting] = useState(false);
  const [helpSent, setHelpSent] = useState(false);
  const [helpError, setHelpError] = useState('');

  // Данные пользователя и фото
  const [user, setUser] = useState(initialData?.user || null);
  const [userName, setUserName] = useState(initialData?.user?.name || '');
  const [avatarUrl, setAvatarUrl] = useState(initialData?.profile?.avatar_url || '');
  const [avatarPreview, setAvatarPreview] = useState('');
  const [avatarUploading, setAvatarUploading] = useState(false);
  const fileInputRef = useRef(null);

  // Шаг 2: Предмет и опыт
  const [primarySubject, setPrimarySubject] = useState(initialData?.profile?.subjects?.[0] || 'Математика');
  const [experienceYears, setExperienceYears] = useState(initialData?.profile?.experience_years || 3);
  const [bio, setBio] = useState(initialData?.profile?.bio || '');

  // Шаг 3: Цена и специализации
  const initialPrice = Number(initialData?.profile?.price_per_hour || 40);
  const [pricePerHour, setPricePerHour] = useState(initialPrice >= 10 ? initialPrice : 40);
  const [isCustomPrice, setIsCustomPrice] = useState(!PRESET_PRICES.includes(initialPrice));
  const [customPriceInput, setCustomPriceInput] = useState(
    !PRESET_PRICES.includes(initialPrice) && initialPrice > 0 ? String(initialPrice) : ''
  );
  const [selectedSpecs, setSelectedSpecs] = useState(
    initialData?.profile?.exam_specializations?.length
      ? initialData.profile.exam_specializations
      : ['Подготовка к ЦТ / ЦЭ 2026', 'Школьная программа (5–11 классы)']
  );

  // Шаг 4: Расписание
  const [selectedDays, setSelectedDays] = useState(initialData?.schedule?.days || [1, 2, 3, 4, 5]);
  const [startTime, setStartTime] = useState(initialData?.schedule?.start_time || '16:00');
  const [endTime, setEndTime] = useState(initialData?.schedule?.end_time || '21:00');
  const [activePreset, setActivePreset] = useState('evenings');

  // Шаг 5: Промокод и тариф
  const [promoCodeInput, setPromoCodeInput] = useState('');
  const [appliedPromo, setAppliedPromo] = useState(null);
  const [promoChecking, setPromoChecking] = useState(false);
  const [promoError, setPromoError] = useState('');

  // Загрузка состояния, если не передано изначально
  useEffect(() => {
    if (!initialData) {
      axios.get('/api/tutor/onboarding/state')
        .then((res) => {
          if (res.data?.success) {
            const data = res.data;
            setUser(data.user);
            setUserName(data.user?.name || '');
            if (data.user?.phone) {
              setHelpPhone(data.user.phone);
            }
            if (data.profile?.is_public !== undefined) {
              setIsPublic(Boolean(data.profile.is_public));
            }
            setAvatarUrl(data.user?.avatar || data.profile?.avatar_url || '');

            const subs = data.profile?.subjects || [];
            if (subs.length > 0) {
              setPrimarySubject(subs[0]);
            }
            if (data.profile?.experience_years) {
              setExperienceYears(data.profile.experience_years);
            }
            if (data.profile?.bio) {
              setBio(data.profile.bio);
            }
            if (data.profile?.price_per_hour) {
              const p = Number(data.profile.price_per_hour);
              setPricePerHour(p);
              if (!PRESET_PRICES.includes(p)) {
                setIsCustomPrice(true);
                setCustomPriceInput(String(p));
              }
            }
            if (data.profile?.exam_specializations?.length) {
              setSelectedSpecs(data.profile.exam_specializations);
            }
            if (data.schedule?.days?.length) {
              setSelectedDays(data.schedule.days);
            }
            if (data.schedule?.start_time) {
              setStartTime(data.schedule.start_time);
            }
            if (data.schedule?.end_time) {
              setEndTime(data.schedule.end_time);
            }
          }
        })
        .finally(() => {
          setIsLoading(false);
        });
    }
  }, [initialData]);

  // Загрузка фотографии
  const handlePhotoSelect = async (e) => {
    const file = e.target.files?.[0];
    if (!file) return;

    if (!file.type.startsWith('image/')) {
      setErrorMessage('Пожалуйста, выберите файл изображения (JPG, PNG или WEBP).');
      return;
    }
    if (file.size > 5 * 1024 * 1024) {
      setErrorMessage('Размер фото не должен превышать 5 МБ.');
      return;
    }

    setErrorMessage('');
    const previewUrl = URL.createObjectURL(file);
    setAvatarPreview(previewUrl);
    setAvatarUploading(true);

    const formData = new FormData();
    formData.append('photo', file);

    try {
      const res = await axios.post('/api/tutor/onboarding/upload-photo', formData, {
        headers: { 'Content-Type': 'multipart/form-data' },
      });
      if (res.data?.success) {
        setAvatarUrl(res.data.avatar_url);
        setSuccessToast('Фотография успешно добавлена!');
        setTimeout(() => setSuccessToast(''), 3000);
      }
    } catch (err) {
      const msg = err.response?.data?.message || 'Не удалось загрузить фотографию. Попробуйте еще раз.';
      setErrorMessage(msg);
    } finally {
      setAvatarUploading(false);
    }
  };

  // ─── ЗАПРОС ЗВОНКА "НАСТРОЙКА ЗА 5 МИНУТ" ───
  const handleRequestHelp = async () => {
    if (!helpPhone.trim()) {
      setHelpError('Пожалуйста, укажите контактный номер телефона.');
      return;
    }
    setHelpSubmitting(true);
    setHelpError('');
    try {
      const res = await axios.post('/api/tutor/onboarding/request-help', {
        phone: helpPhone.trim(),
      });
      if (res.data?.success) {
        setHelpSent(true);
      } else {
        setHelpError(res.data?.message || 'Не удалось отправить заявку.');
      }
    } catch (err) {
      setHelpError(err.response?.data?.message || 'Ошибка отправки заявки. Попробуйте еще раз.');
    } finally {
      setHelpSubmitting(false);
    }
  };

  // ─── ПЕРЕХОД: ШАГ 1 -> ШАГ 2 ───
  const handleStep1Next = () => {
    setErrorMessage('');
    setStep(2);
  };

  // ─── ПЕРЕХОД: ШАГ 2 -> ШАГ 3 ───
  const handleStep2Next = async () => {
    setErrorMessage('');
    if (!userName.trim()) {
      setErrorMessage('Пожалуйста, укажите ваше имя.');
      return;
    }
    if (!primarySubject) {
      setErrorMessage('Пожалуйста, выберите предмет преподавания.');
      return;
    }

    setIsSubmitting(true);
    try {
      await axios.post('/api/tutor/onboarding/save-step1', {
        name: userName.trim(),
        subjects: [primarySubject],
        price_per_hour: pricePerHour || 40,
        experience_years: experienceYears,
        exam_specializations: selectedSpecs,
        bio: bio.trim(),
        is_public: isPublic,
      });
      setStep(3);
    } catch (err) {
      const msg = err.response?.data?.message || 'Не удалось сохранить предмет.';
      setErrorMessage(msg);
    } finally {
      setIsSubmitting(false);
    }
  };

  // ─── ПЕРЕХОД: ШАГ 3 -> ШАГ 4 ───
  const handleStep3Next = async () => {
    setErrorMessage('');

    let finalPrice = Number(pricePerHour);
    if (isCustomPrice) {
      finalPrice = Number(customPriceInput);
    }

    if (!finalPrice || finalPrice < 10) {
      setErrorMessage('Пожалуйста, укажите корректную стоимость (минимум 10 руб/час).');
      return;
    }

    setPricePerHour(finalPrice);
    setIsSubmitting(true);

    try {
      await axios.post('/api/tutor/onboarding/save-step1', {
        name: userName.trim(),
        subjects: [primarySubject],
        price_per_hour: finalPrice,
        experience_years: experienceYears,
        exam_specializations: selectedSpecs,
        bio: bio.trim(),
        is_public: isPublic,
      });
      setStep(4);
    } catch (err) {
      const msg = err.response?.data?.message || 'Не удалось сохранить стоимость.';
      setErrorMessage(msg);
    } finally {
      setIsSubmitting(false);
    }
  };

  // ─── ПЕРЕХОД: ШАГ 4 -> ШАГ 5 ───
  const handleStep4Next = async () => {
    setErrorMessage('');

    if (selectedDays.length === 0) {
      setErrorMessage('Выберите хотя бы один рабочий день.');
      return;
    }
    if (startTime >= endTime) {
      setErrorMessage('Время окончания должно быть позже времени начала.');
      return;
    }

    setIsSubmitting(true);
    try {
      await axios.post('/api/tutor/onboarding/save-step2', {
        availability_days: selectedDays,
        start_time: startTime,
        end_time: endTime,
      });
      setStep(5);
    } catch (err) {
      const msg = err.response?.data?.message || 'Не удалось сохранить график.';
      setErrorMessage(msg);
    } finally {
      setIsSubmitting(false);
    }
  };

  // ─── ПРОВЕРКА ПРОМОКОДА ───
  const handleCheckPromo = async () => {
    const code = promoCodeInput.trim().toUpperCase();
    if (!code) {
      setPromoError('Введите промокод.');
      return;
    }

    setPromoChecking(true);
    setPromoError('');

    try {
      const res = await axios.post('/api/tutor/onboarding/check-promo', { code });
      if (res.data?.success) {
        setAppliedPromo(res.data);
        setSuccessToast(`Промокод ${code} активирован!`);
        setTimeout(() => setSuccessToast(''), 3000);
      } else {
        setPromoError(res.data?.message || 'Неверный промокод.');
      }
    } catch (err) {
      setPromoError(err.response?.data?.message || 'Промокод не найден или истёк.');
    } finally {
      setPromoChecking(false);
    }
  };

  // ─── ФИНАЛЬНАЯ ОТПРАВКА АНКЕТЫ ───
  const handleCompleteOnboarding = async () => {
    setErrorMessage('');
    setIsSubmitting(true);

    try {
      const res = await axios.post('/api/tutor/onboarding/complete', {
        promo_code: appliedPromo ? promoCodeInput.trim().toUpperCase() : null,
        plan: selectedPlan || 'pro',
        is_yearly: false,
      });

      if (res.data?.success) {
        window.location.href = res.data.redirect || '/admin';
      } else {
        setErrorMessage(res.data?.message || 'Не удалось активировать тариф.');
        setShowTrialModal(false);
      }
    } catch (err) {
      const msg = err.response?.data?.message || 'Ошибка связи с сервером.';
      setErrorMessage(msg);
      setShowTrialModal(false);
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
    } catch {
      // Игнорируем
    }
    window.location.href = '/login';
  };

  const toggleDay = (dayId) => {
    setSelectedDays((prev) => {
      const exists = prev.includes(dayId);
      if (exists) {
        if (prev.length === 1) return prev;
        return prev.filter((d) => d !== dayId);
      }
      return [...prev, dayId];
    });
    setActivePreset('custom');
  };

  const applyPreset = (key) => {
    const p = SCHEDULE_PRESETS[key];
    if (p) {
      setSelectedDays(p.days);
      setStartTime(p.start);
      setEndTime(p.end);
      setActivePreset(key);
    }
  };

  const toggleSpec = (spec) => {
    setSelectedSpecs((prev) => {
      if (prev.includes(spec)) {
        if (prev.length === 1) return prev;
        return prev.filter((s) => s !== spec);
      }
      return [...prev, spec];
    });
  };

  const displayAvatar = avatarPreview || avatarUrl;

  if (isLoading) {
    return (
      <div className="min-h-screen bg-[#08090C] text-white flex items-center justify-center p-4">
        <div className="flex flex-col items-center gap-3">
          <Loader2 className="w-8 h-8 animate-spin text-[#C6FF33]" />
          <p className="text-sm text-white/70">Загрузка...</p>
        </div>
      </div>
    );
  }

  return (
    <div className="h-[100dvh] max-h-[100dvh] bg-[#08090C] text-white flex flex-col font-sans selection:bg-[#C6FF33] selection:text-black overflow-hidden">
      {/* Тост */}
      <AnimatePresence>
        {successToast && (
          <motion.div
            initial={{ opacity: 0, y: -20 }}
            animate={{ opacity: 1, y: 0 }}
            exit={{ opacity: 0, y: -20 }}
            className="fixed top-4 left-1/2 -translate-x-1/2 z-[130] max-w-sm w-[90%] bg-[#C6FF33] text-black px-4 py-3 rounded-2xl font-bold text-sm shadow-2xl flex items-center gap-2"
          >
            <CheckCircle2 className="w-5 h-5 shrink-0" />
            <span className="flex-1 leading-snug">{successToast}</span>
          </motion.div>
        )}
      </AnimatePresence>

      {/* Верхняя часть (Шапка + Прогресс) */}
      <div className="shrink-0">
        <header className="bg-[#08090C] border-b border-white/10 px-4 py-2.5">
          <div className="max-w-md mx-auto flex items-center justify-between">
            <div className="flex items-center gap-2.5">
              <div className="w-8 h-8 rounded-xl bg-[#7D39EB] flex items-center justify-center shadow-md shadow-[#7D39EB]/30 shrink-0">
                <svg width="18" height="18" viewBox="0 0 64 64" fill="none">
                  <path d="M32 10L54 32L32 54L10 32L32 10Z" stroke="#C6FF33" strokeWidth="6" strokeLinejoin="round" />
                  <path d="M32 22L42 32L32 42L22 32L32 22Z" fill="#C6FF33" />
                </svg>
              </div>
              <div className="flex flex-col">
                <span className="font-rimma font-bold text-base tracking-wider text-white uppercase leading-none">EDUSFERA</span>
                <span className="text-[9px] text-[#C6FF33] font-mono font-bold tracking-widest uppercase mt-0.5">Экосистема знаний</span>
              </div>
            </div>

            <button
              type="button"
              onClick={handleLogout}
              className="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs text-white/60 hover:text-white"
            >
              <LogOut className="w-4 h-4" />
              <span>Выйти</span>
            </button>
          </div>
        </header>

        {/* Официальный Telegram-канал Edusfera */}
        <div className="bg-gradient-to-r from-sky-500/20 via-sky-500/10 to-purple-600/20 border-b border-sky-400/30 px-3 py-1.5">
          <div className="max-w-md mx-auto flex items-center justify-between">
            <a
              href="https://t.me/edusferaby"
              target="_blank"
              rel="noopener noreferrer"
              className="w-full inline-flex items-center justify-center gap-1.5 text-xs font-semibold text-sky-300 hover:text-sky-200 transition-colors py-0.5 text-center cursor-pointer no-underline"
            >
              <span className="text-sm">✈️</span>
              <span>Телеграм-канал Edusfera.by — новости, поддержка и советы ➔</span>
            </a>
          </div>
        </div>

        {step <= 5 && (
          <div className="bg-[#0D0F14] border-b border-white/5 px-4 py-2">
            <div className="max-w-md mx-auto">
              <div className="flex items-center justify-between text-xs mb-1.5">
                <span className="font-bold text-[#C6FF33] uppercase tracking-wider">
                  Шаг {step} из 5
                </span>
                <span className="text-white/60">
                  {step === 1 && 'Фотография'}
                  {step === 2 && 'Предмет'}
                  {step === 3 && 'Стоимость'}
                  {step === 4 && 'График'}
                  {step === 5 && 'Тариф'}
                </span>
              </div>
              <div className="w-full h-1.5 bg-white/10 rounded-full overflow-hidden">
                <div
                  className="h-full bg-[#C6FF33] transition-all duration-300 rounded-full"
                  style={{ width: `${step * 20}%` }}
                />
              </div>
            </div>
          </div>
        )}
      </div>

      {/* Центральная часть: Контент шага */}
      <main className="flex-1 w-full max-w-md mx-auto px-4 py-3 min-h-0 overflow-y-auto">
        <AnimatePresence mode="wait">

          {/* ══════════════════════════════════════════════════════════ */}
          {/* ШАГ 1: ТОЛЬКО ФОТОГРАФИЯ                                  */}
          {/* ══════════════════════════════════════════════════════════ */}
          {step === 1 && (
            <motion.div
              key="step-1"
              initial={{ opacity: 0, y: 8 }}
              animate={{ opacity: 1, y: 0 }}
              exit={{ opacity: 0, y: -8 }}
              transition={{ duration: 0.15 }}
              className="space-y-4"
            >
              <div>
                <h1 className="text-xl sm:text-2xl font-bold tracking-tight text-white">
                  Ваша фотография
                </h1>
                <p className="text-xs sm:text-sm text-white/65 mt-1 leading-relaxed">
                  Загрузите портретный снимок. Анкеты с фото вызывают больше доверия у родителей и учеников.
                </p>
              </div>

              <div className="p-4 sm:p-5 rounded-2xl bg-white/[0.04] border border-white/10 flex flex-col items-center text-center space-y-3">
                <div className="relative">
                  <div className="w-28 h-28 rounded-full overflow-hidden border-2 border-white/20 bg-neutral-900 flex items-center justify-center shadow-xl">
                    {displayAvatar ? (
                      <img src={displayAvatar} alt="Фото" className="w-full h-full object-cover" />
                    ) : (
                      <div className="flex flex-col items-center justify-center text-white/40">
                        <Camera className="w-8 h-8 mb-1 text-white/50" />
                        <span className="text-[11px]">Нет фото</span>
                      </div>
                    )}

                    {avatarUploading && (
                      <div className="absolute inset-0 bg-black/80 flex items-center justify-center">
                        <Loader2 className="w-7 h-7 animate-spin text-[#C6FF33]" />
                      </div>
                    )}
                  </div>

                  <button
                    type="button"
                    onClick={() => fileInputRef.current?.click()}
                    disabled={avatarUploading}
                    className="absolute bottom-0 right-0 w-9 h-9 rounded-full bg-[#C6FF33] text-black hover:bg-[#d5ff5e] flex items-center justify-center shadow-lg cursor-pointer"
                  >
                    <Camera className="w-4 h-4 stroke-[2.5]" />
                  </button>
                  <input
                    ref={fileInputRef}
                    type="file"
                    accept="image/*"
                    onChange={handlePhotoSelect}
                    className="hidden"
                  />
                </div>

                <div className="space-y-1">
                  <div className="font-semibold text-base text-white">
                    {userName || 'Фото профиля'}
                  </div>
                  {displayAvatar ? (
                    <span className="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-emerald-500/20 text-emerald-400 text-xs font-semibold">
                      <Check className="w-3.5 h-3.5 stroke-[2.5]" /> Фото добавлено
                    </span>
                  ) : (
                    <p className="text-xs text-white/50">
                      Подходит снимок с телефона на светлом фоне
                    </p>
                  )}
                </div>

                <button
                  type="button"
                  onClick={() => fileInputRef.current?.click()}
                  disabled={avatarUploading}
                  className="w-full h-12 rounded-xl bg-white/10 hover:bg-white/15 text-white text-xs sm:text-sm font-semibold border border-white/15 transition-all flex items-center justify-center gap-2 cursor-pointer"
                >
                  <Upload className="w-4 h-4 text-[#C6FF33]" />
                  <span>{displayAvatar ? 'Изменить фотографию' : 'Загрузить фото с телефона'}</span>
                </button>
              </div>
            </motion.div>
          )}

          {/* ══════════════════════════════════════════════════════════ */}
          {/* ШАГ 2: ИМЯ, ПРЕДМЕТ И ОПЫТ                                */}
          {/* ══════════════════════════════════════════════════════════ */}
          {step === 2 && (
            <motion.div
              key="step-2"
              initial={{ opacity: 0, y: 8 }}
              animate={{ opacity: 1, y: 0 }}
              exit={{ opacity: 0, y: -8 }}
              transition={{ duration: 0.15 }}
              className="space-y-4"
            >
              <div>
                <h1 className="text-xl sm:text-2xl font-bold tracking-tight text-white">
                  Предмет преподавания
                </h1>
                <p className="text-xs sm:text-sm text-white/65 mt-1">
                  Укажите имя и дисциплину, которую вы преподаёте.
                </p>
              </div>

              {/* Формат работы кабинета (Radio Cards) */}
              <div className="space-y-2">
                <label className="block text-xs sm:text-sm font-semibold text-white">
                  Формат работы кабинета:
                </label>
                <div className="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                  {/* Вариант 1 (по умолчанию для 50+): Приватный */}
                  <div
                    onClick={() => setIsPublic(false)}
                    className={`p-3 rounded-2xl border cursor-pointer transition-all ${
                      !isPublic
                        ? 'bg-emerald-950/40 border-emerald-400 shadow-md shadow-emerald-500/10'
                        : 'bg-white/[0.04] border-white/10 hover:border-white/20'
                    }`}
                  >
                    <div className="flex items-start gap-2.5">
                      <div className="text-2xl shrink-0 mt-0.5">🔒</div>
                      <div className="min-w-0 flex-1">
                        <div className="flex items-center justify-between">
                          <span className="font-bold text-xs sm:text-sm text-white leading-tight">
                            Работаю только со своими учениками
                          </span>
                          {!isPublic && (
                            <span className="w-4 h-4 rounded-full bg-emerald-400 text-black flex items-center justify-center text-[10px] font-black shrink-0 ml-1">
                              ✓
                            </span>
                          )}
                        </div>
                        <div className="text-[11px] font-semibold text-emerald-400 mt-0.5">
                          (Приватный кабинет)
                        </div>
                        <p className="text-[11px] text-white/60 leading-relaxed mt-1">
                          Ваша анкета и цены скрыты из каталога и поисковиков. Доступ только по вашей личной ссылке.
                        </p>
                      </div>
                    </div>
                  </div>

                  {/* Вариант 2: Открытая витрина */}
                  <div
                    onClick={() => setIsPublic(true)}
                    className={`p-3 rounded-2xl border cursor-pointer transition-all ${
                      isPublic
                        ? 'bg-[#7D39EB]/20 border-[#C6FF33] shadow-md shadow-[#C6FF33]/15'
                        : 'bg-white/[0.04] border-white/10 hover:border-white/20'
                    }`}
                  >
                    <div className="flex items-start gap-2.5">
                      <div className="text-2xl shrink-0 mt-0.5">🌐</div>
                      <div className="min-w-0 flex-1">
                        <div className="flex items-center justify-between">
                          <span className="font-bold text-xs sm:text-sm text-white leading-tight">
                            Хочу получать новых учеников
                          </span>
                          {isPublic && (
                            <span className="w-4 h-4 rounded-full bg-[#C6FF33] text-black flex items-center justify-center text-[10px] font-black shrink-0 ml-1">
                              ✓
                            </span>
                          )}
                        </div>
                        <div className="text-[11px] font-semibold text-[#C6FF33] mt-0.5">
                          (Открытая витрина)
                        </div>
                        <p className="text-[11px] text-white/60 leading-relaxed mt-1">
                          Ваша анкета публикуется в каталоге Edusfera и поиске Google/Яндекс.
                        </p>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              {/* Ваше имя */}
              <div className="space-y-1.5">
                <label className="block text-xs sm:text-sm font-semibold text-white">
                  Ваше имя и фамилия:
                </label>
                <input
                  type="text"
                  value={userName}
                  onChange={(e) => setUserName(e.target.value)}
                  placeholder="Например: Галина Зайцева"
                  className="w-full h-12 px-3.5 rounded-xl bg-neutral-900 border border-white/20 text-white text-base focus:border-[#C6FF33] outline-none"
                />
              </div>

              {/* Предмет */}
              <div className="space-y-1.5">
                <label className="block text-xs sm:text-sm font-semibold text-white">
                  Какой предмет вы преподаёте?
                </label>
                <div className="relative">
                  <select
                    value={primarySubject}
                    onChange={(e) => setPrimarySubject(e.target.value)}
                    className="w-full h-12 pl-3.5 pr-10 rounded-xl bg-neutral-900 border border-white/20 text-white text-base focus:border-[#C6FF33] outline-none appearance-none cursor-pointer"
                  >
                    {POPULAR_SUBJECTS.map((sub) => (
                      <option key={sub} value={sub}>
                        {sub}
                      </option>
                    ))}
                  </select>
                  <div className="absolute inset-y-0 right-0 pr-3.5 flex items-center pointer-events-none text-white/50">
                    <ChevronDown className="w-5 h-5" />
                  </div>
                </div>
              </div>

              {/* Опыт работы */}
              <div className="space-y-1.5">
                <label className="block text-xs sm:text-sm font-semibold text-white">
                  Опыт работы репетитором:
                </label>
                <div className="grid grid-cols-4 gap-2">
                  {[
                    { val: 2, label: '1–2 года' },
                    { val: 4, label: '3–5 лет' },
                    { val: 8, label: '6–10 лет' },
                    { val: 15, label: '10+ лет' },
                  ].map((exp) => (
                    <button
                      key={exp.val}
                      type="button"
                      onClick={() => setExperienceYears(exp.val)}
                      className={`h-11 rounded-xl text-xs sm:text-sm font-semibold transition-all cursor-pointer flex items-center justify-center ${
                        experienceYears === exp.val
                          ? 'bg-[#C6FF33] text-black'
                          : 'bg-neutral-900 text-white/80 border border-white/15'
                      }`}
                    >
                      {exp.label}
                    </button>
                  ))}
                </div>
              </div>
            </motion.div>
          )}

          {/* ══════════════════════════════════════════════════════════ */}
          {/* ШАГ 3: СТОИМОСТЬ И НАПРАВЛЕНИЯ                           */}
          {/* ══════════════════════════════════════════════════════════ */}
          {step === 3 && (
            <motion.div
              key="step-3"
              initial={{ opacity: 0, y: 8 }}
              animate={{ opacity: 1, y: 0 }}
              exit={{ opacity: 0, y: -8 }}
              transition={{ duration: 0.15 }}
              className="space-y-4"
            >
              <div>
                <h1 className="text-xl sm:text-2xl font-bold tracking-tight text-white">
                  Стоимость одного урока
                </h1>
                <p className="text-xs sm:text-sm text-white/65 mt-1">
                  Укажите ставку за 1 час (60 минут) занятий.
                </p>
              </div>

              {/* Кнопки цен */}
              <div className="p-3.5 sm:p-4 rounded-2xl bg-white/[0.04] border border-white/10 space-y-3">
                <div className="grid grid-cols-3 sm:grid-cols-6 gap-2">
                  {PRESET_PRICES.map((price) => (
                    <button
                      key={price}
                      type="button"
                      onClick={() => {
                        setPricePerHour(price);
                        setIsCustomPrice(false);
                        setCustomPriceInput('');
                      }}
                      className={`h-11 rounded-xl text-sm font-bold transition-all cursor-pointer flex items-center justify-center ${
                        pricePerHour === price && !isCustomPrice
                          ? 'bg-[#C6FF33] text-black'
                          : 'bg-neutral-900 text-white/80 border border-white/15'
                      }`}
                    >
                      {price} руб
                    </button>
                  ))}
                </div>

                <div className="flex items-center gap-2 pt-1">
                  <span className="text-xs text-white/60">Своя цена:</span>
                  <input
                    type="number"
                    min="10"
                    max="1000"
                    value={customPriceInput}
                    onChange={(e) => {
                      setCustomPriceInput(e.target.value);
                      setIsCustomPrice(true);
                      const num = Number(e.target.value);
                      if (num >= 10) setPricePerHour(num);
                    }}
                    placeholder="40"
                    className="w-24 h-10 px-3 rounded-xl bg-neutral-900 border border-white/20 text-white font-bold text-sm focus:border-[#C6FF33] outline-none"
                  />
                  <span className="text-xs text-white/60">BYN / час</span>
                </div>
              </div>

              {/* Специализации и направления преподавания */}
              <div className="space-y-2">
                <div className="flex items-center justify-between">
                  <label className="block text-xs sm:text-sm font-semibold text-white">
                    Направления преподавания:
                  </label>
                  <span className="text-[11px] text-white/50">
                    {selectedSpecs.length} выбрано
                  </span>
                </div>
                <div className="flex flex-wrap gap-1.5 sm:gap-2">
                  {SPECIALIZATION_OPTIONS.map((spec) => {
                    const isSelected = selectedSpecs.includes(spec);
                    return (
                      <button
                        key={spec}
                        type="button"
                        onClick={() => toggleSpec(spec)}
                        className={`px-3 py-2 rounded-xl text-xs font-semibold transition-all cursor-pointer flex items-center gap-1.5 ${
                          isSelected
                            ? 'bg-[#C6FF33] text-black shadow-[0_0_12px_rgba(198,255,51,0.25)]'
                            : 'bg-neutral-900 text-white/80 border border-white/15 hover:border-white/30'
                        }`}
                      >
                        {isSelected && <Check className="w-3.5 h-3.5 stroke-[2.5]" />}
                        <span>{spec}</span>
                      </button>
                    );
                  })}
                </div>
              </div>
            </motion.div>
          )}

          {/* ══════════════════════════════════════════════════════════ */}
          {/* ШАГ 4: ГРАФИК ЗАНЯТИЙ                                     */}
          {/* ══════════════════════════════════════════════════════════ */}
          {step === 4 && (
            <motion.div
              key="step-4"
              initial={{ opacity: 0, y: 8 }}
              animate={{ opacity: 1, y: 0 }}
              exit={{ opacity: 0, y: -8 }}
              transition={{ duration: 0.15 }}
              className="space-y-4"
            >
              <div>
                <h1 className="text-xl sm:text-2xl font-bold tracking-tight text-white">
                  График занятий
                </h1>
                <p className="text-xs sm:text-sm text-white/65 mt-1">
                  В какие дни и часы вам удобно проводить уроки?
                </p>
              </div>

              {/* Пресеты */}
              <div className="grid grid-cols-3 gap-2">
                {Object.entries(SCHEDULE_PRESETS).map(([k, p]) => {
                  const isSelected = activePreset === k;
                  return (
                    <button
                      key={k}
                      type="button"
                      onClick={() => applyPreset(k)}
                      className={`p-2.5 rounded-xl border text-left transition-all cursor-pointer ${
                        isSelected
                          ? 'bg-[#C6FF33]/15 border-[#C6FF33] text-white'
                          : 'bg-neutral-900 border-white/15 text-white/70'
                      }`}
                    >
                      <div className="font-bold text-[11px] uppercase tracking-wide text-white">{p.label}</div>
                      <div className="text-[10px] text-white/50 mt-0.5">{p.start}–{p.end}</div>
                    </button>
                  );
                })}
              </div>

              {/* Дни недели */}
              <div className="space-y-1">
                <span className="text-xs font-semibold text-white">Рабочие дни:</span>
                <div className="grid grid-cols-7 gap-1.5">
                  {WEEKDAYS.map((w) => {
                    const isSel = selectedDays.includes(w.id);
                    return (
                      <button
                        key={w.id}
                        type="button"
                        onClick={() => toggleDay(w.id)}
                        className={`h-11 rounded-xl flex items-center justify-center font-bold text-xs transition-all cursor-pointer ${
                          isSel
                            ? 'bg-[#C6FF33] text-black'
                            : 'bg-neutral-900 text-white/60 border border-white/15'
                        }`}
                      >
                        {w.label}
                      </button>
                    );
                  })}
                </div>
              </div>

              {/* Время начала и окончания */}
              <div className="grid grid-cols-2 gap-2.5 pt-1">
                <div className="space-y-1">
                  <span className="text-xs text-white/60">Время начала:</span>
                  <input
                    type="time"
                    value={startTime}
                    onChange={(e) => {
                      setStartTime(e.target.value);
                      setActivePreset('custom');
                    }}
                    className="w-full h-11 px-3 rounded-xl bg-neutral-900 border border-white/20 text-white font-mono text-sm focus:border-[#C6FF33] outline-none"
                  />
                </div>
                <div className="space-y-1">
                  <span className="text-xs text-white/60">Время окончания:</span>
                  <input
                    type="time"
                    value={endTime}
                    onChange={(e) => {
                      setEndTime(e.target.value);
                      setActivePreset('custom');
                    }}
                    className="w-full h-11 px-3 rounded-xl bg-neutral-900 border border-white/20 text-white font-mono text-sm focus:border-[#C6FF33] outline-none"
                  />
                </div>
              </div>
            </motion.div>
          )}

          {/* ══════════════════════════════════════════════════════════ */}
          {/* ШАГ 5: ВЫБОР ТАРИФА И ПРОМОКОД                             */}
          {/* ══════════════════════════════════════════════════════════ */}
          {step === 5 && (
            <motion.div
              key="step-5"
              initial={{ opacity: 0, y: 8 }}
              animate={{ opacity: 1, y: 0 }}
              exit={{ opacity: 0, y: -8 }}
              transition={{ duration: 0.15 }}
              className="space-y-3"
            >
              <div>
                <h1 className="text-xl sm:text-2xl font-bold tracking-tight text-white">
                  Выберите тариф подписки
                </h1>
                <p className="text-xs sm:text-sm text-white/65 mt-0.5">
                  Первые 14 дней бесплатно. Карта не требуется.
                </p>
              </div>

              {/* Сетка тарифов (3 карточки) */}
              <div className="space-y-2">
                {SUBSCRIPTION_PLANS.map((p) => {
                  const isSelected = selectedPlan === p.id;
                  return (
                    <div
                      key={p.id}
                      onClick={() => setSelectedPlan(p.id)}
                      className={`p-3 rounded-2xl border transition-all cursor-pointer relative ${
                        isSelected
                          ? 'bg-neutral-900 border-[#C6FF33] shadow-[0_0_15px_rgba(198,255,51,0.2)]'
                          : 'bg-white/[0.03] border-white/10 hover:border-white/20'
                      }`}
                    >
                      <div className="flex items-center justify-between">
                        <div className="flex items-center gap-2.5">
                          <div
                            className={`w-5 h-5 rounded-full border flex items-center justify-center transition-all ${
                              isSelected
                                ? 'border-[#C6FF33] bg-[#C6FF33] text-black'
                                : 'border-white/30 text-transparent'
                            }`}
                          >
                            <Check className="w-3.5 h-3.5 stroke-[3]" />
                          </div>
                          <div>
                            <div className="flex items-center gap-1.5">
                              <span className="font-bold text-sm text-white">{p.title}</span>
                              <span
                                className={`text-[10px] font-bold px-1.5 py-0.5 rounded-md ${
                                  isSelected
                                    ? 'bg-[#C6FF33]/20 text-[#C6FF33]'
                                    : 'bg-white/10 text-white/70'
                                }`}
                              >
                                {p.badge}
                              </span>
                            </div>
                            <span className="text-[11px] text-white/50 block">
                              {p.tagline}
                            </span>
                          </div>
                        </div>

                        <div className="text-right shrink-0">
                          <span className="text-sm font-black text-white">{p.price} BYN</span>
                          <span className="text-[10px] text-white/50 block">/ месяц</span>
                        </div>
                      </div>

                      {/* Ключевые возможности */}
                      <div className="mt-2 pt-2 border-t border-white/5 space-y-1 pl-7">
                        {p.features.map((feat, idx) => (
                          <div key={idx} className="flex items-center gap-1.5 text-[11px] text-white/70">
                            <span className="w-1 h-1 rounded-full bg-[#C6FF33] shrink-0" />
                            <span>{feat}</span>
                          </div>
                        ))}
                      </div>
                    </div>
                  );
                })}
              </div>

              {/* Ввод промокода */}
              <div className="p-3 rounded-2xl bg-white/[0.04] border border-white/10 space-y-2">
                <div className="flex items-center justify-between text-xs font-semibold text-white">
                  <span className="flex items-center gap-1.5">
                    <Tag className="w-3.5 h-3.5 text-[#C6FF33]" />
                    <span>Промокод (если есть):</span>
                  </span>
                  {appliedPromo && (
                    <span className="text-emerald-400 font-bold text-[11px]">✓ Активирован</span>
                  )}
                </div>

                <div className="flex gap-2">
                  <input
                    type="text"
                    value={promoCodeInput}
                    onChange={(e) => {
                      setPromoCodeInput(e.target.value.toUpperCase());
                      setPromoError('');
                    }}
                    placeholder="EDUSFERA2026"
                    className="flex-1 h-10 px-3 rounded-xl bg-neutral-900 border border-white/20 text-white font-mono uppercase text-xs focus:border-[#C6FF33] outline-none"
                  />
                  <button
                    type="button"
                    onClick={handleCheckPromo}
                    disabled={promoChecking || !promoCodeInput.trim()}
                    className="px-4 h-10 rounded-xl bg-white/10 hover:bg-[#C6FF33] hover:text-black text-white font-bold text-xs uppercase border border-white/15 transition-all cursor-pointer disabled:opacity-40"
                  >
                    {promoChecking ? <Loader2 className="w-3.5 h-3.5 animate-spin" /> : 'Применить'}
                  </button>
                </div>

                {promoError && (
                  <p className="text-xs text-rose-400 font-medium">{promoError}</p>
                )}
              </div>

              {/* 14 дней бесплатно плашка */}
              <div className="p-2.5 rounded-xl bg-[#C6FF33]/10 border border-[#C6FF33]/30 text-xs text-white/80 flex items-center gap-2">
                <ShieldCheck className="w-4 h-4 text-[#C6FF33] shrink-0" />
                <span className="text-[11px] leading-tight">
                  <strong className="text-[#C6FF33]">14 дней бесплатно.</strong> Без привязки карты. Все функции активны сразу.
                </span>
              </div>
            </motion.div>
          )}

          {/* ══════════════════════════════════════════════════════════ */}
          {/* ШАГ 6: УСПЕШНОЕ ЗАВЕРШЕНИЕ                                 */}
          {/* ══════════════════════════════════════════════════════════ */}
          {step === 6 && (
            <motion.div
              key="step-6"
              initial={{ opacity: 0, scale: 0.98 }}
              animate={{ opacity: 1, scale: 1 }}
              transition={{ duration: 0.2 }}
              className="text-center py-6 space-y-4"
            >
              <div className="w-16 h-16 mx-auto rounded-full bg-[#C6FF33]/20 border border-[#C6FF33] flex items-center justify-center text-[#C6FF33]">
                <Check className="w-8 h-8 stroke-[3]" />
              </div>

              <div className="space-y-1">
                <h1 className="text-xl sm:text-2xl font-bold text-white">
                  Анкета успешно отправлена!
                </h1>
                <p className="text-xs sm:text-sm text-white/70 max-w-sm mx-auto">
                  Администратор проверит данные в течение 2–4 часов. Ваш кабинет полностью активирован!
                </p>
              </div>

              <button
                type="button"
                onClick={handleFinishAndRedirect}
                className="w-full h-13 rounded-xl bg-[#C6FF33] text-black font-bold text-sm uppercase tracking-wider hover:bg-[#d5ff5e] transition-all cursor-pointer shadow-[0_0_20px_rgba(198,255,51,0.4)]"
              >
                Перейти в личный кабинет
              </button>
            </motion.div>
          )}

        </AnimatePresence>
      </main>

      {/* ── НИЖНЯЯ ПАНЕЛЬ ДЕЙСТВИЙ (ВСЕГДА В ПОЛЕ ЗРЕНИЯ БЕЗ ПРОКРУТКИ) ── */}
      {step <= 5 && (
        <div
          className="shrink-0 bg-[#08090C]/95 backdrop-blur-md border-t border-white/10 px-4 pt-3"
          style={{
            paddingBottom: 'calc(max(1.25rem, env(safe-area-inset-bottom, 1.25rem)) + 0.75rem)',
          }}
        >
          <div className="max-w-md mx-auto space-y-2">
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
                      : step === 3
                        ? handleStep3Next
                        : step === 4
                          ? handleStep4Next
                          : () => {
                              setErrorMessage('');
                              setShowTrialModal(true);
                            }
                }
                disabled={isSubmitting}
                className="flex-1 h-12 px-5 rounded-xl bg-[#C6FF33] text-black hover:bg-[#d5ff5e] font-bold text-sm tracking-wide transition-all flex items-center justify-center gap-2 cursor-pointer shadow-[0_0_15px_rgba(198,255,51,0.3)] disabled:opacity-50"
              >
                {isSubmitting ? (
                  <>
                    <Loader2 className="w-4 h-4 animate-spin text-black" />
                    <span>Сохранение...</span>
                  </>
                ) : (
                  <>
                    <span>
                      {step === 5
                        ? `Выбрать «${SUBSCRIPTION_PLANS.find((p) => p.id === selectedPlan)?.title || 'Pro'}»`
                        : 'Продолжить'}
                    </span>
                    <ArrowRight className="w-4 h-4 stroke-[2.5]" />
                  </>
                )}
              </button>
            </div>
          </div>
        </div>
      )}

      {/* ── ВСплывающее ОКНО: 14 ДНЕЙ БЕСПЛАТНОГО ДОСТУПА ── */}
      <AnimatePresence>
        {showTrialModal && (
          <motion.div
            initial={{ opacity: 0 }}
            animate={{ opacity: 1 }}
            exit={{ opacity: 0 }}
            className="fixed inset-0 z-[140] bg-black/85 backdrop-blur-md flex items-center justify-center p-4"
          >
            <motion.div
              initial={{ scale: 0.9, y: 20 }}
              animate={{ scale: 1, y: 0 }}
              exit={{ scale: 0.9, y: 20 }}
              className="w-full max-w-sm rounded-3xl bg-[#12141A] border border-[#C6FF33]/40 p-5 sm:p-6 text-center space-y-4 shadow-[0_10px_40px_rgba(0,0,0,0.8),0_0_30px_rgba(198,255,51,0.2)]"
            >
              <div className="w-16 h-16 mx-auto rounded-full bg-[#C6FF33]/15 border-2 border-[#C6FF33] flex items-center justify-center text-3xl shadow-[0_0_20px_rgba(198,255,51,0.3)]">
                🎉
              </div>

              <div className="space-y-1.5">
                <h3 className="text-xl sm:text-2xl font-black text-white tracking-tight">
                  Поздравляем!
                </h3>
                <p className="text-xs sm:text-sm text-white/85 leading-relaxed">
                  Вам предоставлен{' '}
                  <span className="text-[#C6FF33] font-bold">14-дневный бесплатный период</span>{' '}
                  для теста нашей платформы по тарифу{' '}
                  <span className="text-white font-bold">
                    «{SUBSCRIPTION_PLANS.find((p) => p.id === selectedPlan)?.title || 'Pro'}»
                  </span>.
                </p>
              </div>

              <div className="py-2.5 px-3 rounded-2xl bg-white/[0.04] border border-white/10 text-[11px] text-white/70 space-y-1.5 text-left">
                <div className="flex items-center gap-1.5 text-emerald-400 font-semibold">
                  <CheckCircle2 className="w-3.5 h-3.5 shrink-0" />
                  <span>Привязка карты не требуется</span>
                </div>
                <div className="flex items-center gap-1.5 text-white/80">
                  <CheckCircle2 className="w-3.5 h-3.5 shrink-0 text-[#C6FF33]" />
                  <span>Полный доступ ко всем функциям тарифа</span>
                </div>
                <div className="flex items-start gap-1.5 text-white/70 pt-1.5 border-t border-white/10 text-[11px] leading-snug">
                  <Clock className="w-3.5 h-3.5 shrink-0 text-amber-400 mt-0.5" />
                  <span>После окончания пробного периода вам будет предложено продолжить тариф на платной основе.</span>
                </div>
              </div>

              <button
                type="button"
                disabled={isSubmitting}
                onClick={handleCompleteOnboarding}
                className="w-full h-12 rounded-xl bg-[#C6FF33] text-black font-bold text-xs uppercase tracking-wider hover:bg-[#d5ff5e] transition-all cursor-pointer flex items-center justify-center gap-2 shadow-[0_0_20px_rgba(198,255,51,0.4)] disabled:opacity-50"
              >
                {isSubmitting ? (
                  <>
                    <Loader2 className="w-4 h-4 animate-spin text-black" />
                    <span>Активация кабинета...</span>
                  </>
                ) : (
                  <>
                    <span>Перейти в личный кабинет</span>
                    <ArrowRight className="w-4 h-4 stroke-[2.5]" />
                  </>
                )}
              </button>
            </motion.div>
          </motion.div>
        )}
      </AnimatePresence>

      {/* Модальное окно: Настройка по звонку за 5 минут */}
      <AnimatePresence>
        {showHelpModal && (
          <div className="fixed inset-0 z-[150] flex items-center justify-center p-4 bg-black/80 backdrop-blur-md">
            <motion.div
              initial={{ opacity: 0, scale: 0.95 }}
              animate={{ opacity: 1, scale: 1 }}
              exit={{ opacity: 0, scale: 0.95 }}
              className="relative w-full max-w-sm rounded-2xl bg-[#12141A] border border-amber-500/30 p-5 shadow-2xl"
            >
              <button
                type="button"
                onClick={() => setShowHelpModal(false)}
                className="absolute top-4 right-4 text-white/50 hover:text-white"
              >
                <X className="w-5 h-5" />
              </button>

              {!helpSent ? (
                <div className="space-y-4">
                  <div className="flex items-center gap-3">
                    <div className="w-10 h-10 rounded-xl bg-amber-500/20 text-amber-400 flex items-center justify-center text-xl shrink-0">
                      📞
                    </div>
                    <div>
                      <h3 className="text-base font-bold text-white">
                        Настройка по звонку за 5 минут
                      </h3>
                      <p className="text-xs text-amber-400/80">
                        Бесплатная помощь методиста Edusfera
                      </p>
                    </div>
                  </div>

                  <p className="text-xs text-white/70 leading-relaxed">
                    Трудно разобраться? Нажмите кнопку ниже — наш специалист перезвонит вам прямо сейчас и поможет заполнить анкету, расписание и цены прямо во время разговора.
                  </p>

                  <div className="space-y-2">
                    <label className="block text-xs font-semibold text-white/90">
                      Номер телефона для звонка:
                    </label>
                    <input
                      type="text"
                      value={helpPhone}
                      onChange={(e) => setHelpPhone(e.target.value)}
                      placeholder="+375 (29) 123-45-67"
                      className="w-full h-11 px-3.5 rounded-xl bg-neutral-900 border border-white/20 text-white text-sm focus:border-amber-400 outline-none"
                    />
                    {helpError && (
                      <p className="text-xs text-red-400">{helpError}</p>
                    )}
                  </div>

                  <button
                    type="button"
                    onClick={handleRequestHelp}
                    disabled={helpSubmitting}
                    className="w-full h-11 rounded-xl bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-400 hover:to-amber-500 text-black font-bold text-xs sm:text-sm flex items-center justify-center gap-2 transition-all disabled:opacity-50 cursor-pointer"
                  >
                    {helpSubmitting ? (
                      <>
                        <Loader2 className="w-4 h-4 animate-spin text-black" />
                        <span>Отправка...</span>
                      </>
                    ) : (
                      <span>Позвоните мне и настройте всё →</span>
                    )}
                  </button>
                </div>
              ) : (
                <div className="text-center py-4 space-y-3">
                  <div className="w-12 h-12 mx-auto rounded-full bg-emerald-500/20 text-emerald-400 flex items-center justify-center text-2xl font-bold">
                    ✓
                  </div>
                  <h3 className="text-base font-bold text-white">
                    Заявка принята!
                  </h3>
                  <p className="text-xs text-white/70 leading-relaxed">
                    Методист Edusfera уже получил ваше обращение и набирает номер: <br />
                    <span className="font-mono text-emerald-400 font-bold">{helpPhone}</span>.<br />
                    Ожидайте звонка в течение 5 минут.
                  </p>
                  <button
                    type="button"
                    onClick={() => setShowHelpModal(false)}
                    className="px-4 py-2 rounded-xl bg-white/10 hover:bg-white/15 text-white text-xs font-semibold cursor-pointer"
                  >
                    Понятно, жду звонка
                  </button>
                </div>
              )}
            </motion.div>
          </div>
        )}
      </AnimatePresence>
    </div>
  );
}
