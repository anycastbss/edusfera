<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Domain\Subscription\Enums\SubscriptionPlan;
use App\Domain\Subscription\Enums\SubscriptionStatus;
use App\Domain\Subscription\Models\Subscription;
use App\Domain\Subscription\Services\SubscriptionService;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\TutorProfile;
use App\Models\User;
use App\Traits\DetectsMobileDevice;
use Filament\Facades\Filament;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

class SocialAuthController extends Controller
{
    use DetectsMobileDevice;
    private const ALLOWED_PROVIDERS = ['google'];

    /**
     * Redirect user to OAuth provider.
     */
    public function redirect(Request $request, string $provider): RedirectResponse
    {
        if (! in_array($provider, self::ALLOWED_PROVIDERS, true)) {
            abort(404, 'Неподдерживаемый OAuth провайдер.');
        }

        // Store role and plan in session for callback (only if explicitly specified)
        $role = $request->query('role');
        $plan = $request->query('plan', 'pro');

        session([
            'oauth_role' => in_array($role, ['student', 'tutor', 'parent'], true) ? $role : null,
            'oauth_plan' => in_array($plan, ['start', 'pro', 'basic', 'premium'], true) ? $plan : 'pro',
        ]);

        return Socialite::driver($provider)->redirect();
    }

    /**
     * Handle OAuth callback from provider.
     */
    public function callback(Request $request, string $provider): RedirectResponse
    {
        if (! in_array($provider, self::ALLOWED_PROVIDERS, true)) {
            abort(404, 'Неподдерживаемый OAuth провайдер.');
        }

        try {
            $socialUser = Socialite::driver($provider)->user();
        } catch (Throwable $e) {
            Log::warning("OAuth error with {$provider}: ".$e->getMessage());

            return redirect()->route('login')->withErrors([
                'email' => 'Не удалось войти через '.ucfirst($provider).'. Попробуйте снова или используйте e-mail.',
            ]);
        }

        $email = $socialUser->getEmail();
        if (! $email) {
            return redirect()->route('login')->withErrors([
                'email' => 'Сервис '.ucfirst($provider).' не передал ваш e-mail. Авторизация невозможна.',
            ]);
        }

        $normalizedEmail = mb_strtolower(trim($email));
        $providerId = (string) $socialUser->getId();
        $providerIdField = $provider === 'yandex' ? 'yandex_id' : 'google_id';

        // 1. Find user by provider ID or email
        $user = User::where($providerIdField, $providerId)
            ->orWhereRaw('LOWER(email) = ?', [$normalizedEmail])
            ->first();

        if ($user) {
            // Update provider ID and avatar if not set
            $updates = [];
            if (! $user->{$providerIdField}) {
                $updates[$providerIdField] = $providerId;
            }
            if (! $user->avatar && $socialUser->getAvatar()) {
                $updates['avatar'] = $socialUser->getAvatar();
            }
            if (! $user->email_verified_at) {
                $updates['email_verified_at'] = now();
            }
            if (! empty($updates)) {
                $user->update($updates);
            }

            Auth::login($user, true);
            Filament::auth()->login($user, true);
            $request->session()->regenerate();
            $request->session()->put('password_hash_web', $user->getAuthPassword());
            session()->forget(['oauth_role', 'oauth_plan', 'oauth_pending_user']);

            if ($user->isTutor()) {
                $subscription = $user->subscription;
                if (! $subscription || ! $subscription->isActive() || ! $subscription->is_onboarded) {
                    return redirect($this->isMobileDevice($request) ? route('mobile.cabinet') : '/admin/tutor-subscription-page?onboarding=1');
                }
            }

            return redirect($this->isMobileDevice($request) ? route('mobile.cabinet') : '/admin');
        }

        // 2. New User Registration:
        $name = trim((string) ($socialUser->getName() ?? $socialUser->getNickname() ?? 'Пользователь'));
        if ($name === '') {
            $name = 'Пользователь';
        }

        $roleStr = session('oauth_role');

        // If no role was pre-selected (e.g. user clicked Google on /login or generic button):
        // Redirect to role selection page so user explicitly chooses: Tutor or Student!
        if (! $roleStr || ! in_array($roleStr, ['student', 'tutor', 'parent'], true)) {
            session([
                'oauth_pending_user' => [
                    'provider' => $provider,
                    'provider_id' => $providerId,
                    'name' => $name,
                    'email' => $normalizedEmail,
                    'avatar' => $socialUser->getAvatar(),
                    'plan' => session('oauth_plan', 'pro'),
                ],
            ]);

            return redirect()->route('social.select_role');
        }

        $userRole = UserRole::tryFrom($roleStr) ?? UserRole::Student;
        $planStr = (string) session('oauth_plan', 'pro');

        $user = $this->createUserFromOAuth(
            $providerIdField,
            $providerId,
            $name,
            $normalizedEmail,
            $socialUser->getAvatar(),
            $userRole,
            $planStr
        );

        // 3. Login
        Auth::login($user, true);
        Filament::auth()->login($user, true);
        $request->session()->regenerate();
        $request->session()->put('password_hash_web', $user->getAuthPassword());

        // 4. Clean session
        session()->forget(['oauth_role', 'oauth_plan', 'oauth_pending_user']);

        if ($user->isTutor()) {
            return redirect($this->isMobileDevice($request) ? route('mobile.cabinet') : '/admin/tutor-subscription-page?onboarding=1');
        }

        return redirect($this->isMobileDevice($request) ? route('mobile.cabinet') : '/admin');
    }

