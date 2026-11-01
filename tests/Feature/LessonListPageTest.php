<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Filament\Resources\LessonResource\Pages\ListLessons;
use App\Filament\Resources\LessonResource\Widgets\LessonOverviewWidget;
use App\Models\Lesson;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class LessonListPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_lesson_list_shows_overview_tabs_and_actions(): void
    {
        $student = User::factory()->create([
            'role' => 'student',
            'phone' => '+375298200001',
        ]);

        $tutor = User::factory()->create([
            'role' => 'tutor',
            'phone' => '+375298200002',
        ]);

        Lesson::query()->forceCreate([
            'tutor_id' => $tutor->id,
            'student_id' => $student->id,
            'start_time' => now('UTC')->addDay(),
            'end_time' => now('UTC')->addDay()->addHour(),
            'duration_minutes' => 60,
            'price' => '80.00',
            'platform_commission' => '12.00',
            'net_amount' => '68.00',
            'status' => Lesson::STATUS_PENDING,
            'payment_status' => Lesson::PAYMENT_UNPAID,
            'package_code' => 'single',
            'package_lessons' => 1,
        ]);

        Livewire::actingAs($student)
            ->test(ListLessons::class)
            ->assertSee('Предстоящие')
            ->assertSee('Все уроки')
            ->assertSee($tutor->name);

        Livewire::actingAs($student)
            ->test(LessonOverviewWidget::class)
            ->assertSee('Ближайший урок')
            ->assertSee('Предстоящие уроки');
    }

    public function test_tutor_lesson_prep_context_detection_and_chaining(): void
    {
        $student = User::factory()->create([
            'role' => 'student',
            'phone' => '+375298200003',
        ]);

        $tutor = User::factory()->create([
            'role' => 'tutor',
            'phone' => '+375298200004',
        ]);
        $tutor->tutorProfile()->create([
            'subjects' => ['Математика'],
            'hourly_rate' => 80,
            'is_published' => true,
        ]);

        // First lesson - diagnostic
        $lesson1 = Lesson::query()->forceCreate([
            'tutor_id' => $tutor->id,
            'student_id' => $student->id,
            'start_time' => now('UTC')->subDays(2),
            'end_time' => now('UTC')->subDays(2)->addHour(),
            'duration_minutes' => 60,
            'price' => '80.00',
            'platform_commission' => '12.00',
            'net_amount' => '68.00',
            'status' => Lesson::STATUS_COMPLETED,
            'payment_status' => Lesson::PAYMENT_PAID,
            'package_code' => 'single',
            'package_lessons' => 1,
            'tutor_report_summary' => 'Квадратные уравнения и теорема Виета',
            'tutor_report_focus' => 'Ошибки в знаках свободного члена',
            'tutor_next_step' => 'Дробно-рациональные уравнения',
            'tutor_homework_summary' => '№14-20 стр 45',
        ]);

        // Second lesson - upcoming
        $lesson2 = Lesson::query()->forceCreate([
            'tutor_id' => $tutor->id,
            'student_id' => $student->id,
            'start_time' => now('UTC')->addDay(),
            'end_time' => now('UTC')->addDay()->addHour(),
            'duration_minutes' => 60,
            'price' => '80.00',
            'platform_commission' => '12.00',
            'net_amount' => '68.00',
            'status' => Lesson::STATUS_CONFIRMED,
            'payment_status' => Lesson::PAYMENT_PAID,
            'package_code' => 'single',
            'package_lessons' => 1,
        ]);

        $component = new ListLessons();

        // 1. Check first lesson detection when no prior lessons exist
        $lessonSolo = Lesson::query()->forceCreate([
            'tutor_id' => $tutor->id,
            'student_id' => User::factory()->create(['role' => 'student'])->id,
            'start_time' => now('UTC')->addDays(3),
            'end_time' => now('UTC')->addDays(3)->addHour(),
            'duration_minutes' => 60,
            'price' => '80.00',
            'platform_commission' => '12.00',
            'net_amount' => '68.00',
            'status' => Lesson::STATUS_CONFIRMED,
            'payment_status' => Lesson::PAYMENT_PAID,
            'package_code' => 'single',
            'package_lessons' => 1,
        ]);

        $soloContext = $component->detectLessonPrepContext($lessonSolo);
        $this->assertTrue($soloContext['is_first_lesson']);
        $this->assertSame('Входная диагностика и аудит знаний', $soloContext['detected_topic']);
        $this->assertStringContainsString('Вводный урок', $soloContext['topic_source']);

        // 2. Check lesson 2 context chained from lesson 1 tutor_next_step
        $context2 = $component->detectLessonPrepContext($lesson2);
        $this->assertFalse($context2['is_first_lesson']);
        $this->assertSame(2, $context2['lesson_number']);
        $this->assertSame('Дробно-рациональные уравнения', $context2['detected_topic']);
        $this->assertSame('Квадратные уравнения и теорема Виета', $context2['previous_summary']);
        $this->assertSame('Ошибки в знаках свободного члена', $context2['previous_focus']);
        $this->assertStringContainsString('Намечено на уроке #' . $lesson1->id, $context2['topic_source']);

        // 3. Test Livewire component openCalendarLesson and edit topic
        Livewire::actingAs($tutor)
            ->test(ListLessons::class)
            ->call('openCalendarLesson', $lesson2->id)
            ->assertSet('prepTopic', 'Дробно-рациональные уравнения')
            ->set('prepTopic', 'Кастомная тема: Логарифмы')
            ->call('resetPrepTopic', $lesson2->id)
            ->assertSet('prepTopic', 'Дробно-рациональные уравнения')
            ->call('generateQuickPrep', $lesson2->id)
            ->assertNotSet('quickPrepPlan', null);
    }
}
