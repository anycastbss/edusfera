<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GeneratorOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_number',
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
    ];

    public function isPaid(): bool
    {
        return in_array($this->status, ['paid', 'completed', 'generating'], true) || ! empty($this->paid_at);
    }

    public function amountByn(): float
    {
        return round($this->amount_kopecks / 100, 2);
    }
}
