<?php

declare(strict_types=1);

namespace Tests\Feature\Lesson;

use App\Domain\Subscription\Enums\SubscriptionPlan;
use App\Domain\Subscription\Enums\SubscriptionStatus;
use App\Domain\Subscription\Models\Subscription;
use App\Enums\UserRole;
use App\Filament\Resources\LessonResource\Pages\ListLessons;
use App\Models\Lesson;
use App\Models\TutorProfile;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class LessonWeeklySchedulePrintTest extends TestCase
{
    use RefreshDatabase;

    private function createOnboardedTutor(array $attributes = []): User
    {
        $tutor = User::factory()->create(array_merge([
            'role' => UserRole::Tutor,
        ], $attributes));

        Subscription::create([
            'tutor_id' => $tutor->id,
            'status' => SubscriptionStatus::ACTIVE,
            'plan' => SubscriptionPlan::PRO,
            'current_period_ends_at' => now()->addMonth(),
            'is_onboarded' => true,
        ]);

        return $tutor;
    }

    public function test_unauthenticated_user_cannot_access_print_weekly_page(): void
    {
        $response = $this->get(route('admin.lessons.print-weekly'));

        $response->assertRedirect();
    }

    public function test_tutor_can_view_clean_a4_printable_weekly_schedule(): void
    {
        $tutor = $this->createOnboardedTutor([
            'name' => 'Анна Репетитор',
            'phone' => '+375291112233',
        ]);

        TutorProfile::query()->create([
            'user_id' => $tutor->id,
            'subjects' => ['Алгебра и геометрия'],
        ]);

        $student = User::factory()->create([
            'role' => UserRole::Student,
            'name' => 'Иван Иванов',
            'phone' => '+375297778899',
        ]);

        $displayTz = config('booking.display_timezone', 'Europe/Minsk');
        $thisMonday = Carbon::now($displayTz)->startOfWeek(Carbon::MONDAY);
        $lessonTime = $thisMonday->copy()->setHour(14)->setMinute(0);

        Lesson::query()->forceCreate([
            'tutor_id' => $tutor->id,
            'student_id' => $student->id,
            'start_time' => $lessonTime->copy()->utc(),
            'end_time' => $lessonTime->copy()->addMinutes(60)->utc(),
            'duration_minutes' => 60,
            'price' => '45.00',
            'platform_commission' => '0.00',
            'net_amount' => '45.00',
            'status' => Lesson::STATUS_CONFIRMED,
            'payment_status' => Lesson::PAYMENT_PAID,
            'package_code' => 'single',
            'package_lessons' => 1,
            'notes' => 'Повторить тригонометрические формулы',
        ]);

        $response = $this->actingAs($tutor)->get(route('admin.lessons.print-weekly', [
            'week' => $thisMonday->format('Y-m-d'),
        ]));

        $response->assertOk();
        $response->assertSee('Расписание на неделю');
        $response->assertSee('Иван Иванов');
        $response->assertSee('Алгебра и геометрия');
        $response->assertSee('14:00');
        $response->assertSee('15:00');
        $response->assertSee('Оплачен');
        $response->assertSee('Повторить тригонометрические формулы');
        $response->assertSee('Распечатать (А4)');
        $response->assertSee('@page {', false);
        $response->assertSee('size: A4 portrait;', false);
    }

    public function test_tutor_schedule_does_not_leak_other_tutors_lessons(): void
    {
        $tutorA = $this->createOnboardedTutor(['name' => 'Репетитор А']);
        TutorProfile::query()->create([
            'user_id' => $tutorA->id,
            'subjects' => ['Физика'],
        ]);

        $tutorB = $this->createOnboardedTutor(['name' => 'Репетитор Б']);
        TutorProfile::query()->create([
            'user_id' => $tutorB->id,
            'subjects' => ['Химия'],
        ]);

        $studentA = User::factory()->create(['role' => UserRole::Student, 'name' => 'Ученик Первого']);
        $studentB = User::factory()->create(['role' => UserRole::Student, 'name' => 'Ученик Второго']);

        $displayTz = config('booking.display_timezone', 'Europe/Minsk');
        $thisMonday = Carbon::now($displayTz)->startOfWeek(Carbon::MONDAY);

        // Lesson for Tutor A
        Lesson::query()->forceCreate([
            'tutor_id' => $tutorA->id,
            'student_id' => $studentA->id,
            'start_time' => $thisMonday->copy()->setHour(10)->utc(),
            'end_time' => $thisMonday->copy()->setHour(11)->utc(),
            'duration_minutes' => 60,
            'price' => '40.00',
            'platform_commission' => '0.00',
            'net_amount' => '40.00',
            'status' => Lesson::STATUS_CONFIRMED,
            'payment_status' => Lesson::PAYMENT_UNPAID,
            'package_code' => 'single',
            'package_lessons' => 1,
        ]);

        // Lesson for Tutor B
        Lesson::query()->forceCreate([
            'tutor_id' => $tutorB->id,
            'student_id' => $studentB->id,
            'start_time' => $thisMonday->copy()->setHour(12)->utc(),
            'end_time' => $thisMonday->copy()->setHour(13)->utc(),
            'duration_minutes' => 60,
            'price' => '40.00',
            'platform_commission' => '0.00',
            'net_amount' => '40.00',
            'status' => Lesson::STATUS_CONFIRMED,
            'payment_status' => Lesson::PAYMENT_UNPAID,
            'package_code' => 'single',
            'package_lessons' => 1,
        ]);

        $responseA = $this->actingAs($tutorA)->get(route('admin.lessons.print-weekly', [
            'week' => $thisMonday->format('Y-m-d'),
        ]));

        $responseA->assertOk();
        $responseA->assertSee('Ученик Первого');
        $responseA->assertSee('Физика');
        $responseA->assertDontSee('Ученик Второго');
        $responseA->assertDontSee('Химия');
    }

    public function test_print_button_is_rendered_on_lessons_page(): void
    {
        $tutor = $this->createOnboardedTutor();

        Livewire::actingAs($tutor)
            ->test(ListLessons::class)
            ->assertSee('Распечатать на неделю (А4)')
            ->assertActionVisible('print_weekly');
    }
}
