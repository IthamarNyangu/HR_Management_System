<?php

namespace App\Exports;

use App\Exports\Concerns\BuildsReportSheets;

class ArchivedRecordsReportExport extends BuildsReportSheets
{
    protected function type(): string
    {
        return 'archived-records';
    }
}
