import React, { useState, useEffect, useRef, useCallback } from 'react';
import {
  Search, Paperclip, FileText, Check, CheckCheck,
  Clock, Video, Calendar, ArrowLeft, Phone, MessageCircle,
  ExternalLink, AlertCircle, Loader2, X, Download, ArrowUp
} from 'lucide-react';

/**
 * Клиентское сжатие изображений через HTML5 Canvas перед отправкой.
 * Ресайзит до 1920px по большей стороне, сохраняет в формате WebP с качеством 0.82.
 * Снижает размер с 10-20 МБ фото телефонов до ~150-350 КБ без потери четкости.
 */
async function compressImageInBrowser(file, maxDim = 1920, quality = 0.82) {
  if (!file || !file.type.startsWith('image/')) return file;
  if (file.type === 'image/svg+xml' || file.type === 'image/gif') return file;
  if (file.size < 250 * 1024) return file;

  return new Promise((resolve) => {
    const reader = new FileReader();
    reader.onload = (e) => {
      const img = new Image();
      img.onload = () => {
        let width = img.naturalWidth || img.width;
        let height = img.naturalHeight || img.height;

        if (width > maxDim || height > maxDim) {
          if (width >= height) {
            height = Math.round((height * maxDim) / width);
            width = maxDim;
          } else {
            width = Math.round((width * maxDim) / height);
            height = maxDim;
          }
        }

        const canvas = document.createElement('canvas');
        canvas.width = width;
        canvas.height = height;
        const ctx = canvas.getContext('2d');
        if (!ctx) {
          resolve(file);
          return;
        }

        ctx.drawImage(img, 0, 0, width, height);

        canvas.toBlob(
          (blob) => {
            if (!blob || blob.size >= file.size) {
              resolve(file);
              return;
            }
            const cleanName = file.name.replace(/\.[^/.]+$/, '') + '.webp';
            const compressedFile = new File([blob], cleanName, {
              type: 'image/webp',
              lastModified: Date.now(),
            });
            resolve(compressedFile);
          },
          'image/webp',
          quality
        );
      };
      img.onerror = () => resolve(file);
      img.src = e.target.result;
    };
    reader.onerror = () => resolve(file);
    reader.readAsDataURL(file);
  });
}

