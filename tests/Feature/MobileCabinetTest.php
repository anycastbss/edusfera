<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domain\Subscription\Enums\SubscriptionPlan;
use App\Domain\Subscription\Models\Subscription;
use App\Enums\UserRole;
use App\Models\Conversation;
use App\Models\Lesson;
use App\Models\TutorBalance;
use App\Models\TutorProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MobileCabinetTest extends TestCase
{
    use RefreshDatabase;

    protected function createActiveTutor(array $attributes = []): User
    {
        $tutor = User::factory()->create(array_merge([
            'role' => UserRole::Tutor,
        ], $attributes));

        TutorProfile::firstOrCreate(['user_id' => $tutor->id], [
            'headline' => 'Репетитор Edusfera',
            'price_per_hour' => 35.0,
            'is_public' => true,
            'is_verified' => true,
        ]);

        Subscription::firstOrCreate(['tutor_id' => $tutor->id], [
            'plan' => SubscriptionPlan::PRO,
            'status' => \App\Domain\Subscription\Enums\SubscriptionStatus::ACTIVE,
            'is_onboarded' => true,
            'current_period_starts_at' => now(),
            'current_period_ends_at' => now()->addMonth(),
        ]);

        return $tutor;
    }

    public function test_guest_is_redirected_from_mobile_cabinet(): void
    {
        $response = $this->get('/m');
        $response->assertRedirect('/login');
    }

    public function test_tutor_without_active_subscription_is_blocked_from_cabinet_and_redirected(): void
    {
        $tutor = User::factory()->create(['role' => UserRole::Tutor]);

        // Browser requests redirect to subscription paywall page
        $response = $this->actingAs($tutor)->get('/cabinet');
        $response->assertRedirect(route('filament.admin.pages.tutor-subscription-page'));

        // API calls receive 403 subscription_required
        $apiResponse = $this->actingAs($tutor)->getJson('/api/mobile/lessons');
        $apiResponse->assertStatus(403);
        $apiResponse->assertJson([
            'error' => 'subscription_required',
            'redirect' => route('filament.admin.pages.tutor-subscription-page'),
        ]);

        // Promocode apply is accessible without subscription
        $promoRes = $this->actingAs($tutor)->postJson('/api/mobile/promocode/apply', ['code' => '']);
        $this->assertEquals(422, $promoRes->status()); // validates input, not blocked by 403

        // Checkout is accessible without subscription
        $checkoutRes = $this->actingAs($tutor)->postJson('/api/mobile/subscription/checkout', []);
        $this->assertEquals(422, $checkoutRes->status()); // validates input, not blocked by 403
    }

    public function test_authenticated_tutor_can_access_mobile_cabinet(): void
    {
        $tutor = $this->createActiveTutor();

        $response = $this->actingAs($tutor)->get('/m');

        $response->assertStatus(200);
        $response->assertSee('mobile-cabinet');
        $response->assertSee('EDUSFERA_MOBILE_INITIAL_DATA', false);
    }

    public function test_mobile_lessons_api_returns_weekly_lessons_and_upcoming(): void
    {
        $tutor = $this->createActiveTutor();
        $student = User::factory()->create(['role' => UserRole::Student]);

        $lesson = Lesson::create([
            'tutor_id' => $tutor->id,
            'student_id' => $student->id,
            'start_time' => now()->copy()->addMinutes(15),
            'end_time' => now()->copy()->addMinutes(75),
            'duration_minutes' => 60,
            'price' => 40.0,
            'platform_commission' => 0.0,
            'net_amount' => 40.0,
            'status' => Lesson::STATUS_CONFIRMED,
            'payment_status' => Lesson::PAYMENT_PAID,
        ]);

        $response = $this->actingAs($tutor)->getJson('/api/mobile/lessons');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'week_start',
            'week_end',
            'week_label',
            'lessons',
            'upcoming_lesson',
        ]);

        $data = $response->json();
        $this->assertNotEmpty($data['lessons']);
        $this->assertNotNull($data['upcoming_lesson']);
        $this->assertSame($lesson->id, $data['upcoming_lesson']['id']);
    }

    public function test_mobile_chats_api_returns_conversations(): void
    {
        $tutor = $this->createActiveTutor();
        $student = User::factory()->create(['role' => UserRole::Student]);

        Conversation::create([
            'tutor_id' => $tutor->id,
            'student_id' => $student->id,
            'last_message_text' => 'Здравствуйте! Готов к уроку.',
            'last_message_at' => now(),
        ]);

        $response = $this->actingAs($tutor)->getJson('/api/mobile/chats');

        $response->assertStatus(200);
        $response->assertJsonStructure(['chats']);
        $this->assertCount(1, $response->json('chats'));
    }

    public function test_tutor_can_toggle_catalog_visibility_from_mobile(): void
    {
        $tutor = $this->createActiveTutor();
        $profile = $tutor->tutorProfile;
        $profile->update(['is_public' => true]);

        $response = $this->actingAs($tutor)->postJson('/api/mobile/toggle-catalog');

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'is_active' => false,
        ]);

        $this->assertFalse((bool) $profile->fresh()->is_public);
    }

    public function test_tutor_can_fetch_students_list(): void
    {
        $tutor = $this->createActiveTutor();
        $student = User::factory()->create(['role' => UserRole::Student, 'name' => 'Алексей Иванов']);

        Lesson::create([
            'tutor_id' => $tutor->id,
            'student_id' => $student->id,
            'start_time' => now()->addDay(),
            'end_time' => now()->addDay()->addHour(),
            'duration_minutes' => 60,
            'price' => 35.0,
            'platform_commission' => 0.0,
            'net_amount' => 35.0,
            'status' => Lesson::STATUS_CONFIRMED,
            'payment_status' => Lesson::PAYMENT_PAID,
        ]);

        $response = $this->actingAs($tutor)->getJson('/api/mobile/students');

        $response->assertStatus(200);
        $response->assertJsonStructure(['students']);
        $this->assertTrue(collect($response->json('students'))->contains('id', $student->id));
    }

    public function test_tutor_can_create_lesson_from_mobile(): void
    {
        $tutor = $this->createActiveTutor();
        $student = User::factory()->create(['role' => UserRole::Student, 'name' => 'Мария Сидорова']);

        // Tutor already has active subscription

        $payload = [
            'student_mode' => 'existing',
            'student_id' => $student->id,
            'subject' => 'Математика ЦТ',
            'start_time' => now()->addDays(2)->format('Y-m-d 16:00:00'),
            'duration_minutes' => 60,
            'price' => 40.0,
            'payment_status' => 'unpaid',
            'notes' => 'Подготовка к логарифмическим уравнениям',
        ];

        $response = $this->actingAs($tutor)->postJson('/api/mobile/lessons', $payload);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);

        $this->assertDatabaseHas('lessons', [
            'tutor_id' => $tutor->id,
            'student_id' => $student->id,
            'price' => 40.0,
            'payment_status' => 'unpaid',
        ]);

        $createdLesson = Lesson::where('tutor_id', $tutor->id)->where('student_id', $student->id)->latest()->first();
        $this->assertNotNull($createdLesson);
        $this->assertSame('Математика ЦТ', $createdLesson->subject);
    }

    public function test_tutor_can_mark_lesson_paid_from_mobile(): void
    {
        $tutor = $this->createActiveTutor();
        $student = User::factory()->create(['role' => UserRole::Student]);

        $lesson = Lesson::create([
            'tutor_id' => $tutor->id,
            'student_id' => $student->id,
            'start_time' => now()->addDays(2),
            'end_time' => now()->addDays(2)->addHour(),
            'duration_minutes' => 60,
            'price' => 35.0,
            'platform_commission' => 0.0,
            'net_amount' => 35.0,
            'status' => Lesson::STATUS_PENDING,
            'payment_status' => Lesson::PAYMENT_UNPAID,
        ]);

        $response = $this->actingAs($tutor)->postJson("/api/mobile/lessons/{$lesson->id}/mark-paid");

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'payment_status' => Lesson::PAYMENT_PAID,
            'status' => Lesson::STATUS_CONFIRMED,
        ]);

        $this->assertSame(Lesson::PAYMENT_PAID, $lesson->fresh()->payment_status);
        $this->assertSame(Lesson::STATUS_CONFIRMED, $lesson->fresh()->status);
    }

    public function test_tutor_can_reschedule_lesson_from_mobile(): void
    {
        $tutor = $this->createActiveTutor();
        $student = User::factory()->create(['role' => UserRole::Student]);

        $lesson = Lesson::create([
            'tutor_id' => $tutor->id,
            'student_id' => $student->id,
            'start_time' => now()->addDays(2),
            'end_time' => now()->addDays(2)->addHour(),
            'duration_minutes' => 60,
            'price' => 35.0,
            'platform_commission' => 0.0,
            'net_amount' => 35.0,
            'status' => Lesson::STATUS_CONFIRMED,
            'payment_status' => Lesson::PAYMENT_PAID,
        ]);

        $newStartTime = now()->addDays(4)->format('Y-m-d 18:00:00');

        $response = $this->actingAs($tutor)->postJson("/api/mobile/lessons/{$lesson->id}/reschedule", [
            'start_time' => $newStartTime,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);
    }

    public function test_tutor_can_cancel_lesson_from_mobile(): void
    {
        $tutor = $this->createActiveTutor();
        $student = User::factory()->create(['role' => UserRole::Student]);

        $lesson = Lesson::create([
            'tutor_id' => $tutor->id,
            'student_id' => $student->id,
            'start_time' => now()->addDays(2),
            'end_time' => now()->addDays(2)->addHour(),
            'duration_minutes' => 60,
            'price' => 35.0,
            'platform_commission' => 0.0,
            'net_amount' => 35.0,
            'status' => Lesson::STATUS_CONFIRMED,
            'payment_status' => Lesson::PAYMENT_PAID,
        ]);

        $response = $this->actingAs($tutor)->postJson("/api/mobile/lessons/{$lesson->id}/cancel");

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'status' => Lesson::STATUS_CANCELLED,
        ]);

        $this->assertSame(Lesson::STATUS_CANCELLED, $lesson->fresh()->status);
    }

    public function test_tutor_can_generate_lesson_prep_from_mobile(): void
    {
        $tutor = $this->createActiveTutor();
        $student = User::factory()->create(['role' => UserRole::Student]);

        // Tutor already has active subscription

        $lesson = Lesson::create([
            'tutor_id' => $tutor->id,
            'student_id' => $student->id,
            'start_time' => now()->addDays(2),
            'end_time' => now()->addDays(2)->addHour(),
            'duration_minutes' => 60,
            'price' => 35.0,
            'platform_commission' => 0.0,
            'net_amount' => 35.0,
            'status' => Lesson::STATUS_CONFIRMED,
            'payment_status' => Lesson::PAYMENT_PAID,
            'subject' => 'Физика: Кинематика',
        ]);

        $response = $this->actingAs($tutor)->postJson("/api/mobile/lessons/{$lesson->id}/generate-prep", [
            'custom_topic' => 'Равноускоренное движение',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'topic',
            'plan',
        ]);
        $this->assertStringContainsString('Разминка', $response->json('plan'));
    }

    public function test_tutor_can_export_ics_from_mobile(): void
    {
        $tutor = $this->createActiveTutor();
        $student = User::factory()->create(['role' => UserRole::Student]);

        Lesson::create([
            'tutor_id' => $tutor->id,
            'student_id' => $student->id,
            'start_time' => now()->addHours(5),
            'end_time' => now()->addHours(6),
            'duration_minutes' => 60,
            'price' => 35.0,
            'platform_commission' => 0.0,
            'net_amount' => 35.0,
            'status' => Lesson::STATUS_CONFIRMED,
            'payment_status' => Lesson::PAYMENT_PAID,
        ]);

        $response = $this->actingAs($tutor)->get('/api/mobile/schedule/export-ics');

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'text/calendar; charset=utf-8');
    }

    public function test_tutor_can_request_payout_from_mobile(): void
    {
        $tutor = $this->createActiveTutor();

        TutorBalance::create([
            'user_id' => $tutor->id,
            'available_amount' => 150.00,
            'pending_amount' => 50.00,
        ]);

        $response = $this->actingAs($tutor)->postJson('/api/mobile/payout', [
            'card_pan' => '4255 1234 5678 9012',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'available' => 0.0,
        ]);

        $this->assertDatabaseHas('tutor_balances', [
            'user_id' => $tutor->id,
            'available_amount' => '0.00',
        ]);
    }

    public function test_security_cannot_cancel_completed_lesson(): void
    {
        $tutor = $this->createActiveTutor();
        $student = User::factory()->create(['role' => UserRole::Student]);

        $lesson = Lesson::create([
            'tutor_id' => $tutor->id,
            'student_id' => $student->id,
            'start_time' => now()->subDay(),
            'end_time' => now()->subDay()->addHour(),
            'duration_minutes' => 60,
            'price' => 45.0,
            'platform_commission' => 0.0,
            'net_amount' => 45.0,
            'status' => Lesson::STATUS_COMPLETED,
            'payment_status' => Lesson::PAYMENT_PAID,
        ]);

        $response = $this->actingAs($student)->postJson("/api/mobile/lessons/{$lesson->id}/cancel");
        $response->assertStatus(422);
        $this->assertEquals(Lesson::STATUS_COMPLETED, $lesson->fresh()->status);
    }

    public function test_security_cannot_bind_admin_as_student(): void
    {
        $tutor = $this->createActiveTutor();
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $response = $this->actingAs($tutor)->postJson('/api/mobile/lessons', [
            'student_mode' => 'existing',
            'student_id' => $admin->id,
            'subject' => 'Математика',
            'start_time' => now()->addDay()->format('Y-m-d H:i:s'),
            'duration_minutes' => 60,
            'price' => 50.0,
            'payment_status' => 'unpaid',
        ]);

        $response->assertStatus(422);
    }

    public function test_mobile_user_on_admin_is_redirected_to_mobile_cabinet(): void
    {
        $tutor = $this->createActiveTutor();

        // Simulating iPhone Safari User-Agent
        $response = $this->actingAs($tutor)
            ->withHeader('User-Agent', 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Mobile/15E148 Safari/604.1')
            ->get('/admin');

        $response->assertRedirect('/m');
    }

    public function test_mobile_user_is_always_redirected_to_m(): void
    {
        $tutor = $this->createActiveTutor();

        $response = $this->actingAs($tutor)
            ->withHeader('User-Agent', 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Mobile/15E148 Safari/604.1')
            ->get('/admin');

        $response->assertRedirect('/m');
    }

    public function test_mobile_user_with_device_cookie_is_redirected_to_m(): void
    {
        $tutor = $this->createActiveTutor();

        $response = $this->actingAs($tutor)
            ->withCookie('edusfera_device', 'mobile')
            ->get('/admin');

        $response->assertRedirect('/m');
    }

    public function test_mobile_user_with_cf_device_type_header_is_redirected_to_m(): void
    {
        $tutor = $this->createActiveTutor();

        $response = $this->actingAs($tutor)
            ->withHeader('CF-Device-Type', 'mobile')
            ->get('/admin');

        $response->assertRedirect('/m');
    }

    public function test_mobile_param_clears_prefer_desktop_and_redirects_to_m(): void
    {
        $tutor = $this->createActiveTutor();

        // Prior preference was desktop
        $response = $this->actingAs($tutor)
            ->withSession(['prefer_desktop' => true])
            ->withHeader('User-Agent', 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Mobile/15E148 Safari/604.1')
            ->get('/admin?mobile=1');

        $response->assertRedirect('/m');
        $this->assertFalse(session()->has('prefer_desktop'));
    }

    public function test_mobile_lessons_route_is_mapped_to_m_tab_lessons(): void
    {
        $tutor = $this->createActiveTutor();

        $response = $this->actingAs($tutor)
            ->withHeader('User-Agent', 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Mobile/15E148 Safari/604.1')
            ->get('/admin/lessons');

        $response->assertRedirect('/m?tab=lessons');
    }

    public function test_desktop_user_on_admin_is_not_redirected_to_m(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        // Tech Admin on desktop stays on classic Filament /admin
        $response = $this->actingAs($admin)
            ->withHeader('User-Agent', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36')
            ->get('/admin');

        $this->assertNotEquals(302, $response->getStatusCode());

        // Regular non-admin tutor on desktop is redirected to modern /cabinet
        $tutor = $this->createActiveTutor();
        $tutorResponse = $this->actingAs($tutor)
            ->withHeader('User-Agent', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36')
            ->get('/admin');

        $tutorResponse->assertRedirect('/cabinet');
    }

    public function test_desktop_user_on_m_is_redirected_to_admin(): void
    {
        $tutor = $this->createActiveTutor();

        // Desktop Mac UA visiting /m should be redirected to unified /cabinet
        $response = $this->actingAs($tutor)
            ->withHeader('User-Agent', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36')
            ->get('/m');

        $response->assertRedirect('/cabinet');

        // And with ?tab=chat redirected to /cabinet?tab=chat
        $responseChat = $this->actingAs($tutor)
            ->withHeader('User-Agent', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36')
            ->get('/m?tab=chat');

        $responseChat->assertRedirect('/cabinet?tab=chat');
    }

    public function test_mobile_lessons_contain_partner_avatar(): void
    {
        $tutor = $this->createActiveTutor(['avatar' => 'avatars/tutor.jpg']);
        $student = User::factory()->create(['role' => UserRole::Student, 'avatar' => 'avatars/student.jpg']);

        Lesson::create([
            'tutor_id' => $tutor->id,
            'student_id' => $student->id,
            'start_time' => now()->addHours(1),
            'end_time' => now()->addHours(2),
            'duration_minutes' => 60,
            'price' => 40.0,
            'platform_commission' => 0.0,
            'net_amount' => 40.0,
            'status' => Lesson::STATUS_CONFIRMED,
            'payment_status' => Lesson::PAYMENT_UNPAID,
        ]);

        $response = $this->actingAs($tutor)->getJson('/api/mobile/lessons');

        $response->assertStatus(200);
        $lessons = $response->json('lessons');
        $this->assertNotEmpty($lessons);
        $this->assertArrayHasKey('partner_avatar', $lessons[0]);
        $this->assertArrayHasKey('partner_initials', $lessons[0]);
    }

    public function test_mobile_avatar_upload(): void
    {
        \Illuminate\Support\Facades\Storage::fake('public');

        $user = $this->createActiveTutor();
        $file = \Illuminate\Http\UploadedFile::fake()->image('test_avatar.jpg', 300, 300);

        $response = $this->actingAs($user)->postJson('/api/mobile/profile/avatar', [
            'avatar' => $file,
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
        $this->assertNotNull($user->fresh()->avatar);
    }

    public function test_mobile_notifications_endpoints(): void
    {
        $user = $this->createActiveTutor();

        $sub = $user->subscription;

        $user->notify(new \App\Notifications\SubscriptionProfilePublicWarningNotification($sub));

        $this->assertEquals(1, $user->unreadNotifications()->count());

        $getRes = $this->actingAs($user)->getJson('/api/mobile/notifications');
        $getRes->assertStatus(200);
        $getRes->assertJsonStructure(['notifications', 'unread_count']);
        $this->assertEquals(1, $getRes->json('unread_count'));

        $readAllRes = $this->actingAs($user)->postJson('/api/mobile/notifications/read-all');
        $readAllRes->assertStatus(200);
        $readAllRes->assertJson(['success' => true, 'unread_count' => 0]);
        $this->assertEquals(0, $user->fresh()->unreadNotifications()->count());
    }

    public function test_mobile_profile_update(): void
    {
        $user = $this->createActiveTutor();
        $user->tutorProfile->update([
            'price_per_hour' => 30,
            'bio' => 'Старый заголовок',
        ]);

        $response = $this->actingAs($user)->postJson('/api/mobile/profile/update', [
            'name' => 'Виктория Обновленная',
            'hourly_rate' => 45,
            'headline' => 'Топ-репетитор по математике',
            'comfort_vision_mode' => true,
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
        $this->assertSame('Виктория Обновленная', $user->fresh()->name);
        $this->assertEquals(45, (float) $user->tutorProfile->fresh()->price_per_hour);
        $this->assertTrue((bool) $user->tutorProfile->fresh()->comfort_vision_mode);
    }

    public function test_mobile_change_password(): void
    {
        $user = $this->createActiveTutor([
            'password' => \Illuminate\Support\Facades\Hash::make('OldPassword123!'),
        ]);

        $badRes = $this->actingAs($user)->postJson('/api/mobile/profile/change-password', [
            'current_password' => 'WrongPassword',
            'new_password' => 'NewSecurePassword123!',
            'new_password_confirmation' => 'NewSecurePassword123!',
        ]);
        $badRes->assertStatus(422);

        $goodRes = $this->actingAs($user)->postJson('/api/mobile/profile/change-password', [
            'current_password' => 'OldPassword123!',
            'new_password' => 'NewSecurePassword123!',
            'new_password_confirmation' => 'NewSecurePassword123!',
        ]);
        $goodRes->assertStatus(200);
        $goodRes->assertJson(['success' => true]);
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('NewSecurePassword123!', $user->fresh()->password));
    }

    public function test_authenticated_student_can_access_mobile_cabinet(): void
    {
        $student = User::factory()->create([
            'role' => UserRole::Student,
        ]);

        $tutor = User::factory()->create([
            'role' => UserRole::Tutor,
        ]);

        \App\Models\HomeworkAssignment::create([
            'student_id' => $student->id,
            'tutor_id' => $tutor->id,
            'title' => 'Решить тест ЦТ по математике',
            'instructions' => 'Номера 1-10 из части А',
            'status' => 'assigned',
            'due_at' => now()->addDays(2),
        ]);

        $response = $this->actingAs($student)->get('/m');

        $response->assertStatus(200);
        $response->assertSee('mobile-cabinet');
        $response->assertSee('Решить тест ЦТ по математике');
    }

    public function test_tutor_npd_api_and_one_click_receipts(): void
    {
        $tutor = $this->createActiveTutor();
        $student = User::factory()->create(['role' => UserRole::Student]);

        $lesson = Lesson::create([
            'tutor_id' => $tutor->id,
            'student_id' => $student->id,
            'start_time' => now()->startOfMonth()->addDays(2),
            'end_time' => now()->startOfMonth()->addDays(2)->addHour(),
            'duration_minutes' => 60,
            'price' => 50.0,
            'platform_commission' => 0.0,
            'net_amount' => 50.0,
            'status' => Lesson::STATUS_COMPLETED,
            'payment_status' => Lesson::PAYMENT_PAID,
        ]);

        $getRes = $this->actingAs($tutor)->getJson('/api/mobile/npd');
        $getRes->assertStatus(200);
        $getRes->assertJsonStructure(['success', 'npd' => ['month_gross', 'tax_due_month', 'receipts_pending_count']]);
        $this->assertSame(1, $getRes->json('npd.receipts_pending_count'));

        // One-click generate receipts
        $genRes = $this->actingAs($tutor)->postJson('/api/mobile/npd/generate-receipts');
        $genRes->assertStatus(200);
        $this->assertSame(1, $genRes->json('count'));
        $this->assertNotNull($lesson->fresh()->npd_receipt_issued_at);
        $this->assertNotNull($lesson->fresh()->npd_receipt_number);

        // Update / unmark
        $unmarkRes = $this->actingAs($tutor)->postJson("/api/mobile/npd/receipts/{$lesson->id}", ['action' => 'unmark']);
        $unmarkRes->assertStatus(200);
        $this->assertNull($lesson->fresh()->npd_receipt_issued_at);
    }

    public function test_student_homework_api_and_completion(): void
    {
        $student = User::factory()->create(['role' => UserRole::Student]);
        $tutor = $this->createActiveTutor();

        $hw = \App\Models\HomeworkAssignment::create([
            'student_id' => $student->id,
            'tutor_id' => $tutor->id,
            'title' => 'Прочитать параграф 12',
            'status' => 'assigned',
            'due_at' => now()->addDays(3),
        ]);

        $listRes = $this->actingAs($student)->getJson('/api/mobile/homework');
        $listRes->assertStatus(200);
        $this->assertCount(1, $listRes->json('assignments'));

        $compRes = $this->actingAs($student)->postJson("/api/mobile/homework/{$hw->id}/complete");
        $compRes->assertStatus(200);
        $compRes->assertJson(['success' => true]);
        $this->assertSame('completed', $hw->fresh()->status);
    }

    public function test_tutor_can_confirm_lesson_request(): void
    {
        $tutor = $this->createActiveTutor();
        $student = User::factory()->create(['role' => UserRole::Student]);

        $lesson = Lesson::create([
            'tutor_id' => $tutor->id,
            'student_id' => $student->id,
            'start_time' => now()->addDays(2),
            'end_time' => now()->addDays(2)->addHour(),
            'duration_minutes' => 60,
            'price' => 45.0,
            'platform_commission' => 0.0,
            'net_amount' => 45.0,
            'status' => Lesson::STATUS_PENDING,
            'payment_status' => 'unpaid',
            'package_code' => 'single',
        ]);

        $res = $this->actingAs($tutor)->postJson("/api/mobile/lessons/{$lesson->id}/confirm");
        $res->assertStatus(200);
        $res->assertJson(['success' => true]);
        $this->assertSame(Lesson::STATUS_CONFIRMED, $lesson->fresh()->status);
    }

    public function test_tutor_can_assign_homework(): void
    {
        $tutor = $this->createActiveTutor();
        $student = User::factory()->create(['role' => UserRole::Student]);

        $lesson = Lesson::create([
            'tutor_id' => $tutor->id,
            'student_id' => $student->id,
            'start_time' => now()->addDays(1),
            'end_time' => now()->addDays(1)->addHour(),
            'duration_minutes' => 60,
            'price' => 40.0,
            'platform_commission' => 0.0,
            'net_amount' => 40.0,
            'status' => Lesson::STATUS_CONFIRMED,
            'payment_status' => 'paid',
            'package_code' => 'single',
        ]);

        $res = $this->actingAs($tutor)->postJson("/api/mobile/lessons/{$lesson->id}/homework", [
            'title' => 'Решить задания 1-10 из сборника ЦТ',
            'instructions' => 'Обратить внимание на ловушки в задаче №4',
            'due_days' => 3,
        ]);

        $res->assertStatus(200);
        $res->assertJson(['success' => true]);

        $this->assertDatabaseHas('homework_assignments', [
            'lesson_id' => $lesson->id,
            'tutor_id' => $tutor->id,
            'student_id' => $student->id,
            'title' => 'Решить задания 1-10 из сборника ЦТ',
        ]);
    }

    public function test_tutor_can_get_and_save_availability(): void
    {
        $tutor = $this->createActiveTutor();

        $getRes = $this->actingAs($tutor)->getJson('/api/mobile/availability');
        $getRes->assertStatus(200);
        $this->assertCount(7, $getRes->json('availability'));

        $saveRes = $this->actingAs($tutor)->postJson('/api/mobile/availability', [
            'availability' => [
                ['day_of_week' => 1, 'is_active' => true, 'start_time' => '10:00', 'end_time' => '19:00'],
                ['day_of_week' => 2, 'is_active' => true, 'start_time' => '10:00', 'end_time' => '19:00'],
                ['day_of_week' => 3, 'is_active' => true, 'start_time' => '10:00', 'end_time' => '19:00'],
                ['day_of_week' => 4, 'is_active' => true, 'start_time' => '10:00', 'end_time' => '19:00'],
                ['day_of_week' => 5, 'is_active' => true, 'start_time' => '10:00', 'end_time' => '18:00'],
                ['day_of_week' => 6, 'is_active' => false, 'start_time' => '10:00', 'end_time' => '18:00'],
                ['day_of_week' => 0, 'is_active' => false, 'start_time' => '10:00', 'end_time' => '18:00'],
            ],
        ]);

        $saveRes->assertStatus(200);
        $saveRes->assertJson(['success' => true]);

        $this->assertDatabaseHas('tutor_availability', [
            'user_id' => $tutor->id,
            'day_of_week' => 1,
            'is_active' => true,
        ]);
    }

    public function test_tutor_can_apply_promocode(): void
    {
        $tutor = $this->createActiveTutor();

        $promo = \App\Models\PromoCode::create([
            'code' => 'TESTFREE2026',
            'discount_type' => 'free_period',
            'subscription_period' => '1_month',
            'max_uses' => 10,
            'used_count' => 0,
            'is_active' => true,
        ]);

        $res = $this->actingAs($tutor)->postJson('/api/mobile/promocode/apply', [
            'code' => 'TESTFREE2026',
        ]);

        $res->assertStatus(200);
        $res->assertJson(['success' => true, 'is_activated' => true]);
    }

    public function test_student_can_render_cabinet_on_desktop_and_fetch_lessons(): void
    {
        $student = User::factory()->create([
            'role' => \App\Enums\UserRole::Student,
            'name' => 'Иван Студент',
            'email' => 'student.desktop@edusfera.by',
        ]);

        $response = $this->actingAs($student)
            ->withHeader('User-Agent', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36')
            ->get('/cabinet');

        $response->assertStatus(200);
        $response->assertSee('cabinet-app');
        $response->assertSee('EDUSFERA_MOBILE_INITIAL_DATA');

        // Verify API endpoint for student lessons
        $apiRes = $this->actingAs($student)->getJson('/api/mobile/lessons');
        $apiRes->assertStatus(200);
        $apiRes->assertJsonStructure([
            'lessons',
            'week_start',
            'week_label',
        ]);
    }
}
