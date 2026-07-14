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
        Schema::create('report_timelines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_id')->constrained('violation_reports')->onDelete('cascade');
            $table->string('status');
            $table->string('old_status')->nullable();
            $table->text('remarks')->nullable();
            $table->text('action_taken')->nullable();
            $table->string('assigned_personnel')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable(); // User ID who made the update
            $table->timestamps();
            
            // Add index for faster queries
            $table->index('report_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('report_timelines');
    }
};
