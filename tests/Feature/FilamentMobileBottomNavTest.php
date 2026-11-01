<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FilamentMobileBottomNavTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_tutor_sees_mobile_bottom_nav_with_key_destinations(): void
    {
        $tutor = User::factory()->create([
            'role' => 'tutor',
            'phone' => '+375298500001',
        ]);

        \App\Domain\Subscription\Models\Subscription::create([
            'tutor_id' => $tutor->id,
            'status' => \App\Domain\Subscription\Enums\SubscriptionStatus::ACTIVE,
            'plan' => \App\Domain\Subscription\Enums\SubscriptionPlan::PRO,
            'is_onboarded' => true,
        ]);

        $response = $this->actingAs($tutor)->get('/admin');

        $response->assertOk();
        $response->assertSee('ed-mobile-bottom-nav');
        $response->assertSee('Главная');
        $response->assertSee('Уроки');
        $response->assertSee('Чат');
        $response->assertSee('Профиль');
        $response->assertSee('Меню');
        $response->assertSee('/admin/lessons');
        $response->assertSee('/admin/messages');
    }

    public function test_mobile_bottom_nav_shows_unread_messages_badge(): void
    {
        $tutor = User::factory()->create([
            'role' => 'tutor',
            'phone' => '+375298500002',
        ]);

        \App\Domain\Subscription\Models\Subscription::create([
            'tutor_id' => $tutor->id,
            'status' => \App\Domain\Subscription\Enums\SubscriptionStatus::ACTIVE,
            'plan' => \App\Domain\Subscription\Enums\SubscriptionPlan::PRO,
            'is_onboarded' => true,
        ]);

        $student = User::factory()->create([
            'role' => 'student',
            'phone' => '+375298500003',
        ]);

        $conversation = Conversation::create([
            'tutor_id' => $tutor->id,
            'student_id' => $student->id,
            'last_message_at' => now(),
        ]);

        Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $student->id,
            'message' => 'Здравствуйте! Подскажите по домашнему заданию?',
            'is_read' => false,
        ]);

        $response = $this->actingAs($tutor)->get('/admin');

        $response->assertOk();
        $response->assertSee('ed-nav-badge');
        $response->assertSee('1');
    }
}
