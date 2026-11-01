import React, { useState, useEffect, useCallback, useRef } from 'react';
import { motion, AnimatePresence } from 'motion/react';
import axios from 'axios';
import MobileHeader from './MobileHeader';
import MobileBottomNav from './MobileBottomNav';
import DesktopSidebar from './DesktopSidebar';
import DesktopHeader from './DesktopHeader';
import TabLessons from './TabLessons';
import TabChat from './TabChat';
import TabFinance from './TabFinance';
import TabProfile from './TabProfile';
import TabAiAgent from './TabAiAgent';
import TabStudentLessons from './TabStudentLessons';
import TabStudentHomework from './TabStudentHomework';
import LessonDetailBottomSheet from './LessonDetailBottomSheet';
import CreateLessonBottomSheet from './CreateLessonBottomSheet';
import NotificationsSheet from './NotificationsSheet';
import OfflineStatusBar from './OfflineStatusBar';
import MobileOnboardingTour from './MobileOnboardingTour';
import DesktopOnboardingTour from './DesktopOnboardingTour';
import ErrorBoundary from '../ErrorBoundary';
import { flushQueue } from '../../utils/offlineQueue';

export default function MobileCabinetApp() {
  const initialData = window.EDUSFERA_MOBILE_INITIAL_DATA || {};
  const user = initialData.user || { name: 'Пользователь', role: 'tutor', initials: 'ED' };
  const isTutor = user.is_tutor ?? (user.role === 'tutor');
  const isStudent = user.is_student || user.is_parent || (user.role === 'student' || user.role === 'parent');
  
  const tutorProfile = initialData.tutor_profile || null;
  const subscription = initialData.subscription || null;
  const initialBalance = initialData.balance || { available: 0, pending: 0, total_spent: 0, currency: 'BYN' };
  const stats = initialData.stats || null;
  const npd = initialData.npd || null;
  const tutorStudents = initialData.tutor_students || [];
  const initialHomework = initialData.student_homework || [];
  const myTutors = initialData.my_tutors || [];
  const studentGoal = initialData.student_goal || null;

  const bookingUrl = initialData.booking_url || '';
  const bookingShortUrl = initialData.booking_short_url || '';

  // Inter-tab AI bridge (e.g. from Homework to AI Tutor)
  const [aiInitialPrompt, setAiInitialPrompt] = useState('');

  // Notifications State
  const [isNotificationsOpen, setIsNotificationsOpen] = useState(false);
  const [notifications, setNotifications] = useState(() => initialData.notifications || []);
  const [unreadNotificationsCount, setUnreadNotificationsCount] = useState(() => initialData.unread_notifications_count || 0);

  // Responsive Layout detection (Desktop >= 1024px vs Mobile)
  const [isDesktop, setIsDesktop] = useState(() => {
    if (typeof window !== 'undefined') {
      return window.innerWidth >= 1024;
    }
    return Boolean(initialData.is_desktop);
  });

  useEffect(() => {
    const handleResize = () => {
      setIsDesktop(window.innerWidth >= 1024);
    };
    window.addEventListener('resize', handleResize);
    return () => window.removeEventListener('resize', handleResize);
  }, []);

  // Premium Interactive Onboarding Tour State (separate tour key for PC vs Mobile)
  const [isOnboardingTourOpen, setIsOnboardingTourOpen] = useState(false);
  useEffect(() => {
    try {
      const tourKey = isDesktop ? 'edusfera_desktop_tour_completed_v1' : 'edusfera_mobile_tour_completed_v1';
      const tourSeen = localStorage.getItem(tourKey);
      const params = new URLSearchParams(window.location.search);
      const forceTour = params.get('tour') === '1';
      if (!tourSeen || forceTour) {
        const timer = setTimeout(() => setIsOnboardingTourOpen(true), 600);
        return () => clearTimeout(timer);
      }
    } catch (e) {}
  }, [isDesktop]);

  // Online / Offline connectivity listener
  const [isOnline, setIsOnline] = useState(() => typeof navigator !== 'undefined' ? navigator.onLine : true);
  useEffect(() => {
    const handleOnline = () => setIsOnline(true);
    const handleOffline = () => setIsOnline(false);
    window.addEventListener('online', handleOnline);
    window.addEventListener('offline', handleOffline);
    return () => {
      window.removeEventListener('online', handleOnline);
      window.removeEventListener('offline', handleOffline);
    };
  }, []);

  // Theme Management: dark by default, persists in localStorage
  const [theme, setTheme] = useState(() => {
    if (typeof window !== 'undefined') {
      return localStorage.getItem('edusfera_theme') || 'dark';
    }
    return 'dark';
  });

  const toggleTheme = () => {
    const nextTheme = theme === 'dark' ? 'light' : 'dark';
    setTheme(nextTheme);
    localStorage.setItem('edusfera_theme', nextTheme);
  };

  useEffect(() => {
    const root = document.documentElement;
    if (theme === 'dark') {
      root.classList.add('dark');
      root.classList.remove('light');
    } else {
      root.classList.remove('dark');
      root.classList.add('light');
    }
  }, [theme]);

  // Active Tab: role-aware
  const [activeTab, setActiveTab] = useState(() => {
    if (typeof window !== 'undefined') {
      const params = new URLSearchParams(window.location.search);
      const tabParam = params.get('tab');
      const validTabs = isStudent 
        ? ['lessons', 'chat', 'homework', 'ai_tutor', 'profile']
        : ['lessons', 'chat', 'ai_agent', 'finance', 'profile'];

      if (tabParam && validTabs.includes(tabParam)) {
        return tabParam;
      }
      const path = window.location.pathname;
      if (path.includes('/m/chat')) return 'chat';
      if (path.includes('/m/homework')) return 'homework';
      if (path.includes('/m/ai')) return isStudent ? 'ai_tutor' : 'ai_agent';
      if (path.includes('/m/wallet') || path.includes('/m/finance')) return isStudent ? 'lessons' : 'finance';
      if (path.includes('/m/profile')) return 'profile';
    }
    return 'lessons';
  });

  const handleTabChange = (newTab) => {
    setActiveTab(newTab);
    if (typeof window !== 'undefined') {
      const url = new URL(window.location.href);
      if (newTab === 'lessons') {
        url.searchParams.delete('tab');
      } else {
        url.searchParams.set('tab', newTab);
      }
      window.history.replaceState({}, '', url.toString());
    }
  };

  // Helper for default Monday (YYYY-MM-DD)
  const getDefaultMonday = () => {
    const now = new Date();
    const day = now.getDay();
    const diff = now.getDate() - day + (day === 0 ? -6 : 1);
    now.setDate(diff);
    const y = now.getFullYear();
    const m = String(now.getMonth() + 1).padStart(2, '0');
    const d = String(now.getDate()).padStart(2, '0');
    return `${y}-${m}-${d}`;
  };

  // Week & Lessons State with Multi-Tier Local Storage Hydration
  const [currentWeekStart, setCurrentWeekStart] = useState(() => {
    try {
      const cached = localStorage.getItem('edusfera_mobile_lessons_cache');
      if (cached) {
        const val = JSON.parse(cached).week_start;
        if (val) return val;
      }
    } catch(e) {}
    return getDefaultMonday();
  });
  const currentWeekStartRef = useRef(currentWeekStart);
  useEffect(() => {
    currentWeekStartRef.current = currentWeekStart;
  }, [currentWeekStart]);
  const [weekLabel, setWeekLabel] = useState(() => {
    try {
      const cached = localStorage.getItem('edusfera_mobile_lessons_cache');
      if (cached) return JSON.parse(cached).week_label || '';
    } catch(e) {}
    return '';
  });
  const [weekSpanFormatted, setWeekSpanFormatted] = useState(() => {
    try {
      const cached = localStorage.getItem('edusfera_mobile_lessons_cache');
      if (cached) return JSON.parse(cached).week_span_formatted || '';
    } catch(e) {}
    return '';
  });
  const [lessons, setLessons] = useState(() => {
    try {
      const cached = localStorage.getItem('edusfera_mobile_lessons_cache');
      if (cached) return JSON.parse(cached).lessons || [];
    } catch(e) {}
    return [];
  });
  const [upcomingLesson, setUpcomingLesson] = useState(() => {
    try {
      const cached = localStorage.getItem('edusfera_mobile_lessons_cache');
      if (cached) return JSON.parse(cached).upcoming_lesson || null;
    } catch(e) {}
    return null;
  });
  const [selectedLesson, setSelectedLesson] = useState(null);
  const [selectedDay, setSelectedDay] = useState(() => {
    const day = new Date().getDay();
    return day === 0 ? 6 : day - 1; // 0 = Mon, ..., 6 = Sun
  });
  const [isLoadingLessons, setIsLoadingLessons] = useState(false);
  const [isSyncing, setIsSyncing] = useState(false);

  // Tutor Migrated States: Pending Requests & Weekly Availability
  const [pendingRequests, setPendingRequests] = useState(() => initialData.pending_requests || []);
  const [availability, setAvailability] = useState(() => initialData.availability || []);

  const handleConfirmRequest = async (requestId) => {
    try {
      const res = await axios.post(`/api/mobile/lessons/${requestId}/confirm`);
      if (res.data?.success) {
        setPendingRequests((prev) => prev.filter((r) => r.id !== requestId));
        fetchLessons(currentWeekStart);
      }
    } catch (err) {
      alert(err.response?.data?.error || 'Не удалось подтвердить заявку');
    }
  };

  const handleRejectRequest = async (requestId) => {
    if (!window.confirm('Отклонить данную заявку на занятие?')) return;
    try {
      const res = await axios.post(`/api/mobile/lessons/${requestId}/cancel`);
      if (res.data?.success) {
        setPendingRequests((prev) => prev.filter((r) => r.id !== requestId));
      }
    } catch (err) {
      alert(err.response?.data?.error || 'Не удалось отклонить заявку');
    }
  };

  const handleSaveAvailability = (newSchedule) => {
    setAvailability(newSchedule);
  };

  // Create Lesson Bottom Sheet
  const [isCreateLessonOpen, setIsCreateLessonOpen] = useState(false);

  // Chats Data State with Cache Hydration
  const [chats, setChats] = useState(() => {
    try {
      const cached = localStorage.getItem('edusfera_mobile_chats_cache');
      if (cached) return JSON.parse(cached) || [];
    } catch(e) {}
    return [];
  });

  // Fetch Lessons from API for specific week with Stale-While-Revalidate and Local Persistence
  const fetchLessons = useCallback(async (weekStartParam = null) => {
    setIsLoadingLessons(true);
    try {
      const url = weekStartParam
        ? `/api/mobile/lessons?week_start=${weekStartParam}`
        : '/api/mobile/lessons';
      const res = await axios.get(url, { timeout: 3500 });
      if (res.data) {
        setLessons(res.data.lessons || []);
        setUpcomingLesson(res.data.upcoming_lesson || null);
        if (res.data.pending_requests) {
          setPendingRequests(res.data.pending_requests);
        }
        setCurrentWeekStart(res.data.week_start || null);
        setWeekLabel(res.data.week_label || '');
        setWeekSpanFormatted(res.data.week_span_formatted || '');

        // Persist to offline cache
        try {
          localStorage.setItem('edusfera_mobile_lessons_cache', JSON.stringify(res.data));
        } catch(e) {}
      }
    } catch (e) {
      console.warn('[Mobile Lessons] Network slow or offline, using cached lessons:', e);
      // Fallback to local storage only on initial unparameterized load
      if (!weekStartParam) {
        try {
          const cached = localStorage.getItem('edusfera_mobile_lessons_cache');
          if (cached) {
            const parsed = JSON.parse(cached);
            if (parsed.lessons) setLessons(parsed.lessons);
            if (parsed.upcoming_lesson) setUpcomingLesson(parsed.upcoming_lesson);
            if (parsed.week_label) setWeekLabel(parsed.week_label);
          }
        } catch(err) {}
      }
    } finally {
      setIsLoadingLessons(false);
    }
  }, []);

  // Fetch Chats from API with Persistence
  const fetchChats = useCallback(async () => {
    try {
      const res = await axios.get('/api/mobile/chats', { timeout: 3500 });
      if (res.data && res.data.chats) {
        setChats(res.data.chats);
        try {
          localStorage.setItem('edusfera_mobile_chats_cache', JSON.stringify(res.data.chats));
        } catch(e) {}
      }
    } catch (e) {
      console.warn('[Mobile Chats] Network slow or offline, using cached chats:', e);
      try {
        const cached = localStorage.getItem('edusfera_mobile_chats_cache');
        if (cached) setChats(JSON.parse(cached));
      } catch(err) {}
    }
  }, []);

  // Fetch Notifications with Persistence
  const fetchNotifications = useCallback(async () => {
    try {
      const res = await axios.get('/api/mobile/notifications', { timeout: 3500 });
      if (res.data) {
        setNotifications(res.data.notifications || []);
        setUnreadNotificationsCount(res.data.unread_count || 0);
        try {
          localStorage.setItem('edusfera_mobile_notifications_cache', JSON.stringify(res.data));
        } catch(e) {}
      }
    } catch (e) {
      console.warn('[Mobile Notifications] Network slow or offline:', e);
      try {
        const cached = localStorage.getItem('edusfera_mobile_notifications_cache');
        if (cached) {
          const parsed = JSON.parse(cached);
          if (parsed.notifications) setNotifications(parsed.notifications);
          if (parsed.unread_count !== undefined) setUnreadNotificationsCount(parsed.unread_count);
        }
      } catch(err) {}
    }
  }, []);

  const handleWeekChange = useCallback((newWeekStart) => {
    setCurrentWeekStart(newWeekStart);
    fetchLessons(newWeekStart);
  }, [fetchLessons]);

  // Manual & Reconnect Full Synchronization
  const handleSyncAll = useCallback(async () => {
    setIsSyncing(true);
    try {
      await flushQueue();
      await Promise.allSettled([
        fetchLessons(currentWeekStartRef.current),
        fetchChats(),
        fetchNotifications()
      ]);
    } finally {
      setIsSyncing(false);
    }
  }, [fetchLessons, fetchChats, fetchNotifications]);

  // Initial Data Load on Mount
  useEffect(() => {
    fetchLessons(currentWeekStartRef.current);
    fetchChats();
  }, [fetchLessons, fetchChats]);

  // Listen to Service Worker Background Updates
  useEffect(() => {
    if (typeof navigator !== 'undefined' && 'serviceWorker' in navigator) {
      const onSwMessage = (event) => {
        if (event.data && event.data.type === 'MOBILE_API_UPDATED') {
          const path = event.data.path || '';
          if (path.includes('/lessons')) fetchLessons(currentWeekStartRef.current);
          else if (path.includes('/chats')) fetchChats();
          else if (path.includes('/notifications')) fetchNotifications();
        }
      };
      navigator.serviceWorker.addEventListener('message', onSwMessage);
      return () => {
        navigator.serviceWorker.removeEventListener('message', onSwMessage);
      };
    }
  }, [fetchLessons, fetchChats, fetchNotifications]);

  // Smart Visibility-Based Revalidation: only revalidate when user returns to app and connection is alive
  useEffect(() => {
    const handleVisibilityChange = () => {
      if (document.visibilityState === 'visible' && navigator.onLine) {
        fetchLessons(currentWeekStartRef.current);
        fetchNotifications();
      }
    };
    document.addEventListener('visibilitychange', handleVisibilityChange);
    return () => document.removeEventListener('visibilitychange', handleVisibilityChange);
  }, [fetchLessons, fetchNotifications]);

  const handleLessonUpdated = (updatedLesson) => {
    setSelectedLesson(updatedLesson);
    setLessons((prev) =>
      prev.map((l) => (l.id === updatedLesson.id ? { ...l, ...updatedLesson } : l))
    );
    if (upcomingLesson && upcomingLesson.id === updatedLesson.id) {
      setUpcomingLesson((prev) => ({ ...prev, ...updatedLesson }));
    }
  };

  const handleLessonCreated = (newLesson) => {
    fetchLessons(currentWeekStart);
  };

  const totalUnreadChats = chats.reduce((acc, c) => acc + (c.unread_count || 0), 0);

  const renderTabContent = () => (
    <AnimatePresence mode="wait">
      {activeTab === 'lessons' && (
        <motion.div
          key="tab-lessons"
          initial={{ opacity: 0 }}
          animate={{ opacity: 1 }}
          exit={{ opacity: 0 }}
          transition={{ duration: 0.12 }}
        >
          {isStudent ? (
            <TabStudentLessons
              user={user}
              lessons={lessons}
              myTutors={myTutors}
              upcomingLesson={upcomingLesson}
              selectedDay={selectedDay}
              onSelectDay={setSelectedDay}
              onSelectLesson={(lesson) => setSelectedLesson(lesson)}
              onOpenChat={(partnerId) => handleTabChange('chat')}
              onNavigateTab={handleTabChange}
            />
          ) : (
            <TabLessons
              lessons={lessons}
              upcomingLesson={upcomingLesson}
              pendingRequests={pendingRequests}
              onConfirmRequest={handleConfirmRequest}
              onRejectRequest={handleRejectRequest}
              availability={availability}
              onSaveAvailability={handleSaveAvailability}
              isTutor={isTutor}
              weekStart={currentWeekStart}
              weekLabel={weekLabel}
              weekSpanFormatted={weekSpanFormatted}
              onWeekChange={handleWeekChange}
              onSelectLesson={(lesson) => setSelectedLesson(lesson)}
              onOpenQuickCreate={() => setIsCreateLessonOpen(true)}
              bookingUrl={bookingUrl}
              bookingShortUrl={bookingShortUrl}
            />
          )}
        </motion.div>
      )}

      {activeTab === 'chat' && (
        <motion.div
          key="tab-chat"
          initial={{ opacity: 0 }}
          animate={{ opacity: 1 }}
          exit={{ opacity: 0 }}
          transition={{ duration: 0.12 }}
        >
          <TabChat
            chats={chats}
            onRefreshChats={fetchChats}
          />
        </motion.div>
      )}

      {activeTab === 'homework' && (
        <motion.div
          key="tab-homework"
          initial={{ opacity: 0 }}
          animate={{ opacity: 1 }}
          exit={{ opacity: 0 }}
          transition={{ duration: 0.12 }}
        >
          <TabStudentHomework
            initialHomework={initialHomework}
            onAskAi={(prompt) => {
              setAiInitialPrompt(prompt);
              handleTabChange('ai_tutor');
            }}
          />
        </motion.div>
      )}

      {(activeTab === 'ai_agent' || activeTab === 'ai_tutor') && (
        <motion.div
          key="tab-ai"
          initial={{ opacity: 0 }}
          animate={{ opacity: 1 }}
          exit={{ opacity: 0 }}
          transition={{ duration: 0.12 }}
        >
          <TabAiAgent
            user={user}
            tutorStudents={tutorStudents}
            isStudent={isStudent}
            initialPrompt={aiInitialPrompt}
            onAssignHomework={() => fetchLessons(currentWeekStart)}
          />
        </motion.div>
      )}

      {activeTab === 'finance' && (
        <motion.div
          key="tab-finance"
          initial={{ opacity: 0 }}
          animate={{ opacity: 1 }}
          exit={{ opacity: 0 }}
          transition={{ duration: 0.12 }}
        >
          <TabFinance
            tutorProfile={tutorProfile}
            subscription={subscription}
            balance={initialBalance}
            stats={stats}
            npd={npd}
          />
        </motion.div>
      )}

      {activeTab === 'profile' && (
        <motion.div
          key="tab-profile"
          initial={{ opacity: 0 }}
          animate={{ opacity: 1 }}
          exit={{ opacity: 0 }}
          transition={{ duration: 0.12 }}
        >
          <TabProfile
            user={user}
            tutorProfile={tutorProfile}
            theme={theme}
            onToggleTheme={toggleTheme}
            bookingUrl={bookingUrl}
            bookingShortUrl={bookingShortUrl}
            onOpenTour={() => setIsOnboardingTourOpen(true)}
            subscription={subscription}
            onSubscriptionUpdated={(newSub) => setSubscription(newSub)}
          />
        </motion.div>
      )}
    </AnimatePresence>
  );

  return (
    <ErrorBoundary>
      <div className={`relative min-h-screen w-full transition-colors duration-300 ${
        theme === 'dark' ? 'bg-[#0A0A0C] text-white' : 'bg-[#F8FAFC] text-slate-900'
      }`}>
      {/* ─── AURORA AMBIENT GLOW EFFECTS ─── */}
      <div className="fixed inset-0 pointer-events-none overflow-hidden z-0">
        {theme === 'dark' ? (
          <>
            <div className="absolute -top-32 -left-32 w-80 h-80 rounded-full bg-[#7D39EB]/15 blur-[120px]" />
            <div className="absolute top-1/2 -right-32 w-80 h-80 rounded-full bg-[#C6FF33]/10 blur-[130px]" />
            <div className="absolute -bottom-32 left-1/3 w-72 h-72 rounded-full bg-[#38BDF8]/10 blur-[120px]" />
          </>
        ) : (
          <>
            <div className="absolute -top-24 left-1/2 -translate-x-1/2 w-full h-72 bg-gradient-to-b from-purple-100/50 via-slate-100/30 to-transparent blur-3xl" />
            <div className="absolute bottom-0 right-0 w-80 h-80 rounded-full bg-lime-100/40 blur-[120px]" />
          </>
        )}
      </div>

      {isDesktop ? (
        /* ═══════════════════════════════════════════════════════════ */
        /* ─── DESKTOP PC UNIFIED SHELL (>= 1024px) ───────────────── */
        /* ═══════════════════════════════════════════════════════════ */
        <div className="relative z-10 min-h-screen">
          <DesktopSidebar
            user={user}
            tutorProfile={tutorProfile}
            subscription={subscription}
            balance={initialBalance}
            stats={stats}
            activeTab={activeTab}
            onSelectTab={handleTabChange}
            unreadChatsCount={totalUnreadChats}
            pendingHomeworkCount={initialHomework.filter((h) => h.status !== 'completed').length}
            theme={theme}
            onToggleTheme={toggleTheme}
            bookingUrl={bookingUrl}
            bookingShortUrl={bookingShortUrl}
            onOpenCreateLesson={isTutor ? () => setIsCreateLessonOpen(true) : null}
          />

          <DesktopHeader
            activeTab={activeTab}
            user={user}
            tutorProfile={tutorProfile}
            bookingUrl={bookingUrl}
            bookingShortUrl={bookingShortUrl}
            unreadNotificationsCount={unreadNotificationsCount}
            onOpenNotifications={() => {
              setIsNotificationsOpen(true);
              fetchNotifications();
            }}
            onOpenCreateLesson={isTutor ? () => setIsCreateLessonOpen(true) : null}
            isOnline={isOnline}
          />

          <div className="pl-64 xl:pl-72 pt-16 min-h-screen flex flex-col">
            <OfflineStatusBar onSync={handleSyncAll} isSyncing={isSyncing} />
            <main className="flex-1 max-w-7xl w-full mx-auto px-6 lg:px-8 py-8">
              {renderTabContent()}
            </main>
          </div>
        </div>
      ) : (
        /* ═══════════════════════════════════════════════════════════ */
        /* ─── MOBILE TOUCH SHELL (< 1024px) ──────────────────────── */
        /* ═══════════════════════════════════════════════════════════ */
        <div className="relative z-10 flex flex-col min-h-screen max-w-lg mx-auto">
          {/* Sticky Mobile Header */}
          <MobileHeader
            user={user}
            tutorProfile={tutorProfile}
            theme={theme}
            onToggleTheme={toggleTheme}
            unreadNotificationsCount={unreadNotificationsCount}
            onOpenNotifications={() => {
              setIsNotificationsOpen(true);
              fetchNotifications();
            }}
            bookingUrl={bookingUrl}
            bookingShortUrl={bookingShortUrl}
          />

          {/* Offline & Weak Connection Status Indicator */}
          <OfflineStatusBar onSync={handleSyncAll} isSyncing={isSyncing} />

          {/* Main Content View */}
          <main className="flex-1 px-4 pt-4 pb-20">
            {renderTabContent()}
          </main>

          {/* Bottom Navigation Bar */}
          <MobileBottomNav
            activeTab={activeTab}
            onSelectTab={handleTabChange}
            unreadChatsCount={totalUnreadChats}
            pendingHomeworkCount={initialHomework.filter((h) => h.status !== 'completed').length}
            isStudent={isStudent}
            isHidden={Boolean(selectedLesson || isCreateLessonOpen || isNotificationsOpen || isOnboardingTourOpen)}
          />
        </div>
      )}

      {/* ─── UNIVERSAL MODALS & BOTTOM SHEETS ─── */}

      {/* Lesson Detail Modal */}
      <LessonDetailBottomSheet
        lesson={selectedLesson}
        isOpen={Boolean(selectedLesson)}
        onClose={() => setSelectedLesson(null)}
        onLessonUpdated={handleLessonUpdated}
        onOpenChat={(partnerId) => {
          setSelectedLesson(null);
          handleTabChange('chat');
        }}
      />

      {/* Create Lesson Modal */}
      <CreateLessonBottomSheet
        isOpen={isCreateLessonOpen}
        onClose={() => setIsCreateLessonOpen(false)}
        onLessonCreated={handleLessonCreated}
        tutorProfile={tutorProfile}
        initialDate={currentWeekStart}
      />

      {/* Notifications Modal */}
      <NotificationsSheet
        isOpen={isNotificationsOpen}
        onClose={() => setIsNotificationsOpen(false)}
        initialNotifications={notifications}
        unreadCount={unreadNotificationsCount}
        onNotificationsUpdated={(newCount) => setUnreadNotificationsCount(newCount)}
      />

      {/* Interactive Premium Onboarding Tour (PC vs Mobile) */}
      {isDesktop ? (
        <DesktopOnboardingTour
          isOpen={isOnboardingTourOpen}
          onClose={() => setIsOnboardingTourOpen(false)}
          isTutor={isTutor}
          userName={user?.name}
          onNavigateTab={handleTabChange}
        />
      ) : (
        <MobileOnboardingTour
          isOpen={isOnboardingTourOpen}
          onClose={() => setIsOnboardingTourOpen(false)}
          isTutor={isTutor}
          userName={user?.name}
          onNavigateTab={handleTabChange}
        />
      )}
    </div>
    </ErrorBoundary>
  );
}
