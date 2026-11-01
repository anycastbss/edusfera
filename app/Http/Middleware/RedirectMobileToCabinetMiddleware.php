<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Traits\DetectsMobileDevice;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectMobileToCabinetMiddleware
{
    use DetectsMobileDevice;

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Do not redirect if user is in super-admin panel
        if (str_starts_with($request->path(), 'site-admin')) {
            return $next($request);
        }

        // Do not redirect AJAX, Livewire, or file download / print / logout endpoints
        if ($request->ajax() 
            || $request->wantsJson() 
            || $request->is('livewire/*') 
            || $request->is('*/print*') 
            || $request->is('*/certificate*')
            || $request->is('*/logout')
            || $request->is('api/*')
            || $request->is('m')
            || $request->is('m/*')
            || $request->is('cabinet')
            || $request->is('cabinet/*')
        ) {
            return $next($request);
        }

        $isAdminRoute = $request->is('admin') || $request->is('admin/*');
        if (! $isAdminRoute) {
            return $next($request);
        }

        // Emergency / manual override to view Filament
        if ($request->query('filament') === '1') {
            return $next($request);
        }

        $user = $request->user() ?? \Filament\Facades\Filament::auth()->user();

        // Tech Admin keeps classic Filament admin
        if ($user && $user->isAdmin()) {
            return $next($request);
        }

        // Unauthenticated visitor heading to admin panel -> send to login with redirect to cabinet
        if (! $user) {
            return redirect('/login?redirect=%2Fcabinet');
        }

        // All other roles (Tutors, Students, Parents) are routed to modern React cabinet (/cabinet)
        $path = $request->path();
        $tab = null;

        if (str_contains($path, 'messages')) {
            $tab = 'chat';
        } elseif (str_contains($path, 'wallet')) {
            $tab = $user->isTutor() ? 'finance' : 'wallet';
        } elseif (str_contains($path, 'subscription') || str_contains($path, 'npd') || str_contains($path, 'finance')) {
            $tab = 'finance';
        } elseif (str_contains($path, 'homework')) {
            $tab = $user->isTutor() ? 'lessons' : 'homework';
        } elseif (str_contains($path, 'profile')) {
            $tab = 'profile';
        } elseif (str_contains($path, 'ai-copilot') || str_contains($path, 'ai')) {
            $tab = $user->isTutor() ? 'ai_agent' : 'ai_tutor';
        } elseif (str_contains($path, 'lessons') || str_contains($path, 'availability') || str_contains($path, 'schedule') || str_contains($path, 'diagnostic')) {
            $tab = 'lessons';
        }

        $query = $tab ? '?tab=' . $tab : '';

        if ($this->isMobileDevice($request)) {
            return redirect('/m' . $query)
                ->withCookie(cookie('edusfera_device', 'mobile', 525600, null, null, false, false));
        }

        return redirect('/cabinet' . $query);
    }
}
