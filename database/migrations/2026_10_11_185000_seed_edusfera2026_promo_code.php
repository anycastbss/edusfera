<?php

declare(strict_types=1);

use App\Models\PromoCode;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        PromoCode::updateOrCreate(
            ['code' => 'EDUSFERA2026'],
            [
                'description' => 'Специальный промокод платформы: 3 месяца бесплатного доступа к тарифу Pro',
                'discount_type' => 'free_period',
                'discount_value' => 0.00,
                'scope' => 'all',
                'subscription_period' => '3_months',
                'subscription_plan' => 'pro',
                'is_active' => true,
                'max_uses' => null,
                'max_uses_per_user' => 1,
                'starts_at' => now()->subDay(),
                'expires_at' => now()->addYears(2),
            ]
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        PromoCode::where('code', 'EDUSFERA2026')->delete();
    }
};
