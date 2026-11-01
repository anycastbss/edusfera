<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlatformTelemetry extends Model
{
    public $timestamps = false;

    protected $table = 'platform_telemetry';

    protected $fillable = [
        'user_id',
        'user_role',
        'method',
        'url',
        'route',
        'status_code',
        'duration_ms',
        'db_query_count',
        'db_duration_ms',
        'memory_mb',
        'ip_address',
        'device_type',
        'browser',
        'os',
        'action_type',
        'event_name',
        'user_agent',
        'referer',
        'error_message',
        'metadata',
        'created_at',
    ];

    protected $casts = [
        'status_code' => 'integer',
        'duration_ms' => 'integer',
        'db_query_count' => 'integer',
        'db_duration_ms' => 'integer',
        'memory_mb' => 'float',
        'metadata' => 'array',
        'created_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeRecent(Builder $query, int $hours = 24): Builder
    {
        return $query->where('created_at', '>=', now()->subHours($hours));
    }

    public function scopeToday(Builder $query): Builder
    {
        return $query->where('created_at', '>=', now()->startOfDay());
    }

    public function scopeErrors(Builder $query): Builder
    {
        return $query->where('status_code', '>=', 400);
    }

    public function scopeSlow(Builder $query, int $thresholdMs = 500): Builder
    {
        return $query->where('duration_ms', '>=', $thresholdMs);
    }

    public function scopeByRole(Builder $query, string $role): Builder
    {
        return $query->where('user_role', $role);
    }

    public function getStatusBadgeColorAttribute(): string
    {
        return match (true) {
            $this->status_code >= 500 => 'danger',
            $this->status_code >= 400 => 'warning',
            $this->status_code >= 300 => 'info',
            default => 'success',
        };
    }

    public function getActionLabelAttribute(): string
    {
        return match ($this->action_type) {
            'pageview' => '📄 Просмотр страницы',
            'livewire' => '⚡ Livewire действие',
            'api' => '🔌 API запрос',
            'classroom' => '🎥 Видеокласс LiveKit',
            'ai' => '🤖 Запрос к ИИ',
            'auth' => '🔐 Авторизация',
            'payment' => '💳 Платежная операция',
            'beacon' => '📡 Web Vitals / Клиент',
            default => $this->action_type,
        };
    }

    public function getDeviceIconAttribute(): string
    {
        return match ($this->device_type) {
            'mobile' => '📱 Смартфон',
            'tablet' => '📟 Планшет',
            'bot' => '🤖 Бот / Краулер',
            default => '💻 Компьютер',
        };
    }
}
