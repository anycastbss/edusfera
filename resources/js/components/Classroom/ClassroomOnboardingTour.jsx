import React, { useState, useEffect } from 'react';
import { motion, AnimatePresence } from 'motion/react';
import { 
  Sparkles, Pencil, Video, MessageSquare, FileText, Folder,
  Share2, ChevronLeft, X, Check, ArrowRight,
  Move, ShieldCheck, Zap
} from 'lucide-react';

export default function ClassroomOnboardingTour({
  isOpen,
  onClose,
  isTutor = true,
  userName = ''
}) {
  const [currentStep, setCurrentStep] = useState(0);
  const [isMobile, setIsMobile] = useState(() => typeof window !== 'undefined' ? window.innerWidth <= 768 : false);

  useEffect(() => {
    const handleResize = () => setIsMobile(window.innerWidth <= 768);
    window.addEventListener('resize', handleResize);
    return () => window.removeEventListener('resize', handleResize);
  }, []);

  const steps = [
    {
      id: 'welcome',
      tag: 'Виртуальный класс 2026',
      tagBg: 'linear-gradient(135deg, #7D39EB 0%, #4F46E5 100%)',
      title: 'Добро пожаловать в виртуальный класс Edusfera!',
      subtitle: `Рады видеть вас${userName ? ', ' + userName : ''}! Мы создали единое рабочее пространство для комфортных онлайн-уроков: интерактивная доска, видеосвязь высокой чёткости, конспекты и встроенный ИИ-помощник.`,
      icon: Sparkles,
      preview: (
        <div style={{
          width: '100%',
          maxWidth: '300px',
          padding: isMobile ? '14px' : '18px',
          borderRadius: '24px',
          background: 'linear-gradient(145deg, rgba(125, 57, 235, 0.25) 0%, rgba(30, 20, 60, 0.6) 100%)',
          border: '1px solid rgba(167, 139, 250, 0.35)',
          backdropFilter: 'blur(20px)',
          boxShadow: '0 16px 36px rgba(0, 0, 0, 0.5), inset 0 1px 1px rgba(255, 255, 255, 0.3)',
          textAlign: 'center',
          display: 'flex',
          flexDirection: 'column',
          alignItems: 'center',
          gap: '10px'
        }}>
          <div style={{
            width: isMobile ? '52px' : '58px',
            height: isMobile ? '52px' : '58px',
            borderRadius: '18px',
            background: 'linear-gradient(135deg, #7D39EB 0%, #C6FF33 100%)',
            padding: '2px',
            boxShadow: '0 8px 24px rgba(125, 57, 235, 0.5)'
          }}>
            <div style={{
              width: '100%',
              height: '100%',
              borderRadius: '16px',
              backgroundColor: '#0F0F14',
              display: 'flex',
              alignItems: 'center',
              justifyContent: 'center'
            }}>
              <Sparkles size={isMobile ? 26 : 30} color="#C6FF33" />
            </div>
          </div>
          <div>
            <div style={{ fontSize: isMobile ? '13px' : '15px', fontWeight: 800, color: '#FFFFFF' }}>
              Всё для идеального урока
            </div>
            <div style={{ fontSize: '11px', color: 'rgba(255, 255, 255, 0.75)', marginTop: '3px' }}>
              Доска, HD-видео, чат и ИИ без тормозов и задержек
            </div>
          </div>
          <div style={{ display: 'flex', alignItems: 'center', gap: '6px', marginTop: '2px' }}>
            <span style={{
              padding: '3px 8px',
              borderRadius: '9999px',
              backgroundColor: 'rgba(255, 255, 255, 0.12)',
              fontSize: '10px',
              fontWeight: 700,
              color: '#FFFFFF',
              display: 'inline-flex',
              alignItems: 'center',
              gap: '4px'
            }}>
              <span style={{ width: '6px', height: '6px', borderRadius: '50%', backgroundColor: '#C6FF33' }} />
              LiveKit SFU 60 FPS
            </span>
            <span style={{
              padding: '3px 8px',
              borderRadius: '9999px',
              backgroundColor: 'rgba(125, 57, 235, 0.3)',
              border: '1px solid rgba(167, 139, 250, 0.3)',
              fontSize: '10px',
              fontWeight: 700,
              color: '#D8B4FE'
            }}>
              CRDT Sync
            </span>
          </div>
        </div>
      )
    },
    {
      id: 'whiteboard',
      tag: 'Интерактивная доска',
      tagBg: 'linear-gradient(135deg, #9333EA 0%, #DB2777 100%)',
      title: 'Синхронная доска для двоих',
      subtitle: 'Пишите формулы, стройте графики, добавляйте фигуры и текст. Все изменения мгновенно отображаются у собеседника в реальном времени благодаря сквозной синхронизации.',
      icon: Pencil,
      preview: (
        <div style={{
          width: '100%',
          maxWidth: '300px',
          padding: isMobile ? '12px' : '16px',
          borderRadius: '24px',
          backgroundColor: 'rgba(18, 18, 24, 0.85)',
          border: '1px solid rgba(216, 180, 254, 0.25)',
          backdropFilter: 'blur(20px)',
          boxShadow: '0 16px 36px rgba(0, 0, 0, 0.5), inset 0 1px 1px rgba(255, 255, 255, 0.2)',
          display: 'flex',
          flexDirection: 'column',
          gap: '10px'
        }}>
          {/* Mock Floating Toolbar */}
          <div style={{
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'center',
            gap: '5px',
            padding: '5px 8px',
            borderRadius: '14px',
            backgroundColor: 'rgba(255, 255, 255, 0.1)',
            border: '1px solid rgba(255, 255, 255, 0.12)'
          }}>
            <span style={{ padding: '4px 6px', borderRadius: '8px', backgroundColor: '#7D39EB', color: '#FFFFFF', display: 'flex' }}><Pencil size={12} /></span>
            <span style={{ padding: '2px 5px', borderRadius: '6px', color: '#CBD5E1', fontSize: '11px', fontWeight: 700 }}>□</span>
            <span style={{ padding: '2px 5px', borderRadius: '6px', color: '#CBD5E1', fontSize: '11px', fontWeight: 700 }}>○</span>
            <span style={{ padding: '2px 5px', borderRadius: '6px', color: '#CBD5E1', fontSize: '11px', fontWeight: 700, fontFamily: 'serif' }}>T</span>
            <span style={{ padding: '2px 5px', borderRadius: '6px', color: '#CBD5E1', fontSize: '11px', fontWeight: 700 }}>↗</span>
            <span style={{ marginLeft: 'auto', padding: '2px 6px', borderRadius: '6px', backgroundColor: 'rgba(16, 185, 129, 0.2)', color: '#34D399', fontSize: '9px', fontWeight: 700 }}>Открыто</span>
          </div>
          {/* Canvas Illustration */}
          <div style={{
            height: isMobile ? '80px' : '95px',
            borderRadius: '16px',
            backgroundColor: '#09090D',
            border: '1px solid rgba(255, 255, 255, 0.1)',
            padding: '10px',
            display: 'flex',
            flexDirection: 'column',
            justifyContent: 'space-between'
          }}>
            <div style={{ fontFamily: 'monospace', fontSize: '12px', color: '#C6FF33', fontWeight: 800 }}>
              f(x) = 2x² + 4x - 6
            </div>
            <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between' }}>
              <div style={{ padding: '2px 6px', borderRadius: '6px', backgroundColor: '#7D39EB', fontSize: '9px', color: '#FFFFFF', fontWeight: 700 }}>
                Вы (пишете)
              </div>
              <div style={{ padding: '2px 6px', borderRadius: '6px', backgroundColor: '#06B6D4', fontSize: '9px', color: '#FFFFFF', fontWeight: 700 }}>
                Ученик
              </div>
            </div>
          </div>
        </div>
      )
    },
    {
      id: 'video_island',
      tag: 'HD Видео & Звук',
      tagBg: 'linear-gradient(135deg, #2563EB 0%, #06B6D4 100%)',
      title: 'Умное окно видеосвязи',
      subtitle: 'На компьютере окно видео можно свободно перемещать по экрану, менять размер (S / M / L) или скрывать. На смартфоне — разворачивать на весь экран и быстро переключать камеру на тетрадь.',
      icon: Video,
      preview: (
        <div style={{
          width: '100%',
          maxWidth: '300px',
          padding: isMobile ? '12px' : '16px',
          borderRadius: '24px',
          backgroundColor: 'rgba(18, 18, 24, 0.85)',
          border: '1px solid rgba(56, 189, 248, 0.25)',
          backdropFilter: 'blur(20px)',
          boxShadow: '0 16px 36px rgba(0, 0, 0, 0.5), inset 0 1px 1px rgba(255, 255, 255, 0.2)',
          display: 'flex',
          flexDirection: 'column',
          gap: '8px'
        }}>
          <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', fontSize: '11px', fontWeight: 700, color: 'rgba(255, 255, 255, 0.8)' }}>
            <span style={{ display: 'inline-flex', alignItems: 'center', gap: '4px', color: '#38BDF8' }}>
              <Move size={13} /> Окно видеосвязи
            </span>
            <span style={{ padding: '1px 6px', borderRadius: '6px', backgroundColor: 'rgba(255, 255, 255, 0.1)', fontSize: '9px' }}>S / M / L</span>
          </div>
          <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '6px' }}>
            <div style={{
              aspectRatio: '4/3',
              borderRadius: '12px',
              background: 'linear-gradient(135deg, rgba(88, 28, 135, 0.6), #09090D)',
              border: '1px solid rgba(255, 255, 255, 0.1)',
              display: 'flex',
              alignItems: 'center',
              justifyContent: 'center',
              position: 'relative'
            }}>
              <div style={{ width: '28px', height: '28px', borderRadius: '50%', backgroundColor: '#7D39EB', color: '#FFFFFF', display: 'flex', alignItems: 'center', justifyContent: 'center', fontSize: '11px', fontWeight: 800 }}>Я</div>
              <span style={{ position: 'absolute', bottom: '3px', left: '4px', backgroundColor: 'rgba(0,0,0,0.6)', padding: '1px 4px', borderRadius: '4px', color: '#FFFFFF', fontSize: '8px' }}>Вы</span>
            </div>
            <div style={{
              aspectRatio: '4/3',
              borderRadius: '12px',
              background: 'linear-gradient(135deg, rgba(30, 58, 138, 0.6), #09090D)',
              border: '1px solid rgba(255, 255, 255, 0.1)',
              display: 'flex',
              alignItems: 'center',
              justifyContent: 'center',
              position: 'relative'
            }}>
              <div style={{ width: '28px', height: '28px', borderRadius: '50%', backgroundColor: '#06B6D4', color: '#FFFFFF', display: 'flex', alignItems: 'center', justifyContent: 'center', fontSize: '11px', fontWeight: 800 }}>У</div>
              <span style={{ position: 'absolute', bottom: '3px', left: '4px', backgroundColor: 'rgba(0,0,0,0.6)', padding: '1px 4px', borderRadius: '4px', color: '#FFFFFF', fontSize: '8px' }}>Собеседник</span>
            </div>
          </div>
          <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'center', gap: '6px', fontSize: '9px', color: 'rgba(255, 255, 255, 0.7)' }}>
            <span style={{ padding: '2px 6px', borderRadius: '9999px', backgroundColor: 'rgba(16, 185, 129, 0.15)', color: '#34D399' }}>Шумоподавление</span>
            <span style={{ padding: '2px 6px', borderRadius: '9999px', backgroundColor: 'rgba(56, 189, 248, 0.15)', color: '#38BDF8' }}>FaceTime Mode</span>
          </div>
        </div>
      )
    },
    {
      id: 'sidebar_tools',
      tag: 'Материалы & Чат',
      tagBg: 'linear-gradient(135deg, #059669 0%, #0D9488 100%)',
      title: 'Чат, конспекты и обмен файлами',
      subtitle: 'В нижней панели всегда доступны быстрые инструменты: общий чат, автосохраняемый конспект урока и обмен файлами (документы, фото решений, PDF и домашние задания).',
      icon: Folder,
      preview: (
        <div style={{
          width: '100%',
          maxWidth: '300px',
          padding: isMobile ? '10px' : '14px',
          borderRadius: '24px',
          backgroundColor: 'rgba(18, 18, 24, 0.85)',
          border: '1px solid rgba(52, 211, 153, 0.25)',
          backdropFilter: 'blur(20px)',
          boxShadow: '0 16px 36px rgba(0, 0, 0, 0.5), inset 0 1px 1px rgba(255, 255, 255, 0.2)',
          display: 'flex',
          flexDirection: 'column',
          gap: '6px'
        }}>
          <div style={{ display: 'flex', alignItems: 'center', gap: '8px', padding: '6px 10px', borderRadius: '12px', backgroundColor: 'rgba(255, 255, 255, 0.06)' }}>
            <div style={{ padding: '5px', borderRadius: '8px', backgroundColor: 'rgba(125, 57, 235, 0.25)', color: '#C084FC', display: 'flex' }}><MessageSquare size={14} /></div>
            <div style={{ textAlign: 'left' }}>
              <div style={{ fontSize: '11px', fontWeight: 800, color: '#FFFFFF' }}>Чат урока</div>
              <div style={{ fontSize: '9px', color: 'rgba(255, 255, 255, 0.6)' }}>Мгновенные сообщения и ссылки</div>
            </div>
          </div>
          <div style={{ display: 'flex', alignItems: 'center', gap: '8px', padding: '6px 10px', borderRadius: '12px', backgroundColor: 'rgba(255, 255, 255, 0.06)' }}>
            <div style={{ padding: '5px', borderRadius: '8px', backgroundColor: 'rgba(59, 130, 246, 0.25)', color: '#60A5FA', display: 'flex' }}><FileText size={14} /></div>
            <div style={{ textAlign: 'left' }}>
              <div style={{ fontSize: '11px', fontWeight: 800, color: '#FFFFFF' }}>Конспект</div>
              <div style={{ fontSize: '9px', color: 'rgba(255, 255, 255, 0.6)' }}>Формулы и ключевые тезисы урока</div>
            </div>
          </div>
          <div style={{ display: 'flex', alignItems: 'center', gap: '8px', padding: '6px 10px', borderRadius: '12px', backgroundColor: 'rgba(255, 255, 255, 0.06)' }}>
            <div style={{ padding: '5px', borderRadius: '8px', backgroundColor: 'rgba(16, 185, 129, 0.25)', color: '#34D399', display: 'flex' }}><Folder size={14} /></div>
            <div style={{ textAlign: 'left' }}>
              <div style={{ fontSize: '11px', fontWeight: 800, color: '#FFFFFF' }}>Материалы</div>
              <div style={{ fontSize: '9px', color: 'rgba(255, 255, 255, 0.6)' }}>Загрузка и отправка заданий в 1 клик</div>
            </div>
          </div>
        </div>
      )
    },
    {
      id: 'ai_copilot',
      tag: 'Нейросеть в классе',
      tagBg: 'linear-gradient(135deg, #D97706 0%, #EA580C 100%)',
      title: 'Персональный ИИ-Ассистент',
      subtitle: 'Застряли на сложном вопросе или нужно составить тест прямо во время урока? Нажмите фиолетовую иконку ИИ в нижней панели — ассистент мгновенно разберёт решение по шагам.',
      icon: Sparkles,
      preview: (
        <div style={{
          width: '100%',
          maxWidth: '300px',
          padding: isMobile ? '12px' : '16px',
          borderRadius: '24px',
          backgroundColor: 'rgba(18, 18, 24, 0.85)',
          border: '1px solid rgba(251, 191, 36, 0.25)',
          backdropFilter: 'blur(20px)',
          boxShadow: '0 16px 36px rgba(0, 0, 0, 0.5), inset 0 1px 1px rgba(255, 255, 255, 0.2)',
          display: 'flex',
          flexDirection: 'column',
          gap: '8px'
        }}>
          <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between' }}>
            <div style={{ display: 'inline-flex', alignItems: 'center', gap: '5px', color: '#FBBF24', fontSize: '11px', fontWeight: 800 }}>
              <Sparkles size={14} />
              <span>ИИ-Помощник Edusfera</span>
            </div>
            <span style={{ fontSize: '9px', fontWeight: 800, padding: '2px 6px', borderRadius: '9999px', backgroundColor: 'rgba(125, 57, 235, 0.25)', color: '#C6FF33' }}>
              Онлайн 24/7
            </span>
          </div>
          <div style={{ padding: '8px 10px', borderRadius: '12px', backgroundColor: 'rgba(255, 255, 255, 0.06)', textAlign: 'left' }}>
            <div style={{ fontSize: '9px', color: 'rgba(255, 255, 255, 0.55)' }}>Вопрос: Закон Ома простыми словами</div>
            <div style={{ fontSize: '11px', color: 'rgba(255, 255, 255, 0.95)', fontWeight: 600, marginTop: '2px' }}>
              💡 Ток прямо пропорционален напряжению: <span style={{ color: '#C6FF33', fontWeight: 800 }}>I = U / R</span>
            </div>
          </div>
          <div style={{ display: 'flex', alignItems: 'center', gap: '4px', fontSize: '8px', color: 'rgba(255, 255, 255, 0.65)' }}>
            <span style={{ padding: '2px 6px', borderRadius: '6px', backgroundColor: 'rgba(255, 255, 255, 0.08)' }}>Тесты ЦТ/ЕГЭ</span>
            <span style={{ padding: '2px 6px', borderRadius: '6px', backgroundColor: 'rgba(255, 255, 255, 0.08)' }}>План урока</span>
            <span style={{ padding: '2px 6px', borderRadius: '6px', backgroundColor: 'rgba(255, 255, 255, 0.08)' }}>Формулы</span>
          </div>
        </div>
      )
    },
    {
      id: 'guest_link',
      tag: 'Быстрый вход',
      tagBg: 'linear-gradient(135deg, #6366F1 0%, #9333EA 100%)',
      title: 'Вход ученика в 1 клик без паролей',
      subtitle: 'Если ученик ещё не зарегистрирован, нажмите кнопку «Ссылка» в верхнем правом углу видеоокна. Отправьте ссылку в Telegram, WhatsApp или Viber — ученик сразу подключится к классу без регистрации и установки приложений.',
      icon: Share2,
      preview: (
        <div style={{
          width: '100%',
          maxWidth: '300px',
          padding: isMobile ? '12px' : '16px',
          borderRadius: '24px',
          backgroundColor: 'rgba(18, 18, 24, 0.85)',
          border: '1px solid rgba(167, 139, 250, 0.3)',
          backdropFilter: 'blur(20px)',
          boxShadow: '0 16px 36px rgba(0, 0, 0, 0.5), inset 0 1px 1px rgba(255, 255, 255, 0.2)',
          display: 'flex',
          flexDirection: 'column',
          gap: '8px'
        }}>
          <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', fontSize: '11px', fontWeight: 700 }}>
            <span style={{ color: '#C084FC' }}>Прямая ссылка на класс</span>
            <span style={{ fontSize: '9px', padding: '1px 6px', borderRadius: '9999px', backgroundColor: 'rgba(16, 185, 129, 0.2)', color: '#34D399', fontWeight: 800 }}>1 клик</span>
          </div>
          <div style={{
            padding: '6px 8px',
            borderRadius: '10px',
            backgroundColor: 'rgba(255, 255, 255, 0.06)',
            fontFamily: 'monospace',
            fontSize: '10px',
            color: 'rgba(255, 255, 255, 0.85)',
            overflow: 'hidden',
            textOverflow: 'ellipsis',
            whiteSpace: 'nowrap'
          }}>
            edusfera.by/classroom/49/join?token=7a9b...
          </div>
          <div style={{
            padding: '8px 12px',
            borderRadius: '12px',
            background: 'linear-gradient(135deg, #7D39EB 0%, #9333EA 100%)',
            color: '#FFFFFF',
            textAlign: 'center',
            fontSize: '11px',
            fontWeight: 800,
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'center',
            gap: '6px',
            boxShadow: '0 6px 18px rgba(125, 57, 235, 0.4)'
          }}>
            <Check size={14} color="#C6FF33" />
            <span>Скопировать ссылку для ученика</span>
          </div>
          <div style={{ fontSize: '9px', color: 'rgba(255, 255, 255, 0.55)', textAlign: 'center' }}>
            Работает в Chrome, Safari, Яндекс.Браузере без скачиваний
          </div>
        </div>
      )
    }
  ];

  const step = steps[currentStep];

  const handleNext = () => {
    if (currentStep < steps.length - 1) {
      setCurrentStep(prev => prev + 1);
    } else {
      handleComplete();
    }
  };

  const handlePrev = () => {
    if (currentStep > 0) {
      setCurrentStep(prev => prev - 1);
    }
  };

  const handleComplete = () => {
    try {
      localStorage.setItem('edusfera_classroom_tour_completed_v1', 'true');
    } catch (_) {}
    if (onClose) onClose();
  };

  // Keyboard navigation (arrows + Escape)
  useEffect(() => {
    if (!isOpen) return;
    const handleKeyDown = (e) => {
      if (e.key === 'ArrowRight' || e.key === 'Enter') handleNext();
      else if (e.key === 'ArrowLeft') handlePrev();
      else if (e.key === 'Escape') handleComplete();
    };
    window.addEventListener('keydown', handleKeyDown);
    return () => window.removeEventListener('keydown', handleKeyDown);
  }, [isOpen, currentStep]);

  if (!isOpen) return null;

  return (
    <AnimatePresence>
      <motion.div
        key="classroom-tour-overlay"
        initial={{ opacity: 0 }}
        animate={{ opacity: 1 }}
        exit={{ opacity: 0 }}
        transition={{ duration: 0.22 }}
        style={{ 
          position: 'fixed',
          top: 0,
          left: 0,
          right: 0,
          bottom: 0,
          width: '100vw',
          height: '100dvh',
          zIndex: 99999,
          backgroundColor: 'rgba(8, 8, 12, 0.92)',
          backdropFilter: 'blur(30px) saturate(180%)',
          WebkitBackdropFilter: 'blur(30px) saturate(180%)',
          display: 'flex',
          flexDirection: 'column',
          justifyContent: 'space-between',
          color: '#FFFFFF',
          userSelect: 'none',
          overflow: 'hidden',
          paddingTop: 'max(16px, env(safe-area-inset-top))',
          paddingBottom: 'max(20px, env(safe-area-inset-bottom))',
          paddingLeft: '16px',
          paddingRight: '16px',
          boxSizing: 'border-box',
          pointerEvents: 'auto',
          fontFamily: "'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif"
        }}
      >
        {/* ─── TOP BAR: SEGMENTED STORY BARS & SKIP BUTTON ─── */}
        <div style={{
          width: '100%',
          maxWidth: '460px',
          margin: '0 auto',
          display: 'flex',
          flexDirection: 'column',
          gap: '10px'
        }}>
          <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', gap: '10px' }}>
            {/* Segmented Story Progress Bars */}
            <div style={{ flex: 1, display: 'flex', alignItems: 'center', gap: '5px' }}>
              {steps.map((_, idx) => (
                <div
                  key={idx}
                  onClick={() => setCurrentStep(idx)}
                  style={{
                    height: '4px',
                    flex: 1,
                    borderRadius: '9999px',
                    overflow: 'hidden',
                    backgroundColor: 'rgba(255, 255, 255, 0.2)',
                    cursor: 'pointer'
                  }}
                >
                  <div
                    style={{
                      height: '100%',
                      width: idx <= currentStep ? '100%' : '0%',
                      background: idx < currentStep
                        ? '#C6FF33'
                        : idx === currentStep
                        ? 'linear-gradient(90deg, #7D39EB, #C6FF33)'
                        : 'transparent',
                      transition: 'all 0.3s ease'
                    }}
                  />
                </div>
              ))}
            </div>

            {/* Skip Button */}
            <button
              type="button"
              onClick={handleComplete}
              style={{
                width: '32px',
                height: '32px',
                borderRadius: '50%',
                backgroundColor: 'rgba(255, 255, 255, 0.12)',
                border: '1px solid rgba(255, 255, 255, 0.18)',
                color: '#FFFFFF',
                display: 'flex',
                alignItems: 'center',
                justifyContent: 'center',
                cursor: 'pointer',
                flexShrink: 0,
                WebkitTapHighlightColor: 'transparent'
              }}
              title="Пропустить обучение"
            >
              <X size={18} color="#FFFFFF" />
            </button>
          </div>

          <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', fontSize: '11px', fontWeight: 700, color: 'rgba(255, 255, 255, 0.65)' }}>
            <span style={{
              display: 'inline-flex',
              alignItems: 'center',
              padding: '3px 10px',
              borderRadius: '9999px',
              fontSize: '10px',
              fontWeight: 800,
              textTransform: 'uppercase',
              letterSpacing: '0.04em',
              background: step.tagBg,
              color: '#FFFFFF',
              boxShadow: '0 2px 8px rgba(0, 0, 0, 0.3)'
            }}>
              {step.tag}
            </span>
            <span style={{ fontSize: '11px', color: 'rgba(255, 255, 255, 0.8)' }}>
              {currentStep + 1} из {steps.length}
            </span>
          </div>
        </div>

        {/* ─── CENTER HERO SHOWCASE AREA WITH ANIMATED TRANSITIONS ─── */}
        <div style={{
          flex: 1,
          display: 'flex',
          flexDirection: 'column',
          alignItems: 'center',
          justifyContent: 'center',
          maxWidth: '460px',
          margin: '0 auto',
          width: '100%',
          padding: '8px 0'
        }}>
          <AnimatePresence mode="wait">
            <motion.div
              key={step.id}
              initial={{ opacity: 0, y: 12, scale: 0.96 }}
              animate={{ opacity: 1, y: 0, scale: 1 }}
              exit={{ opacity: 0, y: -12, scale: 0.96 }}
              transition={{ duration: 0.2, ease: 'easeOut' }}
              style={{
                width: '100%',
                display: 'flex',
                flexDirection: 'column',
                alignItems: 'center',
                textAlign: 'center',
                gap: isMobile ? '14px' : '20px'
              }}
            >
              {/* Interactive Mock Preview */}
              <div style={{ width: '100%', display: 'flex', justifyContent: 'center' }}>
                {step.preview}
              </div>

              {/* Title & Description */}
              <div style={{ display: 'flex', flexDirection: 'column', gap: '8px', maxWidth: '380px' }}>
                <h2 style={{
                  fontSize: isMobile ? '18px' : '22px',
                  fontWeight: 900,
                  color: '#FFFFFF',
                  margin: 0,
                  lineHeight: 1.25,
                  letterSpacing: '-0.02em',
                  textShadow: '0 2px 10px rgba(0, 0, 0, 0.5)'
                }}>
                  {step.title}
                </h2>
                <p style={{
                  fontSize: isMobile ? '12px' : '14px',
                  color: 'rgba(255, 255, 255, 0.88)',
                  margin: 0,
                  lineHeight: 1.45,
                  fontWeight: 400
                }}>
                  {step.subtitle}
                </p>
              </div>
            </motion.div>
          </AnimatePresence>
        </div>

        {/* ─── BOTTOM CONTROLS: PREV / NEXT / START ─── */}
        <div style={{
          width: '100%',
          maxWidth: '460px',
          margin: '0 auto',
          display: 'flex',
          flexDirection: 'column',
          gap: '10px'
        }}>
          <div style={{ display: 'flex', alignItems: 'center', gap: '8px' }}>
            {currentStep > 0 && (
              <button
                type="button"
                onClick={handlePrev}
                style={{
                  width: '48px',
                  height: '48px',
                  borderRadius: '16px',
                  backgroundColor: 'rgba(255, 255, 255, 0.12)',
                  border: '1px solid rgba(255, 255, 255, 0.18)',
                  color: '#FFFFFF',
                  display: 'flex',
                  alignItems: 'center',
                  justifyContent: 'center',
                  cursor: 'pointer',
                  flexShrink: 0,
                  WebkitTapHighlightColor: 'transparent'
                }}
                title="Назад"
              >
                <ChevronLeft size={22} color="#FFFFFF" />
              </button>
            )}

            <button
              type="button"
              onClick={handleNext}
              style={{
                flex: 1,
                height: '48px',
                borderRadius: '16px',
                fontWeight: 800,
                fontSize: '14px',
                display: 'flex',
                alignItems: 'center',
                justifyContent: 'center',
                gap: '8px',
                border: 'none',
                cursor: 'pointer',
                background: currentStep === steps.length - 1
                  ? 'linear-gradient(135deg, #7D39EB 0%, #9333EA 100%)'
                  : '#FFFFFF',
                color: currentStep === steps.length - 1 ? '#FFFFFF' : '#0F172A',
                boxShadow: currentStep === steps.length - 1
                  ? '0 8px 24px rgba(125, 57, 235, 0.45)'
                  : '0 8px 24px rgba(255, 255, 255, 0.2)',
                transition: 'all 0.15s ease',
                WebkitTapHighlightColor: 'transparent'
              }}
            >
              <span>
                {currentStep === steps.length - 1 ? 'Всё понятно, начать урок! 🚀' : 'Далее'}
              </span>
              {currentStep < steps.length - 1 && <ArrowRight size={16} color="#0F172A" />}
            </button>
          </div>

          <p style={{
            fontSize: '10px',
            textAlign: 'center',
            color: 'rgba(255, 255, 255, 0.5)',
            margin: 0
          }}>
            Кнопка обучения <strong style={{ color: 'rgba(255, 255, 255, 0.8)' }}>(?)</strong> всегда доступна в верхней панели класса
          </p>
        </div>
      </motion.div>
    </AnimatePresence>
  );
}
