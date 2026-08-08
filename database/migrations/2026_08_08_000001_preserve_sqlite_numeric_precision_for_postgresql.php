<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        // SQLite does not enforce the decimal scales declared by Laravel. The
        // existing CIVICLEAR rows therefore contain legitimate values with
        // more precision than PostgreSQL would otherwise retain on import.
        DB::statement(
            'ALTER TABLE violation_reports
                ALTER COLUMN gps_accuracy TYPE numeric(20, 12)
                    USING gps_accuracy::numeric,
                ALTER COLUMN confidence_score TYPE numeric(7, 6)
                    USING confidence_score::numeric,
                ALTER COLUMN response_time_hours TYPE numeric(20, 12)
                    USING response_time_hours::numeric'
        );
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement(
            'ALTER TABLE violation_reports
                ALTER COLUMN gps_accuracy TYPE numeric(10, 2)
                    USING gps_accuracy::numeric(10, 2),
                ALTER COLUMN confidence_score TYPE numeric(5, 2)
                    USING confidence_score::numeric(5, 2),
                ALTER COLUMN response_time_hours TYPE numeric(8, 2)
                    USING response_time_hours::numeric(8, 2)'
        );
    }
};
