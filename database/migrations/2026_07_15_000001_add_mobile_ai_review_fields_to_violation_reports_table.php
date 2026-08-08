<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('violation_reports', function (Blueprint $table) {
            $table->string('image_validation_status')->nullable()->after('confidence_score');
            $table->string('image_model_version')->nullable()->after('image_validation_status');
            $table->boolean('needs_manual_review')->default(false)->after('image_model_version');
        });
    }

    public function down(): void
    {
        Schema::table('violation_reports', function (Blueprint $table) {
            $table->dropColumn([
                'image_validation_status',
                'image_model_version',
                'needs_manual_review',
            ]);
        });
    }
};
