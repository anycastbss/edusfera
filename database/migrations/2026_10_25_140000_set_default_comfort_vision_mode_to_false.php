<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('tutor_profiles', 'comfort_vision_mode')) {
            Schema::table('tutor_profiles', function (Blueprint $table): void {
                $table->boolean('comfort_vision_mode')->default(false)->change();
            });
            DB::table('tutor_profiles')->where('comfort_vision_mode', true)->update(['comfort_vision_mode' => false]);
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('tutor_profiles', 'comfort_vision_mode')) {
            Schema::table('tutor_profiles', function (Blueprint $table): void {
                $table->boolean('comfort_vision_mode')->default(true)->change();
            });
        }
    }
};
