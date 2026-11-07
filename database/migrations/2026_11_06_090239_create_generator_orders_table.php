<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('generator_orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique();
            $table->string('email')->index();
            $table->string('subject', 100);
            $table->string('topic', 255);
            $table->unsignedInteger('variant_count')->default(15);
            $table->unsignedInteger('amount_kopecks')->default(500); // 5.00 BYN
            $table->string('currency', 3)->default('BYN');
            $table->string('status', 32)->default('pending')->index(); // pending, paid, generating, completed, failed
            $table->string('alfa_order_id')->nullable()->index();
            $table->string('payment_method')->nullable();
            $table->json('payload')->nullable();
            $table->longText('generated_content')->nullable();
            $table->string('pdf_path')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('generator_orders');
    }
};
