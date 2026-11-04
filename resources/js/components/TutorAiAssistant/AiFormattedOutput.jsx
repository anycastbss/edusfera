import React, { useMemo, useState } from 'react';
import { marked } from 'marked';
import katex from 'katex';
import { 
    Copy, 
    Check, 
    Printer, 
    Download, 
    Sparkles, 
    ThumbsUp,
    ThumbsDown,
    RotateCcw,
    ZoomIn,
    ZoomOut
} from 'lucide-react';

/**
 * Enhanced LaTeX & Markdown parser
 * Handles:
 * - Mnemonic rules (> [!MNEMONIC] or formatted blocks)
 * - Block math $$ ... $$ and \[ ... \]
 * - Inline math $ ... $ and \( ... \)
 * - Callout blocks (> [!WARNING], > [!NOTE], etc.)
 * - GFM tables, code blocks, lists
 */
export function formatAiMarkdown(rawText) {
    if (!rawText || typeof rawText !== 'string') return '';

    let text = rawText;

    // 0. Protect fenced code blocks first so we don't parse formulas inside code
    const codeBlocks = [];
    text = text.replace(/```[\s\S]*?```/g, (match) => {
        const id = `__CODE_BLOCK_${codeBlocks.length}__`;
        codeBlocks.push({ id, content: match });
        return id;
    });

    // Also protect inline code
    const inlineCodes = [];
    text = text.replace(/`[^`\n]+?`/g, (match) => {
        const id = `__INLINE_CODE_${inlineCodes.length}__`;
        inlineCodes.push({ id, content: match });
        return id;
    });

    // 1. Pre-process Mnemonic Rules (> [!MNEMONIC] Title \n Body)
    text = text.replace(
        /^>\s*\[!(MNEMONIC|MEMORY|RULE)\]\s*(.*?)$((?:\n>.*)*)/gim,
        (match, type, title, body) => {
            const cleanBody = body.replace(/^>\s?/gm, '').trim();
            const heading = title.trim() || 'Мнемоническое правило:';
            return `\n\n<div class="ai-mnemonic-card">\n  <div class="ai-mnemonic-header">\n    <span class="ai-mnemonic-title">🧠 ${heading}</span>\n    <button type="button" class="ai-mnemonic-copy-btn" onclick="navigator.clipboard.writeText(this.getAttribute('data-copy')); this.classList.add('copied'); setTimeout(()=>this.classList.remove('copied'), 2000)" data-copy="${cleanBody.replace(/"/g, '&quot;')}">\n      <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="14" height="14" x="8" y="8" rx="2" ry="2"/><path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"/></svg>\n    </button>\n  </div>\n  <div class="ai-mnemonic-body">\n${cleanBody}\n  </div>\n</div>\n\n`;
        }
    );

    // 2. Pre-process Callouts: Warning / Trap
    text = text.replace(
        /^>\s*\[!(WARNING|CAUTION|DANGER)\]\s*(.*?)$((?:\n>.*)*)/gim,
        (match, type, title, body) => {
            const cleanBody = body.replace(/^>\s?/gm, '').trim();
            const heading = title.trim() || 'Внимание / Ловушка';
            return `\n\n<div class="ai-callout ai-callout-warning">\n<div class="ai-callout-title">⚠️ ${heading}</div>\n<div class="ai-callout-body">\n\n${cleanBody}\n\n</div>\n</div>\n\n`;
        }
    );

    // 3. Pre-process Callouts: Tip
    text = text.replace(
        /^>\s*\[!(TIP|SUCCESS|RECOMMENDED)\]\s*(.*?)$((?:\n>.*)*)/gim,
        (match, type, title, body) => {
            const cleanBody = body.replace(/^>\s?/gm, '').trim();
            const heading = title.trim() || 'Методический совет';
            return `\n\n<div class="ai-callout ai-callout-tip">\n<div class="ai-callout-title">💡 ${heading}</div>\n<div class="ai-callout-body">\n\n${cleanBody}\n\n</div>\n</div>\n\n`;
        }
    );

    // 4. Pre-process Callouts: Note / Info
    text = text.replace(
        /^>\s*\[!(NOTE|INFO|IMPORTANT)\]\s*(.*?)$((?:\n>.*)*)/gim,
        (match, type, title, body) => {
            const cleanBody = body.replace(/^>\s?/gm, '').trim();
            const heading = title.trim() || 'Важное примечание';
            return `\n\n<div class="ai-callout ai-callout-info">\n<div class="ai-callout-title">📌 ${heading}</div>\n<div class="ai-callout-body">\n\n${cleanBody}\n\n</div>\n</div>\n\n`;
        }
    );

    // 5. Extract Block Math: $$ ... $$ and \[ ... \]
    const mathBlocks = [];
    text = text.replace(/\$\$([\s\S]+?)\$\$/g, (match, formula) => {
        const id = `__MATH_BLOCK_${mathBlocks.length}__`;
        mathBlocks.push({ id, formula: formula.trim() });
        return `\n\n${id}\n\n`;
    });

    text = text.replace(/\\\[([\s\S]+?)\\\]/g, (match, formula) => {
        const id = `__MATH_BLOCK_${mathBlocks.length}__`;
        mathBlocks.push({ id, formula: formula.trim() });
        return `\n\n${id}\n\n`;
    });

    // 6. Extract Inline Math: $ ... $ and \( ... \)
    const mathInlines = [];
    text = text.replace(/(?<!\\|\$)\$([^\$\n]+?)\$(?!\$)/g, (match, formula) => {
        if (/^\s*\d+([.,]\d+)?\s*$/.test(formula)) {
            return match;
        }
        const id = `__MATH_INLINE_${mathInlines.length}__`;
        mathInlines.push({ id, formula: formula.trim() });
        return id;
    });

    text = text.replace(/\\\(([\s\S]+?)\\\)/g, (match, formula) => {
        const id = `__MATH_INLINE_${mathInlines.length}__`;
        mathInlines.push({ id, formula: formula.trim() });
        return id;
    });

    // Restore protected code blocks
    codeBlocks.forEach((item) => {
        text = text.replace(item.id, item.content);
    });
    inlineCodes.forEach((item) => {
        text = text.replace(item.id, item.content);
    });

    // Configure marked options
    marked.setOptions({
        gfm: true,
        breaks: true,
    });

    let html = marked.parse(text);

    // 7. Safely render KaTeX into display blocks
    mathBlocks.forEach((item) => {
        let rendered = '';
        try {
            rendered = katex.renderToString(item.formula, {
                displayMode: true,
                throwOnError: false,
                output: 'html',
            });
        } catch (e) {
            rendered = `<div class="p-2 text-rose-500 font-mono text-xs">${item.formula}</div>`;
        }
        const blockHtml = `<div class="katex-display-wrapper my-4 py-3 px-4 rounded-2xl bg-slate-50/90 dark:bg-slate-800/80 border border-slate-200/80 dark:border-slate-700/80 overflow-x-auto text-center shadow-2xs transition-colors">${rendered}</div>`;
        const pRegex = new RegExp(`<p>\\s*${item.id}\\s*<\\/p>`, 'g');
        if (pRegex.test(html)) {
            html = html.replace(pRegex, blockHtml);
        } else {
            html = html.split(item.id).join(blockHtml);
        }
    });

    // 8. Safely render KaTeX into inline formulas
    mathInlines.forEach((item) => {
        let rendered = '';
        try {
            rendered = katex.renderToString(item.formula, {
                displayMode: false,
                throwOnError: false,
                output: 'html',
            });
        } catch (e) {
            rendered = `<span class="text-rose-500 font-mono text-xs">${item.formula}</span>`;
        }
        const inlineHtml = `<span class="katex-inline-wrapper">${rendered}</span>`;
        html = html.split(item.id).join(inlineHtml);
    });

    return html;
}

/**
 * Message Card with Copy, Like/Dislike & Regenerate actions matching reference mockup
 */
export function AiMessageCard({
    content = '',
    onRegenerate = null,
    className = '',
    greeting = '',
    mnemonicData = null,
}) {
    const [copied, setCopied] = useState(false);
    const [feedback, setFeedback] = useState(null); // 'like' | 'dislike' | null

    const htmlContent = useMemo(() => {
        return formatAiMarkdown(content);
    }, [content]);

    const handleCopy = async () => {
        try {
            await navigator.clipboard.writeText(content);
            setCopied(true);
            setTimeout(() => setCopied(false), 2000);
        } catch (err) {
            console.error('Failed to copy: ', err);
        }
    };

    return (
        <div className={`rounded-3xl p-5 sm:p-6 bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800 shadow-sm space-y-4 ${className}`}>
            {greeting && (
                <h3 className="text-base sm:text-lg font-bold text-slate-900 dark:text-white flex items-center gap-2">
                    {greeting}
                </h3>
            )}

            {/* Markdown / KaTeX Content */}
            {content && (
                <div
                    className="ai-content-canvas text-[13.5px] leading-relaxed text-slate-800 dark:text-slate-100"
                    dangerouslySetInnerHTML={{ __html: htmlContent }}
                />
            )}

            {/* Explicit Mnemonic Rule Card if provided as data */}
            {mnemonicData && (
                <div className="ai-mnemonic-card">
                    <div className="ai-mnemonic-header">
                        <span className="ai-mnemonic-title">🧠 {mnemonicData.title || 'Мнемоническое правило:'}</span>
                        <button
                            type="button"
                            onClick={() => {
                                navigator.clipboard.writeText(mnemonicData.formula || mnemonicData.text);
                            }}
                            className="ai-mnemonic-copy-btn"
                            title="Копировать правило"
                        >
                            <Copy className="w-3.5 h-3.5" />
                        </button>
                    </div>
                    <div className="ai-mnemonic-body">
                        {mnemonicData.formula || mnemonicData.text}
                    </div>
                </div>
            )}

            {/* Action Toolbar under message */}
            <div className="flex items-center justify-between pt-2 border-t border-slate-100 dark:border-slate-800/80 text-slate-400">
                <div className="flex items-center gap-1">
                    <button
                        type="button"
                        onClick={handleCopy}
                        title="Копировать сообщение"
                        className="p-1.5 rounded-lg hover:text-slate-700 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
                    >
                        {copied ? <Check className="w-4 h-4 text-emerald-500" /> : <Copy className="w-4 h-4" />}
                    </button>
                    <button
                        type="button"
                        onClick={() => setFeedback(feedback === 'like' ? null : 'like')}
                        title="Полезно"
                        className={`p-1.5 rounded-lg transition-colors ${
                            feedback === 'like'
                                ? 'text-violet-600 bg-violet-50 dark:bg-violet-950/40'
                                : 'hover:text-slate-700 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800'
                        }`}
                    >
                        <ThumbsUp className="w-4 h-4" />
                    </button>
                    <button
                        type="button"
                        onClick={() => setFeedback(feedback === 'dislike' ? null : 'dislike')}
                        title="Не подошло"
                        className={`p-1.5 rounded-lg transition-colors ${
                            feedback === 'dislike'
                                ? 'text-rose-600 bg-rose-50 dark:bg-rose-950/40'
                                : 'hover:text-slate-700 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800'
                        }`}
                    >
                        <ThumbsDown className="w-4 h-4" />
                    </button>
                </div>

                {onRegenerate && (
                    <button
                        type="button"
                        onClick={onRegenerate}
                        className="flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-semibold text-slate-600 dark:text-slate-300 hover:text-violet-600 dark:hover:text-violet-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition-all active:scale-95"
                    >
                        <RotateCcw className="w-3.5 h-3.5" />
                        <span>Перегенерировать</span>
                    </button>
                )}
            </div>
        </div>
    );
}

/**
 * Standard Standalone Formatted Output for Document Mode
 */
export default function AiFormattedOutput({
    content = '',
    isLoading = false,
    onRegenerate = null,
    title = '',
    subtitle = '',
    className = '',
}) {
    const [copiedFormat, setCopiedFormat] = useState(null);
    const [fontSize, setFontSize] = useState('normal'); // 'compact' | 'normal' | 'large'

    const htmlContent = useMemo(() => {
        return formatAiMarkdown(content);
    }, [content]);

    const handleCopyMarkdown = async () => {
        try {
            await navigator.clipboard.writeText(content);
            setCopiedFormat('md');
            setTimeout(() => setCopiedFormat(null), 2000);
        } catch (err) {
            console.error('Failed to copy: ', err);
        }
    };

    const handlePrint = () => {
        window.print();
    };

    const handleDownloadDoc = () => {
        const element = document.createElement('a');
        const file = new Blob([content], { type: 'text/markdown;charset=utf-8' });
        element.href = URL.createObjectURL(file);
        element.download = `${title || 'edusfera-ai-output'}-${new Date().toISOString().slice(0, 10)}.md`;
        document.body.appendChild(element);
        element.click();
        document.body.removeChild(element);
    };

    const fontSizeStyles = {
        compact: 'text-[12.5px] leading-relaxed',
        normal: 'text-[14px] leading-relaxed',
        large: 'text-[15.5px] leading-loose',
    }[fontSize];

    if (isLoading) {
        return (
            <div className={`rounded-3xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900/90 p-8 shadow-sm ${className}`}>
                <div className="flex items-center gap-3 mb-6">
                    <div className="w-10 h-10 rounded-2xl bg-gradient-to-tr from-violet-600 to-indigo-500 text-white flex items-center justify-center shadow-md shadow-violet-500/20 animate-pulse">
                        <Sparkles className="w-5 h-5 animate-spin" style={{ animationDuration: '4s' }} />
                    </div>
                    <div>
                        <h4 className="font-bold text-sm text-slate-900 dark:text-white flex items-center gap-2">
                            <span>Генерация ответа через Edusfera AI</span>
                            <span className="inline-block w-2 h-2 rounded-full bg-emerald-400 animate-ping"></span>
                        </h4>
                        <p className="text-xs text-slate-500 dark:text-slate-400">
                            Анализ методики, экзаменационной программы 2026 и составление конспекта...
                        </p>
                    </div>
                </div>

                <div className="space-y-4 animate-pulse pt-2">
                    <div className="h-4 bg-slate-200 dark:bg-slate-800 rounded-full w-3/4"></div>
                    <div className="h-4 bg-slate-200 dark:bg-slate-800 rounded-full w-full"></div>
                    <div className="h-4 bg-slate-200 dark:bg-slate-800 rounded-full w-5/6"></div>
                    
                    <div className="my-6 p-4 rounded-2xl border border-slate-100 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/40 space-y-2">
                        <div className="h-3 bg-violet-200 dark:bg-violet-900/40 rounded-full w-1/3"></div>
                        <div className="h-8 bg-slate-200 dark:bg-slate-800 rounded-xl w-4/5"></div>
                    </div>

                    <div className="h-4 bg-slate-200 dark:bg-slate-800 rounded-full w-11/12"></div>
                    <div className="h-4 bg-slate-200 dark:bg-slate-800 rounded-full w-2/3"></div>
                </div>
            </div>
        );
    }

    if (!content) {
        return null;
    }

    return (
        <div className={`rounded-3xl border border-slate-200/90 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-sm transition-all overflow-hidden ${className}`}>
            {/* Top Toolbar */}
            <div className="px-5 py-3.5 border-b border-slate-100 dark:border-slate-800 flex flex-wrap items-center justify-between gap-3 bg-slate-50/60 dark:bg-slate-800/40">
                <div className="flex items-center gap-2.5">
                    <div className="w-8 h-8 rounded-xl bg-violet-100 dark:bg-violet-950/60 text-violet-600 dark:text-violet-400 flex items-center justify-center text-sm font-bold">
                        <Sparkles className="w-4 h-4 text-violet-600 dark:text-violet-400" />
                    </div>
                    <div>
                        <div className="font-bold text-xs text-slate-900 dark:text-white flex items-center gap-2">
                            <span>{title || 'Ответ ИИ-Помощника'}</span>
                            <span className="text-[10px] font-semibold px-2 py-0.5 rounded-full bg-emerald-100 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-400 border border-emerald-200/60 dark:border-emerald-800/60">
                                Готово ✓
                            </span>
                        </div>
                        {subtitle && (
                            <p className="text-[11px] text-slate-500 dark:text-slate-400">{subtitle}</p>
                        )}
                    </div>
                </div>

                {/* Action buttons */}
                <div className="flex items-center gap-1.5 ml-auto">
                    {/* Font scale buttons */}
                    <div className="flex items-center bg-white dark:bg-slate-800 border border-slate-200/80 dark:border-slate-700 rounded-xl p-0.5 mr-1 shadow-2xs">
                        <button
                            type="button"
                            onClick={() => setFontSize('compact')}
                            className={`p-1.5 rounded-lg text-xs transition-colors ${fontSize === 'compact' ? 'bg-violet-50 text-violet-600 font-bold' : 'text-slate-400 hover:text-slate-600'}`}
                            title="Мелкий шрифт"
                        >
                            <ZoomOut className="w-3.5 h-3.5" />
                        </button>
                        <button
                            type="button"
                            onClick={() => setFontSize('normal')}
                            className={`p-1.5 rounded-lg text-xs transition-colors ${fontSize === 'normal' ? 'bg-violet-50 text-violet-600 font-bold' : 'text-slate-400 hover:text-slate-600'}`}
                            title="Стандартный шрифт"
                        >
                            <span className="text-[11px] font-bold px-0.5">A</span>
                        </button>
                        <button
                            type="button"
                            onClick={() => setFontSize('large')}
                            className={`p-1.5 rounded-lg text-xs transition-colors ${fontSize === 'large' ? 'bg-violet-50 text-violet-600 font-bold' : 'text-slate-400 hover:text-slate-600'}`}
                            title="Крупный шрифт"
                        >
                            <ZoomIn className="w-3.5 h-3.5" />
                        </button>
                    </div>

                    {/* Copy Markdown */}
                    <button
                        type="button"
                        onClick={handleCopyMarkdown}
                        className="p-2 rounded-xl border border-slate-200/80 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors shadow-2xs"
                        title="Копировать Markdown"
                    >
                        {copiedFormat === 'md' ? (
                            <Check className="w-4 h-4 text-emerald-600 dark:text-emerald-400" />
                        ) : (
                            <Copy className="w-4 h-4" />
                        )}
                    </button>

                    {/* Print */}
                    <button
                        type="button"
                        onClick={handlePrint}
                        className="p-2 rounded-xl border border-slate-200/80 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors shadow-2xs"
                        title="Печать"
                    >
                        <Printer className="w-4 h-4" />
                    </button>

                    {/* Download */}
                    <button
                        type="button"
                        onClick={handleDownloadDoc}
                        className="p-2 rounded-xl border border-slate-200/80 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors shadow-2xs"
                        title="Скачать .md файл"
                    >
                        <Download className="w-4 h-4" />
                    </button>

                    {/* Regenerate if handler provided */}
                    {onRegenerate && (
                        <button
                            type="button"
                            onClick={onRegenerate}
                            className="flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-violet-50 dark:bg-violet-950/40 text-violet-700 dark:text-violet-300 border border-violet-200/80 dark:border-violet-800 text-xs font-semibold hover:bg-violet-100 transition-all shadow-2xs ml-1"
                        >
                            <RotateCcw className="w-3.5 h-3.5" />
                            <span>Перегенерировать</span>
                        </button>
                    )}
                </div>
            </div>

            {/* Rendered HTML content */}
            <div className="p-6 sm:p-8 overflow-x-auto">
                <div
                    className={`ai-content-canvas ${fontSizeStyles} text-slate-800 dark:text-slate-100`}
                    dangerouslySetInnerHTML={{ __html: htmlContent }}
                />
            </div>
        </div>
    );
}
