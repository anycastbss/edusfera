<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserAuditLog extends Model
{
    public $timestamps = false;

    protected $table = 'user_audit_logs';

    protected $fillable = [
        'user_id',
        'user_email',
        'user_role',
        'action',
        'category',
        'ip_address',
        'user_agent',
        'details',
        'status',
        'created_at',
    ];

    protected $casts = [
        'details' => 'array',
        'created_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getActionLabelAttribute(): string
    {
        return match ($this->action) {
            'login_success', 'login' => '🟢 Вход в систему',
            'login_failed' => '🔴 Ошибка авторизации (Сбой пароля)',
            'logout' => '🚪 Выход из аккаунта',
            'lesson_created' => '📅 Забронирован новый урок ' . ($this->details['lesson_id'] ? '#' . $this->details['lesson_id'] : ''),
            'lesson_confirmed' => '✅ Урок подтвержден репетитором',
            'lesson_completed' => '🎓 Урок успешно завершен',
            'lesson_cancelled' => '❌ Урок отменен',
            'classroom_opened', 'classroom_joined' => '🎥 Подключение к видеоклассу LiveKit',
            'classroom_ended' => '🏁 Завершение онлайн-урока в классе',
            'payment_recorded', 'payment_success' => '💳 Оплата ' . ($this->details['amount'] ?? '') . ' BYN',
            'subscription_activated' => '⭐ Активация подписки Pro',
            'profile_updated' => '👤 Обновление профиля преподавателя',
            'dispute_opened' => '⚖️ Открыт спор в арбитраже',
            'system_optimized' => '⚡ Сброс кэша и оптимизация DevOps',
            'settings_updated' => '🛠️ Изменение настроек платформы',
            default => $this->action,
        };
    }

    public function getDeviceLabelAttribute(): string
    {
        $ua = (string) $this->user_agent;
        if (empty($ua)) {
            return '—';
        }

        $platform = 'PC';
        if (str_contains($ua, 'iPhone') || str_contains($ua, 'iPad')) {
            $platform = 'iOS';
        } elseif (str_contains($ua, 'Android')) {
            $platform = 'Android';
        } elseif (str_contains($ua, 'Macintosh') || str_contains($ua, 'Mac OS')) {
            $platform = 'macOS';
        } elseif (str_contains($ua, 'Windows')) {
            $platform = 'Windows';
        } elseif (str_contains($ua, 'Linux')) {
            $platform = 'Linux';
        }

        $browser = 'Браузер';
        if (str_contains($ua, 'Edg')) {
            $browser = 'Edge';
        } elseif (str_contains($ua, 'Chrome')) {
            $browser = 'Chrome';
        } elseif (str_contains($ua, 'Safari')) {
            $browser = 'Safari';
        } elseif (str_contains($ua, 'Firefox')) {
            $browser = 'Firefox';
        }

        return "{$browser} ({$platform})";
    }

    public static function log(
        string $action,
        string $category = 'system',
        ?User $user = null,
        ?string $status = 'success',
        array $details = []
    ): self {
        $currentUser = $user ?? auth()->user();
        $request = request();

        return self::create([
            'user_id' => $currentUser?->id,
            'user_email' => $currentUser?->email ?? ($details['email'] ?? null),
            'user_role' => $currentUser?->role instanceof \BackedEnum ? $currentUser->role->value : ($currentUser?->role ?? 'guest'),
            'action' => $action,
            'category' => $category,
            'ip_address' => $request?->ip() ?? '127.0.0.1',
            'user_agent' => $request ? substr((string) $request->userAgent(), 0, 500) : null,
            'details' => $details,
            'status' => $status,
            'created_at' => now(),
        ]);
    }
}
