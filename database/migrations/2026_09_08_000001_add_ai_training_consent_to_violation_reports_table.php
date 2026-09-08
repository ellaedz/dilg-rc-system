<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('violation_reports', function (Blueprint $table) {
            $table->boolean('ai_training_consent')->default(false)->index()
                ->after('citizen_reported_barangay');
            $table->timestamp('ai_training_consent_at')->nullable()
                ->after('ai_training_consent');
            $table->string('ai_training_notice_version', 20)->nullable()
                ->after('ai_training_consent_at');
        });
    }

    public function down(): void
    {
        Schema::table('violation_reports', function (Blueprint $table) {
            $table->dropIndex(['ai_training_consent']);
            $table->dropColumn([
                'ai_training_consent',
                'ai_training_consent_at',
                'ai_training_notice_version',
            ]);
        });
    }
};
