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
            $table->renameColumn('photo_path', 'image_path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('violation_reports', function (Blueprint $table) {
            $table->renameColumn('image_path', 'photo_path');
        });
    }
};
