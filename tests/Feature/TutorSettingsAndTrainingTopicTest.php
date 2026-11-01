<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Filament\Pages\TutorSettingsPage;
use App\Models\NewsArticle;
use App\Models\TutorProfile;
use App\Models\User;
use Database\Seeders\PlatformTrainingArticlesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TutorSettingsAndTrainingTopicTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (! file_exists(database_path('news.sqlite'))) {
            touch(database_path('news.sqlite'));
        }

        $this->artisan('migrate', [
            '--database' => 'news',
            '--path' => 'database/migrations/news',
            '--force' => true,
        ]);

        (new PlatformTrainingArticlesSeeder())->run();
    }

    public function test_can_filter_news_by_platform_training_category(): void
    {
        $response = $this->get(route('news.index', ['category' => 'platform-training']));

        $response->assertOk();
        $response->assertSee('Обучение работе на платформе');
        $response->assertSee('Как провести свой первый онлайн-урок за 3 простых шага');
        $response->assertSee('Как отправить приглашение на урок в Viber или Telegram');
    }

    public function test_tutor_can_access_settings_page_and_toggle_visual_comfort(): void
    {
        $tutor = User::factory()->create([
            'role' => UserRole::Tutor,
        ]);
        $profile = TutorProfile::create([
            'user_id' => $tutor->id,
            'is_public' => false,
            'price_per_hour' => 35,
        ]);
        \App\Domain\Subscription\Models\Subscription::create([
            'tutor_id' => $tutor->id,
            'status' => \App\Domain\Subscription\Enums\SubscriptionStatus::ACTIVE,
            'plan' => \App\Domain\Subscription\Enums\SubscriptionPlan::PRO,
            'is_onboarded' => true,
        ]);

        $this->actingAs($tutor);

        $response = $this->get('/admin/settings');
        $response->assertOk();
        $response->assertSee('Визуальный комфорт и зрение');
        $response->assertSee('Режим крупного шрифта и высокой контрастности');
        $response->assertSee('Режим работы и видимость анкеты');

        Livewire::test(TutorSettingsPage::class)
            ->call('toggleLargeFont', true)
            ->assertSet('isLargeFont', true);

        $profile->refresh();
        $this->assertTrue((bool) $profile->comfort_vision_mode);
    }

    public function test_tutor_can_toggle_privacy_mode_in_settings(): void
    {
        $tutor = User::factory()->create([
            'role' => UserRole::Tutor,
        ]);
        $profile = TutorProfile::create([
            'user_id' => $tutor->id,
            'is_public' => false,
            'price_per_hour' => 35,
        ]);
        \App\Domain\Subscription\Models\Subscription::create([
            'tutor_id' => $tutor->id,
            'status' => \App\Domain\Subscription\Enums\SubscriptionStatus::ACTIVE,
            'plan' => \App\Domain\Subscription\Enums\SubscriptionPlan::PRO,
            'is_onboarded' => true,
        ]);

        $this->actingAs($tutor);

        Livewire::test(TutorSettingsPage::class)
            ->call('togglePrivacy', true)
            ->assertSet('isPublic', true);

        $profile->refresh();
        $this->assertTrue((bool) $profile->is_public);
    }

    public function test_private_mode_requires_pro_or_trial_in_hero_widget_and_settings(): void
    {
        $tutor = User::factory()->create([
            'role' => UserRole::Tutor,
        ]);
        $profile = TutorProfile::create([
            'user_id' => $tutor->id,
            'is_public' => true,
            'price_per_hour' => 35,
        ]);

        // Basic subscription (cannot hide profile)
        \App\Domain\Subscription\Models\Subscription::create([
            'tutor_id' => $tutor->id,
            'status' => \App\Domain\Subscription\Enums\SubscriptionStatus::ACTIVE,
            'plan' => \App\Domain\Subscription\Enums\SubscriptionPlan::BASIC,
            'is_onboarded' => true,
        ]);

        $this->actingAs($tutor);

        // In Settings: attempting to hide profile without Pro/Trial should fail
        Livewire::test(TutorSettingsPage::class)
            ->call('togglePrivacy', false)
            ->assertSet('isPublic', true);

        $profile->refresh();
        $this->assertTrue((bool) $profile->is_public);

        // In Hero widget: attempting to toggle visibility to private should fail
        Livewire::test(\App\Filament\Widgets\TutorHeroWidget::class)
            ->call('toggleProfileVisibility');

        $profile->refresh();
        $this->assertTrue((bool) $profile->is_public);
    }

    public function test_comfort_vision_and_classroom_controls(): void
    {
        $cssContent = file_get_contents(resource_path('views/partials/filament-mobile-ergonomics.blade.php'));
        $this->assertStringContainsString('.comfort-vision', $cssContent);
        $this->assertStringContainsString('18px !important', $cssContent);
        $this->assertStringContainsString('border-2 border-slate-400', $cssContent);

        $classroomView = file_get_contents(resource_path('views/classroom/show.blade.php'));
        $this->assertStringContainsString('Микрофон вкл', $classroomView);
        $this->assertStringContainsString('Камера вкл', $classroomView);
        $this->assertStringContainsString('Очистить доску', $classroomView);
        $this->assertStringContainsString('Задания ЦТ', $classroomView);
        $this->assertStringContainsString('Завершить урок', $classroomView);
        $this->assertStringContainsString('cr-tools-toggle-pill', $classroomView);
    }
}
