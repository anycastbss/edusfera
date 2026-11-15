/**
 * Sanitizer for academic text and exam payloads
 * Strict enforcement of Edusfera standards (e.g. banning "РИКЗ" in favor of "Стандарты ЦТ / ЦЭ 2026")
 */

const FORBIDDEN_PATTERNS = [
  /\bРИКЗ\b/gu,
  /\bР\.И\.К\.З\.\b/gu,
  /\bРеспубликанск\w*\s+институт\w*\s+контрол\w*\s+знани\w*/giu
];

export function sanitizeAcademicText(raw) {
  if (typeof raw !== 'string' || !raw.trim()) {
    return '';
  }

  let text = raw;

  for (const pattern of FORBIDDEN_PATTERNS) {
    text = text.replace(pattern, 'Стандарты ЦТ / ЦЭ 2026');
  }

  // Normalize excessive double-escaped LaTeX slashes from JSON outputs
  return text
    .replace(/\\\\(frac|sqrt|times|cdot|pm|mp|approx|neq|leq|geq|in|subset|cap|cup|alpha|beta|gamma|delta|pi|theta|lambda|mu|sigma|omega|sum|prod|int|lim|sin|cos|tg|ctg|tan|cot|ln|log|text|left|right|begin|end|mathbf|vec)/g, '\\$1')
    .trim();
}

export function sanitizeExamPayload(payload) {
  if (!payload || typeof payload !== 'object') {
    throw new Error('Payload must be a non-empty object');
  }

  const cleanQuestions = (questions = []) =>
    (Array.isArray(questions) ? questions : []).map((q, idx) => ({
      ...q,
      displayNumber: q.number ?? (idx + 1),
      points: Number(q.points || 1),
      text: sanitizeAcademicText(q.text || q.condition || q.prompt || ''),
      answer: sanitizeAcademicText(q.answer || q.correct || ''),
      explanation: sanitizeAcademicText(q.explanation || ''),
      choices: Array.isArray(q.choices)
        ? q.choices.map((c) => sanitizeAcademicText(typeof c === 'string' ? c : c?.text || ''))
        : Array.isArray(q.options)
          ? q.options.map((c) => sanitizeAcademicText(typeof c === 'string' ? c : c?.text || ''))
          : []
    }));

  const cleanVariants = (variants = []) =>
    (Array.isArray(variants) ? variants : []).map((v, idx) => ({
      number: v.number ?? (idx + 1),
      title: sanitizeAcademicText(v.title || `Вариант ${v.number ?? (idx + 1)}`),
      partA: cleanQuestions(v.part_a || v.partA),
      partB: cleanQuestions(v.part_b || v.partB)
    }));

  return {
    ...payload,
    meta: {
      ...payload.meta,
      title: sanitizeAcademicText(payload.meta?.title || 'Проверочная работа'),
      subject: sanitizeAcademicText(payload.meta?.subject || 'Математика'),
      grade: sanitizeAcademicText(payload.meta?.grade || '11 класс'),
      school: sanitizeAcademicText(payload.meta?.school || 'Edusfera.by'),
      durationMinutes: payload.meta?.durationMinutes ?? payload.meta?.duration ?? 90,
      variant: payload.meta?.variant ?? 1
    },
    // Supports either single variant (partA/partB) or multiple variants array
    partA: cleanQuestions(payload.partA || payload.part_a),
    partB: cleanQuestions(payload.partB || payload.part_b),
    variants: cleanVariants(payload.variants)
  };
}

