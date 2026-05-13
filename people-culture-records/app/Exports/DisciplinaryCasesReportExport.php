<?php

namespace App\Exports;

use App\Exports\Concerns\BuildsReportSheets;

class DisciplinaryCasesReportExport extends BuildsReportSheets
{
    protected function type(): string
    {
        return 'disciplinary-cases';
    }
}
