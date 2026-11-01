import React, { useState, useRef } from 'react';
import { motion, AnimatePresence } from 'motion/react';
import { 
  X, Check, Sparkles, Crown, Zap, ShieldCheck, ChevronRight, 
  Loader2, Tag, CreditCard, AlertCircle
} from 'lucide-react';
import axios from 'axios';

export default function SubscriptionPlansModal({
  isOpen,
  onClose,
  currentPlan = 'pro',
  onPlanChanged,
}) {
  const [billingPeriod, setBillingPeriod] = useState('monthly'); // 'monthly' | 'yearly'
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [selectedPlanId, setSelectedPlanId] = useState(null);
  const [successMessage, setSuccessMessage] = useState(null);
  const [activeTab, setActiveTab] = useState('cards'); // 'cards' | 'comparison'

  // Promo code states
  const [promoCode, setPromoCode] = useState('');
  const [isApplyingPromo, setIsApplyingPromo] = useState(false);
  const isApplyingPromoRef = useRef(false);
  const [appliedPromo, setAppliedPromo] = useState(null); // { code, discount, isFree }
  const [promoError, setPromoError] = useState(null);
  const [promoSuccess, setPromoSuccess] = useState(null);
  const [checkoutError, setCheckoutError] = useState(null);

  if (!isOpen) return null;

  const plans = [
    {
      id: 'start',
      name: 'Стандарт',
      badge: 'Для старта',
      icon: Zap,
      accentColor: '#0284C7',
      monthlyPrice: 20,
      yearlyMonthlyPrice: 16,
      yearlyTotal: 192,
      description: 'Для комфортной работы со своими постоянными учениками в приватном кабинете.',
      features: [
        'До 5 активных учеников',
        'Приватный кабинет (анкета и цены скрыты)',
        'Виртуальный класс (SFU) и интерактивная доска',
        'Персональная ссылка для записи',
        'Базовое облачное хранилище (1 ГБ)',
        'Ручной учет доходов и чеков',
      ],
    },
    {
      id: 'pro',
      name: 'Pro',
      badge: '⭐ Хит продаж',
      icon: Sparkles,
      accentColor: '#7D39EB',
      isPopular: true,
      monthlyPrice: 40,
      yearlyMonthlyPrice: 32,
      yearlyTotal: 384,
      description: 'Для стабильного дохода: ИИ-помощник, авто-чеки НПД и привлечение учеников.',
      features: [
        'До 15 активных учеников',
        'Открытая витрина в каталоге или приватный режим',
        '50 генераций ИИ в месяц (планы уроков, тесты ЦТ/ЦЭ)',
        'Авто-генерация чеков НПД для МНС РБ в 1 клик',
        'Виртуальный класс без ограничений по времени + 15 ГБ',
        'Автонапоминания ученикам в SMS и Telegram',
        'Отклики на заявки новых учеников (до 10 в месяц)',
      ],
    },
    {
      id: 'premium',
      name: 'Премиум',
      badge: '👑 Топ-эксперт',
      icon: Crown,
      accentColor: '#D97706',
      monthlyPrice: 60,
      yearlyMonthlyPrice: 48,
      yearlyTotal: 576,
      description: 'Режим «Личная школа» (White-Label), безлимит учеников и ТОП-1 в каталоге.',
      features: [
        'БЕЗЛИМИТНО учеников (без ограничений)',
        'Режим «Личная онлайн-школа» (White-Label бренд)',
        'БЕЗЛИМИТНЫЙ ИИ-методист Майя',
        'Полный автопилот чеков НПД',
        'Золотой бейдж «Топ-эксперт» и ТОП-1 в каталоге',
        'Видео-визитка прямо в поисковой выдаче',
        'Синхронизация с Google Calendar',
        'Безлимитные отклики на бирже учеников',
        'Персональный менеджер и VIP-поддержка 24/7',
      ],
    },
  ];

  const comparisonRows = [
    { title: 'Активные ученики', start: 'До 5', pro: 'До 15', premium: 'Безлимитно' },
    { title: 'ИИ-Методист Майя', start: '10 ген/мес', pro: '50 ген/мес', premium: 'Безлимитно' },
    { title: 'Чеки НПД (МНС РБ)', start: 'Ручной ввод', pro: 'В 1 клик', premium: 'Полный автопилот' },
    { title: 'Виртуальный класс (SFU)', start: 'До 60 мин', pro: 'Без ограничений', premium: 'Без ограничений' },
    { title: 'Интерактивная доска', start: '✓', pro: '✓', premium: '✓' },
    { title: 'Облачное хранилище', start: '1 ГБ', pro: '15 ГБ', premium: '100 ГБ' },
    { title: 'Отклики на заявки учеников', start: '0', pro: '10 в месяц', premium: 'Безлимитно' },
    { title: 'Позиция в каталоге', start: 'Базовая', pro: 'Высокая (5x)', premium: 'ТОП-1 (10x)' },
    { title: 'Личная школа White-Label', start: '—', pro: '—', premium: '✓ Включено' },
    { title: 'Видео-визитка в анкете', start: '—', pro: '—', premium: '✓ Включено' },
    { title: 'Синхронизация Google Календарь', start: '—', pro: '—', premium: '✓ Включено' },
    { title: 'VIP-поддержка 24/7', start: 'Стандарт', pro: 'Приоритетная', premium: 'Персональный менеджер' },
  ];

  const handleApplyPromo = async () => {
    if (isApplyingPromoRef.current) return;
    const trimmed = promoCode.trim().toUpperCase();
    if (!trimmed) {
      setPromoError('Введите промокод.');
      return;
    }

    isApplyingPromoRef.current = true;
    setIsApplyingPromo(true);
    setPromoError(null);
    setPromoSuccess(null);
    setCheckoutError(null);

    try {
      const res = await axios.post('/api/mobile/promocode/apply', {
        code: trimmed,
        plan: selectedPlanId || undefined,
      });

      if (res.data?.success) {
        setPromoError(null);
        if (res.data.is_activated) {
          // Free subscription activated via promo!
          setPromoSuccess(res.data.message || '🎉 Тариф успешно активирован!');
          setAppliedPromo({ code: trimmed, isFree: true });
          if (onPlanChanged) {
            try {
              onPlanChanged(res.data.subscription);
            } catch (e) {
              console.warn('[SubscriptionPlansModal] onPlanChanged error:', e);
            }
          }
          setTimeout(() => {
            onClose();
          }, 1200);
        } else {
          // Discount promo applied
          setPromoSuccess(res.data.message || 'Промокод применён!');
          setAppliedPromo({
            code: trimmed,
            discount: res.data.discount,
            isFree: false,
          });
        }
      } else {
        setPromoError(res.data?.error || res.data?.message || 'Недействительный промокод.');
      }
    } catch (err) {
      const errorMsg = err.response?.data?.error || err.response?.data?.message;
      if (err.response?.data?.is_activated || (errorMsg && (errorMsg.includes('уже использовали') || errorMsg.includes('уже активирован')))) {
        setPromoError(null);
        setPromoSuccess('🎉 Тариф успешно активирован!');
        setAppliedPromo({ code: trimmed, isFree: true });
        if (onPlanChanged) {
          try {
            onPlanChanged(err.response?.data?.subscription);
          } catch (e) {
            console.warn('[SubscriptionPlansModal] onPlanChanged error:', e);
          }
        }
        setTimeout(() => {
          onClose();
        }, 1200);
      } else {
        setPromoError(errorMsg || 'Недействительный или истекший промокод.');
      }
    } finally {
      isApplyingPromoRef.current = false;
      setIsApplyingPromo(false);
    }
  };

  const handleSelectPlan = async (planId) => {
    setSelectedPlanId(planId);
    setIsSubmitting(true);
    setCheckoutError(null);
    setPromoError(null);
    setSuccessMessage(null);

    try {
      const res = await axios.post('/api/mobile/subscription/checkout', {
        plan: planId,
        billing_period: billingPeriod,
        promo_code: appliedPromo?.code || (promoCode.trim().toUpperCase() || undefined),
      });

      if (res.data?.is_activated) {
        setSuccessMessage(res.data.message || 'Тариф успешно активирован!');
        if (onPlanChanged) {
          onPlanChanged(res.data.subscription);
        }
        setTimeout(() => {
          onClose();
          setSuccessMessage(null);
        }, 1500);
        return;
      }

      if (res.data?.redirect_url) {
        setSuccessMessage('Перенаправление на защищенную страницу оплаты Альфа-Банка...');
        window.location.href = res.data.redirect_url;
        return;
      }

      if (res.data?.error) {
        setCheckoutError(res.data.error);
      }
    } catch (err) {
      console.error(err);
      setCheckoutError(err.response?.data?.error || err.response?.data?.message || 'Не удалось сформировать платёж. Попробуйте снова.');
    } finally {
      setIsSubmitting(false);
    }
  };

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-6 overflow-y-auto">
      {/* Backdrop */}
      <motion.div
        initial={{ opacity: 0 }}
        animate={{ opacity: 1 }}
        exit={{ opacity: 0 }}
        onClick={onClose}
        className="fixed inset-0 bg-slate-900/60 backdrop-blur-md"
      />

      {/* Modal Dialog (Light Theme) */}
      <motion.div
        initial={{ opacity: 0, scale: 0.96, y: 12 }}
        animate={{ opacity: 1, scale: 1, y: 0 }}
        exit={{ opacity: 0, scale: 0.96, y: 12 }}
        transition={{ duration: 0.2 }}
        className="relative z-10 w-full max-w-4xl max-h-[92vh] flex flex-col rounded-[28px] border border-slate-200/90 bg-white text-slate-900 shadow-2xl overflow-hidden"
      >
        {/* Top Header */}
        <div className="p-5 sm:p-6 pb-4 border-b border-slate-200/80 bg-slate-50/50 flex items-center justify-between shrink-0">
          <div>
            <div className="flex items-center gap-2">
              <span className="px-2.5 py-0.5 rounded-full bg-purple-100 text-[#7D39EB] border border-purple-200/60 text-[10px] font-black uppercase tracking-wider">
                Тарифы Edusfera 2026
              </span>
              <span className="text-xs text-slate-500 font-medium hidden sm:inline">• Доступ активируется после оплаты или промокода</span>
            </div>
            <h3 className="text-lg sm:text-xl font-extrabold tracking-tight text-slate-900 mt-1.5">
              Выберите подходящий тарифный план
            </h3>
          </div>

          <button
            type="button"
            onClick={onClose}
            className="w-9 h-9 rounded-full bg-slate-100 hover:bg-slate-200 flex items-center justify-center text-slate-500 hover:text-slate-800 transition active:scale-95"
            aria-label="Закрыть"
          >
            <X className="w-4 h-4" />
          </button>
        </div>

        {/* View Switcher & Period Switcher */}
        <div className="px-5 sm:px-6 py-3 bg-white border-b border-slate-100 flex flex-wrap items-center justify-between gap-3 shrink-0">
          {/* Cards vs Comparison Tab */}
          <div className="flex items-center gap-1 p-1 rounded-xl bg-slate-100 border border-slate-200/60">
            <button
              type="button"
              onClick={() => {
                setActiveTab('cards');
                setCheckoutError(null);
                setPromoError(null);
              }}
              className={`px-3 py-1.5 rounded-lg text-xs font-bold transition ${
                activeTab === 'cards'
                  ? 'bg-white text-[#7D39EB] shadow-sm'
                  : 'text-slate-600 hover:text-slate-900'
              }`}
            >
              Тарифные карты
            </button>
            <button
              type="button"
              onClick={() => {
                setActiveTab('comparison');
                setCheckoutError(null);
                setPromoError(null);
              }}
              className={`px-3 py-1.5 rounded-lg text-xs font-bold transition ${
                activeTab === 'comparison'
                  ? 'bg-white text-[#7D39EB] shadow-sm'
                  : 'text-slate-600 hover:text-slate-900'
              }`}
            >
              Сравнение всех опций
            </button>
          </div>

          {/* Monthly vs Yearly Switcher */}
          <div className="flex items-center gap-2">
            <span className={`text-xs font-bold ${billingPeriod === 'monthly' ? 'text-slate-900' : 'text-slate-400'}`}>
              Месяц
            </span>

            <button
              type="button"
              onClick={() => {
                setBillingPeriod(prev => prev === 'monthly' ? 'yearly' : 'monthly');
                setCheckoutError(null);
              }}
              className="relative w-12 h-6 rounded-full bg-slate-200 p-1 transition-colors hover:bg-slate-300"
            >
              <div
                className={`w-4 h-4 rounded-full bg-[#7D39EB] transition-transform shadow-sm ${
                  billingPeriod === 'yearly' ? 'translate-x-6' : 'translate-x-0'
                }`}
              />
            </button>

            <span className={`text-xs font-bold flex items-center gap-1.5 ${billingPeriod === 'yearly' ? 'text-slate-900' : 'text-slate-400'}`}>
              <span>Год</span>
              <span className="px-1.5 py-0.5 rounded-md bg-emerald-100 text-emerald-700 text-[9px] font-black uppercase">
                -20%
              </span>
            </span>
          </div>
        </div>

        {/* Promo Code Input Card (Light Theme) */}
        <div className="px-5 sm:px-6 pt-3 pb-2 bg-slate-50/70 border-b border-slate-100 shrink-0">
          <div className="p-3.5 sm:p-4 rounded-2xl bg-white border border-slate-200/80 shadow-sm flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3">
            <div className="flex items-center gap-3">
              <div className="w-9 h-9 rounded-xl bg-purple-100 text-[#7D39EB] flex items-center justify-center shrink-0">
                <Tag className="w-4 h-4" />
              </div>
              <div>
                <div className="text-xs font-bold text-slate-900 flex items-center gap-2">
                  <span>Есть промокод?</span>
                  <span className="text-[10px] text-emerald-600 font-semibold">• Бесплатная активация или скидка</span>
                </div>
                <p className="text-[11px] text-slate-500 mt-0.5">
                  Введите промокод для мгновенного открытия доступа к кабинету
                </p>
              </div>
            </div>

            <div className="flex items-center gap-2 w-full sm:w-auto">
              <input
                type="text"
                value={promoCode}
                onChange={(e) => {
                  setPromoCode(e.target.value.toUpperCase());
                  setPromoError(null);
                  setCheckoutError(null);
                }}
                onKeyDown={(e) => {
                  if (e.key === 'Enter') {
                    e.preventDefault();
                    handleApplyPromo();
                  }
                }}
                placeholder="Введите промокод"
                disabled={isApplyingPromo || appliedPromo?.isFree}
                className="flex-1 sm:w-48 px-3.5 py-2 rounded-xl bg-white border border-slate-300 text-xs text-slate-900 placeholder:text-slate-400 uppercase tracking-wider font-mono focus:outline-none focus:border-[#7D39EB] focus:ring-2 focus:ring-purple-100 transition"
              />
              <button
                type="button"
                onClick={handleApplyPromo}
                disabled={isApplyingPromo || !promoCode.trim() || appliedPromo?.isFree}
                className="px-4 py-2 rounded-xl bg-[#7D39EB] hover:bg-purple-700 disabled:opacity-50 text-white text-xs font-bold transition flex items-center gap-1.5 shrink-0 active:scale-95 shadow-md shadow-purple-500/20"
              >
                {isApplyingPromo ? (
                  <Loader2 className="w-3.5 h-3.5 animate-spin" />
                ) : (
                  <span>Применить</span>
                )}
              </button>
            </div>
          </div>

          {promoSuccess && (
            <div className="mt-2.5 p-2.5 rounded-xl bg-emerald-50 border border-emerald-200 text-xs font-bold text-emerald-700 flex items-center gap-2">
              <Check className="w-4 h-4 shrink-0 text-emerald-600" />
              <span>{promoSuccess}</span>
            </div>
          )}

          {promoError && (
            <div className="mt-2.5 p-2.5 rounded-xl bg-rose-50 border border-rose-200 text-xs font-medium text-rose-700 flex items-center justify-between gap-2">
              <div className="flex items-center gap-2">
                <AlertCircle className="w-4 h-4 shrink-0 text-rose-500" />
                <span>{promoError}</span>
              </div>
              <button
                type="button"
                onClick={() => setPromoError(null)}
                className="text-rose-400 hover:text-rose-600 p-0.5"
              >
                <X className="w-3.5 h-3.5" />
              </button>
            </div>
          )}

          {checkoutError && (
            <div className="mt-2.5 p-2.5 rounded-xl bg-rose-50 border border-rose-200 text-xs font-medium text-rose-700 flex items-center justify-between gap-2">
              <div className="flex items-center gap-2">
                <AlertCircle className="w-4 h-4 shrink-0 text-rose-500" />
                <span>{checkoutError}</span>
              </div>
              <button
                type="button"
                onClick={() => setCheckoutError(null)}
                className="text-rose-400 hover:text-rose-600 p-0.5"
              >
                <X className="w-3.5 h-3.5" />
              </button>
            </div>
          )}
        </div>

        {/* Success Toast */}
        {successMessage && (
          <div className="m-4 p-3 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-700 text-xs font-bold text-center shadow-sm">
            {successMessage}
          </div>
        )}

        {/* Modal Scrollable Body */}
        <div className="flex-1 overflow-y-auto p-5 sm:p-6 bg-slate-50/40">
          {activeTab === 'cards' ? (
            /* ─── 3 CARDS VIEW (LIGHT THEME) ─── */
            <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
              {plans.map((p) => {
                const IconComp = p.icon;
                const isCurrent = currentPlan?.toLowerCase() === p.id;
                const isSelected = selectedPlanId === p.id;
                const displayPrice = billingPeriod === 'yearly' ? p.yearlyMonthlyPrice : p.monthlyPrice;

                return (
                  <div
                    key={p.id}
                    className={`relative rounded-[24px] p-5 flex flex-col justify-between border transition-all duration-200 ${
                      p.isPopular
                        ? 'border-2 border-[#7D39EB] bg-gradient-to-b from-purple-50/70 via-white to-purple-50/30 shadow-xl shadow-purple-500/10'
                        : 'border-slate-200 bg-white hover:border-slate-300 hover:shadow-md'
                    }`}
                  >
                    {/* Popular Pill */}
                    {p.isPopular && (
                      <div className="absolute -top-3 left-1/2 -translate-x-1/2 px-3.5 py-1 rounded-full bg-[#7D39EB] text-white text-[10px] font-black uppercase tracking-wider shadow-md">
                        {p.badge}
                      </div>
                    )}

                    <div>
                      {/* Card Header */}
                      <div className="flex items-center justify-between mb-3">
                        <div className="flex items-center gap-2">
                          <div 
                            className="w-9 h-9 rounded-xl flex items-center justify-center font-bold"
                            style={{ backgroundColor: `${p.accentColor}15`, color: p.accentColor }}
                          >
                            <IconComp className="w-4 h-4" />
                          </div>
                          <div>
                            <h4 className="text-base font-black text-slate-900">{p.name}</h4>
                            {!p.isPopular && (
                              <span className="text-[10px] text-slate-500 font-bold uppercase">{p.badge}</span>
                            )}
                          </div>
                        </div>

                        {isCurrent && (
                          <span className="px-2 py-0.5 rounded-full bg-emerald-100 border border-emerald-200 text-emerald-700 text-[10px] font-extrabold">
                            Текущий
                          </span>
                        )}
                      </div>

                      <p className="text-xs text-slate-500 mb-4 min-h-[32px] leading-relaxed">
                        {p.description}
                      </p>

                      {/* Pricing Tag */}
                      <div className={`mb-5 p-3.5 rounded-2xl border ${
                        p.isPopular
                          ? 'bg-purple-100/40 border-purple-200/70'
                          : 'bg-slate-50 border-slate-100'
                      }`}>
                        <div className="flex items-baseline gap-1">
                          <span className={`text-3xl font-black tracking-tight ${
                            p.isPopular ? 'text-[#7D39EB]' : 'text-slate-900'
                          }`}>
                            {displayPrice}
                          </span>
                          <span className="text-xs text-slate-500 font-bold">BYN / месяц</span>
                        </div>
                        {billingPeriod === 'yearly' && (
                          <p className="text-[10px] text-emerald-600 font-semibold mt-0.5">
                            {p.yearlyTotal} BYN/год (экономия 2 месяца)
                          </p>
                        )}
                      </div>

                      {/* Features List */}
                      <div className="space-y-2.5 mb-6">
                        {p.features.map((feat, idx) => (
                          <div key={idx} className="flex items-start gap-2 text-xs text-slate-700">
                            <Check 
                              className="w-3.5 h-3.5 shrink-0 mt-0.5" 
                              style={{ color: p.accentColor }}
                            />
                            <span className="leading-tight">{feat}</span>
                          </div>
                        ))}
                      </div>
                    </div>

                    {/* Action Button: Strictly Checkout via Alfa-Bank or Promo */}
                    <button
                      type="button"
                      disabled={isSubmitting}
                      onClick={() => handleSelectPlan(p.id)}
                      className={`w-full min-h-[44px] rounded-xl font-bold text-xs flex items-center justify-center gap-1.5 transition active:scale-95 ${
                        p.isPopular
                          ? 'bg-[#7D39EB] text-white hover:bg-purple-700 shadow-lg shadow-purple-500/25'
                          : 'bg-slate-900 text-white hover:bg-slate-800 shadow-sm'
                      }`}
                    >
                      {isSubmitting && isSelected ? (
                        <Loader2 className="w-4 h-4 animate-spin" />
                      ) : (
                        <>
                          <CreditCard className="w-3.5 h-3.5" />
                          <span>Оплатить {displayPrice} BYN картой</span>
                          <ChevronRight className="w-3.5 h-3.5" />
                        </>
                      )}
                    </button>
                  </div>
                );
              })}
            </div>
          ) : (
            /* ─── DETAILED COMPARISON TABLE (LIGHT THEME) ─── */
            <div className="rounded-2xl border border-slate-200 overflow-hidden bg-white shadow-sm">
              <div className="overflow-x-auto">
                <table className="w-full text-left text-xs">
                  <thead>
                    <tr className="border-b border-slate-200 bg-slate-50">
                      <th className="p-3.5 font-bold text-slate-600">Возможность</th>
                      <th className="p-3.5 font-bold text-sky-700">Стандарт (20 BYN)</th>
                      <th className="p-3.5 font-bold text-[#7D39EB]">Pro (40 BYN)</th>
                      <th className="p-3.5 font-bold text-amber-700">Премиум (60 BYN)</th>
                    </tr>
                  </thead>
                  <tbody className="divide-y divide-slate-100">
                    {comparisonRows.map((row, idx) => (
                      <tr key={idx} className="hover:bg-slate-50/80 transition">
                        <td className="p-3.5 font-medium text-slate-800">{row.title}</td>
                        <td className="p-3.5 text-slate-500 font-mono">{row.start}</td>
                        <td className="p-3.5 text-[#7D39EB] font-mono font-bold">{row.pro}</td>
                        <td className="p-3.5 text-amber-700 font-mono font-bold">{row.premium}</td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            </div>
          )}
        </div>

        {/* Footer Note */}
        <div className="p-4 bg-slate-50 border-t border-slate-200 flex items-center justify-between text-[11px] text-slate-500 shrink-0">
          <div className="flex items-center gap-1.5">
            <ShieldCheck className="w-3.5 h-3.5 text-emerald-600" />
            <span>0% комиссия платформы со всех уроков — расчеты учеников идут вам напрямую</span>
          </div>
          <span className="hidden sm:inline font-medium text-slate-600">Безопасная оплата картами РБ (Альфа-Банк)</span>
        </div>
      </motion.div>
    </div>
  );
}
