const https = require('https');
const http = require('http');

const BASE_URL = 'https://edusfera.by';

function makeRequest(urlPath, method = 'GET', data = null, headers = {}) {
    return new Promise((resolve) => {
        const tStart = Date.now();
        const url = new URL(urlPath, BASE_URL);

        const options = {
            hostname: url.hostname,
            port: url.port || (url.protocol === 'https:' ? 443 : 80),
            path: url.pathname + url.search,
            method: method,
            headers: {
                'User-Agent': 'Edusfera-StressTest/1.0',
                'Accept': 'application/json, text/html, */*',
                ...headers,
            },
            timeout: 10000,
        };

        if (data) {
            options.headers['Content-Length'] = Buffer.byteLength(data);
        }

        const req = https.request(options, (res) => {
            let body = '';
            res.on('data', chunk => body += chunk);
            res.on('end', () => {
                const duration = Date.now() - tStart;
                resolve({
                    status: res.statusCode,
                    duration,
                    success: res.statusCode >= 200 && res.statusCode < 400,
                    size: Buffer.byteLength(body),
                });
            });
        });

        req.on('error', (err) => {
            resolve({
                status: 0,
                duration: Date.now() - tStart,
                success: false,
                error: err.message,
            });
        });

        req.on('timeout', () => {
            req.destroy();
            resolve({
                status: 408,
                duration: Date.now() - tStart,
                success: false,
                error: 'Timeout (10s)',
            });
        });

        if (data) {
            req.write(data);
        }
        req.end();
    });
}

function wait(ms) {
    return new Promise(r => setTimeout(r, ms));
}

async function runConcurrencyStage(concurrency, totalRequests, endpoint, method = 'GET', postData = null, headers = {}) {
    console.log(`\n--- Тест бэкенда: ${concurrency} одновременных потоков (${totalRequests} всего запросов на ${endpoint}) ---`);

    const results = [];
    let completed = 0;
    let nextIndex = 0;

    const tStartTotal = Date.now();

    async function worker() {
        while (nextIndex < totalRequests) {
            const current = nextIndex++;
            const res = await makeRequest(endpoint, method, postData, headers);
            results.push(res);
            completed++;
        }
    }

    const workers = [];
    for (let i = 0; i < concurrency; i++) {
        workers.push(worker());
    }

    await Promise.all(workers);
    const totalDurationMs = Date.now() - tStartTotal;

    const durations = results.map(r => r.duration).sort((a, b) => a - b);
    const successful = results.filter(r => r.success).length;
    const failed = results.length - successful;
    const rps = ((results.length / totalDurationMs) * 1000).toFixed(1);

    const min = durations[0] || 0;
    const max = durations[durations.length - 1] || 0;
    const avg = durations.length ? Math.round(durations.reduce((a, b) => a + b, 0) / durations.length) : 0;
    const p50 = durations[Math.floor(durations.length * 0.5)] || 0;
    const p95 = durations[Math.floor(durations.length * 0.95)] || 0;
    const p99 = durations[Math.floor(durations.length * 0.99)] || 0;

    const statusCounts = {};
    for (const r of results) {
        statusCounts[r.status] = (statusCounts[r.status] || 0) + 1;
    }

    console.log(`   Результаты: RPS: ${rps} req/sec | Всего: ${totalDurationMs}ms`);
    console.log(`   Успешно: ${successful} / ${totalRequests} (${((successful / totalRequests) * 100).toFixed(1)}%) | Ошибок: ${failed}`);
    console.log(`   Задержка (мс): мин: ${min}, ср: ${avg}, p50: ${p50}, p95: ${p95}, p99: ${p99}, макс: ${max}`);
    console.log(`   Статусы ответов:`, JSON.stringify(statusCounts));

    return {
        concurrency,
        totalRequests,
        endpoint,
        rps: parseFloat(rps),
        durationMs: totalDurationMs,
        successful,
        failed,
        successRate: ((successful / totalRequests) * 100).toFixed(1) + '%',
        latencies: { min, avg, p50, p95, p99, max },
        statuses: statusCounts,
    };
}

async function main() {
    console.log('🚀 ЗАПУСК НАГРУЗОЧНОГО ТЕСТИРОВАНИЯ СЕРВЕРА EDUSFERA.BY');
    console.log('   Проверяем производительность Nginx, PHP-FPM, MySQL и контроллеров классов\n');

    const tiers = [20, 50, 100, 500, 1000];
    const results = [];

    // 1. Тестирование Telemetry / Heartbeat Beacon (легковесный высокочастотный эндпоинт класса)
    console.log('========================================================================');
    console.log('1. ТЕСТИРОВАНИЕ КЛАССНОЙ ТЕЛЕМЕТРИИ / СИГНАЛИЗАЦИИ (/api/telemetry/event)');
    console.log('========================================================================');
    const beaconPayload = JSON.stringify({
        event: 'classroom_heartbeat',
        role: 'student',
        room: 'edusfera_lesson_42',
        ts: Date.now()
    });
    const beaconHeaders = { 'Content-Type': 'application/json' };

    for (const tier of tiers) {
        const total = tier * 2; // e.g. 40, 100, 200, 1000, 2000 requests
        const r = await runConcurrencyStage(tier, total, '/api/telemetry/event', 'POST', beaconPayload, beaconHeaders);
        results.push(r);
        await wait(1000);
    }

    console.log('\n========================================================================');
    console.log('2. ТЕСТИРОВАНИЕ ВХОДА В ВИРТУАЛЬНЫЙ КЛАСС (/classroom/test)');
    console.log('========================================================================');
    for (const tier of [20, 50, 100]) {
        const total = tier * 2;
        const r = await runConcurrencyStage(tier, total, '/classroom/test', 'GET');
        results.push(r);
        await wait(1500);
    }

    const fs = require('fs');
    const outPath = '/Users/sergei/.gemini/antigravity-ide/brain/0c496a45-e262-4034-90bc-586126700923/backend_load_test_results.json';
    fs.writeFileSync(outPath, JSON.stringify(results, null, 2));
    console.log(`\n📁 Итоги сохранены в ${outPath}`);
}

main().catch(console.error);
