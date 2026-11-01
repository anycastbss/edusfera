import React, { useState, useEffect } from 'react';
import { motion, AnimatePresence } from 'motion/react';
import { 
  X, Calendar, Clock, User, Plus, Check, Loader2, Sparkles, 
  Share2, Copy, CheckCircle2, Video, Send, MessageCircle, AlertTriangle, ExternalLink
} from 'lucide-react';
import axios from 'axios';

export default function CreateLessonBottomSheet({
  isOpen,
  onClose,
  onLessonCreated,
  tutorProfile,
  initialDate,
}) {
  const [studentMode, setStudentMode] = useState('existing'); // 'existing' | 'new'
  const [students, setStudents] = useState([]);
  const [isLoadingStudents, setIsLoadingStudents] = useState(false);

  // Form State
  const [studentId, setStudentId] = useState('');
  const [newStudentName, setNewStudentName] = useState('');
  const [newStudentPhone, setNewStudentPhone] = useState('');
  const [subject, setSubject] = useState(tutorProfile?.headline?.split(' ')[0] || 'Математика');
  
  // Date & Time defaults
  const [startDate, setStartDate] = useState(() => {
    if (initialDate) return initialDate;
    return new Date().toISOString().split('T')[0];
  });
  const [startTime, setStartTime] = useState('15:00');
  const [durationMinutes, setDurationMinutes] = useState(60);
  const [price, setPrice] = useState(tutorProfile?.hourly_rate || 35);
  const [paymentStatus, setPaymentStatus] = useState('unpaid');
  const [notes, setNotes] = useState('');

  const [isSubmitting, setIsSubmitting] = useState(false);
  const [errorMessage, setErrorMessage] = useState(null);

  // Success / Share State
  const [createdLesson, setCreatedLesson] = useState(null);
  const [isCopied, setIsCopied] = useState(false);

  useEffect(() => {
    if (isOpen) {
      setErrorMessage(null);
      setCreatedLesson(null);
      setIsCopied(false);
      setIsLoadingStudents(true);
      axios.get('/api/mobile/students')
        .then((res) => {
          const list = res.data?.students || [];
          setStudents(list);
          if (list.length > 0 && !studentId) {
            setStudentId(String(list[0].id));
          }
        })
        .catch((e) => console.error('Failed to load students:', e))
        .finally(() => setIsLoadingStudents(false));
    }
  }, [isOpen]);

  if (!isOpen) return null;

  const handleClose = () => {
    setCreatedLesson(null);
    setIsCopied(false);
    onClose();
  };

  const handleCopyInviteUrl = () => {
    const inviteUrl = createdLesson?.student_invite_url || (window.location.origin + `/classroom/${createdLesson?.id}`);
    if (navigator?.clipboard?.writeText) {
      navigator.clipboard.writeText(inviteUrl);
    } else {
      const textarea = document.createElement('textarea');
      textarea.value = inviteUrl;
      document.body.appendChild(textarea);
      textarea.select();
      document.execCommand('copy');
      document.body.removeChild(textarea);
    }
    setIsCopied(true);
    setTimeout(() => setIsCopied(false), 2500);
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setErrorMessage(null);
    setIsSubmitting(true);

    try {
      const fullStartTime = `${startDate} ${startTime}:00`;
      const payload = {
        student_mode: studentMode,
        student_id: studentMode === 'existing' ? Number(studentId) : null,
        new_student_name: studentMode === 'new' ? newStudentName : null,
        new_student_phone: studentMode === 'new' ? newStudentPhone : null,
        subject,
        start_time: fullStartTime,
        duration_minutes: Number(durationMinutes),
        price: Number(price),
        payment_status: paymentStatus,
        notes,
      };

      const res = await axios.post('/api/mobile/lessons', payload);
      if (res.data?.success) {
        if (onLessonCreated) {
          onLessonCreated(res.data.lesson);
        }
        // Switch to the Success / Invite Modal view
        setCreatedLesson(res.data.lesson);
      }
    } catch (err) {
      console.error(err);
      const msg = err.response?.data?.error || err.response?.data?.message || 'Не удалось сохранить урок. Проверьте данные.';
      setErrorMessage(msg);
    } finally {
      setIsSubmitting(false);
    }
  };

  const inviteUrl = createdLesson?.student_invite_url || (createdLesson?.id ? `${window.location.origin}/classroom/${createdLesson.id}` : '');
  const shareMessage = `Здравствуйте! Ссылка для подключения к нашему уроку (${createdLesson?.subject || subject}, ${createdLesson?.date_formatted || startDate} в ${createdLesson?.start_formatted || startTime}):\n${inviteUrl}\nПодключение в 1 клик прямо из браузера (без установки приложений).`;

  const telegramShareUrl = `https://t.me/share/url?url=${encodeURIComponent(inviteUrl)}&text=${encodeURIComponent(shareMessage)}`;
  const whatsappShareUrl = `https://api.whatsapp.com/send?text=${encodeURIComponent(shareMessage)}`;

  return (
    <AnimatePresence>
      <div className="fixed inset-0 z-[70] flex items-end sm:items-center justify-center p-0 sm:p-4">
        {/* Backdrop */}
        <motion.div
          initial={{ opacity: 0 }}
          animate={{ opacity: 1 }}
          exit={{ opacity: 0 }}
          onClick={handleClose}
          className="fixed inset-0 bg-black/60 backdrop-blur-sm"
        />

        {/* Bottom Sheet / Modal */}
        <motion.div
          initial={{ y: '100%' }}
          animate={{ y: 0 }}
          exit={{ y: '100%' }}
          transition={{ type: 'spring', damping: 30, stiffness: 350 }}
          className="relative w-full max-w-lg rounded-t-[32px] sm:rounded-[28px] p-6 pb-10 sm:pb-7 z-10 border-t sm:border border-white/10 dark:bg-[#121214] bg-white shadow-2xl max-h-[92vh] sm:max-h-[85vh] overflow-y-auto"
        >
          {/* Swipe handle */}
          <div className="w-12 h-1.5 rounded-full bg-slate-300 dark:bg-white/20 mx-auto mb-4 sm:hidden" />

          {/* ═════════════════════════════════════════════════════════════════ */}
          {/* VIEW A: SUCCESS POPUP WITH ONE-CLICK VIRTUAL CLASSROOM LINK    */}
          {/* ═════════════════════════════════════════════════════════════════ */}
          {createdLesson ? (
            <div className="space-y-4 text-xs">
              {/* Header */}
              <div className="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-white/10">
                <div className="flex items-center gap-2.5">
                  <div className="w-10 h-10 rounded-2xl bg-emerald-500/15 text-emerald-400 flex items-center justify-center font-bold">
                    <CheckCircle2 className="w-6 h-6 text-emerald-500" />
                  </div>
                  <div>
                    <h3 className="text-base font-extrabold dark:text-white text-slate-900 tracking-tight">
                      Урок запланирован!
                    </h3>
                    <p className="text-[11px] text-slate-400">
                      Ссылка на виртуальный класс готова
                    </p>
                  </div>
                </div>

                <button
                  type="button"
                  onClick={handleClose}
                  className="w-8 h-8 rounded-full bg-slate-100 dark:bg-white/10 flex items-center justify-center text-slate-600 dark:text-white/70"
                >
                  <X className="w-4 h-4" />
                </button>
              </div>

              {/* Lesson Brief Card */}
              <div className="p-3.5 rounded-2xl bg-slate-50 dark:bg-white/[0.04] border border-slate-200/80 dark:border-white/10 flex items-center justify-between gap-3">
                <div className="min-w-0">
                  <div className="flex items-center gap-1.5">
                    <span className="font-extrabold text-sm dark:text-white text-slate-900 truncate">
                      {createdLesson.title || createdLesson.student_name || newStudentName || 'Ученик'}
                    </span>
                    {createdLesson.is_guest_student && (
                      <span className="text-[9px] font-bold px-2 py-0.5 rounded-full bg-purple-500/15 text-[#7D39EB] dark:text-[#C6FF33]">
                        Без аккаунта
                      </span>
                    )}
                  </div>
                  <p className="text-[11px] text-slate-400 mt-0.5">
                    {createdLesson.subject || subject} • {createdLesson.date_formatted || startDate} в {createdLesson.start_formatted || startTime} ({durationMinutes} мин)
                  </p>
                </div>

                <div className="text-right shrink-0">
                  <div className="text-xs font-mono font-bold text-emerald-500">
                    {price} BYN
                  </div>
                  <span className="text-[10px] text-slate-400">
                    Прямой расчёт
                  </span>
                </div>
              </div>

              {/* Unregistered Student Warning / Guidance */}
              <div className="p-3.5 rounded-2xl bg-amber-500/10 border border-amber-500/25 space-y-1.5">
                <div className="flex items-center gap-1.5 font-bold text-amber-500 dark:text-amber-400 text-xs">
                  <AlertTriangle className="w-4 h-4 shrink-0" />
                  <span>Вход без регистрации и сохранение прогресса</span>
                </div>
                <p className="text-[11px] leading-relaxed text-slate-600 dark:text-slate-300">
                  Ученик подключится к уроку в 1 клик по ссылке ниже без ввода логина и пароля.
                </p>
                <div className="p-2 rounded-xl bg-amber-500/15 border border-amber-500/20 text-[10px] text-amber-900 dark:text-amber-200 font-medium">
                  ⚠️ <strong>Важно:</strong> прогресс, история уроков и домашние задания не будут сохраняться в базе платформы, пока ученик не зарегистрируется в Edusfera. Для долгосрочных занятий предложите ему создать аккаунт.
                </div>
              </div>

              {/* Ready Link Container */}
              <div className="space-y-1.5">
                <div className="flex items-center justify-between text-[11px] font-bold">
                  <span className="text-slate-400 uppercase tracking-wider">
                    Ссылка для ученика
                  </span>
                  <span className="text-[#7D39EB] dark:text-[#C6FF33] font-mono text-[10px]">
                    1 клик без логина
                  </span>
                </div>

                <div className="p-3 rounded-2xl bg-slate-100 dark:bg-black/50 border border-slate-200 dark:border-white/10 font-mono text-xs dark:text-slate-200 text-slate-800 break-all select-all">
                  {inviteUrl}
                </div>
              </div>

              {/* Action Buttons: Copy & Share */}
              <div className="space-y-2 pt-1">
                <motion.button
                  type="button"
                  onClick={handleCopyInviteUrl}
                  whileTap={{ scale: 0.97 }}
                  className={`w-full min-h-[48px] rounded-2xl font-bold text-xs flex items-center justify-center gap-2 shadow-md transition active:scale-95 ${
                    isCopied
                      ? 'bg-emerald-500 text-white shadow-emerald-500/30'
                      : 'bg-gradient-to-r from-[#7D39EB] to-[#9333EA] text-white shadow-purple-500/30'
                  }`}
                >
                  {isCopied ? (
                    <>
                      <Check className="w-4 h-4" />
                      <span>Ссылка скопирована в буфер!</span>
                    </>
                  ) : (
                    <>
                      <Copy className="w-4 h-4" />
                      <span>Скопировать ссылку для ученика</span>
                    </>
                  )}
                </motion.button>

                {/* Quick Share Row */}
                <div className="grid grid-cols-2 gap-2">
                  <a
                    href={telegramShareUrl}
                    target="_blank"
                    rel="noopener noreferrer"
                    className="min-h-[44px] rounded-xl border border-sky-500/30 bg-sky-500/10 hover:bg-sky-500/20 text-sky-400 font-bold text-xs flex items-center justify-center gap-2 active:scale-95 transition"
                  >
                    <Send className="w-4 h-4" />
                    <span>В Telegram</span>
                  </a>

                  <a
                    href={whatsappShareUrl}
                    target="_blank"
                    rel="noopener noreferrer"
                    className="min-h-[44px] rounded-xl border border-emerald-500/30 bg-emerald-500/10 hover:bg-emerald-500/20 text-emerald-400 font-bold text-xs flex items-center justify-center gap-2 active:scale-95 transition"
                  >
                    <MessageCircle className="w-4 h-4" />
                    <span>В WhatsApp</span>
                  </a>
                </div>
              </div>

              {/* Footer Actions */}
              <div className="pt-2 border-t border-slate-100 dark:border-white/10 flex items-center gap-2">
                <button
                  type="button"
                  onClick={handleClose}
                  className="flex-1 py-3 rounded-xl border border-slate-200 dark:border-white/10 text-slate-700 dark:text-slate-300 font-bold text-xs hover:bg-slate-50 dark:hover:bg-white/5 active:scale-95 transition"
                >
                  В расписание
                </button>

                <a
                  href={createdLesson.classroom_url || `/classroom/${createdLesson.id}`}
                  className="flex-1 py-3 rounded-xl bg-[#C6FF33] hover:bg-[#b5f21f] text-black font-extrabold text-xs flex items-center justify-center gap-1.5 shadow-sm active:scale-95 transition text-center"
                >
                  <Video className="w-4 h-4 fill-current" />
                  <span>Войти в класс</span>
                </a>
              </div>
            </div>
          ) : (
            /* ═════════════════════════════════════════════════════════════════ */
            /* VIEW B: FORM TO SCHEDULE THE LESSON                             */
            /* ═════════════════════════════════════════════════════════════════ */
            <div>
              {/* Header */}
              <div className="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-white/10 mb-4">
                <div className="flex items-center gap-2">
                  <div className="w-9 h-9 rounded-xl bg-purple-500/10 text-[#7D39EB] dark:text-[#C6FF33] flex items-center justify-center font-bold">
                    <Plus className="w-5 h-5" />
                  </div>
                  <div>
                    <h3 className="text-base font-extrabold dark:text-white text-slate-900 tracking-tight">
                      Запланировать урок
                    </h3>
                    <p className="text-[11px] text-slate-400">
                      Добавление занятия и создание ссылки
                    </p>
                  </div>
                </div>

                <button
                  type="button"
                  onClick={handleClose}
                  className="w-8 h-8 rounded-full bg-slate-100 dark:bg-white/10 flex items-center justify-center text-slate-600 dark:text-white/70"
                >
                  <X className="w-4 h-4" />
                </button>
              </div>

              {errorMessage && (
                <div className="mb-4 p-3 rounded-xl bg-rose-500/10 border border-rose-500/20 text-rose-500 text-xs font-semibold">
                  {errorMessage}
                </div>
              )}

              <form onSubmit={handleSubmit} className="space-y-4 text-xs">
                {/* Student Mode Switcher */}
                <div className="flex rounded-xl bg-slate-100 dark:bg-white/5 p-1 border border-slate-200/60 dark:border-white/5">
                  <button
                    type="button"
                    onClick={() => setStudentMode('existing')}
                    className={`flex-1 py-1.5 rounded-lg font-bold text-center transition ${
                      studentMode === 'existing'
                        ? 'bg-white dark:bg-white/10 dark:text-white text-slate-900 shadow-sm'
                        : 'text-slate-400 hover:text-slate-200'
                    }`}
                  >
                    Выбрать из базы
                  </button>
                  <button
                    type="button"
                    onClick={() => setStudentMode('new')}
                    className={`flex-1 py-1.5 rounded-lg font-bold text-center transition ${
                      studentMode === 'new'
                        ? 'bg-white dark:bg-white/10 dark:text-white text-slate-900 shadow-sm'
                        : 'text-slate-400 hover:text-slate-200'
                    }`}
                  >
                    + Новый ученик
                  </button>
                </div>

                {studentMode === 'existing' ? (
                  <div>
                    <label className="block text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-1">
                      Ученик
                    </label>
                    {isLoadingStudents ? (
                      <div className="flex items-center gap-2 py-2 text-slate-400">
                        <Loader2 className="w-4 h-4 animate-spin" />
                        <span>Загрузка учеников...</span>
                      </div>
                    ) : students.length > 0 ? (
                      <select
                        value={studentId}
                        onChange={(e) => setStudentId(e.target.value)}
                        required
                        className="w-full px-3 py-2.5 rounded-xl border border-slate-200 dark:border-white/10 dark:bg-white/5 bg-slate-50 dark:text-white text-slate-900 focus:outline-none focus:ring-2 focus:ring-purple-500/50"
                      >
                        {students.map((st) => (
                          <option key={st.id} value={st.id} className="dark:bg-[#18181B] text-slate-900 dark:text-white">
                            {st.name} {st.phone ? `(${st.phone})` : ''}
                          </option>
                        ))}
                      </select>
                    ) : (
                      <div className="text-slate-400 py-1">
                        У вас пока нет сохранённых учеников. Выберите «Новый ученик».
                      </div>
                    )}
                  </div>
                ) : (
                  <div className="space-y-2">
                    <div className="grid grid-cols-2 gap-2">
                      <div>
                        <label className="block text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-1">
                          Имя ученика *
                        </label>
                        <input
                          type="text"
                          required
                          placeholder="Например, Максим"
                          value={newStudentName}
                          onChange={(e) => setNewStudentName(e.target.value)}
                          className="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-white/10 dark:bg-white/5 bg-slate-50 dark:text-white text-slate-900 focus:outline-none focus:ring-2 focus:ring-purple-500/50"
                        />
                      </div>
                      <div>
                        <label className="block text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-1">
                          Телефон (опционально)
                        </label>
                        <input
                          type="tel"
                          placeholder="+375 (29) 123-45-67"
                          value={newStudentPhone}
                          onChange={(e) => setNewStudentPhone(e.target.value)}
                          className="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-white/10 dark:bg-white/5 bg-slate-50 dark:text-white text-slate-900 focus:outline-none focus:ring-2 focus:ring-purple-500/50"
                        />
                      </div>
                    </div>

                    {/* Guest student helpful guidance */}
                    <div className="p-2.5 rounded-xl bg-purple-500/10 border border-purple-500/20 text-slate-600 dark:text-slate-300 text-[11px] space-y-1">
                      <div className="flex items-center gap-1.5 font-bold text-[#7D39EB] dark:text-[#C6FF33]">
                        <Sparkles className="w-3.5 h-3.5 shrink-0" />
                        <span>Разовый урок без регистрации</span>
                      </div>
                      <p className="text-[10px] leading-relaxed">
                        После нажатия кнопки появится прямая ссылка на видеокласс. Ученик войдёт в 1 клик без аккаунта. Прогресс в базе сохраняется после регистрации.
                      </p>
                    </div>
                  </div>
                )}

                {/* Subject */}
                <div>
                  <label className="block text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-1">
                    Предмет
                  </label>
                  <input
                    type="text"
                    required
                    value={subject}
                    onChange={(e) => setSubject(e.target.value)}
                    placeholder="Математика, Физика, Английский..."
                    className="w-full px-3 py-2.5 rounded-xl border border-slate-200 dark:border-white/10 dark:bg-white/5 bg-slate-50 dark:text-white text-slate-900 focus:outline-none focus:ring-2 focus:ring-purple-500/50"
                  />
                </div>

                {/* Date and Start Time */}
                <div className="grid grid-cols-2 gap-2">
                  <div>
                    <label className="block text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-1">
                      Дата
                    </label>
                    <input
                      type="date"
                      required
                      value={startDate}
                      onChange={(e) => setStartDate(e.target.value)}
                      className="w-full px-3 py-2.5 rounded-xl border border-slate-200 dark:border-white/10 dark:bg-white/5 bg-slate-50 dark:text-white text-slate-900 focus:outline-none focus:ring-2 focus:ring-purple-500/50"
                    />
                  </div>
                  <div>
                    <label className="block text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-1">
                      Время начала
                    </label>
                    <input
                      type="time"
                      required
                      value={startTime}
                      onChange={(e) => setStartTime(e.target.value)}
                      className="w-full px-3 py-2.5 rounded-xl border border-slate-200 dark:border-white/10 dark:bg-white/5 bg-slate-50 dark:text-white text-slate-900 focus:outline-none focus:ring-2 focus:ring-purple-500/50"
                    />
                  </div>
                </div>

                {/* Duration selector */}
                <div>
                  <label className="block text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-1">
                    Длительность
                  </label>
                  <div className="grid grid-cols-4 gap-1.5">
                    {[
                      { m: 45, l: '45 мин' },
                      { m: 60, l: '60 мин' },
                      { m: 90, l: '90 мин' },
                      { m: 120, l: '2 часа' },
                    ].map((item) => (
                      <button
                        key={item.m}
                        type="button"
                        onClick={() => setDurationMinutes(item.m)}
                        className={`py-2 rounded-xl text-center font-bold transition ${
                          durationMinutes === item.m
                            ? 'bg-[#7D39EB] text-white shadow-sm'
                            : 'bg-slate-100 dark:bg-white/5 text-slate-500 dark:text-slate-400'
                        }`}
                      >
                        {item.l}
                      </button>
                    ))}
                  </div>
                </div>

                {/* Price & Payment Status */}
                <div className="grid grid-cols-2 gap-2">
                  <div>
                    <label className="block text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-1">
                      Стоимость (BYN)
                    </label>
                    <input
                      type="number"
                      min="0"
                      step="1"
                      required
                      value={price}
                      onChange={(e) => setPrice(e.target.value)}
                      className="w-full px-3 py-2.5 rounded-xl border border-slate-200 dark:border-white/10 dark:bg-white/5 bg-slate-50 dark:text-white text-slate-900 focus:outline-none focus:ring-2 focus:ring-purple-500/50"
                    />
                  </div>

                  <div>
                    <label className="block text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-1">
                      Оплата
                    </label>
                    <select
                      value={paymentStatus}
                      onChange={(e) => setPaymentStatus(e.target.value)}
                      className="w-full px-3 py-2.5 rounded-xl border border-slate-200 dark:border-white/10 dark:bg-white/5 bg-slate-50 dark:text-white text-slate-900 focus:outline-none focus:ring-2 focus:ring-purple-500/50"
                    >
                      <option value="unpaid" className="dark:bg-[#18181B] text-slate-900 dark:text-white">Ожидает</option>
                      <option value="paid" className="dark:bg-[#18181B] text-slate-900 dark:text-white">Оплачен</option>
                    </select>
                  </div>
                </div>

                {/* Notes */}
                <div>
                  <label className="block text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-1">
                    Заметки к уроку (тема, пожелания)
                  </label>
                  <textarea
                    rows={2}
                    value={notes}
                    onChange={(e) => setNotes(e.target.value)}
                    placeholder="Например: Повторить тригонометрические формулы"
                    className="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-white/10 dark:bg-white/5 bg-slate-50 dark:text-white text-slate-900 focus:outline-none focus:ring-2 focus:ring-purple-500/50"
                  />
                </div>

                {/* Submit button */}
                <motion.button
                  type="submit"
                  disabled={isSubmitting}
                  whileTap={{ scale: 0.97 }}
                  className="w-full min-h-[50px] rounded-2xl bg-gradient-to-r from-[#7D39EB] to-[#9333EA] text-white font-extrabold text-sm flex items-center justify-center gap-2 shadow-lg shadow-purple-600/30 active:scale-95 transition"
                >
                  {isSubmitting ? (
                    <>
                      <Loader2 className="w-5 h-5 animate-spin" />
                      <span>Сохранение урока...</span>
                    </>
                  ) : (
                    <>
                      <Check className="w-5 h-5" />
                      <span>Запланировать урок</span>
                    </>
                  )}
                </motion.button>
              </form>
            </div>
          )}
        </motion.div>
      </div>
    </AnimatePresence>
  );
}
