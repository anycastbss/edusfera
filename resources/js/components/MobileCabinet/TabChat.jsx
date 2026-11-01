import React, { useState, useEffect, useRef } from 'react';
import { motion, AnimatePresence } from 'motion/react';
import { 
  MessageSquare, Search, ChevronRight, ArrowLeft, Send, 
  Loader2, CheckCheck, Check, Paperclip 
} from 'lucide-react';
import axios from 'axios';
import UserAvatar from './UserAvatar';

export default function TabChat({ chats = [], onRefreshChats }) {
  const [search, setSearch] = useState('');
  const [activeConversation, setActiveConversation] = useState(null);
  const [messages, setMessages] = useState([]);
  const [isLoadingMessages, setIsLoadingMessages] = useState(false);
  const [messageText, setMessageText] = useState('');
  const [isSending, setIsSending] = useState(false);
  const messagesEndRef = useRef(null);

  const filteredChats = chats.filter((c) =>
    (c.partner_name || '').toLowerCase().includes(search.toLowerCase()) ||
    (c.last_message || '').toLowerCase().includes(search.toLowerCase())
  );

  // Load messages when conversation is selected
  useEffect(() => {
    if (activeConversation) {
      setIsLoadingMessages(true);
      axios.get(`/api/chat/conversations/${activeConversation.id}/messages`)
        .then((res) => {
          if (res.data?.messages) {
            setMessages(res.data.messages);
          }
          // Mark as read
          axios.post(`/api/chat/conversations/${activeConversation.id}/read`).catch(() => {});
          if (onRefreshChats) onRefreshChats();
        })
        .catch((e) => console.error('Failed to load conversation messages:', e))
        .finally(() => setIsLoadingMessages(false));
    }
  }, [activeConversation]);

  // Scroll to bottom on new messages
  useEffect(() => {
    if (messagesEndRef.current) {
      messagesEndRef.current.scrollIntoView({ behavior: 'smooth' });
    }
  }, [messages]);

  const handleSendMessage = async (e) => {
    e.preventDefault();
    const text = messageText.trim();
    if (!text || isSending || !activeConversation) return;

    setIsSending(true);
    try {
      const res = await axios.post(`/api/chat/conversations/${activeConversation.id}/messages`, {
        message: text,
      });

      if (res.data?.success && res.data.message) {
        setMessages((prev) => [...prev, res.data.message]);
        setMessageText('');
        if (onRefreshChats) onRefreshChats();
      }
    } catch (err) {
      console.error('Failed to send message:', err);
      alert('Не удалось отправить сообщение. Попробуйте еще раз.');
    } finally {
      setIsSending(false);
    }
  };

  // If a conversation is active, render the dedicated mobile chat thread
  if (activeConversation) {
    return (
      <div className="flex flex-col h-[calc(100vh-140px)] -mx-4 -mt-4 bg-[#F8FAFC] dark:bg-[#0A0A0C]">
        {/* Chat Thread Header */}
        <div className="px-4 py-3 border-b border-slate-200/80 dark:border-white/10 dark:bg-[#121214] bg-white flex items-center justify-between shadow-sm shrink-0">
          <div className="flex items-center gap-3 min-w-0">
            <button
              type="button"
              onClick={() => setActiveConversation(null)}
              className="p-1.5 -ml-1 rounded-xl text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-white/10 transition"
            >
              <ArrowLeft className="w-5 h-5" />
            </button>

            <UserAvatar
              src={activeConversation.partner_avatar}
              name={activeConversation.partner_name}
              initials={activeConversation.partner_initials}
              size="sm"
              rounded="rounded-xl"
              innerRounded="rounded-[10px]"
            />

            <div className="min-w-0">
              <h4 className="text-sm font-extrabold dark:text-white text-slate-900 truncate">
                {activeConversation.partner_name}
              </h4>
              <p className="text-[10px] text-emerald-500 font-semibold flex items-center gap-1">
                <span className="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                <span>В сети</span>
              </p>
            </div>
          </div>
        </div>

        {/* Messages Stream */}
        <div className="flex-1 overflow-y-auto p-4 space-y-3">
          {isLoadingMessages ? (
            <div className="flex flex-col items-center justify-center py-12 text-slate-400 gap-2">
              <Loader2 className="w-6 h-6 animate-spin text-[#7D39EB]" />
              <span className="text-xs">Загрузка переписки...</span>
            </div>
          ) : messages.length === 0 ? (
            <div className="text-center py-12 text-slate-400 text-xs">
              В этой переписке пока нет сообщений. Напишите первым!
            </div>
          ) : (
            messages.map((msg) => {
              const isOutgoing = msg.is_outgoing;

              return (
                <div
                  key={msg.id}
                  className={`flex flex-col ${isOutgoing ? 'items-end' : 'items-start'}`}
                >
                  <div
                    className={`max-w-[80%] rounded-2xl px-3.5 py-2.5 text-xs shadow-sm leading-relaxed ${
                      isOutgoing
                        ? 'bg-gradient-to-r from-[#7D39EB] to-[#9333EA] text-white rounded-br-sm'
                        : 'dark:bg-[#18181B] bg-white border border-slate-200/80 dark:border-white/10 dark:text-slate-200 text-slate-800 rounded-bl-sm'
                    }`}
                  >
                    <p className="whitespace-pre-line">{msg.body}</p>

                    {msg.attachments && msg.attachments.length > 0 && (
                      <div className="mt-2 space-y-1">
                        {msg.attachments.map((att) => (
                          <a
                            key={att.id}
                            href={att.url}
                            target="_blank"
                            rel="noopener noreferrer"
                            className="block p-1.5 rounded-lg bg-black/20 text-[10px] underline truncate"
                          >
                            📎 {att.file_name}
                          </a>
                        ))}
                      </div>
                    )}
                  </div>

                  <div className="flex items-center gap-1 mt-1 px-1 text-[10px] text-slate-400">
                    <span>{msg.time || ''}</span>
                    {isOutgoing && (
                      msg.is_read ? (
                        <CheckCheck className="w-3 h-3 text-[#C6FF33]" />
                      ) : (
                        <Check className="w-3 h-3 text-slate-400" />
                      )
                    )}
                  </div>
                </div>
              );
            })
          )}
          <div ref={messagesEndRef} />
        </div>

        {/* Message Input Footer */}
        <form
          onSubmit={handleSendMessage}
          className="p-3 border-t border-slate-200/80 dark:border-white/10 dark:bg-[#121214] bg-white shrink-0 flex items-center gap-2"
        >
          <input
            type="text"
            value={messageText}
            onChange={(e) => setMessageText(e.target.value)}
            placeholder="Напишите сообщение ученику..."
            className="flex-1 px-4 py-2.5 rounded-2xl border border-slate-200 dark:border-white/10 dark:bg-white/5 bg-slate-100 text-xs dark:text-white text-slate-900 focus:outline-none focus:ring-2 focus:ring-purple-500/50"
          />

          <motion.button
            type="submit"
            disabled={isSending || !messageText.trim()}
            whileTap={{ scale: 0.95 }}
            className={`w-10 h-10 rounded-2xl flex items-center justify-center transition shadow-md ${
              messageText.trim()
                ? 'bg-[#C6FF33] text-black hover:bg-[#b8f522]'
                : 'bg-slate-200 dark:bg-white/10 text-slate-400 cursor-not-allowed'
            }`}
          >
            {isSending ? (
              <Loader2 className="w-4 h-4 animate-spin" />
            ) : (
              <Send className="w-4 h-4 fill-current ml-0.5" />
            )}
          </motion.button>
        </form>
      </div>
    );
  }

  // Conversation List View
  return (
    <div className="space-y-4 pb-24">
      {/* Header and Search */}
      <div className="space-y-3">
        <div className="flex items-center justify-between px-1">
          <h3 className="text-lg font-black tracking-tight dark:text-white text-slate-900">
            Сообщения
          </h3>
          <span className="text-xs font-mono text-slate-400">
            {chats.length} {chats.length === 1 ? 'диалог' : 'диалогов'}
          </span>
        </div>

        <div className="relative">
          <Search className="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400" />
          <input
            type="text"
            value={search}
            onChange={(e) => setSearch(e.target.value)}
            placeholder="Поиск по диалогам и ученикам..."
            className="w-full pl-10 pr-4 py-2.5 rounded-2xl border border-slate-200 dark:border-white/10 dark:bg-white/[0.04] bg-slate-100 text-sm dark:text-white text-slate-900 focus:outline-none focus:ring-2 focus:ring-purple-500/50"
          />
        </div>
      </div>

      {/* Chats List */}
      <div className="space-y-2">
        {filteredChats.length === 0 ? (
          <div className="py-12 text-center rounded-3xl border border-dashed border-slate-200 dark:border-white/10 p-6 flex flex-col items-center justify-center gap-2">
            <MessageSquare className="w-8 h-8 text-slate-400 mb-1" />
            <h5 className="text-sm font-bold dark:text-white text-slate-800">
              Переписок пока нет
            </h5>
            <p className="text-xs text-slate-400 max-w-xs">
              Когда ученик запишется на занятие или задаст вопрос, диалог появится здесь.
            </p>
          </div>
        ) : (
          filteredChats.map((chat) => (
            <motion.div
              key={chat.id}
              onClick={() => setActiveConversation(chat)}
              whileTap={{ scale: 0.98 }}
              className="cursor-pointer flex items-center justify-between p-3.5 rounded-2xl border border-slate-200/80 dark:border-white/10 dark:bg-white/[0.04] bg-white shadow-sm hover:shadow transition-all"
            >
              <div className="flex items-center gap-3 min-w-0">
                <UserAvatar
                  src={chat.partner_avatar}
                  name={chat.partner_name}
                  initials={chat.partner_initials}
                  size="md"
                  rounded="rounded-2xl"
                  innerRounded="rounded-[14px]"
                />

                <div className="min-w-0">
                  <div className="flex items-center gap-2">
                    <h4 className="text-sm font-bold dark:text-white text-slate-900 truncate">
                      {chat.partner_name}
                    </h4>
                    <span className="text-[10px] font-mono text-slate-400 shrink-0">
                      {chat.last_message_time}
                    </span>
                  </div>
                  <p className="text-xs text-slate-500 dark:text-slate-400 truncate mt-0.5">
                    {chat.last_message}
                  </p>
                </div>
              </div>

              <div className="flex items-center gap-2 shrink-0 ml-3">
                {Boolean(chat.unread_count) && chat.unread_count > 0 && (
                  <span className="px-2 py-0.5 rounded-full bg-[#7D39EB] text-white text-[11px] font-extrabold shadow-sm">
                    {chat.unread_count}
                  </span>
                )}
                <ChevronRight className="w-4 h-4 text-slate-400" />
              </div>
            </motion.div>
          ))
        )}
      </div>
    </div>
  );
}
