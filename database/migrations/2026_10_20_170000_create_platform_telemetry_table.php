<?php

declare(strict_types=1);

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
        Schema::create('platform_telemetry', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('user_role', 30)->default('guest')->index();
            $table->string('method', 10)->index();
            $table->string('url', 500);
            $table->string('route', 100)->nullable()->index();
            $table->unsignedSmallInteger('status_code')->index();
            $table->unsignedInteger('duration_ms')->index();
            $table->unsignedSmallInteger('db_query_count')->default(0);
            $table->unsignedInteger('db_duration_ms')->default(0);
            $table->decimal('memory_mb', 6, 2)->default(0);
            $table->string('ip_address', 45)->index();
            $table->string('device_type', 20)->default('desktop')->index();
            $table->string('browser', 50)->default('Unknown')->index();
            $table->string('os', 50)->default('Unknown')->index();
            $table->string('action_type', 40)->default('pageview')->index();
            $table->string('event_name', 100)->nullable()->index();
            $table->text('user_agent')->nullable();
            $table->string('referer', 500)->nullable();
            $table->text('error_message')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->useCurrent()->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('platform_telemetry');
    }
};
