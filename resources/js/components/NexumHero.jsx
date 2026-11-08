import React, { useState, useEffect, useRef } from 'react';
import { 
  Menu, X, LayoutDashboard, User, LogIn, Sparkles, 
  BrainCircuit, CheckCircle2, ShieldCheck, GraduationCap, 
  TrendingUp, Calendar, Award, ArrowRight, Lock, BookOpen, 
  Users, Check, FileCheck, DollarSign, ChevronDown, LogOut,
  UserPlus, MessageSquare, Target, Search
} from 'lucide-react';
import Footer from './Footer';

export default function NexumHero() {
  const [mobileMenuOpen, setMobileMenuOpen] = useState(false);
  const [registerModalOpen, setRegisterModalOpen] = useState(false);
  const [emailInput, setEmailInput] = useState('');
  const [user, setUser] = useState(typeof window !== 'undefined' ? window.EDUSFERA_USER : null);
  const [linkedAccounts, setLinkedAccounts] = useState(typeof window !== 'undefined' ? (window.EDUSFERA_LINKED_ACCOUNTS || []) : []);
  const [userMenuOpen, setUserMenuOpen] = useState(false);
  const [mobileRole, setMobileRole] = useState('tutor'); // 'tutor' | 'student'
  const [isMobileDevice, setIsMobileDevice] = useState(false);
  const userMenuRef = useRef(null);
  const videoRef = useRef(null);
  const heroSectionRef = useRef(null);

  useEffect(() => {
    if (typeof IntersectionObserver === 'undefined') return;
    const observer = new IntersectionObserver(
      ([entry]) => {
        if (!videoRef.current) return;
        if (entry.isIntersecting) {
          videoRef.current.play().catch(() => {});
        } else {
          videoRef.current.pause();
        }
      },
      { threshold: 0.05 }
    );
    if (heroSectionRef.current) {
      observer.observe(heroSectionRef.current);
    }
    return () => observer.disconnect();
  }, []);

  useEffect(() => {
    if (typeof window !== 'undefined') {
      const isChMobile = typeof navigator !== 'undefined' && navigator.userAgentData && navigator.userAgentData.mobile === true;
      const isMobileUA = typeof navigator !== 'undefined' && /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini|Mobile|Silk|HarmonyOS/i.test(navigator.userAgent);
      const isIpadTouch = typeof navigator !== 'undefined' && /Macintosh/i.test(navigator.userAgent) && navigator.maxTouchPoints > 1 && typeof screen !== 'undefined' && (screen.width <= 1024 || screen.height <= 1024);
      const detected = Boolean(window.EDUSFERA_IS_MOBILE) || Boolean(isChMobile || isMobileUA || isIpadTouch);
      setIsMobileDevice(detected);
      if (detected) {
        document.cookie = "edusfera_device=mobile; path=/; max-age=31536000; SameSite=Lax";
      } else {
        document.cookie = "edusfera_device=desktop; path=/; max-age=31536000; SameSite=Lax";
      }

      if (window.EDUSFERA_USER) {
        setUser(window.EDUSFERA_USER);
      }
      if (window.EDUSFERA_LINKED_ACCOUNTS) {
        setLinkedAccounts(window.EDUSFERA_LINKED_ACCOUNTS);
      }
    }
  }, []);

  const cabinetUrl = isMobileDevice ? '/m' : '/admin';
  const financeUrl = isMobileDevice ? '/m?tab=finance' : '/admin/tutor-subscription-page';
  const scheduleUrl = isMobileDevice ? '/m?tab=lessons' : '/admin/tutor-availability-page';
  const lessonsUrl = isMobileDevice ? '/m?tab=lessons' : '/admin/lessons';
  const messagesUrl = isMobileDevice ? '/m?tab=chat' : '/admin/messages';
  const diagnosticUrl = isMobileDevice ? '/m?tab=lessons' : '/admin/diagnostic';
  const homeworkUrl = isMobileDevice ? '/m?tab=lessons' : '/admin/homework';
  const walletUrl = isMobileDevice ? '/m?tab=finance' : '/admin/wallet';

  useEffect(() => {
    const handleClickOutside = (event) => {
      if (userMenuRef.current && !userMenuRef.current.contains(event.target)) {
        setUserMenuOpen(false);
      }
    };
    document.addEventListener('mousedown', handleClickOutside);
    return () => {
      document.removeEventListener('mousedown', handleClickOutside);
    };
  }, []);

  const handleLogout = () => {
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '/logout';
    
    const csrfInput = document.createElement('input');
    csrfInput.type = 'hidden';
    csrfInput.name = '_token';
    csrfInput.value = (typeof window !== 'undefined' && window.EDUSFERA_CSRF_TOKEN) ? window.EDUSFERA_CSRF_TOKEN : '';
    
    form.appendChild(csrfInput);
    document.body.appendChild(form);
    form.submit();
  };

  const handleSwitchAccount = (accId) => {
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = `/account/switch/${accId}`;
    
    const csrfInput = document.createElement('input');
    csrfInput.type = 'hidden';
    csrfInput.name = '_token';
    csrfInput.value = (typeof window !== 'undefined' && window.EDUSFERA_CSRF_TOKEN) ? window.EDUSFERA_CSRF_TOKEN : '';
    
    form.appendChild(csrfInput);
    document.body.appendChild(form);
    form.submit();
  };

  useEffect(() => {
    if (mobileMenuOpen || registerModalOpen) {
      document.body.style.overflow = 'hidden';
    } else {
      document.body.style.overflow = '';
    }
    return () => {
      document.body.style.overflow = '';
    };
  }, [mobileMenuOpen, registerModalOpen]);

  const handleNavClick = (target) => {
    setMobileMenuOpen(false);
    if (target.startsWith('#')) {
      const el = document.querySelector(target);
      if (el) {
        el.scrollIntoView({ behavior: 'smooth' });
        return;
      }
    }
    window.location.href = target;
  };

  const navItems = [
    { label: 'Каталог', href: '/tutors' },
    { label: 'Генератор ЦТ', href: '/generator', badge: '5 BYN' },
    { label: 'ИИ-Диагностика', href: '/diagnostic' },
    { label: 'Новости', href: '/news' },
    { label: 'Репетиторам', href: '/for-tutors', isSpecial: true },
  ];

  const subjects = [
    { name: 'Математика', gain: '+38 баллов', color: 'from-violet-500/20 to-purple-500/20', border: 'border-violet-500/30', text: 'text-violet-400', href: '/tutors?subject=Математика' },
    { name: 'Физика', gain: '+34 балла', color: 'from-blue-500/20 to-cyan-500/20', border: 'border-blue-500/30', text: 'text-blue-400', href: '/tutors?subject=Физика' },
    { name: 'Русский язык', gain: '+29 баллов', color: 'from-rose-500/20 to-pink-500/20', border: 'border-rose-500/30', text: 'text-rose-400', href: '/tutors?subject=Русский+язык' },
    { name: 'Белорусский язык', gain: '+31 балл', color: 'from-emerald-500/20 to-green-500/20', border: 'border-emerald-500/30', text: 'text-emerald-400', href: '/tutors?subject=Белорусский+язык' },
    { name: 'Английский язык', gain: '+35 баллов', color: 'from-amber-500/20 to-yellow-500/20', border: 'border-amber-500/30', text: 'text-amber-400', href: '/tutors?subject=Английский+язык' },
    { name: 'Обществоведение', gain: '+32 балла', color: 'from-indigo-500/20 to-sky-500/20', border: 'border-indigo-500/30', text: 'text-indigo-400', href: '/tutors?subject=Обществоведение' },
  ];

  return (
    <div className="w-full bg-[#010101] text-white font-sans selection:bg-[#C6FF33] selection:text-black">
      

      {/* ─── 1. HERO SECTION (WITH PHOTO/VIDEO OR VIBRANT MOBILE AURORA) ─── */}
      <section ref={heroSectionRef} className="relative min-h-screen w-full overflow-hidden select-none font-geist flex flex-col justify-between bg-gradient-to-br from-neutral-950 via-slate-900 to-black">
        {/* Desktop Video (hidden on mobile to save 14.6 MB) */}
        <video
          ref={videoRef}
          src="/videos/hero-bg.mp4"
          autoPlay
          loop
          muted
          playsInline
          preload="none"
          className="hidden md:block absolute inset-0 h-full w-full object-cover z-0 pointer-events-none"
          style={{ transform: 'translateZ(0)' }}
        />

        {/* ─── VIBRANT MOBILE BACKGROUND (High-Performance Mesh - Zero Gaussian Blur Overhead) ─── */}
        <div className="md:hidden absolute inset-0 z-0 pointer-events-none overflow-hidden">
          {/* Deep vibrant cosmic foundation with native radial glows */}
          <div 
            className="absolute inset-0"
            style={{
              background: 'radial-gradient(ellipse 95% 70% at 20% 12%, rgba(125,57,235,0.7) 0%, transparent 60%), radial-gradient(ellipse 85% 65% at 85% 30%, rgba(198,255,51,0.4) 0%, transparent 55%), radial-gradient(circle at 45% 65%, rgba(56,189,248,0.25) 0%, transparent 60%), linear-gradient(180deg, #13072b 0%, #0a0d24 55%, #010101 100%)',
              transform: 'translateZ(0)',
            }}
          />

          {/* High-Tech Cyber Pattern Overlay */}
          <div 
            className="absolute inset-0 opacity-15 bg-[radial-gradient(#C6FF33_1px,transparent_1px)] [background-size:24px_24px]" 
          />

          {/* Floating animated glowing light particles / sparkles */}
          <div className="absolute inset-0">
            <span className="absolute top-[18%] left-[12%] w-2 h-2 rounded-full bg-[#C6FF33] shadow-[0_0_12px_#C6FF33] animate-pulse" />
            <span className="absolute top-[32%] right-[16%] w-2.5 h-2.5 rounded-full bg-[#7D39EB] shadow-[0_0_14px_#7D39EB] animate-pulse" style={{ animationDelay: '1.2s' }} />
            <span className="absolute top-[58%] left-[22%] w-2 h-2 rounded-full bg-[#38BDF8] shadow-[0_0_10px_#38BDF8] animate-pulse" style={{ animationDelay: '2.4s' }} />
            <span className="absolute top-[76%] right-[14%] w-2.5 h-2.5 rounded-full bg-[#C6FF33] shadow-[0_0_14px_#C6FF33] animate-pulse" style={{ animationDelay: '3.6s' }} />
          </div>

          {/* Subtle top/bottom contrast ramps for readability */}
          <div className="absolute inset-x-0 top-0 h-28 bg-gradient-to-b from-black/60 to-transparent" />
          <div className="absolute inset-x-0 bottom-0 h-36 bg-gradient-to-t from-[#010101] via-[#010101]/70 to-transparent" />
        </div>

        {/* Desktop overlay */}
        <div className="hidden md:block absolute inset-0 bg-black/45 z-0 pointer-events-none" />

        <div className="relative z-10 flex flex-col min-h-screen justify-between">
          {/* Header Bar */}
          <nav className="flex items-center justify-between px-5 py-5 sm:px-8 sm:py-6 lg:px-12">
            <a href="/" className="flex items-center gap-2.5 text-white transition-colors">
              <svg width="28" height="28" viewBox="0 0 64 64" className="w-7 h-7 rounded-lg shadow-sm">
                <rect width="64" height="64" rx="14" fill="#7D39EB" />
                <path d="M32 10L54 32L32 54L10 32L32 10Z" fill="none" stroke="#C6FF33" strokeWidth="6" strokeLinejoin="round" />
                <path d="M32 22L42 32L32 42L22 32L32 22Z" fill="#C6FF33" />
              </svg>
              <span className="text-xl font-bold tracking-tight text-white font-rimma uppercase">edusfera</span>
            </a>

            {/* Desktop Nav */}
            <div className="hidden md:flex items-center gap-3">
              <div className="rounded-full bg-white/10 px-1.5 py-1.5 backdrop-blur-lg flex items-center gap-1 border border-white/10">
                {navItems.map((item) => (
                  <button
                    key={item.label}
                    onClick={() => handleNavClick(item.href)}
                    className={`rounded-full px-4 py-1.5 text-sm font-medium transition-all flex items-center gap-1.5 cursor-pointer ${
                      item.isSpecial 
                        ? 'bg-[#C6FF33] text-black font-extrabold shadow-sm hover:bg-[#d4ff59]' 
                        : 'text-white/80 hover:bg-white/10 hover:text-white'
                    }`}
                  >
                    <span>{item.label}</span>
                    {item.badge && (
                      <span className="text-[9px] bg-[#C6FF33] text-black px-1.5 py-0.2 rounded-full uppercase font-black">
                        {item.badge}
                      </span>
                    )}
                    {item.isSpecial && (
                      <span className="text-[9px] bg-black text-[#C6FF33] px-1.5 py-0.2 rounded-full uppercase font-black">
                        0 BYN
                      </span>
                    )}
                  </button>
                ))}
              </div>

              {user ? (
                <div className="relative" ref={userMenuRef}>
                  <button
                    type="button"
                    onClick={() => setUserMenuOpen(!userMenuOpen)}
                    className="rounded-full bg-emerald-500/20 text-emerald-300 border border-emerald-500/40 px-4 py-2 text-sm font-semibold hover:bg-emerald-500/30 transition-all flex items-center gap-2.5 cursor-pointer backdrop-blur-md shadow-sm focus:outline-none"
                    aria-expanded={userMenuOpen}
                  >
                    <span className="relative flex h-2.5 w-2.5">
                      <span className="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                      <span className="relative inline-flex rounded-full h-2.5 w-2.5 bg-emerald-500"></span>
                    </span>
                    <span className="truncate max-w-[120px]">{user.name || 'Кабинет'}</span>
                    <span className="text-[10px] px-2 py-0.5 rounded-full bg-white/10 text-emerald-200 font-bold border border-white/10">
                      {user.role_label || (user.role === 'tutor' ? 'Репетитор' : user.role === 'admin' ? 'Администратор' : user.role === 'parent' ? 'Родитель' : 'Ученик')}
                    </span>
                    <ChevronDown className={`w-3.5 h-3.5 text-emerald-400 transition-transform ${userMenuOpen ? 'rotate-180' : ''}`} />
                  </button>

                  {/* Dropdown Menu */}
                  {userMenuOpen && (
                    <div className="absolute right-0 top-full mt-2 w-80 rounded-2xl bg-[#121214]/95 border border-white/10 p-2 text-white shadow-2xl backdrop-blur-xl z-50 animate-in fade-in slide-in-from-top-2 duration-150">
                      {/* Current User Card */}
                      <div className="flex items-center gap-3 p-2.5 rounded-xl bg-white/5 border border-white/5 mb-1.5">
                        <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-emerald-500/20 border border-emerald-500/30 text-emerald-400 font-bold text-sm">
                          {user.name ? user.name.charAt(0).toUpperCase() : 'U'}
                        </div>
                        <div className="min-w-0 flex-1">
                          <p className="text-sm font-bold text-white truncate">{user.name}</p>
                          <div className="flex items-center gap-1.5 mt-0.5">
                            <span className="inline-block text-[10px] font-bold px-2 py-0.5 rounded-md bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">
                              {user.role_label || (user.role === 'tutor' ? 'Репетитор' : user.role === 'admin' ? 'Администратор' : user.role === 'parent' ? 'Родитель' : 'Ученик')}
                            </span>
                            <span className="text-xs text-neutral-400 truncate">{user.email}</span>
                          </div>
                        </div>
                      </div>

                      {/* Unified Dashboard Link */}
                      <div className="space-y-0.5">
                        <a
                          href={cabinetUrl}
                          className="flex items-center gap-2.5 px-3 py-2 text-sm font-semibold text-white rounded-xl bg-gradient-to-r from-[#7D39EB]/30 to-[#C6FF33]/20 border border-white/10 hover:from-[#7D39EB]/50 hover:to-[#C6FF33]/30 transition-all"
                        >
                          <span className="text-base">🚀</span>
                          <span>Личный кабинет</span>
                        </a>

                        {/* Role-Specific Quick Actions */}
                        {user.role === 'tutor' && (
                          <>
                            <a
                              href={financeUrl}
                              className="flex items-center gap-2.5 px-3 py-2 text-sm font-medium text-neutral-200 hover:text-white rounded-xl hover:bg-white/10 transition-colors"
                            >
                              <Sparkles className="w-4 h-4 text-amber-400" />
                              <span>Управление тарифом</span>
                            </a>
                            <a
                              href={scheduleUrl}
                              className="flex items-center gap-2.5 px-3 py-2 text-sm font-medium text-neutral-200 hover:text-white rounded-xl hover:bg-white/10 transition-colors"
                            >
                              <Calendar className="w-4 h-4 text-emerald-400" />
                              <span>Расписание</span>
                            </a>
                            <a
                              href={lessonsUrl}
                              className="flex items-center gap-2.5 px-3 py-2 text-sm font-medium text-neutral-200 hover:text-white rounded-xl hover:bg-white/10 transition-colors"
                            >
                              <BookOpen className="w-4 h-4 text-blue-400" />
                              <span>Мои занятия</span>
                            </a>
                          </>
                        )}

                        {(user.role === 'student' || user.role === 'parent') && (
                          <>
                            <a
                              href={lessonsUrl}
                              className="flex items-center gap-2.5 px-3 py-2 text-sm font-medium text-neutral-200 hover:text-white rounded-xl hover:bg-white/10 transition-colors"
                            >
                              <BookOpen className="w-4 h-4 text-blue-400" />
                              <span>Мои занятия</span>
                            </a>
                            <a
                              href={diagnosticUrl}
                              className="flex items-center gap-2.5 px-3 py-2 text-sm font-medium text-neutral-200 hover:text-white rounded-xl hover:bg-white/10 transition-colors"
                            >
                              <GraduationCap className="w-4 h-4 text-emerald-400" />
                              <span>ИИ-Диагностика</span>
                            </a>
                            <a
                              href={homeworkUrl}
                              className="flex items-center gap-2.5 px-3 py-2 text-sm font-medium text-neutral-200 hover:text-white rounded-xl hover:bg-white/10 transition-colors"
                            >
                              <FileCheck className="w-4 h-4 text-amber-400" />
                              <span>Домашние задания</span>
                            </a>
                            <a
                              href={walletUrl}
                              className="flex items-center gap-2.5 px-3 py-2 text-sm font-medium text-neutral-200 hover:text-white rounded-xl hover:bg-white/10 transition-colors"
                            >
                              <DollarSign className="w-4 h-4 text-[#C6FF33]" />
                              <span>Учёт денег</span>
                            </a>
                          </>
                        )}

                        {user.role === 'admin' && (
                          <>
                            <a
                              href="/admin/users"
                              className="flex items-center gap-2.5 px-3 py-2 text-sm font-medium text-neutral-200 hover:text-white rounded-xl hover:bg-white/10 transition-colors"
                            >
                              <Users className="w-4 h-4 text-blue-400" />
                              <span>Пользователи</span>
                            </a>
                            <a
                              href="/admin/saa-s-management"
                              className="flex items-center gap-2.5 px-3 py-2 text-sm font-medium text-neutral-200 hover:text-white rounded-xl hover:bg-white/10 transition-colors"
                            >
                              <Sparkles className="w-4 h-4 text-violet-400" />
                              <span>Управление SaaS</span>
                            </a>
                          </>
                        )}

                        <a
                          href={messagesUrl}
                          className="flex items-center gap-2.5 px-3 py-2 text-sm font-medium text-neutral-200 hover:text-white rounded-xl hover:bg-white/10 transition-colors"
                        >
                          <MessageSquare className="w-4 h-4 text-violet-400" />
                          <span>Сообщения</span>
                        </a>
                      </div>

                      {/* Linked Accounts (Switching) */}
                      {linkedAccounts && linkedAccounts.length > 0 && (
                        <div className="mt-2 pt-2 border-t border-white/10">
                          <p className="px-3 py-1 text-[11px] font-bold uppercase tracking-wider text-neutral-400">
                            Переключить аккаунт
                          </p>
                          <div className="space-y-0.5 mt-1">
                            {linkedAccounts.map((acc) => (
                              <button
                                type="button"
                                key={acc.id}
                                onClick={() => handleSwitchAccount(acc.id)}
                                className="w-full flex items-center justify-between px-3 py-2 text-sm font-medium text-neutral-200 hover:text-white rounded-xl hover:bg-white/10 transition-colors cursor-pointer text-left"
                              >
                                <div className="flex items-center gap-2.5 min-w-0">
                                  <div className="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-white/10 text-xs font-bold text-neutral-300">
                                    {acc.name ? acc.name.charAt(0).toUpperCase() : 'U'}
                                  </div>
                                  <span className="truncate text-xs font-semibold">{acc.name}</span>
                                </div>
                                <span className="text-[10px] px-2 py-0.5 rounded-md bg-white/10 text-neutral-400 font-medium shrink-0">
                                  {acc.role === 'tutor' ? 'Репетитор' : acc.role === 'admin' ? 'Администратор' : acc.role === 'parent' ? 'Родитель' : 'Ученик'}
                                </span>
                              </button>
                            ))}
                          </div>
                        </div>
                      )}

                      {/* Add Account & Logout */}
                      <div className="mt-2 pt-2 border-t border-white/10 space-y-0.5">
                        <a
                          href="/account/add"
                          className="flex items-center gap-2.5 px-3 py-2 text-sm font-medium text-neutral-200 hover:text-white rounded-xl hover:bg-white/10 transition-colors"
                        >
                          <UserPlus className="w-4 h-4 text-blue-400" />
                          <span>+ Добавить аккаунт</span>
                        </a>

                        <button
                          type="button"
                          onClick={handleLogout}
                          className="w-full flex items-center gap-2.5 px-3 py-2 text-sm font-medium text-red-400 hover:text-red-300 rounded-xl hover:bg-red-500/10 transition-colors cursor-pointer text-left"
                        >
                          <LogOut className="w-4 h-4 text-red-400" />
                          <span>Выйти</span>
                        </button>
                      </div>
                    </div>
                  )}
                </div>
              ) : (
                <div className="flex items-center gap-2">
                  <a
                    href="/login"
                    className="rounded-full px-4 py-2 text-sm font-medium text-white/80 hover:text-white hover:bg-white/10 transition-all"
                  >
                    Войти
                  </a>
                  <a
                    href="/register"
                    className="rounded-full bg-gradient-to-r from-violet-600 to-indigo-600 text-white font-bold px-4 py-2 text-sm hover:from-violet-500 hover:to-indigo-500 transition-all shadow-md"
                  >
                    Регистрация
                  </a>
                  <a
                    href="/diagnostic"
                    className="rounded-full bg-[#C6FF33] text-black font-extrabold px-4 py-2 text-sm hover:bg-[#d4ff59] transition-all shadow-md"
                  >
                    ИИ-Диагностика
                  </a>
                </div>
              )}
            </div>

            {/* Mobile Hamburger */}
            <button
              onClick={() => setMobileMenuOpen(!mobileMenuOpen)}
              aria-label="Главное меню"
              className="md:hidden relative z-50 h-10 w-10 rounded-full bg-white/10 backdrop-blur-lg flex items-center justify-center text-white transition-colors focus:outline-none cursor-pointer border border-white/10"
            >
              {mobileMenuOpen ? <X className="w-5 h-5" /> : <Menu className="w-5 h-5" />}
            </button>
          </nav>

          {/* Mobile Drawer */}
          {mobileMenuOpen && (
            <div className="fixed inset-0 z-40 bg-black/95 backdrop-blur-2xl flex flex-col px-6 pt-24 pb-8 space-y-4 md:hidden overflow-y-auto overscroll-contain safe-top safe-bottom" style={{ WebkitOverflowScrolling: 'touch' }}>
              {navItems.map((item) => (
                <button
                  key={item.label}
                  onClick={() => handleNavClick(item.href)}
                  className="text-left text-lg font-medium text-white/90 py-2.5 border-b border-white/10 active:text-[#C6FF33] transition-colors"
                >
                  {item.label}
                </button>
              ))}

              {user ? (
                <div className="mt-4 pt-4 border-t border-white/10 space-y-2.5">
                  <div className="flex items-center gap-3 p-3 rounded-xl bg-white/5 border border-white/10">
                    <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-emerald-500/20 text-emerald-400 font-bold text-sm">
                      {user.name ? user.name.charAt(0).toUpperCase() : 'U'}
                    </div>
                    <div className="min-w-0 flex-1">
                      <p className="text-sm font-bold text-white truncate">{user.name}</p>
                      <span className="inline-block text-[10px] font-bold px-2 py-0.5 rounded-md bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">
                        {user.role_label || (user.role === 'tutor' ? 'Репетитор' : user.role === 'admin' ? 'Администратор' : user.role === 'parent' ? 'Родитель' : 'Ученик')}
                      </span>
                    </div>
                  </div>

                  <a
                    href={cabinetUrl}
                    className="flex items-center justify-center gap-2.5 rounded-xl bg-gradient-to-r from-[#7D39EB]/40 to-[#C6FF33]/30 border border-white/20 px-4 py-3 text-sm font-semibold text-white"
                  >
                    <span>🚀 Личный кабинет</span>
                  </a>

                  {user.role === 'tutor' && (
                    <div className="space-y-1">
                      <a
                        href={financeUrl}
                        className="flex items-center gap-2.5 rounded-xl bg-white/5 px-4 py-2.5 text-sm font-medium text-white"
                      >
                        <Sparkles className="w-4 h-4 text-amber-400" />
                        <span>Управление тарифом</span>
                      </a>
                      <a
                        href={scheduleUrl}
                        className="flex items-center gap-2.5 rounded-xl bg-white/5 px-4 py-2.5 text-sm font-medium text-white"
                      >
                        <Calendar className="w-4 h-4 text-emerald-400" />
                        <span>Расписание</span>
                      </a>
                      <a
                        href={lessonsUrl}
                        className="flex items-center gap-2.5 rounded-xl bg-white/5 px-4 py-2.5 text-sm font-medium text-white"
                      >
                        <BookOpen className="w-4 h-4 text-blue-400" />
                        <span>Мои занятия</span>
                      </a>
                    </div>
                  )}

                  {(user.role === 'student' || user.role === 'parent') && (
                    <div className="space-y-1">
                      <a
                        href={lessonsUrl}
                        className="flex items-center gap-2.5 rounded-xl bg-white/5 px-4 py-2.5 text-sm font-medium text-white"
                      >
                        <BookOpen className="w-4 h-4 text-blue-400" />
                        <span>Мои занятия</span>
                      </a>
                      <a
                        href={diagnosticUrl}
                        className="flex items-center gap-2.5 rounded-xl bg-white/5 px-4 py-2.5 text-sm font-medium text-white"
                      >
                        <GraduationCap className="w-4 h-4 text-emerald-400" />
                        <span>ИИ-Диагностика</span>
                      </a>
                      <a
                        href={homeworkUrl}
                        className="flex items-center gap-2.5 rounded-xl bg-white/5 px-4 py-2.5 text-sm font-medium text-white"
                      >
                        <FileCheck className="w-4 h-4 text-amber-400" />
                        <span>Домашние задания</span>
                      </a>
                    </div>
                  )}

                  <a
                    href={messagesUrl}
                    className="flex items-center gap-2.5 rounded-xl bg-white/5 px-4 py-2.5 text-sm font-semibold text-white"
                  >
                    <MessageSquare className="w-4 h-4 text-violet-400" />
                    <span>Сообщения</span>
                  </a>

                  {linkedAccounts && linkedAccounts.length > 0 && (
                    <div className="space-y-1.5 pt-2">
                      <p className="text-[11px] font-bold uppercase tracking-wider text-neutral-400 px-1">
                        Сменить аккаунт:
                      </p>
                      {linkedAccounts.map((acc) => (
                        <button
                          type="button"
                          key={acc.id}
                          onClick={() => handleSwitchAccount(acc.id)}
                          className="w-full flex items-center justify-between rounded-xl bg-white/5 px-4 py-2 text-sm text-neutral-200 cursor-pointer text-left"
                        >
                          <span className="font-medium">{acc.name}</span>
                          <span className="text-xs text-neutral-400">
                            {acc.role === 'tutor' ? 'Репетитор' : acc.role === 'admin' ? 'Администратор' : acc.role === 'parent' ? 'Родитель' : 'Ученик'}
                          </span>
                        </button>
                      ))}
                    </div>
                  )}

                  <div className="pt-2 border-t border-white/10 space-y-1">
                    <a
                      href="/account/add"
                      className="flex items-center gap-2.5 rounded-xl bg-white/5 px-4 py-2.5 text-sm font-medium text-neutral-300"
                    >
                      <UserPlus className="w-4 h-4 text-blue-400" />
                      <span>+ Добавить аккаунт</span>
                    </a>
                    <button
                      type="button"
                      onClick={handleLogout}
                      className="w-full flex items-center gap-2.5 rounded-xl bg-red-500/10 px-4 py-2.5 text-sm font-medium text-red-400 cursor-pointer text-left"
                    >
                      <LogOut className="w-4 h-4 text-red-400" />
                      <span>Выйти</span>
                    </button>
                  </div>
                </div>
              ) : (
                <div className="mt-4 pt-4 border-t border-white/10 space-y-3">
                  <a
                    href="/login"
                    className="block text-center rounded-xl bg-white/10 py-3 text-sm font-semibold text-white"
                  >
                    Войти
                  </a>
                  <a
                    href="/register"
                    className="block text-center rounded-xl bg-gradient-to-r from-violet-600 to-indigo-600 py-3 text-sm font-bold text-white shadow-md"
                  >
                    Регистрация
                  </a>
                  <a
                    href="/diagnostic"
                    className="block text-center rounded-xl bg-[#C6FF33] py-3 text-sm font-extrabold text-black shadow-md"
                  >
                    ИИ-Диагностика
                  </a>
                </div>
              )}
            </div>
          )}

          {/* Mobile-Only Floating 3D Hero Graphic (Harmoniously placed in the airy space between Header and Content) */}
          <div className="lg:hidden w-full flex-1 flex items-center justify-center px-4 pt-2 pb-4 my-auto pointer-events-none select-none">
            <div className="relative w-full max-w-[360px] xs:max-w-[400px]">
              {/* Atmospheric Soft Glow Aura */}
              <div 
                className="absolute inset-0 bg-gradient-to-r from-violet-600/30 via-[#C6FF33]/15 to-sky-400/20 blur-3xl rounded-full opacity-70 pointer-events-none"
                aria-hidden="true"
              />

              <picture>
                <source srcSet="/images/hero-student-illustration.webp" type="image/webp" />
                <img
                  src="/images/hero-student-illustration.png"
                  alt="Edusfera — Интерактивный онлайн-класс"
                  width="1536"
                  height="1024"
                  className="relative z-10 w-full h-auto object-contain drop-shadow-[0_15px_35px_rgba(0,0,0,0.5)]"
                  style={{
                    maskImage: 'linear-gradient(to bottom, black 82%, transparent 100%)',
                    WebkitMaskImage: 'linear-gradient(to bottom, black 82%, transparent 100%)',
                  }}
                  loading="eager"
                  fetchPriority="high"
                />
              </picture>
            </div>
          </div>

          {/* Main Hero Content */}
          <main className="mt-auto flex flex-col lg:flex-row lg:items-end lg:justify-between px-5 pb-8 sm:px-8 sm:pb-12 lg:px-12 lg:pb-16 gap-6 sm:gap-8">
            <div className="max-w-2xl">
              <div className="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-white/10 border border-white/15 text-white/90 font-medium text-xs mb-4 backdrop-blur-md">
                <span className="w-2 h-2 rounded-full bg-[#C6FF33] animate-pulse"></span>
                <span>Платформа для репетиторов, учеников и родителей</span>
              </div>

              <h1 className="text-3xl sm:text-4xl lg:text-[3.25rem] font-semibold leading-[1.1] tracking-tight text-white drop-shadow-md">
                Онлайн-уроки без комиссий, посредников и переплат
              </h1>

              <p className="mt-4 text-slate-300 text-sm sm:text-base leading-relaxed max-w-xl">
                Преподаватели получают 100% оплаты без вычетов сервиса. Ученики готовятся к ЦТ и ЦЭ 2026 без стресса и зубрёжки. Родители платят напрямую репетитору и видят реальный результат каждого урока.
              </p>

              {user ? (
                <div className="mt-5 sm:mt-8">
                  <a
                    href={cabinetUrl}
                    className="inline-flex items-center justify-center gap-2.5 rounded-full bg-white text-black font-bold px-6 py-3 sm:px-7 sm:py-3.5 text-sm sm:text-base hover:bg-white/90 transition-all cursor-pointer shadow-lg hover:scale-[1.02]"
                  >
                    <LayoutDashboard className="w-4 h-4 sm:w-5 sm:h-5 text-violet-600 shrink-0" />
                    <span>В Личный Кабинет</span>
                    <span className="text-[10px] px-2 py-0.5 rounded-md bg-black/10 text-black font-extrabold shrink-0">
                      {user.role_label || (user.role === 'tutor' ? 'Репетитор' : user.role === 'admin' ? 'Админ' : 'Кабинет')}
                    </span>
                    <ArrowRight className="w-4 h-4 text-black shrink-0 hidden sm:inline" />
                  </a>
                </div>
              ) : (
                <div className="mt-6 sm:mt-8 flex flex-col sm:flex-row items-stretch sm:items-center gap-3.5">
                  <a
                    href="/tutors"
                    className="inline-flex items-center justify-center gap-2.5 rounded-full bg-[#C6FF33] text-black font-extrabold px-7 py-3.5 text-sm sm:text-base hover:bg-[#d4ff59] transition-all shadow-[0_0_25px_rgba(198,255,51,0.3)] hover:scale-[1.02]"
                  >
                    <Search className="w-4 h-4 text-black" />
                    <span>Найти репетитора</span>
                  </a>
                  <a
                    href="/for-tutors"
                    className="inline-flex items-center justify-center gap-2 rounded-full bg-white/10 hover:bg-white/20 text-white font-semibold px-6 py-3.5 text-sm sm:text-base border border-white/15 backdrop-blur-md transition-all"
                  >
                    <span>Преподавателям (7 дней 0 BYN) →</span>
                  </a>
                  <a
                    href="/diagnostic"
                    className="inline-flex items-center justify-center gap-2 rounded-full bg-white/5 hover:bg-white/10 text-slate-300 hover:text-white font-medium px-5 py-3.5 text-xs sm:text-sm border border-white/10 transition-all"
                  >
                    <Sparkles className="w-3.5 h-3.5 text-[#C6FF33]" />
                    <span>Тест знаний (0 BYN)</span>
                  </a>
                </div>
              )}
            </div>

            {/* ─── MOBILE ONLY: SEGMENTED ROLE SWITCHER (Variant 1) ─── */}
            <div className="lg:hidden w-full space-y-3 mt-4">
              {/* Segmented Control Bar */}
              <div className="grid grid-cols-2 p-1 rounded-2xl bg-white/[0.08] border border-white/15 backdrop-blur-md text-xs font-semibold">
                <button
                  type="button"
                  onClick={() => setMobileRole('tutor')}
                  className={`py-2.5 px-3 rounded-xl transition-all flex items-center justify-center gap-1.5 cursor-pointer ${
                    mobileRole === 'tutor'
                      ? 'bg-[#C6FF33] text-black font-extrabold shadow-md'
                      : 'text-white/70 hover:text-white'
                  }`}
                >
                  <span>👨‍🏫 Репетиторам</span>
                  <span className={`text-[9px] px-1.5 py-0.2 rounded-full font-bold ${
                    mobileRole === 'tutor' ? 'bg-black text-[#C6FF33]' : 'bg-[#C6FF33]/20 text-[#C6FF33]'
                  }`}>
                    0%
                  </span>
                </button>
                <button
                  type="button"
                  onClick={() => setMobileRole('student')}
                  className={`py-2.5 px-3 rounded-xl transition-all flex items-center justify-center gap-1.5 cursor-pointer ${
                    mobileRole === 'student'
                      ? 'bg-white text-black font-extrabold shadow-md'
                      : 'text-white/70 hover:text-white'
                  }`}
                >
                  <span>🎓 Ученикам</span>
                </button>
              </div>

              {/* Dynamic Role Card */}
              {mobileRole === 'tutor' ? (
                <div className="rounded-2xl bg-gradient-to-br from-white/[0.12] via-white/[0.06] to-[#C6FF33]/[0.08] border border-[#C6FF33]/30 p-5 backdrop-blur-md space-y-3.5 shadow-xl animate-in fade-in duration-200">
                  <div className="flex items-center justify-between">
                    <div className="text-xl font-extrabold text-white tracking-tight">
                      100% денег за уроки — вам
                    </div>
                    <span className="text-[10px] font-mono font-bold px-2 py-0.5 rounded-full bg-[#C6FF33]/20 text-[#C6FF33] border border-[#C6FF33]/30">
                      0% комиссии
                    </span>
                  </div>

                  <ul className="space-y-1.5 text-xs text-slate-200">
                    <li className="flex items-center gap-2">
                      <CheckCircle2 className="w-3.5 h-3.5 text-[#C6FF33] shrink-0" />
                      <span>Видеокласс без лимитов времени (SFU)</span>
                    </li>
                    <li className="flex items-center gap-2">
                      <CheckCircle2 className="w-3.5 h-3.5 text-[#C6FF33] shrink-0" />
                      <span>Авто-чеки для налога на профдоход (НПД)</span>
                    </li>
                    <li className="flex items-center gap-2">
                      <CheckCircle2 className="w-3.5 h-3.5 text-[#C6FF33] shrink-0" />
                      <span>Гостевой вход ученика в 1 клик без регистрации</span>
                    </li>
                  </ul>

                  <div className="pt-2.5 border-t border-white/10 flex items-center justify-between gap-3">
                    <div className="text-[11px] text-slate-300">
                      Пробный период: <strong className="text-[#C6FF33]">7 дней бесплатно</strong>
                    </div>
                    <a
                      href="/for-tutors"
                      className="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-[#C6FF33] hover:bg-[#d4ff59] text-black font-extrabold text-xs shadow-md transition-all shrink-0"
                    >
                      <span>Начать 0 BYN</span>
                      <ArrowRight className="w-3 h-3 text-black" />
                    </a>
                  </div>
                </div>
              ) : (
                <div className="rounded-2xl bg-gradient-to-br from-white/[0.12] via-white/[0.06] to-sky-500/[0.08] border border-sky-400/30 p-5 backdrop-blur-md space-y-3.5 shadow-xl animate-in fade-in duration-200">
                  <div className="flex items-center justify-between">
                    <div className="text-xl font-extrabold text-white tracking-tight">
                      Прямая оплата без наценок
                    </div>
                    <span className="text-[10px] font-mono font-bold px-2 py-0.5 rounded-full bg-sky-500/20 text-sky-300 border border-sky-400/30">
                      Без наценок
                    </span>
                  </div>

                  <ul className="space-y-1.5 text-xs text-slate-200">
                    <li className="flex items-center gap-2">
                      <CheckCircle2 className="w-3.5 h-3.5 text-sky-400 shrink-0" />
                      <span>Проверенные дипломы и рейтинг репетиторов</span>
                    </li>
                    <li className="flex items-center gap-2">
                      <CheckCircle2 className="w-3.5 h-3.5 text-sky-400 shrink-0" />
                      <span>Подготовка по стандартам ЦТ и ЦЭ 2026</span>
                    </li>
                    <li className="flex items-center gap-2">
                      <CheckCircle2 className="w-3.5 h-3.5 text-sky-400 shrink-0" />
                      <span>Оплата напрямую преподавателю (карта, ЕРИП)</span>
                    </li>
                  </ul>

                  <div className="pt-2.5 border-t border-white/10 flex items-center justify-between gap-3">
                    <div className="text-[11px] text-slate-300">
                      ИИ-диагностика: <strong className="text-white">15 мин (0 BYN)</strong>
                    </div>
                    <a
                      href="/tutors"
                      className="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-white hover:bg-slate-100 text-black font-extrabold text-xs shadow-md transition-all shrink-0"
                    >
                      <span>Найти репетитора</span>
                      <ArrowRight className="w-3 h-3 text-black" />
                    </a>
                  </div>
                </div>
              )}
            </div>

            {/* ─── DESKTOP ONLY: TWO STATIC CARDS (HIDDEN ON MOBILE) ─── */}
            <div className="hidden lg:flex flex-row lg:w-auto lg:gap-5 w-full">
              {/* Card 1: Для репетиторов и преподавателей */}
              <div className="rounded-2xl bg-white/10 backdrop-blur-lg p-5 sm:p-6 sm:w-72 flex flex-col justify-between border border-white/10 shadow-xl">
                <div>
                  <div className="flex items-center justify-between gap-2 mb-2">
                    <span className="text-[11px] font-bold uppercase tracking-wider text-[#C6FF33]">Преподавателям</span>
                    <span className="text-[10px] px-2 py-0.5 rounded-full bg-[#C6FF33]/20 text-[#C6FF33] border border-[#C6FF33]/30 font-bold">0% комиссии</span>
                  </div>
                  <div className="text-2xl sm:text-3xl font-extrabold tracking-tight text-white">
                    100% вам
                  </div>
                  <p className="text-xs sm:text-sm leading-relaxed mt-2 text-slate-200">
                    Все деньги за уроки остаются у вас. Видеокласс без лимитов времени и чеки для налога (НПД) в 1 клик.
                  </p>
                </div>
                <div className="mt-4 pt-3 border-t border-white/10 flex items-center justify-between text-xs text-slate-300">
                  <span>Пробный период:</span>
                  <span className="font-bold text-[#C6FF33]">7 дней бесплатно</span>
                </div>
              </div>

              {/* Card 2: Для учеников и родителей */}
              <div className="rounded-2xl bg-white/10 backdrop-blur-lg p-5 sm:p-6 sm:w-72 flex flex-col justify-between border border-white/10 shadow-xl">
                <div>
                  <div className="flex items-center justify-between gap-2 mb-2">
                    <span className="text-[11px] font-bold uppercase tracking-wider text-sky-400">Ученикам и родителям</span>
                    <span className="text-[10px] px-2 py-0.5 rounded-full bg-sky-500/20 text-sky-300 border border-sky-400/30 font-bold">Без наценок</span>
                  </div>
                  <div className="text-2xl sm:text-3xl font-extrabold tracking-tight text-white">
                    Прямая оплата
                  </div>
                  <p className="text-xs sm:text-sm leading-relaxed mt-2 text-slate-200">
                    Оплачивайте уроки напрямую репетитору (карта, ЕРИП). Проверенные дипломы и понятные отчёты об успехах ребенка.
                  </p>
                </div>
                <div className="mt-4 pt-3 border-t border-white/10 flex items-center justify-between text-xs text-slate-300">
                  <span>Тест знаний:</span>
                  <span className="font-bold text-white">15 мин (0 BYN)</span>
                </div>
              </div>
            </div>
          </main>
        </div>
      </section>

      {/* ─── 2. HOW IT WORKS SECTION (ПОШАГОВАЯ СХЕМА) ─── */}
      <section id="how-it-works" style={{ contentVisibility: 'auto', containIntrinsicSize: '1px 500px' }} className="py-20 px-5 sm:px-8 lg:px-12 max-w-7xl mx-auto border-t border-slate-800/60">
        <div className="text-center max-w-3xl mx-auto space-y-4 mb-16">
          <div className="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-slate-900 border border-slate-800 text-xs font-bold text-[#C6FF33]">
            <BrainCircuit className="w-4 h-4 text-[#C6FF33]" />
            Как устроена Edusfera
          </div>
          <h2 className="text-3xl sm:text-4xl font-extrabold tracking-tight text-white">
            Всё для удобных занятий в 3 простых шага
          </h2>
          <p className="text-slate-400 text-sm sm:text-base leading-relaxed">
            Понятный и честный процесс для преподавателей, учеников и их родителей.
          </p>
        </div>

        <div className="grid grid-cols-1 md:grid-cols-3 gap-8 relative">
          {/* Step 1 */}
          <div className="rounded-3xl bg-slate-900/60 border border-slate-800 p-8 space-y-5 hover:border-violet-500/40 transition-all group">
            <div className="w-14 h-14 rounded-2xl bg-violet-600/20 border border-violet-500/30 flex items-center justify-center text-violet-400 font-extrabold text-xl group-hover:scale-110 transition-transform">
              01
            </div>
            <h3 className="text-xl font-bold text-white">Проверка знаний за 15 минут</h3>
            <p className="text-slate-400 text-sm leading-relaxed">
              Быстрый онлайн-тест сразу показывает реальный уровень по темам ЦТ и ЦЭ 2026 и слабые места, которые нужно подтянуть.
            </p>
            <div className="pt-2 flex items-center gap-2 text-xs font-bold text-[#C6FF33]">
              <span>Бесплатно и без регистрации</span>
              <CheckCircle2 className="w-4 h-4" />
            </div>
          </div>

          {/* Step 2 */}
          <div className="rounded-3xl bg-slate-900/60 border border-slate-800 p-8 space-y-5 hover:border-lime-500/40 transition-all group">
            <div className="w-14 h-14 rounded-2xl bg-[#C6FF33]/20 border border-[#C6FF33]/30 flex items-center justify-center text-[#C6FF33] font-extrabold text-xl group-hover:scale-110 transition-transform">
              02
            </div>
            <h3 className="text-xl font-bold text-white">Подбор проверенного репетитора</h3>
            <p className="text-slate-400 text-sm leading-relaxed">
              Смотрите видео-визитки, опыт и отзывы. Договаривайтесь об уроках и оплачивайте напрямую преподавателю без скрытых наценок.
            </p>
            <div className="pt-2 flex items-center gap-2 text-xs font-bold text-lime-400">
              <span>Дипломы проверены модерацией</span>
              <ShieldCheck className="w-4 h-4" />
            </div>
          </div>

          {/* Step 3 */}
          <div className="rounded-3xl bg-slate-900/60 border border-slate-800 p-8 space-y-5 hover:border-sky-500/40 transition-all group">
            <div className="w-14 h-14 rounded-2xl bg-sky-600/20 border border-sky-500/30 flex items-center justify-center text-sky-400 font-extrabold text-xl group-hover:scale-110 transition-transform">
              03
            </div>
            <h3 className="text-xl font-bold text-white">Онлайн-класс и отчёты после урока</h3>
            <p className="text-slate-400 text-sm leading-relaxed">
              Уроки проходят на интерактивной доске с видеосвязью прямо в браузере. Родители видят понятный отчёт об успехах ребенка.
            </p>
            <div className="pt-2 flex items-center gap-2 text-xs font-bold text-sky-400">
              <span>Без установки программ • Zoom не нужен</span>
              <TrendingUp className="w-4 h-4" />
            </div>
          </div>
        </div>
      </section>

      {/* ─── 3. INTERACTIVE DASHBOARD PREVIEW ─── */}
      <section style={{ contentVisibility: 'auto', containIntrinsicSize: '1px 600px' }} className="py-16 px-5 sm:px-8 lg:px-12 max-w-7xl mx-auto">
        <div className="rounded-3xl bg-gradient-to-r from-violet-950/60 via-slate-900 to-indigo-950/60 border border-slate-800 p-8 sm:p-12 relative overflow-hidden">
          <div className="grid grid-cols-1 lg:grid-cols-2 gap-10 items-center">
            
            <div className="space-y-6">
              <div className="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-violet-500/20 border border-violet-400/30 text-violet-300 font-bold text-xs">
                <GraduationCap className="w-4 h-4 text-[#C6FF33]" />
                Личный Кабинет Обучения
              </div>
              <h2 className="text-3xl sm:text-4xl font-extrabold text-white leading-tight">
                Вся динамика подготовки перед глазами
              </h2>
              <p className="text-slate-300 text-sm sm:text-base leading-relaxed">
                Забудьте о хаотичных тетрадях. В кабинете Edusfera фиксируются домашние задания, графики занятий, записи уроков и прозрачный учёт проведённых занятий.
              </p>

              <div className="space-y-3 pt-2">
                <div className="flex items-center gap-3 text-sm text-slate-200">
                  <div className="w-6 h-6 rounded-full bg-emerald-500/20 text-emerald-400 flex items-center justify-center font-bold">✓</div>
                  <span>Автоматический отсчёт дней до экзаменов ЦТ и ЦЭ</span>
                </div>
                <div className="flex items-center gap-3 text-sm text-slate-200">
                  <div className="w-6 h-6 rounded-full bg-emerald-500/20 text-emerald-400 flex items-center justify-center font-bold">✓</div>
                  <span>Контроль выполнения домашки после каждого урока</span>
                </div>
                <div className="flex items-center gap-3 text-sm text-slate-200">
                  <div className="w-6 h-6 rounded-full bg-emerald-500/20 text-emerald-400 flex items-center justify-center font-bold">✓</div>
                  <span>Прямая оплата преподавателю (ЕРИП / на карту) без участия платформы</span>
                </div>
              </div>

              <div className="pt-4">
                <a
                  href="/tutors"
                  className="inline-flex items-center gap-2 px-6 py-3.5 rounded-full bg-[#C6FF33] text-black font-extrabold text-sm hover:bg-[#b8f520] transition-colors cursor-pointer"
                >
                  <span>Выбрать репетитора в каталоге</span>
                  <ArrowRight className="w-4 h-4" />
                </a>
              </div>
            </div>

            {/* Visual Dashboard Card Mockup */}
            <div className="space-y-4 bg-slate-950/80 border border-slate-800 rounded-2xl p-6 shadow-2xl">
              <div className="flex items-center justify-between pb-4 border-b border-slate-800">
                <div className="flex items-center gap-3">
                  <div className="w-10 h-10 rounded-xl bg-violet-600 flex items-center justify-center font-bold text-white">
                    МК
                  </div>
                  <div>
                    <h4 className="text-sm font-bold text-white">Максим Ковалев</h4>
                    <p className="text-xs text-slate-400">Цель: Математика ЦЭ (80+)</p>
                  </div>
                </div>
                <span className="px-3 py-1 rounded-full bg-emerald-500/20 text-emerald-400 text-xs font-bold border border-emerald-500/30">
                  Прогноз: 92 балла
                </span>
              </div>

              {/* Progress bar */}
              <div className="space-y-2 pt-2">
                <div className="flex justify-between text-xs text-slate-400 font-bold">
                  <span>Старт: 45 баллов</span>
                  <span className="text-white">Готовность 85%</span>
                </div>
                <div className="w-full h-3 rounded-full bg-slate-800 overflow-hidden">
                  <div className="h-full bg-gradient-to-r from-violet-600 to-[#C6FF33] w-[85%] rounded-full"></div>
                </div>
              </div>

              {/* Weak topics widget mockup */}
              <div className="pt-4 space-y-2">
                <p className="text-xs font-bold uppercase tracking-wider text-slate-400">Слабые темы под контролем:</p>
                <div className="grid grid-cols-2 gap-2 text-xs">
                  <div className="p-3 rounded-xl bg-slate-900 border border-slate-800 flex items-center justify-between">
                    <span className="text-slate-300">Тригонометрия</span>
                    <span className="text-emerald-400 font-bold">Закрыто ✓</span>
                  </div>
                  <div className="p-3 rounded-xl bg-slate-900 border border-slate-800 flex items-center justify-between">
                    <span className="text-slate-300">Стереометрия</span>
                    <span className="text-amber-400 font-bold">В процессе</span>
                  </div>
                </div>
              </div>
            </div>

          </div>
        </div>
      </section>

      {/* ─── 4. SUBJECTS GRID (ПРЕДМЕТЫ ПОДГОТОВКИ) ─── */}
      <section id="subjects" style={{ contentVisibility: 'auto', containIntrinsicSize: '1px 500px' }} className="py-20 px-5 sm:px-8 lg:px-12 max-w-7xl mx-auto border-t border-slate-800/60">
        <div className="text-center max-w-3xl mx-auto space-y-4 mb-16">
          <div className="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-slate-900 border border-slate-800 text-xs font-bold text-[#C6FF33]">
            <BookOpen className="w-4 h-4 text-[#C6FF33]" />
            Каталог предметов 2026
          </div>
          <h2 className="text-3xl sm:text-4xl font-extrabold tracking-tight text-white">
            Подготовка по всем ключевым предметам ЦТ и ЦЭ
          </h2>
          <p className="text-slate-400 text-sm sm:text-base leading-relaxed">
            Выбирайте предмет и находите репетитора под вашу целевую специальность в ВУЗах Беларуси.
          </p>
        </div>

        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
          {subjects.map((sub, idx) => (
            <a
              key={idx}
              href={sub.href}
              className={`rounded-2xl bg-gradient-to-br ${sub.color} border ${sub.border} p-6 hover:scale-[1.02] transition-all cursor-pointer space-y-4 block`}
            >
              <div className="flex items-center justify-between">
                <h3 className="text-xl font-bold text-white">{sub.name}</h3>
                <span className={`text-xs font-black px-2.5 py-1 rounded-full bg-black/40 border border-white/10 ${sub.text}`}>
                  {sub.gain}
                </span>
              </div>
              <p className="text-slate-300 text-xs leading-relaxed">
                Полный курс по программе экзаменов + решение открытых тестов прошлых лет.
              </p>
              <div className="flex items-center gap-1.5 text-xs font-bold text-white pt-2">
                <span>Смотреть преподавателей</span>
                <ArrowRight className="w-3.5 h-3.5" />
              </div>
            </a>
          ))}
        </div>
      </section>

      {/* ─── 4.5 BUSINESS MODEL: 0 BYN ДЛЯ УЧЕНИКОВ И ЧЕСТНАЯ ПОДПИСКА ДЛЯ РЕПЕТИТОРОВ ─── */}
      <section id="business-model" style={{ contentVisibility: 'auto', containIntrinsicSize: '1px 600px' }} className="py-20 px-5 sm:px-8 lg:px-12 max-w-7xl mx-auto border-t border-slate-800/60">
        <div className="text-center max-w-3xl mx-auto space-y-4 mb-16">
          <div className="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-[#C6FF33]/10 border border-[#C6FF33]/30 text-xs font-bold text-[#C6FF33]">
            <Sparkles className="w-4 h-4 text-[#C6FF33]" />
            Бизнес-модель Edusfera 2026
          </div>
          <h2 className="text-3xl sm:text-4xl lg:text-5xl font-extrabold tracking-tight text-white">
            Честная среда без посредников
          </h2>
          <p className="text-slate-400 text-sm sm:text-base leading-relaxed">
            Мы создали чистую SaaS-модель без поборов за уроки. В Edusfera прямые открытые отношения между семьями и преподавателями.
          </p>
        </div>

        <div className="grid grid-cols-1 lg:grid-cols-2 gap-8">
          
          {/* Card 1: Для учеников и родителей */}
          <div className="rounded-3xl bg-gradient-to-br from-slate-900/90 to-slate-950/90 border border-slate-800 p-8 sm:p-10 flex flex-col justify-between relative overflow-hidden">
            <div className="absolute top-0 right-0 w-48 h-48 bg-violet-600/10 rounded-full blur-3xl pointer-events-none"></div>
            <div>
              <div className="inline-block text-xs uppercase font-bold text-violet-400 mb-3 tracking-wider">
                Ученикам и родителям
              </div>
              <h3 className="text-2xl sm:text-3xl font-extrabold text-white mb-4">
                100% бесплатно и открыто
              </h3>
              <p className="text-slate-400 text-sm leading-relaxed mb-6">
                Вы платите строго за проведенные уроки по ставке репетитора. Сервис не берет никаких скрытых доплат.
              </p>

              <ul className="space-y-3.5 text-sm text-slate-200 mb-8">
                <li className="flex items-center gap-3">
                  <div className="w-5 h-5 rounded-full bg-[#C6FF33]/20 text-[#C6FF33] flex items-center justify-center font-bold text-xs">✓</div>
                  <span><strong>0 BYN</strong> за входную ИИ-диагностику и прогноз баллов</span>
                </li>
                <li className="flex items-center gap-3">
                  <div className="w-5 h-5 rounded-full bg-[#C6FF33]/20 text-[#C6FF33] flex items-center justify-center font-bold text-xs">✓</div>
                  <span><strong>0 BYN</strong> за поиск и подбор преподавателя в каталоге</span>
                </li>
                <li className="flex items-center gap-3">
                  <div className="w-5 h-5 rounded-full bg-[#C6FF33]/20 text-[#C6FF33] flex items-center justify-center font-bold text-xs">✓</div>
                  <span><strong>0 BYN</strong> за онлайн-класс с интерактивной доской</span>
                </li>
                <li className="flex items-center gap-3">
                  <div className="w-5 h-5 rounded-full bg-[#C6FF33]/20 text-[#C6FF33] flex items-center justify-center font-bold text-xs">✓</div>
                  <span><strong>Прямая оплата</strong> преподавателю (ЕРИП / на карту) без участия платформы</span>
                </li>
              </ul>
            </div>

            <a
              href="/diagnostic"
              className="inline-flex items-center justify-center gap-2 w-full py-4 rounded-full bg-white text-black font-bold text-sm hover:bg-slate-100 transition-all shadow-lg"
            >
              <span>Пройти диагностику знаний (0 BYN) →</span>
            </a>
          </div>

          {/* Card 2: Для репетиторов */}
          <div className="rounded-3xl bg-gradient-to-br from-[#0B0F19] to-black border-2 border-[#C6FF33]/80 p-8 sm:p-10 flex flex-col justify-between relative overflow-hidden shadow-[0_0_50px_rgba(198,255,51,0.15)]">
            <div className="absolute top-0 right-0 w-48 h-48 bg-[#C6FF33]/10 rounded-full blur-3xl pointer-events-none"></div>
            <div>
              <div className="flex items-center justify-between gap-2 mb-3">
                <span className="text-xs uppercase font-bold text-[#C6FF33] tracking-wider">
                  Преподавателям Беларуси
                </span>
                <span className="text-[10px] font-extrabold uppercase px-2.5 py-0.5 rounded-full bg-[#C6FF33] text-black">
                  7 дней 0 BYN
                </span>
              </div>
              <h3 className="text-2xl sm:text-3xl font-extrabold text-white mb-4">
                Подписка по цене 1 урока
              </h3>
              <p className="text-slate-400 text-sm leading-relaxed mb-6">
                Фиксированная подписка от 20 до 40 BYN/мес. Все 100% дохода с уроков остаются вам без процентов платформе.
              </p>

              <ul className="space-y-3.5 text-sm text-slate-200 mb-8">
                <li className="flex items-center gap-3">
                  <div className="w-5 h-5 rounded-full bg-[#C6FF33]/20 text-[#C6FF33] flex items-center justify-center font-bold text-xs">✓</div>
                  <span><strong>100% дохода вам</strong> с ваших учеников и уроков</span>
                </li>
                <li className="flex items-center gap-3">
                  <div className="w-5 h-5 rounded-full bg-[#C6FF33]/20 text-[#C6FF33] flex items-center justify-center font-bold text-xs">✓</div>
                  <span><strong>Интерактивный видеокласс</strong> (задержка &lt;45 мс в РБ) + доска Excalidraw без лимита 40 минут</span>
                </li>
                <li className="flex items-center gap-3">
                  <div className="w-5 h-5 rounded-full bg-[#C6FF33]/20 text-[#C6FF33] flex items-center justify-center font-bold text-xs">✓</div>
                  <span><strong>Авто-чеки для НПД</strong> в налоговые органы МНС РБ</span>
                </li>
                <li className="flex items-center gap-3">
                  <div className="w-5 h-5 rounded-full bg-[#C6FF33]/20 text-[#C6FF33] flex items-center justify-center font-bold text-xs">✓</div>
                  <span><strong>Встроенный ИИ-методист</strong> по программе экзаменов ЦТ/ЦЭ (генерация ДЗ и тестов за 15 секунд)</span>
                </li>
              </ul>
            </div>

            <a
              href="/for-tutors"
              className="inline-flex items-center justify-center gap-2 w-full py-4 rounded-full bg-[#C6FF33] hover:bg-[#d4ff59] text-black font-extrabold text-sm transition-all shadow-[0_0_30px_rgba(198,255,51,0.3)]"
            >
              <span>Узнать условия для репетиторов →</span>
            </a>
          </div>

        </div>
      </section>

      {/* ─── 5. TRUST & GUARANTEES (ГАРАНТИИ И ЛЕГАЛЬНОСТЬ) ─── */}
      <section id="guarantees" style={{ contentVisibility: 'auto', containIntrinsicSize: '1px 400px' }} className="py-20 px-5 sm:px-8 lg:px-12 max-w-7xl mx-auto border-t border-slate-800/60">
        <div className="text-center max-w-3xl mx-auto space-y-4 mb-16">
          <div className="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-slate-900 border border-slate-800 text-xs font-bold text-emerald-400">
            <ShieldCheck className="w-4 h-4 text-emerald-400" />
            Честность и надёжность
          </div>
          <h2 className="text-3xl sm:text-4xl font-extrabold tracking-tight text-white">
            4 гарантии платформы Edusfera
          </h2>
          <p className="text-slate-400 text-sm sm:text-base leading-relaxed">
            Создаём безопасные и прозрачные условия для преподавателей, учеников и родителей.
          </p>
        </div>

        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
          <div className="rounded-2xl bg-slate-900/80 border border-slate-800 p-6 space-y-3">
            <FileCheck className="w-8 h-8 text-violet-400 mb-2" />
            <h4 className="text-base font-bold text-white">Дипломы проверены</h4>
            <p className="text-slate-400 text-xs leading-relaxed">
              Модерация проверяет профильное образование и опыт каждого преподавателя перед публикацией в каталоге.
            </p>
          </div>

          <div className="rounded-2xl bg-slate-900/80 border border-slate-800 p-6 space-y-3">
            <DollarSign className="w-8 h-8 text-[#C6FF33] mb-2" />
            <h4 className="text-base font-bold text-white">0% скрытых комиссий</h4>
            <p className="text-slate-400 text-xs leading-relaxed">
              100% оплаты за уроки остаётся репетитору. Родители платят напрямую без наценок посредников и агентств.
            </p>
          </div>

          <div className="rounded-2xl bg-slate-900/80 border border-slate-800 p-6 space-y-3">
            <ShieldCheck className="w-8 h-8 text-emerald-400 mb-2" />
            <h4 className="text-base font-bold text-white">Чеки для налога в 1 клик</h4>
            <p className="text-slate-400 text-xs leading-relaxed">
              Полная легальность для репетиторов по налогу на профдоход (НПД) в соответствии с законами Беларуси.
            </p>
          </div>

          <div className="rounded-2xl bg-slate-900/80 border border-slate-800 p-6 space-y-3">
            <Award className="w-8 h-8 text-amber-400 mb-2" />
            <h4 className="text-base font-bold text-white">Отчёты после каждого урока</h4>
            <p className="text-slate-400 text-xs leading-relaxed">
              Родители и ученики видят пройденные темы, выполненные задания и реальный рост баллов в личном кабинете.
            </p>
          </div>
        </div>
      </section>

      {/* ─── 6. FINAL CALL TO ACTION HERO CARD ─── */}
      <section style={{ contentVisibility: 'auto', containIntrinsicSize: '1px 400px' }} className="py-16 px-5 sm:px-8 lg:px-12 max-w-7xl mx-auto">
        <div className="rounded-3xl bg-gradient-to-r from-violet-900 via-slate-900 to-indigo-900 border border-violet-500/30 p-10 sm:p-14 text-center space-y-6 relative overflow-hidden">
          <div className="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-white/10 border border-white/20 text-white font-medium text-xs backdrop-blur-md">
            <Sparkles className="w-3.5 h-3.5 text-[#C6FF33]" />
            <span>Сезон подготовки к ЦТ и ЦЭ 2026</span>
          </div>

          <h2 className="text-3xl sm:text-5xl font-black text-white tracking-tight">
            Начните заниматься или преподавать уже сегодня
          </h2>
          <p className="text-slate-300 text-sm sm:text-base max-w-2xl mx-auto leading-relaxed">
            Преподавателям — 7 дней бесплатного доступа без скрытых комиссий. Ученикам и родителям — проверенные репетиторы, интерактивный онлайн-класс и честная прямая оплата.
          </p>

          <div className="pt-4 flex flex-col sm:flex-row items-center justify-center gap-4">
            <a
              href="/tutors"
              className="w-full sm:w-auto px-8 py-4 rounded-full bg-[#C6FF33] text-black font-extrabold text-base hover:bg-[#d4ff59] transition-transform hover:scale-105 cursor-pointer shadow-[0_0_25px_rgba(198,255,51,0.3)]"
            >
              Найти репетитора
            </a>
            <a
              href="/for-tutors"
              className="w-full sm:w-auto px-8 py-4 rounded-full bg-white/10 text-white font-bold text-base hover:bg-white/20 transition-all cursor-pointer border border-white/20 backdrop-blur-md"
            >
              Я преподаватель (7 дней 0 BYN) →
            </a>
          </div>
        </div>
      </section>

      {/* ─── 7. FOOTER SECTION ─── */}
      <Footer />

      {/* Registration Modal Overlay */}
      {registerModalOpen && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-md">
          <div className="relative w-full max-w-md bg-neutral-900 border border-white/10 rounded-2xl p-6 sm:p-8 text-white shadow-2xl">
            <button
              onClick={() => setRegisterModalOpen(false)}
              aria-label="Закрыть модальное окно"
              className="absolute top-4 right-4 text-white/50 hover:text-white transition-colors cursor-pointer"
            >
              <X className="w-5 h-5" />
            </button>
            <h3 className="text-2xl font-bold text-white mb-2">Начать бесплатный доступ</h3>
            <p className="text-sm text-white/60 mb-6">
              Зарегистрируйтесь за 30 секунд для запуска ИИ-диагностики знаний.
            </p>
            <a
              href="/register"
              className="w-full inline-flex items-center justify-center gap-2 py-3.5 bg-white text-black font-semibold rounded-xl hover:bg-white/90 transition-all cursor-pointer text-sm"
            >
              <span>Перейти к регистрации</span>
            </a>
          </div>
        </div>
      )}

    </div>
  );
}
