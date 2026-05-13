<?php

namespace App\Exports;

use App\Exports\Concerns\BuildsReportSheets;

class StaffRelocationsReportExport extends BuildsReportSheets
{
    protected function type(): string
    {
        return 'relocations';
    }
}
