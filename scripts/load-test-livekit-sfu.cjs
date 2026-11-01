const { AccessToken } = require('livekit-server-sdk');

const LIVEKIT_URL = 'wss://edusfera-iae194s8.livekit.cloud';
const API_KEY = 'API4qadcLJtDRyS';
const API_SECRET = 'CI7ahUesIE2mi5DOzAdZnbaGfmXqy8vcdYfiXPal6FeD';

async function generateToken(identity, name, room, isTutor = false) {
    const at = new AccessToken(API_KEY, API_SECRET, {
        identity,
        name,
        ttl: '1h',
    });
    at.addGrant({
        room,
        roomJoin: true,
        canPublish: true,
        canSubscribe: true,
        canPublishData: true,
        roomAdmin: isTutor,
    });
    return await at.toJwt();
}

function wait(ms) {
    return new Promise(resolve => setTimeout(resolve, ms));
}

async function runTier(targetConnections, holdSeconds = 10, rampDurationMs = 3000) {
    console.log(`\n======================================================`);
    console.log(`🚀 ЗАПУСК ТЕСТА: ${targetConnections} ОДНОВРЕМЕННЫХ LIVEKIT СОЕДИНЕНИЙ`);
    console.log(`   (Комнат: ${Math.ceil(targetConnections / 2)}, Удержание: ${holdSeconds} сек)`);
    console.log(`======================================================`);

    const roomCount = Math.ceil(targetConnections / 2);
    const tokens = [];

    process.stdout.write(`  [1/4] Генерация ${targetConnections} JWT токенов... `);
    const tGenStart = Date.now();
    for (let i = 0; i < targetConnections; i++) {
        const roomIndex = Math.floor(i / 2);
        const isTutor = (i % 2 === 0);
        const role = isTutor ? 'tutor' : 'student';
        const identity = `stress_${role}_${i}`;
        const roomName = `load_test_room_${roomIndex}`;
        tokens.push({
            identity,
            roomName,
            isTutor,
            tokenPromise: generateToken(identity, `User ${i}`, roomName, isTutor)
        });
    }

    const resolvedTokens = await Promise.all(tokens.map(async t => ({
        ...t,
        jwt: await t.tokenPromise
    })));
    console.log(`готово за ${Date.now() - tGenStart}ms`);

    console.log(`  [2/4] Подключение ${targetConnections} пиров к LiveKit Cloud (рамп ${rampDurationMs}ms)...`);
    const connections = [];
    const latencies = [];
    const errors = [];
    let openedCount = 0;
    let closedCount = 0;

    const tStartConnect = Date.now();
    const delayPerConn = rampDurationMs / targetConnections;

    const connectPromises = resolvedTokens.map(async (item, idx) => {
        if (delayPerConn > 0) {
            await wait(Math.min(rampDurationMs, idx * delayPerConn));
        }

        const tStart = Date.now();
        return new Promise((resolve) => {
            let settled = false;
            let ws;
            const timeoutTimer = setTimeout(() => {
                if (!settled) {
                    settled = true;
                    errors.push({ id: item.identity, error: 'Connection Timeout (15s)' });
                    if (ws) {
                        try { ws.close(); } catch (_) {}
                    }
                    resolve({ success: false, latency: 15000 });
                }
            }, 15000);

            try {
                const wsUrl = `${LIVEKIT_URL}/rtc?access_token=${item.jwt}`;
                ws = new WebSocket(wsUrl);

                ws.onopen = () => {
                    if (!settled) {
                        settled = true;
                        clearTimeout(timeoutTimer);
                        const lat = Date.now() - tStart;
                        latencies.push(lat);
                        openedCount++;
                        connections.push(ws);
                        resolve({ success: true, latency: lat });
                    }
                };

                ws.onerror = (err) => {
                    if (!settled) {
                        settled = true;
                        clearTimeout(timeoutTimer);
                        errors.push({ id: item.identity, error: err.message || 'WS Error' });
                        resolve({ success: false, latency: Date.now() - tStart });
                    }
                };

                ws.onclose = (event) => {
                    closedCount++;
                    if (!settled) {
                        settled = true;
                        clearTimeout(timeoutTimer);
                        errors.push({ id: item.identity, error: `Closed early (code: ${event.code}, reason: ${event.reason || 'none'})` });
                        resolve({ success: false, latency: Date.now() - tStart });
                    }
                };
            } catch (err) {
                if (!settled) {
                    settled = true;
                    clearTimeout(timeoutTimer);
                    errors.push({ id: item.identity, error: err.message });
                    resolve({ success: false, latency: Date.now() - tStart });
                }
            }
        });
    });

    await Promise.all(connectPromises);
    const totalConnectTime = Date.now() - tStartConnect;

    console.log(`  [3/4] Все подключения обработаны: ${openedCount}/${targetConnections} активно.`);
    console.log(`        Удержание нагрузки в течение ${holdSeconds} сек...`);
    await wait(holdSeconds * 1000);

    // Calculate latency metrics
    latencies.sort((a, b) => a - b);
    const minLat = latencies.length ? latencies[0] : 0;
    const maxLat = latencies.length ? latencies[latencies.length - 1] : 0;
    const avgLat = latencies.length ? Math.round(latencies.reduce((a, b) => a + b, 0) / latencies.length) : 0;
    const p50 = latencies.length ? latencies[Math.floor(latencies.length * 0.5)] : 0;
    const p95 = latencies.length ? latencies[Math.floor(latencies.length * 0.95)] : 0;
    const p99 = latencies.length ? latencies[Math.floor(latencies.length * 0.99)] : 0;

    console.log(`  [4/4] Завершение соединений (${connections.length} сокетов)...`);
    for (const ws of connections) {
        try {
            ws.close();
        } catch (_) {}
    }
    await wait(1000);

    const errorCounts = {};
    for (const err of errors) {
        errorCounts[err.error] = (errorCounts[err.error] || 0) + 1;
    }

    const report = {
        targetConnections,
        rooms: roomCount,
        connected: openedCount,
        failed: errors.length,
        successRate: ((openedCount / targetConnections) * 100).toFixed(1) + '%',
        totalConnectTimeMs: totalConnectTime,
        latencies: {
            min: minLat,
            avg: avgLat,
            p50,
            p95,
            p99,
            max: maxLat
        },
        errorBreakdown: errorCounts
    };

    console.log(`\n  📊 ИТОГИ ТЕСТА ДЛЯ ${targetConnections} СОЕДИНЕНИЙ:`);
    console.log(`     - Успешно подключено: ${openedCount} / ${targetConnections} (${report.successRate})`);
    console.log(`     - Ошибок / отсечек:    ${errors.length}`);
    console.log(`     - Задержка подключения: мин ${minLat}ms, медиана (p50) ${p50}ms, p95 ${p95}ms, макс ${maxLat}ms`);
    if (Object.keys(errorCounts).length > 0) {
        console.log(`     - Причины сбоев:`, JSON.stringify(errorCounts));
    }
    console.log(`------------------------------------------------------`);

    return report;
}

