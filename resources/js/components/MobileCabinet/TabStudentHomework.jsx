import React, { useState } from 'react';
import axios from 'axios';
import { 
  BookOpen, 
  CheckCircle2, 
  Clock, 
  AlertCircle, 
  Sparkles, 
  User, 
  Check, 
  ChevronRight,
  Filter
} from 'lucide-react';

export default function TabStudentHomework({
  initialHomework = [],
  onAskAi = null
}) {
  const [homeworkList, setHomeworkList] = useState(initialHomework);
  const [filter, setFilter] = useState('assigned'); // 'all' | 'assigned' | 'completed'
  const [completingId, setCompletingId] = useState(null);
  const [successToast, setSuccessToast] = useState(null);

  const filteredList = homeworkList.filter((item) => {
    if (filter === 'assigned') return item.status !== 'completed';
    if (filter === 'completed') return item.status === 'completed';
    return true;
  });

  const pendingCount = homeworkList.filter((i) => i.status !== 'completed').length;
  const completedCount = homeworkList.filter((i) => i.status === 'completed').length;

  const handleCompleteHomework = async (id) => {
    setCompletingId(id);
    try {
      const response = await axios.post(`/api/mobile/homework/${id}/complete`);
      if (response.data?.success) {
        setHomeworkList((prev) =>
          prev.map((item) =>
            item.id === id ? { ...item, status: 'completed', completed_at: 'Только что' } : item
          )
        );
        setSuccessToast('Задание отмечено выполненным!');
        setTimeout(() => setSuccessToast(null), 3000);
      }
    } catch (err) {
      console.error(err);
      alert('Ошибка при обновлении статуса');
    } finally {
      setCompletingId(null);
    }
  };

  return (
    <div className="space-y-4 pb-20">
      {/* ═══ 1. HEADER HERO ═══ */}
      <div className="relative overflow-hidden rounded-3xl p-5 border border-white/10 dark:bg-[#121118]/80 bg-white/80 backdrop-blur-xl shadow-lg">
        <div className="flex items-center justify-between gap-3">
          <div>
            <h2 className="text-lg font-black dark:text-white text-slate-900 font-rimma uppercase tracking-tight">
              Домашние задания
            </h2>
            <p className="text-xs text-slate-500 dark:text-slate-400">
              Задания от ваших преподавателей и контроль сроков
            </p>
          </div>

          <div className="flex items-center gap-2">
            <span className="px-3 py-1 rounded-full text-xs font-black bg-[#7D39EB] text-white shadow-md shadow-violet-500/20">
              {pendingCount} к сдаче
            </span>
          </div>
        </div>

        {/* Filter pills */}
        <div className="mt-4 pt-3 border-t border-white/10 flex items-center gap-2">
          <button
            type="button"
            onClick={() => setFilter('assigned')}
            className={`px-3 py-1.5 rounded-xl text-xs font-bold transition-all ${
              filter === 'assigned'
                ? 'bg-[#7D39EB] text-white shadow-sm'
                : 'dark:bg-white/5 bg-slate-100 text-slate-600 dark:text-slate-300'
            }`}
          >
            К выполнению ({pendingCount})
          </button>
          <button
            type="button"
            onClick={() => setFilter('completed')}
            className={`px-3 py-1.5 rounded-xl text-xs font-bold transition-all ${
              filter === 'completed'
                ? 'bg-[#7D39EB] text-white shadow-sm'
                : 'dark:bg-white/5 bg-slate-100 text-slate-600 dark:text-slate-300'
            }`}
          >
            Выполненные ({completedCount})
          </button>
          <button
            type="button"
            onClick={() => setFilter('all')}
            className={`px-3 py-1.5 rounded-xl text-xs font-bold transition-all ${
              filter === 'all'
                ? 'bg-[#7D39EB] text-white shadow-sm'
                : 'dark:bg-white/5 bg-slate-100 text-slate-600 dark:text-slate-300'
            }`}
          >
            Все ({homeworkList.length})
          </button>
        </div>
      </div>

      {/* Success Notification */}
      {successToast && (
        <div className="p-3 rounded-2xl bg-emerald-500/20 border border-emerald-500/40 text-emerald-800 dark:text-emerald-300 text-xs font-bold flex items-center gap-2 shadow-lg">
          <CheckCircle2 className="w-4 h-4 text-emerald-500 shrink-0" />
          <span>{successToast}</span>
        </div>
      )}

      {/* ═══ 2. ASSIGNMENTS LIST ═══ */}
      <div className="space-y-3">
        {filteredList.length === 0 ? (
          <div className="p-8 rounded-3xl dark:bg-[#121118]/50 bg-white/50 border border-dashed border-white/10 text-center space-y-2">
            <BookOpen className="w-8 h-8 mx-auto text-slate-400" />
            <h4 className="text-sm font-bold dark:text-white text-slate-900">
              {filter === 'assigned' ? 'Нет активных домашних заданий' : 'Список пуст'}
            </h4>
            <p className="text-xs text-slate-500 dark:text-slate-400 max-w-xs mx-auto">
              Когда репетитор назначит вам домашку, она мгновенно появится здесь.
            </p>
          </div>
        ) : (
          filteredList.map((hw) => {
            const isCompleted = hw.status === 'completed';

            return (
              <div
                key={hw.id}
                className="p-4 rounded-3xl dark:bg-[#121118]/80 bg-white/80 border border-white/10 backdrop-blur-xl shadow-md space-y-3"
              >
                <div className="flex items-start justify-between gap-3">
                  <div className="space-y-1">
                    <div className="flex items-center gap-2">
                      <span className="text-xs font-extrabold text-[#7D39EB] dark:text-[#C6FF33]">
                        {hw.subject || 'Предмет'}
                      </span>
                      {hw.due_at && (
                        <span className={`inline-flex items-center gap-1 text-[11px] font-bold ${
                          hw.is_overdue ? 'text-rose-500' : 'text-slate-400'
                        }`}>
                          <Clock className="w-3 h-3" />
                          <span>до {hw.due_at}</span>
                        </span>
                      )}
                    </div>
                    <h3 className="text-sm font-black dark:text-white text-slate-900">
                      {hw.title}
                    </h3>
                  </div>

                  <span
                    className={`px-2.5 py-1 rounded-full text-[10px] font-extrabold uppercase shrink-0 ${
                      isCompleted
                        ? 'bg-emerald-500/15 text-emerald-600 dark:text-emerald-400'
                        : 'bg-amber-500/15 text-amber-700 dark:text-amber-400'
                    }`}
                  >
                    {isCompleted ? 'Сдано' : 'К сдаче'}
                  </span>
                </div>

                {hw.instructions && (
                  <p className="text-xs text-slate-600 dark:text-slate-300 leading-relaxed bg-white/5 p-3 rounded-2xl whitespace-pre-wrap">
                    {hw.instructions}
                  </p>
                )}

                <div className="flex items-center justify-between pt-2 border-t border-white/5 text-xs">
                  <div className="flex items-center gap-2">
                    <div className="w-6 h-6 rounded-full bg-violet-500/10 text-violet-600 text-[10px] font-bold flex items-center justify-center overflow-hidden">
                      {hw.tutor_avatar ? (
                        <img src={hw.tutor_avatar} alt={hw.tutor_name} className="w-full h-full object-cover" />
                      ) : (
                        'РП'
                      )}
                    </div>
                    <span className="text-slate-500 dark:text-slate-400 font-medium text-[11px]">
                      {hw.tutor_name}
                    </span>
                  </div>

                  <div className="flex items-center gap-2">
                    <button
                      type="button"
                      onClick={() => onAskAi && onAskAi(`Помоги разобраться с домашним заданием: «${hw.title}». Инструкция: ${hw.instructions || 'Нет дополнительных данных'}`)}
                      className="inline-flex items-center gap-1 px-2.5 py-1 rounded-xl bg-violet-500/10 hover:bg-violet-500/20 text-[#7D39EB] dark:text-[#C6FF33] font-extrabold text-[11px] transition-colors"
                    >
                      <Sparkles className="w-3 h-3" />
                      <span>ИИ-помощь</span>
                    </button>

                    {!isCompleted && (
                      <button
                        type="button"
                        disabled={completingId === hw.id}
                        onClick={() => handleCompleteHomework(hw.id)}
                        className="inline-flex items-center gap-1 px-3 py-1 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-extrabold text-[11px] transition-colors"
                      >
                        <Check className="w-3 h-3" />
                        <span>Выполнено</span>
                      </button>
                    )}
                  </div>
                </div>
              </div>
            );
          })
        )}
      </div>
    </div>
  );
}
