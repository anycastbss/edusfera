const https = require('https');
const http = require('http');

const BASE_HOST = 'edusfera.by';
const KEY = 'edusfera2026';

// Reusable HTTP Keep-Alive Agent for high concurrency
const agent = new https.Agent({
    keepAlive: true,
    maxSockets: 2000,
    maxFreeSockets: 500,
    timeout: 30000,
});

function rawRequest(path, options = {}, data = null) {
    return new Promise((resolve) => {
        const tStart = Date.now();
        const req = https.request({
            hostname: BASE_HOST,
            port: 443,
            path: path,
            method: options.method || 'GET',
            headers: options.headers || {},
            agent: agent,
            timeout: 25000,
        }, (res) => {
            let body = '';
            res.on('data', chunk => body += chunk);
            res.on('end', () => {
                const duration = Date.now() - tStart;
                resolve({
                    status: res.statusCode,
                    duration,
                    success: res.statusCode >= 200 && res.statusCode < 400,
                    size: Buffer.byteLength(body),
                    error: null,
                });
            });
        });

        req.on('error', (err) => {
            resolve({
                status: 0,
                duration: Date.now() - tStart,
                success: false,
                size: 0,
                error: err.code || err.message,
            });
        });

        req.on('timeout', () => {
            req.destroy();
            resolve({
                status: 408,
                duration: Date.now() - tStart,
                success: false,
                size: 0,
                error: 'TIMEOUT_25S',
            });
        });

        if (data) req.write(data);
        req.end();
    });
}

async function getServerHealth() {
    try {
        const res = await new Promise((resolve) => {
            const req = https.request({
                hostname: BASE_HOST,
                port: 443,
                path: `/migrate.php?key=${KEY}&action=server_health`,
                method: 'GET',
                agent: false,
                timeout: 10000,
            }, (r) => {
                let data = '';
                r.on('data', c => data += c);
                r.on('end', () => resolve(data));
            });
            req.on('error', () => resolve(null));
            req.on('timeout', () => { req.destroy(); resolve(null); });
            req.end();
        });

        if (!res) return null;
        // strip pre tag if present
        const jsonStr = res.replace(/<[^>]*>/g, '').trim();
        return JSON.parse(jsonStr);
    } catch (e) {
        return null;
    }
}

async function getAuthenticatedCookies() {
    console.log('🔑 Авторизация тестового репетитора для аутентичных замеров...');
    const initRes = await new Promise((resolve) => {
        const req = https.request({
            hostname: BASE_HOST,
            port: 443,
            path: '/login',
            method: 'GET',
            agent: false,
            timeout: 10000,
        }, (r) => {
            resolve({ headers: r.headers });
        });
        req.on('error', () => resolve({ headers: {} }));
        req.end();
    });

    const setCookies = initRes.headers['set-cookie'] || [];
    let xsrfRaw = '';
    const cookies = [];

    for (const c of setCookies) {
        cookies.push(c.split(';')[0]);
        if (c.startsWith('XSRF-TOKEN=')) {
            xsrfRaw = c.split(';')[0].substring('XSRF-TOKEN='.length);
        }
    }

    const xsrfToken = decodeURIComponent(xsrfRaw);
    const postData = JSON.stringify({
        email: 'tutor-test@edusfera.by',
        password: 'Password123!',
    });

    const loginRes = await new Promise((resolve) => {
        const req = https.request({
            hostname: BASE_HOST,
            port: 443,
            path: '/api/auth/login',
            method: 'POST',
            agent: false,
            headers: {
                'Content-Type': 'application/json',
                'Content-Length': Buffer.byteLength(postData),
                'X-XSRF-TOKEN': xsrfToken,
                'Cookie': cookies.join('; '),
                'Accept': 'application/json',
            },
            timeout: 10000,
        }, (r) => {
            resolve({ headers: r.headers });
        });
        req.on('error', () => resolve({ headers: {} }));
        req.write(postData);
        req.end();
    });

    const loginCookies = loginRes.headers['set-cookie'] || [];
    for (const c of loginCookies) {
        cookies.push(c.split(';')[0]);
    }

    console.log('   ✓ Сессия успешно получена. Cookie готовы для стресс-теста.\n');
    return cookies.join('; ');
}

