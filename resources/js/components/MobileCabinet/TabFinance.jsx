import React, { useState, useRef } from 'react';
import { motion, AnimatePresence } from 'motion/react';
import { 
  Wallet, ArrowUpRight, ShieldCheck, Sparkles, FileText, 
  ChevronRight, X, Check, Loader2, CreditCard, TrendingUp, 
  BookOpen, Clock, Users, Award, AlertCircle 
} from 'lucide-react';
import axios from 'axios';
import SubscriptionPlansModal from './SubscriptionPlansModal';

export default function TabFinance({ tutorProfile, subscription, balance: initialBalance, stats: initialStats, npd: initialNpd }) {
  const [balance, setBalance] = useState(() => initialBalance || { available: 0, pending: 0, currency: 'BYN' });
  const [stats, setStats] = useState(() => initialStats || {
    completed_lessons: 0,
    completed_hours: 0,
    this_month_lessons: 0,
    this_month_earnings: 0,
    last_month_earnings: 0,
    all_time_earnings: 0,
    active_students: 0,
    average_rate: tutorProfile?.hourly_rate || 35.0,
  });

  const [npd, setNpd] = useState(() => initialNpd || {
    month_gross: 0,
    year_gross: 0,
    tax_due_month: 0,
    tax_saved: 0,
    deduction_remaining: 2000,
    deduction_used: 0,
    deduction_total: 2000,
    apply_first_time_deduction: true,
    payment_deadline: '22 ноября 2026',
    notification_deadline: '10 ноября 2026',
    receipts_issued_count: 0,
    receipts_pending_count: 0,
    lessons: [],
  });
  const [isGeneratingNpd, setIsGeneratingNpd] = useState(false);
  const [npdMessage, setNpdMessage] = useState(null);



  // In-App Modals
  const [isHistoryOpen, setIsHistoryOpen] = useState(false);
  const [transactions, setTransactions] = useState([]);
  const [isLoadingTransactions, setIsLoadingTransactions] = useState(false);
  const [isNpdModalOpen, setIsNpdModalOpen] = useState(false);
  const [isPlanModalOpen, setIsPlanModalOpen] = useState(false);

  // Promo Code State
  const [promoCodeInput, setPromoCodeInput] = useState('');
  const [isApplyingPromo, setIsApplyingPromo] = useState(false);
  const isApplyingPromoRef = useRef(false);
  const [promoMessage, setPromoMessage] = useState(null);
  const [promoError, setPromoError] = useState(null);
  const [subData, setSubData] = useState(() => subscription);

  const handleApplyPromoCode = async (e) => {
    if (e) e.preventDefault();
    if (isApplyingPromoRef.current) return;
    const code = promoCodeInput.trim().toUpperCase();
    if (!code) return;

    isApplyingPromoRef.current = true;
    setIsApplyingPromo(true);
    setPromoError(null);
    setPromoMessage(null);
    try {
      const res = await axios.post('/api/mobile/promocode/apply', { code });
      if (res.data?.success) {
        setPromoMessage(res.data.message || '🎉 Промокод успешно применён!');
        if (res.data.is_activated) {
          setSubData((prev) => ({
            ...prev,
            plan_name: res.data.plan_name || 'Частная практика',
            is_active: true,
            status: 'active',
            days_left: 30,
            period_end: 'Активна',
          }));
        }
        setPromoCodeInput('');
      } else {
        setPromoError(res.data?.error || 'Недействительный промокод');
      }
    } catch (err) {
      const errorMsg = err.response?.data?.error || err.response?.data?.message;
      if (err.response?.data?.is_activated || (errorMsg && (errorMsg.includes('уже использовали') || errorMsg.includes('уже активирован')))) {
        setPromoMessage('🎉 Тариф успешно активирован!');
        setSubData((prev) => ({
          ...prev,
          is_active: true,
          status: 'active',
        }));
        setPromoCodeInput('');
      } else {
        setPromoError(errorMsg || 'Недействительный промокод');
      }
    } finally {
      isApplyingPromoRef.current = false;
      setIsApplyingPromo(false);
    }
  };

  const handleToggleNpdDeduction = async () => {
    try {
      const res = await axios.post('/api/mobile/npd/toggle-deduction');
      if (res.data?.npd) {
        setNpd(res.data.npd);
        setNpdMessage(res.data.message);
        setTimeout(() => setNpdMessage(null), 3000);
      }
    } catch (e) {
      console.error(e);
    }
  };

  const handleGenerateOneClickReceipts = async () => {
    setIsGeneratingNpd(true);
    try {
      const res = await axios.post('/api/mobile/npd/generate-receipts');
      if (res.data?.npd) {
        setNpd(res.data.npd);
        setNpdMessage(res.data.message);
        setTimeout(() => setNpdMessage(null), 3500);
      }
    } catch (e) {
      console.error(e);
      alert('Ошибка при генерации чеков');
    } finally {
      setIsGeneratingNpd(false);
    }
  };

  const handleUpdateReceiptStatus = async (lessonId, action) => {
    try {
      const res = await axios.post(`/api/mobile/npd/receipts/${lessonId}`, { action });
      if (res.data?.npd) {
        setNpd(res.data.npd);
      }
    } catch (e) {
      console.error(e);
    }
  };

  const handleOpenHistory = async () => {
    setIsHistoryOpen(true);
    setIsLoadingTransactions(true);
    try {
      const res = await axios.get('/api/mobile/transactions');
      if (res.data?.transactions) {
        setTransactions(res.data.transactions);
      }
    } catch (e) {
      console.error(e);
    } finally {
      setIsLoadingTransactions(false);
    }
  };

  const aiUsed = subData?.ai_used ?? 0;
  const aiLimit = subData?.ai_limit ?? 10;
  const responsesUsed = subData?.responses_used ?? 0;
  const responsesLimit = subData?.responses_limit ?? 20;
  const planName = subData?.plan_name || (subData?.plan?.toUpperCase() || 'PRO');
  const periodEnd = subData?.period_end || 'Активна';
  const daysLeft = subData?.days_left ?? 0;

  const hourlyRate = Number(tutorProfile?.hourly_rate || stats?.average_rate || 35.0);
  const thisMonthLessons = Number(stats?.this_month_lessons || 0);
  const calculatedMonthIncome = Number(stats?.this_month_earnings || 0) > 0
    ? Number(stats?.this_month_earnings).toFixed(2)
    : Number(thisMonthLessons * hourlyRate).toFixed(2);
  const monthEarnings = calculatedMonthIncome;
  const lastMonthEarnings = Number(stats?.last_month_earnings || 0).toFixed(2);
  const allTimeAmount = Number(stats?.all_time_earnings || 0).toFixed(2);
  const completedLessons = Number(stats?.completed_lessons || 0);
  const completedHours = Number(stats?.completed_hours || 0);

  return (
    <div className="space-y-5 pb-32">
      {/* Header */}
      <div className="px-1">
        <h3 className="text-lg font-black tracking-tight dark:text-white text-slate-900">
          Финансы и Подписка
        </h3>
        <p className="text-xs text-slate-500 dark:text-slate-400">
          Баланс, статистика доходов, уроков и тарифный план
        </p>
      </div>

      {/* ─── 1. REVENUE ANALYTICS CARD (Direct payment model) ─── */}
      <motion.div
        initial={{ opacity: 0, y: 15 }}
        animate={{ opacity: 1, y: 0 }}
        className="relative overflow-hidden rounded-3xl p-6 border border-white/10 dark:bg-gradient-to-br dark:from-[#18122B] dark:to-[#0A0A0C] bg-gradient-to-br from-slate-900 to-slate-800 text-white shadow-2xl shadow-purple-500/10"
      >
        <div className="absolute top-0 right-0 w-36 h-36 bg-[#7D39EB]/30 rounded-full blur-2xl pointer-events-none" />

        <div className="relative z-10 space-y-4">
          <div className="flex items-center justify-between text-xs text-white/60">
            <span className="flex items-center gap-1.5 font-medium">
              <TrendingUp className="w-4 h-4 text-[#C6FF33]" />
              <span>Примерный доход за этот месяц</span>
            </span>
            <span className="font-mono bg-white/10 px-2.5 py-0.5 rounded-full text-white/80 text-[10px] font-bold">
              Прямой расчёт с учениками
            </span>
          </div>

          <div className="flex items-baseline gap-2">
            <span className="text-3xl sm:text-4xl font-black tracking-tight font-mono text-white">
              ~{calculatedMonthIncome}
            </span>
            <span className="text-lg font-bold text-[#C6FF33]">BYN</span>
          </div>

          <div className="text-xs text-white/80 flex items-center gap-2 bg-white/[0.06] p-2.5 rounded-2xl border border-white/10">
            <Clock className="w-4 h-4 text-[#C6FF33] shrink-0" />
            <span>
              На основе <strong>{thisMonthLessons}</strong> уроков в этом месяце × ставка <strong>{hourlyRate} BYN/час</strong>
            </span>
          </div>

          <div className="grid grid-cols-2 gap-2 pt-1 text-[11px]">
            <div className="p-2.5 rounded-xl bg-white/5 border border-white/5">
              <span className="text-white/50 block text-[10px]">Прошлый месяц</span>
              <strong className="text-white font-mono text-xs">{lastMonthEarnings} BYN</strong>
            </div>
            <div className="p-2.5 rounded-xl bg-white/5 border border-white/5">
              <span className="text-white/50 block text-[10px]">Всего часов проведено</span>
              <strong className="text-white font-mono text-xs">{completedHours} ч</strong>
            </div>
          </div>

          <div className="pt-2 flex items-center gap-3">
            <button
              type="button"
              onClick={() => setIsNpdModalOpen(true)}
              className="flex-1 min-h-[44px] rounded-xl bg-gradient-to-r from-[#7D39EB] to-[#9F5BF5] hover:brightness-110 text-white font-extrabold text-xs flex items-center justify-center gap-1.5 shadow-lg shadow-purple-500/25 active:scale-95 transition"
            >
              <FileText className="w-4 h-4" />
              <span>Сформировать чек НПД</span>
            </button>
            <button
              type="button"
              onClick={handleOpenHistory}
              className="px-4 min-h-[44px] rounded-xl bg-white/10 hover:bg-white/15 text-white font-bold text-xs flex items-center justify-center active:scale-95 transition"
            >
              История уроков
            </button>
          </div>

          <p className="text-[10px] text-white/40 leading-snug pt-1">
            * Ученики оплачивают занятия напрямую вам (наличными, картой или ЕРИП). Edusfera не удерживает комиссий за уроки.
          </p>
        </div>
      </motion.div>

      {/* ─── 2. SUBSCRIPTION CARD (Directly in Finance) ─── */}
      <div className="p-5 rounded-3xl border border-slate-200/80 dark:border-white/10 dark:bg-white/[0.04] bg-white shadow-sm space-y-4">
        <div className="flex items-center justify-between">
          <div className="flex items-center gap-2.5">
            <div className="w-9 h-9 rounded-2xl bg-purple-500/10 text-[#7D39EB] dark:text-[#C6FF33] flex items-center justify-center">
              <Sparkles className="w-5 h-5" />
            </div>
            <div>
              <div className="flex items-center gap-2">
                <h4 className="text-sm font-extrabold dark:text-white text-slate-900">
                  Тариф {planName}
                </h4>
                <span className="text-[10px] font-mono px-2 py-0.5 rounded-full font-bold bg-[#C6FF33]/15 text-[#88ba04] dark:text-[#C6FF33] border border-[#C6FF33]/20">
                  {subData?.is_active ? 'Активен' : 'Базовый'}
                </span>
              </div>
              <p className="text-[11px] text-slate-400">
                {subData?.is_active ? `Оплачен до ${periodEnd} (осталось ${daysLeft} дн.)` : 'Доступны базовые возможности'}
              </p>
            </div>
          </div>

          <button
            type="button"
            onClick={() => setIsPlanModalOpen(true)}
            className="px-3 py-1.5 rounded-xl bg-[#7D39EB]/10 hover:bg-[#7D39EB]/20 text-[#7D39EB] dark:text-[#C6FF33] font-bold text-xs active:scale-95 transition"
          >
            Тарифы
          </button>
        </div>

        {/* Meters Grid: AI + Responses */}
        <div className="grid grid-cols-2 gap-3 pt-1">
          <div className="p-3 rounded-2xl bg-slate-50 dark:bg-white/[0.02] border border-slate-100 dark:border-white/5 space-y-1.5">
            <div className="flex items-center justify-between text-[11px]">
              <span className="text-slate-400 font-medium">ИИ-генерации:</span>
              <span className="font-mono font-bold dark:text-white text-slate-800">
                {aiUsed}/{aiLimit}
              </span>
            </div>
            <div className="w-full h-1.5 rounded-full bg-slate-200 dark:bg-white/10 overflow-hidden">
              <div
                className="h-full bg-gradient-to-r from-[#7D39EB] to-[#C6FF33] rounded-full transition-all duration-500"
                style={{ width: `${Math.min(100, (aiUsed / Math.max(1, aiLimit)) * 100)}%` }}
              />
            </div>
          </div>

          <div className="p-3 rounded-2xl bg-slate-50 dark:bg-white/[0.02] border border-slate-100 dark:border-white/5 space-y-1.5">
            <div className="flex items-center justify-between text-[11px]">
              <span className="text-slate-400 font-medium">Отклики на заявки:</span>
              <span className="font-mono font-bold dark:text-white text-slate-800">
                {responsesUsed}/{responsesLimit}
              </span>
            </div>
            <div className="w-full h-1.5 rounded-full bg-slate-200 dark:bg-white/10 overflow-hidden">
              <div
                className="h-full bg-gradient-to-r from-[#10B981] to-[#38BDF8] rounded-full transition-all duration-500"
                style={{ width: `${Math.min(100, (responsesUsed / Math.max(1, responsesLimit)) * 100)}%` }}
              />
            </div>
          </div>
        </div>

        {/* Promo Code Form for Instant PRO or Discount Activation */}
        <form onSubmit={handleApplyPromoCode} className="pt-3 border-t border-slate-100 dark:border-white/5 space-y-1.5">
          <div className="flex items-center gap-1.5">
            <input
              type="text"
              value={promoCodeInput}
              onChange={(e) => setPromoCodeInput(e.target.value.toUpperCase())}
              placeholder="Промокод (PRO или скидка)"
              className="flex-1 px-3 py-2 rounded-xl border border-slate-200 dark:border-white/10 dark:bg-white/5 bg-slate-50 text-xs font-mono font-bold dark:text-white text-slate-900 placeholder:font-sans placeholder:font-normal focus:outline-none focus:ring-1 focus:ring-purple-500 uppercase"
            />
            <button
              type="submit"
              disabled={isApplyingPromo || !promoCodeInput.trim()}
              className="px-3.5 py-2 rounded-xl bg-[#7D39EB] hover:bg-purple-700 text-white font-bold text-xs flex items-center gap-1 shadow-sm active:scale-95 transition disabled:opacity-50 shrink-0"
            >
              {isApplyingPromo ? (
                <Loader2 className="w-3.5 h-3.5 animate-spin" />
              ) : (
                <Check className="w-3.5 h-3.5" />
              )}
              <span>Применить</span>
            </button>
          </div>

          {promoError && (
            <p className="text-[11px] text-red-500 font-bold px-1">
              {promoError}
            </p>
          )}

          {promoMessage && (
            <p className="text-[11px] text-emerald-500 font-bold px-1 flex items-center gap-1">
              <Check className="w-3.5 h-3.5" />
              <span>{promoMessage}</span>
            </p>
          )}
        </form>
      </div>

      {/* ─── 3. LESSONS & MONEY STATISTICS (2x2 Grid) ─── */}
      <div className="space-y-3">
        <div className="px-1 flex items-center justify-between">
          <h4 className="text-xs font-extrabold uppercase tracking-wider text-slate-400">
            Статистика об уроках и деньгах
          </h4>
          <span className="text-[11px] text-slate-400">
            Всего оборот: <strong className="font-mono dark:text-white text-slate-800">{allTimeAmount} BYN</strong>
          </span>
        </div>

        <div className="grid grid-cols-2 gap-3">
          {/* 1. Lessons completed */}
          <div className="p-4 rounded-2xl border border-slate-200/80 dark:border-white/10 dark:bg-white/[0.04] bg-white shadow-sm space-y-1">
            <div className="flex items-center justify-between text-xs text-slate-400">
              <span className="flex items-center gap-1.5">
                <BookOpen className="w-3.5 h-3.5 text-purple-400" />
                <span>Уроки</span>
              </span>
              <span className="text-[10px] font-mono font-bold text-emerald-500">
                +{stats?.this_month_lessons || 0} за мес.
              </span>
            </div>
            <div className="text-2xl font-black font-mono dark:text-white text-slate-900">
              {stats?.completed_lessons || 0}
            </div>
            <p className="text-[10px] text-slate-400">проведено занятий</p>
          </div>

          {/* 2. Teaching Hours */}
          <div className="p-4 rounded-2xl border border-slate-200/80 dark:border-white/10 dark:bg-white/[0.04] bg-white shadow-sm space-y-1">
            <div className="flex items-center justify-between text-xs text-slate-400">
              <span className="flex items-center gap-1.5">
                <Clock className="w-3.5 h-3.5 text-sky-400" />
                <span>Часы</span>
              </span>
              <span className="text-[10px] font-mono text-slate-400">
                активных
              </span>
            </div>
            <div className="text-2xl font-black font-mono dark:text-white text-slate-900">
              {stats?.completed_hours || 0}
            </div>
            <p className="text-[10px] text-slate-400">часов практики</p>
          </div>

          {/* 3. This Month Income */}
          <div className="p-4 rounded-2xl border border-slate-200/80 dark:border-white/10 dark:bg-white/[0.04] bg-white shadow-sm space-y-1">
            <div className="flex items-center justify-between text-xs text-slate-400">
              <span className="flex items-center gap-1.5">
                <TrendingUp className="w-3.5 h-3.5 text-emerald-400" />
                <span>Доход мес.</span>
              </span>
              <span className="text-[10px] font-mono text-slate-400">
                {lastMonthEarnings > 0 ? `пред.: ${lastMonthEarnings}` : 'BYN'}
              </span>
            </div>
            <div className="text-2xl font-black font-mono text-emerald-500">
              {monthEarnings} <span className="text-xs font-bold text-slate-400">BYN</span>
            </div>
            <p className="text-[10px] text-slate-400">расчёт за этот месяц</p>
          </div>

          {/* 4. Hourly Rate */}
          <div className="p-4 rounded-2xl border border-slate-200/80 dark:border-white/10 dark:bg-white/[0.04] bg-white shadow-sm space-y-1">
            <div className="flex items-center justify-between text-xs text-slate-400">
              <span className="flex items-center gap-1.5">
                <Award className="w-3.5 h-3.5 text-amber-400" />
                <span>Ставка</span>
              </span>
              <span className="text-[10px] font-mono text-slate-400">
                за 60 мин
              </span>
            </div>
            <div className="text-2xl font-black font-mono dark:text-white text-slate-900">
              {hourlyRate} <span className="text-xs font-bold text-slate-400">BYN</span>
            </div>
            <p className="text-[10px] text-slate-400">текущая стоимость урока</p>
          </div>
        </div>
      </div>

      {/* ─── 4. TAX NPD INTEGRATION TILE ─── */}
      <button
        type="button"
        onClick={() => setIsNpdModalOpen(true)}
        className="w-full p-4 rounded-2xl border border-slate-200/80 dark:border-white/10 dark:bg-white/[0.04] bg-white shadow-sm hover:border-[#10B981] transition-all group text-left flex items-center justify-between"
      >
        <div className="flex items-center gap-3">
          <div className="w-9 h-9 rounded-xl bg-emerald-500/10 text-emerald-500 flex items-center justify-center shrink-0">
            <ShieldCheck className="w-5 h-5" />
          </div>
          <div>
            <div className="flex items-center gap-2">
              <h5 className="text-xs font-bold dark:text-white text-slate-900">
                Налог на профдоход (НПД 10%)
              </h5>
              <span className="text-[10px] font-mono px-1.5 py-0.2 rounded bg-emerald-500/10 text-emerald-500 font-bold">
                Беларусь
              </span>
            </div>
            <p className="text-[11px] text-slate-400">
              Формирование чеков в приложении «Профдоход» МНС РБ
            </p>
          </div>
        </div>
        <ChevronRight className="w-4 h-4 text-slate-400 group-hover:translate-x-0.5 transition-transform" />
      </button>



      {/* ─── TRANSACTION HISTORY BOTTOM SHEET MODAL ─── */}
      <AnimatePresence>
        {isHistoryOpen && (
          <div className="fixed inset-0 z-[70] flex items-end justify-center">
            <motion.div
              initial={{ opacity: 0 }}
              animate={{ opacity: 1 }}
              exit={{ opacity: 0 }}
              onClick={() => setIsHistoryOpen(false)}
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
                <div>
                  <h4 className="text-base font-extrabold dark:text-white text-slate-900">
                    История операций
                  </h4>
                  <span className="text-[11px] text-slate-400">
                    Проведённые уроки и прямые расчёты
                  </span>
                </div>

                <button
                  type="button"
                  onClick={() => setIsHistoryOpen(false)}
                  className="w-8 h-8 rounded-full bg-slate-100 dark:bg-white/10 flex items-center justify-center text-slate-600 dark:text-white/70"
                >
                  <X className="w-4 h-4" />
                </button>
              </div>

              <div className="flex-1 overflow-y-auto divide-y divide-slate-100 dark:divide-white/5 pr-1">
                {isLoadingTransactions ? (
                  <div className="py-12 flex flex-col items-center justify-center text-slate-400">
                    <Loader2 className="w-6 h-6 animate-spin mb-2 text-[#7D39EB]" />
                    <span>Загрузка операций...</span>
                  </div>
                ) : transactions.length === 0 ? (
                  <div className="py-12 text-center text-slate-400">
                    <Wallet className="w-8 h-8 mx-auto mb-2 opacity-50" />
                    <span>История операций пока пуста</span>
                  </div>
                ) : (
                  transactions.map((tx) => (
                    <div key={tx.id} className="py-3 flex items-center justify-between">
                      <div className="space-y-0.5">
                        <div className="font-bold dark:text-white text-slate-900">
                          {tx.description}
                        </div>
                        <div className="text-[10px] text-slate-400 flex items-center gap-2">
                          <span>{tx.date}</span>
                          <span>•</span>
                          <span>{tx.payment_method}</span>
                        </div>
                      </div>

                      <div className="text-right">
                        <div className="font-mono font-bold text-sm text-emerald-500">
                          +{Number(tx.net_amount || tx.amount).toFixed(2)} BYN
                        </div>
                        <span className="text-[9px] uppercase font-bold tracking-wider px-1.5 py-0.2 rounded bg-emerald-500/10 text-emerald-500">
                          {tx.status === 'succeeded' ? 'Оплачено' : tx.status}
                        </span>
                      </div>
                    </div>
                  ))
                )}
              </div>
            </motion.div>
          </div>
        )}
      </AnimatePresence>

      {/* ─── NPD BOTTOM SHEET MODAL ─── */}
      <AnimatePresence>
        {isNpdModalOpen && (
          <div className="fixed inset-0 z-[70] flex items-end justify-center">
            <motion.div
              initial={{ opacity: 0 }}
              animate={{ opacity: 1 }}
              exit={{ opacity: 0 }}
              onClick={() => setIsNpdModalOpen(false)}
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
                  <div className="w-8 h-8 rounded-xl bg-emerald-500/10 text-emerald-500 flex items-center justify-center">
                    <ShieldCheck className="w-4 h-4" />
                  </div>
                  <div>
                    <h4 className="text-base font-extrabold dark:text-white text-slate-900">
                      Налог на профдоход (НПД)
                    </h4>
                    <span className="text-[11px] text-slate-400">
                      Легальная работа репетитором в Беларуси
                    </span>
                  </div>
                </div>

                <button
                  type="button"
                  onClick={() => setIsNpdModalOpen(false)}
                  className="w-8 h-8 rounded-full bg-slate-100 dark:bg-white/10 flex items-center justify-center text-slate-600 dark:text-white/70"
                >
                  <X className="w-4 h-4" />
                </button>
              </div>

              <div className="flex-1 overflow-y-auto space-y-3.5 pr-1">
                {/* Status toast */}
                {npdMessage && (
                  <div className="p-3 rounded-2xl bg-emerald-500/20 border border-emerald-500/40 text-emerald-800 dark:text-emerald-300 text-xs font-bold flex items-center gap-2">
                    <Check className="w-4 h-4 text-emerald-500 shrink-0" />
                    <span>{npdMessage}</span>
                  </div>
                )}

                {/* 1. Tax metrics grid */}
                <div className="grid grid-cols-2 gap-2">
                  <div className="p-3.5 rounded-2xl dark:bg-white/5 bg-slate-50 border border-white/10 space-y-1">
                    <span className="text-[10px] font-extrabold uppercase text-slate-400">
                      Налог к уплате (10%)
                    </span>
                    <div className="text-xl font-black dark:text-[#C6FF33] text-[#7D39EB] font-rimma">
                      {Number(npd.tax_due_month || 0).toFixed(2)} BYN
                    </div>
                    {npd.tax_saved > 0 && (
                      <span className="text-[10px] text-emerald-500 font-bold block">
                        Сэкономлено: {Number(npd.tax_saved).toFixed(2)} BYN
                      </span>
                    )}
                  </div>

                  <div className="p-3.5 rounded-2xl dark:bg-white/5 bg-slate-50 border border-white/10 space-y-1">
                    <span className="text-[10px] font-extrabold uppercase text-slate-400">
                      Доход за месяц
                    </span>
                    <div className="text-xl font-black dark:text-white text-slate-900 font-rimma">
                      {Number(npd.month_gross || 0).toFixed(2)} BYN
                    </div>
                    <span className="text-[10px] text-slate-400 font-medium block">
                      С начала года: {Number(npd.year_gross || 0).toFixed(2)} BYN
                    </span>
                  </div>
                </div>

                {/* 2. 2000 BYN deduction switch & progress bar */}
                <div className="p-4 rounded-2xl border border-white/10 dark:bg-white/5 bg-slate-50 space-y-3">
                  <div className="flex items-center justify-between">
                    <div>
                      <h5 className="font-bold dark:text-white text-slate-900 text-xs">
                        Льготный вычет 2 000 руб.
                      </h5>
                      <p className="text-[11px] text-slate-400">
                        Для впервые зарегистрированных плательщиков НПД
                      </p>
                    </div>

                    <button
                      type="button"
                      onClick={handleToggleNpdDeduction}
                      className={`w-11 h-6 rounded-full transition-colors relative p-0.5 ${
                        npd.apply_first_time_deduction ? 'bg-[#7D39EB]' : 'bg-slate-300 dark:bg-white/20'
                      }`}
                    >
                      <div
                        className={`w-5 h-5 rounded-full bg-white transition-transform ${
                          npd.apply_first_time_deduction ? 'translate-x-5' : 'translate-x-0'
                        }`}
                      />
                    </button>
                  </div>

                  {npd.apply_first_time_deduction && (
                    <div className="space-y-1.5 pt-1">
                      <div className="flex items-center justify-between text-[11px] text-slate-400 font-medium">
                        <span>Остаток вычета: {Number(npd.deduction_remaining || 0).toFixed(2)} BYN</span>
                        <span>{Number(npd.deduction_used || 0).toFixed(2)} / 2000 BYN</span>
                      </div>
                      <div className="w-full h-2 rounded-full bg-slate-200 dark:bg-white/10 overflow-hidden">
                        <div
                          className="h-full bg-gradient-to-r from-emerald-500 to-[#C6FF33] rounded-full transition-all duration-500"
                          style={{
                            width: `${Math.min(100, ((npd.deduction_used || 0) / 2000) * 100)}%`,
                          }}
                        />
                      </div>
                    </div>
                  )}
                </div>

                {/* 3. Deadlines */}
                <div className="p-3.5 rounded-2xl bg-amber-500/10 border border-amber-500/20 text-amber-800 dark:text-amber-300 text-[11px] space-y-1">
                  <div className="font-bold flex items-center gap-1.5">
                    <Clock className="w-3.5 h-3.5 text-amber-500" />
                    <span>Сроки отчетности и уплаты налога:</span>
                  </div>
                  <p>• Внесение чеков за прошлый месяц: до <strong>10 числа</strong></p>
                  <p>• Уплата налога через ЕРИП: до <strong>{npd.payment_deadline || '22 числа'}</strong></p>
                </div>

                {/* 4. One-click 1-CLICK RECEIPT GENERATION CTA */}
                <button
                  type="button"
                  disabled={isGeneratingNpd}
                  onClick={handleGenerateOneClickReceipts}
                  className="w-full py-3 px-4 rounded-2xl bg-gradient-to-tr from-emerald-600 to-teal-500 hover:from-emerald-500 hover:to-teal-400 text-white font-extrabold text-xs shadow-lg shadow-emerald-500/25 active:scale-98 transition-all flex items-center justify-center gap-2"
                >
                  {isGeneratingNpd ? (
                    <>
                      <Loader2 className="w-4 h-4 animate-spin" />
                      <span>Формирование чеков...</span>
                    </>
                  ) : (
                    <>
                      <Sparkles className="w-4 h-4 text-[#C6FF33]" />
                      <span>⚡ Сформировать данные для чеков в 1 клик</span>
                    </>
                  )}
                </button>

                {/* 5. Lessons and Receipts List */}
                <div className="space-y-2 pt-2">
                  <div className="flex items-center justify-between">
                    <h5 className="font-bold dark:text-white text-slate-900 text-xs">
                      Уроки за месяц ({npd.lessons?.length || 0})
                    </h5>
                    <span className="text-[11px] text-slate-400">
                      Сформировано: {npd.receipts_issued_count || 0} / Ожидает: {npd.receipts_pending_count || 0}
                    </span>
                  </div>

                  {(!npd.lessons || npd.lessons.length === 0) ? (
                    <p className="text-center py-4 text-slate-400 text-xs">
                      Нет завершенных уроков за текущий месяц
                    </p>
                  ) : (
                    <div className="space-y-2 max-h-60 overflow-y-auto">
                      {npd.lessons.map((lesson) => (
                        <div
                          key={lesson.id}
                          className="p-3 rounded-xl border border-white/10 dark:bg-white/5 bg-slate-50 flex items-center justify-between gap-2"
                        >
                          <div className="space-y-0.5">
                            <div className="flex items-center gap-2">
                              <span className="font-bold dark:text-white text-slate-900 text-xs">
                                {lesson.student_name}
                              </span>
                              <span className="text-[11px] text-slate-400">
                                {lesson.subject}
                              </span>
                            </div>
                            <span className="text-[10px] text-slate-400 block">
                              {lesson.date} • {lesson.price} BYN
                            </span>
                          </div>

                          <div className="flex items-center gap-1.5 shrink-0">
                            {lesson.is_receipt_issued ? (
                              <div className="text-right">
                                <span className="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 text-[10px] font-bold">
                                  <Check className="w-3 h-3" />
                                  <span>{lesson.receipt_number || 'Выбит'}</span>
                                </span>
                                <button
                                  type="button"
                                  onClick={() => handleUpdateReceiptStatus(lesson.id, 'unmark')}
                                  className="text-[10px] text-slate-400 hover:text-rose-500 block underline mt-0.5"
                                >
                                  Сбросить
                                </button>
                              </div>
                            ) : (
                              <button
                                type="button"
                                onClick={() => handleUpdateReceiptStatus(lesson.id, 'issue')}
                                className="px-2.5 py-1 rounded-lg bg-[#7D39EB] text-white text-[10px] font-extrabold hover:bg-[#6c2ee0]"
                              >
                                Отметить чек
                              </button>
                            )}
                          </div>
                        </div>
                      ))}
                    </div>
                  )}
                </div>
              </div>
            </motion.div>
          </div>
        )}
      </AnimatePresence>

      {/* ─── SUBSCRIPTION PLANS COMPARISON MODAL ─── */}
      <SubscriptionPlansModal
        isOpen={isPlanModalOpen}
        onClose={() => setIsPlanModalOpen(false)}
        currentPlan={subData?.plan || 'pro'}
        onPlanChanged={(newSub) => {
          if (newSub) {
            setSubData((prev) => ({
              ...prev,
              ...newSub,
              is_active: true,
              status: 'active',
            }));
          }
        }}
      />
    </div>
  );
}
