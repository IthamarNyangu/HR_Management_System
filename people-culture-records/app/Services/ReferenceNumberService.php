<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class ReferenceNumberService
{
    public function generate(string $prefix, string $table, string $column = 'reference_no'): string
    {
        $year = now()->year;
        $needle = "{$prefix}-{$year}-%";

        $latest = DB::table($table)
            ->where($column, 'like', $needle)
            ->lockForUpdate()
            ->orderByDesc($column)
            ->value($column);

        $next = 1;

        if (is_string($latest) && preg_match('/-(\d+)$/', $latest, $matches) === 1) {
            $next = ((int) $matches[1]) + 1;
        }

        return sprintf('%s-%s-%04d', $prefix, $year, $next);
    }

    public function generateRtczVacancyNumber(string $table = 'job_openings', string $column = 'reference_no'): string
    {
        $prefix = 'RTCZ'.now()->format('y');
        $needle = "{$prefix}-%";

        $latest = DB::table($table)
            ->where($column, 'like', $needle)
            ->lockForUpdate()
            ->orderByDesc($column)
            ->value($column);

        $next = 1;

        if (is_string($latest) && preg_match('/-(\d+)$/', $latest, $matches) === 1) {
            $next = ((int) $matches[1]) + 1;
        }

        return sprintf('%s-%03d', $prefix, $next);
    }
}
