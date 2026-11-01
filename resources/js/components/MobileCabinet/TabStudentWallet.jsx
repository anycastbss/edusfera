import React, { useState } from 'react';
import axios from 'axios';
import { 
  Wallet, 
  CreditCard, 
  ArrowUpRight, 
  Clock, 
  ShieldCheck, 
  CheckCircle2, 
  Plus, 
  History,
  Sparkles
} from 'lucide-react';

const PRESET_AMOUNTS = [20, 35, 50, 70, 100, 150];

export default function TabStudentWallet({
  balance = { available: 0, pending: 0, total_spent: 0, currency: 'BYN' },
  onOpenHistory = null
}) {
  const [selectedAmount, setSelectedAmount] = useState(35);
  const [customAmount, setCustomAmount] = useState('');
  const [isProcessing, setIsProcessing] = useState(false);
  const [toastMessage, setToastMessage] = useState(null);

  const finalAmount = customAmount ? parseFloat(customAmount) : selectedAmount;

  const handleTopup = async () => {
    if (!finalAmount || finalAmount < 10) {
      alert('Минимальная сумма пополнения — 10 BYN');
      return;
    }

    setIsProcessing(true);
    try {
      const response = await axios.post('/api/mobile/wallet/topup', {
        amount: finalAmount,
      });

      if (response.data?.redirect_url) {
        window.location.href = response.data.redirect_url;
      } else if (response.data?.success) {
        setToastMessage(response.data.message || `Баланс успешно пополнен на ${finalAmount} BYN`);
        setTimeout(() => setToastMessage(null), 4000);
      } else {
        alert(response.data?.error || 'Ошибка при пополнении');
      }
    } catch (err) {
      console.error(err);
      alert('Ошибка соединения с платежным шлюзом');
    } finally {
      setIsProcessing(false);
    }
  };

  return (
    <div className="space-y-4 pb-20">
      {/* ═══ 1. BALANCE CARD ═══ */}
      <div className="relative overflow-hidden rounded-3xl p-6 border border-white/10 bg-gradient-to-br from-[#1F1438] via-[#14121F] to-[#0A0A0C] text-white shadow-xl">
        <div className="absolute right-0 top-0 -mr-12 -mt-12 w-48 h-48 rounded-full bg-violet-600/20 blur-3xl pointer-events-none" />
        <div className="absolute left-1/3 bottom-0 -mb-10 w-40 h-40 rounded-full bg-[#C6FF33]/15 blur-2xl pointer-events-none" />

        <div className="relative z-10 space-y-4">
          <div className="flex items-center justify-between">
            <div className="flex items-center gap-2">
              <div className="w-8 h-8 rounded-xl bg-white/10 flex items-center justify-center">
                <Wallet className="w-4 h-4 text-[#C6FF33]" />
              </div>
              <span className="text-xs font-bold text-slate-300 uppercase tracking-wider">
                Баланс ученика
              </span>
            </div>

            <span className="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-extrabold bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">
              <ShieldCheck className="w-3 h-3" />
              <span>Безопасный счет</span>
            </span>
          </div>

          <div>
            <div className="text-3xl font-black font-rimma tracking-tight flex items-baseline gap-1.5">
              <span>{Number(balance.available || 0).toFixed(2)}</span>
              <span className="text-sm font-extrabold text-[#C6FF33]">BYN</span>
            </div>
            <p className="text-xs text-slate-400 mt-1">
              Всего оплачено уроков: {Number(balance.total_spent || 0).toFixed(2)} BYN
            </p>
          </div>
        </div>
      </div>

      {/* Success Notification */}
      {toastMessage && (
        <div className="p-3.5 rounded-2xl bg-emerald-500/20 border border-emerald-500/40 text-emerald-800 dark:text-emerald-300 text-xs font-bold flex items-center gap-2 shadow-lg">
          <CheckCircle2 className="w-4 h-4 text-emerald-500 shrink-0" />
          <span>{toastMessage}</span>
        </div>
      )}

      {/* ═══ 2. TOPUP SECTION ═══ */}
      <div className="p-5 rounded-3xl dark:bg-[#121118]/80 bg-white/80 border border-white/10 backdrop-blur-xl shadow-lg space-y-4">
        <div className="flex items-center justify-between">
          <h3 className="text-sm font-black dark:text-white text-slate-900 uppercase tracking-tight">
            Пополнить баланс
          </h3>
          <span className="text-xs text-slate-400 font-bold">Карты РБ / Белкарт</span>
        </div>

        {/* Preset grid */}
        <div className="grid grid-cols-3 gap-2">
          {PRESET_AMOUNTS.map((amt) => {
            const isSelected = selectedAmount === amt && !customAmount;
            return (
              <button
                key={amt}
                type="button"
                onClick={() => {
                  setSelectedAmount(amt);
                  setCustomAmount('');
                }}
                className={`py-3 px-2 rounded-2xl text-xs font-black transition-all ${
                  isSelected
                    ? 'bg-[#7D39EB] text-white shadow-md shadow-violet-500/30'
                    : 'dark:bg-white/5 bg-slate-100 text-slate-700 dark:text-slate-300 hover:bg-white/10'
                }`}
              >
                {amt} BYN
              </button>
            );
          })}
        </div>

        {/* Custom amount */}
        <div>
          <label className="block text-[10px] font-extrabold uppercase text-slate-400 mb-1">
            Или укажите свою сумму (от 10 BYN)
          </label>
          <div className="relative">
            <input
              type="number"
              min="10"
              max="5000"
              placeholder="Например: 45"
              value={customAmount}
              onChange={(e) => setCustomAmount(e.target.value)}
              className="w-full text-sm font-bold py-2.5 px-3 rounded-2xl border border-white/10 dark:bg-black/40 bg-white text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-[#7D39EB]"
            />
            <span className="absolute right-3 top-2.5 text-xs font-extrabold text-slate-400">
              BYN
            </span>
          </div>
        </div>

        {/* Action CTA Button */}
        <button
          type="button"
          disabled={isProcessing || !finalAmount || finalAmount < 10}
          onClick={handleTopup}
          className="w-full py-3.5 px-4 rounded-2xl bg-gradient-to-tr from-[#7D39EB] to-[#9055FA] hover:from-[#6c2ee0] hover:to-[#7D39EB] text-white font-extrabold text-sm shadow-lg shadow-violet-500/25 active:scale-98 transition-all flex items-center justify-center gap-2"
        >
          <CreditCard className="w-4 h-4" />
          <span>
            {isProcessing ? 'Подключение к банку...' : `Оплатить ${finalAmount || 0} BYN`}
          </span>
        </button>

        <p className="text-[11px] text-center text-slate-400">
          🔒 Оплата защищена 3-D Secure. Эквайринг ЗАО «Альфа-Банк» (Минск).
        </p>
      </div>

      {/* ═══ 3. SECURITY & GUARANTEE ═══ */}
      <div className="p-4 rounded-3xl dark:bg-[#121118]/60 bg-white/60 border border-white/10 text-xs space-y-2 text-slate-500 dark:text-slate-400">
        <div className="flex items-center gap-2 font-bold dark:text-slate-300 text-slate-700">
          <ShieldCheck className="w-4 h-4 text-emerald-500" />
          <span>Гарантия возврата средств</span>
        </div>
        <p className="text-[11px] leading-relaxed">
          Деньги холдируются на вашем счёте и списываются в пользу репетитора только после успешного проведения урока. Вы можете отменить урок заранее с полным сохранением средств.
        </p>
      </div>
    </div>
  );
}
