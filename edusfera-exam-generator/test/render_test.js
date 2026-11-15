import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { buildExamHtml } from '../src/renderer.js';
import { renderHtmlToPdf, closeBrowserPool, getPoolMetrics } from '../src/pool.js';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

async function main() {
  console.log('--- Testing edusfera-exam-generator ---');
  const metrics = getPoolMetrics();
  console.log('Initial metrics:', metrics);

  const samplePath = path.join(__dirname, 'sample_test.json');
  const sampleData = JSON.parse(fs.readFileSync(samplePath, 'utf-8'));

  console.log('Compiling HTML with KaTeX...');
  const t0 = performance.now();
  const html = buildExamHtml(sampleData);
  const tHtml = performance.now() - t0;
  console.log(`HTML built in ${tHtml.toFixed(2)}ms (HTML size: ${html.length} chars)`);

  const outHtmlPath = path.join(__dirname, 'output_preview.html');
  fs.writeFileSync(outHtmlPath, html);
  console.log(`Preview HTML saved to ${outHtmlPath}`);

  console.log('Rendering PDF via Playwright Chromium...');
  const t1 = performance.now();
  const pdfBuffer = await renderHtmlToPdf(html);
  const tPdf = performance.now() - t1;
  console.log(`PDF rendered in ${tPdf.toFixed(2)}ms (PDF size: ${pdfBuffer.length} bytes)`);

  const outPdfPath = path.join(__dirname, 'output_test.pdf');
  fs.writeFileSync(outPdfPath, pdfBuffer);
  console.log(`PDF saved to ${outPdfPath}`);

  console.log('Pool metrics after render:', getPoolMetrics());

  await closeBrowserPool();
  console.log('--- Test successfully completed! ---');
}

main().catch((err) => {
  console.error('Test failed:', err);
  process.exit(1);
});

