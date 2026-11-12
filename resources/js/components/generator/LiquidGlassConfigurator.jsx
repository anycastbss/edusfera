import React, { useState, useRef, useEffect } from 'react';
import { motion, AnimatePresence } from 'motion/react';
import {
    ChevronDown,
    SlidersHorizontal,
    Check,
    Printer,
    FileText,
    X,
    ArrowRight,
    BookOpen
} from 'lucide-react';

const FAST_TWEEN = { duration: 0.16, ease: [0.16, 1, 0.3, 1] };

export default function LiquidGlassConfigurator({
    selectedSubject,
    handleSubjectChange,
    SUBJECTS,
    selectedGrade,
    handleGradeChange,
    GRADES,
    topic,
    setTopic,
    currentSubjectName,
    selectedQuarter,
    setSelectedQuarter,
    getCurriculumTopics,
    variantCount,
    setVariantCount,
    VARIANT_COUNTS,
    showAdvancedSettings,
    setShowAdvancedSettings,
    selectedWorkType,
    handleWorkTypeChange,
    selectedWorkTypeObj,
    WORK_TYPES,
    tasksCountA,
    setTasksCountA,
    tasksCountB,
    setTasksCountB,
    printLayout,
    setPrintLayout,
    setShowPayModal
}) {
    const [isSubjectDropdownOpen, setIsSubjectDropdownOpen] = useState(false);
    const dropdownRef = useRef(null);

    // Current subject object
    const activeSubjectObj = SUBJECTS.find(s => s.id === selectedSubject) || SUBJECTS[0];
    const ActiveSubjectIcon = activeSubjectObj?.icon || BookOpen;

    // Close subject dropdown when clicking outside
    useEffect(() => {
        const handleClickOutside = (e) => {
            if (dropdownRef.current && !dropdownRef.current.contains(e.target)) {
                setIsSubjectDropdownOpen(false);
            }
        };
        const handleKeyDown = (e) => {
            if (e.key === 'Escape') {
                setIsSubjectDropdownOpen(false);
            }
        };
        document.addEventListener('mousedown', handleClickOutside);
        document.addEventListener('keydown', handleKeyDown);
        return () => {
            document.removeEventListener('mousedown', handleClickOutside);
            document.removeEventListener('keydown', handleKeyDown);
        };
    }, []);

    // Curriculum topics for current subject/grade/quarter
    const currentTopics = getCurriculumTopics
        ? getCurriculumTopics(selectedSubject, selectedGrade, selectedQuarter)
        : [];

    return (
        <div className="relative group">
            {/* MAIN LIQUID GLASS CONTAINER (Edusfera Aurora Glass Standard) */}
            <div
                className="relative rounded-3xl p-5 sm:p-6 space-y-4 overflow-visible"
                style={{
                    background: 'linear-gradient(135deg, rgba(255, 255, 255, 0.05) 0%, rgba(255, 255, 255, 0.01) 100%), rgba(16, 16, 22, 0.90)',
                    backdropFilter: 'blur(12px)',
                    WebkitBackdropFilter: 'blur(12px)',
                    border: '1px solid rgba(255, 255, 255, 0.12)',
                    boxShadow: 'inset 0 1px 1px 0 rgba(255, 255, 255, 0.2), inset 0 -1px 1px 0 rgba(0, 0, 0, 0.4), 0 20px 40px -10px rgba(0, 0, 0, 0.5)',
                    transform: 'translateZ(0)',
                    contain: 'layout paint'
                }}
            >
                {/* STEP 1: SUBJECT & GRADE SELECTION */}
                <div className="space-y-2.5">
                    <div className="flex items-center justify-between">
                        <div className="flex items-center gap-2">
                            <span className="w-2 h-2 rounded-full bg-[#C6FF33]" />
                            <label className="text-xs font-semibold text-slate-200">
                                1. Предмет и класс
                            </label>
                        </div>
                        <span className="text-[11px] font-mono px-2 py-0.5 rounded-full bg-white/5 border border-white/10 text-slate-300 font-semibold">
                            {selectedGrade === 'abiturient' ? 'Абитуриент' : selectedGrade + ' класс'}
                        </span>
                    </div>

                    <div className="space-y-2">
                        {/* Custom Subject Popover */}
                        <div className="relative" ref={dropdownRef}>
                            <button
                                type="button"
                                onClick={() => setIsSubjectDropdownOpen(!isSubjectDropdownOpen)}
                                className="w-full flex items-center justify-between px-3.5 py-2.5 rounded-2xl transition-colors duration-150 cursor-pointer group/subj text-left relative overflow-hidden"
                                style={{
                                    background: 'rgba(255, 255, 255, 0.04)',
                                    border: isSubjectDropdownOpen
                                        ? '1px solid #7D39EB'
                                        : '1px solid rgba(255, 255, 255, 0.12)',
                                    boxShadow: 'inset 0 1px 1px rgba(255, 255, 255, 0.05)'
                                }}
                            >
                                <div className="flex items-center gap-3 min-w-0">
                                    <div className="w-8 h-8 rounded-xl flex items-center justify-center bg-[#7D39EB]/20 border border-[#7D39EB]/40 text-white shrink-0">
                                        <ActiveSubjectIcon className="w-4 h-4 text-white" />
                                    </div>
                                    <div className="min-w-0">
                                        <div className="text-sm font-bold text-white tracking-tight truncate">
                                            {activeSubjectObj.name}
                                        </div>
                                        <div className="text-[10px] text-slate-400 font-medium truncate">
                                            Экзаменационная спецификация Беларуси 2026
                                        </div>
                                    </div>
                                </div>
                                <div className="flex items-center gap-1.5 pl-2 shrink-0">
                                    <div className={`w-6 h-6 rounded-lg flex items-center justify-center bg-white/5 transition-transform duration-150 ${isSubjectDropdownOpen ? 'rotate-180 bg-[#7D39EB]/20 text-[#C6FF33]' : 'text-slate-400'}`}>
                                        <ChevronDown className="w-3.5 h-3.5" />
                                    </div>
                                </div>
                            </button>

                            {/* Dropdown Menu (Fast Hardware Accelerated Panel) */}
                            <AnimatePresence>
                                {isSubjectDropdownOpen && (
                                    <motion.div
                                        initial={{ opacity: 0, y: -4 }}
                                        animate={{ opacity: 1, y: 0 }}
                                        exit={{ opacity: 0, y: -4 }}
                                        transition={{ duration: 0.14, ease: 'easeOut' }}
                                        className="absolute left-0 right-0 top-full mt-2 z-50 rounded-2xl p-1.5 overflow-hidden shadow-2xl"
                                        style={{
                                            background: '#16161D',
                                            border: '1px solid rgba(255, 255, 255, 0.14)',
                                            boxShadow: '0 20px 40px rgba(0, 0, 0, 0.8), inset 0 1px 1px rgba(255, 255, 255, 0.12)',
                                            transform: 'translateZ(0)'
                                        }}
                                    >
                                        <div className="text-[10px] font-bold text-slate-400 uppercase tracking-wider px-3 py-1.5 flex items-center justify-between border-b border-white/10 mb-1">
                                            <span>Выберите предмет</span>
                                            <span className="text-[#C6FF33] font-mono">10 предметов</span>
                                        </div>
                                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-1 max-h-64 overflow-y-auto pr-1">
                                            {SUBJECTS.map((sub) => {
                                                const SubIcon = sub.icon;
                                                const isSel = selectedSubject === sub.id;
                                                return (
                                                    <button
                                                        key={sub.id}
                                                        type="button"
                                                        onClick={() => {
                                                            handleSubjectChange(sub.id);
                                                            setIsSubjectDropdownOpen(false);
                                                        }}
                                                        className={`w-full flex items-center justify-between px-2.5 py-2 rounded-xl text-xs font-semibold transition-colors duration-150 cursor-pointer text-left ${
                                                            isSel
                                                                ? 'bg-[#7D39EB] text-white shadow-xs'
                                                                : 'text-slate-300 hover:text-white hover:bg-white/10'
                                                        }`}
                                                    >
                                                        <div className="flex items-center gap-2 truncate">
                                                            <SubIcon className={`w-3.5 h-3.5 shrink-0 ${isSel ? 'text-white' : 'text-slate-400'}`} />
                                                            <span className="truncate">{sub.name}</span>
                                                        </div>
                                                        {isSel && <Check className="w-3.5 h-3.5 text-[#C6FF33] shrink-0 ml-1" />}
                                                    </button>
                                                );
                                            })}
                                        </div>
                                    </motion.div>
                                )}
                            </AnimatePresence>
                        </div>

                        {/* Grade Horizontal Segmented Control with Fluid Glass Slider */}
                        <div
                            className="p-1 rounded-2xl grid grid-cols-8 gap-1 relative overflow-hidden"
                            style={{
                                background: 'rgba(255, 255, 255, 0.03)',
                                border: '1px solid rgba(255, 255, 255, 0.08)',
                                boxShadow: 'inset 0 1px 2px rgba(0, 0, 0, 0.4)'
                            }}
                        >
                            {GRADES.map((g) => {
                                const isSel = selectedGrade === g.id;
                                return (
                                    <button
                                        key={g.id}
                                        type="button"
                                        onClick={() => handleGradeChange(g.id)}
                                        className="relative py-2 rounded-xl text-xs font-bold text-center cursor-pointer select-none active:scale-[0.94] transition-transform duration-100"
                                        title={g.label}
                                    >
                                        {/* Fluid Sliding Active Pill */}
                                        {isSel && (
                                            <motion.div
                                                layoutId="activeGradeLiquidPill"
                                                className="absolute inset-0 rounded-xl bg-[#7D39EB]"
                                                style={{
                                                    boxShadow: 'inset 0 1px 1px rgba(255, 255, 255, 0.35), 0 2px 8px rgba(125, 57, 235, 0.3)',
                                                    willChange: 'transform'
                                                }}
                                                transition={FAST_TWEEN}
                                            />
                                        )}
                                        <span className={`relative z-10 block truncate ${isSel ? 'text-white font-extrabold' : 'text-slate-400 hover:text-white transition-colors duration-150'}`}>
                                            {g.id === 'abiturient' ? 'Абит' : g.id}
                                        </span>
                                    </button>
                                );
                            })}
                        </div>
                    </div>
                </div>

                {/* STEP 2: TOPIC SELECTION */}
                <div className="pt-2 border-t border-white/[0.08] space-y-2">
                    <div className="flex items-center justify-between">
                        <label className="text-xs font-bold text-slate-200 uppercase tracking-wider">
                            2. Тема проверочной работы
                        </label>
                        <span className="text-[10px] text-slate-400 font-medium">
                            {currentSubjectName} • {selectedGrade === 'abiturient' ? 'Абитуриент' : selectedGrade + ' класс'}
                        </span>
                    </div>

                    <div className="relative group/topic">
                        <input
                            type="text"
                            value={topic}
                            onChange={(e) => setTopic(e.target.value)}
                            placeholder="Введите тему или выберите из программы..."
                            className="w-full rounded-2xl px-4 py-2.5 text-sm text-white placeholder-slate-500 focus:outline-none transition-colors duration-150 font-medium pr-10"
                            style={{
                                background: 'rgba(255, 255, 255, 0.04)',
                                border: '1px solid rgba(255, 255, 255, 0.12)',
                                boxShadow: 'inset 0 1px 2px rgba(0, 0, 0, 0.5)'
                            }}
                            onFocus={(e) => {
                                e.currentTarget.style.borderColor = 'rgba(125, 57, 235, 0.8)';
                            }}
                            onBlur={(e) => {
                                e.currentTarget.style.borderColor = 'rgba(255, 255, 255, 0.12)';
                            }}
                        />
                        {topic && (
                            <button
                                type="button"
                                onClick={() => setTopic('')}
                                className="absolute right-3 top-1/2 -translate-y-1/2 p-1 rounded-lg text-slate-400 hover:text-white hover:bg-white/10 transition-colors duration-150 cursor-pointer"
                                title="Очистить"
                            >
                                <X className="w-3.5 h-3.5" />
                            </button>
                        )}
                    </div>

                    {/* Verified Curriculum Topics from KTP Belarus */}
                    {currentTopics && currentTopics.length > 0 && (
                        <div className="space-y-1.5 pt-0.5">
                            <div className="text-[10px] font-semibold text-slate-400 flex items-center justify-between">
                                <span className="flex items-center gap-1.5">
                                    <BookOpen className="w-3 h-3 text-[#C6FF33]" />
                                    <span>Темы из программы ({selectedQuarter} четверть):</span>
                                </span>
                                <span className="text-slate-500">клик для выбора</span>
                            </div>
                            <div className="flex flex-wrap gap-1.5">
                                {currentTopics.slice(0, 3).map((topicItem, idx) => {
                                    const isActive = topic.trim().toLowerCase() === topicItem.trim().toLowerCase();
                                    return (
                                        <button
                                            key={idx}
                                            type="button"
                                            onClick={() => setTopic(topicItem)}
                                            className={`text-xs py-1.5 px-3 rounded-xl text-left truncate max-w-full cursor-pointer relative overflow-hidden active:scale-[0.98] transition-colors duration-150 ${
                                                isActive
                                                    ? 'bg-[#7D39EB]/30 border border-[#7D39EB] text-white font-semibold'
                                                    : 'bg-white/[0.04] border border-white/10 text-slate-300 hover:text-white hover:bg-white/[0.08] hover:border-white/20'
                                            }`}
                                            title={topicItem}
                                        >
                                            <span className="text-[#C6FF33] mr-1.5 font-bold">
                                                {isActive ? '✓' : '+'}
                                            </span>
                                            {topicItem}
                                        </button>
                                    );
                                })}
                            </div>
                        </div>
                    )}
                </div>

                {/* STEP 3: VARIANT COUNT (Liquid Segmented Control) */}
                <div className="pt-2 border-t border-white/[0.08] space-y-1.5">
                    <div className="flex items-center justify-between">
                        <label className="text-xs font-bold text-slate-200 uppercase tracking-wider block">
                            3. Количество вариантов на класс
                        </label>
                        <span className="text-[10px] text-slate-400 font-mono">
                            Все с разными числами
                        </span>
                    </div>

                    <div
                        className="grid grid-cols-3 gap-1.5 p-1 rounded-2xl relative overflow-hidden"
                        style={{
                            background: 'rgba(255, 255, 255, 0.03)',
                            border: '1px solid rgba(255, 255, 255, 0.08)',
                            boxShadow: 'inset 0 1px 2px rgba(0, 0, 0, 0.4)'
                        }}
                    >
                        {VARIANT_COUNTS.map((item) => {
                            const isSel = variantCount === item.count;
                            return (
                                <button
                                    key={item.count}
                                    type="button"
                                    onClick={() => setVariantCount(item.count)}
                                    className="py-2.5 rounded-xl text-xs font-semibold text-center cursor-pointer relative select-none active:scale-[0.96] transition-transform duration-100"
                                >
                                    {/* Apple Liquid Glass White Indicator */}
                                    {isSel && (
                                        <motion.div
                                            layoutId="activeVariantLiquidPill"
                                            className="absolute inset-0 rounded-xl bg-white"
                                            style={{
                                                boxShadow: '0 2px 8px rgba(0,0,0,0.3)',
                                                willChange: 'transform'
                                            }}
                                            transition={FAST_TWEEN}
                                        />
                                    )}

                                    <div className="relative z-10 flex items-center justify-center gap-1.5">
                                        <span className={isSel ? 'text-[#0B0B0E] font-extrabold' : 'text-slate-300 hover:text-white transition-colors duration-150'}>
                                            {item.label}
                                        </span>
                                        {item.popular && (
                                            <span
                                                className={`text-[9px] px-1.5 py-0.5 rounded-full font-bold uppercase tracking-wider transition-colors duration-150 ${
                                                    isSel
                                                        ? 'bg-[#7D39EB] text-white'
                                                        : 'bg-white/10 text-slate-400'
                                                }`}
                                            >
                                                класс
                                            </span>
                                        )}
                                    </div>
                                </button>
                            );
                        })}
                    </div>
                </div>

                {/* ADVANCED SETTINGS ACCORDION (Apple Control Center Glass Card) */}
                <div className="pt-2 border-t border-white/[0.08] space-y-2">
                    <button
                        type="button"
                        onClick={() => setShowAdvancedSettings(!showAdvancedSettings)}
                        className="w-full flex items-center justify-between p-3 rounded-2xl text-left cursor-pointer group select-none relative overflow-hidden transition-colors duration-150"
                        style={{
                            background: showAdvancedSettings ? 'rgba(255, 255, 255, 0.06)' : 'rgba(255, 255, 255, 0.03)',
                            border: showAdvancedSettings
                                ? '1px solid rgba(125, 57, 235, 0.5)'
                                : '1px solid rgba(255, 255, 255, 0.1)',
                            boxShadow: 'inset 0 1px 1px rgba(255, 255, 255, 0.08)'
                        }}
                    >
                        <div className="flex items-center gap-2.5 min-w-0">
                            <div className="w-7 h-7 rounded-xl flex items-center justify-center bg-white/5 border border-white/10 text-[#C6FF33] shrink-0">
                                <SlidersHorizontal className="w-3.5 h-3.5" />
                            </div>
                            <div className="min-w-0">
                                <div className="text-xs font-bold text-white truncate flex items-center gap-1.5">
                                    <span>Тонкие настройки заданий</span>
                                    <span className="text-[10px] text-slate-400 font-normal hidden sm:inline">(печать, структура)</span>
                                </div>
                                <div className="text-[10px] text-slate-400 truncate flex items-center gap-1.5 pt-0.5 font-medium">
                                    <span className="px-1.5 py-0.2 rounded bg-white/5 text-slate-300 font-mono">
                                        {tasksCountA + tasksCountB} зад.
                                    </span>
                                    <span>•</span>
                                    <span>{printLayout === 'economy' ? '2 вар./лист ✂' : '1 вар./лист'}</span>
                                    <span>•</span>
                                    <span className="text-slate-400 truncate">{selectedWorkTypeObj?.shortLabel || selectedWorkTypeObj?.name}</span>
                                </div>
                            </div>
                        </div>
                        <div className={`w-6 h-6 rounded-lg flex items-center justify-center bg-white/5 transition-transform duration-150 text-slate-400 shrink-0 ${showAdvancedSettings ? 'rotate-180 bg-[#7D39EB]/20 text-[#C6FF33]' : 'group-hover:text-white'}`}>
                            <ChevronDown className="w-4 h-4" />
                        </div>
                    </button>

                    <AnimatePresence>
                        {showAdvancedSettings && (
                            <motion.div
                                initial={{ opacity: 0, height: 0 }}
                                animate={{ opacity: 1, height: 'auto' }}
                                exit={{ opacity: 0, height: 0 }}
                                transition={{ duration: 0.18, ease: 'easeOut' }}
                                className="overflow-hidden space-y-3 pt-1"
                            >
                                <div
                                    className="p-4 rounded-2xl space-y-3.5 text-xs"
                                    style={{
                                        background: 'rgba(18, 18, 24, 0.90)',
                                        border: '1px solid rgba(255, 255, 255, 0.1)',
                                        boxShadow: 'inset 0 1px 2px rgba(0, 0, 0, 0.4)'
                                    }}
                                >
                                    {/* Type of work */}
                                    <div>
                                        <label className="text-[11px] font-bold text-slate-200 uppercase tracking-wider block mb-1.5">
                                            Тип проверочной работы:
                                        </label>
                                        <div className="grid grid-cols-3 gap-1.5">
                                            {WORK_TYPES.slice(0, 3).map((wt) => {
                                                const isSel = selectedWorkType === wt.id;
                                                return (
                                                    <button
                                                        key={wt.id}
                                                        type="button"
                                                        onClick={() => handleWorkTypeChange(wt.id)}
                                                        className={`py-2 px-2 rounded-xl text-xs font-bold text-center cursor-pointer border select-none active:scale-[0.96] transition-colors duration-150 ${
                                                            isSel
                                                                ? 'bg-[#7D39EB] border-[#7D39EB] text-white shadow-xs'
                                                                : 'bg-white/[0.03] border-white/10 text-slate-300 hover:text-white hover:bg-white/[0.08]'
                                                        }`}
                                                    >
                                                        {wt.shortLabel || wt.name}
                                                    </button>
                                                );
                                            })}
                                        </div>
                                    </div>

                                    {/* Task Counters: Part A & Part B */}
                                    <div className="grid grid-cols-2 gap-3 pt-2 border-t border-white/[0.08]">
                                        <div>
                                            <div className="text-[11px] font-bold text-slate-200 mb-1.5 flex justify-between items-center">
                                                <span>С выбором (А):</span>
                                                <span className="text-[#C6FF33] font-mono px-1.5 py-0.5 rounded bg-[#C6FF33]/10 font-bold">
                                                    {tasksCountA} зад.
                                                </span>
                                            </div>
                                            <div
                                                className="grid grid-cols-5 gap-1 p-1 rounded-xl relative"
                                                style={{
                                                    background: 'rgba(0, 0, 0, 0.4)',
                                                    border: '1px solid rgba(255, 255, 255, 0.08)'
                                                }}
                                            >
                                                {[1, 2, 3, 4, 5].map((cnt) => {
                                                    const isSel = tasksCountA === cnt;
                                                    return (
                                                        <button
                                                            key={cnt}
                                                            type="button"
                                                            onClick={() => setTasksCountA(cnt)}
                                                            className="relative py-1 text-[11px] rounded-lg font-bold text-center cursor-pointer select-none active:scale-[0.92] transition-transform duration-100"
                                                        >
                                                            {isSel && (
                                                                <motion.div
                                                                    layoutId="activeTasksAGlass"
                                                                    className="absolute inset-0 rounded-lg bg-[#7D39EB]"
                                                                    style={{
                                                                        boxShadow: 'inset 0 1px 1px rgba(255,255,255,0.4)',
                                                                        willChange: 'transform'
                                                                    }}
                                                                    transition={FAST_TWEEN}
                                                                />
                                                            )}
                                                            <span className={`relative z-10 ${isSel ? 'text-white font-extrabold' : 'text-slate-400 hover:text-white transition-colors duration-150'}`}>
                                                                {cnt}
                                                            </span>
                                                        </button>
                                                    );
                                                })}
                                            </div>
                                        </div>

                                        <div>
                                            <div className="text-[11px] font-bold text-slate-200 mb-1.5 flex justify-between items-center">
                                                <span>Открытые (Б):</span>
                                                <span className="text-[#C6FF33] font-mono px-1.5 py-0.5 rounded bg-[#C6FF33]/10 font-bold">
                                                    {tasksCountB} зад.
                                                </span>
                                            </div>
                                            <div
                                                className="grid grid-cols-5 gap-1 p-1 rounded-xl relative"
                                                style={{
                                                    background: 'rgba(0, 0, 0, 0.4)',
                                                    border: '1px solid rgba(255, 255, 255, 0.08)'
                                                }}
                                            >
                                                {[0, 1, 2, 3, 4].map((cnt) => {
                                                    const isSel = tasksCountB === cnt;
                                                    return (
                                                        <button
                                                            key={cnt}
                                                            type="button"
                                                            onClick={() => setTasksCountB(cnt)}
                                                            className="relative py-1 text-[11px] rounded-lg font-bold text-center cursor-pointer select-none active:scale-[0.92] transition-transform duration-100"
                                                        >
                                                            {isSel && (
                                                                <motion.div
                                                                    layoutId="activeTasksBGlass"
                                                                    className="absolute inset-0 rounded-lg bg-[#7D39EB]"
                                                                    style={{
                                                                        boxShadow: 'inset 0 1px 1px rgba(255,255,255,0.4)',
                                                                        willChange: 'transform'
                                                                    }}
                                                                    transition={FAST_TWEEN}
                                                                />
                                                            )}
                                                            <span className={`relative z-10 ${isSel ? 'text-white font-extrabold' : 'text-slate-400 hover:text-white transition-colors duration-150'}`}>
                                                                {cnt}
                                                            </span>
                                                        </button>
                                                    );
                                                })}
                                            </div>
                                        </div>
                                    </div>

                                    {/* Print layout */}
                                    <div className="pt-2 border-t border-white/[0.08]">
                                        <label className="text-[11px] font-bold text-slate-200 uppercase tracking-wider block mb-1.5">
                                            Раскладка для печати на А4:
                                        </label>
                                        <div className="grid grid-cols-2 gap-2">
                                            <button
                                                type="button"
                                                onClick={() => setPrintLayout('economy')}
                                                className={`p-2.5 rounded-2xl border text-left cursor-pointer relative overflow-hidden transition-colors duration-150 ${
                                                    printLayout === 'economy'
                                                        ? 'bg-[#7D39EB]/20 border-[#7D39EB] text-white shadow-xs'
                                                        : 'bg-white/[0.03] border-white/10 text-slate-300 hover:text-white hover:bg-white/[0.06]'
                                                }`}
                                            >
                                                <div className="flex items-center justify-between mb-1">
                                                    <span className="text-xs font-bold flex items-center gap-1.5">
                                                        <Printer className="w-3.5 h-3.5 text-[#C6FF33]" />
                                                        2 вар./лист А4 ✂
                                                    </span>
                                                    {printLayout === 'economy' && (
                                                        <span className="w-2 h-2 rounded-full bg-[#C6FF33]" />
                                                    )}
                                                </div>
                                                <div className="text-[10px] text-emerald-400 font-semibold">
                                                    Экономия бумаги 50%
                                                </div>
                                            </button>

                                            <button
                                                type="button"
                                                onClick={() => setPrintLayout('standard')}
                                                className={`p-2.5 rounded-2xl border text-left cursor-pointer relative overflow-hidden transition-colors duration-150 ${
                                                    printLayout === 'standard'
                                                        ? 'bg-[#7D39EB]/20 border-[#7D39EB] text-white shadow-xs'
                                                        : 'bg-white/[0.03] border-white/10 text-slate-300 hover:text-white hover:bg-white/[0.06]'
                                                }`}
                                            >
                                                <div className="flex items-center justify-between mb-1">
                                                    <span className="text-xs font-bold flex items-center gap-1.5">
                                                        <FileText className="w-3.5 h-3.5 text-slate-300" />
                                                        1 вар./лист А4
                                                    </span>
                                                    {printLayout === 'standard' && (
                                                        <span className="w-2 h-2 rounded-full bg-[#C6FF33]" />
                                                    )}
                                                </div>
                                                <div className="text-[10px] text-slate-400">
                                                    Крупный школьный шрифт
                                                </div>
                                            </button>
                                        </div>
                                    </div>

                                    {/* Quarter Switcher inside settings */}
                                    <div className="pt-2 border-t border-white/[0.08]">
                                        <label className="text-[11px] font-bold text-slate-200 uppercase tracking-wider block mb-1.5">
                                            Четверть программы КТП:
                                        </label>
                                        <div
                                            className="grid grid-cols-4 gap-1 p-1 rounded-xl relative"
                                            style={{
                                                background: 'rgba(0, 0, 0, 0.4)',
                                                border: '1px solid rgba(255, 255, 255, 0.08)'
                                            }}
                                        >
                                            {[
                                                { id: '1', label: 'I четв.' },
                                                { id: '2', label: 'II четв.' },
                                                { id: '3', label: 'III четв.' },
                                                { id: '4', label: 'IV четв.' }
                                            ].map((q) => {
                                                const isSel = selectedQuarter === q.id;
                                                return (
                                                    <button
                                                        key={q.id}
                                                        type="button"
                                                        onClick={() => setSelectedQuarter(q.id)}
                                                        className="relative py-1 rounded-lg text-[11px] font-semibold text-center cursor-pointer select-none active:scale-[0.94] transition-transform duration-100"
                                                    >
                                                        {isSel && (
                                                            <motion.div
                                                                layoutId="activeQuarterGlass"
                                                                className="absolute inset-0 rounded-lg bg-[#7D39EB]"
                                                                style={{
                                                                    boxShadow: 'inset 0 1px 1px rgba(255,255,255,0.4)',
                                                                    willChange: 'transform'
                                                                }}
                                                                transition={FAST_TWEEN}
                                                            />
                                                        )}
                                                        <span className={`relative z-10 ${isSel ? 'text-white font-bold' : 'text-slate-400 hover:text-white transition-colors duration-150'}`}>
                                                            {q.label}
                                                        </span>
                                                    </button>
                                                );
                                            })}
                                        </div>
                                    </div>
                                </div>
                            </motion.div>
                        )}
                    </AnimatePresence>
                </div>

                {/* PRIMARY ACTION CTA BUTTON */}
                <div className="pt-2 space-y-2.5">
                    <button
                        type="button"
                        onClick={() => setShowPayModal(true)}
                        className="w-full relative group/cta py-4 px-6 rounded-2xl bg-[#7D39EB] hover:bg-[#6D28D9] text-white font-bold text-base tracking-tight cursor-pointer overflow-hidden select-none active:scale-[0.98] transition-all"
                        style={{
                            boxShadow: 'inset 0 1px 1px 0 rgba(255, 255, 255, 0.35), 0 10px 25px -4px rgba(125, 57, 235, 0.35)'
                        }}
                    >
                        <div className="relative z-10 flex items-center justify-center gap-2">
                            <span>Сформировать комплект на урок — 5 BYN</span>
                            <ArrowRight className="w-4 h-4 group-hover/cta:translate-x-1 transition-transform duration-150" />
                        </div>
                    </button>

                    {/* Trust badges in clean light capsules */}
                    <div className="flex items-center justify-center gap-2 text-[11px] text-slate-300 font-medium">
                        <span className="px-2 py-0.5 rounded-full bg-white/[0.04] border border-white/10">
                            Чек НПД
                        </span>
                        <span className="text-slate-500">•</span>
                        <span className="px-2 py-0.5 rounded-full bg-white/[0.04] border border-white/10">
                            Альфа-Банк
                        </span>
                        <span className="text-slate-500">•</span>
                        <span className="px-2 py-0.5 rounded-full bg-white/[0.04] border border-white/10">
                            Без подписок
                        </span>
                    </div>
                </div>

            </div>
        </div>
    );
}
