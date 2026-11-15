<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('generator_orders', function (Blueprint $table) {
            $table->string('access_token', 64)->nullable()->unique()->after('order_number');
            // Links emailed before this migration have no token; keep them working.
            $table->boolean('legacy_access')->default(false)->after('access_token');
        });

        // Backfill existing orders so their previously emailed links keep working.
        DB::table('generator_orders')
            ->whereNull('access_token')
            ->orderBy('id')
            ->chunkById(200, function ($orders) {
                foreach ($orders as $order) {
                    DB::table('generator_orders')
                        ->where('id', $order->id)
                        ->update([
                            'access_token' => Str::random(48),
                            'legacy_access' => true,
                        ]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('generator_orders', function (Blueprint $table) {
            $table->dropUnique(['access_token']);
            $table->dropColumn(['access_token', 'legacy_access']);
        });
    }
};
