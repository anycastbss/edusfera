<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

class TrackUtmParametersTest extends TestCase
{
    public function test_utm_parameters_are_captured_in_session_and_cookie(): void
    {
        $response = $this->get('/?utm_source=threads&utm_medium=bio&utm_campaign=spring2026');

        $response->assertStatus(200);
        $response->assertSessionHas('utm');

        $utm = session('utm');
        $this->assertEquals('threads', $utm['source']);
        $this->assertEquals('bio', $utm['medium']);
        $this->assertEquals('spring2026', $utm['campaign']);

        $response->assertCookie('edusfera_utm');
    }
}
