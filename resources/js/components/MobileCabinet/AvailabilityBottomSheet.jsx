import React, { useState, useEffect } from 'react';
import { motion, AnimatePresence } from 'motion/react';
import { X, Clock, Check, Loader2, Sparkles } from 'lucide-react';
import axios from 'axios';

const DEFAULT_DAYS = [
  { day_of_week: 1, day_label: 'Понедельник', is_active: true, start_time: '10:00', end_time: '19:00' },
  { day_of_week: 2, day_label: 'Вторник', is_active: true, start_time: '10:00', end_time: '19:00' },
  { day_of_week: 3, day_label: 'Среда', is_active: true, start_time: '10:00', end_time: '19:00' },
  { day_of_week: 4, day_label: 'Четверг', is_active: true, start_time: '10:00', end_time: '19:00' },
  { day_of_week: 5, day_label: 'Пятница', is_active: true, start_time: '10:00', end_time: '18:00' },
  { day_of_week: 6, day_label: 'Суббота', is_active: false, start_time: '10:00', end_time: '16:00' },
  { day_of_week: 0, day_label: 'Воскресенье', is_active: false, start_time: '10:00', end_time: '16:00' },
];

export default function AvailabilityBottomSheet({
  isOpen,
  onClose,
  initialAvailability = [],
  onSaved,
}) {
  const [schedule, setSchedule] = useState(() => {
    if (initialAvailability && initialAvailability.length === 7) {
      return initialAvailability;
    }
    return DEFAULT_DAYS;
  });

  const [isSubmitting, setIsSubmitting] = useState(false);
  const [errorMessage, setErrorMessage] = useState(null);
  const [successMessage, setSuccessMessage] = useState(null);

  useEffect(() => {
    if (isOpen && initialAvailability && initialAvailability.length === 7) {
      setSchedule(initialAvailability);
      setErrorMessage(null);
      setSuccessMessage(null);
    }
  }, [isOpen, initialAvailability]);

  if (!isOpen) return null;

  const handleToggleDay = (index) => {
    setSchedule((prev) => {
      const copy = [...prev];
      copy[index] = { ...copy[index], is_active: !copy[index].is_active };
      return copy;
    });
  };

  const handleTimeChange = (index, field, value) => {
    setSchedule((prev) => {
      const copy = [...prev];
      copy[index] = { ...copy[index], [field]: value };
      return copy;
    });
  };

  const handleApplyWeekdaysPreset = () => {
    setSchedule((prev) =>
      prev.map((day) => ({
        ...day,
        is_active: day.day_of_week >= 1 && day.day_of_week <= 5,
        start_time: '10:00',
        end_time: day.day_of_week === 5 ? '18:00' : '19:00',
      }))
    );
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setErrorMessage(null);
    setSuccessMessage(null);
    setIsSubmitting(true);

    try {
      const res = await axios.post('/api/mobile/availability', {
        availability: schedule,
      });

      if (res.data?.success) {
        setSuccessMessage('График успешно сохранён!');
        if (onSaved) onSaved(schedule);
        setTimeout(() => {
          onClose();
        }, 800);
      }
    } catch (err) {
      const msg = err.response?.data?.error || err.response?.data?.message || 'Не удалось сохранить график';
      setErrorMessage(msg);
    } finally {
      setIsSubmitting(false);
    }
  };

  return (
    <AnimatePresence>
      <div className="fixed inset-0 z-50 flex items-end justify-center bg-black/60 backdrop-blur-sm">
        <motion.div
          initial={{ opacity: 0 }}
          animate={{ opacity: 1 }}
          exit={{ opacity: 0 }}
          onClick={onClose}
          className="absolute inset-0"
        />

        <motion.div
          initial={{ y: '100%' }}
          animate={{ y: 0 }}
          exit={{ y: '100%' }}
          transition={{ type: 'spring', damping: 28, stiffness: 300 }}
          className="relative z-10 w-full max-w-lg rounded-t-3xl border-t border-white/10 dark:bg-[#12111A] bg-white px-3.5 py-4 sm:p-5 shadow-2xl max-h-[90vh] flex flex-col"
        >
          {/* Header */}
          <div className="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-white/10 shrink-0">
            <div className="flex items-center gap-2">
              <div className="w-8 h-8 rounded-xl bg-purple-500/10 flex items-center justify-center text-[#7D39EB] dark:text-[#C6FF33]">
                <Clock className="w-4 h-4" />
              </div>
              <div>
                <h3 className="text-base font-black tracking-tight dark:text-white text-slate-900">
                  Свободные часы
                </h3>
                <p className="text-[11px] text-slate-400">
                  Время, доступное для онлайн-записи
                </p>
              </div>
            </div>

            <button
              type="button"
              onClick={onClose}
              className="p-1.5 rounded-full hover:bg-slate-100 dark:hover:bg-white/10 text-slate-400 transition"
            >
              <X className="w-5 h-5" />
            </button>
          </div>

          {/* Quick preset button */}
          <div className="pt-3 pb-2 shrink-0 flex items-center justify-between">
            <span className="text-[11px] text-slate-400">Быстрый шаблон:</span>
            <button
              type="button"
              onClick={handleApplyWeekdaysPreset}
              className="text-xs font-bold text-[#7D39EB] dark:text-[#C6FF33] flex items-center gap-1 hover:underline active:scale-95 transition"
            >
              <Sparkles className="w-3.5 h-3.5" />
              Пн–Пт с 10:00 до 19:00
            </button>
          </div>

          {/* Form with scrollable days list and fixed sticky submit button */}
          <form onSubmit={handleSubmit} className="flex flex-col flex-1 min-h-0 mt-1">
            <div className="flex-1 overflow-y-auto space-y-2 py-1 pr-0.5 scrollbar-none">
              {schedule.map((day, idx) => (
                <div
                  key={day.day_of_week}
                  className={`px-3 py-2.5 rounded-2xl border transition-all ${
                    day.is_active
                      ? 'border-purple-500/30 dark:bg-purple-950/20 bg-purple-50/50'
                      : 'border-slate-200 dark:border-white/5 opacity-60 bg-slate-50 dark:bg-white/[0.02]'
                  }`}
                >
                  <div className="flex items-center justify-between gap-1.5">
                    <div className="flex items-center gap-2 min-w-0">
                      <button
                        type="button"
                        onClick={() => handleToggleDay(idx)}
                        className={`w-5.5 h-5.5 rounded-lg flex items-center justify-center text-xs font-bold transition active:scale-95 shrink-0 ${
                          day.is_active
                            ? 'bg-[#7D39EB] text-white shadow-sm'
                            : 'border border-slate-300 dark:border-white/20 text-transparent'
                        }`}
                      >
                        <Check className="w-3.5 h-3.5" />
                      </button>
                      <span className="text-xs font-bold dark:text-white text-slate-800 truncate">
                        {day.day_label}
                      </span>
                    </div>

                    {day.is_active ? (
                      <div className="flex items-center gap-1 text-xs shrink-0">
                        <span className="text-[11px] text-slate-400">с</span>
                        <input
                          type="time"
                          value={day.start_time}
                          onChange={(e) => handleTimeChange(idx, 'start_time', e.target.value)}
                          className="w-[66px] sm:w-[74px] px-1 py-1 rounded-lg border border-slate-200 dark:border-white/10 dark:bg-white/10 bg-white font-mono text-[11px] sm:text-xs text-center dark:text-white text-slate-900 focus:outline-none focus:ring-1 focus:ring-purple-500"
                        />
                        <span className="text-[11px] text-slate-400">до</span>
                        <input
                          type="time"
                          value={day.end_time}
                          onChange={(e) => handleTimeChange(idx, 'end_time', e.target.value)}
                          className="w-[66px] sm:w-[74px] px-1 py-1 rounded-lg border border-slate-200 dark:border-white/10 dark:bg-white/10 bg-white font-mono text-[11px] sm:text-xs text-center dark:text-white text-slate-900 focus:outline-none focus:ring-1 focus:ring-purple-500"
                        />
                      </div>
                    ) : (
                      <span className="text-[11px] font-semibold text-slate-400 shrink-0 pr-1">
                        Выходной
                      </span>
                    )}
                  </div>
                </div>
              ))}

              {errorMessage && (
                <div className="p-3 rounded-xl bg-red-500/10 border border-red-500/20 text-red-500 text-xs font-bold">
                  {errorMessage}
                </div>
              )}

              {successMessage && (
                <div className="p-3 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-500 text-xs font-bold flex items-center gap-1.5">
                  <Check className="w-4 h-4" />
                  {successMessage}
                </div>
              )}
            </div>

            {/* Pinned submit button with bottom spacing */}
            <div className="pt-3 pb-2 shrink-0 border-t border-slate-100 dark:border-white/10">
              <button
                type="submit"
                disabled={isSubmitting}
                className="w-full min-h-[48px] rounded-2xl bg-[#7D39EB] hover:bg-purple-700 text-white font-bold text-sm flex items-center justify-center gap-2 shadow-lg shadow-purple-600/20 active:scale-95 transition disabled:opacity-50"
              >
                {isSubmitting ? (
                  <>
                    <Loader2 className="w-4 h-4 animate-spin" />
                    <span>Сохранение...</span>
                  </>
                ) : (
                  <>
                    <Check className="w-4 h-4" />
                    <span>Сохранить график</span>
                  </>
                )}
              </button>
            </div>
          </form>
        </motion.div>
      </div>
    </AnimatePresence>
  );
}
