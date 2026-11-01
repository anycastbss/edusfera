<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\PlatformTelemetry;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class CapturePlatformTelemetry
{
    private const START_TIME_KEY = '_telemetry_start_time';
    private const START_MEM_KEY = '_telemetry_start_memory';

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $request->attributes->set(self::START_TIME_KEY, microtime(true));
        $request->attributes->set(self::START_MEM_KEY, memory_get_usage(true));

        // Enable query log for accurate DB metrics gathering
        try {
            DB::enableQueryLog();
        } catch (Throwable) {
            // Ignore if DB connection fails early
        }

        return $next($request);
    }

    /**
     * Handle tasks after the response has been sent to the browser.
     */
    public function terminate(Request $request, Response $response): void
    {
        try {
            if ($this->shouldSkip($request)) {
                return;
            }

            $defaultStart = defined('LARAVEL_START') ? (float) constant('LARAVEL_START') : microtime(true);
            $startTime = (float) $request->attributes->get(self::START_TIME_KEY, $defaultStart);
            $startMem = (int) $request->attributes->get(self::START_MEM_KEY, 0);

            $durationMs = (int) round((microtime(true) - $startTime) * 1000);
            $peakMemMb = round((memory_get_peak_usage(true) - $startMem) / (1024 * 1024), 2);
            if ($peakMemMb < 0.1) {
                $peakMemMb = round(memory_get_peak_usage(true) / (1024 * 1024), 2);
            }

            // DB Query Stats
            $queries = [];
            $dbDurationMs = 0;
            try {
                $queries = DB::getQueryLog();
                foreach ($queries as $q) {
                    $dbDurationMs += (int) ($q['time'] ?? 0);
                }
            } catch (Throwable) {}

            $user = null;
            try {
                $user = $request->user()
                    ?? (class_exists(\Filament\Facades\Filament::class) ? \Filament\Facades\Filament::auth()->user() : null)
                    ?? Auth::user();
            } catch (Throwable) {}

            $userRole = 'guest';
            if ($user) {
                $rawRole = $user->role ?? 'user';
                $userRole = $rawRole instanceof \BackedEnum ? $rawRole->value : (string) $rawRole;
            }

            $ua = (string) $request->userAgent();
            $deviceInfo = $this->parseUserAgent($ua);

            $actionType = $this->resolveActionType($request);

            $route = null;
            try {
                $route = $request->route()?->getName();
            } catch (Throwable) {}

            $statusCode = $response->getStatusCode();
            $errorMessage = null;

            if ($statusCode >= 400) {
                if ($statusCode === 404) {
                    $errorMessage = 'Page Not Found';
                } elseif ($statusCode === 429) {
                    $errorMessage = 'Rate Limit Exceeded (Too Many Requests)';
                } elseif ($statusCode >= 500) {
                    $errorMessage = 'Server Internal Error (' . $statusCode . ')';
                }
            }

            $telemetryUserId = null;
            if ($user && $user->getKey()) {
                try {
                    if ($user->exists && \Illuminate\Support\Facades\DB::table('users')->where('id', $user->getKey())->exists()) {
                        $telemetryUserId = $user->getKey();
                    }
                } catch (\Throwable) {}
            }

            PlatformTelemetry::create([
                'user_id' => $telemetryUserId,
                'user_role' => $userRole,
                'method' => strtoupper($request->method()),
                'url' => substr($request->fullUrl(), 0, 500),
                'route' => $route ? substr($route, 0, 100) : null,
                'status_code' => $statusCode,
                'duration_ms' => max(0, $durationMs),
                'db_query_count' => count($queries),
                'db_duration_ms' => max(0, $dbDurationMs),
                'memory_mb' => max(0.0, $peakMemMb),
                'ip_address' => substr((string) ($request->ip() ?? '127.0.0.1'), 0, 45),
                'device_type' => $deviceInfo['device'],
                'browser' => $deviceInfo['browser'],
                'os' => $deviceInfo['os'],
                'action_type' => $actionType,
                'event_name' => $request->header('X-Telemetry-Event'),
                'user_agent' => substr($ua, 0, 500),
                'referer' => substr((string) $request->header('referer', ''), 0, 500) ?: null,
                'error_message' => $errorMessage,
                'metadata' => [
                    'query_params' => $request->query(),
                    'is_ajax' => $request->ajax(),
                    'content_type' => $response->headers->get('content-type'),
                ],
                'created_at' => now(),
            ]);
        } catch (Throwable $e) {
            if (app()->environment('testing')) {
                throw $e;
            }
            // Silently suppress errors in production — telemetry MUST NEVER disrupt the request
        }
    }

    private function shouldSkip(Request $request): bool
    {
        $path = $request->path();

        // Skip internal health checks and system telemetry polling to prevent recursion
        if ($path === 'up' || str_starts_with($path, 'site-admin/telemetry')) {
            return true;
        }

        // Skip static asset extensions
        if (preg_match('/\.(js|css|png|jpg|jpeg|gif|ico|svg|webp|woff|woff2|ttf|eot|map|txt|xml|webmanifest)$/i', $path)) {
            return true;
        }

        // Skip Vite hot reload or internal build assets
        if (str_starts_with($path, 'build/') || str_starts_with($path, '@vite')) {
            return true;
        }

        return false;
    }

    private function resolveActionType(Request $request): string
    {
        $path = $request->path();

        if ($request->hasHeader('X-Livewire') || str_contains($path, 'livewire')) {
            return 'livewire';
        }

        if (str_starts_with($path, 'api/telemetry')) {
            return 'beacon';
        }

        if (str_starts_with($path, 'api/')) {
            return 'api';
        }

        if (str_contains($path, 'classroom')) {
            return 'classroom';
        }

        if (str_contains($path, 'copilot') || str_contains($path, 'ai-assistant')) {
            return 'ai';
        }

        if (str_contains($path, 'login') || str_contains($path, 'register') || str_contains($path, 'auth')) {
            return 'auth';
        }

        if (str_contains($path, 'checkout') || str_contains($path, 'payment') || str_contains($path, 'subscription') || str_contains($path, 'wallet')) {
            return 'payment';
        }

        return 'pageview';
    }

    private function parseUserAgent(string $ua): array
    {
        if (empty($ua)) {
            return ['device' => 'desktop', 'browser' => 'Unknown', 'os' => 'Unknown'];
        }

        // Bot Detection
        if (preg_match('/(bot|crawl|spider|slurp|facebookexternalhit|bingbot|googlebot)/i', $ua)) {
            return ['device' => 'bot', 'browser' => 'Bot/Crawler', 'os' => 'Server'];
        }

        // Device Type
        $device = 'desktop';
        if (preg_match('/(tablet|ipad|playbook|silk)|(android(?!.*mobile))/i', $ua)) {
            $device = 'tablet';
        } elseif (preg_match('/(mobi|iphone|ipod|blackberry|opera mini|iemobile|mobile)/i', $ua)) {
            $device = 'mobile';
        }

        // OS
        $os = 'Unknown';
        if (str_contains($ua, 'iPhone') || str_contains($ua, 'iPad')) {
            $os = 'iOS';
        } elseif (str_contains($ua, 'Android')) {
            $os = 'Android';
        } elseif (str_contains($ua, 'Macintosh') || str_contains($ua, 'Mac OS')) {
            $os = 'macOS';
        } elseif (str_contains($ua, 'Windows')) {
            $os = 'Windows';
        } elseif (str_contains($ua, 'Linux')) {
            $os = 'Linux';
        }

        // Browser
        $browser = 'Unknown';
        if (str_contains($ua, 'Edg')) {
            $browser = 'Edge';
        } elseif (str_contains($ua, 'YaBrowser')) {
            $browser = 'Yandex';
        } elseif (str_contains($ua, 'Chrome') && ! str_contains($ua, 'Edg')) {
            $browser = 'Chrome';
        } elseif (str_contains($ua, 'Safari') && ! str_contains($ua, 'Chrome')) {
            $browser = 'Safari';
        } elseif (str_contains($ua, 'Firefox')) {
            $browser = 'Firefox';
        } elseif (str_contains($ua, 'Opera') || str_contains($ua, 'OPR')) {
            $browser = 'Opera';
        }

        return [
            'device' => $device,
            'browser' => $browser,
            'os' => $os,
        ];
    }
}
