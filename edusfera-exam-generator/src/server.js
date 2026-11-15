import 'dotenv/config';
import Fastify from 'fastify';
import { buildExamHtml } from './renderer.js';
import { getBrowser, renderHtmlToPdf, closeBrowserPool, getPoolMetrics } from './pool.js';

const PORT = Number(process.env.PORT || 3005);
const HOST = process.env.HOST || '127.0.0.1';

const fastify = Fastify({
  logger: {
    level: process.env.LOG_LEVEL || 'info'
  },
  bodyLimit: 10 * 1024 * 1024 // 10 MB for large multi-variant bundles
});

// Health check endpoint
fastify.get('/health', async (request, reply) => {
  const metrics = getPoolMetrics();
  const statusCode = metrics.browserConnected ? 200 : 503;

  return reply.code(statusCode).send({
    service: 'edusfera-exam-generator',
    status: metrics.browserConnected ? 'ok' : 'degraded',
    timestamp: new Date().toISOString(),
    ...metrics
  });
});

// PDF Rendering endpoint
fastify.post('/render-pdf', async (request, reply) => {
  const payload = request.body;

  if (!payload || typeof payload !== 'object') {
    return reply.code(400).send({
      error: 'Invalid request body. JSON object expected.'
    });
  }

  const startTime = performance.now();

  try {
    const html = buildExamHtml(payload);
    const pdfBuffer = await renderHtmlToPdf(html);
    const durationMs = Math.round(performance.now() - startTime);

    request.log.info({ durationMs, bytes: pdfBuffer.length }, 'Exam PDF rendered successfully');

    return reply
      .code(200)
      .header('Content-Type', 'application/pdf')
      .header('Content-Length', pdfBuffer.length)
      .header('X-Render-Duration-Ms', String(durationMs))
      .send(pdfBuffer);
  } catch (err) {
    request.log.error(err, 'Failed to render PDF');
    return reply.code(500).send({
      error: 'PDF rendering failed',
      message: err.message
    });
  }
});

// Graceful shutdown
const shutdown = async (signal) => {
  fastify.log.info(`Received ${signal}, shutting down gracefully...`);
  await fastify.close();
  await closeBrowserPool();
  process.exit(0);
};

process.on('SIGINT', () => shutdown('SIGINT'));
process.on('SIGTERM', () => shutdown('SIGTERM'));

// Warm up Chrome/Chromium before opening port
try {
  await getBrowser();
  await fastify.listen({ port: PORT, host: HOST });
  fastify.log.info(`edusfera-exam-generator running at http://${HOST}:${PORT}`);
} catch (err) {
  fastify.log.error(err, 'Failed to start microservice');
  process.exit(1);
}

