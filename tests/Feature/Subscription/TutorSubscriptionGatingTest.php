<?php

declare(strict_types=1);

namespace Tests\Feature\Subscription;

use App\Domain\Subscription\Enums\InvoiceStatus;
use App\Domain\Subscription\Enums\SubscriptionPlan;
use App\Domain\Subscription\Enums\SubscriptionStatus;
use App\Domain\Subscription\Models\Subscription;
use App\Domain\Subscription\Models\SubscriptionInvoice;
use App\Enums\UserRole;
use App\Models\PromoCode;
use App\Models\TutorProfile;
use App\Models\User;
use App\Services\Payment\AlfaBankPaymentGateway;
use App\Services\Payment\PaymentGatewayInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class TutorSubscriptionGatingTest extends TestCase
{
    use RefreshDatabase;

    public function test_tutor_without_active_subscription_is_blocked_from_cabinet_and_mobile(): void
    {
        $tutor = User::factory()->create([
            'role' => UserRole::Tutor,
        ]);
        TutorProfile::create([
            'user_id' => $tutor->id,
        ]);

        Subscription::create([
            'tutor_id' => $tutor->id,
            'plan' => SubscriptionPlan::PRO,
            'status' => SubscriptionStatus::EXPIRED,
            'is_onboarded' => false,
        ]);

        $responseCabinet = $this->actingAs($tutor)->get('/cabinet');
        $responseCabinet->assertRedirect(route('filament.admin.pages.tutor-subscription-page'));

        $responseMobile = $this->actingAs($tutor)->get('/m');
        $responseMobile->assertRedirect(route('filament.admin.pages.tutor-subscription-page'));
    }

    public function test_tutor_without_active_subscription_is_blocked_from_mobile_api(): void
    {
        $tutor = User::factory()->create([
            'role' => UserRole::Tutor,
        ]);
        TutorProfile::create([
            'user_id' => $tutor->id,
        ]);

        Subscription::create([
            'tutor_id' => $tutor->id,
            'plan' => SubscriptionPlan::PRO,
            'status' => SubscriptionStatus::EXPIRED,
            'is_onboarded' => false,
        ]);

        $responseApi = $this->actingAs($tutor)->getJson('/api/mobile/lessons');
        $responseApi->assertStatus(403);
        $responseApi->assertJsonStructure(['error', 'redirect', 'redirect_url']);
    }

    public function test_checkout_subscription_returns_payment_url_and_does_not_activate_immediately(): void
    {
        $tutor = User::factory()->create([
            'role' => UserRole::Tutor,
            'email' => 'tutor@test.by',
        ]);
        TutorProfile::create([
            'user_id' => $tutor->id,
        ]);

        $sub = Subscription::create([
            'tutor_id' => $tutor->id,
            'plan' => SubscriptionPlan::START,
            'status' => SubscriptionStatus::EXPIRED,
            'is_onboarded' => false,
        ]);

        $mockGateway = Mockery::mock(AlfaBankPaymentGateway::class);
        $mockGateway->shouldReceive('createPayment')
            ->once()
            ->andReturn([
                'success' => true,
                'redirect_url' => 'https://alfa.rbsvat.com/payment/merchants/test/payment_ru.html?mdOrder=alfa-order-12345',
                'orderId' => 'alfa-order-12345',
            ]);
        $this->app->instance(PaymentGatewayInterface::class, $mockGateway);

        $response = $this->actingAs($tutor)->postJson('/api/mobile/subscription/checkout', [
            'plan' => 'pro',
            'period' => 'month',
        ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'redirect_url' => 'https://alfa.rbsvat.com/payment/merchants/test/payment_ru.html?mdOrder=alfa-order-12345',
        ]);

        // Verify subscription is STILL expired (NOT prematurely activated)
        $sub->refresh();
        $this->assertEquals(SubscriptionStatus::EXPIRED, $sub->status);
        $this->assertFalse((bool) $sub->is_onboarded);
    }

    public function test_applying_valid_promo_code_activates_subscription_and_unlocks_cabinet(): void
    {
        $tutor = User::factory()->create([
            'role' => UserRole::Tutor,
        ]);
        TutorProfile::create([
            'user_id' => $tutor->id,
        ]);

        $sub = Subscription::create([
            'tutor_id' => $tutor->id,
            'plan' => SubscriptionPlan::START,
            'status' => SubscriptionStatus::EXPIRED,
            'is_onboarded' => false,
        ]);

        PromoCode::create([
            'code' => 'TUTORFREE2026',
            'discount_type' => 'free_period',
            'subscription_period' => '3_months',
            'subscription_plan' => 'pro',
            'scope' => 'subscriptions',
            'is_active' => true,
            'starts_at' => now()->subDay(),
            'expires_at' => now()->addYear(),
        ]);

        $applyRes = $this->actingAs($tutor)->postJson('/api/mobile/promocode/apply', [
            'code' => 'TUTORFREE2026',
            'plan' => 'pro',
        ]);

        $applyRes->assertOk();
        $applyRes->assertJson([
            'success' => true,
        ]);

        $sub->refresh();
        $this->assertEquals(SubscriptionStatus::ACTIVE, $sub->status);
        $this->assertTrue((bool) $sub->is_onboarded);

        // Applying again (duplicate click / retry) should return success idempotently
        $applyAgainRes = $this->actingAs($tutor)->postJson('/api/mobile/promocode/apply', [
            'code' => 'TUTORFREE2026',
            'plan' => 'pro',
        ]);
        $applyAgainRes->assertOk();
        $applyAgainRes->assertJson([
            'success' => true,
            'is_activated' => true,
        ]);

        // Now cabinet and mobile API are unlocked
        $responseCabinet = $this->actingAs($tutor)->get('/cabinet');
        $responseCabinet->assertOk();

        $responseApi = $this->actingAs($tutor)->getJson('/api/mobile/lessons');
        $responseApi->assertOk();
    }

    public function test_returning_from_alfabank_with_paid_status_activates_subscription_and_unlocks_cabinet(): void
    {
        $tutor = User::factory()->create([
            'role' => UserRole::Tutor,
        ]);
        TutorProfile::create([
            'user_id' => $tutor->id,
        ]);

        $sub = Subscription::create([
            'tutor_id' => $tutor->id,
            'plan' => SubscriptionPlan::PRO,
            'status' => SubscriptionStatus::EXPIRED,
            'is_onboarded' => false,
        ]);

        $invoice = SubscriptionInvoice::create([
            'subscription_id' => $sub->id,
            'tutor_id' => $tutor->id,
            'invoice_number' => 'INV-TEST-777',
            'plan' => SubscriptionPlan::PRO,
            'amount_kopecks' => 6900,
            'currency' => 'BYN',
            'status' => InvoiceStatus::PENDING,
            'due_date' => now()->addDays(2),
            'alfa_order_id' => 'alfa-success-999',
        ]);

        $mockGateway = Mockery::mock(AlfaBankPaymentGateway::class);
        $mockGateway->shouldReceive('getPaymentStatusDetails')
            ->with('alfa-success-999')
            ->once()
            ->andReturn([
                'success' => true,
                'isPaid' => true,
                'orderStatus' => 2,
                'actionCode' => 0,
            ]);
        $this->app->instance(AlfaBankPaymentGateway::class, $mockGateway);

        $response = $this->actingAs($tutor)->get('/cabinet?payment=success&orderId=alfa-success-999');

        $response->assertOk();

        $sub->refresh();
        $this->assertEquals(SubscriptionStatus::ACTIVE, $sub->status);
        $this->assertTrue((bool) $sub->is_onboarded);

        $invoice->refresh();
        $this->assertEquals(InvoiceStatus::PAID, $invoice->status);
    }

    public function test_full_audit_registration_onboarding_payment_block_and_promo_activation(): void
    {
        // 1. Registration of a new tutor
        $registerRes = $this->postJson('/api/auth/register', [
            'firstName' => 'Иван',
            'lastName' => 'Тестов',
            'email' => 'ivan.testov@example.com',
            'password' => 'SecurePass123!',
            'role' => 'tutor',
            'plan' => 'pro',
        ]);

        $registerRes->assertOk();
        $registerRes->assertJson([
            'success' => true,
            'isTutor' => true,
            'role' => 'tutor',
        ]);

        $user = User::where('email', 'ivan.testov@example.com')->first();
        $this->assertNotNull($user);
        $this->assertTrue($user->isTutor());

        // Verify initial subscription is created as EXPIRED and NOT onboarded
        $sub = $user->subscription;
        $this->assertNotNull($sub);
        $this->assertEquals(SubscriptionStatus::EXPIRED, $sub->status);
        $this->assertFalse((bool) $sub->is_onboarded);
        $this->assertFalse($sub->isActive());

        // 2. Complete onboarding without a promo code
        $onboardingRes = $this->actingAs($user)->postJson('/api/tutor/onboarding/complete', [
            'plan' => 'pro',
        ]);

        $onboardingRes->assertOk();
        $sub->refresh();
        $this->assertTrue((bool) $sub->is_onboarded);
        // Subscription must STILL be EXPIRED because no payment or promo code has occurred!
        $this->assertEquals(SubscriptionStatus::EXPIRED, $sub->status);
        $this->assertFalse($sub->isActive());

        // 3. Attempting to access cabinet or API MUST be blocked by paywall
        $cabinetRes = $this->actingAs($user)->get('/cabinet');
        $cabinetRes->assertRedirect(route('filament.admin.pages.tutor-subscription-page'));

        $mobileRes = $this->actingAs($user)->get('/m');
        $mobileRes->assertRedirect(route('filament.admin.pages.tutor-subscription-page'));

        $apiRes = $this->actingAs($user)->getJson('/api/mobile/lessons');
        $apiRes->assertStatus(403);
        $apiRes->assertJson([
            'error' => 'subscription_required',
        ]);

        // 4. Test payment initiation (Checkout): does NOT activate subscription immediately
        $mockGateway = Mockery::mock(AlfaBankPaymentGateway::class);
        $mockGateway->shouldReceive('createPayment')
            ->once()
            ->andReturn([
                'success' => true,
                'orderId' => 'alfa-order-audit-123',
                'redirect_url' => 'https://payment.alfabank.by/pay/order-audit-123',
            ]);
        $this->app->instance(PaymentGatewayInterface::class, $mockGateway);

        $checkoutRes = $this->actingAs($user)->postJson('/api/mobile/subscription/checkout', [
            'plan' => 'pro',
            'period' => 'month',
        ]);

        $checkoutRes->assertOk();
        $checkoutRes->assertJson([
            'success' => true,
            'redirect_url' => 'https://payment.alfabank.by/pay/order-audit-123',
        ]);

        $sub->refresh();
        $this->assertEquals(SubscriptionStatus::EXPIRED, $sub->status);
        $this->assertFalse($sub->isActive());

        // Tutor is still blocked from cabinet
        $blockedCabinetRes = $this->actingAs($user)->get('/cabinet');
        $blockedCabinetRes->assertRedirect(route('filament.admin.pages.tutor-subscription-page'));

        // 5. Instead of paying, the tutor applies a promo code!
        // First test invalid promo code
        $invalidPromoRes = $this->actingAs($user)->postJson('/api/mobile/promocode/apply', [
            'code' => 'NONEXISTENT_CODE',
            'plan' => 'pro',
        ]);
        $invalidPromoRes->assertStatus(422);

        // Now test valid free subscription promo code
        PromoCode::create([
            'code' => 'AUDITPROMO2026',
            'discount_type' => 'free_period',
            'subscription_period' => '3_months',
            'subscription_plan' => 'pro',
            'scope' => 'subscriptions',
            'is_active' => true,
            'starts_at' => now()->subDay(),
            'expires_at' => now()->addYear(),
        ]);

        $applyRes = $this->actingAs($user)->postJson('/api/mobile/promocode/apply', [
            'code' => 'AUDITPROMO2026',
            'plan' => 'pro',
        ]);

        $applyRes->assertOk();
        $applyRes->assertJson([
            'success' => true,
            'is_activated' => true,
        ]);

        $sub->refresh();
        $this->assertEquals(SubscriptionStatus::ACTIVE, $sub->status);
        $this->assertTrue($sub->isActive());
        $this->assertTrue((bool) $sub->is_onboarded);

        // 6. Access to personal cabinet is now UNLOCKED!
        $unlockedCabinetRes = $this->actingAs($user)->get('/cabinet');
        $unlockedCabinetRes->assertOk();

        $unlockedMobileRes = $this->actingAs($user)->get('/m');
        $unlockedMobileRes->assertOk();

        $unlockedApiRes = $this->actingAs($user)->getJson('/api/mobile/lessons');
        $unlockedApiRes->assertOk();
    }
}
