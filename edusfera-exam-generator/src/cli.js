#!/usr/bin/env node

import fs from 'node:fs';
import { buildExamHtml } from './renderer.js';
import { renderHtmlToPdf, closeBrowserPool } from './pool.js';

async function readStdin() {
  const chunks = [];
  for await (const chunk of process.stdin) {
    chunks.push(chunk);
  }
  return Buffer.concat(chunks).toString('utf-8');
}

async function main() {
  let jsonString = '';

  const args = process.argv.slice(2);
  const jsonArgIdx = args.indexOf('--json');
  const outArgIdx = args.indexOf('--out');

  if (jsonArgIdx !== -1 && args[jsonArgIdx + 1]) {
    const jsonPath = args[jsonArgIdx + 1];
    jsonString = fs.readFileSync(jsonPath, 'utf-8');
  } else {
    jsonString = await readStdin();
  }

  if (!jsonString.trim()) {
    process.stderr.write('Error: Empty JSON input provided to edusfera CLI.\n');
    process.exit(1);
  }

  const payload = JSON.parse(jsonString);
  const html = buildExamHtml(payload);
  const pdfBuffer = await renderHtmlToPdf(html);

  if (outArgIdx !== -1 && args[outArgIdx + 1]) {
    const outPath = args[outArgIdx + 1];
    fs.writeFileSync(outPath, pdfBuffer);
  } else {
    process.stdout.write(pdfBuffer);
  }

  await closeBrowserPool();
  process.exit(0);
}

main().catch(async (err) => {
  process.stderr.write(`[edusfera-cli-error] ${err.message}\n`);
  await closeBrowserPool().catch(() => {});
  process.exit(1);
});

