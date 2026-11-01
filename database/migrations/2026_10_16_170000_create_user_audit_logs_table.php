<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('user_audit_logs')) {
            Schema::create('user_audit_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('user_email')->nullable()->index();
                $table->string('user_role')->nullable()->index();
                $table->string('action')->index(); // login_success, login_failed, logout, profile_update, tariff_change, etc.
                $table->string('category')->default('auth')->index(); // auth, profile, lesson, payment, security, system
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->json('details')->nullable();
                $table->string('status')->default('success')->index(); // success, failed, warning
                $table->timestamp('created_at')->useCurrent()->index();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('user_audit_logs');
    }
};
