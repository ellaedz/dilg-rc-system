<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('violation_reports', function (Blueprint $table) {
            $table->unsignedInteger('task_generation')->default(0)
                ->after('task_creation_status');
            $table->unsignedInteger('task_creation_attempts')->default(0)
                ->after('task_generation');
            $table->char('task_id_hash', 64)->nullable()->unique()
                ->after('task_creation_attempts');
            $table->char('task_creation_token_hash', 64)->nullable()->index()
                ->after('task_id_hash');
            $table->timestamp('task_creation_started_at')->nullable()
                ->after('task_creation_token_hash');
            $table->timestamp('task_creation_expires_at')->nullable()->index()
                ->after('task_creation_started_at');
            $table->timestamp('task_last_attempted_at')->nullable()
                ->after('task_creation_expires_at');
            $table->timestamp('task_created_at')->nullable()->index()
                ->after('task_last_attempted_at');
            $table->string('task_creation_error_code')->nullable()->index()
                ->after('task_created_at');
            $table->text('task_creation_error_message')->nullable()
                ->after('task_creation_error_code');
        });
    }

    public function down(): void
    {
        Schema::table('violation_reports', function (Blueprint $table) {
            $table->dropUnique(['task_id_hash']);
            $table->dropIndex(['task_creation_token_hash']);
            $table->dropIndex(['task_creation_expires_at']);
            $table->dropIndex(['task_created_at']);
            $table->dropIndex(['task_creation_error_code']);
            $table->dropColumn([
                'task_generation',
                'task_creation_attempts',
                'task_id_hash',
                'task_creation_token_hash',
                'task_creation_started_at',
                'task_creation_expires_at',
                'task_last_attempted_at',
                'task_created_at',
                'task_creation_error_code',
                'task_creation_error_message',
            ]);
        });
    }
};
