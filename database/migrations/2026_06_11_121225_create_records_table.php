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
        Schema::create('records', function (Blueprint $table) {
            $table->id();
            $table->string('record_id')->unique();
            $table->string('full_name');
            $table->string('contact_number');
            $table->text('address');
            $table->string('concern_type'); // Changed from enum to string for Phase 2
            $table->text('description');
            $table->date('date_submitted');
            $table->string('status')->default('Submitted'); // Changed from enum to string
            $table->string('assigned_office')->nullable();
            $table->string('assigned_personnel')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('records');
    }
};
