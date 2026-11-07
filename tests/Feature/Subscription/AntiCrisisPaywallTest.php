<?php

declare(strict_types=1);

namespace Tests\Feature\Subscription;

use App\Domain\Subscription\Enums\InvoiceStatus;
use App\Domain\Subscription\Enums\SubscriptionPlan;
use App\Domain\Subscription\Enums\SubscriptionStatus;
use App\Domain\Subscription\Models\Subscription;
use App\Domain\Subscription\Models\SubscriptionInvoice;
use App\Domain\Subscription\Services\SubscriptionFeatureGate;
use App\Domain\Subscription\Services\SubscriptionService;
use App\Enums\UserRole;
use App\Filament\Pages\TutorSubscriptionPage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AntiCrisisPaywallTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_7_day_trial_has_full_access_and_expired_is_blocked(): void
    {
        $gate = app(SubscriptionFeatureGate::class);

        $tutor = User::factory()->create(['role' => UserRole::Tutor]);

        // Active 7-day trial
        $sub = Subscription::create([
            'tutor_id' => $tutor->id,
            'plan' => SubscriptionPlan::PRO,
            'status' => SubscriptionStatus::TRIAL,
            'trial_ends_at' => now()->addDays(7),
            'current_period_ends_at' => now()->addDays(7),
            'payment_token' => null,
            'is_onboarded' => true,
        ]);

        $this->assertTrue($gate->hasActiveSubscription($tutor));
        $this->assertTrue($gate->canAccessClassroom($tutor));
        $this->assertTrue($gate->canUseAiTools($tutor));
        $this->assertTrue($gate->canUseOneClickNpd($tutor));
        $this->assertTrue($gate->canHideProfile($tutor));

        // Expired trial
        $sub->update([
            'trial_ends_at' => now()->subDay(),
            'current_period_ends_at' => now()->subDay(),
            'status' => SubscriptionStatus::EXPIRED,
        ]);

        $this->assertFalse($gate->hasActiveSubscription($tutor));
        $this->assertFalse($gate->canAccessClassroom($tutor));
        $this->assertFalse($gate->canUseAiTools($tutor));
        $this->assertFalse($gate->canUseOneClickNpd($tutor));
        $this->assertFalse($gate->canHideProfile($tutor));
    }

    public function test_card_backed_trial_has_active_access(): void
    {
        $gate = app(SubscriptionFeatureGate::class);

        $tutor = User::factory()->create(['role' => UserRole::Tutor]);

        // Trial with payment_token (card bound / test-drive)
        $sub = Subscription::create([
            'tutor_id' => $tutor->id,
            'plan' => SubscriptionPlan::TEST_DRIVE,
            'status' => SubscriptionStatus::TRIAL,
            'trial_ends_at' => now()->addDays(7),
            'payment_token' => 'tok_alfa_bound_card_123',
            'is_onboarded' => true,
        ]);

        $this->assertTrue($gate->hasActiveSubscription($tutor));
        $this->assertTrue($gate->canAccessClassroom($tutor));
        $this->assertTrue($gate->canUseAiTools($tutor));
        $this->assertTrue($gate->canUseOneClickNpd($tutor));
        $this->assertTrue($gate->canHideProfile($tutor));
    }

    public function test_anti_crisis_tariff_pricing_and_invoice_creation(): void
    {
        $service = app(SubscriptionService::class);
        $tutor = User::factory()->create(['role' => UserRole::Tutor]);

        $sub = Subscription::create([
            'tutor_id' => $tutor->id,
            'plan' => SubscriptionPlan::PRO,
            'status' => SubscriptionStatus::EXPIRED,
            'is_onboarded' => true,
        ]);

        // 1. Test Drive (5 BYN = 500 kopecks)
        $invoiceTestDrive = $service->createInvoice($sub, SubscriptionPlan::TEST_DRIVE, 1);
        $this->assertEquals(500, $invoiceTestDrive->amount_kopecks);
        $this->assertEquals(SubscriptionPlan::TEST_DRIVE, $invoiceTestDrive->plan);

        // 2. Founders Club (49 BYN = 4900 kopecks, 3 months)
        $invoiceFounders = $service->createInvoice($sub, SubscriptionPlan::FOUNDERS_CLUB, 3);
        $this->assertEquals(4900, $invoiceFounders->amount_kopecks);
        $this->assertEquals(3, $invoiceFounders->period_months);
        $this->assertEquals(SubscriptionPlan::FOUNDERS_CLUB, $invoiceFounders->plan);
    }

    public function test_tutor_subscription_page_computes_anticrisis_amounts_and_spots(): void
    {
        $tutor = User::factory()->create(['role' => UserRole::Tutor]);

        $sub = Subscription::create([
            'tutor_id' => $tutor->id,
            'plan' => SubscriptionPlan::PRO,
            'status' => SubscriptionStatus::EXPIRED,
            'is_onboarded' => true,
        ]);

        $this->actingAs($tutor);

        Livewire::test(TutorSubscriptionPage::class)
            ->assertSet('foundersSpotsRemaining', 10)
            ->call('openPaymentModal', 'test_drive')
            ->assertSet('checkoutPlanCode', 'test_drive')
            ->assertSet('checkoutBaseAmount', 5.0)
            ->assertSet('checkoutFinalAmount', 5.0)
            ->assertSee('Тариф «Тест-драйв» (7 дней)')
            ->call('openPaymentModal', 'founders_club')
            ->assertSet('checkoutPlanCode', 'founders_club')
            ->assertSet('checkoutBaseAmount', 49.0)
            ->assertSet('checkoutFinalAmount', 49.0)
            ->assertSee('Клуб основателей')
            ->assertSee('Доступно 10 мест');
    }
}
