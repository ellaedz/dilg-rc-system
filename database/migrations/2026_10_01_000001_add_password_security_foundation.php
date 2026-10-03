<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->index();
            $table->boolean('must_change_password')->default(false)->index();
            $table->timestamp('password_changed_at')->nullable();
            $table->unsignedInteger('failed_login_attempts')->default(0);
            $table->timestamp('last_failed_login_at')->nullable();
            $table->timestamp('locked_until')->nullable()->index();
            $table->unsignedBigInteger('session_version')->default(1);
        });

        Schema::create('password_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('password_hash');
            $table->timestamp('created_at')->useCurrent();

            $table->index(['user_id', 'created_at']);
        });

        Schema::create('security_audit_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('target_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 64);
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 512)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['action', 'created_at']);
            $table->index(['actor_user_id', 'created_at']);
            $table->index(['target_user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('security_audit_events');
        Schema::dropIfExists('password_histories');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'is_active',
                'must_change_password',
                'password_changed_at',
                'failed_login_attempts',
                'last_failed_login_at',
                'locked_until',
                'session_version',
            ]);
        });
    }
};
