<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('violation_reports', function (Blueprint $table) {
            // Add verification status field
            $table->string('verification_status')->default('Unverified')->after('status');
            
            // Add response tracking fields
            $table->timestamp('response_started_at')->nullable()->after('action_taken');
            $table->timestamp('resolved_at')->nullable()->after('response_started_at');
            $table->decimal('response_time_hours', 8, 2)->nullable()->after('resolved_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('violation_reports', function (Blueprint $table) {
            $table->dropColumn(['verification_status', 'response_started_at', 'resolved_at', 'response_time_hours']);
        });
    }
};
