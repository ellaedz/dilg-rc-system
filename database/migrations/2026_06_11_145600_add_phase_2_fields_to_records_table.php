<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('records', function (Blueprint $table) {
            // Add email field
            $table->string('email')->nullable()->after('contact_number');
            
            // Add photo evidence
            $table->string('photo_evidence')->nullable()->after('description');
            
            // Add GPS coordinates
            $table->decimal('latitude', 10, 8)->nullable()->after('photo_evidence');
            $table->decimal('longitude', 11, 8)->nullable()->after('latitude');
            $table->decimal('gps_accuracy', 10, 2)->nullable()->after('longitude');
            
            // Add barangay assignment (GPS-based)
            $table->string('detected_barangay')->nullable()->after('gps_accuracy');
            $table->string('assigned_barangay_office')->nullable()->after('detected_barangay');
            $table->string('location_name')->nullable()->after('assigned_barangay_office');
            
            // Add urgency level
            $table->string('urgency_level')->default('Medium')->after('location_name');
        });
        
        // For SQLite, we need to update existing enum values using raw SQL
        // Update concern_type column (convert to string for flexibility)
        DB::statement('UPDATE records SET concern_type = concern_type');
        
        // Update status column
        DB::statement('UPDATE records SET status = "Submitted" WHERE status = "Pending"');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('records', function (Blueprint $table) {
            $table->dropColumn([
                'email',
                'photo_evidence',
                'latitude',
                'longitude',
                'gps_accuracy',
                'detected_barangay',
                'assigned_barangay_office',
                'location_name',
                'urgency_level'
            ]);
        });
    }
};
