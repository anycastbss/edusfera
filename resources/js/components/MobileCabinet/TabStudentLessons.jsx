import React, { useState, useMemo } from 'react';
import { motion } from 'motion/react';
import { 
  Calendar, 
  Clock, 
  Video, 
  MessageSquare, 
  ExternalLink, 
  CheckCircle2, 
  Sparkles, 
  User, 
  ChevronRight,
  BookOpen
} from 'lucide-react';

export default function TabStudentLessons({
  user,
  lessons = [],
  myTutors = [],
  upcomingLesson = null,
  selectedDay = 0,
  onSelectDay = () => {},
  onSelectLesson = () => {},
  onOpenChat = () => {},
  onNavigateTab = () => {}
}) {
  const daysOfWeek = useMemo(() => {
    const labels = ['Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб', 'Вс'];
    const now = new Date();
    const currentDay = now.getDay();
    // Monday as 0, Sunday as 6
    const diffToMonday = currentDay === 0 ? -6 : 1 - currentDay;
    const monday = new Date(now);
    monday.setDate(now.getDate() + diffToMonday);

    return labels.map((label, idx) => {
      const d = new Date(monday);
      d.setDate(monday.getDate() + idx);
      const isoDate = `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
      const isToday = d.toDateString() === now.toDateString();
      return {
        label,
        num: String(d.getDate()),
        isoDate,
        isToday,
      };
    });
  }, []);

  const selectedDayDate = daysOfWeek[selectedDay]?.isoDate;
  const filteredLessons = useMemo(() => {
    if (!selectedDayDate || !lessons || lessons.length === 0) return [];
    return lessons.filter((lesson) => {
      const lessonDate = lesson.date || (lesson.start_iso ? lesson.start_iso.slice(0, 10) : null);
      return lessonDate === selectedDayDate;
    });
  }, [lessons, selectedDayDate]);

  const activeLessonList = filteredLessons.length > 0 ? filteredLessons : (lessons.length === 0 ? [] : filteredLessons);

  return (
    <div className="space-y-4 pb-20">
      {/* ═══ 1. UPCOMING LESSON HERO WITH JOIN BUTTON ═══ */}
      {upcomingLesson ? (
        <div className="relative overflow-hidden rounded-3xl p-5 border border-emerald-500/30 dark:bg-emerald-950/20 bg-emerald-50/80 backdrop-blur-xl shadow-lg">
          <div className="flex items-center justify-between gap-3">
            <div className="flex items-center gap-2">
              <span className="relative flex h-2.5 w-2.5">
                <span className="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75" />
                <span className="relative inline-flex rounded-full h-2.5 w-2.5 bg-emerald-500" />
              </span>
              <span className="text-xs font-black uppercase tracking-wider text-emerald-700 dark:text-emerald-400">
                Ближайший урок
              </span>
            </div>
            <span className="text-xs font-bold text-slate-500 dark:text-slate-400">
              {upcomingLesson.time_formatted || upcomingLesson.time}
            </span>
          </div>

          <div className="mt-3 flex items-center justify-between gap-4">
            <div>
              <h3 className="text-lg font-black dark:text-white text-slate-900 font-rimma">
                {upcomingLesson.subject || 'Занятие'}
              </h3>
              <p className="text-xs font-medium text-slate-600 dark:text-slate-300">
                Преподаватель: {upcomingLesson.partner_name || upcomingLesson.title}
              </p>
            </div>

            <a
              href={upcomingLesson.classroom_url || `/classroom/${upcomingLesson.id}`}
              className="inline-flex items-center gap-2 px-4 py-2.5 rounded-2xl bg-gradient-to-tr from-emerald-600 to-teal-500 text-white font-extrabold text-xs shadow-lg shadow-emerald-500/30 hover:scale-105 active:scale-95 transition-all"
            >
              <Video className="w-4 h-4" />
              <span>Войти в класс</span>
            </a>
          </div>
        </div>
      ) : (
        <div className="p-4 rounded-3xl dark:bg-[#121118]/80 bg-white/80 border border-white/10 backdrop-blur-xl flex items-center justify-between">
          <div>
            <h4 className="text-sm font-bold dark:text-white text-slate-900">
              График занятий актуален
            </h4>
            <p className="text-xs text-slate-500 dark:text-slate-400">
              Все уроки отображаются в вашем расписании ниже
            </p>
          </div>
          <button
            type="button"
            onClick={() => onNavigateTab && onNavigateTab('ai_tutor')}
            className="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-violet-500/10 text-violet-700 dark:text-[#C6FF33] font-bold text-xs"
          >
            <Sparkles className="w-3.5 h-3.5" />
            <span>ИИ-Тьютор</span>
          </button>
        </div>
      )}

      {/* ═══ 2. DAY OF WEEK SELECTOR ═══ */}
      <div className="p-2 rounded-2xl dark:bg-[#121118]/80 bg-white/80 border border-white/10 backdrop-blur-xl flex items-center justify-between gap-1 overflow-x-auto scrollbar-none">
        {daysOfWeek.map((day, dIdx) => {
          const isSelected = selectedDay === dIdx;
          return (
            <button
              key={dIdx}
              type="button"
              onClick={() => onSelectDay(dIdx)}
              className={`flex-1 min-w-[42px] py-2 px-1 rounded-xl flex flex-col items-center justify-center transition-all ${
                isSelected
                  ? 'bg-[#7D39EB] text-white shadow-md shadow-violet-500/30 font-black'
                  : 'text-slate-500 hover:bg-white/5 font-semibold'
              }`}
            >
              <span className="text-[10px] uppercase">{day.label}</span>
              <span className="text-xs font-bold">{day.num}</span>
            </button>
          );
        })}
      </div>

      {/* ═══ 3. LESSONS LIST ═══ */}
      <div className="space-y-2.5">
        <div className="flex items-center justify-between px-1">
          <h3 className="text-sm font-extrabold dark:text-white text-slate-900 uppercase tracking-tight">
            Уроки на выбранный день
          </h3>
          <span className="text-xs font-bold text-slate-500 dark:text-slate-400">
            {activeLessonList.length} уроков
          </span>
        </div>

        {activeLessonList.length === 0 ? (
          <div className="p-8 rounded-3xl dark:bg-[#121118]/50 bg-white/50 border border-dashed border-white/10 text-center space-y-2">
            <span className="text-3xl">☕</span>
            <h4 className="text-sm font-bold dark:text-white text-slate-900">
              В этот день уроков нет
            </h4>
            <p className="text-xs text-slate-500 dark:text-slate-400 max-w-xs mx-auto">
              Отличный момент для повторения материала или решения домашнего задания.
            </p>
          </div>
        ) : (
          activeLessonList.map((lesson) => (
            <div
              key={lesson.id}
              onClick={() => onSelectLesson(lesson)}
              className="p-4 rounded-3xl dark:bg-[#121118]/80 bg-white/80 border border-white/10 backdrop-blur-xl shadow-md hover:border-[#7D39EB]/50 transition-all cursor-pointer space-y-3"
            >
              <div className="flex items-center justify-between gap-2">
                <div className="flex items-center gap-3">
                  <div className="w-10 h-10 rounded-2xl bg-violet-500/10 text-violet-600 dark:text-violet-300 font-extrabold flex items-center justify-center text-xs overflow-hidden">
                    {lesson.partner_avatar ? (
                      <img src={lesson.partner_avatar} alt={lesson.partner_name} className="w-full h-full object-cover" />
                    ) : (
                      lesson.partner_initials || 'РП'
                    )}
                  </div>
                  <div>
                    <h4 className="text-sm font-black dark:text-white text-slate-900">
                      {lesson.subject || 'Занятие'}
                    </h4>
                    <p className="text-xs font-medium text-slate-500 dark:text-slate-400">
                      Репетитор: {lesson.partner_name}
                    </p>
                  </div>
                </div>

                <div className="text-right">
                  <span className="text-xs font-black text-[#7D39EB] dark:text-[#C6FF33] block">
                    {lesson.price} BYN
                  </span>
                  <span className={`text-[10px] font-bold uppercase ${
                    lesson.payment_status === 'paid' ? 'text-emerald-500' : 'text-amber-500'
                  }`}>
                    {lesson.payment_status === 'paid' ? '✓ Оплачен' : '⏳ Не оплачен'}
                  </span>
                </div>
              </div>

              <div className="flex items-center justify-between pt-2 border-t border-white/5 text-xs text-slate-500 dark:text-slate-400">
                <div className="flex items-center gap-1.5 font-bold">
                  <Clock className="w-3.5 h-3.5 text-violet-500" />
                  <span>{lesson.time_formatted || lesson.time}</span>
                  <span className="text-[10px] text-slate-400">({lesson.duration_minutes || 60} мин)</span>
                </div>

                <div className="flex items-center gap-2" onClick={(e) => e.stopPropagation()}>
                  {lesson.payment_status !== 'paid' && (
                    <a
                      href={lesson.checkout_url || `/checkout/${lesson.id}`}
                      className="px-2.5 py-1 rounded-xl bg-violet-600 hover:bg-violet-700 text-white font-extrabold text-[11px] shadow-sm transition-all"
                    >
                      Оплатить
                    </a>
                  )}
                  <a
                    href={lesson.classroom_url || `/classroom/${lesson.id}`}
                    className="inline-flex items-center gap-1 font-extrabold text-emerald-600 dark:text-emerald-400 hover:underline"
                  >
                    <Video className="w-3.5 h-3.5" />
                    <span>В класс</span>
                  </a>
                </div>
              </div>
            </div>
          ))
        )}
      </div>

      {/* ═══ 4. MY TUTORS SECTION ═══ */}
      {myTutors.length > 0 && (
        <div className="space-y-2 pt-2">
          <h3 className="text-sm font-extrabold dark:text-white text-slate-900 uppercase tracking-tight px-1">
            Мои преподаватели
          </h3>
          <div className="grid grid-cols-1 gap-2">
            {myTutors.map((tutor) => (
              <div
                key={tutor.id}
                className="p-3.5 rounded-2xl dark:bg-[#121118]/80 bg-white/80 border border-white/10 flex items-center justify-between gap-3"
              >
                <div className="flex items-center gap-3">
                  <div className="w-10 h-10 rounded-xl bg-violet-500/10 text-violet-600 dark:text-violet-300 font-bold flex items-center justify-center text-xs overflow-hidden">
                    {tutor.avatar_url ? (
                      <img src={tutor.avatar_url} alt={tutor.name} className="w-full h-full object-cover" />
                    ) : (
                      'РП'
                    )}
                  </div>
                  <div>
                    <h5 className="text-xs font-black dark:text-white text-slate-900">{tutor.name}</h5>
                    <p className="text-[11px] text-slate-500 dark:text-slate-400">{tutor.subject}</p>
                  </div>
                </div>

                <div className="flex items-center gap-1.5">
                  <button
                    type="button"
                    onClick={() => onOpenChat && onOpenChat(tutor.id)}
                    className="p-2 rounded-xl bg-white/5 hover:bg-white/10 text-slate-600 dark:text-slate-300 transition-colors"
                  >
                    <MessageSquare className="w-4 h-4" />
                  </button>
                  <a
                    href={tutor.booking_url || `/tutors/${tutor.id}`}
                    className="px-3 py-1.5 rounded-xl bg-[#7D39EB] text-white text-[11px] font-extrabold hover:bg-[#6c2ee0] transition-colors"
                  >
                    Записаться
                  </a>
                </div>
              </div>
            ))}
          </div>
        </div>
      )}
    </div>
  );
}
