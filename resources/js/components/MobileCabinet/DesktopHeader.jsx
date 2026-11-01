import React, { useState, useMemo } from 'react';
import { 
  Bell, Plus, Copy, Check, Calendar, Sparkles, 
  Share2, WifiOff, CheckCircle2 
} from 'lucide-react';

export default function DesktopHeader({
  activeTab,
  user,
  tutorProfile,
  bookingUrl = '',
  bookingShortUrl = '',
  unreadNotificationsCount = 0,
  onOpenNotifications,
  onOpenCreateLesson,
  isOnline = true,
}) {
  const isTutor = user?.is_tutor ?? (user?.role === 'tutor');
  const [copied, setCopied] = useState(false);

  const displayBookingUrl = bookingShortUrl || bookingUrl;

  const tabTitles = {
    lessons: isTutor ? 'Расписание и занятия' : 'Мои уроки и график',
    chat: 'Сообщения и чаты',
    ai_agent: 'ИИ-Методист Edusfera Copilot',
    ai_tutor: 'ИИ-Репетитор для подготовки',
    finance: 'Финансы, статистика и налоги НПД',
    wallet: 'Баланс счета и история оплат',
    homework: 'Домашние задания и материалы',
    profile: 'Настройки профиля и безопасности',
  };

  const currentDateFormatted = useMemo(() => {
    try {
      const now = new Date();
      return now.toLocaleDateString('ru-RU', {
        weekday: 'long',
        day: 'numeric',
        month: 'long',
        year: 'numeric',
      });
    } catch (e) {
      return '';
    }
  }, []);

  const handleCopyLink = async () => {
    if (!displayBookingUrl) return;
    try {
      await navigator.clipboard.writeText(displayBookingUrl);
      setCopied(true);
      setTimeout(() => setCopied(false), 2000);
    } catch (e) {
      console.error(e);
    }
  };

  return (
    <header className="fixed top-0 left-64 xl:left-72 right-0 h-16 z-20 px-8 flex items-center justify-between border-b border-slate-200 dark:border-white/10 bg-white/80 dark:bg-[#0A0A0C]/80 backdrop-blur-xl transition-colors duration-200">
      {/* ─── Left: Tab Title & Date ─── */}
      <div className="flex items-center gap-4">
        <div>
          <h1 className="text-base font-extrabold text-slate-900 dark:text-white tracking-tight flex items-center gap-2">
            <span>{tabTitles[activeTab] || 'Личный кабинет'}</span>
            {activeTab === 'ai_agent' || activeTab === 'ai_tutor' ? (
              <span className="px-2 py-0.5 rounded-full text-[10px] font-extrabold uppercase tracking-wider bg-purple-500/15 text-[#7D39EB] dark:text-[#C6FF33] border border-purple-500/30 flex items-center gap-1">
                <Sparkles className="w-3 h-3" />
                <span>AI</span>
              </span>
            ) : null}
          </h1>
          <p className="text-[11px] text-slate-500 dark:text-slate-400 capitalize">
            {currentDateFormatted}
          </p>
        </div>
      </div>

      {/* ─── Right: Actions & Notifications ─── */}
      <div className="flex items-center gap-3">
        {/* Offline indicator */}
        {!isOnline && (
          <div className="flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-amber-500/15 border border-amber-500/30 text-amber-500 text-xs font-bold animate-pulse">
            <WifiOff className="w-3.5 h-3.5" />
            <span>Офлайн-режим</span>
          </div>
        )}

        {/* Tutor Booking Quick Copy Pill */}
        {isTutor && displayBookingUrl && (
          <div className="hidden sm:flex items-center gap-2 px-3 py-1.5 rounded-xl bg-slate-100 dark:bg-white/5 border border-slate-200 dark:border-white/10">
            <span className="text-[11px] font-medium text-slate-500 dark:text-slate-400">
              Ссылка на запись:
            </span>
            <button
              type="button"
              onClick={handleCopyLink}
              className="flex items-center gap-1.5 text-xs font-bold text-[#7D39EB] dark:text-[#C6FF33] hover:opacity-80 transition"
              title="Скопировать прямую ссылку на запись для учеников"
            >
              {copied ? (
                <>
                  <Check className="w-3.5 h-3.5 text-emerald-500" />
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
        )}

        {/* Tutor Quick Button: + Запланировать урок */}
        {isTutor && onOpenCreateLesson && (
          <button
            type="button"
            onClick={onOpenCreateLesson}
            className="py-2 px-4 rounded-xl bg-[#C6FF33] hover:bg-[#b8f026] text-black font-extrabold text-xs flex items-center gap-1.5 shadow-md shadow-[#C6FF33]/20 active:scale-95 transition"
          >
            <Plus className="w-4 h-4 stroke-[2.5]" />
            <span>Урок</span>
          </button>
        )}

        {/* Notifications Bell */}
        <button
          type="button"
          onClick={onOpenNotifications}
          className="relative w-10 h-10 rounded-xl bg-slate-100 dark:bg-white/5 hover:bg-slate-200 dark:hover:bg-white/10 border border-slate-200 dark:border-white/10 flex items-center justify-center text-slate-600 dark:text-slate-300 transition"
          title="Уведомления"
        >
          <Bell className="w-4 h-4" />
          {unreadNotificationsCount > 0 && (
            <span className="absolute -top-1 -right-1 min-w-[18px] h-[18px] px-1 rounded-full bg-rose-500 text-white text-[10px] font-black flex items-center justify-center ring-2 ring-white dark:ring-[#0A0A0C]">
              {unreadNotificationsCount > 9 ? '9+' : unreadNotificationsCount}
            </span>
          )}
        </button>
      </div>
    </header>
  );
}