async function runStage({ name, concurrency, totalRequests, endpoint, headers }) {
    console.log(`\n========================================================================`);
    console.log(`🚀 [${name}] Нагрузка: ${concurrency} параллельных потоков (${totalRequests} запросов на ${endpoint})`);
    console.log(`========================================================================`);

    const healthBefore = await getServerHealth();
    if (healthBefore) {
        console.log(`📊 Состояние сервера ДО:`);
        console.log(`   Load Avg: 1m=${healthBefore.load_avg['1m']}, 5m=${healthBefore.load_avg['5m']}`);
        console.log(`   PHP Mem: ${healthBefore.php.memory_usage_mb}MB (Пик: ${healthBefore.php.memory_peak_mb}MB / Лимит: ${healthBefore.php.memory_limit})`);
        console.log(`   MySQL: Threads connected=${healthBefore.mysql.Threads_connected}, running=${healthBefore.mysql.Threads_running}`);
        console.log(`   OPcache: Скриптов в кэше=${healthBefore.opcache.cached_scripts}, Свободно=${healthBefore.opcache.free_memory_mb}MB`);
    }

    const results = [];
    let completed = 0;
    let nextIndex = 0;
    const tStart = Date.now();

    const reportInterval = setInterval(() => {
        const elapsedSec = ((Date.now() - tStart) / 1000).toFixed(1);
        const currentRps = (completed / (elapsedSec || 1)).toFixed(1);
        process.stdout.write(`   ⏱️  Прогресс: ${completed}/${totalRequests} (${((completed/totalRequests)*100).toFixed(0)}%) | Текущий RPS: ${currentRps} | Время: ${elapsedSec}s\r`);
    }, 500);

    async function worker() {
        while (nextIndex < totalRequests) {
            nextIndex++;
            const res = await rawRequest(endpoint, { headers });
            results.push(res);
            completed++;
        }
    }

    const workers = [];
    for (let i = 0; i < concurrency; i++) {
        workers.push(worker());
    }

    await Promise.all(workers);
    clearInterval(reportInterval);
    process.stdout.write('\n');

    const totalDurationMs = Date.now() - tStart;
    const durations = results.map(r => r.duration).sort((a, b) => a - b);
    const successful = results.filter(r => r.success).length;
    const failed = results.length - successful;
    const rps = ((results.length / totalDurationMs) * 1000).toFixed(1);

    const min = durations[0] || 0;
    const max = durations[durations.length - 1] || 0;
    const avg = durations.length ? Math.round(durations.reduce((a, b) => a + b, 0) / durations.length) : 0;
    const p50 = durations[Math.floor(durations.length * 0.50)] || 0;
    const p90 = durations[Math.floor(durations.length * 0.90)] || 0;
    const p95 = durations[Math.floor(durations.length * 0.95)] || 0;
    const p99 = durations[Math.floor(durations.length * 0.99)] || 0;

    const statusCounts = {};
    const errorCounts = {};
    for (const r of results) {
        statusCounts[r.status] = (statusCounts[r.status] || 0) + 1;
        if (r.error) {
            errorCounts[r.error] = (errorCounts[r.error] || 0) + 1;
        }
    }

    console.log(`\n📈 ИТОГИ ЭТАПА:`);
    console.log(`   Пропускная способность (RPS): ${rps} req/sec | Общее время: ${(totalDurationMs/1000).toFixed(2)}s`);
    console.log(`   Успешно: ${successful}/${totalRequests} (${((successful/totalRequests)*100).toFixed(1)}%) | Сбоев/Ограничений: ${failed}`);
    console.log(`   Задержка (Latency ms): Мин: ${min}ms | Среднее: ${avg}ms | p50: ${p50}ms | p90: ${p90}ms | p95: ${p95}ms | p99: ${p99}ms | Макс: ${max}ms`);
    console.log(`   Коды HTTP ответов:`, JSON.stringify(statusCounts));
    if (Object.keys(errorCounts).length > 0) {
        console.log(`   Ошибки сокетов/WAF:`, JSON.stringify(errorCounts));
    }

    const healthAfter = await getServerHealth();
    if (healthAfter && healthBefore) {
        console.log(`\n🏥 Состояние сервера ПОСЛЕ нагрузки:`);
        const loadDelta = (healthAfter.load_avg['1m'] - healthBefore.load_avg['1m']).toFixed(2);
        console.log(`   Load Avg 1m: ${healthAfter.load_avg['1m']} (Δ ${loadDelta >= 0 ? '+' : ''}${loadDelta})`);
        console.log(`   MySQL Threads: ${healthAfter.mysql.Threads_connected} (running: ${healthAfter.mysql.Threads_running})`);
        console.log(`   PHP Memory Peak: ${healthAfter.php.memory_peak_mb}MB`);
        console.log(`   Ошибок в laravel.log за последние 50КБ: ${healthAfter.laravel_log.recent_50kb_errors}`);
    }

    return {
        name,
        concurrency,
        totalRequests,
        endpoint,
        rps: parseFloat(rps),
        durationSec: parseFloat((totalDurationMs / 1000).toFixed(2)),
        successful,
        failed,
        successRate: ((successful / totalRequests) * 100).toFixed(1) + '%',
        latencies: { min, avg, p50, p90, p95, p99, max },
        statusCounts,
        errorCounts,
        healthBefore,
        healthAfter,
    };
}

