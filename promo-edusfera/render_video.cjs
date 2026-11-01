// High-Performance Deterministic Video Renderer using Playwright and FFmpeg
const { chromium } = require('playwright');
const { spawn } = require('child_process');
const path = require('path');
const fs = require('fs');

async function renderPromo() {
  console.log('🚀 Starting Edusfera 9:16 Promo Video Render (30.0s)...');

  const fps = 30;
  const totalSeconds = 30.0;
  const totalFrames = Math.floor(totalSeconds * fps);
  const audioPath = path.join(__dirname, 'audio/soundtrack.wav');
  const outputPath = path.join(__dirname, 'edusfera_promo_9x16.mp4');

  if (!fs.existsSync(audioPath)) {
    console.error('Audio file not found:', audioPath);
    process.exit(1);
  }

  // Launch Google Chrome via Playwright
  const chromePath = '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome';
  const browser = await chromium.launch({
    headless: true,
    executablePath: fs.existsSync(chromePath) ? chromePath : undefined,
    args: ['--no-sandbox', '--disable-setuid-sandbox', '--enable-webgl', '--font-render-hinting=none']
  });

  const context = await browser.newContext({
    viewport: { width: 1080, height: 1920 },
    deviceScaleFactor: 1
  });

  const page = await context.newPage();

  console.log('Navigating to http://127.0.0.1:8282/ ...');
  await page.goto('http://127.0.0.1:8282/', { waitUntil: 'networkidle' });

  // Ensure fonts and DOM are ready
  await page.evaluate(() => document.fonts.ready);
  console.log('Fonts ready, initializing FFmpeg pipeline...');

  // Spawn FFmpeg to encode on the fly from stdin
  const ffmpegArgs = [
    '-y',
    '-f', 'image2pipe',
    '-vcodec', 'mjpeg',
    '-r', fps.toString(),
    '-i', '-', // frames from stdin
    '-i', audioPath, // audio track
    '-c:v', 'libx264',
    '-preset', 'medium',
    '-crf', '18',
    '-pix_fmt', 'yuv420p',
    '-c:a', 'aac',
    '-b:a', '192k',
    '-movflags', '+faststart',
    '-t', totalSeconds.toString(),
    outputPath
  ];

  const ffmpeg = spawn('ffmpeg', ffmpegArgs);

  ffmpeg.stderr.on('data', (data) => {
    // Only log errors or completion
    const msg = data.toString();
    if (msg.includes('Error') || msg.includes('fatal')) {
      console.error('FFmpeg stderr:', msg);
    }
  });

  ffmpeg.on('close', (code) => {
    console.log(`FFmpeg process finished with code ${code}`);
  });

  console.log(`Rendering ${totalFrames} frames at ${fps} fps...`);
  const startTime = Date.now();

  for (let frame = 0; frame < totalFrames; frame++) {
    const t = frame / fps;

    // Deterministically update frame
    await page.evaluate((time) => window.renderAt(time), t);

    // Capture JPEG buffer at quality 95
    const imgBuffer = await page.screenshot({
      type: 'jpeg',
      quality: 95
    });

    // Write to FFmpeg pipe
    const canContinue = ffmpeg.stdin.write(imgBuffer);
    if (!canContinue) {
      await new Promise((resolve) => ffmpeg.stdin.once('drain', resolve));
    }

    if (frame % 90 === 0 || frame === totalFrames - 1) {
      const pct = ((frame / totalFrames) * 100).toFixed(1);
      const elapsed = ((Date.now() - startTime) / 1000).toFixed(1);
      console.log(`Progress: [${frame}/${totalFrames}] ${pct}% (t = ${t.toFixed(2)}s, elapsed: ${elapsed}s)`);
    }
  }

  ffmpeg.stdin.end();

  await new Promise((resolve) => ffmpeg.on('close', resolve));
  await browser.close();

  const totalTime = ((Date.now() - startTime) / 1000).toFixed(1);
  const stats = fs.statSync(outputPath);
  console.log(`\n🎉 Render COMPLETE in ${totalTime}s!`);
  console.log(`File: ${outputPath}`);
  console.log(`Size: ${(stats.size / 1024 / 1024).toFixed(2)} MB`);
}

renderPromo().catch((err) => {
  console.error('Render error:', err);
  process.exit(1);
});
