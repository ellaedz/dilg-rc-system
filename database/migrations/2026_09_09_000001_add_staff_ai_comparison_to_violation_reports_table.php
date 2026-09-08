<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('violation_reports', function (Blueprint $table) {
            $table->string('ai_prediction_at_verification')->nullable()->after('verified_at');
            $table->decimal('ai_confidence_at_verification', 5, 4)->nullable()->after('ai_prediction_at_verification');
            $table->boolean('staff_agreed_with_ai')->nullable()->index()->after('ai_confidence_at_verification');
            $table->text('staff_verification_reason')->nullable()->after('staff_agreed_with_ai');
        });
    }

    public function down(): void
    {
        Schema::table('violation_reports', function (Blueprint $table) {
            $table->dropIndex(['staff_agreed_with_ai']);
            $table->dropColumn([
                'ai_prediction_at_verification',
                'ai_confidence_at_verification',
                'staff_agreed_with_ai',
                'staff_verification_reason',
            ]);
        });
    }
};
