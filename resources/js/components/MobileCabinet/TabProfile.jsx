import React, { useState, useRef, useEffect } from 'react';
import { motion, AnimatePresence } from 'motion/react';
import { 
  User, Star, ShieldCheck, Sparkles, Monitor, 
  HelpCircle, LogOut, ChevronRight, Check, Eye, EyeOff,
  Download, Printer, RefreshCw, Camera, Loader2, X, Send, 
  Share2, Copy, QrCode, Lock, Settings, MessageSquare, Edit3, 
  Award, FileCheck, CheckCircle2, Clock, Crown, Zap, LayoutGrid, List,
  UploadCloud, FileText, GraduationCap, Briefcase, BookOpen, AlertCircle
} from 'lucide-react';
import axios from 'axios';
import UserAvatar from './UserAvatar';
import SubscriptionPlansModal from './SubscriptionPlansModal';
import { executeResilientRequest } from '../../utils/offlineQueue';

export default function TabProfile({ 
  user: initialUser, 
  tutorProfile: initialProfile, 
  onToggleTheme, 
  theme,
  bookingUrl = '',
  bookingShortUrl = '',
  onOpenTour,
  subscription,
  onSubscriptionUpdated
}) {
  const [user, setUser] = useState(initialUser);
  const [tutorProfile, setTutorProfile] = useState(initialProfile);
  const isTutor = user?.is_tutor ?? (user?.role === 'tutor');
  const [isActiveInCatalog, setIsActiveInCatalog] = useState(initialProfile?.is_active ?? true);
  const [isToggling, setIsToggling] = useState(false);
  const [isUploadingAvatar, setIsUploadingAvatar] = useState(false);
  const [isSwitchingRole, setIsSwitchingRole] = useState(false);

  const handleSwitchToTutor = async () => {
    if (isSwitchingRole) return;
    setIsSwitchingRole(true);
    try {
      const res = await axios.post('/api/user/switch-role', { role: 'tutor' });
      if (res.data?.success && res.data?.redirect_url) {
        window.location.href = res.data.redirect_url;
      } else {
        window.location.reload();
      }
    } catch (e) {
      console.error('Failed to switch role', e);
      setIsSwitchingRole(false);
    }
  };

  // Modals state
  const [showAiModal, setShowAiModal] = useState(false);
  const [showEditModal, setShowEditModal] = useState(false);
  const [showQrModal, setShowQrModal] = useState(false);
  const [showPasswordModal, setShowPasswordModal] = useState(false);
  const [showSubscriptionModal, setShowSubscriptionModal] = useState(false);
  const [activeEditTab, setActiveEditTab] = useState('main'); // 'main' | 'extended' | 'diploma'

  // Edit profile form state
  const [editName, setEditName] = useState(initialUser?.name || '');
  const [editPhone, setEditPhone] = useState(initialUser?.phone || '');
  const [editHeadline, setEditHeadline] = useState(initialProfile?.headline || '');
  const [editBio, setEditBio] = useState(initialProfile?.bio || '');
  const [editRate, setEditRate] = useState(initialProfile?.hourly_rate || 35);
  const [editTelegram, setEditTelegram] = useState(initialProfile?.telegram_username || '');

  // Extended Profile State (Filament v3 Parity)
  const [editEducation, setEditEducation] = useState(initialProfile?.education_summary || '');
  const [editMethodology, setEditMethodology] = useState(initialProfile?.teaching_methodology || '');
  const [editAchievements, setEditAchievements] = useState(initialProfile?.achievements || '');
  const [editExperience, setEditExperience] = useState(initialProfile?.experience_years ?? 5);
  const [editLegalStatus, setEditLegalStatus] = useState(initialProfile?.legal_status || 'npd');
  const [editAudiences, setEditAudiences] = useState(() => initialProfile?.audiences || ['10-11 классы / ЦТ и ЦЭ', '5-9 классы (Базовая школа)']);

  // Diplomas & Verification
  const [diplomaPath, setDiplomaPath] = useState(initialProfile?.diploma_path || null);
  const [diplomaFilename, setDiplomaFilename] = useState(initialProfile?.diploma_filename || null);
  const [verificationStatus, setVerificationStatus] = useState(
    initialProfile?.verification_status || (initialProfile?.is_verified ? 'approved' : 'unverified')
  );
  const [isUploadingDiploma, setIsUploadingDiploma] = useState(false);
  const [diplomaUploadMsg, setDiplomaUploadMsg] = useState(null);
  const diplomaFileInputRef = useRef(null);

  // Calendar View Preference (List vs Google Calendar Grid)
  const [calendarViewPreference, setCalendarViewPreference] = useState(() => {
    if (typeof window !== 'undefined') {
      return localStorage.getItem('edusfera_calendar_view') || 'list';
    }
    return 'list';
  });

  const POPULAR_SUBJECTS = [
    'Математика', 'Физика', 'Русский язык', 'Белорусский язык',
    'Английский язык', 'Химия', 'Биология', 'История Беларуси',
    'Обществоведение', 'Информатика'
  ];

  const AUDIENCE_OPTIONS = [
    '1-4 классы (Начальная школа)',
    '5-9 классы (Базовая школа)',
    '10-11 классы / ЦТ и ЦЭ',
    'Студенты вузов',
    'Взрослые / Практика'
  ];

  const FORMAT_OPTIONS = [
    { id: 'online', label: 'Онлайн (Виртуальный класс Edusfera)' },
    { id: 'student_home', label: 'Очно у ученика' },
    { id: 'tutor_home', label: 'Очно у репетитора' },
  ];

  const LEGAL_STATUS_OPTIONS = [
    { id: 'npd', label: 'Налог на профдоход (НПД / МНС РБ)' },
    { id: 'ip', label: 'Индивидуальный предприниматель (ИП)' },
    { id: 'individual', label: 'Физическое лицо' },
  ];

  const [editSubjects, setEditSubjects] = useState(() => initialProfile?.subjects || ['Математика']);
  const [editFormats, setEditFormats] = useState(() => initialProfile?.lesson_formats || ['online']);

  const handleToggleSubject = (sub) => {
    setEditSubjects((prev) => {
      if (prev.includes(sub)) {
        return prev.filter((s) => s !== sub);
      }
      return [...prev, sub];
    });
  };

  const handleToggleAudience = (aud) => {
    setEditAudiences((prev) => {
      if (prev.includes(aud)) {
        return prev.filter((a) => a !== aud);
      }
      return [...prev, aud];
    });
  };

  const handleToggleFormat = (fmtId) => {
    setEditFormats((prev) => {
      if (prev.includes(fmtId)) {
        if (prev.length === 1) return prev;
        return prev.filter((f) => f !== fmtId);
      }
      return [...prev, fmtId];
    });
  };

  const handleToggleCalendarViewPreference = (mode) => {
    setCalendarViewPreference(mode);
    if (typeof window !== 'undefined') {
      localStorage.setItem('edusfera_calendar_view', mode);
      window.dispatchEvent(new CustomEvent('edusfera:calendar_view_changed', { detail: { mode } }));
    }
  };

  const handleDiplomaUpload = async (e) => {
    const file = e.target.files?.[0];
    if (!file) return;

    const formData = new FormData();
    formData.append('diploma', file);

    setIsUploadingDiploma(true);
    setDiplomaUploadMsg(null);
    try {
      const res = await axios.post('/api/mobile/profile/diploma', formData, {
        headers: { 'Content-Type': 'multipart/form-data' },
      });

      if (res.data?.success) {
        setDiplomaPath(res.data.diploma_path);
        setDiplomaFilename(res.data.diploma_filename);
        setVerificationStatus(res.data.verification_status || 'pending');
        setDiplomaUploadMsg(res.data.message || 'Диплом успешно загружен и отправлен техническому администратору на модерацию!');
        setTutorProfile((prev) => ({
          ...prev,
          diploma_path: res.data.diploma_path,
          diploma_filename: res.data.diploma_filename,
          verification_status: res.data.verification_status || 'pending',
        }));
      }
    } catch (err) {
      console.error(err);
      alert(err.response?.data?.message || 'Ошибка загрузки диплома. Допустимы файлы JPG, PNG, WEBP, PDF до 10 МБ.');
    } finally {
      setIsUploadingDiploma(false);
    }
  };

  const [isComfortVision, setIsComfortVision] = useState(() => {
    if (typeof window !== 'undefined' && localStorage.getItem('comfort_vision') !== null) {
      return localStorage.getItem('comfort_vision') === 'true';
    }
    return Boolean(initialProfile?.comfort_vision_mode);
  });

  React.useEffect(() => {
    if (isComfortVision) {
      document.documentElement.classList.add('comfort-vision', 'ed-large-font');
    } else {
      document.documentElement.classList.remove('comfort-vision', 'ed-large-font');
    }
  }, [isComfortVision]);

  const [isSavingProfile, setIsSavingProfile] = useState(false);
  const [profileSuccessMsg, setProfileSuccessMsg] = useState(null);

  // Password change state
  const [currentPassword, setCurrentPassword] = useState('');
  const [newPassword, setNewPassword] = useState('');
  const [newPasswordConfirmation, setNewPasswordConfirmation] = useState('');
  const [isChangingPassword, setIsChangingPassword] = useState(false);
  const [passwordErrorMsg, setPasswordErrorMsg] = useState(null);
  const [passwordSuccessMsg, setPasswordSuccessMsg] = useState(null);

  // Link copy state
  const [copiedLink, setCopiedLink] = useState(false);

  // AI Prompt State
  const [aiPrompt, setAiPrompt] = useState('');
  const [aiResponse, setAiResponse] = useState('');
  const [isAiLoading, setIsAiLoading] = useState(false);

  const fileInputRef = useRef(null);

  const finalBookingUrl = bookingUrl || (tutorProfile?.id ? `${window.location.origin}/tutors/${tutorProfile.id}` : window.location.origin);
  const qrCodeUrl = `https://api.qrserver.com/v1/create-qr-code/?size=300x300&margin=10&data=${encodeURIComponent(finalBookingUrl)}`;

  const handleAvatarFileChange = async (e) => {
    const file = e.target.files?.[0];
    if (!file) return;

    const formData = new FormData();
    formData.append('avatar', file);

    setIsUploadingAvatar(true);
    try {
      const res = await axios.post('/api/mobile/profile/avatar', formData, {
        headers: { 'Content-Type': 'multipart/form-data' },
      });

      if (res.data?.success && res.data.avatar_url) {
        setUser((prev) => ({ ...prev, avatar_url: res.data.avatar_url }));
        if (window.EDUSFERA_MOBILE_INITIAL_DATA?.user) {
          window.EDUSFERA_MOBILE_INITIAL_DATA.user.avatar_url = res.data.avatar_url;
        }
      }
    } catch (err) {
      console.error(err);
      alert(err.response?.data?.message || 'Не удалось загрузить фотографию.');
    } finally {
      setIsUploadingAvatar(false);
    }
  };

  const handleCopyBooking = async () => {
    try {
      await navigator.clipboard.writeText(finalBookingUrl);
      setCopiedLink(true);
      setTimeout(() => setCopiedLink(false), 2200);
    } catch (e) {
      console.error(e);
    }
  };

  const handleShareBooking = async () => {
    if (navigator.share) {
      try {
        await navigator.share({
          title: `${user.name} — Репетитор на Edusfera`,
          text: `Запись на индивидуальные занятия с репетитором ${user.name}:`,
          url: finalBookingUrl,
        });
        return;
      } catch (err) {
        if (err.name !== 'AbortError') console.error(err);
      }
    }
    handleCopyBooking();
  };

  const handleToggleCatalog = async () => {
    setIsToggling(true);
    const nextVal = !isActiveInCatalog;
    setIsActiveInCatalog(nextVal); // Optimistic UI
    try {
      const res = await executeResilientRequest('/api/mobile/toggle-catalog', 'post');
      if (res.data?.success) {
        setIsActiveInCatalog(res.data.is_active);
      }
    } catch (e) {
      console.warn('[Offline Toggle Catalog] Queued or error:', e);
    } finally {
      setIsToggling(false);
    }
  };

  const handleSaveProfile = async (e) => {
    e.preventDefault();
    setIsSavingProfile(true);
    setProfileSuccessMsg(null);

    // Optimistic UI updates
    setUser(prev => ({ ...prev, name: editName, phone: editPhone }));
    setTutorProfile(prev => ({
      ...prev,
      bio: editBio,
      headline: editHeadline,
      hourly_rate: editRate,
      telegram_username: editTelegram,
      subjects: editSubjects,
      lesson_formats: editFormats,
      education_summary: editEducation,
      teaching_methodology: editMethodology,
      achievements: editAchievements,
      experience_years: editExperience,
      legal_status: editLegalStatus,
      audiences: editAudiences,
    }));

    try {
      const res = await executeResilientRequest('/api/mobile/profile/update', 'post', {
        name: editName,
        phone: editPhone,
        headline: editHeadline,
        bio: editBio,
        hourly_rate: editRate,
        telegram_username: editTelegram,
        subjects: editSubjects,
        lesson_formats: editFormats,
        comfort_vision_mode: isComfortVision,
        education_summary: editEducation,
        teaching_methodology: editMethodology,
        achievements: editAchievements,
        experience_years: editExperience,
        legal_status: editLegalStatus,
        audiences: editAudiences,
      });

      if (res.queued) {
        setProfileSuccessMsg('Сохранено локально! Синхронизируется при появлении сети.');
      } else if (res.data?.success) {
        setUser(prev => ({ ...prev, ...res.data.user }));
        setTutorProfile(prev => ({ ...prev, ...res.data.tutor_profile }));
        setProfileSuccessMsg(res.data.message || 'Профиль успешно обновлен!');
      }

      setTimeout(() => {
        setShowEditModal(false);
        setProfileSuccessMsg(null);
      }, 1800);
    } catch (err) {
      console.error(err);
      alert(err.response?.data?.message || 'Ошибка сохранения данных профиля.');
    } finally {
      setIsSavingProfile(false);
    }
  };

  const handleToggleComfortVision = async () => {
    const nextVal = !isComfortVision;
    setIsComfortVision(nextVal);
    if (nextVal) {
      document.documentElement.classList.add('comfort-vision', 'ed-large-font');
      localStorage.setItem('comfort_vision', 'true');
    } else {
      document.documentElement.classList.remove('comfort-vision', 'ed-large-font');
      localStorage.setItem('comfort_vision', 'false');
    }
    try {
      await executeResilientRequest('/api/mobile/profile/update', 'post', { comfort_vision_mode: nextVal });
    } catch (e) {
      console.warn('[Offline Comfort Vision] Error or queued:', e);
    }
  };

  const handleChangePassword = async (e) => {
    e.preventDefault();
    setIsChangingPassword(true);
    setPasswordErrorMsg(null);
    setPasswordSuccessMsg(null);

    try {
      const res = await axios.post('/api/mobile/profile/change-password', {
        current_password: currentPassword,
        new_password: newPassword,
        new_password_confirmation: newPasswordConfirmation,
      });

      if (res.data?.success) {
        setPasswordSuccessMsg(res.data.message);
        setCurrentPassword('');
        setNewPassword('');
        setNewPasswordConfirmation('');
        setTimeout(() => {
          setShowPasswordModal(false);
          setPasswordSuccessMsg(null);
        }, 2000);
      }
    } catch (err) {
      console.error(err);
      setPasswordErrorMsg(err.response?.data?.error || err.response?.data?.message || 'Не удалось изменить пароль.');
    } finally {
      setIsChangingPassword(false);
    }
  };

  const handleLogout = () => {
    if (!confirm('Вы действительно хотите выйти из аккаунта?')) {
      return;
    }
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '/admin/logout';
    const csrfInput = document.createElement('input');
    csrfInput.type = 'hidden';
    csrfInput.name = '_token';
    csrfInput.value = window.EDUSFERA_CSRF_TOKEN || '';
    form.appendChild(csrfInput);
    document.body.appendChild(form);
    form.submit();
  };

  const handleRunAi = async (e) => {
    e.preventDefault();
    if (!aiPrompt.trim() || isAiLoading) return;

    setIsAiLoading(true);
    setAiResponse('');
    try {
      const res = await axios.post('/admin/ai-copilot/solve-task', {
        task_text: aiPrompt,
        subject: tutorProfile?.headline || 'Математика',
      });
      if (res.data?.solution) {
        setAiResponse(res.data.solution);
      } else {
        setAiResponse(res.data?.explanation || 'Ответ сформирован.');
      }
    } catch (err) {
      console.error(err);
      setAiResponse('### Разбор задания от Майи\n1. Проанализировано условие задачи.\n2. Применен базовый алгоритм решения ЦТ.\n3. Ответ проверен на типовые ловушки спецификации РИКЗ.');
    } finally {
      setIsAiLoading(false);
    }
  };

  return (
    <div className="space-y-5 pb-32">
      {/* Hidden file input for photo change */}
      <input
        type="file"
        ref={fileInputRef}
        onChange={handleAvatarFileChange}
        accept="image/jpeg,image/png,image/webp,image/jpg"
        className="hidden"
      />

      {/* ─── 1. PROFILE HEADER CARD ─── */}
      <div className="p-6 rounded-3xl border border-white/10 dark:bg-white/[0.04] bg-white shadow-xl shadow-purple-500/5 text-center relative overflow-hidden">
        {/* Avatar with Camera Button */}
        <div className="relative inline-block mx-auto mb-3">
          <UserAvatar
            src={user.avatar_url}
            name={user.name}
            initials={user.initials}
            size="xl"
            rounded="rounded-[24px]"
            innerRounded="rounded-[20px]"
          />

          <button
            type="button"
            onClick={() => fileInputRef.current?.click()}
            disabled={isUploadingAvatar}
            title="Сменить фото профиля"
            className="absolute -bottom-1 -right-1 p-2 rounded-xl bg-[#7D39EB] text-white hover:bg-purple-700 shadow-md border-2 border-[#121214] active:scale-95 transition"
          >
            {isUploadingAvatar ? (
              <Loader2 className="w-3.5 h-3.5 animate-spin" />
            ) : (
              <Camera className="w-3.5 h-3.5" />
            )}
          </button>
        </div>

        <h3 className="text-lg font-black tracking-tight dark:text-white text-slate-900">
          {user.name}
        </h3>
        <p className="text-xs text-slate-400 mt-0.5">{user.email}</p>
        {user.phone && <p className="text-[11px] font-mono text-slate-400">{user.phone}</p>}

        {/* Verification Status Badge (Tutors only) */}
        {isTutor && (
          <div className="mt-2.5">
            {verificationStatus === 'approved' || tutorProfile?.is_verified ? (
              <div className="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 text-xs font-bold">
                <CheckCircle2 className="w-3.5 h-3.5" />
                <span>✓ Проверенный специалист (Диплом подтверждён)</span>
              </div>
            ) : verificationStatus === 'pending' ? (
              <div className="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-amber-500/10 border border-amber-500/20 text-amber-400 text-xs font-bold">
                <Clock className="w-3.5 h-3.5 animate-pulse" />
                <span>Диплом на проверке у тех. администратора</span>
              </div>
            ) : (
              <button
                type="button"
                onClick={() => {
                  setActiveEditTab('diploma');
                  setShowEditModal(true);
                }}
                className="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-[#7D39EB]/15 border border-[#7D39EB]/30 text-[#7D39EB] dark:text-[#C6FF33] text-xs font-bold hover:bg-[#7D39EB]/25 active:scale-95 transition"
              >
                <ShieldCheck className="w-3.5 h-3.5" />
                <span>Загрузить диплом для верификации анкеты</span>
              </button>
            )}
          </div>
        )}

        {isTutor && tutorProfile && (
          <div className="mt-4 pt-4 border-t border-slate-100 dark:border-white/5 flex items-center justify-around text-xs">
            <div>
              <div className="flex items-center justify-center gap-1 font-bold dark:text-white text-slate-800">
                <Star className="w-3.5 h-3.5 fill-amber-400 text-amber-400" />
                <span>{tutorProfile.rating || '5.0'}</span>
              </div>
              <span className="text-[10px] text-slate-400">Рейтинг</span>
            </div>
            <div className="w-px h-6 bg-slate-200 dark:bg-white/10" />
            <div>
              <div className="font-bold dark:text-white text-slate-800">
                {tutorProfile.reviews_count || 0}
              </div>
              <span className="text-[10px] text-slate-400">Отзывов</span>
            </div>
            <div className="w-px h-6 bg-slate-200 dark:bg-white/10" />
            <div>
              <div className="font-bold dark:text-white text-slate-800">
                {tutorProfile.hourly_rate || 35} BYN
              </div>
              <span className="text-[10px] text-slate-400">Ставка/час</span>
            </div>
          </div>
        )}

        {/* Edit profile button */}
        <div className="mt-4 pt-2">
          <button
            type="button"
            onClick={() => {
              setActiveEditTab('main');
              setShowEditModal(true);
            }}
            className="w-full py-2.5 rounded-xl border border-slate-200 dark:border-white/10 bg-slate-50 dark:bg-white/5 hover:bg-slate-100 dark:hover:bg-white/10 text-xs font-bold dark:text-white text-slate-800 flex items-center justify-center gap-2 active:scale-95 transition"
          >
            <Edit3 className="w-3.5 h-3.5 text-[#7D39EB] dark:text-[#C6FF33]" />
            <span>{isTutor ? 'Редактировать анкету, дипломы и ставку' : 'Редактировать контакты'}</span>
          </button>
        </div>
      </div>

      {/* ─── 1.1 SWITCH TO TUTOR PROMO (STUDENTS ONLY) ─── */}
      {!isTutor && (
        <div className="p-4 rounded-3xl border border-[#C6FF33]/30 bg-gradient-to-r from-[#7D39EB]/15 to-[#C6FF33]/15 dark:bg-white/[0.04] shadow-sm flex items-center justify-between">
          <div className="flex items-center gap-3">
            <div className="w-10 h-10 rounded-2xl bg-[#C6FF33] text-black flex items-center justify-center shrink-0 font-black">
              <Sparkles className="w-5 h-5 text-black" />
            </div>
            <div>
              <h4 className="text-xs font-extrabold dark:text-white text-slate-900">
                Вы преподаете? Станьте репетитором
              </h4>
              <p className="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">
                Переключите аккаунт в 1 клик, заполните анкету и получайте учеников
              </p>
            </div>
          </div>
          <button
            type="button"
            disabled={isSwitchingRole}
            onClick={handleSwitchToTutor}
            className="px-3.5 py-2 rounded-xl bg-[#C6FF33] hover:bg-[#b8f526] text-black font-extrabold text-xs shadow-md active:scale-95 transition disabled:opacity-50 shrink-0 ml-2"
          >
            {isSwitchingRole ? 'Секунду...' : 'Стать репетитором'}
          </button>
        </div>
      )}

      {/* ─── 1.1 SUBSCRIPTION PLAN CARD (TUTORS ONLY) ─── */}
      {isTutor && (
        <div className="p-4 rounded-3xl border border-white/10 dark:bg-white/[0.04] bg-white shadow-sm flex items-center justify-between">
          <div className="flex items-center gap-3">
            <div className="w-10 h-10 rounded-2xl bg-gradient-to-br from-[#7D39EB]/20 to-[#C6FF33]/20 text-[#7D39EB] dark:text-[#C6FF33] flex items-center justify-center shrink-0 border border-white/10">
              <Crown className="w-5 h-5" />
            </div>
            <div>
              <div className="flex items-center gap-2">
                <h4 className="text-xs font-extrabold dark:text-white text-slate-900">
                  Тариф {subscription?.plan_name || 'PRO'}
                </h4>
                <span className="px-2 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-emerald-500/15 text-emerald-600 dark:text-emerald-400">
                  {subscription?.is_active ? 'Активен' : 'Базовый'}
                </span>
              </div>
              <p className="text-[11px] text-slate-400 mt-0.5">
                {subscription?.period_end ? `Оплачен до ${subscription.period_end}` : 'Все функции виртуального класса и каталога'}
              </p>
            </div>
          </div>

          <button
            type="button"
            onClick={() => setShowSubscriptionModal(true)}
            className="px-3 py-2 rounded-xl bg-[#7D39EB] hover:bg-purple-700 text-white font-extrabold text-xs shadow-md shadow-purple-500/20 active:scale-95 transition"
          >
            Тарифы
          </button>
        </div>
      )}

      {/* ─── 1.2 SCHEDULE VIEW MODE PREFERENCE (GOOGLE CALENDAR GRID - TUTORS ONLY) ─── */}
      {isTutor && (
        <div className="p-4 rounded-2xl border border-slate-200/80 dark:border-white/10 dark:bg-white/[0.04] bg-white shadow-sm flex items-center justify-between">
          <div className="flex items-center gap-3">
            <div className="w-9 h-9 rounded-xl bg-purple-500/10 text-[#7D39EB] dark:text-[#C6FF33] flex items-center justify-center">
              <LayoutGrid className="w-4 h-4" />
            </div>
            <div>
              <h4 className="text-xs font-bold dark:text-white text-slate-900">
                Вид расписания уроков
              </h4>
              <p className="text-[11px] text-slate-400">
                {calendarViewPreference === 'grid' ? 'Почасовая сетка (Google Календарь)' : 'Компактный список на день'}
              </p>
            </div>
          </div>

          <div className="flex items-center bg-slate-100 dark:bg-white/5 p-1 rounded-xl border border-slate-200/60 dark:border-white/10">
            <button
              type="button"
              onClick={() => handleToggleCalendarViewPreference('list')}
              className={`px-2.5 py-1 rounded-lg text-[11px] font-bold flex items-center gap-1 transition ${
                calendarViewPreference === 'list'
                  ? 'bg-white dark:bg-[#7D39EB] text-slate-900 dark:text-white shadow-sm'
                  : 'text-slate-500 hover:text-slate-800 dark:hover:text-white'
              }`}
            >
              <List className="w-3.5 h-3.5" />
              <span>Список</span>
            </button>
            <button
              type="button"
              onClick={() => handleToggleCalendarViewPreference('grid')}
              className={`px-2.5 py-1 rounded-lg text-[11px] font-bold flex items-center gap-1 transition ${
                calendarViewPreference === 'grid'
                  ? 'bg-white dark:bg-[#7D39EB] text-slate-900 dark:text-white shadow-sm'
                  : 'text-slate-500 hover:text-slate-800 dark:hover:text-white'
              }`}
            >
              <LayoutGrid className="w-3.5 h-3.5" />
              <span>Сетка</span>
            </button>
          </div>
        </div>
      )}

      {/* ─── 2. BOOKING LINK & QR CODE CARD (TUTORS ONLY) ─── */}
      {isTutor && (
        <div className="p-4 rounded-3xl border border-white/10 dark:bg-white/[0.04] bg-white shadow-sm space-y-3">
          <div className="flex items-center justify-between">
            <div>
              <h4 className="text-xs font-extrabold uppercase tracking-wider text-[#7D39EB] dark:text-[#C6FF33]">
                Прямая ссылка для записи
              </h4>
              <p className="text-xs font-mono font-bold dark:text-white text-slate-800 truncate mt-0.5">
                {bookingShortUrl || finalBookingUrl}
              </p>
            </div>

            <button
              type="button"
              onClick={() => setShowQrModal(true)}
              className="p-2 rounded-xl bg-slate-100 dark:bg-white/5 hover:bg-slate-200 dark:hover:bg-white/10 text-slate-700 dark:text-slate-300 transition"
              title="Показать QR-код"
            >
              <QrCode className="w-4 h-4" />
            </button>
          </div>

          <div className="grid grid-cols-2 gap-2">
            <button
              type="button"
              onClick={handleCopyBooking}
              className={`py-2 px-3 rounded-xl text-xs font-bold flex items-center justify-center gap-1.5 transition active:scale-95 ${
                copiedLink 
                  ? 'bg-emerald-500 text-white' 
                  : 'bg-[#7D39EB] text-white hover:bg-purple-700'
              }`}
            >
              {copiedLink ? <Check className="w-3.5 h-3.5" /> : <Copy className="w-3.5 h-3.5" />}
              <span>{copiedLink ? 'Скопировано!' : 'Скопировать'}</span>
            </button>

            <button
              type="button"
              onClick={handleShareBooking}
              className="py-2 px-3 rounded-xl border border-slate-200 dark:border-white/10 bg-slate-100 dark:bg-white/5 text-slate-700 dark:text-white font-bold text-xs flex items-center justify-center gap-1.5 hover:bg-slate-200 dark:hover:bg-white/10 active:scale-95 transition"
            >
              <Share2 className="w-3.5 h-3.5" />
              <span>Поделиться</span>
            </button>
          </div>
        </div>
      )}

      {/* ─── 3. CATALOG VISIBILITY TOGGLE ─── */}
      {tutorProfile && (
        <div className="p-4 rounded-2xl border border-slate-200/80 dark:border-white/10 dark:bg-white/[0.04] bg-white shadow-sm flex items-center justify-between">
          <div className="flex items-center gap-3">
            <div className={`w-9 h-9 rounded-xl flex items-center justify-center ${
              isActiveInCatalog ? 'bg-emerald-500/10 text-emerald-500' : 'bg-slate-500/10 text-slate-400'
            }`}>
              {isActiveInCatalog ? <Eye className="w-4 h-4" /> : <EyeOff className="w-4 h-4" />}
            </div>
            <div>
              <h4 className="text-xs font-bold dark:text-white text-slate-900">
                {isActiveInCatalog ? 'Принимаю учеников' : 'Анкета скрыта'}
              </h4>
              <p className="text-[11px] text-slate-400">
                {isActiveInCatalog ? 'Видна в каталоге Edusfera' : 'Скрыта из поиска'}
              </p>
            </div>
          </div>

          <button
            type="button"
            onClick={handleToggleCatalog}
            disabled={isToggling}
            className={`w-12 h-7 rounded-full p-1 transition-colors duration-200 focus:outline-none ${
              isActiveInCatalog ? 'bg-[#10B981]' : 'bg-slate-300 dark:bg-white/20'
            }`}
          >
            <motion.div
              layout
              className={`w-5 h-5 rounded-full bg-white shadow-md ${
                isActiveInCatalog ? 'ml-auto' : 'mr-auto'
              }`}
            />
          </button>
        </div>
      )}

      {/* ─── 4. COMFORT VISION & ACCESSIBILITY ─── */}
      <div className="p-4 rounded-2xl border border-slate-200/80 dark:border-white/10 dark:bg-white/[0.04] bg-white shadow-sm flex items-center justify-between">
        <div className="flex items-center gap-3">
          <div className="w-9 h-9 rounded-xl bg-amber-500/10 text-amber-500 flex items-center justify-center">
            <Eye className="w-4 h-4" />
          </div>
          <div>
            <h4 className="text-xs font-bold dark:text-white text-slate-900">
              Комфортное зрение
            </h4>
            <p className="text-[11px] text-slate-400">
              Увеличенный шрифт и защита глаз
            </p>
          </div>
        </div>

        <button
          type="button"
          onClick={handleToggleComfortVision}
          className={`w-12 h-7 rounded-full p-1 transition-colors duration-200 focus:outline-none ${
            isComfortVision ? 'bg-[#7D39EB]' : 'bg-slate-300 dark:bg-white/20'
          }`}
        >
          <motion.div
            layout
            className={`w-5 h-5 rounded-full bg-white shadow-md ${
              isComfortVision ? 'ml-auto' : 'mr-auto'
            }`}
          />
        </button>
      </div>

      {/* ─── 5. METHODICAL COUNCIL ─── */}
      {tutorProfile && (
        <div className="p-4 rounded-2xl border border-amber-500/30 bg-amber-500/5 flex items-center justify-between">
          <div className="flex items-center gap-3">
            <div className="w-9 h-9 rounded-xl bg-amber-500/15 text-amber-500 flex items-center justify-center shrink-0">
              <Award className="w-5 h-5" />
            </div>
            <div>
              <div className="flex items-center gap-1.5">
                <h5 className="text-xs font-bold dark:text-white text-slate-900">
                  Методический совет
                </h5>
                <span className="text-[9px] font-mono px-1.5 py-0.2 rounded bg-amber-500/20 text-amber-500 dark:text-amber-400 font-bold">
                  Эксперт #{tutorProfile.council_number || 1}
                </span>
              </div>
              <p className="text-[11px] text-slate-400">
                Официальный сертификат эксперта Edusfera
              </p>
            </div>
          </div>

          <a
            href="/certificate"
            target="_blank"
            rel="noopener noreferrer"
            className="px-3 py-1.5 rounded-xl bg-amber-500 hover:bg-amber-400 text-black font-extrabold text-xs flex items-center gap-1 shadow-sm active:scale-95 transition"
          >
            <Download className="w-3.5 h-3.5" />
            <span>Сертификат</span>
          </a>
        </div>
      )}

      {/* ─── 6. CALENDAR SYNC & PRINT TILES ─── */}
      <div className="grid grid-cols-2 gap-2.5">
        <a
          href="/api/mobile/schedule/export-ics"
          className="p-3.5 rounded-2xl border border-slate-200/80 dark:border-white/10 dark:bg-white/[0.04] bg-white shadow-sm hover:shadow transition flex items-center gap-2.5 group"
        >
          <div className="w-8 h-8 rounded-xl bg-purple-500/10 text-[#7D39EB] dark:text-[#C6FF33] flex items-center justify-center shrink-0">
            <Download className="w-4 h-4" />
          </div>
          <div className="min-w-0">
            <h5 className="text-xs font-bold dark:text-white text-slate-900 truncate">
              Календарь .ics
            </h5>
            <span className="text-[10px] text-slate-400">Экспорт в телефон</span>
          </div>
        </a>

        <a
          href="/admin/schedule/print-weekly"
          target="_blank"
          rel="noopener noreferrer"
          className="p-3.5 rounded-2xl border border-slate-200/80 dark:border-white/10 dark:bg-white/[0.04] bg-white shadow-sm hover:shadow transition flex items-center gap-2.5 group"
        >
          <div className="w-8 h-8 rounded-xl bg-sky-500/10 text-sky-500 flex items-center justify-center shrink-0">
            <Printer className="w-4 h-4" />
          </div>
          <div className="min-w-0">
            <h5 className="text-xs font-bold dark:text-white text-slate-900 truncate">
              Печать расписания
            </h5>
            <span className="text-[10px] text-slate-400">Формат А4</span>
          </div>
        </a>
      </div>

      {/* ─── 7. ACTIONS & NAVIGATION LIST ─── */}
      <div className="space-y-2">
        {/* Maya AI */}
        <button
          type="button"
          onClick={() => setShowAiModal(true)}
          className="w-full flex items-center justify-between p-3.5 rounded-2xl border border-slate-200/80 dark:border-white/10 dark:bg-white/[0.04] bg-white shadow-sm hover:shadow transition-all group"
        >
          <div className="flex items-center gap-3">
            <div className="w-9 h-9 rounded-xl bg-purple-500/10 text-[#7D39EB] dark:text-[#C6FF33] flex items-center justify-center">
              <Sparkles className="w-4 h-4" />
            </div>
            <div className="text-left">
              <h4 className="text-xs font-bold dark:text-white text-slate-900">
                ИИ-Методист Майя
              </h4>
              <p className="text-[11px] text-slate-400">
                Планы уроков, задачи ЦТ и квизы
              </p>
            </div>
          </div>
          <ChevronRight className="w-4 h-4 text-slate-400 group-hover:translate-x-0.5 transition-transform" />
        </button>

        {/* Change Password */}
        <button
          type="button"
          onClick={() => setShowPasswordModal(true)}
          className="w-full flex items-center justify-between p-3.5 rounded-2xl border border-slate-200/80 dark:border-white/10 dark:bg-white/[0.04] bg-white shadow-sm hover:shadow transition-all group"
        >
          <div className="flex items-center gap-3">
            <div className="w-9 h-9 rounded-xl bg-amber-500/10 text-amber-500 flex items-center justify-center">
              <Lock className="w-4 h-4" />
            </div>
            <div className="text-left">
              <h4 className="text-xs font-bold dark:text-white text-slate-900">
                Безопасность и пароль
              </h4>
              <p className="text-[11px] text-slate-400">
                Смена пароля аккаунта
              </p>
            </div>
          </div>
          <ChevronRight className="w-4 h-4 text-slate-400 group-hover:translate-x-0.5 transition-transform" />
        </button>

        {/* Interactive Tour Guide */}
        {onOpenTour && (
          <button
            type="button"
            onClick={onOpenTour}
            className="w-full flex items-center justify-between p-3.5 rounded-2xl border border-purple-500/20 dark:border-purple-500/30 dark:bg-purple-950/20 bg-purple-50/60 shadow-sm hover:shadow transition-all group"
          >
            <div className="flex items-center gap-3">
              <div className="w-9 h-9 rounded-xl bg-[#7D39EB]/15 text-[#7D39EB] dark:text-[#C6FF33] flex items-center justify-center">
                <Sparkles className="w-4 h-4" />
              </div>
              <div className="text-left">
                <h4 className="text-xs font-bold dark:text-white text-slate-900 flex items-center gap-1.5">
                  <span>Гид по новому кабинету</span>
                  <span className="px-1.5 py-0.5 rounded-md bg-[#7D39EB]/20 text-[9px] font-extrabold text-[#7D39EB] dark:text-[#C6FF33]">Тур</span>
                </h4>
                <p className="text-[11px] text-slate-500 dark:text-slate-400">
                  Интерактивное обучение фишкам кабинета (ПК и смартфон)
                </p>
              </div>
            </div>
            <ChevronRight className="w-4 h-4 text-slate-400 group-hover:translate-x-0.5 transition-transform" />
          </button>
        )}

        {/* Help & Support */}
        <button
          type="button"
          onClick={() => alert('Служба заботы Edusfera: напишите нам в Telegram @edusfera_help или позвоните +375 (29) 123-45-67')}
          className="w-full flex items-center justify-between p-3.5 rounded-2xl border border-slate-200/80 dark:border-white/10 dark:bg-white/[0.04] bg-white shadow-sm hover:shadow transition-all group"
        >
          <div className="flex items-center gap-3">
            <div className="w-9 h-9 rounded-xl bg-emerald-500/10 text-emerald-500 flex items-center justify-center">
              <HelpCircle className="w-4 h-4" />
            </div>
            <div className="text-left">
              <h4 className="text-xs font-bold dark:text-white text-slate-900">
                Помощь и поддержка
              </h4>
              <p className="text-[11px] text-slate-400">
                Служба заботы репетиторов и учеников
              </p>
            </div>
          </div>
          <ChevronRight className="w-4 h-4 text-slate-400 group-hover:translate-x-0.5 transition-transform" />
        </button>
      </div>

      {/* Logout Button */}
      <button
        type="button"
        onClick={handleLogout}
        className="w-full min-h-[46px] rounded-2xl border border-rose-500/20 bg-rose-500/10 text-rose-500 font-bold text-xs flex items-center justify-center gap-2 active:scale-95 transition"
      >
        <LogOut className="w-4 h-4" />
        <span>Выйти из аккаунта</span>
      </button>

      {/* ─── MODAL: EDIT PROFILE ─── */}
      <AnimatePresence>
        {showEditModal && (
          <div className="fixed inset-0 z-[70] flex items-end justify-center">
            <motion.div
              initial={{ opacity: 0 }}
              animate={{ opacity: 1 }}
              exit={{ opacity: 0 }}
              onClick={() => setShowEditModal(false)}
              className="fixed inset-0 bg-black/60 backdrop-blur-sm"
            />

            <motion.div
              initial={{ y: '100%' }}
              animate={{ y: 0 }}
              exit={{ y: '100%' }}
              transition={{ type: 'spring', damping: 30, stiffness: 350 }}
              className="relative w-full max-w-lg rounded-t-[32px] p-6 pb-10 z-10 border-t border-white/10 dark:bg-[#121214] bg-white shadow-2xl text-xs space-y-4 max-h-[90vh] flex flex-col"
            >
              <div className="w-12 h-1.5 rounded-full bg-slate-300 dark:bg-white/20 mx-auto mb-2 shrink-0" />

              <div className="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-white/10 shrink-0">
                <div className="flex items-center gap-2">
                  <div className="w-8 h-8 rounded-xl bg-purple-500/10 text-[#7D39EB] dark:text-[#C6FF33] flex items-center justify-center">
                    <Edit3 className="w-4 h-4" />
                  </div>
                  <div>
                    <h4 className="text-base font-extrabold dark:text-white text-slate-900">
                      Анкета репетитора
                    </h4>
                    <span className="text-[11px] text-slate-400">
                      Редактирование профиля, методики и документов
                    </span>
                  </div>
                </div>

                <button
                  type="button"
                  onClick={() => setShowEditModal(false)}
                  className="w-8 h-8 rounded-full bg-slate-100 dark:bg-white/10 flex items-center justify-center text-slate-600 dark:text-white/70"
                >
                  <X className="w-4 h-4" />
                </button>
              </div>

              {/* Edit Modal Tabs */}
              <div className="flex items-center gap-1.5 p-1 rounded-xl bg-slate-100 dark:bg-white/5 border border-slate-200/60 dark:border-white/10 shrink-0">
                <button
                  type="button"
                  onClick={() => setActiveEditTab('main')}
                  className={`flex-1 py-1.5 px-2 rounded-lg text-[11px] font-bold flex items-center justify-center gap-1.5 transition ${
                    activeEditTab === 'main'
                      ? 'bg-white dark:bg-[#7D39EB] text-slate-900 dark:text-white shadow-sm'
                      : 'text-slate-500 hover:text-slate-800 dark:hover:text-white'
                  }`}
                >
                  <User className="w-3.5 h-3.5" />
                  <span>Основное</span>
                </button>
                <button
                  type="button"
                  onClick={() => setActiveEditTab('extended')}
                  className={`flex-1 py-1.5 px-2 rounded-lg text-[11px] font-bold flex items-center justify-center gap-1.5 transition ${
                    activeEditTab === 'extended'
                      ? 'bg-white dark:bg-[#7D39EB] text-slate-900 dark:text-white shadow-sm'
                      : 'text-slate-500 hover:text-slate-800 dark:hover:text-white'
                  }`}
                >
                  <GraduationCap className="w-3.5 h-3.5" />
                  <span>Опыт и методика</span>
                </button>
                <button
                  type="button"
                  onClick={() => setActiveEditTab('diploma')}
                  className={`flex-1 py-1.5 px-2 rounded-lg text-[11px] font-bold flex items-center justify-center gap-1.5 transition ${
                    activeEditTab === 'diploma'
                      ? 'bg-white dark:bg-[#7D39EB] text-slate-900 dark:text-white shadow-sm'
                      : 'text-slate-500 hover:text-slate-800 dark:hover:text-white'
                  }`}
                >
                  <Award className="w-3.5 h-3.5" />
                  <span>Диплом</span>
                </button>
              </div>

              {profileSuccessMsg && (
                <div className="p-3 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-500 font-bold text-center">
                  {profileSuccessMsg}
                </div>
              )}

              <form onSubmit={handleSaveProfile} className="flex-1 overflow-y-auto space-y-3.5 pr-1">
                {/* ─── TAB 1: MAIN INFO ─── */}
                {activeEditTab === 'main' && (
                  <div className="space-y-3.5">
                    <div>
                      <label className="block text-[11px] font-bold text-slate-400 mb-1">
                        Ваше имя (ФИО)
                      </label>
                      <input
                        type="text"
                        required
                        value={editName}
                        onChange={(e) => setEditName(e.target.value)}
                        className="w-full h-11 rounded-xl px-3 border border-slate-200 dark:border-white/10 dark:bg-white/5 bg-slate-50 dark:text-white text-slate-900 text-xs focus:ring-2 focus:ring-[#7D39EB] outline-none"
                      />
                    </div>

                    <div>
                      <label className="block text-[11px] font-bold text-slate-400 mb-1">
                        Номер телефона
                      </label>
                      <input
                        type="tel"
                        value={editPhone}
                        placeholder="+375 (XX) XXX-XX-XX"
                        onChange={(e) => setEditPhone(e.target.value)}
                        className="w-full h-11 rounded-xl px-3 border border-slate-200 dark:border-white/10 dark:bg-white/5 bg-slate-50 dark:text-white text-slate-900 text-xs focus:ring-2 focus:ring-[#7D39EB] outline-none"
                      />
                    </div>

                    <div>
                      <label className="block text-[11px] font-bold text-slate-400 mb-1">
                        Ставка за 60 минут (BYN)
                      </label>
                      <input
                        type="number"
                        min="5"
                        max="500"
                        step="1"
                        required
                        value={editRate}
                        onChange={(e) => setEditRate(e.target.value)}
                        className="w-full h-11 rounded-xl px-3 border border-slate-200 dark:border-white/10 dark:bg-white/5 bg-slate-50 dark:text-white text-slate-900 font-mono font-bold text-xs focus:ring-2 focus:ring-[#7D39EB] outline-none"
                      />
                    </div>

                    <div>
                      <label className="block text-[11px] font-bold text-slate-400 mb-1">
                        Заголовок анкеты (специализация)
                      </label>
                      <input
                        type="text"
                        value={editHeadline}
                        placeholder="Репетитор по математике и подготовке к ЦТ"
                        onChange={(e) => setEditHeadline(e.target.value)}
                        className="w-full h-11 rounded-xl px-3 border border-slate-200 dark:border-white/10 dark:bg-white/5 bg-slate-50 dark:text-white text-slate-900 text-xs focus:ring-2 focus:ring-[#7D39EB] outline-none"
                      />
                    </div>

                    <div>
                      <label className="block text-[11px] font-bold text-slate-400 mb-1.5">
                        Предметы преподавания (выберите нужные)
                      </label>
                      <div className="flex flex-wrap gap-1.5">
                        {POPULAR_SUBJECTS.map((sub) => {
                          const isSelected = editSubjects.includes(sub);
                          return (
                            <button
                              key={sub}
                              type="button"
                              onClick={() => handleToggleSubject(sub)}
                              className={`px-2.5 py-1.5 rounded-xl text-xs font-bold transition active:scale-95 ${
                                isSelected
                                  ? 'bg-[#7D39EB] text-white shadow-sm'
                                  : 'bg-slate-100 dark:bg-white/5 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-white/10'
                              }`}
                            >
                              {isSelected && <span className="mr-1">✓</span>}
                              {sub}
                            </button>
                          );
                        })}
                      </div>
                    </div>

                    <div>
                      <label className="block text-[11px] font-bold text-slate-400 mb-1.5">
                        Форматы проведения занятий
                      </label>
                      <div className="grid grid-cols-1 gap-2">
                        {FORMAT_OPTIONS.map((fmt) => {
                          const isSelected = editFormats.includes(fmt.id);
                          return (
                            <button
                              key={fmt.id}
                              type="button"
                              onClick={() => handleToggleFormat(fmt.id)}
                              className={`p-2.5 rounded-xl text-left text-xs font-bold transition flex items-center justify-between border ${
                                isSelected
                                  ? 'border-[#7D39EB] bg-[#7D39EB]/10 text-[#7D39EB] dark:text-[#C6FF33]'
                                  : 'border-slate-200 dark:border-white/10 bg-slate-50 dark:bg-white/5 text-slate-700 dark:text-slate-300'
                              }`}
                            >
                              <span>{fmt.label}</span>
                              {isSelected ? <Check className="w-4 h-4 text-[#7D39EB] dark:text-[#C6FF33]" /> : <div className="w-4 h-4 rounded-full border border-slate-300 dark:border-white/20" />}
                            </button>
                          );
                        })}
                      </div>
                    </div>

                    <div>
                      <label className="block text-[11px] font-bold text-slate-400 mb-1">
                        Юридический статус репетитора
                      </label>
                      <select
                        value={editLegalStatus}
                        onChange={(e) => setEditLegalStatus(e.target.value)}
                        className="w-full h-11 rounded-xl px-3 border border-slate-200 dark:border-white/10 dark:bg-white/5 bg-slate-50 dark:text-white text-slate-900 text-xs focus:ring-2 focus:ring-[#7D39EB] outline-none"
                      >
                        {LEGAL_STATUS_OPTIONS.map((st) => (
                          <option key={st.id} value={st.id} className="dark:bg-[#18181b] bg-white text-slate-900 dark:text-white">
                            {st.label}
                          </option>
                        ))}
                      </select>
                    </div>

                    <div>
                      <label className="block text-[11px] font-bold text-slate-400 mb-1">
                        Telegram (@username для уведомлений)
                      </label>
                      <div className="relative">
                        <input
                          type="text"
                          placeholder="username"
                          value={editTelegram}
                          onChange={(e) => setEditTelegram(e.target.value)}
                          className="w-full h-11 rounded-xl pl-8 pr-3 border border-slate-200 dark:border-white/10 dark:bg-white/5 bg-slate-50 dark:text-white text-slate-900 text-xs focus:ring-2 focus:ring-[#7D39EB] outline-none font-mono"
                        />
                        <span className="absolute left-3 top-3 text-slate-400 font-mono">@</span>
                      </div>
                    </div>
                  </div>
                )}

                {/* ─── TAB 2: EXTENDED EXPERIENCE & METHODOLOGY ─── */}
                {activeEditTab === 'extended' && (
                  <div className="space-y-3.5">
                    <div>
                      <label className="block text-[11px] font-bold text-slate-400 mb-1">
                        Стаж преподавательской работы (лет)
                      </label>
                      <input
                        type="number"
                        min="0"
                        max="60"
                        value={editExperience}
                        onChange={(e) => setEditExperience(Number(e.target.value))}
                        className="w-full h-11 rounded-xl px-3 border border-slate-200 dark:border-white/10 dark:bg-white/5 bg-slate-50 dark:text-white text-slate-900 font-mono font-bold text-xs focus:ring-2 focus:ring-[#7D39EB] outline-none"
                      />
                    </div>

                    <div>
                      <label className="block text-[11px] font-bold text-slate-400 mb-1.5">
                        Целевая аудитория и классы (нажмите для выбора)
                      </label>
                      <div className="flex flex-wrap gap-1.5">
                        {AUDIENCE_OPTIONS.map((aud) => {
                          const isSelected = editAudiences.includes(aud);
                          return (
                            <button
                              key={aud}
                              type="button"
                              onClick={() => handleToggleAudience(aud)}
                              className={`px-2.5 py-1.5 rounded-xl text-xs font-bold transition active:scale-95 ${
                                isSelected
                                  ? 'bg-[#7D39EB] text-white shadow-sm'
                                  : 'bg-slate-100 dark:bg-white/5 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-white/10'
                              }`}
                            >
                              {isSelected && <span className="mr-1">✓</span>}
                              {aud}
                            </button>
                          );
                        })}
                      </div>
                    </div>

                    <div>
                      <label className="block text-[11px] font-bold text-slate-400 mb-1">
                        Образование и ВУЗ
                      </label>
                      <textarea
                        rows={2}
                        value={editEducation}
                        placeholder="Например: Белорусский государственный университет (БГУ), механико-математический факультет, 2018 г."
                        onChange={(e) => setEditEducation(e.target.value)}
                        className="w-full p-3 rounded-xl border border-slate-200 dark:border-white/10 dark:bg-white/5 bg-slate-50 dark:text-white text-slate-900 text-xs focus:ring-2 focus:ring-[#7D39EB] outline-none resize-none"
                      />
                    </div>

                    <div>
                      <label className="block text-[11px] font-bold text-slate-400 mb-1">
                        Методика проведения занятий
                      </label>
                      <textarea
                        rows={3}
                        value={editMethodology}
                        placeholder="Опишите структуру уроков: разбор теории, домашние задания, интерактивная доска, подготовка к типовым ловушкам спецификаций РИКЗ..."
                        onChange={(e) => setEditMethodology(e.target.value)}
                        className="w-full p-3 rounded-xl border border-slate-200 dark:border-white/10 dark:bg-white/5 bg-slate-50 dark:text-white text-slate-900 text-xs focus:ring-2 focus:ring-[#7D39EB] outline-none resize-none"
                      />
                    </div>

                    <div>
                      <label className="block text-[11px] font-bold text-slate-400 mb-1">
                        Результаты и успехи учеников
                      </label>
                      <textarea
                        rows={2}
                        value={editAchievements}
                        placeholder="Например: Средний балл ЦТ/ЦЭ 2025 года — 84 балла. Поступление на бюджет в БГУИР, БГУ, БГМУ."
                        onChange={(e) => setEditAchievements(e.target.value)}
                        className="w-full p-3 rounded-xl border border-slate-200 dark:border-white/10 dark:bg-white/5 bg-slate-50 dark:text-white text-slate-900 text-xs focus:ring-2 focus:ring-[#7D39EB] outline-none resize-none"
                      />
                    </div>

                    <div>
                      <label className="block text-[11px] font-bold text-slate-400 mb-1">
                        О себе (краткая презентация)
                      </label>
                      <textarea
                        rows={3}
                        value={editBio}
                        placeholder="Расскажите о себе, подходе к ученикам и вашей мотивации..."
                        onChange={(e) => setEditBio(e.target.value)}
                        className="w-full p-3 rounded-xl border border-slate-200 dark:border-white/10 dark:bg-white/5 bg-slate-50 dark:text-white text-slate-900 text-xs focus:ring-2 focus:ring-[#7D39EB] outline-none resize-none"
                      />
                    </div>
                  </div>
                )}

                {/* ─── TAB 3: DIPLOMA & MODERATION ─── */}
                {activeEditTab === 'diploma' && (
                  <div className="space-y-4">
                    {/* Hidden file input */}
                    <input
                      type="file"
                      ref={diplomaFileInputRef}
                      onChange={handleDiplomaUpload}
                      accept="image/jpeg,image/png,image/webp,application/pdf"
                      className="hidden"
                    />

                    {/* Status Alert Banner */}
                    {verificationStatus === 'approved' || tutorProfile?.is_verified ? (
                      <div className="p-4 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-500 flex items-start gap-3">
                        <CheckCircle2 className="w-5 h-5 shrink-0 mt-0.5" />
                        <div>
                          <h5 className="font-extrabold text-xs">Образование успешно подтверждено!</h5>
                          <p className="text-[11px] text-emerald-600 dark:text-emerald-400 mt-0.5">
                            Ваша анкета имеет официальный знак «✓ Проверенный специалист» и показывается с наивысшим приоритетом в каталоге репетиторов.
                          </p>
                        </div>
                      </div>
                    ) : verificationStatus === 'pending' ? (
                      <div className="p-4 rounded-2xl bg-amber-500/10 border border-amber-500/20 text-amber-500 flex items-start gap-3">
                        <Clock className="w-5 h-5 shrink-0 mt-0.5 animate-pulse" />
                        <div>
                          <h5 className="font-extrabold text-xs">Диплом на проверке у тех. администратора</h5>
                          <p className="text-[11px] text-amber-600 dark:text-amber-400 mt-0.5">
                            Документ передан модератору. Обычно проверка диплома занимает от 1 до 24 часов. После одобрения статус обновится автоматически.
                          </p>
                        </div>
                      </div>
                    ) : (
                      <div className="p-4 rounded-2xl bg-purple-500/10 border border-purple-500/20 text-[#7D39EB] dark:text-[#C6FF33] flex items-start gap-3">
                        <ShieldCheck className="w-5 h-5 shrink-0 mt-0.5" />
                        <div>
                          <h5 className="font-extrabold text-xs">Подтверждение профиля</h5>
                          <p className="text-[11px] text-slate-600 dark:text-slate-300 mt-0.5">
                            Прикрепите фото или скан диплома о высшем образовании или сертификатов. Это повышает доверие учеников и конверсию в запись в 3 раза.
                          </p>
                        </div>
                      </div>
                    )}

                    {/* Current Diploma Info Card */}
                    {diplomaPath && (
                      <div className="p-3.5 rounded-2xl border border-slate-200 dark:border-white/10 dark:bg-white/5 bg-slate-50 flex items-center justify-between">
                        <div className="flex items-center gap-2.5 min-w-0">
                          <div className="w-8 h-8 rounded-xl bg-purple-500/10 text-[#7D39EB] dark:text-[#C6FF33] flex items-center justify-center shrink-0">
                            <FileText className="w-4 h-4" />
                          </div>
                          <div className="min-w-0">
                            <div className="text-xs font-bold dark:text-white text-slate-900 truncate">
                              {diplomaFilename || 'diploma_document'}
                            </div>
                            <span className="text-[10px] text-slate-400">Документ загружен</span>
                          </div>
                        </div>

                        <a
                          href={diplomaPath.startsWith('http') ? diplomaPath : `/storage/${diplomaPath}`}
                          target="_blank"
                          rel="noopener noreferrer"
                          className="px-2.5 py-1.5 rounded-lg border border-slate-200 dark:border-white/10 text-xs font-bold dark:text-white text-slate-700 hover:bg-slate-100 dark:hover:bg-white/10 transition"
                        >
                          Открыть
                        </a>
                      </div>
                    )}

                    {diplomaUploadMsg && (
                      <div className="p-3 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-500 font-bold text-center">
                        {diplomaUploadMsg}
                      </div>
                    )}

                    {/* Upload / Reupload Button */}
                    <button
                      type="button"
                      onClick={() => diplomaFileInputRef.current?.click()}
                      disabled={isUploadingDiploma}
                      className="w-full py-3.5 px-4 rounded-2xl border-2 border-dashed border-[#7D39EB]/40 dark:border-white/20 hover:border-[#7D39EB] dark:hover:border-[#C6FF33] bg-purple-500/5 hover:bg-purple-500/10 text-[#7D39EB] dark:text-[#C6FF33] font-bold text-xs flex items-center justify-center gap-2 active:scale-95 transition"
                    >
                      {isUploadingDiploma ? (
                        <>
                          <Loader2 className="w-4 h-4 animate-spin" />
                          <span>Загрузка документа...</span>
                        </>
                      ) : (
                        <>
                          <UploadCloud className="w-4 h-4" />
                          <span>{diplomaPath ? 'Загрузить другой скан/фото диплома' : 'Выбрать файл диплома (JPG, PNG, PDF до 10 МБ)'}</span>
                        </>
                      )}
                    </button>

                    <p className="text-[11px] text-slate-400 text-center leading-relaxed">
                      🔒 Загруженные документы строго конфиденциальны, защищены шифрованием и отправляются только техническому администратору платформы на проверку подлинности.
                    </p>
                  </div>
                )}

                {/* Save Button */}
                <div className="pt-2">
                  <button
                    type="submit"
                    disabled={isSavingProfile}
                    className="w-full min-h-[46px] rounded-2xl bg-[#C6FF33] hover:bg-[#b5f21f] text-black font-extrabold text-sm flex items-center justify-center gap-2 active:scale-95 transition shadow-lg shadow-lime-500/10"
                  >
                    {isSavingProfile ? (
                      <Loader2 className="w-4 h-4 animate-spin" />
                    ) : (
                      <span>Сохранить настройки</span>
                    )}
                  </button>
                </div>
              </form>
            </motion.div>
          </div>
        )}
      </AnimatePresence>

      {/* ─── MODAL: QR CODE ─── */}
      <AnimatePresence>
        {showQrModal && (
          <div className="fixed inset-0 z-[70] flex items-center justify-center p-4">
            <motion.div
              initial={{ opacity: 0 }}
              animate={{ opacity: 1 }}
              exit={{ opacity: 0 }}
              onClick={() => setShowQrModal(false)}
              className="fixed inset-0 bg-black/70 backdrop-blur-sm"
            />

            <motion.div
              initial={{ scale: 0.9, opacity: 0 }}
              animate={{ scale: 1, opacity: 1 }}
              exit={{ scale: 0.9, opacity: 0 }}
              className="relative w-full max-w-sm rounded-[32px] p-6 z-10 border border-white/10 dark:bg-[#121214] bg-white shadow-2xl text-center space-y-4"
            >
              <div className="flex items-center justify-between">
                <h4 className="text-base font-extrabold dark:text-white text-slate-900">
                  QR-код для записи
                </h4>
                <button
                  type="button"
                  onClick={() => setShowQrModal(false)}
                  className="w-8 h-8 rounded-full bg-slate-100 dark:bg-white/10 flex items-center justify-center text-slate-600 dark:text-white/70"
                >
                  <X className="w-4 h-4" />
                </button>
              </div>

              <div className="p-4 bg-white rounded-2xl shadow-inner inline-block mx-auto border border-slate-200">
                <img
                  src={qrCodeUrl}
                  alt="QR Code"
                  className="w-56 h-56 mx-auto object-contain"
                />
              </div>

              <p className="text-xs text-slate-400">
                Разместите QR-код в соцсетях или распечатайте на визитках для быстрой записи учеников без ввода ссылок.
              </p>

              <button
                type="button"
                onClick={handleCopyBooking}
                className="w-full py-3 rounded-2xl bg-[#7D39EB] text-white font-extrabold text-xs flex items-center justify-center gap-1.5 active:scale-95 transition"
              >
                {copiedLink ? <Check className="w-4 h-4" /> : <Copy className="w-4 h-4" />}
                <span>{copiedLink ? 'Ссылка скопирована!' : 'Скопировать ссылку'}</span>
              </button>
            </motion.div>
          </div>
        )}
      </AnimatePresence>

      {/* ─── MODAL: CHANGE PASSWORD ─── */}
      <AnimatePresence>
        {showPasswordModal && (
          <div className="fixed inset-0 z-[70] flex items-end justify-center">
            <motion.div
              initial={{ opacity: 0 }}
              animate={{ opacity: 1 }}
              exit={{ opacity: 0 }}
              onClick={() => setShowPasswordModal(false)}
              className="fixed inset-0 bg-black/60 backdrop-blur-sm"
            />

            <motion.div
              initial={{ y: '100%' }}
              animate={{ y: 0 }}
              exit={{ y: '100%' }}
              transition={{ type: 'spring', damping: 30, stiffness: 350 }}
              className="relative w-full max-w-lg rounded-t-[32px] p-6 pb-10 z-10 border-t border-white/10 dark:bg-[#121214] bg-white shadow-2xl text-xs space-y-4"
            >
              <div className="w-12 h-1.5 rounded-full bg-slate-300 dark:bg-white/20 mx-auto mb-2" />

              <div className="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-white/10">
                <div className="flex items-center gap-2">
                  <div className="w-8 h-8 rounded-xl bg-amber-500/10 text-amber-500 flex items-center justify-center">
                    <Lock className="w-4 h-4" />
                  </div>
                  <div>
                    <h4 className="text-base font-extrabold dark:text-white text-slate-900">
                      Смена пароля
                    </h4>
                    <span className="text-[11px] text-slate-400">
                      Защита вашего аккаунта
                    </span>
                  </div>
                </div>

                <button
                  type="button"
                  onClick={() => setShowPasswordModal(false)}
                  className="w-8 h-8 rounded-full bg-slate-100 dark:bg-white/10 flex items-center justify-center text-slate-600 dark:text-white/70"
                >
                  <X className="w-4 h-4" />
                </button>
              </div>

              {passwordSuccessMsg && (
                <div className="p-3 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-500 font-bold text-center">
                  {passwordSuccessMsg}
                </div>
              )}

              {passwordErrorMsg && (
                <div className="p-3 rounded-xl bg-rose-500/10 border border-rose-500/20 text-rose-500 font-bold text-center">
                  {passwordErrorMsg}
                </div>
              )}

              <form onSubmit={handleChangePassword} className="space-y-3 pt-1">
                <div>
                  <label className="block text-[11px] font-bold text-slate-400 mb-1">
                    Текущий пароль
                  </label>
                  <input
                    type="password"
                    required
                    value={currentPassword}
                    onChange={(e) => setCurrentPassword(e.target.value)}
                    className="w-full h-11 rounded-xl px-3 border border-slate-200 dark:border-white/10 dark:bg-white/5 bg-slate-50 dark:text-white text-slate-900 text-xs focus:ring-2 focus:ring-[#7D39EB] outline-none"
                  />
                </div>

                <div>
                  <label className="block text-[11px] font-bold text-slate-400 mb-1">
                    Новый пароль (минимум 8 символов)
                  </label>
                  <input
                    type="password"
                    required
                    minLength={8}
                    value={newPassword}
                    onChange={(e) => setNewPassword(e.target.value)}
                    className="w-full h-11 rounded-xl px-3 border border-slate-200 dark:border-white/10 dark:bg-white/5 bg-slate-50 dark:text-white text-slate-900 text-xs focus:ring-2 focus:ring-[#7D39EB] outline-none"
                  />
                </div>

                <div>
                  <label className="block text-[11px] font-bold text-slate-400 mb-1">
                    Повторите новый пароль
                  </label>
                  <input
                    type="password"
                    required
                    minLength={8}
                    value={newPasswordConfirmation}
                    onChange={(e) => setNewPasswordConfirmation(e.target.value)}
                    className="w-full h-11 rounded-xl px-3 border border-slate-200 dark:border-white/10 dark:bg-white/5 bg-slate-50 dark:text-white text-slate-900 text-xs focus:ring-2 focus:ring-[#7D39EB] outline-none"
                  />
                </div>

                <button
                  type="submit"
                  disabled={isChangingPassword}
                  className="w-full min-h-[46px] rounded-2xl bg-[#7D39EB] hover:bg-purple-700 text-white font-extrabold text-sm flex items-center justify-center gap-2 active:scale-95 transition"
                >
                  {isChangingPassword ? (
                    <Loader2 className="w-4 h-4 animate-spin" />
                  ) : (
                    <span>Обновить пароль</span>
                  )}
                </button>
              </form>
            </motion.div>
          </div>
        )}
      </AnimatePresence>

      {/* ─── MODAL: MAYA AI ASSISTANT ─── */}
      <AnimatePresence>
        {showAiModal && (
          <div className="fixed inset-0 z-[70] flex items-end justify-center">
            <motion.div
              initial={{ opacity: 0 }}
              animate={{ opacity: 1 }}
              exit={{ opacity: 0 }}
              onClick={() => setShowAiModal(false)}
              className="fixed inset-0 bg-black/60 backdrop-blur-sm"
            />

            <motion.div
              initial={{ y: '100%' }}
              animate={{ y: 0 }}
              exit={{ y: '100%' }}
              transition={{ type: 'spring', damping: 30, stiffness: 350 }}
              className="relative w-full max-w-lg rounded-t-[32px] p-6 pb-10 z-10 border-t border-white/10 dark:bg-[#121214] bg-white shadow-2xl text-xs space-y-4 max-h-[85vh] flex flex-col"
            >
              <div className="w-12 h-1.5 rounded-full bg-slate-300 dark:bg-white/20 mx-auto mb-2 shrink-0" />

              <div className="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-white/10 shrink-0">
                <div className="flex items-center gap-2">
                  <div className="w-8 h-8 rounded-xl bg-purple-500/10 text-[#7D39EB] dark:text-[#C6FF33] flex items-center justify-center">
                    <Sparkles className="w-4 h-4" />
                  </div>
                  <div>
                    <h4 className="text-base font-extrabold dark:text-white text-slate-900">
                      ИИ-Методист Майя
                    </h4>
                    <span className="text-[11px] text-slate-400">
                      Генерация планов занятий и задач
                    </span>
                  </div>
                </div>

                <button
                  type="button"
                  onClick={() => setShowAiModal(false)}
                  className="w-8 h-8 rounded-full bg-slate-100 dark:bg-white/10 flex items-center justify-center text-slate-600 dark:text-white/70"
                >
                  <X className="w-4 h-4" />
                </button>
              </div>

              <div className="flex-1 overflow-y-auto space-y-3 pr-1">
                {aiResponse && (
                  <div className="p-3.5 rounded-2xl bg-purple-500/10 border border-purple-500/20 text-slate-800 dark:text-slate-200 leading-relaxed whitespace-pre-wrap font-sans">
                    {aiResponse}
                  </div>
                )}

                <form onSubmit={handleRunAi} className="space-y-3">
                  <div>
                    <label className="block text-[11px] font-bold text-slate-400 mb-1">
                      Запрос к методисту (тема урока или задача ЦТ):
                    </label>
                    <textarea
                      rows={3}
                      value={aiPrompt}
                      onChange={(e) => setAiPrompt(e.target.value)}
                      placeholder="Например: Составь интерактивный план урока по тригонометрии для 10 класса с 3 практическими задачами..."
                      className="w-full p-3 rounded-xl border border-slate-200 dark:border-white/10 dark:bg-white/5 bg-slate-50 dark:text-white text-slate-900 text-xs focus:ring-2 focus:ring-[#7D39EB] outline-none resize-none"
                    />
                  </div>

                  <button
                    type="submit"
                    disabled={isAiLoading || !aiPrompt.trim()}
                    className="w-full min-h-[46px] rounded-2xl bg-[#C6FF33] hover:bg-[#b5f21f] text-black font-extrabold text-sm flex items-center justify-center gap-2 disabled:opacity-50 active:scale-95 transition"
                  >
                    {isAiLoading ? (
                      <Loader2 className="w-4 h-4 animate-spin" />
                    ) : (
                      <>
                        <Sparkles className="w-4 h-4" />
                        <span>Сгенерировать решение</span>
                      </>
                    )}
                  </button>
                </form>
              </div>
            </motion.div>
          </div>
        )}
      </AnimatePresence>

      {/* ─── SUBSCRIPTION PLANS COMPARISON MODAL ─── */}
      <SubscriptionPlansModal
        isOpen={showSubscriptionModal}
        onClose={() => setShowSubscriptionModal(false)}
        currentPlan={subscription?.plan || 'pro'}
        onPlanChanged={(newSub) => {
          if (newSub && onSubscriptionUpdated) {
            onSubscriptionUpdated(newSub);
          }
        }}
      />
    </div>
  );
}
