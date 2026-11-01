<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Services\AvailabilityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AvailabilityServiceTest extends TestCase
{
    use RefreshDatabase;

    private AvailabilityService $service;

    private User $tutor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(AvailabilityService::class);
        $this->tutor = User::factory()->create(['role' => 'tutor']);
    }

    public function test_can_create_valid_availability_window(): void
    {
        $window = $this->service->createWindow(
            userId: $this->tutor->id,
            dayOfWeek: 1,
            startTime: '10:00',
            endTime: '14:00',
        );

        $this->assertDatabaseHas('tutor_availability', [
            'id' => $window->id,
            'user_id' => $this->tutor->id,
            'day_of_week' => 1,
            'start_time' => '10:00',
            'end_time' => '14:00',
        ]);
    }

    public function test_rejects_inverted_start_and_end_times(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Время окончания должно быть позже времени начала.');

        $this->service->createWindow(
            userId: $this->tutor->id,
            dayOfWeek: 1,
            startTime: '18:00',
            endTime: '10:00',
        );
    }

    public function test_rejects_equal_start_and_end_times(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Время окончания должно быть позже времени начала.');

        $this->service->createWindow(
            userId: $this->tutor->id,
            dayOfWeek: 1,
            startTime: '12:00',
            endTime: '12:00',
        );
    }

    public function test_rejects_overlapping_window(): void
    {
        $this->service->createWindow(
            userId: $this->tutor->id,
            dayOfWeek: 1,
            startTime: '10:00',
            endTime: '14:00',
        );

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Выбранное время пересекается с существующим окном доступности.');

        $this->service->createWindow(
            userId: $this->tutor->id,
            dayOfWeek: 1,
            startTime: '12:00',
            endTime: '16:00',
        );
    }

    public function test_can_update_window_excluding_self(): void
    {
        $window = $this->service->createWindow(
            userId: $this->tutor->id,
            dayOfWeek: 1,
            startTime: '10:00',
            endTime: '14:00',
        );

        $updated = $this->service->updateWindow($window, 1, '09:00', '13:00');

        $this->assertEquals('09:00', $updated->start_time);
        $this->assertEquals('13:00', $updated->end_time);
    }

    public function test_accepts_times_without_leading_zero_properly(): void
    {
        $window = $this->service->createWindow(
            userId: $this->tutor->id,
            dayOfWeek: 2,
            startTime: '9:00',
            endTime: '10:00',
        );

        $this->assertNotNull($window->id);
    }
}
