<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class HelpCallbackService
{
    /**
     * Handle incoming request for 5-minute phone onboarding setup.
     */
    public function requestCallback(User $user, ?string $phone = null): array
    {
        $resolvedPhone = trim((string) ($phone ?: $user->phone));

        if (! empty($phone) && $phone !== $user->phone) {
            $user->phone = $resolvedPhone;
            $user->saveQuietly();
        }

        $now = now()->timezone(config('booking.display_timezone', 'Europe/Minsk'))->format('d.m.Y H:i:s');

        // 1. Log request
        Log::channel('single')->info("🚨 [Tutor Setup Request] User #{$user->id} ({$user->name}) requested 5-min phone onboarding. Phone: {$resolvedPhone}");

        // 2. Dispatch Telegram notification if configured
        $telegramSent = $this->sendTelegramNotification($user, $resolvedPhone, $now);

        // 3. Notify Admin in Filament (if database notifications are enabled)
        try {
            $admins = User::query()->where('role', 'admin')->get();
            foreach ($admins as $admin) {
                Notification::make()
                    ->title('📞 Настройка репетитора за 5 минут')
                    ->body("Репетитор {$user->name} просит позвонить для настройки анкеты.\nТелефон: {$resolvedPhone}")
                    ->icon('heroicon-o-phone')
                    ->success()
                    ->sendToDatabase($admin);
            }
        } catch (\Throwable $e) {
            Log::warning('Could not send database notification for callback request: ' . $e->getMessage());
        }

        return [
            'success' => true,
            'phone' => $resolvedPhone,
            'telegram_sent' => $telegramSent,
            'message' => 'Спасибо! Мы уже набираем ваш номер. Наш методист позвонит вам в течение 5 минут и настроит всё под ключ.',
        ];
    }

    /**
     * Send notification to Telegram bot or webhook if env is configured.
     */
    protected function sendTelegramNotification(User $user, string $phone, string $time): bool
    {
        $botToken = env('TELEGRAM_BOT_TOKEN') ?: env('TELEGRAM_SUPPORT_BOT_TOKEN');
        $chatId = env('TELEGRAM_CHAT_ID') ?: env('TELEGRAM_SUPPORT_CHAT_ID');
        $webhookUrl = env('TELEGRAM_WEBHOOK_URL') ?: env('TELEGRAM_LEADS_WEBHOOK');

        $text = "🚨 *Запрос на настройку за 5 минут (Онбординг)*\n\n"
            . "👤 *Репетитор:* " . $this->escapeMarkdown($user->name) . "\n"
            . "📞 *Телефон:* `{$phone}`\n"
            . "✉️ *Email:* " . $this->escapeMarkdown($user->email) . "\n"
            . "🆔 *ID:* `{$user->id}`\n"
            . "⏰ *Время (Минск):* `{$time}`\n\n"
            . "👉 *Действие:* Перезвонить репетитору прямо сейчас и помочь с предметами, ставкой и расписанием!";

        if ($webhookUrl) {
            try {
                $response = Http::timeout(4)->post($webhookUrl, [
                    'text' => $text,
                    'user_id' => $user->id,
                    'name' => $user->name,
                    'phone' => $phone,
                    'email' => $user->email,
                ]);

                if ($response->successful()) {
                    return true;
                }
            } catch (\Throwable $e) {
                Log::warning('Telegram webhook call failed: ' . $e->getMessage());
            }
        }

        if ($botToken && $chatId) {
            try {
                $response = Http::timeout(4)->post("https://api.telegram.org/bot{$botToken}/sendMessage", [
                    'chat_id' => $chatId,
                    'text' => $text,
                    'parse_mode' => 'Markdown',
                ]);

                if ($response->successful()) {
                    return true;
                }
            } catch (\Throwable $e) {
                Log::warning('Telegram bot API sendMessage failed: ' . $e->getMessage());
            }
        }

        return false;
    }

    private function escapeMarkdown(string $text): string
    {
        return str_replace(['_', '*', '[', ']', '(', ')', '~', '`', '>', '#', '+', '-', '=', '|', '{', '}', '.', '!'], ' ', $text);
    }
}
