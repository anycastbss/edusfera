import React, { useState } from 'react';
import { 
  Calendar, MessageSquare, Sparkles, Wallet, User, BookOpen, 
  CreditCard, Plus, Copy, Check, ExternalLink, Moon, Sun, 
  LogOut, Shield, ChevronRight, CheckCircle2
} from 'lucide-react';
import UserAvatar from './UserAvatar';

export default function DesktopSidebar({
  user,
  tutorProfile,
  subscription,
  balance,
  stats,
  activeTab,
  onSelectTab,
  unreadChatsCount = 0,
  pendingHomeworkCount = 0,
  theme,
  onToggleTheme,
  bookingUrl = '',
  bookingShortUrl = '',
  onOpenCreateLesson,
}) {
  const isTutor = user?.is_tutor ?? (user?.role === 'tutor');
  const isStudent = user?.is_student || user?.is_parent || (user?.role === 'student' || user?.role === 'parent');
  const [copiedLink, setCopiedLink] = useState(false);

  const displayBookingUrl = bookingShortUrl || bookingUrl;

  const handleCopyBooking = async () => {
    if (!displayBookingUrl) return;
    try {
      await navigator.clipboard.writeText(displayBookingUrl);
      setCopiedLink(true);
      setTimeout(() => setCopiedLink(false), 2000);
    } catch (e) {
      console.error(e);
    }
  };

  const navItems = isTutor ? [
    { id: 'lessons', label: 'Расписание уроков', icon: Calendar },
    { id: 'chat', label: 'Сообщения и чаты', icon: MessageSquare, badge: unreadChatsCount },
    { id: 'ai_agent', label: 'ИИ-Методист', icon: Sparkles, accent: true },
    { id: 'finance', label: 'Финансы и налоги', icon: Wallet },
    { id: 'profile', label: 'Мой профиль', icon: User },
  ] : [
    { id: 'lessons', label: 'Мои уроки', icon: Calendar },
    { id: 'chat', label: 'Сообщения', icon: MessageSquare, badge: unreadChatsCount },
    { id: 'homework', label: 'Домашние задания', icon: BookOpen, badge: pendingHomeworkCount },
    { id: 'ai_tutor', label: 'ИИ-Репетитор', icon: Sparkles, accent: true },
    { id: 'profile', label: 'Мой профиль', icon: User },
  ];

  const handleSwitchToTutor = async () => {
    try {
      const res = await axios.post('/api/user/switch-role', { role: 'tutor' });
      if (res.data?.redirect) {
        window.location.href = res.data.redirect;
      }
    } catch (err) {
      console.error('Failed to switch role:', err);
    }
  };

  return (
    <aside className="fixed inset-y-0 left-0 w-64 xl:w-72 z-30 flex flex-col justify-between border-r border-slate-200 dark:border-white/10 bg-white dark:bg-[#0E0E14] transition-colors duration-200 select-none shadow-xl shadow-black/5">
      {/* ─── Top Section: Brand & User ─── */}
      <div className="p-5 pb-3">
        {/* Brand Lockup */}
        <div className="flex items-center justify-between mb-6">
          <a href="/cabinet" className="flex items-center gap-2.5 group">
            <div className="w-10 h-10 rounded-xl bg-[#121216] border border-white/15 flex items-center justify-center shadow-lg group-hover:scale-105 transition-transform">
              <svg width="22" height="22" viewBox="0 0 64 64" fill="none">
                <path d="M32 10L54 32L32 54L10 32L32 10Z" stroke="#C6FF33" stroke-width="6" stroke-linejoin="round" />
                <path d="M32 22L42 32L32 42L22 32L32 22Z" fill="#C6FF33" />
              </svg>
            </div>
            <div>
              <span className="text-lg font-black tracking-wider text-slate-900 dark:text-white font-rimma block leading-none">
                EDUSFERA
              </span>
              <span className="text-[10px] font-mono tracking-widest text-[#7D39EB] dark:text-[#C6FF33] uppercase font-bold">
                {isTutor ? 'Кабинет репетитора' : 'Кабинет ученика'}
              </span>
            </div>
          </a>

          {subscription?.is_active && (
            <span className="px-2 py-0.5 rounded-full text-[10px] font-extrabold uppercase tracking-wider bg-[#C6FF33]/15 text-[#73a808] dark:text-[#C6FF33] border border-[#C6FF33]/30">
              PRO
            </span>
          )}
        </div>

        {/* User Profile Badge (Clickable) */}
        <button
          type="button"
          onClick={() => onSelectTab('profile')}
          className={`w-full text-left p-3 rounded-2xl border transition-all cursor-pointer group flex items-center gap-3 ${
            activeTab === 'profile'
              ? 'bg-[#7D39EB]/15 border-[#7D39EB]/40 shadow-sm ring-1 ring-[#7D39EB]/30'
              : 'bg-slate-50 dark:bg-white/[0.04] hover:bg-slate-100 dark:hover:bg-white/[0.08] border-slate-200/80 dark:border-white/10'
          }`}
          title="Открыть Мой профиль"
        >
          <div className="relative">
            <UserAvatar
              src={user?.avatar_url}
              name={user?.name}
              initials={user?.initials}
              size="md"
            />
            <span className="absolute bottom-0 right-0 w-2.5 h-2.5 rounded-full bg-emerald-500 ring-2 ring-white dark:ring-[#0E0E14]" />
          </div>
          <div className="min-w-0 flex-1">
            <div className="flex items-center justify-between">
              <h4 className="text-xs font-bold text-slate-900 dark:text-white truncate group-hover:text-[#7D39EB] dark:group-hover:text-[#C6FF33] transition-colors">
                {user?.name || 'Пользователь'}
              </h4>
              <ChevronRight className="w-3.5 h-3.5 text-slate-400 group-hover:translate-x-0.5 group-hover:text-slate-200 transition-all" />
            </div>
            <p className="text-[11px] text-slate-500 dark:text-slate-400 truncate">
              {user?.email || (isTutor ? 'Преподаватель' : 'Ученик')}
            </p>
          </div>
        </button>

        {/* Tutor Quick Action: + Запланировать урок */}
        {isTutor && onOpenCreateLesson && (
          <button
            type="button"
            onClick={onOpenCreateLesson}
            className="w-full mt-3 py-2.5 px-3 rounded-xl bg-[#C6FF33] hover:bg-[#b8f026] text-black font-extrabold text-xs flex items-center justify-center gap-2 shadow-md shadow-[#C6FF33]/20 active:opacity-90 transition"
          >
            <Plus className="w-4 h-4 stroke-[2.5]" />
            <span>Запланировать урок</span>
          </button>
        )}
      </div>

      {/* ─── Middle Section: Navigation & Balance ─── */}
      <div className="flex-1 px-4 py-2 overflow-y-auto space-y-1">
        <div className="text-[10px] font-bold text-slate-400 dark:text-white/40 uppercase tracking-widest px-3 mb-2">
          Навигация
        </div>

        {navItems.map((item) => {
          const Icon = item.icon;
          const isActive = activeTab === item.id;

          return (
            <button
              key={item.id}
              type="button"
              onClick={() => onSelectTab(item.id)}
              className={`w-full flex items-center justify-between px-3 py-2.5 rounded-xl text-xs font-bold transition-all ${
                isActive
                  ? 'bg-[#7D39EB] text-white shadow-lg shadow-purple-500/25'
                  : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-white/[0.06] hover:text-slate-900 dark:hover:text-white'
              }`}
            >
              <div className="flex items-center gap-3 min-w-0">
                <Icon className={`w-4 h-4 shrink-0 ${isActive ? 'text-white' : item.accent ? 'text-[#7D39EB] dark:text-[#C6FF33]' : 'text-slate-400 dark:text-slate-400'}`} />
                <span className="truncate">{item.label}</span>
              </div>

              {Boolean(item.badge && item.badge > 0) && (
                <span className={`px-2 py-0.5 rounded-full text-[10px] font-black shrink-0 ${
                  isActive
                    ? 'bg-white text-[#7D39EB]'
                    : 'bg-[#7D39EB] text-white'
                }`}>
                  {item.badge}
                </span>
              )}
            </button>
          );
        })}

        {/* Quick Month Earnings (TUTORS ONLY) */}
        {isTutor && (
          <div className="pt-4">
            <div className="p-3.5 rounded-2xl bg-gradient-to-br from-purple-500/10 via-purple-500/5 to-transparent border border-purple-500/20">
              <div className="flex items-center justify-between mb-1">
                <span className="text-[10px] uppercase font-bold tracking-wider text-slate-500 dark:text-slate-400">
                  Примерный доход за этот месяц
                </span>
                <Wallet className="w-3.5 h-3.5 text-[#7D39EB] dark:text-[#C6FF33]" />
              </div>
              <div className="text-base font-black text-slate-900 dark:text-white">
                ~{Number(stats?.this_month_earnings ?? (balance?.available ?? 0)).toFixed(2)}{' '}
                <span className="text-xs font-mono font-bold text-slate-400">BYN</span>
              </div>
              <button
                type="button"
                onClick={() => onSelectTab('finance')}
                className="mt-2 text-[11px] font-bold text-[#7D39EB] dark:text-[#C6FF33] hover:underline flex items-center gap-1 cursor-pointer"
              >
                <span>Статистика и чеки НПД</span>
                <ChevronRight className="w-3 h-3" />
              </button>
            </div>
          </div>
        )}

        {/* Switch to Tutor Card (STUDENTS ONLY) */}
        {!isTutor && (
          <div className="pt-4">
            <div className="p-3.5 rounded-2xl bg-gradient-to-br from-lime-500/10 via-lime-500/5 to-transparent border border-lime-500/20">
              <div className="flex items-center justify-between mb-1">
                <span className="text-[10px] uppercase font-bold tracking-wider text-[#7D39EB] dark:text-[#C6FF33]">
                  Вы преподаватель?
                </span>
              </div>
              <p className="text-[11px] text-slate-600 dark:text-slate-300 mb-2 leading-relaxed">
                Переключитесь на кабинет репетитора в один клик
              </p>
              <button
                type="button"
                onClick={handleSwitchToTutor}
                className="w-full py-2 px-3 rounded-xl bg-[#C6FF33] text-black text-xs font-bold flex items-center justify-center gap-1.5 hover:bg-[#d4ff59] transition-all cursor-pointer shadow-sm"
              >
                <span>Стать репетитором</span>
                <ChevronRight className="w-3.5 h-3.5" />
              </button>
            </div>
          </div>
        )}

        {/* Tutor Booking Link Card */}
        {isTutor && displayBookingUrl && (
          <div className="pt-2">
            <div className="p-3 rounded-xl bg-slate-50 dark:bg-white/[0.03] border border-slate-200/80 dark:border-white/10">
              <span className="text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-400 block mb-1">
                Ссылка для учеников
              </span>
              <div className="flex items-center gap-1.5">
                <input
                  type="text"
                  readOnly
                  value={displayBookingUrl}
                  className="w-full text-[11px] font-mono px-2 py-1 rounded-lg bg-white dark:bg-white/5 border border-slate-200 dark:border-white/10 text-slate-600 dark:text-slate-300 truncate focus:outline-none"
                />
                <button
                  type="button"
                  onClick={handleCopyBooking}
                  className="p-1.5 rounded-lg bg-slate-200 dark:bg-white/10 hover:bg-[#C6FF33] hover:text-black text-slate-700 dark:text-white transition shrink-0"
                  title="Скопировать ссылку"
                >
                  {copiedLink ? <Check className="w-3.5 h-3.5 text-emerald-500" /> : <Copy className="w-3.5 h-3.5" />}
                </button>
              </div>
            </div>
          </div>
        )}
      </div>

      {/* ─── Bottom Section: Settings & Switchers ─── */}
      <div className="p-4 border-t border-slate-200 dark:border-white/10 space-y-1">
        {/* Theme Toggle */}
        <button
          type="button"
          onClick={onToggleTheme}
          className="w-full flex items-center justify-between px-3 py-2 rounded-xl text-xs font-semibold text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-white/5 transition"
        >
          <div className="flex items-center gap-2.5">
            {theme === 'dark' ? <Moon className="w-4 h-4 text-[#C6FF33]" /> : <Sun className="w-4 h-4 text-amber-500" />}
            <span>{theme === 'dark' ? 'Темная тема' : 'Светлая тема'}</span>
          </div>
          <span className="text-[10px] font-mono text-slate-400 uppercase">
            {theme === 'dark' ? 'Dark' : 'Light'}
          </span>
        </button>

        {/* Switch to Classic Filament Mode (/admin) - strictly for Tech Admin */}
        {Boolean(user?.is_admin || user?.role === 'admin') && (
          <a
            href="/admin?filament=1"
            className="w-full flex items-center justify-between px-3 py-2 rounded-xl text-xs font-semibold text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-white/5 transition group"
            title="Панель администратора Filament"
          >
            <div className="flex items-center gap-2.5">
              <Shield className="w-4 h-4 text-slate-400 group-hover:text-[#7D39EB] transition-colors" />
              <span>Админка Filament</span>
            </div>
            <ExternalLink className="w-3 h-3 text-slate-400 group-hover:translate-x-0.5 transition-transform" />
          </a>
        )}

        {/* Logout */}
        <form method="POST" action="/admin/logout" className="w-full">
          <input type="hidden" name="_token" value={window.EDUSFERA_CSRF_TOKEN || ''} />
          <button
            type="submit"
            className="w-full flex items-center gap-2.5 px-3 py-2 rounded-xl text-xs font-semibold text-rose-500/90 hover:text-rose-600 hover:bg-rose-500/10 transition"
          >
            <LogOut className="w-4 h-4" />
            <span>Выйти из аккаунта</span>
          </button>
        </form>
      </div>
    </aside>
  );
}
