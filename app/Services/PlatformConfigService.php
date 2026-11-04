<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\File;

class PlatformConfigService
{
    protected string $filePath;

    public function __construct()
    {
        $this->filePath = storage_path('app/platform_settings.json');
    }

    public function getAll(): array
    {
        $defaults = $this->getDefaults();

        if (File::exists($this->filePath)) {
            $saved = json_decode(File::get($this->filePath), true) ?: [];
            return array_replace_recursive($defaults, $saved);
        }

        return $defaults;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $all = $this->getAll();
        return data_get($all, $key, $default);
    }

    public function set(string $key, mixed $value): void
    {
        $all = $this->getAll();
        data_set($all, $key, $value);
        $this->saveAll($all);
    }

    public function saveAll(array $settings): void
    {
        $dir = dirname($this->filePath);
        if (! File::isDirectory($dir)) {
            File::makeDirectory($dir, 0755, true);
        }

        File::put(
            $this->filePath,
            json_encode($settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );
    }

    public function getDefaults(): array
    {
        return [
            'maintenance' => [
                'enabled' => false,
                'message' => 'На платформе проводятся плановые технические работы. Мы скоро вернемся!',
                'allowed_ips' => '127.0.0.1',
            ],
            'webrtc' => [
                'livekit_host' => config('classroom.livekit_host', env('LIVEKIT_HOST', 'wss://edusfera-iae194s8.livekit.cloud')),
                'livekit_api_key' => config('classroom.livekit_api_key', env('LIVEKIT_API_KEY', '')),
                'livekit_api_secret' => config('classroom.livekit_api_secret', env('LIVEKIT_API_SECRET', '')),
                'turn_server_url' => config('classroom.turn.url', env('TURN_SERVER_URL', 'turn:turn.metered.ca:80')),
                'turn_api_key' => config('classroom.metered.api_key', env('METERED_API_KEY', '')),
                'max_lesson_duration' => 60,
                'auto_recording' => false,
            ],
            'ai' => [
                'provider' => 'google',
                'gemini_api_key' => config('services.gemini.api_key', env('GEMINI_API_KEY', '')),
                'model' => config('services.gemini.model', 'gemini-2.5-flash'),
                'temperature' => 0.7,
                'system_prompt' => "Ты — Майя, премиальный методический ИИ-помощник белорусской образовательной экосистемы Edusfera.by. Твоя специализация — подготовка школьников к ЦТ и ЦЭ по программе 2026 года.",
            ],
            'billing' => [
                'pro_monthly_price' => 40.00,
                'grace_period_days' => 5,
                'trial_days' => 14,
                'platform_commission_pct' => 10.0,
                'free_ai_generations_monthly' => 15,
                'pro_ai_generations_monthly' => 200,
            ],
            'integrations' => [
                'telegram_bot_token' => env('TELEGRAM_BOT_TOKEN', ''),
                'telegram_alert_chat_id' => env('TELEGRAM_ALERT_CHAT_ID', ''),
                'support_phone' => '+375 (29) 123-45-67',
                'support_telegram' => 'https://t.me/edusferaby',
            ],
        ];
    }
}
