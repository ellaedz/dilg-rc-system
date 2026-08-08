<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('violation_reports', function (Blueprint $table) {
            $table->string('text_prediction')->nullable()->after('needs_manual_review');
            $table->decimal('text_confidence', 5, 4)->nullable()->after('text_prediction');
            $table->string('final_ai_prediction')->nullable()->after('text_confidence');
            $table->decimal('final_ai_confidence', 5, 4)->nullable()->after('final_ai_prediction');
            $table->string('ai_decision_source')->nullable()->after('final_ai_confidence');
            $table->boolean('ai_needs_manual_review')->default(true)->after('ai_decision_source');
            $table->string('ai_processing_status')->default('pending')->index()->after('ai_needs_manual_review');
            $table->timestamp('ai_processed_at')->nullable()->after('ai_processing_status');
            $table->string('ai_model_version')->nullable()->after('ai_processed_at');
            $table->json('ai_raw_response')->nullable()->after('ai_model_version');
        });
    }

    public function down(): void
    {
        Schema::table('violation_reports', function (Blueprint $table) {
            $table->dropIndex(['ai_processing_status']);
            $table->dropColumn([
                'text_prediction',
                'text_confidence',
                'final_ai_prediction',
                'final_ai_confidence',
                'ai_decision_source',
                'ai_needs_manual_review',
                'ai_processing_status',
                'ai_processed_at',
                'ai_model_version',
                'ai_raw_response',
            ]);
        });
    }
};
