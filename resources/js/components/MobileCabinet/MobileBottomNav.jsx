import React from 'react';
import { motion } from 'motion/react';
import { Calendar, MessageSquare, Wallet, User, Sparkles, BookOpen } from 'lucide-react';

export default function MobileBottomNav({ 
  activeTab, 
  onSelectTab, 
  unreadChatsCount = 0, 
  pendingHomeworkCount = 0,
  isStudent = false,
  isHidden = false 
}) {
  const tutorTabs = [
    { id: 'lessons', label: 'Уроки', icon: Calendar },
    { id: 'chat', label: 'Чат', icon: MessageSquare, badge: unreadChatsCount },
    { id: 'ai_agent', label: 'ИИ-Агент', icon: Sparkles },
    { id: 'finance', label: 'Финансы', icon: Wallet },
    { id: 'profile', label: 'Профиль', icon: User },
  ];

  const studentTabs = [
    { id: 'lessons', label: 'Уроки', icon: Calendar },
    { id: 'chat', label: 'Чат', icon: MessageSquare, badge: unreadChatsCount },
    { id: 'homework', label: 'Домашка', icon: BookOpen, badge: pendingHomeworkCount },
    { id: 'ai_tutor', label: 'ИИ-Тьютор', icon: Sparkles },
    { id: 'profile', label: 'Профиль', icon: User },
  ];

  const tabs = isStudent ? studentTabs : tutorTabs;

  return (
    <nav className={`fixed bottom-0 left-0 right-0 z-30 px-4 pb-6 pt-2 backdrop-blur-2xl border-t border-white/10 dark:bg-[#0A0A0C]/90 bg-white/90 transition-all duration-300 ${
      isHidden ? 'translate-y-full opacity-0 pointer-events-none' : 'translate-y-0 opacity-100'
    }`}>
      <div className="max-w-md mx-auto flex items-center justify-around relative">
        {tabs.map((tab) => {
          const Icon = tab.icon;
          const isActive = activeTab === tab.id;

          return (
            <button
              key={tab.id}
              type="button"
              onClick={() => onSelectTab(tab.id)}
              className="relative flex flex-col items-center justify-center py-1.5 px-3 rounded-2xl transition-colors duration-200 select-none group focus:outline-none"
            >
              {/* Floating Spring Pill indicator */}
              {isActive && (
                <motion.div
                  layoutId="mobile-nav-pill"
                  className="absolute inset-0 rounded-2xl dark:bg-white/10 bg-slate-900/10 shadow-sm"
                  transition={{ type: 'spring', stiffness: 500, damping: 35 }}
                />
              )}

              <div className="relative z-10 flex flex-col items-center gap-1">
                <div className="relative">
                  <Icon
                    className={`w-5 h-5 transition-transform duration-200 ${
                      isActive
                        ? 'dark:text-[#C6FF33] text-[#7D39EB] scale-110'
                        : 'dark:text-slate-400 text-slate-500 group-hover:text-slate-900 dark:group-hover:text-white'
                    }`}
                  />
                  {Boolean(tab.badge) && tab.badge > 0 && (
                    <span className="absolute -top-1 -right-2 px-1.5 py-0.2 min-w-[16px] h-4 rounded-full bg-rose-500 text-[10px] font-extrabold text-white flex items-center justify-center shadow-md">
                      {tab.badge}
                    </span>
                  )}
                </div>

                <span
                  className={`text-[11px] font-bold tracking-tight transition-colors duration-200 ${
                    isActive
                      ? 'dark:text-white text-slate-900'
                      : 'dark:text-slate-400 text-slate-500'
                  }`}
                >
                  {tab.label}
                </span>
              </div>
            </button>
          );
        })}
      </div>
    </nav>
  );
}
