import React, { useMemo } from 'react';
import { Clock, Video, User, CheckCircle2, AlertCircle } from 'lucide-react';

const HOURS = [8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22];
const HOUR_HEIGHT = 64; // px per hour

export default function GoogleCalendarView({
  days = [],
  lessons = [],
  availability = [],
  onSelectLesson,
  onOpenQuickCreate,
  isTutor = false,
}) {
  const now = new Date();
  const currentHour = now.getHours();
  const currentMinute = now.getMinutes();
  const todayIso = useMemo(() => {
    const y = now.getFullYear();
    const m = String(now.getMonth() + 1).padStart(2, '0');
    const d = String(now.getDate()).padStart(2, '0');
    return `${y}-${m}-${d}`;
  }, []);

  // Map availability by day of week (1 = Monday, 7 = Sunday)
  const availabilityMap = useMemo(() => {
    const map = {};
    if (Array.isArray(availability)) {
      availability.forEach((slot) => {
        const dow = Number(slot.day_of_week);
        if (dow >= 0) map[dow] = slot;
      });
    }
    return map;
  }, [availability]);

  // Group lessons by date (YYYY-MM-DD)
  const lessonsByDay = useMemo(() => {
    const map = {};
    days.forEach(d => { map[d.iso] = []; });

    if (Array.isArray(lessons)) {
      lessons.forEach(lesson => {
        let lessonDate = lesson.date_iso;
        if (!lessonDate && lesson.start_time) {
          lessonDate = lesson.start_time.split('T')[0];
        }
        if (lessonDate && map[lessonDate]) {
          map[lessonDate].push(lesson);
        }
      });
    }
    return map;
  }, [days, lessons]);

  // Helper to parse "HH:MM" into float hours (e.g. "14:30" -> 14.5)
  const parseTimeToHours = (timeStr) => {
    if (!timeStr) return 0;
    const parts = timeStr.split(':');
    const h = parseInt(parts[0], 10) || 0;
    const m = parseInt(parts[1], 10) || 0;
    return h + m / 60;
  };

  const isCurrentTimeVisible = currentHour >= 8 && currentHour <= 22;
  const currentTimeTop = (currentHour - 8 + currentMinute / 60) * HOUR_HEIGHT;

  return (
    <div className="relative rounded-3xl border border-slate-200/80 dark:border-white/10 dark:bg-white/[0.02] bg-white shadow-sm overflow-hidden flex flex-col">
      {/* Calendar Header with 7 Days */}
      <div className="grid grid-cols-[56px_repeat(7,1fr)] border-b border-slate-200/80 dark:border-white/10 dark:bg-white/[0.04] bg-slate-50 sticky top-0 z-20">
        {/* Time label corner */}
        <div className="p-3 text-[10px] font-mono font-bold text-slate-400 flex items-center justify-center border-r border-slate-200/60 dark:border-white/5">
          GMT+3
        </div>

        {/* 7 Column Headers */}
        {days.map((day, idx) => {
          const isToday = day.iso === todayIso;
          return (
            <div
              key={day.iso}
              className={`p-2.5 sm:p-3 text-center border-r border-slate-200/60 dark:border-white/5 last:border-r-0 transition ${
                isToday ? 'dark:bg-[#7D39EB]/10 bg-purple-50/60' : ''
              }`}
            >
              <div className="text-[10px] font-extrabold uppercase tracking-wider text-slate-400">
                {day.dayName}
              </div>
              <div className="mt-0.5 inline-flex items-center justify-center">
                <span className={`w-7 h-7 rounded-full text-xs font-black flex items-center justify-center ${
                  isToday 
                    ? 'bg-[#7D39EB] text-white shadow-md shadow-[#7D39EB]/30' 
                    : 'dark:text-white text-slate-800'
                }`}>
                  {day.dayNumber}
                </span>
              </div>
              {day.hasLessons && (
                <div className="flex justify-center mt-1">
                  <span className="w-1.5 h-1.5 rounded-full bg-[#C6FF33]" />
                </div>
              )}
            </div>
          );
        })}
      </div>

      {/* Calendar Body: Hours Grid & Lesson Blocks */}
      <div className="relative overflow-x-auto overflow-y-auto max-h-[640px] scrollbar-thin">
        <div 
          className="grid grid-cols-[56px_repeat(7,1fr)] relative min-w-[680px]"
          style={{ height: `${HOURS.length * HOUR_HEIGHT}px` }}
        >
          {/* Hour Labels on Left Column */}
          <div className="relative border-r border-slate-200/80 dark:border-white/10 select-none dark:bg-white/[0.01] bg-slate-50/50">
            {HOURS.map((hour) => (
              <div
                key={hour}
                style={{ top: `${(hour - 8) * HOUR_HEIGHT}px`, height: `${HOUR_HEIGHT}px` }}
                className="absolute inset-x-0 border-b border-slate-100 dark:border-white/[0.03] pr-2 text-right text-[10px] font-mono font-bold text-slate-400 flex items-start justify-end pt-1"
              >
                {String(hour).padStart(2, '0')}:00
              </div>
            ))}
          </div>

          {/* 7 Day Vertical Columns */}
          {days.map((day, dayIndex) => {
            const isToday = day.iso === todayIso;
            const dayLessons = lessonsByDay[day.iso] || [];

            // Dow: 1 for Monday .. 7 for Sunday (convert to JS day 0-6 where 0 is Sun, 1 is Mon)
            const jsDay = parseTimeToHours; // placeholder
            const dow = dayIndex === 6 ? 7 : dayIndex + 1;
            const avail = availabilityMap[dow] || availabilityMap[dayIndex === 6 ? 0 : dayIndex + 1];

            let availTop = 0;
            let availHeight = 0;
            if (avail && avail.is_active && avail.start_time && avail.end_time) {
              const startH = Math.max(8, parseTimeToHours(avail.start_time));
              const endH = Math.min(23, parseTimeToHours(avail.end_time));
              if (endH > startH) {
                availTop = (startH - 8) * HOUR_HEIGHT;
                availHeight = (endH - startH) * HOUR_HEIGHT;
              }
            }

            return (
              <div
                key={day.iso}
                className={`relative border-r border-slate-100 dark:border-white/[0.05] last:border-r-0 ${
                  isToday ? 'dark:bg-[#7D39EB]/[0.03] bg-purple-50/30' : ''
                }`}
              >
                {/* Horizontal hour guide lines */}
                {HOURS.map((hour) => (
                  <div
                    key={hour}
                    style={{ top: `${(hour - 8) * HOUR_HEIGHT}px`, height: `${HOUR_HEIGHT}px` }}
                    onClick={() => {
                      if (onOpenQuickCreate) {
                        onOpenQuickCreate(day.iso, `${String(hour).padStart(2, '0')}:00`);
                      }
                    }}
                    className="absolute inset-x-0 border-b border-slate-100 dark:border-white/[0.04] cursor-pointer hover:bg-[#7D39EB]/5 transition-colors"
                  />
                ))}

                {/* Working hours subtle green/purple glow overlay */}
                {availHeight > 0 && (
                  <div
                    style={{ top: `${availTop}px`, height: `${availHeight}px` }}
                    className="absolute inset-x-0.5 rounded-lg border border-dashed border-[#10B981]/20 bg-[#10B981]/[0.03] pointer-events-none"
                  />
                )}

                {/* Lesson blocks */}
                {dayLessons.map((lesson) => {
                  const startHours = parseTimeToHours(lesson.start_formatted || '10:00');
                  const durationMins = lesson.duration_minutes || 60;
                  const top = Math.max(0, (startHours - 8) * HOUR_HEIGHT);
                  const height = Math.max(34, (durationMins / 60) * HOUR_HEIGHT - 4);
                  const isPaid = lesson.payment_status === 'paid';

                  return (
                    <div
                      key={lesson.id}
                      onClick={() => onSelectLesson && onSelectLesson(lesson)}
                      style={{ top: `${top}px`, height: `${height}px` }}
                      className={`absolute inset-x-1 z-10 rounded-xl p-2 cursor-pointer shadow-md transition-all duration-150 hover:scale-[1.02] hover:z-20 border overflow-hidden flex flex-col justify-between ${
                        isPaid
                          ? 'bg-gradient-to-br from-[#7D39EB] to-[#6025C0] border-purple-400/30 text-white shadow-purple-900/30'
                          : 'bg-gradient-to-br from-[#1E1B4B] to-[#110E2E] border-[#7D39EB]/40 text-slate-100 shadow-black/40'
                      }`}
                    >
                      <div className="min-w-0">
                        <div className="flex items-center justify-between gap-1 mb-0.5">
                          <span className="text-[10px] font-mono font-extrabold text-[#C6FF33] truncate">
                            {lesson.time_formatted || `${lesson.start_formatted}`}
                          </span>
                          {isPaid ? (
                            <span className="w-1.5 h-1.5 rounded-full bg-[#C6FF33]" title="Оплачен" />
                          ) : (
                            <span className="text-[9px] font-mono px-1 py-0.2 rounded bg-amber-500/20 text-amber-300">
                              {lesson.price}р
                            </span>
                          )}
                        </div>

                        <h5 className="text-[11px] font-bold tracking-tight truncate leading-tight">
                          {lesson.student_name || lesson.partner_name || lesson.title}
                        </h5>
                        <p className="text-[9px] text-white/70 truncate">
                          {lesson.subject}
                        </p>
                      </div>

                      {height >= 50 && (
                        <div className="flex items-center justify-between pt-1 text-[9px] text-white/60 border-t border-white/10 mt-auto">
                          <span className="font-mono">{durationMins} мин</span>
                          <span className="flex items-center gap-0.5 text-[#C6FF33] font-bold">
                            <Video className="w-2.5 h-2.5" />
                            Класс
                          </span>
                        </div>
                      )}
                    </div>
                  );
                })}

                {/* Current Time Indicator Red/Lime Line */}
                {isToday && isCurrentTimeVisible && (
                  <div
                    style={{ top: `${currentTimeTop}px` }}
                    className="absolute inset-x-0 z-20 pointer-events-none flex items-center"
                  >
                    <div className="w-2.5 h-2.5 rounded-full bg-[#EF4444] shadow-sm shadow-red-500 -ml-1.5" />
                    <div className="flex-1 h-0.5 bg-[#EF4444] shadow-sm shadow-red-500/50" />
                  </div>
                )}
              </div>
            );
          })}
        </div>
      </div>

      {/* Footer Info / Legend */}
      <div className="p-3 bg-white/[0.02] border-t border-slate-200/80 dark:border-white/10 flex flex-wrap items-center justify-between gap-2 text-[11px] text-slate-400 shrink-0">
        <div className="flex items-center gap-3">
          <div className="flex items-center gap-1.5">
            <span className="w-2.5 h-2.5 rounded-md bg-[#7D39EB]" />
            <span>Урок</span>
          </div>
          <div className="flex items-center gap-1.5">
            <span className="w-2.5 h-2.5 rounded-md border border-dashed border-[#10B981] bg-[#10B981]/20" />
            <span>Свободные часы для записи</span>
          </div>
          <div className="flex items-center gap-1.5">
            <span className="w-2 h-2 rounded-full bg-[#EF4444]" />
            <span>Текущее время</span>
          </div>
        </div>
        <span className="text-[10px] text-slate-500 font-mono">
          Кликните на свободный слот для быстрой записи
        </span>
      </div>
    </div>
  );
}
