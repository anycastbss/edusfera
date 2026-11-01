<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tutor_profiles', function (Blueprint $table): void {
            $table->boolean('is_public')->default(true)->index()->after('diagnostic_supported');
            $table->boolean('is_white_label')->default(false)->after('is_public');
            $table->boolean('comfort_vision_mode')->default(false)->after('is_white_label');
        });
    }

    public function down(): void
    {
        Schema::table('tutor_profiles', function (Blueprint $table): void {
            $table->dropColumn(['is_public', 'is_white_label', 'comfort_vision_mode']);
        });
    }
};
