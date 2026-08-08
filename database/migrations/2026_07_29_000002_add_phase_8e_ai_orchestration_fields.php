<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('violation_reports', function (Blueprint $table) {
            $table->unsignedInteger('ai_processing_attempts')->default(0)
                ->after('ai_processing_status');
            $table->uuid('ai_request_id')->nullable()->index()
                ->after('ai_processing_attempts');
            $table->char('ai_processing_token_hash', 64)->nullable()->index()
                ->after('ai_request_id');
            $table->timestamp('ai_processing_started_at')->nullable()
                ->after('ai_processing_token_hash');
            $table->timestamp('ai_processing_expires_at')->nullable()->index()
                ->after('ai_processing_started_at');
            $table->timestamp('ai_last_attempted_at')->nullable()
                ->after('ai_processing_expires_at');

            $table->string('ai_image_prediction')->nullable()
                ->after('ai_model_version');
            $table->decimal('ai_image_confidence', 7, 6)->nullable()
                ->after('ai_image_prediction');
            $table->string('ai_image_status')->nullable()
                ->after('ai_image_confidence');
            $table->json('ai_image_detections')->nullable()
                ->after('ai_image_status');
            $table->json('ai_gis_result')->nullable()
                ->after('ai_image_detections');
            $table->json('ai_model_metadata')->nullable()
                ->after('ai_gis_result');
            $table->json('ai_timing')->nullable()
                ->after('ai_model_metadata');
            $table->json('ai_manual_review_reasons')->nullable()
                ->after('ai_manual_review_reason');
        });
    }

    public function down(): void
    {
        Schema::table('violation_reports', function (Blueprint $table) {
            $table->dropIndex(['ai_request_id']);
            $table->dropIndex(['ai_processing_token_hash']);
            $table->dropIndex(['ai_processing_expires_at']);

            $table->dropColumn([
                'ai_processing_attempts',
                'ai_request_id',
                'ai_processing_token_hash',
                'ai_processing_started_at',
                'ai_processing_expires_at',
                'ai_last_attempted_at',
                'ai_image_prediction',
                'ai_image_confidence',
                'ai_image_status',
                'ai_image_detections',
                'ai_gis_result',
                'ai_model_metadata',
                'ai_timing',
                'ai_manual_review_reasons',
            ]);
        });
    }
};