async function main() {
    const requestedTiers = process.argv.slice(2).map(Number).filter(n => !isNaN(n) && n > 0);
    const tiers = requestedTiers.length > 0 ? requestedTiers : [20, 50, 100, 500, 1000];

    console.log(`🎯 ПЛАН СТРЕСС-ТЕСТИРОВАНИЯ LIVEKIT CLOUD SFU:`);
    console.log(`   Уровни нагрузки: ${tiers.join(', ')} одновременных сокетов`);
    console.log(`   SFU Кластер: ${LIVEKIT_URL} (Frankfurt)`);

    const summaryResults = [];

    for (const tier of tiers) {
        const rampMs = tier <= 50 ? 2000 : (tier <= 100 ? 3000 : (tier <= 500 ? 5000 : 7000));
        const holdSec = tier >= 500 ? 6 : 8;
        const res = await runTier(tier, holdSec, rampMs);
        summaryResults.push(res);
        // Cool-down between tiers
        console.log(`⏳ Пауза 3 сек для очистки сокетов SFU...`);
        await wait(3000);
    }

    console.log('\n======================================================');
    console.log('🏆 ИТОГОВАЯ СВОДНАЯ ТАБЛИЦА LIVEKIT CLOUD SFU:');
    console.log('======================================================');
    console.table(summaryResults.map(r => ({
        'Пиров': r.targetConnections,
        'Комнат': r.rooms,
        'Успешно': r.connected,
        'Ошибок': r.failed,
        '% Успеха': r.successRate,
        'p50 (мс)': r.latencies.p50,
        'p95 (мс)': r.latencies.p95,
        'Макс (мс)': r.latencies.max
    })));

    const fs = require('fs');
    const outPath = '/Users/sergei/.gemini/antigravity-ide/brain/0c496a45-e262-4034-90bc-586126700923/livekit_load_test_results.json';
    fs.writeFileSync(outPath, JSON.stringify(summaryResults, null, 2));
    console.log(`📁 Полный отчет сохранен в: ${outPath}`);
}

main().catch(console.error);
