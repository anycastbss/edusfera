import React, { useState, useEffect } from 'react';
import { motion, AnimatePresence } from 'motion/react';

export default function OfflineStatusBar({ onSync, isSyncing = false }) {
  const [isOnline, setIsOnline] = useState(() => (typeof navigator !== 'undefined' ? navigator.onLine : true));
  const [networkQuality, setNetworkQuality] = useState('good'); // 'good' | 'weak' | 'offline'
  const [showReconnectedToast, setShowReconnectedToast] = useState(false);

  useEffect(() => {
    const checkNetwork = () => {
      if (typeof navigator === 'undefined') return;

      if (!navigator.onLine) {
        setIsOnline(false);
        setNetworkQuality('offline');
        return;
      }

      setIsOnline(true);

      const conn = navigator.connection || navigator.mozConnection || navigator.webkitConnection;
      if (conn) {
        const isWeak = conn.effectiveType === '2g' || conn.effectiveType === 'slow-2g' || conn.saveData || (conn.rtt && conn.rtt > 1200);
        setNetworkQuality(isWeak ? 'weak' : 'good');
      } else {
        setNetworkQuality('good');
      }
    };

    const handleOnline = () => {
      setIsOnline(true);
      setShowReconnectedToast(true);
      checkNetwork();
      if (onSync) onSync();
      const timer = setTimeout(() => {
        setShowReconnectedToast(false);
      }, 4000);
      return () => clearTimeout(timer);
    };

    const handleOffline = () => {
      setIsOnline(false);
      setNetworkQuality('offline');
      setShowReconnectedToast(false);
    };

    window.addEventListener('online', handleOnline);
    window.addEventListener('offline', handleOffline);

    const conn = navigator.connection || navigator.mozConnection || navigator.webkitConnection;
    if (conn && conn.addEventListener) {
      conn.addEventListener('change', checkNetwork);
    }

    checkNetwork();

    return () => {
      window.removeEventListener('online', handleOnline);
      window.removeEventListener('offline', handleOffline);
      if (conn && conn.removeEventListener) {
        conn.removeEventListener('change', checkNetwork);
      }
    };
  }, [onSync]);

  return (
    <div className="sticky top-0 z-30 px-3 pt-2">
      <AnimatePresence>
        {!isOnline && (
          <motion.div
            initial={{ opacity: 0, y: -12, scale: 0.98 }}
            animate={{ opacity: 1, y: 0, scale: 1 }}
            exit={{ opacity: 0, y: -12, scale: 0.98 }}
            transition={{ duration: 0.2 }}
            className="flex items-center justify-between gap-2.5 px-3.5 py-2 rounded-xl bg-amber-500/15 border border-amber-500/30 backdrop-blur-md text-amber-200 text-xs shadow-lg shadow-amber-950/20"
          >
            <div className="flex items-center gap-2 min-w-0">
              <span className="flex h-2 w-2 rounded-full bg-amber-400 animate-ping" />
              <div className="truncate">
                <span className="font-semibold text-amber-300">Офлайн-режим</span>
                <span className="hidden sm:inline text-amber-200/80"> — данные сохранены в телефоне</span>
              </div>
            </div>
            <button
              type="button"
              onClick={onSync}
              disabled={isSyncing}
              className="shrink-0 flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-amber-500/20 hover:bg-amber-500/30 text-amber-200 text-[11px] font-medium transition-all active:scale-95"
            >
              <svg
                className={`w-3 h-3 ${isSyncing ? 'animate-spin' : ''}`}
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
              >
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
              </svg>
              <span>{isSyncing ? 'Проверка...' : 'Проверить'}</span>
            </button>
          </motion.div>
        )}

        {isOnline && networkQuality === 'weak' && !showReconnectedToast && (
          <motion.div
            initial={{ opacity: 0, y: -12 }}
            animate={{ opacity: 1, y: 0 }}
            exit={{ opacity: 0, y: -12 }}
            className="flex items-center justify-between gap-2 px-3.5 py-1.5 rounded-xl bg-sky-500/15 border border-sky-500/30 backdrop-blur-md text-sky-200 text-xs shadow-sm"
          >
            <div className="flex items-center gap-2">
              <span className="text-sm">📶</span>
              <span className="font-medium text-sky-300">Слабый интернет</span>
              <span className="text-sky-200/70 text-[11px]">Экономия трафика включена</span>
            </div>
            <button
              type="button"
              onClick={onSync}
              className="text-[11px] font-medium text-sky-300 underline underline-offset-2 active:opacity-75"
            >
              Обновить
            </button>
          </motion.div>
        )}

        {isOnline && showReconnectedToast && (
          <motion.div
            initial={{ opacity: 0, y: -12, scale: 0.98 }}
            animate={{ opacity: 1, y: 0, scale: 1 }}
            exit={{ opacity: 0, y: -12, scale: 0.98 }}
            className="flex items-center gap-2 px-3.5 py-2 rounded-xl bg-emerald-500/15 border border-emerald-500/30 backdrop-blur-md text-emerald-200 text-xs shadow-lg shadow-emerald-950/20"
          >
            <svg className="w-4 h-4 text-emerald-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 13l4 4L19 7" />
            </svg>
            <span className="font-semibold text-emerald-300">Соединение восстановлено</span>
            <span className="text-emerald-200/80 text-[11px] truncate">Данные синхронизированы с сервером</span>
          </motion.div>
        )}
      </AnimatePresence>
    </div>
  );
}
