import React, { useState, useEffect, useMemo } from 'react';
import { motion, AnimatePresence } from 'motion/react';
import { 
  Video, Calendar, Clock, Plus, ChevronRight, Sparkles, 
  ChevronLeft, Printer, Download, CheckCircle2, Copy, Share2, Check, Link as LinkIcon,
  Inbox, AlertCircle, XCircle, LayoutGrid, List
} from 'lucide-react';
import UserAvatar from './UserAvatar';
import AvailabilityBottomSheet from './AvailabilityBottomSheet';
import GoogleCalendarView from './GoogleCalendarView';

// Timezone-safe local ISO helpers (avoiding UTC conversion shifts)
const parseIsoDate = (isoStr) => {
  if (!isoStr) return new Date();
  const parts = isoStr.split('-');
  if (parts.length === 3) {
    return new Date(Number(parts[0]), Number(parts[1]) - 1, Number(parts[2]), 12, 0, 0);
  }
  return new Date();
};

const formatLocalIso = (date) => {
  const y = date.getFullYear();
  const m = String(date.getMonth() + 1).padStart(2, '0');
  const d = String(date.getDate()).padStart(2, '0');
  return `${y}-${m}-${d}`;
};

const shiftIsoDays = (isoStr, deltaDays) => {
  const date = parseIsoDate(isoStr);
  date.setDate(date.getDate() + deltaDays);
  return formatLocalIso(date);
};

const getMondayOfIso = (isoStr) => {
  const date = parseIsoDate(isoStr);
  const day = date.getDay();
  const diff = date.getDate() - day + (day === 0 ? -6 : 1);
  date.setDate(diff);
  return formatLocalIso(date);
};

