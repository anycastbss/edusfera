import React from 'react';
import { Sliders, X, Check, Sparkles, GraduationCap, Target, Zap, Lightbulb, FileText, CheckCircle2 } from 'lucide-react';

const PERSONAS = [
    {
        id: 'methodologist',
        title: 'ИИ-Методист',
        desc: 'Технологические карты занятий, тайминг, дидактика и педагогические приемы',
        icon: GraduationCap,
        badge: 'Основной',
    },
    {
        id: 'rikz_expert',
        title: 'Эксперт РИКЗ 2026',
        desc: 'Спецификации ЦТ/ЦЭ, разбор каверзных ловушек в частях А и Б, шкалы перевода',
        icon: Target,
        badge: 'ЦТ/ЦЭ',
    },
    {
        id: 'express_solver',
        title: 'Экспресс-решатель',
        desc: 'Строгое пошаговое математическое доказательство с чистым оформлением формул LaTeX',
        icon: Zap,
        badge: 'Задачи',
    },
    {
        id: 'simple_analogy',
        title: 'Наглядные аналогии',
        desc: 'Объяснение сложнейших теорем и формул «на пальцах» и жизненных примерах',
        icon: Lightbulb,
        badge: 'Мнемоника',
    },
    {
        id: 'homework_gen',
        title: 'Генератор ДЗ и проверочных',
        desc: 'Дифференцированные блоки задач по 3 уровням сложности с ответами для проверки',
        icon: FileText,
        badge: 'Практика',
    },
];

const TONES = [
    { id: 'friendly', label: 'Дружелюбный', desc: 'Воодушевляющий тон наставника' },
    { id: 'academic', label: 'Академический', desc: 'Строгая научная терминология' },
    { id: 'step_by_step', label: 'Пошаговый', desc: 'По пунктам: Шаг 1, Шаг 2...' },
    { id: 'concise', label: 'Краткий', desc: 'Без лишней воды, строго по сути' },
];

const LENGTHS = [
    { id: 'balanced', label: 'Сбалансированный' },
    { id: 'short', label: 'Компактный конспект' },
    { id: 'detailed', label: 'Развернутый разбор' },
];

const SUBJECTS = [
    'Математика',
    'Физика',
    'Русский язык',
    'Английский язык',
    'Химия',
    'Биология',
    'История Беларуси',
    'Обществоведение',
];

