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
        Schema::create('complaints', function (Blueprint $table) {
            $table->id();
            $table->string('complaint_id')->unique();
            $table->string('full_name');
            $table->string('contact_number');
            $table->text('address');
            $table->string('barangay');
            $table->string('municipality');
            $table->string('province')->default('Laguna');
            $table->enum('concern_type', ['Complaint', 'Request', 'Referral', 'Inquiry', 'Report']);
            $table->string('subject');
            $table->text('description');
            $table->enum('priority', ['High', 'Medium', 'Low'])->default('Medium');
            $table->enum('status', ['Pending', 'In Progress', 'Resolved', 'Referred', 'Closed'])->default('Pending');
            $table->string('assigned_office')->nullable();
            $table->string('assigned_personnel')->nullable();
            $table->text('remarks')->nullable();
            $table->date('date_filed');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('complaints');
    }
};