export default function TabLessons({
  lessons = [],
  upcomingLesson = null,
  pendingRequests = [],
  onConfirmRequest,
  onRejectRequest,
  availability = [],
  onSaveAvailability,
  isTutor = false,
  weekStart,
  weekLabel = '',
  weekSpanFormatted = '',
  onWeekChange,
  onSelectLesson,
  onOpenQuickCreate,
  bookingUrl = '',
  bookingShortUrl = '',
}) {
  const todayIso = useMemo(() => formatLocalIso(new Date()), []);
  const [selectedDate, setSelectedDate] = useState(todayIso);
  const [copiedBooking, setCopiedBooking] = useState(false);
  const [isAvailabilityOpen, setIsAvailabilityOpen] = useState(false);

  // Optional Calendar View: 'list' (compact daily) vs 'grid' (Google Calendar hourly week grid)
  const [calendarView, setCalendarView] = useState(() => {
    if (typeof window !== 'undefined') {
      return localStorage.getItem('edusfera_calendar_view') || 'list';
    }
    return 'list';
  });

  const handleToggleCalendarView = (mode) => {
    setCalendarView(mode);
    if (typeof window !== 'undefined') {
      localStorage.setItem('edusfera_calendar_view', mode);
      window.dispatchEvent(new CustomEvent('edusfera:calendar_view_changed', { detail: { mode } }));
    }
  };

  useEffect(() => {
    const handleSync = (e) => {
      const mode = e?.detail?.mode || localStorage.getItem('edusfera_calendar_view');
      if (mode && (mode === 'list' || mode === 'grid')) {
        setCalendarView(mode);
      }
    };
    window.addEventListener('edusfera:calendar_view_changed', handleSync);
    window.addEventListener('storage', handleSync);
    return () => {
      window.removeEventListener('edusfera:calendar_view_changed', handleSync);
      window.removeEventListener('storage', handleSync);
    };
  }, []);

  const handleCopyBookingUrl = async () => {
    if (!bookingUrl) return;
    try {
      await navigator.clipboard.writeText(bookingUrl);
      setCopiedBooking(true);
      setTimeout(() => setCopiedBooking(false), 2000);
    } catch (e) {
      console.error(e);
    }
  };

  const handleShareBookingUrl = async () => {
    if (!bookingUrl) return;
    if (navigator.share) {
      try {
        await navigator.share({
          title: 'Запись на урок — Edusfera',
          text: 'Запишитесь на индивидуальный урок через Edusfera:',
          url: bookingUrl,
        });
        return;
      } catch (e) {
        if (e.name !== 'AbortError') console.error(e);
      }
    }
    handleCopyBookingUrl();
  };

  const days = useMemo(() => {
    const mondayStr = weekStart ? getMondayOfIso(weekStart) : getMondayOfIso(todayIso);
    const monday = parseIsoDate(mondayStr);

    const weekDays = [];
    const russianDays = ['Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб', 'Вс'];

    for (let i = 0; i < 7; i++) {
      const d = new Date(monday);
      d.setDate(monday.getDate() + i);
      const iso = formatLocalIso(d);
      const hasLessons = lessons.some((l) => l.date === iso && l.status !== 'cancelled');

      weekDays.push({
        iso,
        dayName: russianDays[i],
        dayNumber: d.getDate(),
        isToday: iso === todayIso,
        hasLessons,
      });
    }
    return weekDays;
  }, [weekStart, lessons, todayIso]);

  const computedWeekLabel = useMemo(() => {
    const mondayStr = weekStart ? getMondayOfIso(weekStart) : getMondayOfIso(todayIso);
    const monday = parseIsoDate(mondayStr);
    const months = [
      'Январь', 'Февраль', 'Март', 'Апрель', 'Май', 'Июнь',
      'Июль', 'Август', 'Сентябрь', 'Октябрь', 'Ноябрь', 'Декабрь'
    ];
    return `${months[monday.getMonth()]} ${monday.getFullYear()}`;
  }, [weekStart, todayIso]);

  // If selectedDate is not in the current week, pick Monday or today if today is in the week
  useEffect(() => {
    if (!days || days.length === 0) return;
    const hasSelectedInWeek = days.some((d) => d.iso === selectedDate);
    if (!hasSelectedInWeek) {
      const todayInWeek = days.find((d) => d.isToday);
      setSelectedDate(todayInWeek ? todayInWeek.iso : days[0].iso);
    }
  }, [days, selectedDate]);

  const currentDayLessons = lessons.filter((l) => l.date === selectedDate);

  const handlePrevWeek = () => {
    const baseMonday = weekStart ? getMondayOfIso(weekStart) : getMondayOfIso(todayIso);
    const prevMonday = shiftIsoDays(baseMonday, -7);
    if (onWeekChange) onWeekChange(prevMonday);
  };

  const handleNextWeek = () => {
    const baseMonday = weekStart ? getMondayOfIso(weekStart) : getMondayOfIso(todayIso);
    const nextMonday = shiftIsoDays(baseMonday, 7);
    if (onWeekChange) onWeekChange(nextMonday);
  };

  const handleTodayClick = () => {
    const thisMonday = getMondayOfIso(todayIso);
    if (onWeekChange) onWeekChange(thisMonday);
    setSelectedDate(todayIso);
  };

  return (
    <div className="space-y-5 pb-32">
      {/* ─── 0. PENDING LESSON REQUESTS FROM STUDENTS (1-Click confirmation) ─── */}
      {isTutor && pendingRequests && pendingRequests.length > 0 && (
        <motion.div
          initial={{ opacity: 0, y: -10 }}
          animate={{ opacity: 1, y: 0 }}
          className="rounded-3xl p-4 border border-amber-500/30 dark:bg-amber-950/20 bg-amber-50/70 shadow-lg shadow-amber-500/5 space-y-3"
        >
          <div className="flex items-center justify-between">
            <div className="flex items-center gap-2">
              <span className="w-2.5 h-2.5 rounded-full bg-amber-500 animate-pulse" />
              <h3 className="text-xs font-black uppercase tracking-wider text-amber-700 dark:text-amber-400">
                Новые заявки от учеников ({pendingRequests.length})
              </h3>
            </div>
            <span className="text-[10px] font-bold px-2 py-0.5 rounded-full bg-amber-500/20 text-amber-600 dark:text-amber-300">
              Требуют ответа
            </span>
          </div>

          <div className="space-y-2.5">
            {pendingRequests.map((req) => (
              <div
                key={req.id}
                className="p-3.5 rounded-2xl border border-white/60 dark:border-white/10 dark:bg-white/[0.04] bg-white shadow-sm space-y-2.5"
              >
                <div className="flex items-start justify-between gap-2">
                  <div className="flex items-center gap-2.5 min-w-0">
                    <UserAvatar
                      src={req.student_avatar}
                      name={req.student_name}
                      size="sm"
                    />
                    <div className="min-w-0">
                      <h4 className="text-xs font-bold dark:text-white text-slate-900 truncate">
                        {req.student_name}
                      </h4>
                      <p className="text-[11px] text-slate-500 dark:text-slate-400">
                        {req.subject} • <span className="font-bold text-[#7D39EB] dark:text-[#C6FF33]">{req.price} BYN</span>
                      </p>
                    </div>
                  </div>

                  <span className="text-[11px] font-mono font-bold text-slate-600 dark:text-slate-300 shrink-0">
                    {req.date_formatted} в {req.time_formatted}
                  </span>
                </div>

                {req.notes && (
                  <p className="text-[11px] text-slate-600 dark:text-slate-300 italic px-2.5 py-1 rounded-xl bg-slate-50 dark:bg-white/5 border border-slate-100 dark:border-white/5">
                    «{req.notes}»
                  </p>
                )}

                <div className="flex items-center gap-2 pt-1">
                  <button
                    type="button"
                    onClick={() => onConfirmRequest && onConfirmRequest(req.id)}
                    className="flex-1 min-h-[38px] rounded-xl bg-[#7D39EB] hover:bg-purple-700 text-white text-xs font-bold flex items-center justify-center gap-1.5 shadow-sm active:scale-95 transition"
                  >
                    <Check className="w-3.5 h-3.5" />
                    <span>Принять занятие</span>
                  </button>

                  <button
                    type="button"
                    onClick={() => onRejectRequest && onRejectRequest(req.id)}
                    className="px-3 min-h-[38px] rounded-xl border border-slate-200 dark:border-white/10 text-slate-500 dark:text-slate-400 hover:text-red-500 hover:border-red-500/30 text-xs font-bold flex items-center justify-center gap-1 active:scale-95 transition"
                  >
                    <XCircle className="w-3.5 h-3.5" />
                    <span>Отклонить</span>
                  </button>
                </div>
              </div>
            ))}
          </div>
        </motion.div>
      )}

      {/* ─── 1. URGENT / UPCOMING HERO CARD (1-Click Action) ─── */}
      {upcomingLesson ? (
        <motion.div
          initial={{ opacity: 0, y: 15 }}
          animate={{ opacity: 1, y: 0 }}
          className="relative overflow-hidden rounded-3xl p-5 border border-white/10 dark:bg-gradient-to-br dark:from-[#18122B] dark:to-[#0A0A0C] bg-gradient-to-br from-purple-50 to-white shadow-xl shadow-purple-500/5 transition-all"
        >
          {/* Ambient Corner Glow */}
          <div className="absolute -top-12 -right-12 w-32 h-32 rounded-full bg-[#7D39EB]/30 blur-2xl pointer-events-none" />

          <div className="relative z-10 space-y-3.5">
            <div className="flex items-center justify-between">
              <div className="flex items-center gap-2">
                <span className="relative flex h-2.5 w-2.5">
                  <span className="animate-ping absolute inline-flex h-full w-full rounded-full bg-[#10B981] opacity-75"></span>
                  <span className="relative inline-flex rounded-full h-2.5 w-2.5 bg-[#10B981]"></span>
                </span>
                <span className="text-xs font-bold uppercase tracking-wider text-emerald-500">
                  {upcomingLesson.date_formatted} в {upcomingLesson.start_formatted}
                </span>
              </div>
              <span className="text-[11px] font-mono font-bold px-2 py-0.5 rounded-full dark:bg-white/10 bg-slate-200/80 text-slate-700 dark:text-slate-300">
                {upcomingLesson.subject}
              </span>
            </div>

            <div className="flex items-center gap-3">
              <UserAvatar
                src={upcomingLesson.partner_avatar}
                name={upcomingLesson.partner_name || upcomingLesson.title}
                initials={upcomingLesson.partner_initials}
                size="md"
              />
              <div className="min-w-0">
                <h3 className="text-base font-black tracking-tight dark:text-white text-slate-900 truncate">
                  {upcomingLesson.title}
                </h3>
                <p className="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                  {upcomingLesson.time_span}
                </p>
              </div>
            </div>

            {/* Direct 1-Click Launch Button */}
            <motion.a
              href={upcomingLesson.classroom_url}
              whileTap={{ scale: 0.97 }}
              className="w-full min-h-[48px] rounded-2xl bg-[#C6FF33] hover:bg-[#b5f21f] text-black font-extrabold text-sm flex items-center justify-center gap-2 shadow-lg shadow-[#C6FF33]/25 transition"
            >
              <Video className="w-4 h-4 fill-current" />
              <span>Войти в виртуальный класс (1 клик)</span>
            </motion.a>
          </div>
        </motion.div>
      ) : (
        <div className="p-4 rounded-2xl border border-dashed border-slate-200 dark:border-white/10 flex items-center justify-between">
          <div className="flex items-center gap-3">
            <div className="w-9 h-9 rounded-xl bg-purple-500/10 flex items-center justify-center text-[#7D39EB]">
              <Sparkles className="w-4 h-4" />
            </div>
            <div>
              <h4 className="text-xs font-bold dark:text-white text-slate-800">Нет уроков прямо сейчас</h4>
              <p className="text-[11px] text-slate-400">График актуален</p>
            </div>
          </div>
          <button
            type="button"
            onClick={onOpenQuickCreate}
            className="text-xs font-bold text-[#7D39EB] dark:text-[#C6FF33] hover:underline"
          >
            + Запланировать
          </button>
        </div>
      )}

      {/* ─── 1.1 BOOKING LINK FOR TUTORS ─── */}
      {bookingUrl && (
        <div className="p-3.5 sm:p-4 rounded-2xl border border-purple-500/20 dark:bg-purple-950/20 bg-purple-50/60 backdrop-blur-md flex items-center justify-between gap-3 shadow-sm">
          <div className="min-w-0 flex-1 space-y-0.5">
            <div className="flex items-center gap-1.5">
              <LinkIcon className="w-3.5 h-3.5 text-[#7D39EB] dark:text-[#C6FF33]" />
              <span className="text-[10px] font-extrabold uppercase tracking-wider text-[#7D39EB] dark:text-[#C6FF33]">
                Ссылка для записи учеников
              </span>
            </div>
            <p className="text-xs font-mono font-bold dark:text-white text-slate-800 truncate">
              {bookingShortUrl || bookingUrl}
            </p>
          </div>

          <div className="flex items-center gap-1.5 shrink-0">
            <button
              type="button"
              onClick={handleCopyBookingUrl}
              className={`px-3 py-1.5 rounded-xl text-xs font-bold flex items-center gap-1 shadow-sm transition active:scale-95 ${
                copiedBooking 
                  ? 'bg-emerald-500 text-white' 
                  : 'bg-[#7D39EB] text-white hover:bg-purple-700'
              }`}
            >
              {copiedBooking ? (
                <>
                  <Check className="w-3.5 h-3.5" />
                  <span>Готово!</span>
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
              onClick={handleShareBookingUrl}
              className="p-1.5 rounded-xl bg-slate-200/80 dark:bg-white/10 text-slate-700 dark:text-white hover:bg-slate-300 dark:hover:bg-white/20 active:scale-95 transition"
              title="Поделиться"
            >
              <Share2 className="w-3.5 h-3.5" />
            </button>
          </div>
        </div>
      )}

      {/* ─── 2. WEEK CONTROLS BAR (Prev / Today / Next + Month & ICS) ─── */}
      <div className="p-3.5 rounded-2xl border border-slate-200/80 dark:border-white/10 dark:bg-white/[0.04] bg-white shadow-sm space-y-2.5">
        <div className="flex items-center justify-between">
          {/* Week Pager */}
          <div className="flex items-center gap-1.5">
            <button
              type="button"
              onClick={handleTodayClick}
              className="px-2.5 py-1.5 rounded-xl border border-slate-200 dark:border-white/10 text-xs font-bold dark:text-white text-slate-800 hover:bg-slate-100 dark:hover:bg-white/5 active:scale-95 transition"
            >
              Сегодня
            </button>

            <div className="flex items-center rounded-xl border border-slate-200 dark:border-white/10 overflow-hidden">
              <button
                type="button"
                onClick={handlePrevWeek}
                title="Предыдущая неделя"
                className="p-1.5 text-slate-500 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-white/10 transition"
              >
                <ChevronLeft className="w-4 h-4" />
              </button>
              <div className="w-px h-4 bg-slate-200 dark:bg-white/10" />
              <button
                type="button"
                onClick={handleNextWeek}
                title="Следующая неделя"
                className="p-1.5 text-slate-500 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-white/10 transition"
              >
                <ChevronRight className="w-4 h-4" />
              </button>
            </div>

            <span className="text-xs font-black tracking-tight dark:text-white text-slate-900 ml-1">
              {computedWeekLabel}
            </span>
          </div>

          {/* Quick sync, print, view switcher & availability links */}
          <div className="flex items-center gap-1.5 flex-wrap">
            {/* View Mode Toggle: List vs Google Grid */}
            <div className="flex items-center p-0.5 rounded-xl border border-slate-200 dark:border-white/10 bg-slate-100/80 dark:bg-white/5">
              <button
                type="button"
                onClick={() => handleToggleCalendarView('list')}
                title="Список занятий"
                className={`px-2.5 py-1 rounded-lg text-xs font-bold flex items-center gap-1.5 transition ${
                  calendarView === 'list'
                    ? 'bg-white dark:bg-white/20 text-slate-900 dark:text-white shadow-xs'
                    : 'text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'
                }`}
              >
                <List className="w-3.5 h-3.5" />
                <span className="hidden sm:inline">Список</span>
              </button>
              <button
                type="button"
                onClick={() => handleToggleCalendarView('grid')}
                title="Сетка времени (Google Календарь)"
                className={`px-2.5 py-1 rounded-lg text-xs font-bold flex items-center gap-1.5 transition ${
                  calendarView === 'grid'
                    ? 'bg-white dark:bg-white/20 text-slate-900 dark:text-white shadow-xs'
                    : 'text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'
                }`}
              >
                <LayoutGrid className="w-3.5 h-3.5" />
                <span className="hidden sm:inline">Сетка (Google)</span>
              </button>
            </div>

            {isTutor && (
              <button
                type="button"
                onClick={() => setIsAvailabilityOpen(true)}
                title="Настроить свободные часы для записи"
                className="px-2.5 py-1.5 rounded-xl border border-purple-500/20 bg-purple-500/10 text-xs font-bold text-[#7D39EB] dark:text-[#C6FF33] flex items-center gap-1 hover:bg-purple-500/20 active:scale-95 transition"
              >
                <Clock className="w-3.5 h-3.5" />
                <span>Часы</span>
              </button>
            )}

            <a
              href="/api/mobile/schedule/export-ics"
              title="Скачать .ics для календаря"
              className="p-2 rounded-xl text-slate-500 dark:text-slate-400 hover:text-purple-600 dark:hover:text-[#C6FF33] hover:bg-slate-100 dark:hover:bg-white/5 transition"
            >
              <Download className="w-3.5 h-3.5" />
            </a>

            <a
              href={`/admin/schedule/print-weekly?week=${weekStart || todayIso}`}
              target="_blank"
              rel="noopener noreferrer"
              title="Распечатать график (А4)"
              className="p-2 rounded-xl text-slate-500 dark:text-slate-400 hover:text-purple-600 dark:hover:text-[#C6FF33] hover:bg-slate-100 dark:hover:bg-white/5 transition"
            >
              <Printer className="w-3.5 h-3.5" />
            </a>
          </div>
        </div>

        {/* 7-Day Weekday Horizontal Carousel (Only in List Mode) */}
        {calendarView === 'list' && (
          <div className="flex items-center gap-1.5 overflow-x-auto pb-1 scrollbar-none">
            {days.map((day) => {
              const isSelected = selectedDate === day.iso;

              return (
                <button
                  key={day.iso}
                  type="button"
                  onClick={() => setSelectedDate(day.iso)}
                  className={`relative flex-1 min-w-[44px] py-2 rounded-xl flex flex-col items-center justify-center transition-all duration-200 active:scale-95 ${
                    isSelected
                      ? 'dark:bg-white bg-slate-900 text-white dark:text-black shadow-md'
                      : 'dark:bg-white/[0.04] bg-slate-50 dark:text-slate-300 text-slate-600 hover:bg-slate-100 dark:hover:bg-white/10'
                  }`}
                >
                  <span className={`text-[9px] font-bold uppercase tracking-wider mb-0.5 ${
                    isSelected ? 'dark:text-black/70 text-white/70' : 'text-slate-400'
                  }`}>
                    {day.dayName}
                  </span>

                  <span className="text-sm font-extrabold leading-none">
                    {day.dayNumber}
                  </span>

                  {/* Has Lessons Indicator Dot */}
                  {day.hasLessons && (
                    <span className={`w-1.5 h-1.5 rounded-full mt-1 ${
                      isSelected ? 'dark:bg-[#7D39EB] bg-[#C6FF33]' : 'bg-[#10B981]'
                    }`} />
                  )}
                </button>
              );
            })}
          </div>
        )}
      </div>

      {calendarView === 'grid' ? (
        <GoogleCalendarView
          days={days}
          lessons={lessons}
          availability={availability}
          onSelectLesson={onSelectLesson}
          onOpenQuickCreate={(isoDate, timeStr) => {
            if (onOpenQuickCreate) onOpenQuickCreate(isoDate, timeStr);
          }}
          isTutor={isTutor}
        />
      ) : (
        <div className="space-y-3">
        <div className="flex items-center justify-between px-1">
          <h4 className="text-sm font-extrabold tracking-tight dark:text-white text-slate-900">
            Занятия на день
          </h4>
          <span className="text-xs font-mono text-slate-400">
            {currentDayLessons.length} {currentDayLessons.length === 1 ? 'урок' : 'уроков'}
          </span>
        </div>

        <AnimatePresence mode="popLayout">
          {currentDayLessons.length === 0 ? (
            <motion.div
              initial={{ opacity: 0 }}
              animate={{ opacity: 1 }}
              exit={{ opacity: 0 }}
              className="py-10 text-center rounded-3xl border border-dashed border-slate-200 dark:border-white/10 p-6 flex flex-col items-center justify-center gap-2"
            >
              <div className="text-3xl">☕</div>
              <h5 className="text-sm font-bold dark:text-white text-slate-800">
                Занятий не запланировано
              </h5>
              <p className="text-xs text-slate-400 max-w-xs">
                Отдохните или запланируйте индивидуальный урок для ученика в 2 клика.
              </p>
              <button
                type="button"
                onClick={onOpenQuickCreate}
                className="mt-2 px-4 py-2 rounded-xl bg-purple-600 hover:bg-purple-700 text-white text-xs font-bold active:scale-95 transition"
              >
                + Запланировать урок
              </button>
            </motion.div>
          ) : (
            currentDayLessons.map((lesson) => {
              const isPaid = lesson.payment_status === 'paid';
              const isCancelled = lesson.status === 'cancelled';

              return (
                <motion.div
                  key={lesson.id}
                  layout
                  initial={{ opacity: 0, y: 10 }}
                  animate={{ opacity: 1, y: 0 }}
                  exit={{ opacity: 0 }}
                  onClick={() => onSelectLesson(lesson)}
                  className={`cursor-pointer group p-4 rounded-2xl border transition-all active:scale-[0.98] ${
                    isCancelled 
                      ? 'border-rose-500/20 bg-rose-500/5 opacity-70' 
                      : 'border-slate-200/80 dark:border-white/10 dark:bg-white/[0.04] bg-white shadow-sm hover:shadow-md'
                  }`}
                >
                  <div className="flex items-center justify-between">
                    <div className="flex items-center gap-3">
                      <div className="relative">
                        <UserAvatar
                          src={lesson.partner_avatar}
                          name={lesson.partner_name}
                          initials={lesson.partner_initials}
                          size="sm"
                          rounded="rounded-xl"
                          innerRounded="rounded-[10px]"
                        />
                      </div>
                      <div>
                        <div className="flex items-center gap-1.5">
                          <h4 className="text-sm font-bold dark:text-white text-slate-900 group-hover:text-purple-600 dark:group-hover:text-[#C6FF33] transition-colors truncate max-w-[160px]">
                            {lesson.partner_name}
                          </h4>
                          {isCancelled && (
                            <span className="text-[10px] font-bold text-rose-500">(Отменён)</span>
                          )}
                        </div>
                        <span className="text-[11px] text-slate-500 dark:text-slate-400">
                          {lesson.time_formatted.split(' — ')[0]} • {lesson.subject}
                        </span>
                      </div>
                    </div>

                    <div className="flex items-center gap-2">
                      <span className={`text-[10px] font-extrabold px-2 py-0.5 rounded-full ${
                        isCancelled
                          ? 'bg-rose-500/15 text-rose-500'
                          : isPaid 
                            ? 'bg-emerald-500/15 text-emerald-500' 
                            : 'bg-amber-500/15 text-amber-500'
                      }`}>
                        {isCancelled ? 'Отменён' : isPaid ? 'Оплачен' : 'Ожидает'}
                      </span>
                      <ChevronRight className="w-4 h-4 text-slate-400 group-hover:translate-x-0.5 transition-transform" />
                    </div>
                  </div>
                </motion.div>
              );
            })
          )}
        </AnimatePresence>
      </div>
      )}

      {/* ─── 4. FLOATING ACTION BUTTON (FAB) ─── */}
      <motion.button
        type="button"
        onClick={onOpenQuickCreate}
        whileTap={{ scale: 0.9 }}
        className="fixed bottom-24 right-5 z-40 w-13 h-13 rounded-full bg-gradient-to-tr from-[#7D39EB] to-[#9333EA] text-white flex items-center justify-center shadow-xl shadow-purple-600/30 active:scale-95 focus:outline-none"
        title="Запланировать урок"
      >
        <Plus className="w-6 h-6 stroke-[2.5]" />
      </motion.button>

      {/* ─── 5. AVAILABILITY BOTTOM SHEET (Tutor Schedule) ─── */}
      <AvailabilityBottomSheet
        isOpen={isAvailabilityOpen}
        onClose={() => setIsAvailabilityOpen(false)}
        initialAvailability={availability}
        onSaved={onSaveAvailability}
      />
    </div>
  );
}
