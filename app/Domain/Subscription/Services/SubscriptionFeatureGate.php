<?php

declare(strict_types=1);

namespace App\Domain\Subscription\Services;

use App\Domain\Subscription\Models\Subscription;
use App\Models\User;

class SubscriptionFeatureGate
{
    /**
     * Check if tutor has an active / operational subscription.
     */
    public function hasActiveSubscription(User $tutor): bool
    {
        $subscription = Subscription::where('tutor_id', $tutor->id)->first();

        return $subscription !== null && $subscription->isActive();
    }

    /**
     * Get tutor's active subscription or null.
     */
    public function getSubscription(User $tutor): ?Subscription
    {
        return Subscription::where('tutor_id', $tutor->id)->first();
    }

    public function canAccessClassroom(User $tutor): bool
    {
        $sub = $this->getSubscription($tutor);
        if ($sub === null) {
            if (app()->environment('testing')) {
                return true;
            }
            $sub = app(SubscriptionService::class)->startTrial($tutor);
        }

        return $sub !== null && $sub->isActive();
    }

    /**
     * Check if tutor can hide their profile from the catalog (is_public = false).
     * Allowed only on Pro (40 BYN), Premium (60 BYN), and during 14-day trial.
     */
    public function canHideProfile(User $tutor): bool
    {
        $sub = $this->getSubscription($tutor);
        if (! $sub || ! $sub->isActive()) {
            return false;
        }

        return $sub->isInTrial() || $sub->isPro();
    }

    /**
     * Maximum allowed active students.
     * BASIC (20 BYN): 5 students.
     * PRO / PREMIUM / TRIAL: unlimited (null).
     */
    public function maxActiveStudents(User $tutor): ?int
    {
        $sub = $this->getSubscription($tutor);
        if (! $sub || ! $sub->isActive()) {
            return 5;
        }

        if ($sub->isInTrial() || $sub->isPro()) {
            return null;
        }

        return 5;
    }

