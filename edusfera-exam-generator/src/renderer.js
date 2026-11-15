import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath, pathToFileURL } from 'node:url';
import { createRequire } from 'node:module';
import Handlebars from 'handlebars';
import MarkdownIt from 'markdown-it';
import markdownItKatex from '@iktakahiro/markdown-it-katex';
import { sanitizeExamPayload } from './sanitizer.js';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);
const require = createRequire(import.meta.url);

// 1. Load KaTeX minified CSS once and rebase fonts to absolute file:// URLs
const katexCssPath = require.resolve('katex/dist/katex.min.css');
const katexFontsDir = path.dirname(katexCssPath);
const katexFontsBaseUrl = pathToFileURL(katexFontsDir).href;

const katexInlineCss = fs
  .readFileSync(katexCssPath, 'utf-8')
  .replace(/url\(fonts\//g, `url(${katexFontsBaseUrl}/fonts/`);

// 2. Safe Markdown-It + KaTeX parser (blocks harmful raw HTML, renders LaTeX in-memory)
const md = new MarkdownIt({
  html: false,
  breaks: true,
  typographer: true
}).use(markdownItKatex, {
  throwOnError: false,
  errorColor: '#b91c1c'
});

const RUS_LETTERS = ['А', 'Б', 'В', 'Г', 'Д', 'Е'];

function renderInline(text) {
  if (!text) return '';
  return md.renderInline(String(text));
}

function renderBlock(text) {
  if (!text) return '';
  return md.render(String(text));
}

// 3. Compile Handlebars template
const templatePath = path.join(__dirname, 'templates', 'exam_a4.hbs');
const templateSource = fs.readFileSync(templatePath, 'utf-8');
const compiledTemplate = Handlebars.compile(templateSource);

/**
 * Builds standalone printable A4 HTML from structured exam payload
 */
export function buildExamHtml(rawPayload) {
  const clean = sanitizeExamPayload(rawPayload);

  const processQuestions = (questions = []) =>
    questions.map((q) => ({
      ...q,
      renderedText: renderBlock(q.text),
      renderedAnswer: renderInline(q.answer),
      renderedExplanation: renderBlock(q.explanation),
      renderedChoices: (q.choices || []).map((choice, idx) => ({
        letter: RUS_LETTERS[idx] || String(idx + 1),
        html: renderInline(choice)
      }))
    }));

  // Support single-variant payload or multi-variant payload
  let resolvedVariants = [];

  if (Array.isArray(clean.variants) && clean.variants.length > 0) {
    resolvedVariants = clean.variants.map((v) => {
      const partA = processQuestions(v.partA);
      const partB = processQuestions(v.partB);
      const totalTasks = partA.length + partB.length;
      const maxPoints = [...partA, ...partB].reduce((sum, q) => sum + (Number(q.points) || 1), 0);

      return {
        number: v.number,
        title: v.title,
        partA,
        partB,
        totalTasks,
        maxPoints
      };
    });
  } else {
    const partA = processQuestions(clean.partA);
    const partB = processQuestions(clean.partB);
    const totalTasks = partA.length + partB.length;
    const maxPoints = [...partA, ...partB].reduce((sum, q) => sum + (Number(q.points) || 1), 0);

    resolvedVariants = [
      {
        number: clean.meta.variant || 1,
        title: `Вариант ${clean.meta.variant || 1}`,
        partA,
        partB,
        totalTasks,
        maxPoints
      }
    ];
  }

  return compiledTemplate({
    katexInlineCss,
    options: {
      showAnswers: Boolean(clean.options?.showAnswers ?? true),
      showExplanations: Boolean(clean.options?.showExplanations ?? true),
      fontSizePt: Number(clean.options?.fontSizePt || 9.5)
    },
    meta: {
      ...clean.meta,
      generatedDate: clean.meta.generatedDate || new Date().toLocaleDateString('ru-RU')
    },
    resolvedVariants
  });
}

