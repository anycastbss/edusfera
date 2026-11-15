import fs from 'node:fs';
import { chromium } from 'playwright-core';
import pLimit from 'p-limit';

const MAX_CONCURRENCY = Number(process.env.RENDER_CONCURRENCY || 3);
const MAX_RENDERS_BEFORE_RESTART = Number(process.env.BROWSER_RESTART_THRESHOLD || 400);
const RENDER_TIMEOUT_MS = Number(process.env.RENDER_TIMEOUT_MS || 20000);

const limit = pLimit(MAX_CONCURRENCY);

let browser = null;
let browserLaunchPromise = null;
let activePages = 0;
let totalRendered = 0;
let sinceRestartRendered = 0;

/**
 * Auto-detect Chrome/Chromium binary path for macOS (local dev) and Linux (production)
 */
export function resolveExecutablePath() {
  if (process.env.CHROME_EXECUTABLE_PATH && fs.existsSync(process.env.CHROME_EXECUTABLE_PATH)) {
    return process.env.CHROME_EXECUTABLE_PATH;
  }

  const candidates = [
    '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome',
    '/Applications/Chromium.app/Contents/MacOS/Chromium',
    '/usr/bin/chromium',
    '/usr/bin/chromium-browser',
    '/usr/bin/google-chrome-stable',
    '/usr/bin/google-chrome',
    '/snap/bin/chromium'
  ];

  for (const candidate of candidates) {
    if (fs.existsSync(candidate)) {
      return candidate;
    }
  }

  return null;
}

/**
 * Obtain or lazily launch browser singleton with race-condition guard
 */
export async function getBrowser() {
  if (browser && browser.isConnected()) {
    return browser;
  }

  if (!browserLaunchPromise) {
    const executablePath = resolveExecutablePath();
    const launchOptions = {
      headless: true,
      args: [
        '--no-sandbox',
        '--disable-setuid-sandbox',
        '--disable-dev-shm-usage',
        '--disable-gpu',
        '--disable-extensions',
        '--disable-background-networking',
        '--font-render-hinting=none'
      ]
    };

    if (executablePath) {
      launchOptions.executablePath = executablePath;
    }

    browserLaunchPromise = chromium
      .launch(launchOptions)
      .then((instance) => {
        browser = instance;
        sinceRestartRendered = 0;
        browser.on('disconnected', () => {
          browser = null;
        });
        return instance;
      })
      .finally(() => {
        browserLaunchPromise = null;
      });
  }

  return browserLaunchPromise;
}

/**
 * Render compiled HTML into a binary PDF Buffer within an isolated BrowserContext
 */
export async function renderHtmlToPdf(html) {
  return limit(async () => {
    activePages++;
    let context = null;

    try {
      const instance = await getBrowser();
      context = await instance.newContext();
      const page = await context.newPage();

      // Block any external requests from the template
      await page.route('**/*', (route) => {
        const url = route.request().url();
        if (url.startsWith('data:') || url.startsWith('file:') || url === 'about:blank') {
          return route.continue();
        }
        return route.abort();
      });

      await page.setContent(html, {
        waitUntil: 'domcontentloaded',
        timeout: RENDER_TIMEOUT_MS
      });

      // Wait a tick for KaTeX web fonts to render cleanly
      await page.evaluate(() => document.fonts?.ready);

      const pdfBuffer = await page.pdf({
        format: 'A4',
        printBackground: true,
        preferCSSPageSize: true
      });

      totalRendered++;
      sinceRestartRendered++;

      return pdfBuffer;
    } finally {
      activePages--;
      if (context) {
        await context.close().catch(() => {});
      }

      // Scheduled gentle browser recycling during idle state
      if (sinceRestartRendered >= MAX_RENDERS_BEFORE_RESTART && activePages === 0 && browser) {
        const staleBrowser = browser;
        browser = null;
        await staleBrowser.close().catch(() => {});
      }
    }
  });
}

export async function closeBrowserPool() {
  if (browser) {
    const instance = browser;
    browser = null;
    await instance.close().catch(() => {});
  }
}

export function getPoolMetrics() {
  return {
    browserConnected: Boolean(browser && browser.isConnected()),
    executablePath: resolveExecutablePath(),
    maxConcurrency: MAX_CONCURRENCY,
    activePages,
    pendingQueue: limit.pendingCount,
    totalRendered,
    memoryRssMb: Math.round(process.memoryUsage().rss / 1024 / 1024)
  };
}

