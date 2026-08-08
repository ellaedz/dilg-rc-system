<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class ReportNumberService
{
    public function next(?int $year = null): string
    {
        $year ??= (int) now()->year;

        return DB::transaction(function () use ($year) {
            DB::table('report_number_sequences')->insertOrIgnore([
                'year' => $year,
                'last_number' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('report_number_sequences')
                ->where('year', $year)
                ->increment('last_number', 1, ['updated_at' => now()]);

            $number = (int) DB::table('report_number_sequences')
                ->where('year', $year)
                ->value('last_number');

            return sprintf('RCV-%04d-%04d', $year, $number);
        }, 5);
    }
}
