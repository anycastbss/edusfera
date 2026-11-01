<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PlatformTelemetry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Throwable;

class TelemetryEventController extends Controller
{
    /**
     * Record a client-side telemetry beacon event (Web Vitals, JS error, custom action).
     */
    public function record(Request $request): JsonResponse
    {
        try {
            $data = $request->validate([
                'event_name' => ['required', 'string', 'max:100'],
                'url' => ['nullable', 'string', 'max:500'],
                'duration_ms' => ['nullable', 'numeric'],
                'error_message' => ['nullable', 'string', 'max:1000'],
                'metadata' => ['nullable', 'array'],
            ]);

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
            $device = 'desktop';
            if (preg_match('/(tablet|ipad|playbook|silk)|(android(?!.*mobile))/i', $ua)) {
                $device = 'tablet';
            } elseif (preg_match('/(mobi|iphone|ipod|blackberry|opera mini|iemobile|mobile)/i', $ua)) {
                $device = 'mobile';
            }

            PlatformTelemetry::create([
                'user_id' => $user?->id,
                'user_role' => $userRole,
                'method' => 'BEACON',
                'url' => substr($data['url'] ?? $request->fullUrl(), 0, 500),
                'route' => 'api.telemetry.event',
                'status_code' => 200,
                'duration_ms' => isset($data['duration_ms']) ? (int) $data['duration_ms'] : 0,
                'db_query_count' => 0,
                'db_duration_ms' => 0,
                'memory_mb' => 0,
                'ip_address' => substr((string) ($request->ip() ?? '127.0.0.1'), 0, 45),
                'device_type' => $device,
                'browser' => 'Client-Beacon',
                'os' => 'Client',
                'action_type' => 'beacon',
                'event_name' => $data['event_name'],
                'user_agent' => substr($ua, 0, 500),
                'referer' => substr((string) $request->header('referer', ''), 0, 500) ?: null,
                'error_message' => $data['error_message'] ?? null,
                'metadata' => $data['metadata'] ?? null,
                'created_at' => now(),
            ]);

            return response()->json(['status' => 'recorded'], 201);
        } catch (Throwable $e) {
            return response()->json(['status' => 'ignored', 'error' => $e->getMessage()], 200);
        }
    }
}