export default function EdusferaMessenger({ initialData = null }) {
  const currentUserId = initialData?.user?.id || window.EDUSFERA_CHAT_INITIAL?.user?.id;
  const initialConvId = initialData?.conversationId || window.EDUSFERA_CHAT_INITIAL?.conversationId || null;

  const [conversations, setConversations] = useState([]);
  const [selectedId, setSelectedId] = useState(initialConvId);
  const [messages, setMessages] = useState([]);
  const [otherUser, setOtherUser] = useState(null);
  const [lessonBanner, setLessonBanner] = useState(null);
  const [quickReplies, setQuickReplies] = useState([]);

  const [inputText, setInputText] = useState('');
  const [stagedFile, setStagedFile] = useState(null);
  const [searchQuery, setSearchQuery] = useState('');
  const [activeFilter, setActiveFilter] = useState('all'); // 'all' | 'unread' | 'paid'

  const [isLoadingList, setIsLoadingList] = useState(true);
  const [isLoadingMessages, setIsLoadingMessages] = useState(false);
  const [isUploading, setIsUploading] = useState(false);
  const [isMobileViewingChat, setIsMobileViewingChat] = useState(Boolean(initialConvId));
  const [previewImage, setPreviewImage] = useState(null);
  const [errorMessage, setErrorMessage] = useState(null);

  // Синхронизация мобильного отображения чата с классами body для скрытия нижнего навбара
  useEffect(() => {
    if (typeof document !== 'undefined') {
      if (isMobileViewingChat && window.innerWidth < 768) {
        document.body.classList.add('ed-chat-open');
      } else {
        document.body.classList.remove('ed-chat-open');
      }
    }
    return () => {
      if (typeof document !== 'undefined') {
        document.body.classList.remove('ed-chat-open');
      }
    };
  }, [isMobileViewingChat]);

  // Поддержка системной кнопки «Назад» на телефонах при открытом диалоге
  useEffect(() => {
    const handlePopState = () => {
      if (window.innerWidth < 768 && isMobileViewingChat) {
        setIsMobileViewingChat(false);
      }
    };
    window.addEventListener('popstate', handlePopState);
    return () => window.removeEventListener('popstate', handlePopState);
  }, [isMobileViewingChat]);

  const messagesEndRef = useRef(null);
  const fileInputRef = useRef(null);
  const textareaRef = useRef(null);
  const searchInputRef = useRef(null);
  const pollingRef = useRef(null);

  const getCsrfToken = () => {
    return (
      window.EDUSFERA_CHAT_INITIAL?.csrfToken ||
      window.EDUSFERA_CSRF_TOKEN ||
      document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ||
      ''
    );
  };

  const scrollToBottom = useCallback((smooth = true) => {
    if (messagesEndRef.current) {
      messagesEndRef.current.scrollIntoView({ behavior: smooth ? 'smooth' : 'auto' });
    }
  }, []);

  // ─── Fetch Conversations List ───
  const fetchConversations = useCallback(async (quiet = false) => {
    if (!quiet && conversations.length === 0) setIsLoadingList(true);
    try {
      const res = await fetch('/api/chat/conversations', {
        headers: { Accept: 'application/json' },
      });
      const data = await res.json();
      if (data.success) {
        setConversations(data.conversations || []);
        if (!selectedId && data.conversations.length > 0 && window.innerWidth >= 768) {
          setSelectedId(data.conversations[0].id);
        }
      }
    } catch (e) {
      console.error('Failed to load conversations', e);
    } finally {
      setIsLoadingList(false);
    }
  }, [selectedId, conversations.length]);

  // ─── Fetch Messages for Selected Conversation ───
  const fetchMessages = useCallback(async (convId, isPolling = false) => {
    if (!convId) return;
    if (!isPolling) setIsLoadingMessages(true);

    try {
      const res = await fetch(`/api/chat/conversations/${convId}/messages`, {
        headers: { Accept: 'application/json' },
      });
      const data = await res.json();
      if (data.success) {
        setMessages(prev => {
          if (isPolling) {
            const serverIds = new Set(data.messages.map(m => m.id));
            const pending = prev.filter(m => m.isPending && !serverIds.has(m.id));
            return [...data.messages, ...pending];
          }
          return data.messages || [];
        });
        setOtherUser(data.other_user || null);
        setLessonBanner(data.lesson_banner || null);
        setQuickReplies(data.quick_replies || []);

        if (!isPolling) {
          setTimeout(() => scrollToBottom(false), 80);
        }
      }
    } catch (e) {
      console.error('Failed to load messages', e);
    } finally {
      if (!isPolling) setIsLoadingMessages(false);
    }
  }, [scrollToBottom]);

  // Initial load
  useEffect(() => {
    fetchConversations();
  }, [fetchConversations]);

  // Selected Conversation change
  useEffect(() => {
    if (selectedId) {
      fetchMessages(selectedId, false);
      setIsMobileViewingChat(true);

      setConversations(prev =>
        prev.map(c => c.id === selectedId ? { ...c, unread_count: 0 } : c)
      );
    }
  }, [selectedId, fetchMessages]);

  // Polling for real-time updates (every 3.5 seconds)
  useEffect(() => {
    if (!selectedId) return;

    if (pollingRef.current) clearInterval(pollingRef.current);
    pollingRef.current = setInterval(() => {
      fetchMessages(selectedId, true);
      fetchConversations(true);
    }, 3500);

    return () => {
      if (pollingRef.current) clearInterval(pollingRef.current);
    };
  }, [selectedId, fetchMessages, fetchConversations]);

  // Auto-resize textarea
  useEffect(() => {
    if (textareaRef.current) {
      textareaRef.current.style.height = 'auto';
      textareaRef.current.style.height = `${Math.min(textareaRef.current.scrollHeight, 140)}px`;
    }
  }, [inputText]);

  // Global shortcut (⌘K / Ctrl+K) to focus search
  useEffect(() => {
    const handleGlobalKeyDown = (e) => {
      if ((e.metaKey || e.ctrlKey) && e.key.toLowerCase() === 'k') {
        e.preventDefault();
        searchInputRef.current?.focus();
        searchInputRef.current?.select();
      }
    };
    window.addEventListener('keydown', handleGlobalKeyDown);
    return () => window.removeEventListener('keydown', handleGlobalKeyDown);
  }, []);

  // ─── Format File Size ───
  const formatFileSize = (bytes) => {
    if (!bytes || bytes === 0) return '0 B';
    const k = 1024;
    const sizes = ['B', 'KB', 'MB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
  };

  // ─── Handle File Select ───
  const handleFileSelect = async (e) => {
    const rawFile = e.target.files?.[0];
    if (!rawFile) return;

    const isImg = rawFile.type.startsWith('image/');
    let fileToUse = rawFile;

    if (isImg) {
      try {
        fileToUse = await compressImageInBrowser(rawFile);
      } catch (err) {
        console.warn('Browser compression fallback to original', err);
      }
    }

    const previewUrl = isImg ? URL.createObjectURL(fileToUse) : null;

    setStagedFile({
      file: fileToUse,
      preview: previewUrl,
      isImage: isImg,
      sizeStr: formatFileSize(fileToUse.size),
    });

    if (fileInputRef.current) fileInputRef.current.value = '';
    if (textareaRef.current) textareaRef.current.focus();
  };

  // ─── Send Message ───
  const handleSend = async (customText = null, customMeta = null) => {
    const textToSend = (customText !== null ? customText : inputText).trim();
    if (!textToSend && !customMeta) return;
    if (!selectedId) return;

    const clientId = 'c_' + Date.now() + '_' + Math.random().toString(36).substring(2, 6);
    const nowTime = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });

    const optimisticMsg = {
      id: clientId,
      client_id: clientId,
      conversation_id: selectedId,
      sender_id: currentUserId,
      is_mine: true,
      is_system: false,
      message: textToSend,
      formatted_html: textToSend ? textToSend.replace(/\n/g, '<br/>') : '',
      meta: customMeta,
      is_read: false,
      time: nowTime,
      isPending: true,
    };

    setMessages(prev => [...prev, optimisticMsg]);
    setInputText('');
    if (textareaRef.current) textareaRef.current.style.height = 'auto';
    setErrorMessage(null);
    setTimeout(() => scrollToBottom(true), 30);

    try {
      const res = await fetch(`/api/chat/conversations/${selectedId}/messages`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-CSRF-TOKEN': getCsrfToken(),
        },
        body: JSON.stringify({
          message: textToSend,
          client_id: clientId,
          meta: customMeta,
        }),
      });

      const data = await res.json();
      if (data.success && data.message) {
        setMessages(prev =>
          prev.map(m => (m.client_id === clientId ? { ...data.message, isPending: false } : m))
        );
        setConversations(prev =>
          prev.map(c => c.id === selectedId ? {
            ...c,
            last_message: {
              id: data.message.id,
              text: data.message.message,
              sender_id: currentUserId,
              is_mine: true,
              is_read: false,
              time: data.message.time,
            }
          } : c)
        );
      } else {
        setMessages(prev =>
          prev.map(m => (m.client_id === clientId ? { ...m, isPending: false, isFailed: true } : m))
        );
        setErrorMessage(data.message || 'Не удалось отправить сообщение.');
      }
    } catch (e) {
      console.error('Send failed', e);
      setMessages(prev =>
        prev.map(m => (m.client_id === clientId ? { ...m, isPending: false, isFailed: true } : m))
      );
      setErrorMessage('Ошибка соединения с сервером.');
    }
  };

  // ─── Send With Staged Attachment ───
  const handleSendWithStaged = async () => {
    const text = inputText.trim();
    if (!text && !stagedFile) return;
    if (!selectedId) return;

    if (stagedFile) {
      setIsUploading(true);
      setErrorMessage(null);
      const formData = new FormData();
      formData.append('file', stagedFile.file);

      try {
        const res = await fetch(`/api/chat/conversations/${selectedId}/upload`, {
          method: 'POST',
          headers: {
            'Accept': 'application/json',
            'X-CSRF-TOKEN': getCsrfToken(),
          },
          body: formData,
        });

        const data = await res.json();
        if (data.success && data.attachment) {
          const finalMsgText = text || '';
          const finalMeta = data.attachment;
          setStagedFile(null);
          setInputText('');
          if (textareaRef.current) textareaRef.current.style.height = 'auto';
          await handleSend(finalMsgText, finalMeta);
        } else {
          setErrorMessage(data.message || 'Ошибка загрузки файла.');
        }
      } catch (err) {
        console.error('Upload failed', err);
        setErrorMessage('Не удалось загрузить файл.');
      } finally {
        setIsUploading(false);
      }
    } else {
      await handleSend(text);
    }
  };

  const handleKeyDown = (e) => {
    if (e.key === 'Enter' && !e.shiftKey) {
      e.preventDefault();
      handleSendWithStaged();
    }
  };

  const activeConversation = conversations.find(c => c.id === selectedId);

  const filteredConversations = conversations.filter(conv => {
    if (searchQuery.trim()) {
      const q = searchQuery.toLowerCase().trim();
      const nameMatch = conv.other_user?.name?.toLowerCase().includes(q);
      const subjectMatch = conv.subject?.toLowerCase().includes(q);
      const lastMsgMatch = conv.last_message?.text?.toLowerCase().includes(q);
      if (!nameMatch && !subjectMatch && !lastMsgMatch) {
        return false;
      }
    }
    if (activeFilter === 'unread') {
      return (conv.unread_count || 0) > 0;
    }
    if (activeFilter === 'paid') {
      return Boolean(conv.is_paid || conv.lesson_info?.paid);
    }
    return true;
  });

  return (
    <div className="relative flex h-full md:h-[calc(100dvh-4.25rem)] w-full overflow-hidden rounded-none md:rounded-2xl border-0 md:border border-zinc-200/80 bg-white shadow-none md:shadow-xl dark:border-zinc-800 dark:bg-zinc-950">
      {/* ─────────────────── LEFT: CONVERSATIONS SIDEBAR ─────────────────── */}
      <aside
        className={`flex flex-col border-r border-zinc-200/70 bg-zinc-50/40 dark:border-zinc-800/80 dark:bg-zinc-900/30 md:w-80 lg:w-[340px] shrink-0 ${
          isMobileViewingChat ? 'hidden md:flex' : 'w-full flex'
        }`}
      >
        {/* Header & Search */}
        <div
          style={{
            paddingTop: 'max(14px, env(safe-area-inset-top, 14px))',
          }}
          className="border-b border-zinc-200/60 p-4 dark:border-zinc-800/80"
        >
          <div className="flex items-center justify-between mb-3.5">
            <h1 className="text-xl font-extrabold tracking-tight text-zinc-900 dark:text-white flex items-center gap-2">
              Сообщения
            </h1>
            <span className="rounded-full bg-violet-100 px-2.5 py-0.5 text-xs font-bold text-violet-700 dark:bg-violet-950/70 dark:text-violet-300">
              {filteredConversations.length}
            </span>
          </div>

          {/* Search Bar Capsule */}
          <div
            className="group relative mb-3 flex items-center gap-2.5 rounded-xl bg-zinc-100/90 dark:bg-zinc-800/60 px-3 py-2.5 transition-all duration-200 border border-zinc-200/80 dark:border-zinc-700/60 hover:border-zinc-300 dark:hover:border-zinc-600 focus-within:!bg-white dark:focus-within:!bg-zinc-900 focus-within:!border-violet-500 focus-within:ring-2 focus-within:ring-violet-500/20 shadow-xs cursor-text"
            onClick={() => searchInputRef.current?.focus()}
          >
            <Search
              className="h-4 w-4 shrink-0 text-zinc-400 group-focus-within:text-violet-600 dark:group-focus-within:text-violet-400 transition-colors pointer-events-none"
              strokeWidth={2.2}
            />
            <input
              ref={searchInputRef}
              type="text"
              placeholder="Поиск по диалогам..."
              value={searchQuery}
              onChange={(e) => setSearchQuery(e.target.value)}
              style={{
                border: 'none',
                outline: 'none',
                boxShadow: 'none',
                background: 'transparent',
                padding: 0,
                margin: 0,
                width: '100%',
              }}
              className="w-full bg-transparent !border-0 !outline-none !ring-0 !shadow-none !p-0 text-sm font-medium text-zinc-900 placeholder:text-zinc-400 dark:text-zinc-100 dark:placeholder:text-zinc-500"
            />
            {searchQuery ? (
              <button
                type="button"
                onClick={(e) => {
                  e.stopPropagation();
                  setSearchQuery('');
                  searchInputRef.current?.focus();
                }}
                className="shrink-0 rounded-full p-0.5 text-zinc-400 hover:bg-zinc-200 hover:text-zinc-700 dark:hover:bg-zinc-700 dark:hover:text-zinc-200 transition-colors"
                title="Очистить поиск"
              >
                <X className="h-3.5 w-3.5" strokeWidth={2.5} />
              </button>
            ) : (
              <kbd className="hidden sm:inline-flex shrink-0 items-center px-1.5 py-0.5 text-[10px] font-semibold text-zinc-400 dark:text-zinc-500 bg-white dark:bg-zinc-800 border border-zinc-200/80 dark:border-zinc-700 rounded shadow-2xs pointer-events-none select-none">
                ⌘K
              </kbd>
            )}
          </div>

          {/* Segmented Filter Pills */}
          <div className="flex rounded-xl bg-zinc-200/60 p-1 text-xs font-semibold dark:bg-zinc-800/60 gap-1">
            {[
              { id: 'all', label: 'Все' },
              { id: 'unread', label: 'Новые' },
              { id: 'paid', label: 'С оплатой' },
            ].map(tab => (
              <button
                key={tab.id}
                onClick={() => setActiveFilter(tab.id)}
                className={`flex-1 rounded-lg py-1.5 px-2 text-center whitespace-nowrap transition-all ${
                  activeFilter === tab.id
                    ? 'bg-white text-zinc-900 shadow-xs dark:bg-zinc-900 dark:text-white font-bold'
                    : 'text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white'
                }`}
              >
                {tab.label}
              </button>
            ))}
          </div>
        </div>

        {/* Conversations List */}
        <div className="flex-1 overflow-y-auto px-2 py-2 space-y-1">
          {isLoadingList ? (
            <div className="flex h-36 items-center justify-center text-zinc-400">
              <Loader2 className="h-5 w-5 animate-spin text-violet-500" />
            </div>
          ) : filteredConversations.length === 0 ? (
            <div className="flex flex-col items-center justify-center p-8 text-center my-auto min-h-[220px]">
              <div className="flex h-12 w-12 items-center justify-center rounded-2xl bg-violet-50 dark:bg-violet-950/50 text-violet-600 dark:text-violet-400 mb-3 shadow-xs">
                <MessageCircle className="h-6 w-6" strokeWidth={1.8} />
              </div>
              <p className="text-sm font-bold text-zinc-800 dark:text-zinc-200 mb-1">
                {searchQuery ? 'Ничего не найдено' : 'Диалогов пока нет'}
              </p>
              <p className="text-xs text-zinc-500 dark:text-zinc-400 max-w-xs leading-relaxed">
                {searchQuery
                  ? `По запросу «${searchQuery}» совпадений не обнаружено`
                  : 'Когда ученик напишет вам или забронирует занятие, переписка отобразится здесь'}
              </p>
            </div>
          ) : (
            filteredConversations.map(conv => {
              const isSelected = conv.id === selectedId;
              const other = conv.other_user;
              const lastMsg = conv.last_message;

              return (
                <div
                  key={conv.id}
                  onClick={() => {
                    setSelectedId(conv.id);
                    setIsMobileViewingChat(true);
                    if (window.innerWidth < 768) {
                      window.history.pushState({ edusferaChatOpen: true }, '', `?conversation=${conv.id}`);
                    }
                  }}
                  className={`group relative flex items-center gap-3 rounded-xl p-3 transition-colors cursor-pointer ${
                    isSelected
                      ? 'bg-violet-50/90 dark:bg-violet-950/40'
                      : 'hover:bg-zinc-100/70 dark:hover:bg-zinc-800/40'
                  }`}
                >
                  {/* Avatar */}
                  <div className="relative shrink-0">
                    {other?.avatar ? (
                      <img
                        src={other.avatar}
                        alt={other.name}
                        className="h-11 w-11 rounded-full object-cover ring-1 ring-zinc-200/80 dark:ring-zinc-800"
                      />
                    ) : (
                      <div className="flex h-11 w-11 items-center justify-center rounded-full bg-gradient-to-tr from-violet-600 to-indigo-600 text-sm font-bold text-white shadow-xs">
                        {other?.name ? other.name[0].toUpperCase() : 'U'}
                      </div>
                    )}
                  </div>

                  {/* Body */}
                  <div className="min-w-0 flex-1">
                    <div className="flex items-center justify-between mb-0.5">
                      <span className="truncate text-sm font-semibold text-zinc-900 dark:text-white">
                        {other?.name}
                      </span>
                      {lastMsg && (
                        <span className="shrink-0 text-[11px] font-medium text-zinc-400">
                          {lastMsg.time}
                        </span>
                      )}
                    </div>

                    <p className="text-[11px] font-medium text-violet-600 dark:text-violet-400 truncate mb-1">
                      {conv.subject}
                    </p>

                    <div className="flex items-center justify-between gap-2">
                      <p className="truncate text-xs text-zinc-500 dark:text-zinc-400">
                        {lastMsg ? (
                          <>
                            {lastMsg.is_mine && <span className="font-semibold text-zinc-700 dark:text-zinc-300">Вы: </span>}
                            {lastMsg.text}
                          </>
                        ) : (
                          <span className="italic text-zinc-400">Нет сообщений</span>
                        )}
                      </p>

                      {conv.unread_count > 0 && (
                        <span className="shrink-0 flex h-4.5 min-w-[18px] items-center justify-center rounded-full bg-violet-600 px-1 text-[10px] font-bold text-white shadow-xs">
                          {conv.unread_count}
                        </span>
                      )}
                    </div>
                  </div>
                </div>
              );
            })
          )}
        </div>
      </aside>

      {/* ─────────────────── RIGHT: ACTIVE CHAT ─────────────────── */}
      <main
        className={`flex flex-1 flex-col bg-white dark:bg-zinc-950 min-w-0 overflow-hidden w-full ${
          !isMobileViewingChat ? 'hidden md:flex' : 'flex'
        }`}
      >
        {selectedId && (otherUser || activeConversation) ? (
          <>
            {/* Header */}
            <header
              style={{
                paddingTop: 'env(safe-area-inset-top, 0px)',
                height: 'calc(3.75rem + env(safe-area-inset-top, 0px))',
              }}
              className="px-3 sm:px-6 border-b border-zinc-200/70 dark:border-zinc-800/80 bg-white/90 dark:bg-zinc-950/90 backdrop-blur-md flex items-center justify-between shrink-0 w-full min-w-0"
            >
              <div className="flex items-center gap-3 min-w-0">
                <button
                  type="button"
                  onClick={() => {
                    setIsMobileViewingChat(false);
                    if (window.innerWidth < 768 && window.history.state?.edusferaChatOpen) {
                      window.history.back();
                    } else {
                      window.history.replaceState({}, '', '/admin/messages');
                    }
                  }}
                  className="md:hidden flex h-10 w-10 min-h-[44px] min-w-[44px] items-center justify-center rounded-xl text-zinc-600 hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-zinc-800 active:scale-95 transition"
                  title="Назад к списку диалогов"
                  aria-label="Назад к диалогам"
                >
                  <ArrowLeft className="h-5 w-5" />
                </button>

                <div className="relative">
                  {otherUser?.avatar ? (
                    <img
                      src={otherUser.avatar}
                      alt={otherUser.name}
                      className="h-10 w-10 rounded-full object-cover ring-1 ring-zinc-200 dark:ring-zinc-800"
                    />
                  ) : (
                    <div className="flex h-10 w-10 items-center justify-center rounded-full bg-violet-600 font-bold text-white text-sm shadow-xs">
                      {otherUser?.name ? otherUser.name[0].toUpperCase() : 'U'}
                    </div>
                  )}
                  <span className="absolute bottom-0 right-0 h-2.5 w-2.5 rounded-full bg-emerald-500 ring-2 ring-white dark:ring-zinc-950" />
                </div>

                <div className="min-w-0">
                  <div className="flex items-center gap-2">
                    <h2 className="truncate text-sm font-bold text-zinc-900 dark:text-white">
                      {otherUser?.name}
                    </h2>
                  </div>
                  <p className="truncate text-xs text-zinc-500 dark:text-zinc-400">
                    {otherUser?.is_tutor ? 'Репетитор' : 'Ученик'} · {otherUser?.subject || activeConversation?.subject || 'Платформа'}
                  </p>
                </div>
              </div>

              {/* Action Buttons */}
              <div className="flex items-center gap-1.5">
                {otherUser?.phone && (
                  <a
                    href={`tel:${otherUser.phone}`}
                    className="flex h-9 w-9 items-center justify-center rounded-xl bg-zinc-100 text-zinc-600 hover:bg-zinc-200/80 dark:bg-zinc-800 dark:text-zinc-300 dark:hover:bg-zinc-700 transition"
                    title={`Позвонить: ${otherUser.phone}`}
                  >
                    <Phone className="h-4 w-4 text-emerald-600" />
                  </a>
                )}
                {otherUser?.telegram && (
                  <a
                    href={`https://t.me/${otherUser.telegram.replace('@', '')}`}
                    target="_blank"
                    rel="noopener noreferrer"
                    className="flex h-9 w-9 items-center justify-center rounded-xl bg-sky-50 text-sky-600 hover:bg-sky-100 dark:bg-sky-950/40 dark:text-sky-300 transition"
                    title="Написать в Telegram"
                  >
                    <ExternalLink className="h-4 w-4" />
                  </a>
                )}
              </div>
            </header>

            {/* Smart Lesson Banner (Only if pending or confirmed) */}
            {lessonBanner && (
              <div className="mx-4 my-2 px-4 py-2.5 rounded-2xl bg-gradient-to-r from-violet-500/10 via-purple-500/5 to-indigo-500/10 border border-violet-200/60 dark:border-violet-900/40 flex items-center justify-between gap-3 text-xs">
                <div className="flex items-center gap-2.5">
                  <Calendar className="h-4 w-4 text-violet-600 shrink-0" />
                  <div>
                    <span className="font-semibold text-zinc-900 dark:text-white">
                      Урок: {lessonBanner.start_time}
                    </span>
                    <span className="ml-2 rounded-full bg-violet-100 px-2 py-0.5 text-[10px] font-bold text-violet-700 dark:bg-violet-950 dark:text-violet-300">
                      {lessonBanner.status_label}
                    </span>
                  </div>
                </div>

                {lessonBanner.can_enter_class && lessonBanner.classroom_url && (
                  <a
                    href={lessonBanner.classroom_url}
                    className="inline-flex items-center gap-1.5 rounded-xl bg-emerald-600 px-3 py-1 font-bold text-white shadow-xs hover:bg-emerald-700 transition"
                  >
                    <Video className="h-3.5 w-3.5" />
                    <span>Войти в класс</span>
                  </a>
                )}

                {lessonBanner.checkout_url && (
                  <a
                    href={lessonBanner.checkout_url}
                    className="inline-flex items-center rounded-xl bg-violet-600 px-3 py-1 font-bold text-white shadow-xs hover:bg-violet-700 transition"
                  >
                    <span>Оплатить</span>
                  </a>
                )}
              </div>
            )}

            {/* Messages Canvas */}
            <div className="flex-1 overflow-y-auto overflow-x-hidden bg-[#F8FAFC] dark:bg-[#09090B] px-3 sm:px-4 py-4 sm:py-6 space-y-3 w-full min-w-0">
              {isLoadingMessages ? (
                <div className="flex h-48 items-center justify-center text-zinc-400">
                  <Loader2 className="h-5 w-5 animate-spin text-violet-500" />
                </div>
              ) : messages.length === 0 ? (
                <div className="flex h-full flex-col items-center justify-center text-center p-6 text-zinc-400">
                  <div className="mb-3 flex h-12 w-12 items-center justify-center rounded-2xl bg-white shadow-xs text-violet-600 dark:bg-zinc-900 dark:text-violet-400">
                    <MessageCircle className="h-6 w-6" />
                  </div>
                  <h3 className="text-sm font-bold text-zinc-700 dark:text-zinc-200">
                    Начало переписки
                  </h3>
                  <p className="mt-1 text-xs text-zinc-400 max-w-xs">
                    Напишите первое сообщение или отправьте файл с заданием.
                  </p>
                </div>
              ) : (
                messages.map((msg, idx) => {
                  const isMine = msg.is_mine;
                  const isSystem = msg.is_system;

                  if (isSystem) {
                    return (
                      <div key={msg.id || idx} className="my-2 flex justify-center">
                        <span className="rounded-full bg-zinc-200/50 px-3 py-1 text-[11px] font-medium text-zinc-500 dark:bg-zinc-800 dark:text-zinc-400">
                          {msg.message}
                        </span>
                      </div>
                    );
                  }

                  const prevMsg = messages[idx - 1];
                  const isSameSender = prevMsg && prevMsg.sender_id === msg.sender_id;

                  return (
                    <div
                      key={msg.id || idx}
                      className={`flex items-end gap-2 w-full min-w-0 ${isMine ? 'justify-end' : 'justify-start'} ${
                        isSameSender ? 'mt-1' : 'mt-3'
                      }`}
                    >
                      {/* Interlocutor avatar on left (only shown once per group) */}
                      {!isMine && (
                        <div className="w-7 shrink-0 mb-0.5">
                          {!isSameSender && (
                            <>
                              {otherUser?.avatar ? (
                                <img
                                  src={otherUser.avatar}
                                  alt=""
                                  className="h-7 w-7 rounded-full object-cover ring-1 ring-zinc-200 dark:ring-zinc-800"
                                />
                              ) : (
                                <div className="flex h-7 w-7 items-center justify-center rounded-full bg-violet-600 text-[11px] font-bold text-white shadow-xs">
                                  {otherUser?.name ? otherUser.name[0].toUpperCase() : 'U'}
                                </div>
                              )}
                            </>
                          )}
                        </div>
                      )}

                      {/* Bubble */}
                      <div
                        className={`max-w-[82vw] xs:max-w-[78vw] sm:max-w-[70%] w-fit flex flex-col items-start transition ${
                          msg.meta?.type === 'image' && msg.meta?.url ? 'p-1.5' : 'px-3.5 py-2 sm:px-4 sm:py-2.5'
                        } text-[13.5px] leading-relaxed break-words overflow-hidden ${
                          isMine
                            ? 'bg-[#7D39EB] text-white rounded-2xl rounded-br-xs shadow-sm shadow-violet-500/10'
                            : 'bg-white dark:bg-zinc-900 text-zinc-900 dark:text-zinc-100 border border-zinc-200/70 dark:border-zinc-800 rounded-2xl rounded-tl-xs shadow-[0_1px_2px_rgba(0,0,0,0.03)]'
                        }`}
                      >
                        {/* Image Attachment (strictly bounded on mobile) */}
                        {msg.meta?.type === 'image' && msg.meta?.url && (
                          <div className="overflow-hidden rounded-xl bg-black/5 dark:bg-black/20 w-fit max-w-[72vw] sm:max-w-[420px]">
                            <img
                              src={msg.meta.url}
                              alt={msg.meta.file_name || 'Фотография'}
                              onClick={() => setPreviewImage(msg.meta.url)}
                              loading="lazy"
                              className="max-h-[260px] sm:max-h-[380px] w-auto max-w-full rounded-xl object-contain cursor-pointer hover:opacity-95 transition shadow-2xs block"
                            />
                          </div>
                        )}

                        {/* File Attachment */}
                        {msg.meta?.type === 'file' && msg.meta?.url && (
                          <a
                            href={msg.meta.url}
                            target="_blank"
                            rel="noopener noreferrer"
                            className={`mb-1 flex items-center gap-2.5 sm:gap-3 rounded-xl p-2 sm:p-2.5 text-xs font-semibold max-w-[72vw] sm:max-w-none transition ${
                              isMine
                                ? 'bg-white/15 hover:bg-white/20 text-white'
                                : 'bg-zinc-100 hover:bg-zinc-200/70 text-zinc-900 dark:bg-zinc-800 dark:text-zinc-100'
                            }`}
                          >
                            <div className={`flex h-8 w-8 sm:h-9 sm:w-9 shrink-0 items-center justify-center rounded-lg ${
                              isMine ? 'bg-white/20 text-white' : 'bg-violet-100 text-violet-700 dark:bg-violet-950/70 dark:text-violet-300'
                            }`}>
                              <FileText className="h-4 w-4" />
                            </div>
                            <div className="min-w-0 flex-1">
                              <p className="truncate font-semibold">{msg.meta.file_name}</p>
                              {msg.meta.file_size ? (
                                <p className={`text-[10px] ${isMine ? 'text-violet-200' : 'text-zinc-400'}`}>
                                  {formatFileSize(msg.meta.file_size)}
                                </p>
                              ) : null}
                            </div>
                            <Download className="h-4 w-4 shrink-0 opacity-80 hover:opacity-100" />
                          </a>
                        )}

                        {/* Text / Caption */}
                        {Boolean(msg.message && msg.message.trim() && msg.message !== msg.meta?.file_name) && (
                          <div
                            className={`break-words w-full max-w-[72vw] sm:max-w-none text-left ${msg.meta?.type === 'image' ? 'px-2 pt-1.5 pb-0.5' : 'mt-1'}`}
                            dangerouslySetInnerHTML={{ __html: msg.formatted_html || msg.message }}
                          />
                        )}

                        {/* Timestamp & Status */}
                        <div
                          className={`mt-1 flex items-center justify-end gap-1 text-[10px] font-medium w-full ${
                            msg.meta?.type === 'image' ? 'px-2 pb-0.5' : ''
                          } ${
                            isMine ? 'text-violet-200/90' : 'text-zinc-400'
                          }`}
                        >
                          <span>{msg.time}</span>
                          {isMine && (
                            <>
                              {msg.isPending ? (
                                <Clock className="h-3 w-3 animate-spin" />
                              ) : msg.isFailed ? (
                                <AlertCircle className="h-3 w-3 text-rose-300" title="Ошибка отправки" />
                              ) : msg.is_read ? (
                                <CheckCheck className="h-3.5 w-3.5 text-lime-300" title="Прочитано" />
                              ) : (
                                <Check className="h-3.5 w-3.5 text-violet-200" title="Отправлено" />
                              )}
                            </>
                          )}
                        </div>
                      </div>
                    </div>
                  );
                })
              )}
              <div ref={messagesEndRef} />
            </div>

            {/* Quick Replies Bar */}
            {quickReplies.length > 0 && (
              <div className="flex gap-1.5 overflow-x-auto border-t border-zinc-150 bg-white/80 dark:bg-zinc-950/80 px-4 py-2 text-xs">
                {quickReplies.map((reply, index) => (
                  <button
                    key={index}
                    type="button"
                    onClick={() => handleSend(reply)}
                    className="shrink-0 rounded-full border border-zinc-200/80 bg-zinc-50 px-3 py-1 font-medium text-zinc-700 hover:border-violet-300 hover:bg-violet-50 hover:text-violet-700 transition dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-300"
                  >
                    {reply}
                  </button>
                ))}
              </div>
            )}

            {/* Error Notification */}
            {errorMessage && (
              <div className="flex items-center justify-between bg-rose-50 px-4 py-2 text-xs font-semibold text-rose-700 dark:bg-rose-950/40 dark:text-rose-400">
                <div className="flex items-center gap-2">
                  <AlertCircle className="h-3.5 w-3.5" />
                  <span>{errorMessage}</span>
                </div>
                <button onClick={() => setErrorMessage(null)}>
                  <X className="h-3.5 w-3.5" />
                </button>
              </div>
            )}

            {/* ─────────────────── SOLID INPUT BAR ─────────────────── */}
            <footer
              style={{
                paddingBottom: 'max(12px, env(safe-area-inset-bottom, 12px))',
              }}
              className="px-3 sm:px-4 pt-2 bg-[#F8FAFC] dark:bg-[#09090B] w-full min-w-0 border-t border-zinc-200/60 dark:border-zinc-800/60"
            >
              {/* Staged File Preview Chip */}
              {stagedFile && (
                <div className="mb-2 flex items-center justify-between gap-2 sm:gap-3 p-2 bg-white dark:bg-zinc-900 border border-zinc-200/80 dark:border-zinc-800 rounded-2xl shadow-xs w-full min-w-0">
                  <div className="flex items-center gap-2 sm:gap-2.5 min-w-0 flex-1">
                    {stagedFile.preview ? (
                      <img
                        src={stagedFile.preview}
                        alt="Preview"
                        className="h-8 w-8 sm:h-9 sm:w-9 shrink-0 rounded-xl object-cover ring-1 ring-violet-300"
                      />
                    ) : (
                      <div className="flex h-8 w-8 sm:h-9 sm:w-9 shrink-0 items-center justify-center rounded-xl bg-violet-600 text-white shadow-xs">
                        <FileText className="h-4 w-4" />
                      </div>
                    )}
                    <div className="min-w-0 flex-1">
                      <p className="truncate text-xs font-bold text-zinc-900 dark:text-white max-w-[170px] xs:max-w-[210px] sm:max-w-xs">
                        {stagedFile.file.name}
                      </p>
                      <p className="text-[10px] font-semibold text-violet-600 dark:text-violet-400">
                        {stagedFile.sizeStr} · Готов к отправке
                      </p>
                    </div>
                  </div>
                  <button
                    type="button"
                    onClick={() => setStagedFile(null)}
                    className="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg text-zinc-400 hover:bg-zinc-100 hover:text-zinc-700 transition"
                  >
                    <X className="h-4 w-4" />
                  </button>
                </div>
              )}

              {/* Unified Rounded Capsule Input */}
              <div className="flex items-end gap-2 bg-white dark:bg-zinc-900 rounded-[22px] border border-zinc-200/80 dark:border-zinc-800 shadow-[0_2px_12px_rgba(0,0,0,0.04)] p-1.5 focus-within:border-violet-500 focus-within:ring-2 focus-within:ring-violet-500/20 transition-all duration-200">
                {/* File Upload Button */}
                <input
                  ref={fileInputRef}
                  type="file"
                  onChange={handleFileSelect}
                  accept="image/*,.pdf,.doc,.docx,.xls,.xlsx,.pptx,.txt,.zip"
                  className="hidden"
                />
                <button
                  type="button"
                  disabled={isUploading}
                  onClick={() => fileInputRef.current?.click()}
                  className="h-9 w-9 shrink-0 flex items-center justify-center rounded-full text-zinc-400 hover:text-violet-600 hover:bg-zinc-100 dark:hover:bg-zinc-800 transition active:scale-95 disabled:opacity-50"
                  title="Прикрепить фото или документ"
                >
                  {isUploading ? (
                    <Loader2 className="h-4 w-4 animate-spin text-violet-600" />
                  ) : (
                    <Paperclip className="h-4.5 w-4.5 -rotate-45" />
                  )}
                </button>

                {/* Auto-growing Textarea */}
                <div className="flex-1 min-w-0 py-1.5 px-1">
                  <textarea
                    ref={textareaRef}
                    rows={1}
                    value={inputText}
                    onChange={(e) => setInputText(e.target.value)}
                    onKeyDown={handleKeyDown}
                    placeholder={stagedFile ? 'Добавьте подпись к файлу...' : 'Напишите сообщение...'}
                    style={{ border: 'none', outline: 'none', boxShadow: 'none', background: 'transparent' }}
                    className="w-full min-w-0 resize-none !border-none !outline-none !ring-0 !shadow-none bg-transparent p-0 text-[16px] sm:text-[14px] leading-relaxed text-zinc-900 placeholder:text-zinc-400 dark:text-zinc-100 dark:placeholder:text-zinc-500 max-h-36"
                  />
                </div>

                {/* Send Button */}
                <button
                  type="button"
                  disabled={(!inputText.trim() && !stagedFile) || isUploading}
                  onClick={() => handleSendWithStaged()}
                  style={{
                    backgroundColor: (inputText.trim() || stagedFile) && !isUploading ? '#7D39EB' : undefined,
                  }}
                  className={`h-9 w-9 shrink-0 flex items-center justify-center rounded-full transition-all duration-200 ${
                    (inputText.trim() || stagedFile) && !isUploading
                      ? 'bg-violet-600 hover:bg-violet-700 text-white shadow-md shadow-violet-500/30 hover:scale-105 active:scale-95 cursor-pointer'
                      : 'bg-zinc-100 dark:bg-zinc-800 text-zinc-400 dark:text-zinc-500 cursor-default'
                  }`}
                  title="Отправить (Enter)"
                >
                  {isUploading ? (
                    <Loader2 className="h-4 w-4 animate-spin text-white" />
                  ) : (
                    <ArrowUp className="h-4.5 w-4.5 stroke-[2.5]" />
                  )}
                </button>
              </div>
            </footer>
          </>
        ) : selectedId && (isLoadingMessages || isLoadingList) ? (
          <div className="flex h-full flex-col w-full">
            <header
              style={{
                paddingTop: 'env(safe-area-inset-top, 0px)',
                height: 'calc(3.75rem + env(safe-area-inset-top, 0px))',
              }}
              className="px-3 sm:px-6 border-b border-zinc-200/70 dark:border-zinc-800/80 bg-white/90 dark:bg-zinc-950/90 backdrop-blur-md flex items-center gap-3 shrink-0"
            >
              <button
                type="button"
                onClick={() => {
                  setIsMobileViewingChat(false);
                  setSelectedId(null);
                  if (window.innerWidth < 768 && window.history.state?.edusferaChatOpen) {
                    window.history.back();
                  } else {
                    window.history.replaceState({}, '', '/admin/messages');
                  }
                }}
                className="md:hidden flex h-10 w-10 min-h-[44px] min-w-[44px] items-center justify-center rounded-xl text-zinc-600 hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-zinc-800 active:scale-95 transition"
                title="Назад к списку диалогов"
              >
                <ArrowLeft className="h-5 w-5" />
              </button>
              <span className="text-sm font-semibold text-zinc-600 dark:text-zinc-300">Загрузка диалога...</span>
            </header>
            <div className="flex flex-1 items-center justify-center">
              <Loader2 className="h-8 w-8 animate-spin text-violet-600" />
            </div>
          </div>
        ) : (
          /* Empty state on desktop */
          <div className="flex h-full flex-col items-center justify-center text-center p-8 text-zinc-400">
            <div className="mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-zinc-100 text-zinc-400 dark:bg-zinc-900">
              <MessageCircle className="h-7 w-7" />
            </div>
            <h2 className="text-base font-bold text-zinc-800 dark:text-zinc-200">
              Выберите диалог для общения
            </h2>
            <p className="mt-1 text-xs text-zinc-500 max-w-sm">
              Здесь вы можете переписываться, отправлять домашние задания и обсуждать уроки.
            </p>
          </div>
        )}
      </main>

      {/* ─────────────────── IMAGE PREVIEW MODAL ─────────────────── */}
      {previewImage && (
        <div
          className="fixed inset-0 z-50 flex items-center justify-center bg-black/80 p-4 backdrop-blur-sm"
          onClick={() => setPreviewImage(null)}
        >
          <div className="relative max-h-[90vh] max-w-[90vw]">
            <button
              onClick={() => setPreviewImage(null)}
              className="absolute -top-10 right-0 flex h-8 w-8 items-center justify-center rounded-full bg-white/20 text-white hover:bg-white/40"
            >
              <X className="h-5 w-5" />
            </button>
            <img
              src={previewImage}
              alt="Enlarged"
              className="max-h-[85vh] max-w-[85vw] rounded-2xl object-contain shadow-2xl"
            />
          </div>
        </div>
      )}
    </div>
  );
}
