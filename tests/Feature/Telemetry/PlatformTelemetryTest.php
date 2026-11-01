<?php

declare(strict_types=1);

namespace Tests\Feature\Telemetry;

use App\Models\PlatformTelemetry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlatformTelemetryTest extends TestCase
{
    use RefreshDatabase;

    public function test_telemetry_captures_page_visit(): void
    {
        $middleware = new \App\Http\Middleware\CapturePlatformTelemetry();
        $request = \Illuminate\Http\Request::create('/catalog', 'GET');
        $response = new \Symfony\Component\HttpFoundation\Response('OK', 200);

        $middleware->handle($request, function ($req) use ($response) {
            return $response;
        });
        $middleware->terminate($request, $response);

        $this->assertDatabaseHas('platform_telemetry', [
            'method' => 'GET',
            'status_code' => 200,
            'url' => 'http://localhost/catalog',
        ]);
    }

    public function test_telemetry_event_endpoint_records_beacon(): void
    {
        $response = $this->postJson('/api/telemetry/event', [
            'event_name' => 'web_vital_lcp',
            'duration_ms' => 342,
            'url' => 'https://edusfera.by/catalog',
            'metadata' => ['metric' => 'LCP'],
        ]);

        $response->assertStatus(201)
            ->assertJson(['status' => 'recorded']);

        $this->assertDatabaseHas('platform_telemetry', [
            'action_type' => 'beacon',
            'event_name' => 'web_vital_lcp',
            'duration_ms' => 342,
        ]);
    }

    public function test_telemetry_prune_command_deletes_old_records(): void
    {
        // 20 days old record
        \App\Models\PlatformTelemetry::create([
            'method' => 'GET',
            'url' => 'https://edusfera.by/old-page',
            'status_code' => 200,
            'duration_ms' => 120,
            'ip_address' => '127.0.0.1',
            'created_at' => now()->subDays(20),
        ]);

        // 2 days old record
        \App\Models\PlatformTelemetry::create([
            'method' => 'GET',
            'url' => 'https://edusfera.by/recent-page',
            'status_code' => 200,
            'duration_ms' => 150,
            'ip_address' => '127.0.0.1',
            'created_at' => now()->subDays(2),
        ]);

        $this->artisan('telemetry:prune', ['--days' => 14])
            ->assertSuccessful();

        $this->assertDatabaseMissing('platform_telemetry', [
            'url' => 'https://edusfera.by/old-page',
        ]);

        $this->assertDatabaseHas('platform_telemetry', [
            'url' => 'https://edusfera.by/recent-page',
        ]);
    }
}
