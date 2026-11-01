import React, { useState } from 'react';
import { motion, AnimatePresence } from 'motion/react';
import { 
  Bell, X, Check, CheckCheck, Clock, Calendar, 
  Wallet, Sparkles, MessageSquare, Info, ChevronRight, Loader2 
} from 'lucide-react';
import axios from 'axios';
import { executeResilientRequest } from '../../utils/offlineQueue';

export default function NotificationsSheet({ 
  isOpen, 
  onClose, 
  initialNotifications = [],
  unreadCount: initialUnreadCount = 0,
  onNotificationsUpdated 
}) {
  const [notifications, setNotifications] = useState(initialNotifications);
  const [unreadCount, setUnreadCount] = useState(initialUnreadCount);
  const [isMarkingAll, setIsMarkingAll] = useState(false);

  // Sync when prop updates
  React.useEffect(() => {
    setNotifications(initialNotifications);
    setUnreadCount(initialUnreadCount);
  }, [initialNotifications, initialUnreadCount]);

  const handleMarkAllRead = async () => {
    if (unreadCount === 0 || isMarkingAll) return;
    setIsMarkingAll(true);
    // Optimistic UI update
    setNotifications(prev => prev.map(n => ({ ...n, read: true })));
    setUnreadCount(0);
    if (onNotificationsUpdated) {
      onNotificationsUpdated(0);
    }
    try {
      await executeResilientRequest('/api/mobile/notifications/read-all', 'post');
    } catch (e) {
      console.warn('[Offline Notifications] Error marking all read:', e);
    } finally {
      setIsMarkingAll(false);
    }
  };

  const handleMarkSingleRead = async (id) => {
    // Optimistic UI update
    setNotifications(prev => prev.map(n => n.id === id ? { ...n, read: true } : n));
    const nextUnread = Math.max(0, unreadCount - 1);
    setUnreadCount(nextUnread);
    if (onNotificationsUpdated) {
      onNotificationsUpdated(nextUnread);
    }
    try {
      await executeResilientRequest(`/api/mobile/notifications/${id}/read`, 'post');
    } catch (e) {
      console.warn('[Offline Notifications] Error marking single read:', e);
    }
  };

  const getIcon = (type) => {
    switch (type) {
      case 'lesson':
        return <Calendar className="w-4 h-4 text-emerald-400" />;
      case 'finance':
        return <Wallet className="w-4 h-4 text-amber-400" />;
      case 'chat':
        return <MessageSquare className="w-4 h-4 text-sky-400" />;
      case 'subscription':
        return <Sparkles className="w-4 h-4 text-[#C6FF33]" />;
      default:
        return <Bell className="w-4 h-4 text-[#7D39EB]" />;
    }
  };

  if (!isOpen) return null;

  return (
    <div className="fixed inset-0 z-[70] flex items-end sm:items-center justify-center p-0 sm:p-4">
      <motion.div
        initial={{ opacity: 0 }}
        animate={{ opacity: 1 }}
        exit={{ opacity: 0 }}
        onClick={onClose}
        className="fixed inset-0 bg-black/60 backdrop-blur-sm"
      />

      <motion.div
        initial={{ y: '100%' }}
        animate={{ y: 0 }}
        exit={{ y: '100%' }}
        transition={{ type: 'spring', damping: 30, stiffness: 350 }}
        className="relative w-full max-w-lg rounded-t-[32px] sm:rounded-[28px] p-6 pb-10 sm:pb-6 z-10 border-t sm:border border-white/10 dark:bg-[#121214] bg-white shadow-2xl max-h-[85vh] sm:max-h-[80vh] flex flex-col"
      >
        {/* Drag Pill */}
        <div className="w-12 h-1.5 rounded-full bg-slate-300 dark:bg-white/20 mx-auto mb-3 shrink-0 sm:hidden" />

        {/* Sheet Header */}
        <div className="flex items-center justify-between pb-4 border-b border-slate-100 dark:border-white/10 shrink-0">
          <div className="flex items-center gap-2.5">
            <div className="w-9 h-9 rounded-2xl bg-[#7D39EB]/15 text-[#7D39EB] dark:text-[#C6FF33] flex items-center justify-center relative">
              <Bell className="w-4 h-4" />
              {unreadCount > 0 && (
                <span className="absolute -top-1 -right-1 min-w-[18px] h-[18px] px-1 rounded-full bg-rose-500 text-white text-[10px] font-extrabold flex items-center justify-center">
                  {unreadCount}
                </span>
              )}
            </div>
            <div>
              <h4 className="text-base font-extrabold dark:text-white text-slate-900">
                Уведомления
              </h4>
              <p className="text-[11px] text-slate-400">
                {unreadCount > 0 ? `${unreadCount} новых сообщений` : 'Все прочитаны'}
              </p>
            </div>
          </div>

          <div className="flex items-center gap-2">
            {unreadCount > 0 && (
              <button
                type="button"
                onClick={handleMarkAllRead}
                disabled={isMarkingAll}
                className="px-2.5 py-1.5 rounded-xl bg-slate-100 dark:bg-white/5 text-slate-600 dark:text-slate-300 hover:text-black dark:hover:text-white text-xs font-semibold flex items-center gap-1 active:scale-95 transition"
              >
                {isMarkingAll ? (
                  <Loader2 className="w-3 h-3 animate-spin" />
                ) : (
                  <CheckCheck className="w-3.5 h-3.5" />
                )}
                <span>Прочитать все</span>
              </button>
            )}

            <button
              type="button"
              onClick={onClose}
              className="w-8 h-8 rounded-full bg-slate-100 dark:bg-white/10 flex items-center justify-center text-slate-600 dark:text-white/70"
            >
              <X className="w-4 h-4" />
            </button>
          </div>
        </div>

        {/* Notification List */}
        <div className="flex-1 overflow-y-auto divide-y divide-slate-100 dark:divide-white/5 py-2">
          {notifications.length === 0 ? (
            <div className="py-16 text-center space-y-2">
              <div className="w-12 h-12 rounded-2xl bg-purple-500/10 text-[#7D39EB] dark:text-[#C6FF33] flex items-center justify-center mx-auto">
                <Bell className="w-6 h-6 opacity-60" />
              </div>
              <h5 className="text-sm font-bold dark:text-white text-slate-800">
                Пока нет уведомлений
              </h5>
              <p className="text-xs text-slate-400 max-w-xs mx-auto">
                Здесь будут появляться записи на занятия, подтверждения оплат и важные системные напоминания
              </p>
            </div>
          ) : (
            notifications.map((item) => (
              <div
                key={item.id}
                onClick={() => !item.read && handleMarkSingleRead(item.id)}
                className={`py-3.5 px-2 rounded-2xl transition cursor-pointer flex items-start gap-3 ${
                  item.read 
                    ? 'opacity-70 hover:opacity-100' 
                    : 'dark:bg-white/[0.03] bg-purple-50/50'
                }`}
              >
                <div className="w-9 h-9 rounded-xl bg-slate-100 dark:bg-white/5 flex items-center justify-center shrink-0 mt-0.5">
                  {getIcon(item.type)}
                </div>

                <div className="flex-1 min-w-0">
                  <div className="flex items-center justify-between gap-2">
                    <h5 className="text-xs font-bold dark:text-white text-slate-900 truncate">
                      {item.title}
                    </h5>
                    <span className="text-[10px] text-slate-400 whitespace-nowrap shrink-0">
                      {item.created_at}
                    </span>
                  </div>

                  <p className="text-xs text-slate-600 dark:text-slate-300 mt-0.5 leading-relaxed break-words">
                    {item.message}
                  </p>
                </div>

                {!item.read && (
                  <span className="w-2 h-2 rounded-full bg-[#7D39EB] shrink-0 mt-2" />
                )}
              </div>
            ))
          )}
        </div>
      </motion.div>
    </div>
  );
}
