<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscriptions', function (Blueprint $table): void {
            $table->unsignedInteger('ai_generations_used_this_month')->default(0)->after('responses_period');
            $table->string('ai_generations_period', 7)->nullable()->after('ai_generations_used_this_month');
        });
    }

    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table): void {
            $table->dropColumn(['ai_generations_used_this_month', 'ai_generations_period']);
        });
    }
};
