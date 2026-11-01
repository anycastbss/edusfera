<?php

use App\Models\ClassroomSession;
use App\Models\Lesson;
use App\Models\TutorProfile;
use App\Models\User;
use App\Services\Ai\GeminiService;
use App\Services\Classroom\LiveKitService;
use App\Services\ClassroomService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

/**
 * Одноразовый веб-скрипт запуска миграций, обслуживания базы данных и настройки WebRTC Edusfera.
 * Использование: https://edusfera.by/migrate.php?key=edusfera2026
 */
$authKey = 'edusfera2026';
$providedKey = (string) ($_GET['key'] ?? $_POST['key'] ?? '');

if (! hash_equals($authKey, $providedKey)) {
    usleep(200000); // Замедление брутфорса
    header('Content-Type: text/html; charset=utf-8');
    ?>
    <!DOCTYPE html>
    <html lang="ru">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Обслуживание платформы — Edusfera</title>
        <style>
            body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #0F172A; color: #F8FAFC; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; padding: 20px; }
            .card { background: #1E293B; border: 1px solid #334155; border-radius: 20px; padding: 32px; max-width: 480px; width: 100%; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.5); }
            h2 { margin: 0 0 10px 0; font-size: 1.35rem; color: #FFFFFF; }
            p { color: #94A3B8; font-size: 0.9rem; line-height: 1.5; margin: 0 0 20px 0; }
            .btn { display: inline-flex; align-items: center; justify-content: center; width: 100%; height: 46px; background: #10B981; color: #FFFFFF; text-decoration: none; border-radius: 12px; font-weight: 700; font-size: 0.95rem; border: none; cursor: pointer; transition: background 0.15s ease; box-sizing: border-box; }
            .btn:hover { background: #059669; }
            input[type="text"], input[type="password"] { width: 100%; padding: 12px; border-radius: 10px; border: 1px solid #334155; background: #0F172A; color: #fff; margin-bottom: 12px; box-sizing: border-box; }
        </style>
    </head>
    <body>
        <div class="card">
            <h2>🔒 Вход в панель обслуживания Edusfera</h2>
            <p>Введите ключ доступа для работы с миграциями, кодом и настройками WebRTC:</p>
            <form method="get" action="">
                <input type="password" name="key" placeholder="Введите ключ доступа..." required autofocus>
                <button type="submit" class="btn">Войти</button>
            </form>
        </div>
    </body>
    </html>
    <?php
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? 'menu';

// ── АВАРИЙНЫЙ ОБРАБОТЧИК: Загрузка и распаковка патчей ДО загрузки ядра Laravel ──
// Позволяет восстановить платформу, даже если в коде Laravel есть синтаксическая ошибка
if ($action === 'upload_patch' || $action === 'unpack_patch') {
    header('Content-Type: text/html; charset=utf-8');
    echo "<!DOCTYPE html><html lang='ru'><body style='background:#0f172a; color:#f8fafc; font-family:monospace; padding:20px;'><pre>";
    $zipFile = null;

    if ($action === 'upload_patch') {
        echo "🚀 <b>Загрузка и распаковка архива патча (Standalone Recovery)...</b>\n\n";
        if (! isset($_FILES['patch_file']) || $_FILES['patch_file']['error'] !== UPLOAD_ERR_OK) {
            echo "❌ Ошибка при загрузке файла: код ".($_FILES['patch_file']['error'] ?? 'empty')."\n";
            echo "</pre></body></html>";
            exit;
        }
        $targetZip = __DIR__.'/edusfera-patch.zip';
        if (! move_uploaded_file($_FILES['patch_file']['tmp_name'], $targetZip)) {
            echo "❌ Не удалось сохранить загруженный файл в {$targetZip}\n";
            echo "</pre></body></html>";
            exit;
        }
        echo '   ✓ Файл успешно сохранен как public/edusfera-patch.zip ('.round(filesize($targetZip) / 1024 / 1024, 2)." МБ)\n";
        $zipFile = $targetZip;
    } else {
        echo "🚀 <b>Распаковка архива edusfera-patch.zip (Standalone Recovery)...</b>\n\n";
        $zipPaths = [
            __DIR__.'/edusfera-patch.zip',
            __DIR__.'/../edusfera-patch.zip',
        ];
        foreach ($zipPaths as $zp) {
            if (file_exists($zp)) {
                $zipFile = $zp;
                break;
            }
        }
    }

    if (! $zipFile || ! file_exists($zipFile)) {
        echo "❌ Архив edusfera-patch.zip не найден.\n";
        echo "</pre></body></html>";
        exit;
    }

    $zip = new ZipArchive;
    if ($zip->open($zipFile) === true) {
        $targetDir = realpath(__DIR__.'/..');
        $extractedCount = 0;
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $entryName = $zip->getNameIndex($i);
            if (str_contains($entryName, '..') || str_starts_with($entryName, '/') || str_starts_with($entryName, '\\')) {
                continue;
            }
            $zip->extractTo($targetDir, [$entryName]);
            $extractedCount++;
        }
        $zip->close();
        echo "   ✓ Безопасно распаковано файлов: {$extractedCount} в корень сайта ({$targetDir})\n";

        // Сброс кэша Laravel и PHP OPcache
        if (function_exists('opcache_reset')) {
            @opcache_reset();
            echo "   ✓ PHP OPcache успешно сброшен\n";
        }

        try {
            require_once __DIR__.'/../vendor/autoload.php';
            $app = require_once __DIR__.'/../bootstrap/app.php';
            $kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
            $kernel->bootstrap();
            $kernel->call('optimize:clear');
            $kernel->call('cache:clear');
            echo "   ✓ Кэш оптимизации и Rate Limiter сброшены:\n".$kernel->output()."\n";
            // Очистка файлов кэша rate-limiting в storage/framework/cache/data
            $cacheDir = __DIR__.'/../storage/framework/cache/data';
            if (is_dir($cacheDir)) {
                @exec("rm -rf {$cacheDir}/* 2>/dev/null");
                echo "   ✓ Файловый кэш Rate Limiter очищен\n";
            }
        } catch (\Throwable $e) {
            echo "   ⚠️ Предупреждение при вызове optimize:clear: ".$e->getMessage()."\n";
        }

        echo "\n🎉 <b>ОБНОВЛЕНИЕ ЗАВЕРШЕНО УСПЕШНО!</b>\n";
        echo "<a href='?key=edusfera2026' style='color:#38bdf8; font-weight:bold;'>← Вернуться в меню обслуживания</a>\n";
    } else {
        echo "❌ Не удалось открыть zip-архив.\n";
    }

    echo "</pre></body></html>";
    exit;
}

define('LARAVEL_START', microtime(true));

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

// Helper function to update .env
function updateEnvKey(string $key, string $val): bool
{
    $envPath = __DIR__.'/../.env';
    if (! file_exists($envPath)) {
        return false;
    }
    $content = file_get_contents($envPath);
    $pattern = "/^{$key}=.*/m";
    if (preg_match($pattern, $content)) {
        $content = preg_replace($pattern, "{$key}={$val}", $content);
    } else {
        $content .= "\n{$key}={$val}";
    }

    return file_put_contents($envPath, $content) !== false;
}

// Current patch info
$localPatch = __DIR__.'/edusfera-patch.zip';
$rootPatch = __DIR__.'/../edusfera-patch.zip';
$activePatchFile = file_exists($localPatch) ? $localPatch : (file_exists($rootPatch) ? $rootPatch : null);
$patchSizeMb = $activePatchFile ? round(filesize($activePatchFile) / 1024 / 1024, 2) : 0;
$patchDate = $activePatchFile ? date('Y-m-d H:i:s', filemtime($activePatchFile)) : 'не найден';

// Current WebRTC settings
$currentMeteredApp = (string) env('METERED_APP_NAME', config('classroom.metered.app_name', ''));
$currentMeteredKey = (string) env('METERED_API_KEY', config('classroom.metered.api_key', ''));
$currentTurnUrl = (string) env('TURN_SERVER_URL', config('classroom.turn.url', ''));
$currentLiveKitUrl = (string) env('LIVEKIT_URL', env('LIVEKIT_HOST', config('classroom.livekit_host', '')));
$currentLiveKitKey = (string) env('LIVEKIT_API_KEY', config('classroom.livekit_api_key', ''));
$currentLiveKitSecret = (string) env('LIVEKIT_API_SECRET', config('classroom.livekit_api_secret', ''));

// Current Gemini AI settings
$currentGeminiKey = (string) env('GEMINI_API_KEY', config('services.gemini.api_key', ''));
$currentGeminiModel = (string) env('GEMINI_MODEL', config('services.gemini.model', 'gemini-flash-latest'));
$currentGeminiLiteModel = (string) env('GEMINI_LITE_MODEL', config('services.gemini.lite_model', 'gemini-3.5-flash-lite'));
$currentGeminiBaseUrl = (string) env('GEMINI_BASE_URL', config('services.gemini.base_url', 'https://generativelanguage.googleapis.com/v1beta'));
$currentGeminiProxy = (string) env('GEMINI_PROXY', config('services.gemini.proxy', ''));
$currentGeminiUseXboxDns = (bool) env('GEMINI_USE_XBOX_DNS', config('services.gemini.use_xbox_dns', true));
$currentGeminiDnsServers = (string) env('GEMINI_DNS_SERVERS', config('services.gemini.dns_servers', '181.215.40.49,31.58.216.201'));

// Current Queue settings
$currentQueueConnection = (string) env('QUEUE_CONNECTION', config('queue.default', 'database'));
$pendingJobsCount = 0;
$failedJobsCount = 0;
try {
    if (Schema::hasTable('jobs')) {
        $pendingJobsCount = (int) DB::table('jobs')->count();
    }
    if (Schema::hasTable('failed_jobs')) {
        $failedJobsCount = (int) DB::table('failed_jobs')->count();
    }
} catch (Throwable $e) {
    // ignore if table not accessible
}

if ($action === 'menu') {
    header('Content-Type: text/html; charset=utf-8');
    ?>
    <!DOCTYPE html>
    <html lang="ru">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Панель обслуживания Edusfera (Без Docker)</title>
        <style>
            body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #0F172A; color: #F8FAFC; margin: 0; padding: 24px; display: flex; justify-content: center; }
            .container { max-width: 680px; width: 100%; }
            .card { background: #1E293B; border: 1px solid #334155; border-radius: 16px; padding: 24px; margin-bottom: 20px; box-shadow: 0 10px 25px rgba(0,0,0,0.3); }
            h2 { margin: 0 0 8px 0; font-size: 1.25rem; color: #FFFFFF; display: flex; align-items: center; gap: 8px; }
            p { color: #94A3B8; font-size: 0.9rem; line-height: 1.5; margin: 0 0 16px 0; }
            .badge { display: inline-block; padding: 4px 10px; border-radius: 8px; font-size: 0.8rem; font-weight: 600; }
            .badge-green { background: rgba(34, 197, 94, 0.15); color: #22c55e; }
            .badge-yellow { background: rgba(234, 179, 8, 0.15); color: #eab308; }
            .badge-blue { background: rgba(56, 189, 248, 0.15); color: #38bdf8; }
            .btn { display: inline-flex; align-items: center; justify-content: center; height: 42px; padding: 0 18px; color: #FFFFFF; text-decoration: none; border-radius: 10px; font-weight: 600; font-size: 0.9rem; border: none; cursor: pointer; transition: opacity 0.15s; }
            .btn:hover { opacity: 0.9; }
            .btn-green { background: #10B981; }
            .btn-blue { background: #0284c7; }
            .btn-purple { background: #7D39EB; }
            .btn-gray { background: #334155; }
            .btn-red { background: #ef4444; }
            .btn-full { width: 100%; box-sizing: border-box; margin-top: 8px; }
            input[type="text"], input[type="password"] { width: 100%; padding: 10px 12px; border-radius: 8px; border: 1px solid #475569; background: #0F172A; color: #fff; margin-bottom: 10px; box-sizing: border-box; }
            label { display: block; font-size: 0.85rem; color: #94A3B8; margin-bottom: 4px; }
            .box { background: #0F172A; border: 1px solid #334155; border-radius: 10px; padding: 14px; margin-bottom: 16px; font-size: 0.85rem; }
        </style>
    </head>
    <body>
        <div class="container">
            <!-- Header -->
            <div style="margin-bottom: 24px; text-align: center;">
                <h1 style="font-size: 1.6rem; margin: 0 0 6px 0; color: #fff;">Edusfera.by — Обслуживание сервера</h1>
                <span class="badge badge-blue">Режим: Bare-Metal (Без Docker)</span>
            </div>

            <!-- 1. Patch Updater -->
            <div class="card">
                <h2>📦 1. Обновление кода и ассетов (edusfera-patch.zip)</h2>
                <div class="box">
                    <div>Текущий архив на сервере: <b><?= $activePatchFile ? 'Найден' : 'Отсутствует' ?></b></div>
                    <div>Размер: <b><?= $patchSizeMb ?> МБ</b> | Дата: <b><?= $patchDate ?></b></div>
                    <?php if ($patchSizeMb < 2 && $patchSizeMb > 0) { ?>
                        <div style="color: #eab308; margin-top: 6px;">⚠️ Внимание: На сервере старый архив (< 2 МБ). Для поддержки Excalidraw и связи 4G/LTE загрузите свежий архив (~3.3 МБ).</div>
                    <?php } ?>
                </div>

                <div style="display: flex; gap: 10px; margin-bottom: 16px;">
                    <?php if ($activePatchFile) { ?>
                    <a href="?key=edusfera2026&action=unpack_patch" class="btn btn-blue" style="flex: 1;">
                        ⚡ Распаковать текущий <?= $patchSizeMb ?> МБ патч
                    </a>
                    <?php } ?>
                </div>

                <!-- Direct Upload form -->
                <form action="?key=edusfera2026&action=upload_patch" method="post" enctype="multipart/form-data" style="border-top: 1px solid #334155; padding-top: 16px;">
                    <label><b>Загрузить свежий edusfera-patch.zip прямо с ПК:</b></label>
                    <input type="file" name="patch_file" accept=".zip" required style="margin-bottom: 12px;">
                    <button type="submit" class="btn btn-purple btn-full">
                        🚀 Загрузить с компьютера и сразу применить код
                    </button>
                </form>
            </div>

            <!-- 2. LiveKit Cloud SFU -->
            <div class="card">
                <h2>📡 2. Видеосвязь через LiveKit Cloud SFU</h2>
                <p>Высокоскоростной медиа-сервер SFU для мгновенной двусторонней видеосвязи на ПК, планшетах и смартфонах через 4G/LTE без задержек и сбоев:</p>

                <div class="box">
                    <div>Статус LiveKit: <b><?= $currentLiveKitKey ? '<span style="color:#22c55e">Подключен ('.$currentLiveKitKey.')</span>' : '<span style="color:#eab308">Не настроен</span>' ?></b></div>
                    <div>WebSocket URL: <b><?= $currentLiveKitUrl ? htmlspecialchars($currentLiveKitUrl) : 'Не задан' ?></b></div>
                </div>

                <form action="?key=edusfera2026&action=save_livekit" method="post">
                    <label>LiveKit WebSocket URL:</label>
                    <input type="text" name="livekit_url" value="<?= htmlspecialchars($currentLiveKitUrl ?: 'wss://edusfera-iae194s8.livekit.cloud') ?>" placeholder="wss://edusfera-iae194s8.livekit.cloud" required>

                    <label>LiveKit API Key:</label>
                    <input type="text" name="livekit_key" value="<?= htmlspecialchars($currentLiveKitKey) ?>" placeholder="например: APImGcEym6iCNi2" required>

                    <label>LiveKit API Secret:</label>
                    <input type="password" name="livekit_secret" value="<?= htmlspecialchars($currentLiveKitSecret) ?>" placeholder="секретный ключ API Secret" required>

                    <button type="submit" class="btn btn-green btn-full">
                        💾 Сохранить LiveKit ключи в .env
                    </button>
                </form>

                <div style="margin-top: 12px; text-align: right;">
                    <a href="?key=edusfera2026&action=test_livekit" class="btn btn-gray" style="font-size: 0.8rem; height: 36px;">
                        🔍 Проверить подключение и выпуск токена LiveKit
                    </a>
                </div>
            </div>

            <!-- 3. Google Gemini AI & Copilot -->
            <div class="card">
                <h2>🤖 3. Google Gemini AI (Edusfera Copilot & Методист)</h2>
                <p>Нейросетевой помощник для всех кабинетов платформы (ИИ-Методист для репетитора, ИИ-Тьютор для ученика):</p>

                <div class="box">
                    <div>Статус Gemini: <b><?= $currentGeminiKey ? '<span style="color:#22c55e">Ключ задан ('.substr($currentGeminiKey, 0, 8).'...)</span>' : '<span style="color:#eab308">Не настроен</span>' ?></b></div>
                    <div>Модель: <b><?= htmlspecialchars($currentGeminiModel) ?></b> (Lite: <?= htmlspecialchars($currentGeminiLiteModel) ?>)</div>
                    <div>Маршрутизация: <b><?= $currentGeminiUseXboxDns ? '<span style="color:#22c55e">🎮 Через xbox-dns.ru (SNI Proxy)</span>' : ($currentGeminiBaseUrl !== 'https://generativelanguage.googleapis.com/v1beta' ? '<span style="color:#38bdf8">Через Reverse Proxy</span>' : ($currentGeminiProxy ? '<span style="color:#38bdf8">Через Прокси</span>' : '<span style="color:#eab308">Прямое подключение (проверьте регион)</span>')) ?></b></div>
                </div>

                <form action="?key=edusfera2026&action=save_gemini" method="post">
                    <label>Google Gemini API Key:</label>
                    <input type="text" name="gemini_key" value="<?= htmlspecialchars($currentGeminiKey) ?>" placeholder="AIzaSy..." required>

                    <label style="display: flex; align-items: center; gap: 8px; margin: 10px 0; cursor: pointer; color: #f1f5f9; font-weight: 500;">
                        <input type="checkbox" name="gemini_use_xbox_dns" value="1" <?= $currentGeminiUseXboxDns ? 'checked' : '' ?> style="width: auto; margin: 0;">
                        <span>Маршрутизировать через <b>Smart DNS / SNI</b> (обход региональной блокировки Google в РБ)</span>
                    </label>

                    <label>DNS / SNI узлы для Gemini (через запятую):</label>
                    <input type="text" name="gemini_dns_servers" value="<?= htmlspecialchars($currentGeminiDnsServers) ?>" placeholder="181.215.40.49,31.58.216.201">

                    <label>Base URL (Reverse Proxy / Cloudflare Worker):</label>
                    <input type="text" name="gemini_base_url" value="<?= htmlspecialchars($currentGeminiBaseUrl) ?>" placeholder="https://generativelanguage.googleapis.com/v1beta">

                    <label>HTTP/SOCKS Прокси (если используется без xbox-dns):</label>
                    <input type="text" name="gemini_proxy" value="<?= htmlspecialchars($currentGeminiProxy) ?>" placeholder="например: http://user:pass@proxy.host:port">

                    <div style="display: flex; gap: 10px;">
                        <div style="flex: 1;">
                            <label>Основная модель:</label>
                            <input type="text" name="gemini_model" value="<?= htmlspecialchars($currentGeminiModel ?: 'gemini-flash-latest') ?>">
                        </div>
                        <div style="flex: 1;">
                            <label>Lite модель:</label>
                            <input type="text" name="gemini_lite_model" value="<?= htmlspecialchars($currentGeminiLiteModel ?: 'gemini-3.5-flash-lite') ?>">
                        </div>
                    </div>

                    <button type="submit" class="btn btn-green btn-full">
                        💾 Сохранить настройки Gemini в .env
                    </button>
                </form>

                <div style="margin-top: 12px; display: flex; gap: 10px;">
                    <a href="?key=edusfera2026&action=test_ai" class="btn btn-purple" style="font-size: 0.85rem; height: 38px; width: auto; padding: 0 16px; flex: 1;">
                        🔍 Протестировать Gemini Service
                    </a>
                    <a href="?key=edusfera2026&action=test_xbox_dns" class="btn btn-cyan" style="font-size: 0.85rem; height: 38px; width: auto; padding: 0 16px; flex: 1;">
                        🎮 Диагностика DNS (181.215.40.49, 31.58.216.201)
                    </a>
                </div>

                <details style="margin-top: 14px; background: rgba(15,23,42,0.6); padding: 12px; border-radius: 8px; font-size: 0.82rem; color: #94A3B8;">
                    <summary style="cursor: pointer; font-weight: 600; color: #38bdf8;">
                        ℹ️ Если Google выдает "User location is not supported" (для IP Беларуси):
                    </summary>
                    <div style="margin-top: 8px; line-height: 1.5;">
                        Google AI Studio ограничивает прямые запросы с IP Беларуси/РФ. Чтобы запросы шли без ограничений, достаточно создать бесплатный Cloudflare Worker (или указать европейский прокси):<br><br>
                        <b>5 строк кода в Cloudflare Workers:</b>
                        <pre style="background: #020617; padding: 8px; border-radius: 6px; color: #a5f3fc; overflow-x: auto; margin: 6px 0;">export default {
  async fetch(req) {
    const url = new URL(req.url);
    url.hostname = 'generativelanguage.googleapis.com';
    return fetch(url.toString(), { method: req.method, headers: req.headers, body: req.body });
  }
};</pre>
                        После развертывания вставьте полученный URL воркера (например: <code>https://my-gemini.workers.dev/v1beta</code>) в поле Base URL выше.
                    </div>
                </details>
            </div>

            <!-- 4. Database & Classroom Test -->
            <div class="card">
                <h2>🛠️ 4. База данных и Тестирование класса</h2>
                <p>Запуск системных миграций и подготовка тестовой пары:</p>
                <div style="display: flex; flex-direction: column; gap: 10px;">
                    <a href="?key=edusfera2026&action=classroom_test" class="btn btn-purple">
                        🎥 Подготовить тестового ученика и урок
                    </a>
                    <a href="?key=edusfera2026&action=run_migrations" class="btn btn-green">
                        ▶ Запустить обновление базы и очистку кэша
                    </a>
                    <a href="?key=edusfera2026&action=view_log" class="btn btn-gray" style="font-size:0.85rem;">
                        📋 Просмотреть логи ошибок (storage/logs/laravel.log)
                    </a>
                </div>
            </div>

            <!-- 5. Фоновые очереди задач (Laravel Queue) -->
            <div class="card">
                <h2>⚡ 5. Фоновые очереди задач (Laravel Queue)</h2>
                <p>Асинхронная отправка email-уведомлений, счетов и фоновых задач без задержки ответа страниц:</p>

                <div class="box">
                    <div>Драйвер очереди: <b><?= $currentQueueConnection === 'database' ? '<span style="color:#22c55e">✓ database (Асинхронно, в базе данных)</span>' : ($currentQueueConnection === 'sync' ? '<span style="color:#eab308">⚠ sync (Синхронно, блокирует отклик страниц)</span>' : '<span style="color:#38bdf8">'.htmlspecialchars($currentQueueConnection).'</span>') ?></b></div>
                    <div>Задач в очереди (pending): <b style="color:<?= $pendingJobsCount > 0 ? '#38bdf8' : '#94a3b8' ?>"><?= $pendingJobsCount ?></b></div>
                    <div>Ошибок выполнения (failed): <b style="color:<?= $failedJobsCount > 0 ? '#ef4444' : '#22c55e' ?>"><?= $failedJobsCount ?></b></div>
                </div>

                <div style="display: flex; gap: 10px; margin-bottom: 12px;">
                    <?php if ($currentQueueConnection !== 'database') { ?>
                        <a href="?key=edusfera2026&action=set_queue_database" class="btn btn-green" style="flex:1;">
                            🚀 Включить очередь database
                        </a>
                    <?php } else { ?>
                        <a href="?key=edusfera2026&action=set_queue_sync" class="btn btn-gray" style="flex:1;">
                            ⏸ Переключить на sync
                        </a>
                    <?php } ?>
                    <a href="?key=edusfera2026&action=run_queue" class="btn btn-purple" style="flex:1;">
                        ▶ Обработать очередь сейчас
                    </a>
                </div>

                <?php if ($failedJobsCount > 0) { ?>
                    <div style="display: flex; gap: 10px; margin-bottom: 12px;">
                        <a href="?key=edusfera2026&action=retry_failed_jobs" class="btn btn-cyan" style="flex:1; font-size: 0.85rem;">
                            🔄 Повторить упавшие задачи (<?= $failedJobsCount ?>)
                        </a>
                        <a href="?key=edusfera2026&action=flush_failed_jobs" class="btn btn-gray" style="flex:1; font-size: 0.85rem; color:#ef4444;">
                            🗑 Очистить ошибки
                        </a>
                    </div>
                <?php } ?>

                <details style="background: rgba(15,23,42,0.6); padding: 12px; border-radius: 8px; font-size: 0.82rem; color: #94A3B8;">
                    <summary style="cursor: pointer; font-weight: 600; color: #38bdf8;">
                        📋 Настройка регулярного запуска (Cron на Beget):
                    </summary>
                    <div style="margin-top: 8px; line-height: 1.5;">
                        В панели управления хостингом добавьте задание Cron (раз в минуту):<br>
                        <code style="background: #020617; padding: 4px 8px; border-radius: 4px; color: #a5f3fc; display: block; margin: 6px 0; word-break: break-all;">
                            * * * * * cd /var/www/u41698-5508/data/www/edusfera.by && php artisan schedule:run >> /dev/null 2>&1
                        </code>
                        Планировщик автоматически выполнит <code>queue:work --stop-when-empty</code> и обработает все накопленные уведомления.
                    </div>
                </details>

                <div style="margin-top: 16px; border-top: 1px solid #334155; padding-top: 16px;">
                    <a href="?key=edusfera2026&action=cleanup_trashed_tutors" class="btn btn-red" style="font-size: 0.85rem; width: 100%;" onclick="return confirm('Вы уверены? Все мягко-удаленные пользователи и зависшие анкеты репетиторов будут безвозвратно удалены из БД.');">
                        🧹 Полная очистка удаленных аккаунтов и зависших анкет в БД
                    </a>
                </div>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}

header('Content-Type: text/html; charset=utf-8');
echo "<pre style='background:#0f172a; color:#22c55e; padding:24px; font-family:monospace; line-height:1.5; font-size:14px; border-radius:12px; margin:16px; overflow-x:auto;'>";

try {
    // ── ACTION: Set queue database ────────────────────────────────────
    if ($action === 'set_queue_database') {
        echo "⚡ <b>Переключение очереди на драйвер database...</b>\n\n";
        updateEnvKey('QUEUE_CONNECTION', 'database');
        $kernel->call('optimize:clear');
        echo "   ✓ Драйвер QUEUE_CONNECTION=database успешно сохранен в .env\n";
        echo "   ✓ Кэш конфигурации сброшен.\n\n";
        echo "Теперь все уведомления и фоновые задачи отправляются в таблицу jobs без блокировки интерфейса.\n";
        echo "\n<a href='?key=edusfera2026' style='color:#38bdf8; font-weight:bold;'>← Вернуться в меню обслуживания</a>\n";
        echo '</pre>';
        exit;
    }

    // ── ACTION: Set queue sync ────────────────────────────────────────
    if ($action === 'set_queue_sync') {
        echo "⏸ <b>Переключение очереди на драйвер sync...</b>\n\n";
        updateEnvKey('QUEUE_CONNECTION', 'sync');
        $kernel->call('optimize:clear');
        echo "   ✓ Драйвер QUEUE_CONNECTION=sync сохранен в .env\n";
        echo "   ✓ Кэш конфигурации сброшен.\n\n";
        echo "\n<a href='?key=edusfera2026' style='color:#38bdf8; font-weight:bold;'>← Вернуться в меню обслуживания</a>\n";
        echo '</pre>';
        exit;
    }

    // ── ACTION: Run queue work ────────────────────────────────────────
    if ($action === 'run_queue') {
        echo "▶ <b>Обработка очереди задач (php artisan queue:work --stop-when-empty --tries=3)...</b>\n\n";
        $kernel->call('queue:work', ['--stop-when-empty' => true, '--tries' => 3]);
        $output = trim($kernel->output());
        if ($output) {
            echo $output."\n";
        } else {
            echo "   ✓ Очередь пуста, новых задач нет.\n";
        }
        echo "\n<a href='?key=edusfera2026' style='color:#38bdf8; font-weight:bold;'>← Вернуться в меню обслуживания</a>\n";
        echo '</pre>';
        exit;
    }

    // ── ACTION: Retry failed jobs ─────────────────────────────────────
    if ($action === 'retry_failed_jobs') {
        echo "🔄 <b>Повторный запуск упавших задач (php artisan queue:retry all)...</b>\n\n";
        $kernel->call('queue:retry', ['id' => ['all']]);
        echo $kernel->output()."\n";
        echo "\n<a href='?key=edusfera2026' style='color:#38bdf8; font-weight:bold;'>← Вернуться в меню обслуживания</a>\n";
        echo '</pre>';
        exit;
    }

    // ── ACTION: Ensure test tutor ─────────────────────────────────────
    if ($action === 'ensure_test_tutor') {
        echo "👤 <b>Создание/сброс тестового репетитора для проверки онбординга...</b>\n\n";
        $user = \App\Models\User::withTrashed()->where('email', 'tutor-test@edusfera.by')->first();
        if ($user && $user->trashed()) {
            $user->restore();
        }
        if (! $user) {
            $user = \App\Models\User::create([
                'name' => 'Галина Васильевна',
                'email' => 'tutor-test@edusfera.by',
                'phone' => '+375298889900',
                'password' => \Illuminate\Support\Facades\Hash::make('Password123!'),
                'role' => \App\Enums\UserRole::Tutor,
                'offer_accepted_at' => now(),
            ]);
        } else {
            $user->update([
                'password' => \Illuminate\Support\Facades\Hash::make('Password123!'),
                'role' => \App\Enums\UserRole::Tutor,
            ]);
        }

        $profile = \App\Models\TutorProfile::firstOrCreate(['user_id' => $user->id]);
        $sub = app(\App\Domain\Subscription\Services\SubscriptionService::class)->ensureTrialStarted($user, \App\Domain\Subscription\Enums\SubscriptionPlan::PRO);
        $sub->update(['is_onboarded' => false]);
        $profile->update(['onboarding_completed_at' => null]);

        echo "   ✓ Репетитор tutor-test@edusfera.by готов (пароль: Password123!)\n";
        echo "   ✓ Onboarding статус сброшен: is_onboarded = false\n";
        echo "\n<a href='?key=edusfera2026' style='color:#38bdf8; font-weight:bold;'>← Вернуться в меню обслуживания</a>\n";
        echo '</pre>';
        exit;
    }

    if ($action === 'complete_tutor_onboarding') {
        echo "<pre style='background:#0f172a;color:#22c55e;padding:20px;border-radius:12px;font-family:monospace;'>";
        $user = \App\Models\User::withTrashed()->where('email', 'tutor-test@edusfera.by')->first();
        if ($user) {
            $profile = \App\Models\TutorProfile::firstOrCreate(['user_id' => $user->id]);
            $profile->update([
                'onboarding_completed_at' => now(),
                'subjects' => ['Математика'],
                'price_per_hour' => 45.00,
            ]);
            $sub = app(\App\Domain\Subscription\Services\SubscriptionService::class)->ensureTrialStarted($user, \App\Domain\Subscription\Enums\SubscriptionPlan::PRO);
            $sub->update(['is_onboarded' => true]);
            echo "   ✓ Onboarding завершен для tutor-test@edusfera.by, доступ в /admin открыт!\n";
        }
        echo '</pre>';
        exit;
    }

    if ($action === 'disable_default_comfort_vision') {
        echo "<pre style='background:#0f172a;color:#22c55e;padding:20px;border-radius:12px;font-family:monospace;'>";
        $count = \App\Models\TutorProfile::query()->update(['comfort_vision_mode' => false]);
        echo "   ✓ Сброшен режим комфортного зрения по умолчанию для {$count} профилей репетиторов!\n";
        echo "\n<a href='?key=edusfera2026' style='color:#38bdf8; font-weight:bold;'>← Вернуться в меню обслуживания</a>\n";
        echo '</pre>';
        exit;
    }

    if ($action === 'flush_failed_jobs') {
        echo "🗑 <b>Очистка списка упавших задач (php artisan queue:flush)...</b>\n\n";
        $kernel->call('queue:flush');
        echo $kernel->output()."\n";
        echo "\n<a href='?key=edusfera2026' style='color:#38bdf8; font-weight:bold;'>← Вернуться в меню обслуживания</a>\n";
        echo '</pre>';
        exit;
    }

    // ── ACTION: Upload patch ──────────────────────────────────────────
    if ($action === 'upload_patch') {
        echo "🚀 <b>Загрузка и распаковка архива патча...</b>\n\n";
        if (! isset($_FILES['patch_file']) || $_FILES['patch_file']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('Ошибка при загрузке файла: код '.($_FILES['patch_file']['error'] ?? 'empty'));
        }
        $targetZip = __DIR__.'/edusfera-patch.zip';
        if (! move_uploaded_file($_FILES['patch_file']['tmp_name'], $targetZip)) {
            throw new Exception('Не удалось сохранить загруженный файл в '.$targetZip);
        }
        echo '   ✓ Файл успешно сохранен как public/edusfera-patch.zip ('.round(filesize($targetZip) / 1024 / 1024, 2)." МБ)\n";

        $zip = new ZipArchive;
        if ($zip->open($targetZip) === true) {
            $targetDir = realpath(__DIR__.'/..');
            $extractedCount = 0;
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $entryName = $zip->getNameIndex($i);
                // Защита от Zip Slip (directory traversal)
                if (str_contains($entryName, '..') || str_starts_with($entryName, '/') || str_starts_with($entryName, '\\')) {
                    continue;
                }
                $zip->extractTo($targetDir, [$entryName]);
                $extractedCount++;
            }
            $zip->close();
            echo "   ✓ Безопасно распаковано файлов: {$extractedCount} в корень сайта ({$targetDir})\n";
            $kernel->call('optimize:clear');
            echo "   ✓ Кэш оптимизации Laravel сброшен:\n".$kernel->output()."\n";
            echo "\n🎉 <b>КОД И АССЕТЫ EXCALIDRAW УСПЕШНО ОБНОВЛЕНЫ!</b>\n";
            echo "<a href='?key=edusfera2026' style='color:#38bdf8; font-weight:bold;'>← Вернуться в меню обслуживания</a>\n";
        } else {
            throw new Exception('Не удалось открыть zip-архив.');
        }
        echo '</pre>';
        exit;
    }

    // ── ACTION: Unpack local patch ────────────────────────────────────
    if ($action === 'unpack_patch') {
        echo "🚀 <b>Распаковка архива edusfera-patch.zip...</b>\n\n";
        $zipPaths = [
            __DIR__.'/edusfera-patch.zip',
            __DIR__.'/../edusfera-patch.zip',
        ];
        $zipFile = null;
        foreach ($zipPaths as $zp) {
            if (file_exists($zp)) {
                $zipFile = $zp;
                break;
            }
        }
        if (! $zipFile) {
            echo "   ❌ Архив edusfera-patch.zip не найден ни в public/, ни в корне.\n";
        } else {
            echo "   Архив: {$zipFile}\n";
            echo '   Размер: '.round(filesize($zipFile) / 1024 / 1024, 2).' МБ, Дата: '.date('Y-m-d H:i:s', filemtime($zipFile))."\n\n";

            $zip = new ZipArchive;
            if ($zip->open($zipFile) === true) {
                $targetDir = realpath(__DIR__.'/..');
                $extractedCount = 0;
                for ($i = 0; $i < $zip->numFiles; $i++) {
                    $entryName = $zip->getNameIndex($i);
                    // Защита от Zip Slip (directory traversal)
                    if (str_contains($entryName, '..') || str_starts_with($entryName, '/') || str_starts_with($entryName, '\\')) {
                        continue;
                    }
                    $zip->extractTo($targetDir, [$entryName]);
                    $extractedCount++;
                }
                $zip->close();
                echo "   ✓ Безопасно распаковано файлов: {$extractedCount} в {$targetDir}\n";

                // Автоматическая очистка мягко-удаленных пользователей и сиротских анкет
                try {
                    $trashed = User::onlyTrashed()->get();
                    if ($trashed->isNotEmpty()) {
                        foreach ($trashed as $u) {
                            $u->tutorProfile()?->delete();
                            $u->subscription()?->delete();
                            $u->availability()->delete();
                            $u->tutorBalance()?->delete();
                            $u->studentBalance()?->delete();
                            $u->forceDelete();
                        }
                        echo "   ✓ Очищено мягко-удаленных аккаунтов из БД: " . $trashed->count() . "\n";
                    }
                    $orphans = TutorProfile::whereNotExists(function ($q): void {
                        $q->select(DB::raw(1))
                            ->from('users')
                            ->whereColumn('users.id', 'tutor_profiles.user_id');
                    })->delete();
                    if ($orphans > 0) {
                        echo "   ✓ Удалено сиротских анкет репетиторов: {$orphans}\n";
                    }
                } catch (\Throwable $e) {
                    echo "   ⚠️ Проверка анкет в БД: " . $e->getMessage() . "\n";
                }

                $kernel->call('optimize:clear');
                echo "   ✓ Кэш оптимизации Laravel сброшен:\n".$kernel->output()."\n";
                echo "\n🎉 <b>ОБНОВЛЕНИЕ ЗАВЕРШЕНО!</b>\n";
                echo "<a href='?key=edusfera2026' style='color:#38bdf8; font-weight:bold;'>← Вернуться в меню обслуживания</a>\n";
            } else {
                echo "   ❌ Не удалось открыть zip-архив: {$zipFile}\n";
            }
        }
        echo '</pre>';
        exit;
    }

    // ── ACTION: Cleanup Trashed Tutors & Orphan Profiles ─────────────
    if ($action === 'cleanup_trashed_tutors') {
        echo "🧹 <b>Очистка удаленных пользователей и зависших анкет...</b>\n\n";
        $trashedUsers = User::onlyTrashed()->get();
        echo "Найдено мягко-удаленных пользователей: {$trashedUsers->count()}\n";
        foreach ($trashedUsers as $u) {
            echo "  - Очистка [{$u->id}] {$u->email} ({$u->name})\n";
            $u->tutorProfile()?->delete();
            $u->subscription()?->delete();
            $u->availability()->delete();
            $u->tutorBalance()?->delete();
            $u->studentBalance()?->delete();
            $u->forceDelete();
        }

        $orphanedProfiles = TutorProfile::whereNotExists(function ($q): void {
            $q->select(DB::raw(1))
                ->from('users')
                ->whereColumn('users.id', 'tutor_profiles.user_id');
        })->get();
        echo "Найдено зависших анкет без пользователя: {$orphanedProfiles->count()}\n";
        foreach ($orphanedProfiles as $p) {
            echo "  - Удаление анкеты #{$p->id} (user_id={$p->user_id})\n";
            $p->delete();
        }

        $kernel->call('optimize:clear');
        echo "\n✓ Кэш очищен. Все удаленные аккаунты безвозвратно удалены!\n";
        echo "<a href='?key=edusfera2026' style='color:#38bdf8; font-weight:bold;'>← Вернуться в меню обслуживания</a>\n";
        echo '</pre>';
        exit;
    }

    // ── ACTION: Ensure Admin User ────────────────────────────────────
    if ($action === 'ensure_admin') {
        echo "<pre style='background:#0f172a;color:#f8fafc;padding:20px;border-radius:12px;font-family:monospace;'>";
        $admin = User::firstOrCreate(
            ['email' => 'admin@edusfera.by'],
            ['name' => 'Администратор Эдусфера', 'phone' => '+375293333333']
        );
        $admin->password = Hash::make('password');
        $admin->role = 'admin';
        $admin->save();
        echo "✓ Администратор admin@edusfera.by с паролем 'password' успешно обновлен!\n";
        echo "</pre>";
        exit;
    }

    // ── ACTION: Save LiveKit settings ────────────────────────────────
    if ($action === 'save_livekit') {
        echo "<pre style='background:#0f172a;color:#f8fafc;padding:20px;border-radius:12px;font-family:monospace;'>";
        echo "⚙️ <b>Сохранение настроек LiveKit Cloud в .env...</b>\n\n";
        $url = trim($_POST['livekit_url'] ?? '');
        $key = trim($_POST['livekit_key'] ?? '');
        $secret = trim($_POST['livekit_secret'] ?? '');

        updateEnvKey('LIVEKIT_URL', $url);
        updateEnvKey('LIVEKIT_HOST', $url);
        updateEnvKey('LIVEKIT_API_KEY', $key);
        updateEnvKey('LIVEKIT_API_SECRET', $secret);

        $kernel->call('config:clear');
        echo "   ✓ Настройки записаны в .env:\n";
        echo "     LIVEKIT_URL={$url}\n";
        echo "     LIVEKIT_API_KEY={$key}\n";
        echo '     LIVEKIT_API_SECRET='.($secret ? substr($secret, 0, 5).'***' : '')."\n\n";
        echo "   ✓ Кэш конфигурации сброшен.\n\n";
        echo "<a href='?key=edusfera2026&action=test_livekit' style='color:#38bdf8; font-weight:bold;'>👉 Проверить статус и выпуск токена LiveKit</a> | ";
        echo "<a href='?key=edusfera2026' style='color:#94a3b8;'>← В меню</a>\n";
        echo '</pre>';
        exit;
    }

    // ── ACTION: Test LiveKit connection ───────────────────────────────
    if ($action === 'test_livekit') {
        echo "<pre style='background:#0f172a;color:#f8fafc;padding:20px;border-radius:12px;font-family:monospace;'>";
        echo "🔍 <b>Диагностика подключения к LiveKit Cloud:</b>\n\n";
        $service = app(LiveKitService::class);
        $isConfigured = $service->isConfigured();

        echo '1. Статус конфигурации LiveKit: '.($isConfigured ? '✅ НАСТРОЕН' : '❌ НЕ НАСТРОЕН')."\n";
        echo '   Host / URL: '.$service->getWsUrl()."\n";
        echo '   API Key: '.(config('classroom.livekit_api_key') ?: 'пусто')."\n";
        echo '   API Secret: '.(config('classroom.livekit_api_secret') ? 'задан ('.strlen(config('classroom.livekit_api_secret')).' симв.)' : 'пусто')."\n\n";

        if ($isConfigured) {
            $lesson = Lesson::find(23) ?? Lesson::latest('id')->first();
            $user = User::first();
            if ($lesson && $user) {
                try {
                    $token = $service->generateToken($lesson, $user);
                    echo "2. Тест генерации JWT токена комнаты: ✅ УСПЕШНО\n";
                    echo "   Урок ID: {$lesson->id}\n";
                    echo "   Комната: edusfera_lesson_{$lesson->id}\n";
                    echo '   Токен (первые 40 симв.): '.substr($token, 0, 40)."...\n";
                } catch (Throwable $e) {
                    echo '2. Ошибка генерации токена: ❌ '.$e->getMessage()."\n";
                }
            }
        }
        echo "\n<a href='?key=edusfera2026' style='color:#38bdf8; font-weight:bold;'>← Вернуться в меню обслуживания</a>\n";
        echo '</pre>';
        exit;
    }

    // ── ACTION: Save Gemini AI settings ───────────────────────────────
    if ($action === 'save_gemini') {
        echo "<pre style='background:#0f172a;color:#f8fafc;padding:20px;border-radius:12px;font-family:monospace;'>";
        echo "⚙️ <b>Сохранение настроек Google Gemini AI в .env...</b>\n\n";
        $key = trim($_POST['gemini_key'] ?? '');
        $baseUrl = trim($_POST['gemini_base_url'] ?? '');
        $proxy = trim($_POST['gemini_proxy'] ?? '');
        $model = trim($_POST['gemini_model'] ?? 'gemini-flash-latest');
        $liteModel = trim($_POST['gemini_lite_model'] ?? 'gemini-3.5-flash-lite');
        $useXboxDns = ! empty($_POST['gemini_use_xbox_dns']) ? 'true' : 'false';
        $dnsServers = trim($_POST['gemini_dns_servers'] ?? '181.215.40.49,31.58.216.201');

        updateEnvKey('GEMINI_API_KEY', $key);
        updateEnvKey('GEMINI_BASE_URL', $baseUrl ?: 'https://generativelanguage.googleapis.com/v1beta');
        updateEnvKey('GEMINI_PROXY', $proxy);
        updateEnvKey('GEMINI_MODEL', $model);
        updateEnvKey('GEMINI_LITE_MODEL', $liteModel);
        updateEnvKey('GEMINI_USE_XBOX_DNS', $useXboxDns);
        updateEnvKey('GEMINI_DNS_SERVERS', $dnsServers);

        $kernel->call('config:clear');
        echo "   ✓ Настройки записаны в .env:\n";
        echo '     GEMINI_API_KEY='.($key ? substr($key, 0, 8).'...' : 'пусто')."\n";
        echo '     GEMINI_BASE_URL='.($baseUrl ?: 'https://generativelanguage.googleapis.com/v1beta')."\n";
        echo '     GEMINI_PROXY='.($proxy ?: 'отключен')."\n";
        echo "     GEMINI_MODEL={$model}\n";
        echo "     GEMINI_LITE_MODEL={$liteModel}\n";
        echo "     GEMINI_USE_XBOX_DNS={$useXboxDns}\n";
        echo "     GEMINI_DNS_SERVERS={$dnsServers}\n\n";
        echo "   ✓ Кэш конфигурации сброшен.\n\n";
        echo "<a href='?key=edusfera2026&action=test_ai' style='color:#38bdf8; font-weight:bold;'>👉 Проверить вызов Gemini API</a> | ";
        echo "<a href='?key=edusfera2026' style='color:#94a3b8;'>← В меню</a>\n";
        echo '</pre>';
        exit;
    }

    // ── ACTION: Test AI / Gemini ──────────────────────────────────────
    if ($action === 'test_ai') {
        echo "<pre style='background:#0f172a;color:#f8fafc;padding:20px;border-radius:12px;font-family:monospace;'>";
        echo "🤖 <b>Диагностика Google Gemini API:</b>\n\n";

        // Check if local proxy exists (e.g. 1080, 8080, 3128, etc)
        $proxyPorts = [1080, 8080, 3128, 8888, 9050];
        $openProxies = [];
        foreach ($proxyPorts as $p) {
            $fp = @fsockopen('127.0.0.1', $p, $errno, $errstr, 0.2);
            if ($fp) {
                $openProxies[] = $p;
                fclose($fp);
            }
        }
        echo '   Локальные порты (proxy?): '.(! empty($openProxies) ? implode(', ', $openProxies) : 'нет активных')."\n\n";

        $service = app(GeminiService::class);
        $isConfigured = $service->isConfigured();
        $useXbox = config('services.gemini.use_xbox_dns', true);
        echo '1. Статус конфигурации: '.($isConfigured ? '✅ НАСТРОЕН' : '❌ НЕ НАСТРОЕН')."\n";
        echo '   Model: '.config('services.gemini.model')."\n";
        echo '   Lite Model: '.config('services.gemini.lite_model')."\n";
        echo '   API Key: '.(config('services.gemini.api_key') ? substr(config('services.gemini.api_key'), 0, 8).'...' : 'пусто')."\n";
        echo '   Маршрутизация xbox-dns.ru: '.($useXbox ? '✅ ВКЛЮЧЕНА (SNI узлы: '.implode(', ', $service->getXboxDnsIps()).')' : '❌ ВЫКЛЮЧЕНА')."\n\n";

        if ($isConfigured) {
            try {
                $connResult = $service->testConnection();
                echo "2. Тест прямого подключения:\n";
                echo '   Успех: '.($connResult['success'] ? '✅ ДА' : '❌ НЕТ')."\n";
                echo '   Сообщение: '.($connResult['message'] ?? '')."\n";
                echo '   Задержка: '.($connResult['latency_ms'] ?? 0)." мс\n\n";

                $start = microtime(true);
                $resp = $service->generateText('Ответь одним коротким предложением: кто ты?', 'Ты умный методист Edusfera.');
                $dur = round((microtime(true) - $start), 2);
                echo '3. Тестовый запрос Gemini generateText: '.(! empty($resp) ? "✅ УСПЕШНО ({$dur} сек)" : '⚠️ ПУСТОЙ ОТВЕТ')."\n";
                echo "   Ответ: {$resp}\n";
            } catch (Throwable $e) {
                echo '2. Ошибка вызова Gemini: ❌ '.$e->getMessage()."\n";
            }
        }
        echo "\n<a href='?key=edusfera2026' style='color:#38bdf8; font-weight:bold;'>← Вернуться в меню обслуживания</a>\n";
        echo '</pre>';
        exit;
    }

    // ── ACTION: Test DNS proxy ─────────────────────────────────────────
    if ($action === 'test_xbox_dns') {
        echo "<pre style='background:#0f172a;color:#f8fafc;padding:20px;border-radius:12px;font-family:monospace;'>";
        echo "🎮 <b>Тестирование доступа через DNS (181.215.40.49, 31.58.216.201):</b>\n\n";

        $apiKey = config('services.gemini.api_key');
        echo '1. API Key: '.($apiKey ? substr($apiKey, 0, 8).'...' : 'пусто')."\n";

        // Step 1: IPs from config / service
        $ips = config('services.gemini.dns_servers')
            ? array_values(array_filter(array_map('trim', explode(',', config('services.gemini.dns_servers')))))
            : ['181.215.40.49', '31.58.216.201'];
        echo "2. Прокси-узлы DNS для generativelanguage.googleapis.com:\n";
        foreach ($ips as $ip) {
            echo "   - {$ip}\n";
        }

        // Step 2: Try curl with CURLOPT_RESOLVE for each IP
        foreach ($ips as $idx => $ip) {
            echo "\n3. Проверка узла #{$idx} ({$ip}):\n";

            // TCP Port test (53 and 443)
            $fp443 = @fsockopen($ip, 443, $errno443, $errstr443, 3);
            echo "   TCP 443: ".($fp443 ? "✅ Открыт" : "❌ Ошибка ({$errno443}: {$errstr443})")."\n";
            if ($fp443) fclose($fp443);

            $fp53 = @fsockopen("udp://{$ip}", 53, $errno53, $errstr53, 3);
            echo "   UDP 53: ".($fp53 ? "✅ Доступен" : "❌ Ошибка ({$errno53}: {$errstr53})")."\n";
            if ($fp53) fclose($fp53);

            $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-flash-latest:generateContent?key={$apiKey}";

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
                'contents' => [
                    ['role' => 'user', 'parts' => [['text' => 'Привет! Ответь одним коротким предложением: кто ты?']]],
                ],
            ]));
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 4);
            curl_setopt($ch, CURLOPT_TIMEOUT, 8);
            curl_setopt($ch, CURLOPT_RESOLVE, [
                "generativelanguage.googleapis.com:443:{$ip}",
            ]);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

            $t0 = microtime(true);
            $raw = curl_exec($ch);
            $dur = round((microtime(true) - $t0) * 1000);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $err = curl_error($ch);
            curl_close($ch);

            echo "   HTTP Code: {$code} (заняло {$dur} мс)\n";
            if ($err) {
                echo "   cURL Error: {$err}\n";
            }
            if ($raw) {
                $decoded = json_decode($raw, true);
                if (isset($decoded['candidates'][0]['content']['parts'][0]['text'])) {
                    echo "   ✅ УСПЕХ! Ответ модели:\n";
                    echo '   >>> '.trim($decoded['candidates'][0]['content']['parts'][0]['text'])."\n";
                    break;
                } else {
                    echo '   Ответ сервера: '.substr($raw, 0, 300)."\n";
                }
            }
        }

        echo "\n<a href='?key=edusfera2026' style='color:#38bdf8; font-weight:bold;'>← Вернуться в меню обслуживания</a>\n";
        echo '</pre>';
        exit;
    }

    // ── ACTION: Save TURN settings ────────────────────────────────────
    if ($action === 'save_turn') {
        echo "⚙️ <b>Сохранение настроек WebRTC / TURN в .env...</b>\n\n";
        $mApp = trim($_POST['metered_app'] ?? '');
        $mKey = trim($_POST['metered_key'] ?? '');

        updateEnvKey('METERED_APP_NAME', $mApp);
        updateEnvKey('METERED_API_KEY', $mKey);

        $kernel->call('config:clear');
        echo "   ✓ Настройки записаны в .env:\n";
        echo "     METERED_APP_NAME={$mApp}\n";
        echo '     METERED_API_KEY='.($mKey ? substr($mKey, 0, 5).'***' : '')."\n\n";
        echo "   ✓ Кэш конфигурации сброшен.\n\n";
        echo "<a href='?key=edusfera2026&action=test_turn' style='color:#38bdf8; font-weight:bold;'>👉 Проверить генерацию ICE/TURN серверов</a> | ";
        echo "<a href='?key=edusfera2026' style='color:#94a3b8;'>← В меню</a>\n";
        echo '</pre>';
        exit;
    }

    // ── ACTION: Test TURN / ICE servers ───────────────────────────────
    if ($action === 'test_turn') {
        echo "🔍 <b>Диагностика серверов WebRTC ICE & TURN для обхода 4G CGNAT:</b>\n\n";
        $service = app(ClassroomService::class);
        $iceServers = $service->getIceServers();

        echo 'Всего сконфигурировано ICE серверов: '.count($iceServers)."\n\n";
        $hasTurn = false;
        foreach ($iceServers as $i => $s) {
            $urls = (array) ($s['urls'] ?? []);
            $urlsStr = implode(', ', $urls);
            $isTurn = str_contains($urlsStr, 'turn:') || str_contains($urlsStr, 'turns:');
            if ($isTurn) {
                $hasTurn = true;
            }
            $type = $isTurn ? '🛡️ TURN Relay' : '⚡ STUN';
            echo "   [{$i}] {$type}: {$urlsStr}\n";
            if (isset($s['username'])) {
                echo '       User: '.$s['username']."\n";
            }
        }

        echo "\n";
        if ($hasTurn) {
            echo "   ✅ <b>TURN Relay сервер найден!</b>\n";
            echo "   Связь между мобильным телефоном на 4G/LTE и домашним Wi-Fi защищена и сможет обходить NAT.\n";
        } else {
            echo "   ⚠️ <b>ВНИМАНИЕ: Нет активного TURN Relay сервера!</b>\n";
            echo "   Для работы видео со смартфонов через 4G/LTE (без Docker) укажите Metered App/Key в меню обслуживания.\n";
        }
        echo "\n<a href='?key=edusfera2026' style='color:#38bdf8; font-weight:bold;'>← Вернуться в меню обслуживания</a>\n";
        echo '</pre>';
        exit;
    }

    // ── ACTION: Create Onboarding Test Tutor ───────────────────────────
    if ($action === 'create_onboarding_tutor') {
        echo "<pre style='background:#0f172a;color:#f8fafc;padding:20px;border-radius:12px;font-family:monospace;'>";
        echo "🧑‍🏫 <b>Создание/сброс тестового репетитора для онбординга:</b>\n\n";

        $email = 'tutor-onboarding@edusfera.by';
        $pass = 'Edusfera2026!';

        $tutor = User::withTrashed()->where('email', $email)->first();
        if (! $tutor) {
            $tutor = User::create([
                'name' => 'Виктория Репетитор',
                'email' => $email,
                'password' => Hash::make($pass),
                'role' => 'tutor',
                'phone' => '+37529'.rand(1000000, 9999999),
                'email_verified_at' => now(),
            ]);
        } else {
            if ($tutor->trashed()) {
                $tutor->restore();
            }
            $tutor->password = Hash::make($pass);
            $tutor->role = 'tutor';
            $tutor->save();
        }

        // Setup active trial subscription so middleware allows entering /admin
        $sub = \App\Domain\Subscription\Models\Subscription::updateOrCreate(
            ['tutor_id' => $tutor->id],
            [
                'plan' => \App\Domain\Subscription\Enums\SubscriptionPlan::PRO,
                'status' => \App\Domain\Subscription\Enums\SubscriptionStatus::TRIAL,
                'trial_ends_at' => now()->addDays(14),
                'current_period_starts_at' => now(),
                'current_period_ends_at' => now()->addDays(14),
                'is_onboarded' => true,
            ]
        );

        // Reset TutorProfile and Availability so onboarding triggers completely fresh!
        TutorProfile::where('user_id', $tutor->id)->delete();
        \App\Models\TutorAvailability::where('user_id', $tutor->id)->delete();

        // Ensure EDUSFERA2026 promo code is active in database
        $promo = \App\Models\PromoCode::withTrashed()->where('code', 'EDUSFERA2026')->first();
        if ($promo) {
            if ($promo->trashed()) {
                $promo->restore();
            }
            $promo->update([
                'description' => 'Специальный промокод платформы: 3 месяца бесплатного доступа к тарифу Pro',
                'discount_type' => 'free_period',
                'discount_value' => 0.00,
                'scope' => 'all',
                'subscription_period' => '3_months',
                'subscription_plan' => 'pro',
                'is_active' => true,
                'max_uses' => null,
                'max_uses_per_user' => 1,
                'starts_at' => now()->subDay(),
                'expires_at' => now()->addYears(2),
            ]);
        } else {
            \App\Models\PromoCode::create([
                'code' => 'EDUSFERA2026',
                'description' => 'Специальный промокод платформы: 3 месяца бесплатного доступа к тарифу Pro',
                'discount_type' => 'free_period',
                'discount_value' => 0.00,
                'scope' => 'all',
                'subscription_period' => '3_months',
                'subscription_plan' => 'pro',
                'is_active' => true,
                'max_uses' => null,
                'max_uses_per_user' => 1,
                'starts_at' => now()->subDay(),
                'expires_at' => now()->addYears(2),
            ]);
        }

        echo "✅ <b>Тестовый аккаунт репетитора готов к онбордингу!</b>\n";
        echo "   Email:    <b>{$email}</b>\n";
        echo "   Пароль:   <b>{$pass}</b>\n";
        echo "   Роль:     tutor (Репетитор)\n";
        echo "   Статус:   Анкета и расписание сброшены (onboarding_completed_at = null)\n\n";
        echo "👉 <b>Ссылка для входа:</b> <a href='/admin/login' target='_blank' style='color:#38bdf8;font-weight:bold;'>https://edusfera.by/admin/login</a>\n\n";
        echo "<a href='?key=edusfera2026' style='color:#94a3b8;'>← В меню</a></pre>";
        exit;
    }

    // ── ACTION: Ensure Admin ───────────────────────────────────────────
    if ($action === 'ensure_admin') {
        echo "👤 <b>Настройка учетной записи администратора:</b>\n\n";
        $admins = User::withTrashed()->where('role', 'admin')->orWhere('role', \App\Enums\UserRole::Admin)->orWhere('email', 'like', '%admin%')->get();
        echo "Найдено аккаунтов администраторов: " . $admins->count() . "\n";
        foreach ($admins as $u) {
            echo " - ID: {$u->id}, Email: '{$u->email}', Role: " . (is_object($u->role) ? $u->role->value : $u->role) . ", Trashed: " . ($u->trashed() ? 'YES' : 'NO') . "\n";
            $u->restore();
            $u->password = 'Password123!';
            $u->role = \App\Enums\UserRole::Admin;
            $u->save();
            echo "   -> Пароль сброшен на 'Password123!'. Проверка Hash::check: " . (Hash::check('Password123!', $u->password) ? 'OK' : 'FAIL') . "\n";
        }
        
        $admin = User::firstOrCreate(
            ['email' => 'admin@edusfera.by'],
            [
                'name' => 'Администратор Edusfera',
                'password' => 'Password123!',
                'role' => \App\Enums\UserRole::Admin,
                'phone' => '+375293333333',
                'email_verified_at' => now(),
            ]
        );
        $admin->restore();
        $admin->password = 'Password123!';
        $admin->role = \App\Enums\UserRole::Admin;
        $admin->save();
        echo "✓ Гарантирован основной аккаунт admin@edusfera.by (Пароль: Password123!, Valid: " . (Hash::check('Password123!', $admin->password) ? 'OK' : 'FAIL') . ")\n";

        echo "\n<a href='?key=edusfera2026' style='color:#38bdf8; font-weight:bold;'>← Вернуться в меню обслуживания</a>\n";
        echo '</pre>';
        exit;
    }

    // ── ACTION: Classroom test ────────────────────────────────────────
    if ($action === 'classroom_test') {
        echo "🎥 <b>Создание тестового ученика, связки с репетитором и урока:</b>\n\n";
        $useTestTutor = isset($_GET['test_tutor']);
        if ($useTestTutor) {
            $testTutor = User::withTrashed()->where('email', 'tutor-test@edusfera.by')->first();
            if (! $testTutor) {
                $testTutor = User::create([
                    'name' => 'Виктор Репетитор',
                    'email' => 'tutor-test@edusfera.by',
                    'password' => Hash::make('Password123!'),
                    'role' => 'tutor',
                    'phone' => '+37529'.rand(1000000, 9999999),
                    'email_verified_at' => now(),
                ]);
            } else {
                if ($testTutor->trashed()) {
                    $testTutor->restore();
                }
                $testTutor->password = Hash::make('Password123!');
                $testTutor->save();
            }
            TutorProfile::firstOrCreate(
                ['user_id' => $testTutor->id],
                ['headline' => 'Тестовый репетитор', 'hourly_rate' => 35.00, 'is_approved' => true, 'is_active' => true]
            );
            $kernel->call('classroom:prepare-test', ['--tutor-email' => 'tutor-test@edusfera.by']);
        } else {
            $kernel->call('classroom:prepare-test');
        }
        echo $kernel->output();
        $lastLesson = Lesson::orderByDesc('id')->first();
        if ($lastLesson) {
            $joinToken = substr(hash_hmac('sha256', 'classroom_join_'.$lastLesson->id, (string) config('app.key')), 0, 16);
            $joinUrl = url("/classroom/{$lastLesson->id}/join?token={$joinToken}");
            echo "\n🔗 1-Click Guest Join URL: {$joinUrl}\n";
        }
        echo "\n<a href='?key=edusfera2026' style='color:#38bdf8; font-weight:bold;'>← Вернуться в меню обслуживания</a>\n";
        echo '</pre>';
        exit;
    }

    // ── ACTION: Inspect signals ──────────────────────────────────────
    if ($action === 'inspect_signals') {
        echo "🔍 <b>Содержимое кэша сигналов WebRTC:</b>\n\n";
        $targetLid = (int) ($_GET['lesson_id'] ?? 23);
        $checkList = array_unique([$targetLid, 23, 22, 21, 20]);
        foreach ($checkList as $lid) {
            $sigKey = 'classroom_signals_'.$lid;
            $sigs = Cache::get($sigKey, []);
            echo "--- Lesson {$lid} ({$sigKey}): ".count($sigs)." сигналов ---\n";
            foreach (array_slice($sigs, -25) as $s) {
                $type = $s['type'] ?? 'unknown';
                $role = $s['sender_role'] ?? 'unknown';
                $sender = $s['sender_id'] ?? 'unknown';
                $cid = $s['client_id'] ?? 'unknown';
                $seq = $s['seq'] ?? '-';
                $t = isset($s['created_at_ms']) ? date('H:i:s', (int) ($s['created_at_ms'] / 1000)) : '?';
                echo "   [{$t} | seq:{$seq}] Type: {$type} | Role: {$role} | Sender: {$sender} | Client: {$cid}\n";
            }
            echo "\n";
        }
        echo "\nПоследние 150 строк laravel.log:\n";
        $logPath = storage_path('logs/laravel.log');
        if (file_exists($logPath)) {
            $lines = array_slice(file($logPath), -150);
            echo htmlspecialchars(implode('', $lines));
        }
        echo "\n<a href='?key=edusfera2026' style='color:#38bdf8;'>← В меню</a></pre>";
        exit;
    }

    // ── ACTION: Inspect lesson ───────────────────────────────────────
    if ($action === 'inspect_lesson') {
        echo "📋 <b>Информация об уроке и пользователях:</b>\n\n";
        $lid = (int) ($_GET['lesson_id'] ?? 23);
        $lesson = Lesson::find($lid);
        if ($lesson) {
            echo "Урок #{$lesson->id}:\n";
            echo "  Статус: {$lesson->status}\n";
            echo "  Tutor ID: {$lesson->tutor_id} ({$lesson->tutor?->name} / {$lesson->tutor?->email})\n";
            echo "  Student ID: {$lesson->student_id} ({$lesson->student?->name} / {$lesson->student?->email})\n";
            echo "  Start: {$lesson->start_time} | End: {$lesson->end_time}\n";
            $session = $lesson->activeClassroom ?? ClassroomSession::where('lesson_id', $lid)->latest('id')->first();
            if ($session) {
                echo "  ClassroomSession #{$session->id}: room_id={$session->room_id}, status={$session->status}, started_at={$session->started_at}\n";
            } else {
                echo "  ClassroomSession: отсутствует\n";
            }
        } else {
            echo "Урок #{$lid} не найден в БД.\n";
        }
        echo "\n<a href='?key=edusfera2026' style='color:#38bdf8;'>← В меню</a></pre>";
        exit;
    }

    // ── ACTION: Inspect Users Incident ────────────────────────────────
    if ($action === 'inspect_users_incident') {
        echo "🔍 <b>Детальное расследование инцидента 20.10.2026:</b>\n\n";

        $galina = User::where('email', 'galuny2013@gmail.com')->first();
        $andrey = User::where('email', 'koipysh77@gmail.com')->first();

        echo "👤 <b>Пользователи:</b>\n";
        $galinaRole = $galina?->role instanceof \BackedEnum ? $galina->role->value : ($galina?->role ?? 'none');
        $andreyRole = $andrey?->role instanceof \BackedEnum ? $andrey->role->value : ($andrey?->role ?? 'none');
        echo "  Галина: " . ($galina ? "ID: {$galina->id}, Name: {$galina->name}, Role: {$galinaRole}, Created: {$galina->created_at}" : "НЕ НАЙДЕНА") . "\n";
        echo "  Андрей: " . ($andrey ? "ID: {$andrey->id}, Name: {$andrey->name}, Role: {$andreyRole}, Created: {$andrey->created_at}" : "НЕ НАЙДЕН") . "\n\n";

        $userIds = array_filter([$galina?->id, $andrey?->id]);

        echo "📚 <b>Уроки (Lessons #40, #41 и связанные):</b>\n";
        $lessons = Lesson::whereIn('id', [40, 41])
            ->orWhere(function($q) use ($userIds) {
                if (!empty($userIds)) {
                    $q->whereIn('tutor_id', $userIds)->orWhereIn('student_id', $userIds);
                }
            })
            ->latest('id')
            ->limit(10)
            ->get();

        foreach ($lessons as $l) {
            $statusStr = $l->status instanceof \BackedEnum ? $l->status->value : $l->status;
            echo "  Урок #{$l->id}:\n";
            echo "    Статус: {$statusStr}\n";
            echo "    Tutor: ID {$l->tutor_id} ({$l->tutor?->name})\n";
            echo "    Student: ID {$l->student_id} ({$l->student?->name})\n";
            echo "    Subject: " . ($l->subject?->name ?? 'не указан') . " | Price: {$l->price}\n";
            echo "    Start: {$l->start_time} | End: {$l->end_time}\n";
            echo "    Created: {$l->created_at} | Updated: {$l->updated_at}\n";
            echo "    Meta: " . json_encode($l->meta, JSON_UNESCAPED_UNICODE) . "\n\n";
        }

        echo "🚪 <b>Сессии виртуального класса (ClassroomSession):</b>\n";
        $sessions = ClassroomSession::whereIn('lesson_id', [40, 41])
            ->latest('id')
            ->get();
        if ($sessions->isEmpty()) {
            echo "  Сессий для уроков 40 и 41 не найдено.\n";
        }
        foreach ($sessions as $s) {
            $sessStatusStr = $s->status instanceof \BackedEnum ? $s->status->value : $s->status;
            echo "  Сессия #{$s->id} (Урок #{$s->lesson_id}):\n";
            echo "    Room ID: {$s->room_id}\n";
            echo "    Статус: {$sessStatusStr}\n";
            echo "    Started At: {$s->started_at} | Ended At: {$s->ended_at} | Длительность: {$s->duration_seconds} сек\n";
            echo "    Meta: " . json_encode($s->meta, JSON_UNESCAPED_UNICODE) . "\n";
            echo "    Whiteboard State: " . ($s->whiteboard_state ? "Имеется (" . strlen(json_encode($s->whiteboard_state)) . " байт)" : "пусто") . "\n\n";
        }

        echo "📜 <b>Аудит-логи (UserAuditLog):</b>\n";
        if (Schema::hasTable('user_audit_logs')) {
            $logs = DB::table('user_audit_logs')
                ->where(function($q) use ($userIds) {
                    if (!empty($userIds)) {
                        $q->whereIn('user_id', $userIds);
                    }
                    $q->orWhere('details', 'LIKE', '%"lesson_id":40%')
                      ->orWhere('details', 'LIKE', '%"lesson_id":41%')
                      ->orWhere('ip_address', '37.214.3.16')
                      ->orWhere('ip_address', '37.214.3.157');
                })
                ->orderBy('created_at', 'asc')
                ->get();

            echo "  Найдено записей аудита: {$logs->count()}\n";
            foreach ($logs as $log) {
                $act = $log->action ?? 'unknown';
                echo "  [{$log->created_at}] User ID: {$log->user_id} ({$log->user_role}) | Action: {$act} | Status: {$log->status} | IP: {$log->ip_address}\n";
                echo "      Details: {$log->details}\n";
            }
        } else {
            echo "  Таблица user_audit_logs отсутствует.\n";
        }
        echo "\n";

        echo "📡 <b>Телеметрия Андрея (IP 37.214.3.157 / User ID 29) за 20.10.2026:</b>\n";
        if (Schema::hasTable('platform_telemetry')) {
            $telemetryAndrey = DB::table('platform_telemetry')
                ->where(function($q) {
                    $q->where('user_id', 29)
                      ->orWhere('ip_address', '37.214.3.157');
                })
                ->where('created_at', 'LIKE', '2026-10-20%')
                ->orderBy('created_at', 'asc')
                ->get();

            echo "  Записей телеметрии Андрея: {$telemetryAndrey->count()}\n";
            foreach ($telemetryAndrey as $t) {
                echo "  [{$t->created_at}] Route: {$t->route} | Method: {$t->method} | Status: {$t->status_code} | URL: {$t->url}\n";
                if ($t->error_message) {
                    echo "      Error: {$t->error_message}\n";
                }
            }

            echo "\n📡 <b>Запросы в /classroom/* за 20.10.2026:</b>\n";
            $classroomTele = DB::table('platform_telemetry')
                ->where('url', 'LIKE', '%classroom%')
                ->where('created_at', 'LIKE', '2026-10-20%')
                ->orderBy('created_at', 'asc')
                ->get();
            echo "  Записей входа в класс за 20.10.2026: {$classroomTele->count()}\n";
            foreach ($classroomTele as $t) {
                echo "  [{$t->created_at}] User ID: {$t->user_id} | IP: {$t->ip_address} | Status: {$t->status_code} | URL: {$t->url}\n";
            }
        }
        echo "\n";

        echo "💬 <b>Переписка и чат (Messages):</b>\n";
        if (Schema::hasTable('messages')) {
            $messages = DB::table('messages')
                ->where(function($q) use ($userIds) {
                    if (!empty($userIds)) {
                        $q->whereIn('sender_id', $userIds);
                    }
                })
                ->orderBy('created_at', 'asc')
                ->get();

            echo "  Сообщений в чатах: {$messages->count()}\n";
            foreach ($messages as $m) {
                echo "  [{$m->created_at}] Conversation #{$m->conversation_id} | Sender ID: {$m->sender_id} | Message: {$m->message}\n";
            }
        }
        echo "\n";

        echo "📑 <b>Записи из laravel.log (за 20.10.2026):</b>\n";
        $logPath = __DIR__.'/../storage/logs/laravel.log';
        if (file_exists($logPath)) {
            $lines = file($logPath);
            $matched = [];
            foreach ($lines as $line) {
                if (str_contains($line, '2026-10-20') && (
                    str_contains($line, '40') ||
                    str_contains($line, '41') ||
                    str_contains($line, 'galuny') ||
                    str_contains($line, 'koipysh') ||
                    str_contains($line, '37.214.3') ||
                    str_contains($line, 'LiveKit') ||
                    str_contains($line, 'Classroom')
                )) {
                    $matched[] = $line;
                }
            }
            echo "  Найдено строк в логе: " . count($matched) . "\n";
            echo htmlspecialchars(implode("", array_slice($matched, -50)));
        }

        echo "\n<a href='?key=edusfera2026' style='color:#38bdf8;'>← В меню</a></pre>";
        exit;
    }

    if ($action === 'inspect_promos') {
        echo "🏷️ <b>Промокоды и последние использования:</b>\n\n";
        $promos = \App\Models\PromoCode::latest('id')->limit(20)->get();
        foreach ($promos as $p) {
            echo "ID: {$p->id} | Code: {$p->code} | Type: {$p->type} | Plan: {$p->subscription_plan} | Uses: {$p->used_count}/" . ($p->max_uses ?? '∞') . " | MaxPerUser: " . ($p->max_uses_per_user ?? '1') . " | Active: " . ($p->is_active ? 'YES' : 'NO') . "\n";
        }
        echo "\n<b>Последние использования (PromoCodeUsage):</b>\n";
        $usages = \App\Models\PromoCodeUsage::latest('id')->limit(15)->get();
        foreach ($usages as $u) {
            echo "ID: {$u->id} | Promo ID: {$u->promo_code_id} | User ID: {$u->user_id} | OrderType: {$u->order_type} | Created: {$u->created_at}\n";
        }
        echo "\n<b>Последние подписки (Subscriptions):</b>\n";
        $subs = \App\Domain\Subscription\Models\Subscription::latest('updated_at')->limit(10)->get();
        foreach ($subs as $s) {
            echo "ID: {$s->id} | Tutor ID: {$s->tutor_id} | Plan: {$s->plan->value} | Status: {$s->status->value} | Period Ends: {$s->current_period_ends_at} | Updated: {$s->updated_at}\n";
        }
        echo "\n<a href='?key=edusfera2026' style='color:#38bdf8;'>← В меню</a></pre>";
        exit;
    }

    if ($action === 'server_health') {
        header('Content-Type: application/json; charset=utf-8');
        
        $load = function_exists('sys_getloadavg') ? @sys_getloadavg() : [0, 0, 0];
        if (! is_array($load)) {
            $load = [0, 0, 0];
        }
        
        // Meminfo (suppress open_basedir error)
        $meminfo = [];
        try {
            if (@is_readable('/proc/meminfo')) {
                $memLines = @file('/proc/meminfo');
                if (is_array($memLines)) {
                    foreach ($memLines as $line) {
                        if (preg_match('/^([a-zA-Z0-9_()]+):\s+(\d+)\s*kB/', $line, $m)) {
                            $meminfo[$m[1]] = round(((int)$m[2]) / 1024, 1); // in MB
                        }
                    }
                }
            }
        } catch (Throwable $e) {}
        
        // CPU cores
        $cpuCores = 1;
        try {
            if (@is_readable('/proc/cpuinfo')) {
                $cpuInfo = @file_get_contents('/proc/cpuinfo');
                if ($cpuInfo) {
                    $cpuCores = max(1, substr_count($cpuInfo, 'processor'));
                }
            }
        } catch (Throwable $e) {}
        
        // MySQL Status
        $dbStatus = [];
        try {
            $db = $app->make('db');
            $rows = $db->select("SHOW GLOBAL STATUS WHERE Variable_name IN ('Threads_connected', 'Threads_running', 'Max_used_connections', 'Uptime', 'Queries', 'Aborted_connects')");
            foreach ($rows as $r) {
                $dbStatus[$r->Variable_name] = $r->Value;
            }
        } catch (Throwable $e) {
            $dbStatus['error'] = $e->getMessage();
        }
        
        // OPcache
        $opcacheInfo = [];
        if (function_exists('opcache_get_status')) {
            $opStatus = @opcache_get_status(false);
            if ($opStatus) {
                $opcacheInfo = [
                    'used_memory_mb' => round(($opStatus['memory_usage']['used_memory'] ?? 0) / 1024 / 1024, 1),
                    'free_memory_mb' => round(($opStatus['memory_usage']['free_memory'] ?? 0) / 1024 / 1024, 1),
                    'hit_rate' => round($opStatus['opcache_statistics']['opcache_hit_rate'] ?? 0, 1),
                    'cached_scripts' => $opStatus['opcache_statistics']['num_cached_scripts'] ?? 0,
                ];
            }
        }
        
        // Disk
        $diskTotalGb = round(@disk_total_space(__DIR__) / 1024 / 1024 / 1024, 2);
        $diskFreeGb = round(@disk_free_space(__DIR__) / 1024 / 1024 / 1024, 2);
        
        // Laravel log recent errors
        $logPath = __DIR__.'/../storage/logs/laravel.log';
        $logTail = '';
        $recentErrorCount = 0;
        if (file_exists($logPath)) {
            $chunk = substr(file_get_contents($logPath), -50000);
            $recentErrorCount = substr_count($chunk, '.ERROR:');
            $lines = explode("\n", trim($chunk));
            $logTail = implode("\n", array_slice($lines, -15));
        }

        echo json_encode([
            'status' => 'ok',
            'timestamp' => date('Y-m-d H:i:s T'),
            'load_avg' => [
                '1m' => round($load[0] ?? 0, 2),
                '5m' => round($load[1] ?? 0, 2),
                '15m' => round($load[2] ?? 0, 2),
            ],
            'cpu_cores' => $cpuCores,
            'php' => [
                'version' => PHP_VERSION,
                'memory_usage_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
                'memory_peak_mb' => round(memory_get_peak_usage(true) / 1024 / 1024, 2),
                'memory_limit' => ini_get('memory_limit'),
            ],
            'os_memory' => [
                'total_mb' => $meminfo['MemTotal'] ?? null,
                'free_mb' => $meminfo['MemFree'] ?? null,
                'available_mb' => $meminfo['MemAvailable'] ?? null,
                'swap_used_mb' => isset($meminfo['SwapTotal'], $meminfo['SwapFree']) ? round($meminfo['SwapTotal'] - $meminfo['SwapFree'], 1) : null,
            ],
            'disk' => [
                'total_gb' => $diskTotalGb,
                'free_gb' => $diskFreeGb,
            ],
            'mysql' => $dbStatus,
            'opcache' => $opcacheInfo,
            'laravel_log' => [
                'recent_50kb_errors' => $recentErrorCount,
                'tail' => $logTail,
            ],
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($action === 'view_log') {
        echo "📋 <b>Последние строки storage/logs/laravel.log:</b>\n\n";
        $logPath = __DIR__.'/../storage/logs/laravel.log';
        if (file_exists($logPath)) {
            $content = file_get_contents($logPath);
            $chunk = substr($content, -40000);
            echo htmlspecialchars($chunk);
        } else {
            echo "Файл лога не найден: {$logPath}\n";
        }
        echo "\n<a href='?key=edusfera2026' style='color:#38bdf8;'>← В меню</a></pre>";
        exit;
    }

    // ── ACTION: Run migrations (default) ──────────────────────────────
    echo "🛠️ <b>Запуск миграций и оптимизации базы данных...</b>\n\n";
    $db = $app->make('db');

    try {
        $hasPromoCodes = ! empty($db->select("SHOW TABLES LIKE 'promo_codes'"));
        if ($hasPromoCodes) {
            $existingCols = $db->select('SHOW COLUMNS FROM promo_codes');
            $colNames = array_map(fn ($c) => $c->Field, $existingCols);
            $db->statement("ALTER TABLE promo_codes MODIFY discount_type VARCHAR(32) NOT NULL DEFAULT 'percent'");
            $db->statement('ALTER TABLE promo_codes MODIFY discount_value DECIMAL(10, 2) NOT NULL DEFAULT 0.00');
            if (! in_array('subscription_period', $colNames, true)) {
                $db->statement('ALTER TABLE promo_codes ADD COLUMN subscription_period VARCHAR(32) NULL AFTER scope');
            }
            if (! in_array('subscription_plan', $colNames, true)) {
                $db->statement('ALTER TABLE promo_codes ADD COLUMN subscription_plan VARCHAR(32) NULL AFTER subscription_period');
            }
            echo "   ✓ Таблица promo_codes обновлена.\n";
        }
    } catch (Throwable $e) {
        echo '   ⚠️ '.$e->getMessage()."\n";
    }

    try {
        if (Schema::connection('news')->hasTable('news_articles') && ! Schema::connection('news')->hasColumn('news_articles', 'category')) {
            Schema::connection('news')->table('news_articles', function ($table) {
                $table->string('category')->default('platform-training')->after('slug');
            });
            echo "   ✓ Колонка category добавлена в таблицу news_articles.\n";
        }

        if (class_exists(\Database\Seeders\PlatformTrainingArticlesSeeder::class)) {
            (new \Database\Seeders\PlatformTrainingArticlesSeeder())->run();
            echo "   ✓ Статьи и памятки с актуальными скриншотами успешно обновлены в базе знаний.\n";
        }
    } catch (Throwable $e) {
        echo '   ⚠️ Проверка новостей/памяток: '.$e->getMessage()."\n";
    }

    try {
        // Автоматическое обновление GEMINI_DNS_SERVERS в .env при обнаружении устаревших IP
        $currentDnsEnv = (string) env('GEMINI_DNS_SERVERS', '');
        if (empty($currentDnsEnv) || str_contains($currentDnsEnv, '143.20.64.55') || str_contains($currentDnsEnv, '91.108.243.78')) {
            updateEnvKey('GEMINI_DNS_SERVERS', '181.215.40.49,31.58.216.201');
            echo "   ✓ DNS серверы для Gemini в .env обновлены на 181.215.40.49,31.58.216.201\n";
        }
    } catch (Throwable $e) {
        echo '   ⚠️ Проверка Gemini DNS в .env: '.$e->getMessage()."\n";
    }

    echo "\n⚡ <b>Запуск php artisan migrate --force:</b>\n";
    $kernel->call('migrate', ['--force' => true]);
    echo $kernel->output();

    echo "\n⚡ <b>Очистка системного кэша и сброс Rate Limiter:</b>\n";
    $kernel->call('optimize:clear');
    echo $kernel->output();
    $kernel->call('cache:clear');
    echo $kernel->output();
    try {
        \Illuminate\Support\Facades\Cache::flush();
        echo "   ✓ Rate Limiter и кэш данных полностью сброшены (Cache::flush).\n";
    } catch (Throwable $e) {
        echo "   ⚠️ Cache::flush: ".$e->getMessage()."\n";
    }

    echo "\n🎉 <b>ВСЕ ОПЕРАЦИИ ВЫПОЛНЕНЫ!</b>\n";
    echo "<a href='?key=edusfera2026' style='color:#38bdf8; font-weight:bold;'>← Вернуться в панель обслуживания</a>\n";

} catch (Throwable $e) {
    echo "\n❌ <b>ОШИБКА:</b> ".htmlspecialchars($e->getMessage())."\n\n";
    echo htmlspecialchars($e->getTraceAsString());
}

echo '</pre>';
