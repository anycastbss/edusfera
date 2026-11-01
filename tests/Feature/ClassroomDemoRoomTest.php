<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

class ClassroomDemoRoomTest extends TestCase
{
    public function test_demo_room_is_accessible_without_authentication(): void
    {
        $response = $this->get('/demo-room');

        $response->assertOk();
        $response->assertSee('Виртуальный класс PRO (Демо-песочница) — Edusfera');
        $response->assertSee('Песочница PRO');
        $response->assertSee('"isDemo":true');
        $response->assertSee('Теорема Пифагора');
    }

    public function test_demo_ai_chat_returns_geometry_tasks(): void
    {
        $response = $this->postJson('/demo-room/ai-chat', [
            'message' => 'Сгенерировать 5 задач по геометрии',
        ]);

        $response->assertOk();
        $response->assertJsonStructure(['reply']);
        $this->assertStringContainsString('5 задач по геометрии', $response->json('reply'));
        $this->assertStringContainsString('Прямоугольный треугольник', $response->json('reply'));
    }

    public function test_demo_ai_chat_returns_ct_test_variant(): void
    {
        $response = $this->postJson('/demo-room/ai-chat', [
            'message' => 'Составить вариант теста под ЦТ',
        ]);

        $response->assertOk();
        $response->assertJsonStructure(['reply']);
        $this->assertStringContainsString('Часть А', $response->json('reply'));
        $this->assertStringContainsString('Часть Б', $response->json('reply'));
    }

    public function test_demo_ai_chat_returns_lesson_plan(): void
    {
        $response = $this->postJson('/demo-room/ai-chat', [
            'message' => 'План урока на 45 минут',
        ]);

        $response->assertOk();
        $response->assertJsonStructure(['reply']);
        $this->assertStringContainsString('Поурочный план занятия (45 минут)', $response->json('reply'));
    }
}
