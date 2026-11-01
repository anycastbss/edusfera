<?php

declare(strict_types=1);

namespace Tests\Feature\Subscription;

use App\Domain\Subscription\Enums\SubscriptionPlan;
use App\Domain\Subscription\Enums\SubscriptionStatus;
use App\Domain\Subscription\Models\Subscription;
use App\Domain\Subscription\Services\SubscriptionFeatureGate;
use App\Domain\Subscription\Services\SubscriptionService;
use App\Enums\UserRole;
use App\Models\Lesson;
use App\Models\TutorProfile;
use App\Models\User;
use App\Notifications\SubscriptionProfilePublicWarningNotification;
use App\Services\WhiteLabelService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class BlockOneTariffLogicTest extends TestCase
{
    use RefreshDatabase;

    public function test_tutor_profile_has_correct_defaults(): void
    {
        $user = User::factory()->create(['role' => UserRole::Tutor]);
        $profile = TutorProfile::create([
            'user_id' => $user->id,
            'price_per_hour' => 50.00,
        ]);

        $this->assertTrue($profile->is_public);
        $this->assertFalse($profile->is_white_label);
        $this->assertFalse($profile->comfort_vision_mode);
    }

    public function test_can_hide_profile_rules(): void
    {
        $gate = app(SubscriptionFeatureGate::class);
        $service = app(SubscriptionService::class);

        // 1. Tutor on trial can hide profile
        $trialTutor = User::factory()->create(['role' => UserRole::Tutor]);
        $service->ensureTrialStarted($trialTutor);
        $this->assertTrue($gate->canHideProfile($trialTutor));

        // 2. Tutor on Basic (20 BYN) cannot hide profile
        $basicTutor = User::factory()->create(['role' => UserRole::Tutor]);
        $service->subscribe($basicTutor, SubscriptionPlan::START, 1);
        $this->assertFalse($gate->canHideProfile($basicTutor));

        // 3. Tutor on Pro (40 BYN) can hide profile
        $proTutor = User::factory()->create(['role' => UserRole::Tutor]);
        $service->subscribe($proTutor, SubscriptionPlan::PRO, 1);
        $this->assertTrue($gate->canHideProfile($proTutor));

        // 4. Tutor on Premium (60 BYN) can hide profile
        $premiumTutor = User::factory()->create(['role' => UserRole::Tutor]);
        $service->subscribe($premiumTutor, SubscriptionPlan::PREMIUM, 1);
        $this->assertTrue($gate->canHideProfile($premiumTutor));
    }

    public function test_cron_auto_resets_hidden_profile_to_public_for_ineligible_tutors_and_notifies(): void
    {
        Notification::fake();
        $service = app(SubscriptionService::class);

        $tutor = User::factory()->create(['role' => UserRole::Tutor]);
        $profile = TutorProfile::create([
            'user_id' => $tutor->id,
            'price_per_hour' => 45.00,
            'is_public' => false,
        ]);

        // Subscription expiring in 2 days -> should send warning notification
        $sub = Subscription::create([
            'tutor_id' => $tutor->id,
            'plan' => SubscriptionPlan::PRO,
            'status' => SubscriptionStatus::ACTIVE,
            'period_type' => 'monthly',
            'amount' => 40.00,
            'currency' => 'BYN',
            'current_period_starts_at' => now()->subDays(28),
            'current_period_ends_at' => now()->addDays(2)->startOfHour(),
        ]);

        $service->processDailyBillingCycle();

        Notification::assertSentTo($tutor, SubscriptionProfilePublicWarningNotification::class);
        $this->assertFalse($profile->fresh()->is_public);

        // Now subscription expires and is downgraded to expired/ineligible
        $sub->update([
            'status' => SubscriptionStatus::EXPIRED,
            'current_period_ends_at' => now()->subDay(),
        ]);

        $service->processDailyBillingCycle();

        // Profile must be auto-reset to public
        $this->assertTrue($profile->fresh()->is_public);
    }

    public function test_max_active_students_limit_on_basic(): void
    {
        $gate = app(SubscriptionFeatureGate::class);
        $service = app(SubscriptionService::class);

        $basicTutor = User::factory()->create(['role' => UserRole::Tutor]);
        $service->subscribe($basicTutor, SubscriptionPlan::START, 1);

        $this->assertSame(5, $gate->maxActiveStudents($basicTutor));

        // Add 5 distinct students
        for ($i = 1; $i <= 5; $i++) {
            $student = User::factory()->create(['role' => UserRole::Student]);
            Lesson::create([
                'tutor_id' => $basicTutor->id,
                'student_id' => $student->id,
                'start_time' => now()->addDays($i),
                'end_time' => now()->addDays($i)->addHour(),
                'duration_minutes' => 60,
                'price' => 40.00,
                'platform_commission' => 0.00,
                'net_amount' => 40.00,
                'status' => Lesson::STATUS_CONFIRMED,
                'payment_status' => Lesson::PAYMENT_PAID,
            ]);
            $this->assertTrue($gate->canAddStudent($basicTutor, $student->id));
        }

        // 6th student must be rejected on Basic
        $sixthStudent = User::factory()->create(['role' => UserRole::Student]);
        $this->assertFalse($gate->canAddStudent($basicTutor, $sixthStudent->id));

        // Upgrade to Pro (40 BYN) -> unlimited students
        $service->subscribe($basicTutor, SubscriptionPlan::PRO, 1);
        $this->assertNull($gate->maxActiveStudents($basicTutor));
        $this->assertTrue($gate->canAddStudent($basicTutor, $sixthStudent->id));
    }

    public function test_white_label_service_and_header_hiding(): void
    {
        $whiteLabelService = app(WhiteLabelService::class);
        $gate = app(SubscriptionFeatureGate::class);
        $service = app(SubscriptionService::class);

        $premiumTutor = User::factory()->create(['role' => UserRole::Tutor]);
        $service->subscribe($premiumTutor, SubscriptionPlan::PREMIUM, 1);
        TutorProfile::create([
            'user_id' => $premiumTutor->id,
            'price_per_hour' => 50.00,
            'is_white_label' => true,
            'is_public' => false,
        ]);

        $this->assertTrue($gate->canUseWhiteLabel($premiumTutor));

        $whiteLabelService->setSessionWhiteLabelTutor($premiumTutor->id);
        $this->assertTrue($whiteLabelService->isWhiteLabelActive());
        $this->assertSame($premiumTutor->id, $whiteLabelService->getActiveWhiteLabelTutor()?->id);
    }

    public function test_can_use_one_click_npd(): void
    {
        $gate = app(SubscriptionFeatureGate::class);
        $service = app(SubscriptionService::class);

        $basicTutor = User::factory()->create(['role' => UserRole::Tutor]);
        $service->subscribe($basicTutor, SubscriptionPlan::START, 1);
        $this->assertFalse($gate->canUseOneClickNpd($basicTutor));

        $proTutor = User::factory()->create(['role' => UserRole::Tutor]);
        $service->subscribe($proTutor, SubscriptionPlan::PRO, 1);
        $this->assertTrue($gate->canUseOneClickNpd($proTutor));

        $premiumTutor = User::factory()->create(['role' => UserRole::Tutor]);
        $service->subscribe($premiumTutor, SubscriptionPlan::PREMIUM, 1);
        $this->assertTrue($gate->canUseOneClickNpd($premiumTutor));
    }

    public function test_max_lesson_duration_minutes(): void
    {
        $gate = app(SubscriptionFeatureGate::class);
        $service = app(SubscriptionService::class);

        $basicTutor = User::factory()->create(['role' => UserRole::Tutor]);
        $service->subscribe($basicTutor, SubscriptionPlan::START, 1);
        $this->assertSame(60, $gate->maxLessonDurationMinutes($basicTutor));

        $proTutor = User::factory()->create(['role' => UserRole::Tutor]);
        $service->subscribe($proTutor, SubscriptionPlan::PRO, 1);
        $this->assertNull($gate->maxLessonDurationMinutes($proTutor));

        $premiumTutor = User::factory()->create(['role' => UserRole::Tutor]);
        $service->subscribe($premiumTutor, SubscriptionPlan::PREMIUM, 1);
        $this->assertNull($gate->maxLessonDurationMinutes($premiumTutor));
    }
}