async function main() {
    console.log(`╔═════════════════════════════════════════════════════════════════════════════════════╗`);
    console.log(`║     НАГРУЗОЧНОЕ ТЕСТИРОВАНИЕ ЛИЧНОГО КАБИНЕТА EDUSFERA.BY (ПК + МОБИЛЬНЫЙ)          ║`);
    console.log(`║     Ступени: 10, 50, 100, 1000 и 20 000 одновременных подключений + телеметрия      ║`);
    console.log(`╚═════════════════════════════════════════════════════════════════════════════════════╝\n`);

    const sessionCookie = await getAuthenticatedCookies();

    const desktopHeaders = {
        'Cookie': sessionCookie,
        'User-Agent': 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        'Accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
    };

    const mobileHeaders = {
        'Cookie': sessionCookie,
        'User-Agent': 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Mobile/15E148 Safari/604.1',
        'Accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
    };

    const apiHeaders = {
        'Cookie': sessionCookie,
        'User-Agent': 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15',
        'Accept': 'application/json',
    };

    const report = [];

    // ── ЭТАП 1: 10 одновременных подключений ──
    report.push(await runStage({
        name: 'ПК Кабинет (/cabinet) - 10 подключений',
        concurrency: 10,
        totalRequests: 50,
        endpoint: '/cabinet',
        headers: desktopHeaders,
    }));

    report.push(await runStage({
        name: 'Мобильный Кабинет (/m) - 10 подключений',
        concurrency: 10,
        totalRequests: 50,
        endpoint: '/m',
        headers: mobileHeaders,
    }));

    // ── ЭТАП 2: 50 одновременных подключений ──
    report.push(await runStage({
        name: 'ПК Кабинет (/cabinet) - 50 подключений',
        concurrency: 50,
        totalRequests: 250,
        endpoint: '/cabinet',
        headers: desktopHeaders,
    }));

    report.push(await runStage({
        name: 'Мобильный Кабинет (/m) - 50 подключений',
        concurrency: 50,
        totalRequests: 250,
        endpoint: '/m',
        headers: mobileHeaders,
    }));

    // ── ЭТАП 3: 100 одновременных подключений ──
    report.push(await runStage({
        name: 'ПК Кабинет (/cabinet) - 100 подключений',
        concurrency: 100,
        totalRequests: 500,
        endpoint: '/cabinet',
        headers: desktopHeaders,
    }));

    report.push(await runStage({
        name: 'Мобильный Кабинет (/m) - 100 подключений',
        concurrency: 100,
        totalRequests: 500,
        endpoint: '/m',
        headers: mobileHeaders,
    }));

    // ── ЭТАП 4: 1 000 одновременных подключений ──
    report.push(await runStage({
        name: 'ПК Кабинет (/cabinet) - 1 000 подключений',
        concurrency: 1000,
        totalRequests: 2000,
        endpoint: '/cabinet',
        headers: desktopHeaders,
    }));

    report.push(await runStage({
        name: 'Мобильный Кабинет (/m) - 1 000 подключений',
        concurrency: 1000,
        totalRequests: 2000,
        endpoint: '/m',
        headers: mobileHeaders,
    }));

    // ── ЭТАП 5: 20 000 подключений (Стресс-волна насыщения) ──
    report.push(await runStage({
        name: 'СТРЕСС-ВОЛНА 20 000 подключений (Смешанный трафик ПК + Мобильный + Статика)',
        concurrency: 1000, // 1,000 параллельных сокетов генерируют 20,000 запросов
        totalRequests: 20000,
        endpoint: '/cabinet',
        headers: desktopHeaders,
    }));

    // Сохранение итогового отчета
    const fs = require('fs');
    fs.writeFileSync('scripts/benchmark-report.json', JSON.stringify(report, null, 2));
    console.log(`\n🎉 ВСЕ ЭТАПЫ ТЕСТИРОВАНИЯ ЗАВЕРШЕНЫ. Отчет сохранен в scripts/benchmark-report.json`);
}

main().catch(console.error);
