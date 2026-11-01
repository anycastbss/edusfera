<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTutorHasActiveSubscription
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // 1. Only enforce paywall for tutors
        if (! $user || ! $user->isTutor()) {
            return $next($request);
        }

        // 2. Allow subscription page, logout, and payment routes
        if ($this->isAllowedRoute($request)) {
            return $next($request);
        }

        // 2.1 Verify returning bank payment if orderId is present
        if ($request->has('orderId') || $request->has('mdOrder')) {
            $orderId = (string) ($request->query('orderId') ?: $request->query('mdOrder'));
            try {
                $gateway = app(\App\Services\Payment\AlfaBankPaymentGateway::class);
                $statusDetails = $gateway->getPaymentStatusDetails($orderId);
                if ($statusDetails['success']) {
                    $sub = $user->subscription;
                    if (! $sub) {
                        $sub = \App\Domain\Subscription\Models\Subscription::create([
                            'tutor_id' => $user->id,
                            'plan' => \App\Domain\Subscription\Enums\SubscriptionPlan::PRO,
                            'status' => \App\Domain\Subscription\Enums\SubscriptionStatus::EXPIRED,
                            'current_period_starts_at' => now(),
                            'current_period_ends_at' => now(),
                        ]);
                    }
                    $subService = app(\App\Domain\Subscription\Services\SubscriptionService::class);
                    $invoice = \App\Domain\Subscription\Models\SubscriptionInvoice::where('subscription_id', $sub->id)
                        ->whereIn('status', [\App\Domain\Subscription\Enums\InvoiceStatus::PENDING, 'pending'])
                        ->latest()
                        ->first();
                    if (! $invoice) {
                        $invoice = $subService->createInvoice($sub, $sub->plan, 1);
                    }
                    $subService->recordPayment($invoice, 'card', [
                        'alfa_order_id' => $orderId,
                        'bank' => 'Альфа-Банк (Alfa-Bank)',
                        'verified_at' => now()->toIso8601String(),
                    ]);
                    $sub->refresh();
                    $sub->update(['is_onboarded' => true]);
                    $user->load('subscription');
                }
            } catch (\Throwable $e) {
                \Log::warning('[EnsureTutorHasActiveSubscription] Payment check error: ' . $e->getMessage());
            }
        }

        // 3. Verify active subscription and onboarding completion
        $subscription = $user->subscription;
        $isOperational = $subscription !== null && $subscription->isActive();
        $isOnboarded = $subscription !== null && (bool) $subscription->is_onboarded;

        if (! $isOperational) {
            if ($request->hasSession()) {
                session()->flash('subscription_required', true);
            }

            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'subscription_required',
                    'message' => 'Для доступа к личному кабинету требуется активная подписка. Оплатите тариф или активируйте промокод.',
                    'redirect' => route('filament.admin.pages.tutor-subscription-page'),
                    'redirect_url' => route('filament.admin.pages.tutor-subscription-page'),
                ], 403);
            }

            return redirect()->route('filament.admin.pages.tutor-subscription-page');
        }

        if (! $isOnboarded) {
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'onboarding_required',
                    'message' => 'Пожалуйста, заполните анкету репетитора.',
                    'redirect' => route('tutor.onboarding'),
                    'redirect_url' => route('tutor.onboarding'),
                ], 403);
            }

            return redirect()->route('tutor.onboarding');
        }

        return $next($request);
    }

    protected function isAllowedRoute(Request $request): bool
    {
        // Check route name
        $routeName = (string) $request->route()?->getName();
        if (
            $routeName === 'tutor.onboarding'
            || $routeName === 'filament.admin.pages.tutor-subscription-page'
            || $routeName === 'filament.admin.auth.logout'
            || $routeName === 'logout'
            || str_starts_with($routeName, 'alfabank.')
            || str_starts_with($routeName, 'payments.')
        ) {
            return true;
        }

        // Check path
        $path = trim($request->path(), '/');
        if (
            $path === 'onboarding'
            || $path === 'admin/tutor-subscription-page'
            || $path === 'admin/logout'
            || $path === 'logout'
            || str_starts_with($path, 'livewire/')
            || str_starts_with($path, 'payments/alfabank')
            || str_starts_with($path, 'api/payments')
            || str_starts_with($path, 'api/subscription')
            || str_starts_with($path, 'api/mobile/promocode/apply')
            || str_starts_with($path, 'api/mobile/subscription')
            || str_starts_with($path, 'api/tutor/onboarding')
        ) {
            return true;
        }

        // Check Livewire requests on tutor-subscription-page
        if ($request->hasHeader('X-Livewire')) {
            $fingerprint = $request->input('fingerprint');
            $componentName = is_array($fingerprint) ? ($fingerprint['name'] ?? '') : '';
            if (
                str_contains((string) $componentName, 'tutor-subscription-page')
                || str_contains((string) $componentName, 'TutorSubscriptionPage')
            ) {
                return true;
            }

            $referer = (string) $request->header('referer');
            if (str_contains($referer, 'admin/tutor-subscription-page')) {
                return true;
            }
        }

        return false;
    }
}
