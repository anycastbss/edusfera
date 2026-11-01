<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        try {
            if (Schema::connection('news')->hasTable('news_articles') && ! Schema::connection('news')->hasColumn('news_articles', 'category')) {
                Schema::connection('news')->table('news_articles', function (Blueprint $table) {
                    $table->string('category')->default('platform-training')->after('slug');
                });
            }
        } catch (\Throwable $e) {
            // Safe fallback if column already exists or table structure differs
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        try {
            if (Schema::connection('news')->hasTable('news_articles') && Schema::connection('news')->hasColumn('news_articles', 'category')) {
                Schema::connection('news')->table('news_articles', function (Blueprint $table) {
                    $table->dropColumn('category');
                });
            }
        } catch (\Throwable $e) {
            // Safe fallback
        }
    }
};
