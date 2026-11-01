<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domain\Subscription\Enums\SubscriptionPlan;
use App\Domain\Subscription\Enums\SubscriptionStatus;
use App\Domain\Subscription\Models\Subscription;
use App\Filament\Widgets\TutorOnboardingWidget;
use App\Models\PromoCode;
use App\Models\TutorAvailability;
use App\Models\TutorProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TutorOnboardingWidgetTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_cannot_view_tutor_onboarding_widget(): void
    {
        $student = User::factory()->create([
            'role' => 'student',
        ]);

        $this->actingAs($student);
        $this->assertFalse(TutorOnboardingWidget::canView());
    }

    public function test_new_tutor_sees_modal_and_completes_3_step_onboarding(): void
    {
        $tutor = User::factory()->create([
            'role' => 'tutor',
            'name' => 'Елена Репетитор',
        ]);

        $this->actingAs($tutor);
        $this->assertTrue(TutorOnboardingWidget::canView());

        $component = Livewire::actingAs($tutor)
            ->test(TutorOnboardingWidget::class)
            ->assertSet('showModal', true)
            ->assertSet('step', 1)
            ->assertSet('selectedSubjects', []) // Initial subjects are empty by default
            // Step 1: select subjects, set price, save
            ->call('toggleSubject', 'Белорусский язык')
            ->call('toggleSubject', 'Физика')
            ->call('setQuickPrice', '55')
            ->call('toggleSpecialization', 'ЦТ / ЦЭ 2026')
            ->call('saveStep1')
            ->assertSet('step', 2);

        // Verify profile was created in DB
        $profile = TutorProfile::where('user_id', $tutor->id)->first();
        $this->assertNotNull($profile);
        $this->assertEquals(55.00, (float) $profile->price_per_hour);
        $this->assertContains('Белорусский язык', $profile->subjects);
        $this->assertContains('Физика', $profile->subjects);

        // Step 2: apply preset, save schedule
        $component->call('applyPreset', 'evenings')
            ->assertSet('startTime', '16:00')
            ->assertSet('endTime', '21:00')
            ->call('saveStep2')
            ->assertSet('step', 3);

        // Verify availability in DB
        $availCount = TutorAvailability::where('user_id', $tutor->id)->where('is_active', true)->count();
        $this->assertEquals(5, $availCount);

        // Step 3: finish onboarding -> Step modal disappears, Tariff modal appears
        $component->call('finishOnboarding')
            ->assertSet('isCompleted', true)
            ->assertSet('showModal', false)
            ->assertSet('showTariffModal', true)
            ->assertSet('progress', 100);

        // Verify onboarding_completed_at is set and profile sent to moderation (pending, NOT verified yet)
        $profile->refresh();
        $this->assertNotNull($profile->onboarding_completed_at);
        $this->assertFalse($profile->is_verified);
        $this->assertEquals('pending', $profile->verification_status);

        // Tariff modal interactions: toggle billing cycle, select standard, select pro, activate
        $component->call('toggleBillingCycle')
            ->assertSet('isYearly', true)
            ->call('selectPlan', 'standard')
            ->assertSet('selectedPlan', 'standard')
            ->call('selectPlan', 'pro')
            ->assertSet('selectedPlan', 'pro')
            ->call('activateSubscription', 'pro')
            ->assertSet('showTariffModal', false);

        // Verify subscription record was created with 14-day trial
        $subscription = Subscription::where('tutor_id', $tutor->id)->first();
        $this->assertNotNull($subscription);
        $this->assertEquals(SubscriptionPlan::PRO, $subscription->plan);
        $this->assertEquals(SubscriptionStatus::TRIAL, $subscription->status);
        $this->assertNotNull($subscription->trial_ends_at);
    }

    public function test_tutor_can_activate_subscription_via_promo_code_without_trial(): void
    {
        $tutor = User::factory()->create([
            'role' => 'tutor',
            'name' => 'Андрей Репетитор',
        ]);

        $promo = PromoCode::updateOrCreate(
            ['code' => 'EDUSFERA2026'],
            [
                'description' => '3 месяца бесплатно Pro',
                'discount_type' => 'free_period',
                'discount_value' => 0.00,
                'scope' => 'all',
                'subscription_period' => '3_months',
                'subscription_plan' => 'pro',
                'is_active' => true,
                'max_uses' => null,
                'max_uses_per_user' => 1,
            ]
        );

        Livewire::actingAs($tutor)
            ->test(TutorOnboardingWidget::class)
            ->set('showTariffModal', true)
            ->set('promoCode', 'EDUSFERA2026')
            ->call('applyPromoCode')
            ->assertSet('showTariffModal', false)
            ->assertSet('showModal', false)
            ->assertSet('isCompleted', true);

        // Verify subscription was created directly as ACTIVE and WITHOUT 14-day trial!
        $sub = Subscription::where('tutor_id', $tutor->id)->first();
        $this->assertNotNull($sub);
        $this->assertEquals(SubscriptionPlan::PRO, $sub->plan);
        $this->assertEquals(SubscriptionStatus::ACTIVE, $sub->status);
        $this->assertNull($sub->trial_ends_at, 'Trial MUST be null when promo code is applied');
        $this->assertTrue($sub->current_period_ends_at->isFuture());
    }

    public function test_tutor_can_dismiss_modal_and_reopen_from_banner(): void
    {
        $tutor = User::factory()->create([
            'role' => 'tutor',
        ]);

        Livewire::actingAs($tutor)
            ->test(TutorOnboardingWidget::class)
            ->assertSet('showModal', true)
            ->call('dismissModal')
            ->assertSet('showModal', false)
            ->call('openModal', 2)
            ->assertSet('showModal', true)
            ->assertSet('step', 2);
    }
}
