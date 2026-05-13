<?php

namespace App\Exports;

use App\Exports\Concerns\BuildsReportSheets;

class ExpiringCasesReportExport extends BuildsReportSheets
{
    protected function type(): string
    {
        return 'expiring-cases';
    }
}