export default function SettingsDrawer({
    isOpen,
    onClose,
    persona,
    setPersona,
    tone,
    setTone,
    length,
    setLength,
    subject,
    setSubject,
}) {
    if (!isOpen) return null;

    return (
        <div className="fixed inset-0 z-50 flex justify-end">
            {/* Backdrop */}
            <div
                className="fixed inset-0 bg-black/40 backdrop-blur-xs transition-opacity animate-fade-in"
                onClick={onClose}
            />

            {/* Drawer Container */}
            <div className="relative w-full max-w-md bg-white dark:bg-slate-900 h-full shadow-2xl z-10 flex flex-col border-l border-slate-200 dark:border-slate-800">
                {/* Header */}
                <div className="p-5 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
                    <div className="flex items-center gap-2.5">
                        <div className="w-8 h-8 rounded-xl bg-violet-100 dark:bg-violet-950/60 text-violet-600 dark:text-violet-400 flex items-center justify-center">
                            <Sliders className="w-4 h-4" />
                        </div>
                        <div>
                            <h3 className="font-bold text-sm text-slate-900 dark:text-white">Параметры ассистента</h3>
                            <p className="text-[11px] text-slate-500 dark:text-slate-400">Настройка стиля, персоны и предмета</p>
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

                {/* Content */}
                <div className="flex-1 overflow-y-auto p-5 space-y-6">
                    {/* 1. Subject */}
                    <div>
                        <label className="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2">
                            Предмет преподавания
                        </label>
                        <div className="flex flex-wrap gap-1.5">
                            {SUBJECTS.map((s) => (
                                <button
                                    key={s}
                                    type="button"
                                    onClick={() => setSubject(s)}
                                    className={`px-3 py-1.5 rounded-xl text-xs font-semibold transition-all ${
                                        subject === s
                                            ? 'bg-violet-600 text-white shadow-xs'
                                            : 'bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700'
                                    }`}
                                >
                                    {s}
                                </button>
                            ))}
                        </div>
                    </div>

                    {/* 2. Persona */}
                    <div>
                        <label className="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2.5">
                            Специализация / Роль
                        </label>
                        <div className="space-y-2">
                            {PERSONAS.map((p) => {
                                const Icon = p.icon;
                                const isSelected = persona === p.id;
                                return (
                                    <button
                                        key={p.id}
                                        type="button"
                                        onClick={() => setPersona(p.id)}
                                        className={`w-full text-left p-3.5 rounded-2xl border transition-all flex items-start gap-3 ${
                                            isSelected
                                                ? 'bg-violet-50/80 dark:bg-violet-950/40 border-violet-300 dark:border-violet-700 ring-2 ring-violet-500/20 shadow-xs'
                                                : 'bg-white dark:bg-slate-800/60 border-slate-200/80 dark:border-slate-700/60 hover:border-slate-300 dark:hover:border-slate-600'
                                        }`}
                                    >
                                        <div className={`w-8 h-8 rounded-xl flex items-center justify-center shrink-0 ${
                                            isSelected
                                                ? 'bg-violet-600 text-white'
                                                : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400'
                                        }`}>
                                            <Icon className="w-4 h-4" />
                                        </div>
                                        <div className="flex-1 min-w-0">
                                            <div className="flex items-center justify-between gap-1">
                                                <span className="text-xs font-bold text-slate-900 dark:text-white">
                                                    {p.title}
                                                </span>
                                                <span className="text-[10px] px-2 py-0.5 rounded-md font-bold bg-[#C6FF33] text-black">
                                                    {p.badge}
                                                </span>
                                            </div>
                                            <p className="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5 leading-relaxed">
                                                {p.desc}
                                            </p>
                                        </div>
                                    </button>
                                );
                            })}
                        </div>
                    </div>

                    {/* 3. Tone */}
                    <div>
                        <label className="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2">
                            Тональность и стиль ответов
                        </label>
                        <div className="grid grid-cols-2 gap-2">
                            {TONES.map((t) => (
                                <button
                                    key={t.id}
                                    type="button"
                                    onClick={() => setTone(t.id)}
                                    className={`p-2.5 rounded-xl border text-left transition-all ${
                                        tone === t.id
                                            ? 'bg-violet-50 dark:bg-violet-950/40 border-violet-300 dark:border-violet-700 text-violet-900 dark:text-violet-200'
                                            : 'bg-white dark:bg-slate-800/60 border-slate-200/80 dark:border-slate-700/60 text-slate-700 dark:text-slate-300'
                                    }`}
                                >
                                    <div className="flex items-center justify-between">
                                        <span className="text-xs font-semibold">{t.label}</span>
                                        {tone === t.id && <Check className="w-3.5 h-3.5 text-violet-600" />}
                                    </div>
                                    <p className="text-[10px] text-slate-400 dark:text-slate-500 mt-0.5 truncate">{t.desc}</p>
                                </button>
                            ))}
                        </div>
                    </div>

                    {/* 4. Length */}
                    <div>
                        <label className="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2">
                            Глубина проработки
                        </label>
                        <div className="flex rounded-xl p-1 bg-slate-100 dark:bg-slate-800 border border-slate-200/70 dark:border-slate-700">
                            {LENGTHS.map((l) => (
                                <button
                                    key={l.id}
                                    type="button"
                                    onClick={() => setLength(l.id)}
                                    className={`flex-1 py-1.5 text-center text-xs font-semibold rounded-lg transition-all ${
                                        length === l.id
                                            ? 'bg-white dark:bg-slate-700 text-slate-900 dark:text-white shadow-2xs'
                                            : 'text-slate-600 dark:text-slate-400 hover:text-slate-900'
                                    }`}
                                >
                                    {l.label}
                                </button>
                            ))}
                        </div>
                    </div>
                </div>

                {/* Footer */}
                <div className="p-4 border-t border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/20">
                    <button
                        type="button"
                        onClick={onClose}
                        className="w-full py-2.5 rounded-xl bg-violet-600 hover:bg-violet-700 text-white font-bold text-xs shadow-sm transition-all"
                    >
                        Применить параметры
                    </button>
                </div>
            </div>
        </div>
    );
}
