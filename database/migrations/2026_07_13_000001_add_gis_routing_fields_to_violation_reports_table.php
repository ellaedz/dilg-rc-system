<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('violation_reports', function (Blueprint $table) {
            $table->boolean('municipality_validated')->default(false)->after('location_context');
            $table->string('municipality_name')->nullable()->after('municipality_validated');
            $table->string('barangay_detection_status')->default('not_checked')->after('municipality_name');
            $table->boolean('needs_manual_barangay_review')->default(false)->after('barangay_detection_status');
            $table->string('manually_assigned_barangay')->nullable()->after('needs_manual_barangay_review');
            $table->text('manual_assignment_reason')->nullable()->after('manually_assigned_barangay');
            $table->foreignId('manual_assignment_by')->nullable()->after('manual_assignment_reason')
                ->constrained('users')->nullOnDelete();
            $table->timestamp('manual_assignment_at')->nullable()->after('manual_assignment_by');

            $table->index(['needs_manual_barangay_review', 'manual_assignment_at'], 'violation_reports_manual_review_idx');
            $table->index('manually_assigned_barangay', 'violation_reports_manual_barangay_idx');
        });

        // The only current polygon is municipal. It must never be stored as a barangay.
        DB::table('violation_reports')
            ->whereIn('detected_barangay', ['Santa Cruz (Capital)', 'Santa Cruz'])
            ->update([
                'detected_barangay' => null,
                'assigned_barangay_office' => null,
                'municipality_validated' => true,
                'municipality_name' => 'Santa Cruz',
                'barangay_detection_status' => 'barangay_boundary_unavailable',
                'needs_manual_barangay_review' => true,
                'location_context' => 'Inside Santa Cruz; Needs Barangay Review',
            ]);
    }

    public function down(): void
    {
        Schema::table('violation_reports', function (Blueprint $table) {
            $table->dropForeign(['manual_assignment_by']);
            $table->dropIndex('violation_reports_manual_review_idx');
            $table->dropIndex('violation_reports_manual_barangay_idx');
            $table->dropColumn([
                'municipality_validated',
                'municipality_name',
                'barangay_detection_status',
                'needs_manual_barangay_review',
                'manually_assigned_barangay',
                'manual_assignment_reason',
                'manual_assignment_by',
                'manual_assignment_at',
            ]);
        });
    }
};
