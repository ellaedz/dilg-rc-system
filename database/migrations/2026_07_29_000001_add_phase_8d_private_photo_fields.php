<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('violation_reports', function (Blueprint $table) {
            $table->char('submission_payload_hash', 64)->nullable()->index()
                ->after('idempotency_key_hash');
            $table->string('photo_object_key')->nullable()->unique()
                ->after('image_path');
            $table->string('photo_pending_object_key')->nullable()->unique()
                ->after('photo_object_key');
            $table->string('photo_storage_disk')->nullable()
                ->after('photo_pending_object_key');
            $table->string('photo_mime_type', 32)->nullable()
                ->after('photo_storage_disk');
            $table->unsignedBigInteger('photo_size_bytes')->nullable()
                ->after('photo_mime_type');
            $table->unsignedInteger('photo_width')->nullable()
                ->after('photo_size_bytes');
            $table->unsignedInteger('photo_height')->nullable()
                ->after('photo_width');
            $table->char('photo_sha256', 64)->nullable()->index()
                ->after('photo_height');
            $table->unsignedInteger('photo_upload_attempts')->default(0)
                ->after('photo_upload_status');
            $table->string('photo_upload_error_code')->nullable()->index()
                ->after('photo_upload_attempts');
            $table->string('photo_upload_error_message')->nullable()
                ->after('photo_upload_error_code');
            $table->timestamp('photo_uploaded_at')->nullable()
                ->after('photo_upload_error_message');
            $table->char('photo_processing_token_hash', 64)->nullable()->index()
                ->after('photo_uploaded_at');
            $table->timestamp('photo_processing_started_at')->nullable()
                ->after('photo_processing_token_hash');
            $table->timestamp('photo_processing_expires_at')->nullable()->index()
                ->after('photo_processing_started_at');
            $table->string('photo_compensation_status')->nullable()
                ->after('photo_processing_expires_at');
        });
    }

    public function down(): void
    {
        Schema::table('violation_reports', function (Blueprint $table) {
            $table->dropUnique(['photo_object_key']);
            $table->dropUnique(['photo_pending_object_key']);
            $table->dropIndex(['submission_payload_hash']);
            $table->dropIndex(['photo_sha256']);
            $table->dropIndex(['photo_upload_error_code']);
            $table->dropIndex(['photo_processing_token_hash']);
            $table->dropIndex(['photo_processing_expires_at']);
            $table->dropColumn([
                'submission_payload_hash',
                'photo_object_key',
                'photo_pending_object_key',
                'photo_storage_disk',
                'photo_mime_type',
                'photo_size_bytes',
                'photo_width',
                'photo_height',
                'photo_sha256',
                'photo_upload_attempts',
                'photo_upload_error_code',
                'photo_upload_error_message',
                'photo_uploaded_at',
                'photo_processing_token_hash',
                'photo_processing_started_at',
                'photo_processing_expires_at',
                'photo_compensation_status',
            ]);
        });
    }
};
