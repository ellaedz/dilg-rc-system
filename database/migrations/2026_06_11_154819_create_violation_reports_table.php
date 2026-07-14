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
        Schema::create('violation_reports', function (Blueprint $table) {
            $table->id();
            $table->string('report_id')->unique();
            
            // Citizen information
            $table->string('submitted_by');
            $table->string('contact_number');
            
            // Violation content
            $table->text('description');
            $table->string('photo_path')->nullable();
            
            // GPS and location
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->decimal('gps_accuracy', 10, 2)->nullable();
            $table->timestamp('timestamp')->nullable();
            
            // Violation classification
            $table->string('selected_violation_type'); // User-selected type
            $table->string('predicted_violation_category')->nullable(); // AI prediction (Phase 3+)
            $table->decimal('confidence_score', 5, 2)->nullable(); // AI confidence (Phase 3+)
            
            // Barangay assignment (GPS-based)
            $table->string('detected_barangay')->nullable();
            $table->string('assigned_barangay_office')->nullable();
            $table->string('location_context')->nullable();
            
            // Status and workflow
            $table->string('status')->default('Submitted');
            $table->string('assigned_personnel')->nullable();
            $table->text('action_taken')->nullable();
            $table->text('remarks')->nullable();
            
            // Timestamps
            $table->date('date_submitted');
            $table->date('date_updated')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('violation_reports');
    }
};