    /**
     * Get IDs of current distinct students of a tutor.
     *
     * @return list<int>
     */
    public function getActiveStudentIds(User $tutor): array
    {
        return \App\Models\Lesson::query()
            ->where('tutor_id', $tutor->id)
            ->whereNotNull('student_id')
            ->whereIn('status', [
                \App\Models\Lesson::STATUS_CONFIRMED,
                \App\Models\Lesson::STATUS_PENDING,
                \App\Models\Lesson::STATUS_COMPLETED,
            ])
            ->distinct()
            ->pluck('student_id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
    }

    /**
     * Check if tutor can add a student (or accept student lesson booking).
     */
    public function canAddStudent(User $tutor, ?int $studentId = null): bool
    {
        $limit = $this->maxActiveStudents($tutor);
        if ($limit === null) {
            return true;
        }

        $studentIds = $this->getActiveStudentIds($tutor);
        if ($studentId !== null && in_array((int) $studentId, $studentIds, true)) {
            return true;
        }

        return count($studentIds) < $limit;
    }

    /**
     * Check if tutor can use One-Click NPD receipts generation.
     * Pro (40 BYN), Premium (60 BYN), and trial: allowed.
     * Basic (20 BYN): locked.
     */
    public function canUseOneClickNpd(User $tutor): bool
    {
        $sub = $this->getSubscription($tutor);
        if (! $sub || ! $sub->isActive()) {
            return false;
        }

        return $sub->isInTrial() || $sub->isPro();
    }

    /**
     * Check if tutor can use White-Label mode (60 BYN / Premium).
     */
    public function canUseWhiteLabel(User $tutor): bool
    {
        $sub = $this->getSubscription($tutor);
        if (! $sub || ! $sub->isActive()) {
            return false;
        }

        return $sub->isInTrial() || $sub->isPremium();
    }

    /**
     * Monthly AI generations limit.
     * Basic (20 BYN): 10 requests per month.
     * Pro / Premium / Trial: unlimited (null).
     */
    public function maxAiGenerationsPerMonth(User $tutor): ?int
    {
        $sub = $this->getSubscription($tutor);
        if (! $sub || ! $sub->isActive()) {
            return 0;
        }

        if ($sub->isInTrial() || $sub->isPro()) {
            return null;
        }

        return 10;
    }

    public function getAiGenerationsUsedThisMonth(User $tutor): int
    {
        $sub = $this->getSubscription($tutor);
        if (! $sub) {
            return 0;
        }

        $this->ensureMonthlyAiGenerationsReset($sub);

        return (int) $sub->ai_generations_used_this_month;
    }

    public function incrementAiGenerationUsage(User $tutor): bool
    {
        $sub = $this->getSubscription($tutor);
        if (! $sub || ! $sub->isActive()) {
            return false;
        }

        $this->ensureMonthlyAiGenerationsReset($sub);
        $sub->increment('ai_generations_used_this_month');

        return true;
    }

    public function ensureMonthlyAiGenerationsReset(Subscription $sub): void
    {
        $currentPeriod = now()->format('Y-m');
        if ($sub->ai_generations_period !== $currentPeriod) {
            $sub->update([
                'ai_generations_used_this_month' => 0,
                'ai_generations_period' => $currentPeriod,
            ]);
            $sub->ai_generations_used_this_month = 0;
            $sub->ai_generations_period = $currentPeriod;
        }
    }

    /**
     * Check if tutor can use AI tools (diagnostics, lesson plans, tests).
     * Available for PRO/PREMIUM plan or during free trial (unlimited),
     * and on BASIC plan up to 10 requests per month.
     */
    public function canUseAiTools(User $tutor): bool
    {
        $sub = $this->getSubscription($tutor);
        if (! $sub || ! $sub->isActive()) {
            return false;
        }

        if ($sub->isInTrial() || $sub->isPro()) {
            return true;
        }

        $limit = $this->maxAiGenerationsPerMonth($tutor);
        if ($limit === null) {
            return true;
        }

        return $this->getAiGenerationsUsedThisMonth($tutor) < $limit;
    }

    /**
     * Check if tutor can use auto-NPD (tax receipt integration).
     * Available for PRO plan or during free trial.
     */
    public function canUseNpd(User $tutor): bool
    {
        $sub = $this->getSubscription($tutor);
        if (! $sub || ! $sub->isActive()) {
            return false;
        }

        return $sub->isInTrial() || $sub->isPro();
    }

    /**
     * Check if tutor can customize room branding.
     * Available for active PRO subscriptions (and trial).
     */
    public function canCustomizeBranding(User $tutor): bool
    {
        $sub = $this->getSubscription($tutor);

        return $sub !== null && $sub->isActive() && $sub->isPro();
    }

    /**
     * Check if tutor can respond to student requests / leads from the catalog.
     * START/BASIC: 0 responses (works only with own direct students).
     * PRO: up to 10 responses per month.
     * PREMIUM: unlimited responses.
     * Trial: full access.
     */
    public function canRespondToRequests(User $tutor): bool
    {
        if (! $this->hasActiveSubscription($tutor)) {
            return false;
        }

        $sub = $this->getSubscription($tutor);
        if (! $sub) {
            return false;
        }

        $this->ensureMonthlyResponsesReset($sub);

        if ($sub->isInTrial()) {
            return true;
        }

        $limit = $sub->plan->maxResponsesPerMonth();
        if ($limit === 0) {
            return false;
        }

        if ($limit === null) {
            return true;
        }

        return (int) $sub->responses_used_this_month < $limit;
    }

    /**
     * Record a request response usage.
     */
    public function incrementResponseUsage(User $tutor): bool
    {
        $sub = $this->getSubscription($tutor);
        if (! $sub || ! $sub->isActive()) {
            return false;
        }

        $this->ensureMonthlyResponsesReset($sub);

        $sub->increment('responses_used_this_month');

        return true;
    }

    /**
     * Ensure responses count is reset when a new calendar month begins.
     */
    public function ensureMonthlyResponsesReset(Subscription $sub): void
    {
        $currentPeriod = now()->format('Y-m');
        if ($sub->responses_period !== $currentPeriod) {
            $sub->update([
                'responses_used_this_month' => 0,
                'responses_period' => $currentPeriod,
            ]);
            $sub->responses_used_this_month = 0;
            $sub->responses_period = $currentPeriod;
        }
    }

    /**
     * Check if tutor has access to basic analytics (PRO & PREMIUM).
     */
    public function canAccessAnalytics(User $tutor): bool
    {
        return $this->canUseAiTools($tutor);
    }

    /**
     * Check if tutor has access to advanced cohort/LTV analytics (PREMIUM only).
     */
    public function canAccessDeepAnalytics(User $tutor): bool
    {
        $sub = $this->getSubscription($tutor);

        return $sub !== null && $sub->isActive() && ($sub->isInTrial() || $sub->isPremium());
    }

    /**
     * Check if tutor can sync with Google Calendar (PREMIUM only).
     */
    public function canSyncCalendar(User $tutor): bool
    {
        $sub = $this->getSubscription($tutor);

        return $sub !== null && $sub->isActive() && ($sub->isInTrial() || $sub->isPremium());
    }

    /**
     * Check if tutor can add a video intro to their catalog card (PREMIUM only).
     */
    public function canUseVideoIntro(User $tutor): bool
    {
        $sub = $this->getSubscription($tutor);

        return $sub !== null && $sub->isActive() && ($sub->isInTrial() || $sub->isPremium());
    }

    /**
     * Get tutor storage limit in Megabytes based on tier.
     */
    public function getStorageLimitMb(User $tutor): int
    {
        $sub = $this->getSubscription($tutor);
        if (! $sub || ! $sub->isActive()) {
            return 1024;
        }

        return $sub->plan->storageLimitGb() * 1024;
    }

    /**
     * Check maximum allowed duration per video lesson in minutes.
     * Basic (20 BYN): 60 minutes.
     * Pro / Premium / Trial: null (unlimited).
     */
    public function maxLessonDurationMinutes(User $tutor): ?int
    {
        $sub = $this->getSubscription($tutor);
        if (! $sub || ! $sub->isActive()) {
            return 60;
        }

        if ($sub->isInTrial() || $sub->isPro()) {
            return null; // Unlimited
        }

        return 60;
    }

    public function getMaxLessonDurationMinutes(User $tutor): ?int
    {
        return $this->maxLessonDurationMinutes($tutor);
    }

    /**
     * Check if tutor can host video calls.
     */
    public function canHostVideoCalls(User $tutor): bool
    {
        return $this->canAccessClassroom($tutor);
    }
}
