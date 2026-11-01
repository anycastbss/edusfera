<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FilamentAccessibilityFontToggleTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_panel_renders_accessibility_font_toggle_and_zero_fouc_script(): void
    {
        $tutor = User::factory()->create([
            'role' => 'tutor',
            'phone' => '+375298500010',
        ]);

        \App\Domain\Subscription\Models\Subscription::create([
            'tutor_id' => $tutor->id,
            'status' => \App\Domain\Subscription\Enums\SubscriptionStatus::ACTIVE,
            'plan' => \App\Domain\Subscription\Enums\SubscriptionPlan::PRO,
            'is_onboarded' => true,
        ]);

        $response = $this->actingAs($tutor)->get('/admin');

        $response->assertOk();
        $response->assertSee('ed-font-toggle-container');
        $response->assertSee('Крупный шрифт');
        $response->assertSee('edusfera_large_font');
    }

    public function test_messages_page_renders_isolated_mobile_messenger_container(): void
    {
        $tutor = User::factory()->create([
            'role' => 'tutor',
            'phone' => '+375298500011',
        ]);

        \App\Domain\Subscription\Models\Subscription::create([
            'tutor_id' => $tutor->id,
            'status' => \App\Domain\Subscription\Enums\SubscriptionStatus::ACTIVE,
            'plan' => \App\Domain\Subscription\Enums\SubscriptionPlan::PRO,
            'is_onboarded' => true,
        ]);

        $response = $this->actingAs($tutor)->get('/admin/messages');

        $response->assertOk();
        $response->assertSee('id="edusfera-messenger-root"', false);
        $response->assertSee('ed-mobile-bottom-nav');
    }
}
