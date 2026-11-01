<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tutor_profiles', function (Blueprint $table) {
            $table->boolean('is_council_expert')->default(false)->after('is_white_label');
            $table->unsignedSmallInteger('council_number')->nullable()->after('is_council_expert');
            $table->string('council_certificate_number')->nullable()->after('council_number');
            $table->timestamp('council_joined_at')->nullable()->after('council_certificate_number');
        });
    }

    public function down(): void
    {
        Schema::table('tutor_profiles', function (Blueprint $table) {
            $table->dropColumn([
                'is_council_expert',
                'council_number',
                'council_certificate_number',
                'council_joined_at',
            ]);
        });
    }
};