    /**
     * Show Role Selection screen for pending OAuth user.
     */
    public function showSelectRole(Request $request)
    {
        /** @var User|null $currentUser */
        $currentUser = Auth::user();

        // If already logged in:
        if ($currentUser) {
            // Allow student to switch to tutor if requested (?switch=1)
            if ($request->query('switch') || $request->query('role') === 'tutor') {
                return view('auth.select-role', [
                    'currentUser' => $currentUser,
                    'pending' => [
                        'name' => $currentUser->name,
                        'email' => $currentUser->email,
                        'avatar' => $currentUser->avatar_url,
                    ],
                    'isSwitch' => true,
                ]);
            }

            return redirect($this->isMobileDevice($request) ? route('mobile.cabinet') : '/admin');
        }

        $pending = session('oauth_pending_user');
        if (! $pending || empty($pending['email'])) {
            return redirect()->route('login');
        }

        return view('auth.select-role', [
            'pending' => $pending,
            'isSwitch' => false,
        ]);
    }

    /**
     * Handle submission from Role Selection screen.
     */
    public function processSelectRole(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'role' => ['required', 'string', 'in:student,tutor,parent'],
            'plan' => ['nullable', 'string', 'in:start,pro,basic,premium'],
        ]);

        $selectedRole = UserRole::from($validated['role']);
        $selectedPlan = $validated['plan'] ?? 'pro';

        /** @var User|null $currentUser */
        $currentUser = Auth::user();

        // Handle role upgrade/switch for existing logged-in user
        if ($currentUser) {
            if ($selectedRole === UserRole::Tutor && ! $currentUser->isTutor()) {
                $currentUser->update(['role' => UserRole::Tutor]);
                TutorProfile::firstOrCreate(['user_id' => $currentUser->id]);
                try {
                    $planEnum = match ($selectedPlan) {
                        'start', 'basic' => SubscriptionPlan::START,
                        'premium' => SubscriptionPlan::PREMIUM,
                        default => SubscriptionPlan::PRO,
                    };
                    $sub = app(SubscriptionService::class)->ensureTrialStarted($currentUser, $planEnum);
                    $sub->update(['is_onboarded' => false]);
                } catch (Throwable $e) {
                    Log::error('Failed to create subscription on role switch: '.$e->getMessage());
                }

                return redirect($this->isMobileDevice($request) ? route('mobile.cabinet') : '/admin/tutor-subscription-page?onboarding=1');
            }

            return redirect($this->isMobileDevice($request) ? route('mobile.cabinet') : '/admin');
        }

        $pending = session('oauth_pending_user');
        if (! $pending || empty($pending['email'])) {
            return redirect()->route('login')->withErrors([
                'email' => 'Сессия выбора роли истекла. Пожалуйста, выполните вход через Google снова.',
            ]);
        }

        $providerIdField = ($pending['provider'] ?? 'google') === 'yandex' ? 'yandex_id' : 'google_id';

        // Check if user was created concurrently
        $user = User::where($providerIdField, $pending['provider_id'])
            ->orWhereRaw('LOWER(email) = ?', [mb_strtolower(trim($pending['email']))])
            ->first();

        if (! $user) {
            $user = $this->createUserFromOAuth(
                $providerIdField,
                $pending['provider_id'],
                $pending['name'],
                $pending['email'],
                $pending['avatar'] ?? null,
                $selectedRole,
                $selectedPlan
            );
        } else {
            // Update role if user was pending
            if ($user->role !== $selectedRole) {
                $user->update(['role' => $selectedRole]);
            }
            if ($selectedRole === UserRole::Tutor) {
                TutorProfile::firstOrCreate(['user_id' => $user->id]);
                try {
                    $sub = app(SubscriptionService::class)->ensureTrialStarted($user, SubscriptionPlan::PRO);
                    $sub->update(['is_onboarded' => false]);
                } catch (Throwable $e) {
                    Log::error('Subscription error: '.$e->getMessage());
                }
            }
        }

        Auth::login($user, true);
        Filament::auth()->login($user, true);
        $request->session()->regenerate();
        $request->session()->put('password_hash_web', $user->getAuthPassword());

        session()->forget(['oauth_pending_user', 'oauth_role', 'oauth_plan']);

        if ($user->isTutor()) {
            return redirect($this->isMobileDevice($request) ? route('mobile.cabinet') : '/admin/tutor-subscription-page?onboarding=1');
        }

        return redirect($this->isMobileDevice($request) ? route('mobile.cabinet') : '/admin');
    }

    /**
     * Helper to create a new user from OAuth provider data.
     */
    private function createUserFromOAuth(
        string $providerIdField,
        string $providerId,
        string $name,
        string $normalizedEmail,
        ?string $avatar,
        UserRole $userRole,
        string $planStr
    ): User {
        $user = new User([
            'name' => $name,
            'email' => $normalizedEmail,
            'offer_accepted_at' => now(),
            'email_verified_at' => now(),
        ]);

        $user->{$providerIdField} = $providerId;
        $user->avatar = $avatar;
        $user->role = $userRole;
        $user->is_verified = true;
        $user->save();

        if ($userRole === UserRole::Tutor) {
            TutorProfile::firstOrCreate([
                'user_id' => $user->id,
            ]);

            $plan = match ($planStr) {
                'start', 'basic' => SubscriptionPlan::START,
                'pro' => SubscriptionPlan::PRO,
                'premium' => SubscriptionPlan::PREMIUM,
                default => SubscriptionPlan::PRO,
            };

            try {
                $sub = app(SubscriptionService::class)->ensureTrialStarted($user, $plan);
                $sub->update(['is_onboarded' => false]);
            } catch (Throwable $e) {
                Log::error('Failed to create subscription for OAuth tutor: '.$e->getMessage());
            }
        }

        return $user;
    }
}
