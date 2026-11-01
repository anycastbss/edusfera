import React from 'react';
import { History, X, Trash2, MessageSquare, Plus, Clock, ChevronRight } from 'lucide-react';

export default function HistoryDrawer({
    isOpen,
    onClose,
    sessions = [],
    activeSessionId,
    onSelectSession,
    onNewChat,
    onClearAll,
}) {
    if (!isOpen) return null;

    return (
        <div className="fixed inset-0 z-50 flex justify-end">
            {/* Backdrop */}
            <div
                className="fixed inset-0 bg-black/40 backdrop-blur-xs transition-opacity animate-fade-in"
                onClick={onClose}
            />

            {/* Drawer container */}
            <div className="relative w-full max-w-sm bg-white dark:bg-slate-900 h-full shadow-2xl z-10 flex flex-col border-l border-slate-200 dark:border-slate-800">
                {/* Header */}
                <div className="p-5 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
                    <div className="flex items-center gap-2.5">
                        <div className="w-8 h-8 rounded-xl bg-violet-100 dark:bg-violet-950/60 text-violet-600 dark:text-violet-400 flex items-center justify-center">
                            <History className="w-4 h-4" />
                        </div>
                        <div>
                            <h3 className="font-bold text-sm text-slate-900 dark:text-white">История диалогов</h3>
                            <p className="text-[11px] text-slate-500 dark:text-slate-400">Сохраненные сессии и конспекты</p>
                        </div>
                    </div>
                    <button
                        type="button"
                        onClick={onClose}
                        className="p-1.5 rounded-lg text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
                    >
                        <X className="w-4 h-4" />
                    </button>
                </div>

                {/* Action: New Chat */}
                <div className="p-4 border-b border-slate-100 dark:border-slate-800">
                    <button
                        type="button"
                        onClick={() => {
                            onNewChat();
                            onClose();
                        }}
                        className="w-full flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-violet-600 hover:bg-violet-700 text-white font-semibold text-xs shadow-sm transition-all active:scale-[0.98]"
                    >
                        <Plus className="w-4 h-4" />
                        <span>Новый диалог с методистом</span>
                    </button>
                </div>

                {/* Sessions List */}
                <div className="flex-1 overflow-y-auto p-4 space-y-2">
                    {sessions.length === 0 ? (
                        <div className="text-center py-12 px-4">
                            <Clock className="w-8 h-8 text-slate-300 dark:text-slate-700 mx-auto mb-2" />
                            <p className="text-xs text-slate-500 dark:text-slate-400 font-medium">История пуста</p>
                            <p className="text-[11px] text-slate-400 dark:text-slate-500 mt-1">
                                Ваши вопросы и сгенерированные конспекты будут сохраняться здесь
                            </p>
                        </div>
                    ) : (
                        sessions.map((session) => {
                            const isActive = session.id === activeSessionId;
                            return (
                                <button
                                    key={session.id}
                                    type="button"
                                    onClick={() => {
                                        onSelectSession(session);
                                        onClose();
                                    }}
                                    className={`w-full text-left p-3 rounded-2xl border transition-all flex items-start justify-between gap-2 group ${
                                        isActive
                                            ? 'bg-violet-50/80 dark:bg-violet-950/40 border-violet-200 dark:border-violet-800 text-violet-900 dark:text-violet-200'
                                            : 'bg-white dark:bg-slate-800/60 border-slate-100 dark:border-slate-700/60 hover:border-slate-200 dark:hover:border-slate-600 text-slate-700 dark:text-slate-200'
                                    }`}
                                >
                                    <div className="flex items-start gap-2.5 min-w-0">
                                        <MessageSquare className="w-4 h-4 text-violet-500 shrink-0 mt-0.5" />
                                        <div className="min-w-0">
                                            <p className="text-xs font-semibold truncate">
                                                {session.title || 'Диалог с методистом'}
                                            </p>
                                            <p className="text-[10px] text-slate-400 dark:text-slate-500 mt-0.5">
                                                {session.date || 'Сегодня'} • {session.messageCount || 1} сообщ.
                                            </p>
                                        </div>
                                    </div>
                                    <ChevronRight className="w-3.5 h-3.5 text-slate-400 group-hover:translate-x-0.5 transition-transform shrink-0 mt-1" />
                                </button>
                            );
                        })
                    )}
                </div>

                {/* Footer: Clear All */}
                {sessions.length > 0 && (
                    <div className="p-4 border-t border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/20">
                        <button
                            type="button"
                            onClick={onClearAll}
                            className="w-full flex items-center justify-center gap-1.5 text-xs text-rose-600 dark:text-rose-400 hover:text-rose-700 py-2 rounded-lg hover:bg-rose-50 dark:hover:bg-rose-950/30 transition-colors font-medium"
                        >
                            <Trash2 className="w-3.5 h-3.5" />
                            <span>Очистить историю</span>
                        </button>
                    </div>
                )}
            </div>
        </div>
    );
}
