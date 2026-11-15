<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class GeneratorOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_number',
        'access_token',
        'email',
        'subject',
        'topic',
        'variant_count',
        'amount_kopecks',
        'currency',
        'status',
        'alfa_order_id',
        'payment_method',
        'payload',
        'generated_content',
        'pdf_path',
        'paid_at',
    ];

    protected $casts = [
        'variant_count' => 'integer',
        'amount_kopecks' => 'integer',
        'payload' => 'array',
        'paid_at' => 'datetime',
        'legacy_access' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $order): void {
            if (empty($order->access_token)) {
                $order->access_token = Str::random(48);
            }
        });
    }

    public function isPaid(): bool
    {
        return in_array($this->status, ['paid', 'completed', 'generating'], true) || ! empty($this->paid_at);
    }

    public function hasValidAccessToken(?string $token): bool
    {
        return is_string($this->access_token)
            && $this->access_token !== ''
            && is_string($token)
            && $token !== ''
            && hash_equals($this->access_token, $token);
    }

    public function amountByn(): float
    {
        return round($this->amount_kopecks / 100, 2);
    }
}
