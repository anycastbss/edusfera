import React, { useMemo } from 'react';
import katex from 'katex';

/**
 * Normalizes unicode math expressions into LaTeX syntax
 */
function normalizeToLatex(raw) {
    if (!raw || typeof raw !== 'string') return '';

    let text = raw;

    // Convert unicode square roots like √(2x + 7) or √(3x + 4) or √x to $\sqrt{...}$
    // Match √(expr) where expr has balanced parentheses
    text = text.replace(/√\(([^)]+)\)/g, '$\\sqrt{$1}$');
    text = text.replace(/√([a-zA-Z0-9]+)/g, '$\\sqrt{$1}$');

    // Convert standalone quadratic/cubic powers like x² -> $x^2$, x³ -> $x^3$
    // only if not already inside a $...$ formula
    const parts = text.split(/(\$[^$]+\$)/g);
    for (let i = 0; i < parts.length; i++) {
        if (!parts[i].startsWith('$')) {
            // Replace middle dots with math dot if surrounded by math terms
            parts[i] = parts[i].replace(/·/g, '$\\cdot$');
            // Superscripts
            parts[i] = parts[i].replace(/([a-zA-Z0-9\)])²/g, '$1^2');
            parts[i] = parts[i].replace(/([a-zA-Z0-9\)])³/g, '$1^3');
        }
    }
    text = parts.join('');

    return text;
}

/**
 * MathText renders text containing LaTeX formulas ($...$ or $$...$$) using KaTeX,
 * delivering crisp Microsoft Word / Cambridge Math quality formatting.
 */
export default function MathText({ text = '', className = '' }) {
    const renderedParts = useMemo(() => {
        if (!text) return null;

        const normalized = normalizeToLatex(text);

        // Split text by inline math $...$ and block math $$...$$
        const regex = /(\$\$[\s\S]+?\$\$|\$[^\$\n]+?\$)/g;
        const tokens = normalized.split(regex);

        return tokens.map((token, idx) => {
            if (!token) return null;

            const isBlock = token.startsWith('$$') && token.endsWith('$$') && token.length >= 4;
            const isInline = token.startsWith('$') && token.endsWith('$') && token.length >= 2;

            if (isBlock || isInline) {
                const formula = isBlock ? token.slice(2, -2).trim() : token.slice(1, -1).trim();
                try {
                    const html = katex.renderToString(formula, {
                        displayMode: isBlock,
                        throwOnError: false,
                        output: 'html'
                    });
                    return (
                        <span
                            key={idx}
                            className={`katex-math-render ${isBlock ? 'block my-1.5 text-center' : 'inline'}`}
                            dangerouslySetInnerHTML={{ __html: html }}
                        />
                    );
                } catch (e) {
                    return <span key={idx} className="font-mono text-slate-800">{token}</span>;
                }
            }

            return <span key={idx}>{token}</span>;
        });
    }, [text]);

    return (
        <span className={`inline font-sans ${className}`}>
            {renderedParts}
        </span>
    );
}
