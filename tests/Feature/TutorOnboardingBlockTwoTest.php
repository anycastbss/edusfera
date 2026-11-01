<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Filament\Pages\TutorAiAssistantPage;
use App\Filament\Pages\TutorAvailabilityPage;
use App\Filament\Widgets\TutorHeroWidget;
use App\Models\TutorProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TutorOnboardingBlockTwoTest extends TestCase
{
    use RefreshDatabase;

    public function test_onboarding_save_step1_defaults_is_public_to_false_for_privacy(): void
    {
        $tutor = User::factory()->create([
            'role' => 'tutor',
            'name' => 'Валентина Васильевна',
        ]);

        $response = $this->actingAs($tutor)->postJson('/api/tutor/onboarding/save-step1', [
            'name' => 'Валентина Васильевна',
            'subjects' => ['Математика'],
            'price_per_hour' => 45,
            'experience_years' => 15,
            'bio' => 'Опытный преподаватель математики.',
        ]);

        $response->assertOk();
        $response->assertJson(['success' => true]);

        $profile = TutorProfile::where('user_id', $tutor->id)->first();
        $this->assertNotNull($profile);
        $this->assertFalse((bool) $profile->is_public, 'Profile must default to is_public = false (private mode) for 50+ tutors.');
    }

    public function test_onboarding_save_step1_can_set_is_public_to_true(): void
    {
        $tutor = User::factory()->create([
            'role' => 'tutor',
            'name' => 'Дмитрий Иванов',
        ]);

        $response = $this->actingAs($tutor)->postJson('/api/tutor/onboarding/save-step1', [
            'name' => 'Дмитрий Иванов',
            'subjects' => ['Физика'],
            'price_per_hour' => 50,
            'is_public' => true,
        ]);

        $response->assertOk();

        $profile = TutorProfile::where('user_id', $tutor->id)->first();
        $this->assertNotNull($profile);
        $this->assertTrue((bool) $profile->is_public);
    }

    public function test_onboarding_state_returns_is_public(): void
    {
        $tutor = User::factory()->create(['role' => 'tutor']);
        TutorProfile::create([
            'user_id' => $tutor->id,
            'is_public' => false,
            'subjects' => ['Химия'],
            'price_per_hour' => 40,
        ]);

        $response = $this->actingAs($tutor)->getJson('/api/tutor/onboarding/state');
        $response->assertOk();
        $response->assertJsonPath('profile.is_public', false);
    }

    public function test_phone_help_callback_request(): void
    {
        $tutor = User::factory()->create([
            'role' => 'tutor',
            'name' => 'Наталья Петровна',
            'phone' => '+375291234567',
        ]);

        $response = $this->actingAs($tutor)->postJson('/api/tutor/onboarding/request-help', [
            'phone' => '+375 (29) 999-88-77',
        ]);

        $response->assertOk();
        $response->assertJson(['success' => true]);
        $this->assertDatabaseHas('users', [
            'id' => $tutor->id,
            'phone' => '+375 (29) 999-88-77',
        ]);
    }

    public function test_filament_navigation_items_are_renamed(): void
    {
        $this->assertEquals(
            'Свободные часы',
            TutorAvailabilityPage::getNavigationLabel()
        );

        $this->assertEquals(
            'Задания ЦТ / ЦЭ',
            TutorAiAssistantPage::getNavigationLabel()
        );
    }

    public function test_classroom_test_route_works(): void
    {
        $tutor = User::factory()->create(['role' => 'tutor']);
        $response = $this->actingAs($tutor)->get(route('classroom.test'));
        $response->assertOk();
    }

    public function test_tutor_hero_widget_toggles_privacy_status(): void
    {
        $tutor = User::factory()->create(['role' => 'tutor']);
        $profile = TutorProfile::create([
            'user_id' => $tutor->id,
            'is_public' => false,
            'price_per_hour' => 45,
            'subjects' => ['Математика'],
        ]);

        Livewire::actingAs($tutor)
            ->test(TutorHeroWidget::class)
            ->assertSee('Статус кабинета: ПРИВАТНЫЙ')
            ->call('toggleProfileVisibility')
            ->assertSee('Статус кабинета: ОТКРЫТАЯ ВИТРИНА');

        $this->assertTrue((bool) $profile->fresh()->is_public);

        Livewire::actingAs($tutor)
            ->test(TutorHeroWidget::class)
            ->call('toggleProfileVisibility')
            ->assertSee('Статус кабинета: ПРИВАТНЫЙ');

        $this->assertFalse((bool) $profile->fresh()->is_public);
    }
}
