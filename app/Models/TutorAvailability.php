<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TutorAvailability extends Model
{
    protected $table = 'tutor_availability';

    protected $fillable = [
        'user_id',
        'day_of_week',
        'start_time',
        'end_time',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'day_of_week' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public static function normalizeTimeString(?string $value): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        $parts = explode(':', trim($value));
        if (count($parts) >= 2) {
            $hours = str_pad($parts[0], 2, '0', STR_PAD_LEFT);
            $minutes = str_pad($parts[1], 2, '0', STR_PAD_LEFT);
            $seconds = isset($parts[2]) ? str_pad($parts[2], 2, '0', STR_PAD_LEFT) : '00';

            return "{$hours}:{$minutes}:{$seconds}";
        }

        return $value;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
