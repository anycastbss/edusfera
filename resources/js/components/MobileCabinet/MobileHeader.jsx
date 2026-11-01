import React, { useState } from 'react';
import { motion, AnimatePresence } from 'motion/react';
import { Sun, Moon, Bell, Share2, Link as LinkIcon, Check } from 'lucide-react';
import UserAvatar from './UserAvatar';

export default function MobileHeader({ 
  user, 
  tutorProfile, 
  theme, 
  onToggleTheme,
  unreadNotificationsCount = 0,
  onOpenNotifications,
  bookingUrl,
  bookingShortUrl,
}) {
  const isDark = theme === 'dark';
  const [copiedLink, setCopiedLink] = useState(false);

  const handleShareBookingLink = async () => {
    const url = bookingUrl || (tutorProfile?.id ? `${window.location.origin}/tutors/${tutorProfile.id}` : window.location.origin);
    const title = `${user.name} — Репетитор на Edusfera`;
    const text = `Запись на индивидуальные занятия с репетитором ${user.name}:`;

    if (navigator.share) {
      try {
        await navigator.share({ title, text, url });
        return;
      } catch (err) {
        if (err.name !== 'AbortError') {
          console.error(err);
        }
      }
    }

    // Fallback: copy to clipboard
    try {
      await navigator.clipboard.writeText(url);
      setCopiedLink(true);
      setTimeout(() => setCopiedLink(false), 2200);
    } catch (e) {
      console.error(e);
    }
  };

  return (
    <header className="sticky top-0 z-40 w-full px-4 pt-3 pb-3 transition-colors duration-300 backdrop-blur-xl border-b border-white/5 dark:border-white/10 dark:bg-[#0A0A0C]/80 bg-[#F8FAFC]/85">
      <div className="flex items-center justify-between max-w-lg mx-auto">
        {/* User Profile Pill */}
        <div className="flex items-center gap-3">
          <div className="relative">
            <UserAvatar
              src={user.avatar_url}
              name={user.name}
              initials={user.initials}
              size="sm"
              rounded="rounded-2xl"
              innerRounded="rounded-[14px]"
            />
            <div className="absolute -bottom-0.5 -right-0.5 w-3 h-3 rounded-full bg-[#10B981] border-2 border-[#0A0A0C]" />
          </div>

          <div>
            <div className="flex items-center gap-1.5">
              <h2 className="text-sm font-extrabold tracking-tight dark:text-white text-slate-900 truncate max-w-[130px] sm:max-w-[180px]">
                {user.name}
              </h2>
              {user.role === 'tutor' && (
                <span className="text-[10px] font-mono px-1.5 py-0.5 rounded-full font-bold bg-[#C6FF33]/15 text-[#88ba04] dark:text-[#C6FF33] border border-[#C6FF33]/20">
                  ПРО
                </span>
              )}
            </div>
            <p className="text-[11px] font-medium text-slate-500 dark:text-slate-400 truncate max-w-[150px]">
              {user.role === 'tutor' ? (tutorProfile?.headline || 'Репетитор Edusfera') : 'Ученик'}
            </p>
          </div>
        </div>

        {/* Action Controls: Booking Link, Theme Switcher & Notifications */}
        <div className="flex items-center gap-1.5 sm:gap-2">
          {/* Quick Share / Booking Link Button */}
          {user.role === 'tutor' && (
            <motion.button
              type="button"
              onClick={handleShareBookingLink}
              whileTap={{ scale: 0.92 }}
              className={`h-9 px-2.5 rounded-xl flex items-center gap-1.5 text-xs font-bold transition shadow-sm border ${
                copiedLink
                  ? 'bg-emerald-500 text-white border-emerald-500'
                  : 'dark:bg-[#7D39EB]/15 dark:border-[#7D39EB]/30 dark:text-[#C6FF33] bg-purple-50 border-purple-200 text-[#7D39EB]'
              }`}
              title="Поделиться ссылкой для записи"
            >
              {copiedLink ? (
                <>
                  <Check className="w-3.5 h-3.5" />
                  <span className="text-[11px] hidden sm:inline">Скопировано!</span>
                </>
              ) : (
                <>
                  <Share2 className="w-3.5 h-3.5" />
                  <span className="text-[11px] hidden sm:inline">Ссылка</span>
                </>
              )}
            </motion.button>
          )}

          {/* Theme Toggle Button */}
          <motion.button
            type="button"
            onClick={onToggleTheme}
            whileTap={{ scale: 0.90 }}
            className="w-9 h-9 rounded-xl flex items-center justify-center transition-all duration-200 border dark:bg-white/5 dark:border-white/10 dark:text-amber-300 bg-slate-100 border-slate-200 text-slate-700 shadow-sm"
            title={isDark ? 'Переключить на светлую тему' : 'Переключить на тёмную тему'}
          >
            {isDark ? (
              <Sun className="w-4 h-4 text-amber-300 drop-shadow-[0_0_8px_rgba(252,211,77,0.5)]" />
            ) : (
              <Moon className="w-4 h-4 text-slate-700" />
            )}
          </motion.button>

          {/* Quick Notification Bell */}
          <div className="relative">
            <motion.button
              type="button"
              onClick={onOpenNotifications}
              whileTap={{ scale: 0.90 }}
              className="w-9 h-9 rounded-xl flex items-center justify-center transition-all duration-200 border dark:bg-white/5 dark:border-white/10 dark:text-white/80 bg-slate-100 border-slate-200 text-slate-700 shadow-sm relative"
              title="Открыть уведомления"
            >
              <Bell className="w-4 h-4" />
              {unreadNotificationsCount > 0 && (
                <span className="absolute -top-1 -right-1 min-w-[18px] h-[18px] px-1 rounded-full bg-rose-500 text-white text-[10px] font-extrabold flex items-center justify-center border-2 border-[#0A0A0C]">
                  {unreadNotificationsCount}
                </span>
              )}
            </motion.button>
          </div>
        </div>
      </div>
    </header>
  );
}
