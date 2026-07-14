<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * This migration anonymizes all existing violation reports to comply with
     * Phase 4A.1 Anonymous Citizen Reporting requirements.
     */
    public function up(): void
    {
        // Step 1: Make contact_number nullable
        Schema::table('violation_reports', function (Blueprint $table) {
            $table->string('contact_number')->nullable()->change();
        });

        // Step 2: Update all existing reports to be anonymous
        DB::table('violation_reports')->update([
            'submitted_by' => 'Anonymous Citizen',
            'contact_number' => null, // Remove all contact numbers for privacy
        ]);

        // Step 3: Update timeline entries to show anonymous submissions
        DB::table('report_timelines')
            ->where('updated_by', '!=', 'Anonymous Citizen')
            ->where('status', 'Submitted')
            ->update([
                'updated_by' => 'Anonymous Citizen',
                'remarks' => 'Report submitted anonymously via mobile app',
            ]);

        echo "\n✅ All existing reports have been anonymized.\n";
        echo "   - contact_number field is now nullable\n";
        echo "   - submitted_by → 'Anonymous Citizen'\n";
        echo "   - contact_number → NULL\n";
        echo "   - Timeline entries updated\n\n";
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Cannot reverse this migration as original data is lost for privacy reasons
        echo "\n⚠️  Cannot reverse anonymization migration.\n";
        echo "   Original citizen data has been permanently removed for privacy compliance.\n\n";
    }
};
