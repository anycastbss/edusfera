<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Lesson;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClassroomWaitingScreenTest extends TestCase
{
    use RefreshDatabase;

    public function test_pending_lesson_displays_waiting_screen_for_student_without_403(): void
    {
        $tutor = User::factory()->create(['role' => 'tutor']);
        $student = User::factory()->create(['role' => 'student']);

        $lesson = Lesson::forceCreate([
            'tutor_id' => $tutor->id,
            'student_id' => $student->id,
            'status' => Lesson::STATUS_PENDING,
            'payment_status' => Lesson::PAYMENT_UNPAID,
            'start_time' => now()->addDay(),
            'end_time' => now()->addDay()->addHour(),
            'duration_minutes' => 60,
            'price' => 40,
            'platform_commission' => 8,
            'net_amount' => 32,
        ]);

        $response = $this->actingAs($student)->get(route('classroom.show', $lesson));

        $response->assertStatus(200);
        $response->assertViewIs('classroom.waiting');
        $response->assertSee('Ожидает подтверждения');
        $response->assertSee('Преподаватель');
    }

    public function test_tutor_can_one_click_confirm_pending_lesson_on_entry(): void
    {
        $tutor = User::factory()->create(['role' => 'tutor']);
        $student = User::factory()->create(['role' => 'student']);

        $lesson = Lesson::forceCreate([
            'tutor_id' => $tutor->id,
            'student_id' => $student->id,
            'status' => Lesson::STATUS_PENDING,
            'payment_status' => Lesson::PAYMENT_UNPAID,
            'start_time' => now()->addMinutes(10),
            'end_time' => now()->addHour(),
            'duration_minutes' => 60,
            'price' => 40,
            'platform_commission' => 8,
            'net_amount' => 32,
        ]);

        config(['classroom.enabled' => true]);

        // Tutor passes ?confirm=1
        $response = $this->actingAs($tutor)->get(route('classroom.show', ['lesson' => $lesson, 'confirm' => 1]));

        $this->assertEquals(Lesson::STATUS_CONFIRMED, $lesson->fresh()->status);
        $response->assertStatus(200);
        $response->assertViewIs('classroom.show');
    }

    public function test_classroom_status_endpoint_returns_json(): void
    {
        $tutor = User::factory()->create(['role' => 'tutor']);
        $student = User::factory()->create(['role' => 'student']);

        $lesson = Lesson::forceCreate([
            'tutor_id' => $tutor->id,
            'student_id' => $student->id,
            'status' => Lesson::STATUS_PENDING,
            'payment_status' => Lesson::PAYMENT_UNPAID,
            'start_time' => now()->addDay(),
            'end_time' => now()->addDay()->addHour(),
            'duration_minutes' => 60,
            'price' => 40,
            'platform_commission' => 8,
            'net_amount' => 32,
        ]);

        $response = $this->actingAs($student)->get(route('classroom.status', $lesson));

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'pending',
            'can_enter' => false,
        ]);

        $lesson->update(['status' => Lesson::STATUS_CONFIRMED]);

        $response2 = $this->actingAs($student)->get(route('classroom.status', $lesson));
        $response2->assertStatus(200);
        $response2->assertJson([
            'status' => 'confirmed',
            'can_enter' => true,
        ]);
    }
}
