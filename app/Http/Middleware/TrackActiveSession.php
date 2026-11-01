<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class TrackActiveSession
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        try {
            if (Auth::check()) {
                $user = Auth::user();
                $now = now()->timestamp;

                // Throttle writes: only update session cache if > 30 seconds passed since last record
                $lastLogged = Cache::get("user_last_tracked_{$user->id}");
                if (! $lastLogged || ($now - (int) $lastLogged) >= 30) {
                    Cache::put("user_last_tracked_{$user->id}", $now, now()->addMinutes(5));

                    $sessionData = [
                        'user_id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'role' => $user->role instanceof \BackedEnum ? $user->role->value : (string) $user->role,
                        'ip' => $request->ip() ?? '127.0.0.1',
                        'user_agent' => substr((string) $request->userAgent(), 0, 255),
                        'path' => '/' . ltrim($request->path(), '/'),
                        'last_activity' => $now,
                    ];

                    Cache::put("user_active_sess_{$user->id}", $sessionData, now()->addMinutes(15));

                    $activeIds = Cache::get('active_user_ids', []);
                    if (! in_array($user->id, $activeIds, true)) {
                        $activeIds[] = $user->id;
                        Cache::put('active_user_ids', array_slice($activeIds, -50), now()->addMinutes(30));
                    }
                }
            }
        } catch (\Throwable $e) {}

        return $response;
    }
}
