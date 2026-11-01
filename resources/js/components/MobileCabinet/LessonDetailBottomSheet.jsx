import React, { useState } from 'react';
import { motion, AnimatePresence } from 'motion/react';
import { 
  Video, Clock, User, Calendar, CreditCard, MessageCircle, 
  X, Check, AlertCircle, Sparkles, Copy, CheckCheck, Loader2, 
  Trash2, ExternalLink, BookOpen 
} from 'lucide-react';
import axios from 'axios';
import UserAvatar from './UserAvatar';

export default function LessonDetailBottomSheet({ 
  lesson, 
  isOpen, 
  onClose, 
  onOpenChat,
  onLessonUpdated,
}) {
  if (!lesson) return null;

  const isPaid = lesson.payment_status === 'paid';
  const isCancelled = lesson.status === 'cancelled';

  // Maya AI Prep state
  const [showAiPrep, setShowAiPrep] = useState(false);
  const [aiTopic, setAiTopic] = useState('');
  const [isGeneratingPrep, setIsGeneratingPrep] = useState(false);
  const [aiPlan, setAiPlan] = useState(null);
  const [isCopied, setIsCopied] = useState(false);

  // Homework assignment state
  const [showHomeworkForm, setShowHomeworkForm] = useState(false);
  const [homeworkTitle, setHomeworkTitle] = useState('');
  const [homeworkInstructions, setHomeworkInstructions] = useState('');
  const [homeworkDueDays, setHomeworkDueDays] = useState(3);
  const [isSubmittingHomework, setIsSubmittingHomework] = useState(false);
  const [homeworkSuccess, setHomeworkSuccess] = useState(null);

  // Reschedule state
  const [isRescheduling, setIsRescheduling] = useState(false);
  const [rescheduleDate, setRescheduleDate] = useState(lesson.date || '');
  const [rescheduleTime, setRescheduleTime] = useState(
    lesson.time_formatted ? lesson.time_formatted.split(' — ')[0] : '15:00'
  );
  const [isSubmittingReschedule, setIsSubmittingReschedule] = useState(false);

  // Mark as paid state
  const [isMarkingPaid, setIsMarkingPaid] = useState(false);
  const [isCancelling, setIsCancelling] = useState(false);
  const [feedbackMessage, setFeedbackMessage] = useState(null);

  // Student Invite URL copy state
  const [isInviteCopied, setIsInviteCopied] = useState(false);

  const handleCopyStudentInvite = () => {
    const inviteUrl = lesson.student_invite_url || (window.location.origin + `/classroom/${lesson.id}`);
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
    setIsInviteCopied(true);
    setTimeout(() => setIsInviteCopied(false), 2500);
  };

  const handleAssignHomework = async (e) => {
    e.preventDefault();
    if (!homeworkTitle.trim()) return;
    setIsSubmittingHomework(true);
    setHomeworkSuccess(null);
    try {
      const res = await axios.post(`/api/mobile/lessons/${lesson.id}/homework`, {
        title: homeworkTitle.trim(),
        instructions: homeworkInstructions.trim(),
        due_days: homeworkDueDays,
      });
      if (res.data?.success) {
        setHomeworkSuccess(res.data.message);
        setHomeworkTitle('');
        setHomeworkInstructions('');
        setTimeout(() => setShowHomeworkForm(false), 2000);
      }
    } catch (err) {
      alert(err.response?.data?.error || 'Не удалось отправить домашнее задание');
    } finally {
      setIsSubmittingHomework(false);
    }
  };

  const handleMarkPaid = async () => {
    setIsMarkingPaid(true);
    try {
      const res = await axios.post(`/api/mobile/lessons/${lesson.id}/mark-paid`);
      if (res.data?.success) {
        setFeedbackMessage('✓ Оплата зафиксирована!');
        if (onLessonUpdated) {
          onLessonUpdated({
            ...lesson,
            payment_status: 'paid',
            status: res.data.status || lesson.status,
          });
        }
      }
    } catch (e) {
      console.error(e);
      alert('Ошибка фиксации оплаты');
    } finally {
      setIsMarkingPaid(false);
    }
  };

  const handleGenerateAiPrep = async () => {
    setIsGeneratingPrep(true);
    setAiPlan(null);
    try {
      const res = await axios.post(`/api/mobile/lessons/${lesson.id}/generate-prep`, {
        custom_topic: aiTopic || lesson.subject,
      });
      if (res.data?.plan) {
        setAiPlan(res.data.plan);
      }
    } catch (e) {
      console.error(e);
      alert(e.response?.data?.error || 'Не удалось сгенерировать план занятия.');
    } finally {
      setIsGeneratingPrep(false);
    }
  };

  const handleCopyPlan = () => {
    if (!aiPlan) return;
    navigator.clipboard.writeText(aiPlan);
    setIsCopied(true);
    setTimeout(() => setIsCopied(false), 2000);
  };

  const handleRescheduleSubmit = async (e) => {
    e.preventDefault();
    setIsSubmittingReschedule(true);
    try {
      const fullStartTime = `${rescheduleDate} ${rescheduleTime}:00`;
      const res = await axios.post(`/api/mobile/lessons/${lesson.id}/reschedule`, {
        start_time: fullStartTime,
      });
      if (res.data?.success) {
        setFeedbackMessage(res.data.message);
        setIsRescheduling(false);
        if (onLessonUpdated) {
          onLessonUpdated({
            ...lesson,
            date: res.data.date,
            time_formatted: res.data.time_formatted,
          });
        }
      }
    } catch (e) {
      console.error(e);
      alert('Ошибка при переносе урока');
    } finally {
      setIsSubmittingReschedule(false);
    }
  };

  const handleCancelLesson = async () => {
    if (!confirm('Вы уверены, что хотите отменить этот урок?')) return;

    setIsCancelling(true);
    try {
      const res = await axios.post(`/api/mobile/lessons/${lesson.id}/cancel`);
      if (res.data?.success) {
        setFeedbackMessage('Урок отменён.');
        if (onLessonUpdated) {
          onLessonUpdated({
            ...lesson,
            status: 'cancelled',
          });
        }
      }
    } catch (e) {
      console.error(e);
      alert('Ошибка при отмене урока');
    } finally {
      setIsCancelling(false);
    }
  };

  return (
    <AnimatePresence>
      {isOpen && (
        <div className="fixed inset-0 z-[70] flex items-end sm:items-center justify-center p-0 sm:p-4">
          {/* Backdrop Blur */}
          <motion.div
            initial={{ opacity: 0 }}
            animate={{ opacity: 1 }}
            exit={{ opacity: 0 }}
            onClick={onClose}
            className="fixed inset-0 bg-black/60 backdrop-blur-sm"
          />

          {/* Bottom Sheet Card / Modal with Spring physics */}
          <motion.div
            initial={{ y: '100%' }}
            animate={{ y: 0 }}
            exit={{ y: '100%' }}
            transition={{ type: 'spring', damping: 30, stiffness: 350 }}
            className="relative w-full max-w-lg rounded-t-[32px] sm:rounded-[28px] p-6 pb-10 sm:pb-7 z-10 border-t sm:border border-white/10 dark:bg-[#121214] bg-white shadow-2xl transition-colors duration-300 max-h-[90vh] sm:max-h-[85vh] overflow-y-auto"
          >
            {/* Swipe Down Drag Indicator Handle */}
            <div className="w-12 h-1.5 rounded-full bg-slate-300 dark:bg-white/20 mx-auto mb-4 sm:hidden" />

            {feedbackMessage && (
              <div className="mb-4 p-3 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-500 text-xs font-bold text-center">
                {feedbackMessage}
              </div>
            )}

            {/* Header with Title and Close Button */}
            <div className="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-white/10">
              <div className="flex items-center gap-3">
                <UserAvatar
                  src={lesson.partner_avatar}
                  name={lesson.partner_name}
                  initials={lesson.partner_initials}
                  size="md"
                  rounded="rounded-2xl"
                  innerRounded="rounded-[14px]"
                />
                <div>
                  <div className="flex items-center gap-2">
                    <h3 className="text-base font-extrabold dark:text-white text-slate-900 tracking-tight">
                      {lesson.partner_name}
                    </h3>
                    <span className="text-[10px] font-mono text-slate-400">
                      #{lesson.id}
                    </span>
                  </div>
                  <p className="text-xs font-semibold text-slate-500 dark:text-slate-400">
                    {lesson.subject}
                  </p>
                </div>
              </div>

              <button
                type="button"
                onClick={onClose}
                className="w-8 h-8 rounded-full bg-slate-100 dark:bg-white/10 flex items-center justify-center text-slate-600 dark:text-white/70"
              >
                <X className="w-4 h-4" />
              </button>
            </div>

            {/* Lesson Info Grid */}
            <div className="grid grid-cols-2 gap-3 my-4">
              <div className="p-3.5 rounded-2xl bg-slate-50 dark:bg-white/5 border border-slate-200/80 dark:border-white/5">
                <div className="flex items-center gap-2 text-slate-400 text-xs font-medium mb-1">
                  <Clock className="w-3.5 h-3.5" />
                  <span>Время</span>
                </div>
                <div className="text-sm font-bold dark:text-white text-slate-900">
                  {lesson.time_formatted}
                </div>
                <div className="text-[11px] text-slate-400">
                  {lesson.duration_minutes} минут
                </div>
              </div>

              <div className="p-3.5 rounded-2xl bg-slate-50 dark:bg-white/5 border border-slate-200/80 dark:border-white/5">
                <div className="flex items-center gap-2 text-slate-400 text-xs font-medium mb-1">
                  <CreditCard className="w-3.5 h-3.5" />
                  <span>Оплата</span>
                </div>
                <div className="text-sm font-bold dark:text-white text-slate-900">
                  {lesson.price} BYN
                </div>
                <span className={`inline-block text-[10px] font-extrabold uppercase mt-0.5 px-1.5 py-0.2 rounded-md ${
                  isPaid ? 'bg-emerald-500/15 text-emerald-500' : 'bg-amber-500/15 text-amber-500'
                }`}>
                  {isPaid ? '✓ Оплачен' : '⏳ Ожидает оплаты'}
                </span>
              </div>
            </div>

            {/* Parent & Notes details */}
            {lesson.parent_name && (
              <div className="mb-3 px-3.5 py-2.5 rounded-xl bg-slate-50 dark:bg-white/5 border border-slate-200/60 dark:border-white/5 flex items-center gap-2 text-xs text-slate-600 dark:text-slate-300">
                <User className="w-4 h-4 text-slate-400 shrink-0" />
                <span>Родитель: <strong className="text-slate-900 dark:text-white">{lesson.parent_name}</strong></span>
              </div>
            )}

            {lesson.notes && (
              <div className="mb-3 p-3 rounded-xl bg-amber-500/10 border border-amber-500/20 text-xs text-amber-700 dark:text-amber-300">
                <span className="font-bold block text-[10px] uppercase tracking-wider mb-0.5">Заметки к уроку:</span>
                <p className="whitespace-pre-line">{lesson.notes}</p>
              </div>
            )}

            {/* ─── MAYA AI PREP EXPANDABLE SECTION ─── */}
            <div className="mb-4 rounded-2xl border border-amber-500/30 dark:bg-amber-500/5 bg-amber-50/50 p-3.5 space-y-2.5">
              <div className="flex items-center justify-between">
                <div className="flex items-center gap-2">
                  <div className="w-7 h-7 rounded-lg bg-amber-500 text-white flex items-center justify-center font-bold text-xs shadow-sm">
                    ⚡
                  </div>
                  <div>
                    <h4 className="text-xs font-bold text-amber-900 dark:text-amber-300">
                      ИИ-шпаргалка к занятию
                    </h4>
                    <p className="text-[10px] text-amber-700 dark:text-amber-400">
                      Разминка • Ловушки РИКЗ • 3 задачи • Финал
                    </p>
                  </div>
                </div>

                <button
                  type="button"
                  onClick={() => setShowAiPrep(!showAiPrep)}
                  className="text-xs font-bold text-amber-700 dark:text-amber-400 hover:underline"
                >
                  {showAiPrep ? 'Скрыть' : 'Открыть'}
                </button>
              </div>

              {showAiPrep && (
                <div className="pt-2 border-t border-amber-500/20 space-y-2.5 text-xs">
                  <div className="flex gap-2">
                    <input
                      type="text"
                      value={aiTopic}
                      onChange={(e) => setAiTopic(e.target.value)}
                      placeholder={`Тема (по умолч.: ${lesson.subject})`}
                      className="flex-1 px-3 py-2 rounded-xl border border-amber-300 dark:border-amber-500/30 dark:bg-[#18181B] bg-white text-xs dark:text-white text-slate-900 focus:outline-none"
                    />
                    <button
                      type="button"
                      onClick={handleGenerateAiPrep}
                      disabled={isGeneratingPrep}
                      className="px-3 py-2 rounded-xl bg-amber-500 hover:bg-amber-600 text-white font-bold flex items-center gap-1.5 shrink-0 shadow-sm transition"
                    >
                      {isGeneratingPrep ? (
                        <Loader2 className="w-4 h-4 animate-spin" />
                      ) : (
                        <Sparkles className="w-4 h-4" />
                      )}
                      <span>{aiPlan ? 'Обновить' : 'Составить'}</span>
                    </button>
                  </div>

                  {aiPlan && (
                    <div className="p-3 rounded-xl bg-white dark:bg-[#18181B] border border-amber-500/20 text-xs dark:text-slate-200 text-slate-800 space-y-2">
                      <div className="flex items-center justify-between border-b border-amber-500/20 pb-1.5">
                        <span className="text-[10px] font-mono uppercase font-bold text-amber-600">
                          План занятия • ЦТ 2026
                        </span>
                        <button
                          type="button"
                          onClick={handleCopyPlan}
                          className="flex items-center gap-1 text-[11px] font-bold text-amber-600 hover:underline"
                        >
                          {isCopied ? (
                            <>
                              <CheckCheck className="w-3.5 h-3.5 text-emerald-500" />
                              <span className="text-emerald-500">Скопировано!</span>
                            </>
                          ) : (
                            <>
                              <Copy className="w-3.5 h-3.5" />
                              <span>Скопировать</span>
                            </>
                          )}
                        </button>
                      </div>

                      <div className="max-h-48 overflow-y-auto whitespace-pre-line text-[11px] leading-relaxed">
                        {aiPlan}
                      </div>
                    </div>
                  )}
                </div>
              )}
            </div>

            {/* ─── HOMEWORK ASSIGNMENT SECTION ─── */}
            <div className="mb-4 rounded-2xl border border-purple-500/20 dark:bg-purple-950/20 bg-purple-50/50 p-3.5 space-y-2.5">
              <div className="flex items-center justify-between">
                <div className="flex items-center gap-2">
                  <div className="w-7 h-7 rounded-lg bg-[#7D39EB] text-white flex items-center justify-center font-bold text-xs shadow-sm">
                    <BookOpen className="w-4 h-4" />
                  </div>
                  <div>
                    <h4 className="text-xs font-bold dark:text-white text-slate-900">
                      Домашнее задание ученику
                    </h4>
                    <p className="text-[10px] text-slate-500 dark:text-slate-400">
                      Задать ДЗ с дедлайном
                    </p>
                  </div>
                </div>

                <button
                  type="button"
                  onClick={() => setShowHomeworkForm(!showHomeworkForm)}
                  className="text-xs font-bold text-[#7D39EB] dark:text-[#C6FF33] hover:underline"
                >
                  {showHomeworkForm ? 'Скрыть' : 'Задать ДЗ'}
                </button>
              </div>

              {showHomeworkForm && (
                <form onSubmit={handleAssignHomework} className="pt-2 border-t border-purple-500/20 space-y-2.5 text-xs">
                  <div>
                    <label className="block text-[10px] uppercase font-bold text-slate-400 mb-1">
                      Тема / Задание
                    </label>
                    <input
                      type="text"
                      required
                      value={homeworkTitle}
                      onChange={(e) => setHomeworkTitle(e.target.value)}
                      placeholder="Например: Параграф 12, задачи 1–8"
                      className="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-white/10 dark:bg-[#18181B] bg-white text-xs dark:text-white text-slate-900 focus:outline-none focus:ring-1 focus:ring-purple-500"
                    />
                  </div>

                  <div>
                    <label className="block text-[10px] uppercase font-bold text-slate-400 mb-1">
                      Комментарий или ссылки (необязательно)
                    </label>
                    <textarea
                      rows={2}
                      value={homeworkInstructions}
                      onChange={(e) => setHomeworkInstructions(e.target.value)}
                      placeholder="Указания, формулы, страницы..."
                      className="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-white/10 dark:bg-[#18181B] bg-white text-xs dark:text-white text-slate-900 focus:outline-none focus:ring-1 focus:ring-purple-500 resize-none"
                    />
                  </div>

                  <div className="flex items-center justify-between gap-2">
                    <div className="flex items-center gap-1 text-[11px] text-slate-500">
                      <span>Срок:</span>
                      {[2, 3, 7].map((days) => (
                        <button
                          key={days}
                          type="button"
                          onClick={() => setHomeworkDueDays(days)}
                          className={`px-2 py-0.5 rounded-lg font-bold transition ${
                            homeworkDueDays === days
                              ? 'bg-[#7D39EB] text-white'
                              : 'bg-slate-200 dark:bg-white/10 text-slate-700 dark:text-slate-300'
                          }`}
                        >
                          {days === 7 ? '1 нед.' : `${days} дн.`}
                        </button>
                      ))}
                    </div>

                    <button
                      type="submit"
                      disabled={isSubmittingHomework || !homeworkTitle.trim()}
                      className="px-3.5 py-2 rounded-xl bg-[#7D39EB] hover:bg-purple-700 text-white font-bold flex items-center gap-1.5 shadow-sm active:scale-95 transition disabled:opacity-50"
                    >
                      {isSubmittingHomework ? (
                        <Loader2 className="w-3.5 h-3.5 animate-spin" />
                      ) : (
                        <Check className="w-3.5 h-3.5" />
                      )}
                      <span>Отправить</span>
                    </button>
                  </div>

                  {homeworkSuccess && (
                    <div className="p-2 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-500 text-[11px] font-bold flex items-center gap-1">
                      <Check className="w-3.5 h-3.5" />
                      {homeworkSuccess}
                    </div>
                  )}
                </form>
              )}
            </div>

            {/* ─── RESCHEDULE ACCORDION ─── */}
            {isRescheduling && (
              <form onSubmit={handleRescheduleSubmit} className="mb-4 p-3.5 rounded-2xl bg-slate-100 dark:bg-white/5 border border-slate-200 dark:border-white/10 space-y-3">
                <div className="flex items-center justify-between text-xs font-bold text-slate-700 dark:text-slate-300">
                  <span>Перенос занятия</span>
                  <button
                    type="button"
                    onClick={() => setIsRescheduling(false)}
                    className="text-slate-400 text-xs hover:underline"
                  >
                    Отмена
                  </button>
                </div>
                <div className="grid grid-cols-2 gap-2 text-xs">
                  <div>
                    <label className="block text-[10px] text-slate-400 uppercase font-bold mb-1">Новая дата</label>
                    <input
                      type="date"
                      required
                      value={rescheduleDate}
                      onChange={(e) => setRescheduleDate(e.target.value)}
                      className="w-full px-2.5 py-2 rounded-xl border border-slate-200 dark:border-white/10 dark:bg-[#18181B] bg-white text-xs dark:text-white"
                    />
                  </div>
                  <div>
                    <label className="block text-[10px] text-slate-400 uppercase font-bold mb-1">Новое время</label>
                    <input
                      type="time"
                      required
                      value={rescheduleTime}
                      onChange={(e) => setRescheduleTime(e.target.value)}
                      className="w-full px-2.5 py-2 rounded-xl border border-slate-200 dark:border-white/10 dark:bg-[#18181B] bg-white text-xs dark:text-white"
                    />
                  </div>
                </div>

                <button
                  type="submit"
                  disabled={isSubmittingReschedule}
                  className="w-full py-2.5 rounded-xl bg-purple-600 hover:bg-purple-700 text-white font-bold text-xs flex items-center justify-center gap-2 transition"
                >
                  {isSubmittingReschedule ? <Loader2 className="w-4 h-4 animate-spin" /> : <Check className="w-4 h-4" />}
                  <span>Подтвердить перенос</span>
                </button>
              </form>
            )}

            {/* ─── ONE-CLICK GUEST INVITE FOR STUDENT ─── */}
            {!isCancelled && (
              <div className="p-3.5 rounded-2xl bg-gradient-to-r from-purple-500/10 to-indigo-500/10 border border-purple-500/20 space-y-2">
                <div className="flex items-center justify-between">
                  <span className="text-[10px] font-extrabold uppercase tracking-wider text-[#7D39EB] dark:text-[#C6FF33] flex items-center gap-1.5">
                    <Sparkles className="w-3.5 h-3.5" />
                    <span>Ссылка для входа ученика (1 клик)</span>
                  </span>
                  <span className="text-[10px] text-slate-400">
                    Без регистрации
                  </span>
                </div>
                <div className="flex items-center gap-2">
                  <div className="flex-1 p-2 rounded-xl bg-white/60 dark:bg-black/40 border border-slate-200/80 dark:border-white/10 font-mono text-[11px] text-slate-800 dark:text-slate-200 truncate select-all">
                    {lesson.student_invite_url || (window.location.origin + `/classroom/${lesson.id}`)}
                  </div>
                  <button
                    type="button"
                    onClick={handleCopyStudentInvite}
                    className={`px-3 py-2 rounded-xl text-xs font-bold flex items-center gap-1 shrink-0 transition active:scale-95 ${
                      isInviteCopied
                        ? 'bg-emerald-500 text-white'
                        : 'bg-[#7D39EB] text-white hover:bg-purple-700'
                    }`}
                  >
                    {isInviteCopied ? (
                      <>
                        <Check className="w-3.5 h-3.5" />
                        <span>Скопировано!</span>
                      </>
                    ) : (
                      <>
                        <Copy className="w-3.5 h-3.5" />
                        <span>Копировать</span>
                      </>
                    )}
                  </button>
                </div>
                <p className="text-[10px] text-slate-400 leading-tight">
                  Отправьте ученику в Telegram или WhatsApp. Он сразу подключится к уроку. Прогресс в базе будет сохранён, если ученик зарегистрирован.
                </p>
              </div>
            )}

            {/* ─── PRIMARY ACTIONS ─── */}
            <div className="space-y-2.5">
              {!isCancelled && (
                <a
                  href={lesson.classroom_url}
                  className="w-full min-h-[50px] rounded-2xl bg-[#C6FF33] hover:bg-[#b8f522] text-black font-extrabold text-sm flex items-center justify-center gap-2 shadow-lg shadow-[#C6FF33]/20 active:scale-95 transition-all"
                >
                  <Video className="w-5 h-5 fill-current" />
                  <span>Войти в виртуальный класс (1 клик)</span>
                </a>
              )}

              {/* Pay Online Button for Unpaid Lessons */}
              {!isPaid && !isCancelled && (
                <a
                  href={lesson.checkout_url || `/checkout/${lesson.id}`}
                  className="w-full min-h-[48px] rounded-2xl bg-[#7D39EB] hover:bg-[#6c2ee0] text-white font-extrabold text-sm flex items-center justify-center gap-2 shadow-lg shadow-[#7D39EB]/25 active:scale-95 transition-all"
                >
                  <CreditCard className="w-5 h-5" />
                  <span>Оплатить урок ({lesson.price} BYN)</span>
                </a>
              )}

              {/* Secondary Row 1: Mark Paid & Google Calendar */}
              <div className="grid grid-cols-2 gap-2">
                {!isPaid && lesson.can_mark_paid && (
                  <button
                    type="button"
                    onClick={handleMarkPaid}
                    disabled={isMarkingPaid}
                    className="min-h-[44px] rounded-xl border border-emerald-500/30 bg-emerald-500/10 text-emerald-500 hover:bg-emerald-500/20 text-xs font-bold flex items-center justify-center gap-1.5 active:scale-95 transition"
                  >
                    {isMarkingPaid ? (
                      <Loader2 className="w-4 h-4 animate-spin" />
                    ) : (
                      <Check className="w-4 h-4" />
                    )}
                    <span>Отметить оплату</span>
                  </button>
                )}

                {lesson.gcal_link && (
                  <a
                    href={lesson.gcal_link}
                    target="_blank"
                    rel="noopener noreferrer"
                    className={`min-h-[44px] rounded-xl border border-slate-200 dark:border-white/10 dark:bg-white/5 bg-slate-100 dark:text-white text-slate-800 text-xs font-bold flex items-center justify-center gap-1.5 active:scale-95 transition ${
                      isPaid || !lesson.can_mark_paid ? 'col-span-2' : ''
                    }`}
                  >
                    <Calendar className="w-4 h-4 text-sky-500" />
                    <span>В Google Calendar</span>
                    <ExternalLink className="w-3 h-3 text-slate-400" />
                  </a>
                )}
              </div>

              {/* Secondary Row 2: Chat & Reschedule */}
              <div className="grid grid-cols-2 gap-2">
                <button
                  type="button"
                  onClick={() => {
                    onClose();
                    onOpenChat(lesson.partner_id);
                  }}
                  className="min-h-[44px] rounded-xl border border-slate-200 dark:border-white/10 dark:bg-white/5 bg-slate-100 dark:text-white text-slate-800 text-xs font-bold flex items-center justify-center gap-1.5 active:scale-95 transition"
                >
                  <MessageCircle className="w-4 h-4" />
                  <span>Чат с учеником</span>
                </button>

                {!isCancelled && (
                  <button
                    type="button"
                    onClick={() => setIsRescheduling(!isRescheduling)}
                    className="min-h-[44px] rounded-xl border border-slate-200 dark:border-white/10 dark:bg-white/5 bg-slate-100 dark:text-white text-slate-800 text-xs font-bold flex items-center justify-center gap-1.5 active:scale-95 transition"
                  >
                    <Clock className="w-4 h-4" />
                    <span>Перенести урок</span>
                  </button>
                )}
              </div>

              {/* Cancel Lesson Button */}
              {!isCancelled && (
                <button
                  type="button"
                  onClick={handleCancelLesson}
                  disabled={isCancelling}
                  className="w-full py-2.5 rounded-xl text-rose-500 dark:text-rose-400 text-xs font-bold hover:bg-rose-500/10 transition flex items-center justify-center gap-1.5"
                >
                  {isCancelling ? <Loader2 className="w-4 h-4 animate-spin" /> : <Trash2 className="w-3.5 h-3.5" />}
                  <span>Отменить занятие</span>
                </button>
              )}
            </div>
          </motion.div>
        </div>
      )}
    </AnimatePresence>
  );
}
