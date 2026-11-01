import React, { useMemo } from 'react';
import { marked } from 'marked';
import katex from 'katex';

/**
 * Robust mathematical formula and Markdown parser
 * Converts:
 * - Block math: $$ ... $$ and \[ ... \] into KaTeX display blocks
 * - Inline math: $ ... $ and \( ... \) into KaTeX inline spans
 * - Full GitHub Flavored Markdown into semantic HTML with 2026 design styling
 */
export function renderMathAndMarkdown(rawText) {
    if (!rawText || typeof rawText !== 'string') return '';

    let text = rawText;

    // 0. Protect fenced code blocks & inline code
    const codeBlocks = [];
    text = text.replace(/```[\s\S]*?```/g, (match) => {
        const id = `__CODE_BLOCK_${codeBlocks.length}__`;
        codeBlocks.push({ id, content: match });
        return id;
    });

    const inlineCodes = [];
    text = text.replace(/`[^`\n]+?`/g, (match) => {
        const id = `__INLINE_CODE_${inlineCodes.length}__`;
        inlineCodes.push({ id, content: match });
        return id;
    });

    // 1. Extract block math $$...$$ and \[...\]
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

    // 2. Extract inline math $...$ and \(...\)
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

    // 3. Render block math
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
        const blockHtml = `<div class="katex-display-wrapper my-4 py-3 px-4 rounded-2xl bg-slate-50/80 dark:bg-slate-800/60 border border-slate-200/80 dark:border-slate-700/60 overflow-x-auto text-center shadow-xs transition-colors">${rendered}</div>`;
        const pRegex = new RegExp(`<p>\\s*${item.id}\\s*<\\/p>`, 'g');
        if (pRegex.test(html)) {
            html = html.replace(pRegex, blockHtml);
        } else {
            html = html.split(item.id).join(blockHtml);
        }
    });

    // 4. Render inline math
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

export default function AiFormattedContent({ content, fontSize = 'normal', className = '' }) {
    const html = useMemo(() => {
        return renderMathAndMarkdown(content);
    }, [content]);

    const fontSizeClasses = {
        compact: 'text-[12px] leading-relaxed',
        normal: 'text-[13.5px] leading-relaxed',
        large: 'text-[15px] leading-loose',
    }[fontSize] || 'text-[13.5px] leading-relaxed';

    return (
        <div
            className={`ai-content-canvas ${fontSizeClasses} text-slate-800 dark:text-slate-100 ${className}`}
            dangerouslySetInnerHTML={{ __html: html }}
        />
    );
}
