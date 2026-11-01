<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Domain\Subscription\Enums\SubscriptionPlan;
use App\Domain\Subscription\Models\Subscription;
use App\Enums\UserRole;
use App\Models\PromoCode;
use App\Models\TutorAvailability;
use App\Models\TutorProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TutorRegistrationOnboardingTest extends TestCase
{
    use RefreshDatabase;

    public function test_tutor_registration_only_requires_basic_credentials_and_redirects_to_onboarding(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'firstName' => 'Александр',
            'lastName' => 'Иванов',
            'email' => 'alex.tutor@edusfera.by',
            'phone' => '+375 29 111-22-33',
            'password' => 'Password123!',
            'role' => 'tutor',
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'role' => 'tutor',
                'isTutor' => true,
                'redirect' => '/onboarding',
            ]);

        $user = User::where('email', 'alex.tutor@edusfera.by')->firstOrFail();
        $this->assertEquals('Александр Иванов', $user->name);
        $this->assertEquals(UserRole::Tutor, $user->role);
        $this->assertEquals('+375291112233', $user->phone);

        // Tutor profile exists
        $profile = TutorProfile::where('user_id', $user->id)->first();
        $this->assertNotNull($profile);

        // Subscription trial started, pending onboarding
        $sub = Subscription::where('tutor_id', $user->id)->first();
        $this->assertNotNull($sub);
        $this->assertFalse((bool) $sub->is_onboarded);

        $this->assertAuthenticatedAs($user);
    }

    public function test_tutor_onboarding_state_api_returns_expected_data(): void
    {
        $tutor = User::factory()->create([
            'role' => UserRole::Tutor,
            'name' => 'Елена Репетитор',
        ]);
        $profile = TutorProfile::create([
            'user_id' => $tutor->id,
            'subjects' => ['Математика', 'Физика'],
            'price_per_hour' => 50.00,
        ]);

        $response = $this->actingAs($tutor)->getJson('/api/tutor/onboarding/state');

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'user' => [
                    'id' => $tutor->id,
                    'name' => 'Елена Репетитор',
                ],
                'profile' => [
                    'price_per_hour' => 50,
                ],
            ]);

        $data = $response->json();
        $this->assertContains('Математика', $data['profile']['subjects']);
        $this->assertArrayHasKey('catalog', $data);
        $this->assertArrayHasKey('plans', $data);
    }

    public function test_tutor_onboarding_photo_upload_saves_avatar(): void
    {
        Storage::fake('public');

        $tutor = User::factory()->create(['role' => UserRole::Tutor]);
        $profile = TutorProfile::create(['user_id' => $tutor->id]);

        $file = UploadedFile::fake()->image('avatar.jpg', 400, 400);

        $response = $this->actingAs($tutor)->postJson('/api/tutor/onboarding/upload-photo', [
            'photo' => $file,
        ]);

        $response->assertOk()
            ->assertJson(['success' => true]);

        $profile->refresh();
        $tutor->refresh();

        $this->assertNotEmpty($profile->avatar_path);
        $this->assertEquals($profile->avatar_path, $tutor->avatar);
        Storage::disk('public')->assertExists($profile->avatar_path);
    }

    public function test_tutor_onboarding_save_steps_persists_subjects_price_and_schedule(): void
    {
        $tutor = User::factory()->create(['role' => UserRole::Tutor]);
        TutorProfile::create(['user_id' => $tutor->id]);

        // Step 1: Subjects & Price
        $res1 = $this->actingAs($tutor)->postJson('/api/tutor/onboarding/save-step1', [
            'subjects' => ['Математика', 'Информатика'],
            'price_per_hour' => 45,
            'experience_years' => 5,
            'exam_specializations' => ['ЦТ/ЦЭ 2026'],
            'bio' => 'Преподаю математику более 5 лет.',
        ]);
        $res1->assertOk()->assertJson(['success' => true]);

        $profile = TutorProfile::where('user_id', $tutor->id)->firstOrFail();
        $this->assertEquals(['Математика', 'Информатика'], $profile->subjects);
        $this->assertEquals(45.0, (float) $profile->price_per_hour);
        $this->assertEquals(5, $profile->experience_years);

        // Step 2: Schedule
        $res2 = $this->actingAs($tutor)->postJson('/api/tutor/onboarding/save-step2', [
            'availability_days' => [1, 3, 5],
            'start_time' => '16:00',
            'end_time' => '21:00',
        ]);
        $res2->assertOk()->assertJson(['success' => true]);

        $activeCount = TutorAvailability::where('user_id', $tutor->id)->where('is_active', true)->count();
        $this->assertEquals(3, $activeCount);

        $mon = TutorAvailability::where('user_id', $tutor->id)->where('day_of_week', 1)->firstOrFail();
        $this->assertTrue($mon->is_active);
        $this->assertStringStartsWith('16:00', $mon->start_time);
    }

    public function test_tutor_onboarding_complete_with_promo_submits_for_moderation_and_activates(): void
    {
        $tutor = User::factory()->create(['role' => UserRole::Tutor]);
        $profile = TutorProfile::create([
            'user_id' => $tutor->id,
            'subjects' => ['Математика'],
            'price_per_hour' => 45,
        ]);

        $promo = PromoCode::create([
            'code' => 'EDUSFERA_TEST_PROMO',
            'discount_type' => 'free_period',
            'discount_value' => 100,
            'subscription_period' => '3_months',
            'subscription_plan' => 'pro',
            'is_active' => true,
        ]);

        // 1. Check promo
        $checkRes = $this->actingAs($tutor)->postJson('/api/tutor/onboarding/check-promo', [
            'code' => 'EDUSFERA_TEST_PROMO',
        ]);
        $checkRes->assertOk()
            ->assertJson([
                'success' => true,
                'code' => 'EDUSFERA_TEST_PROMO',
                'is_free' => true,
            ]);

        // 2. Complete onboarding
        $completeRes = $this->actingAs($tutor)->postJson('/api/tutor/onboarding/complete', [
            'plan' => 'pro',
            'promo_code' => 'EDUSFERA_TEST_PROMO',
            'is_yearly' => false,
        ]);

        $completeRes->assertOk()
            ->assertJson([
                'success' => true,
                'redirect' => '/admin',
            ]);

        $profile->refresh();
        $this->assertEquals('pending', $profile->verification_status);
        $this->assertFalse($profile->is_verified);
        $this->assertNotNull($profile->onboarding_completed_at);
        $this->assertNotNull($profile->verification_submitted_at);

        $sub = Subscription::where('tutor_id', $tutor->id)->firstOrFail();
        $this->assertTrue((bool) $sub->is_onboarded);
        $this->assertTrue($sub->isActive());
        $this->assertEquals(\App\Domain\Subscription\Enums\SubscriptionStatus::ACTIVE, $sub->status);
        $this->assertDatabaseHas('subscription_invoices', [
            'tutor_id' => $tutor->id,
            'payment_method' => 'promo',
        ]);
    }
}
