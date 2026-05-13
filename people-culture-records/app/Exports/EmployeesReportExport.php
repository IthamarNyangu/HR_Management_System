<?php

namespace App\Exports;

use App\Exports\Concerns\BuildsReportSheets;

class EmployeesReportExport extends BuildsReportSheets
{
    protected function type(): string
    {
        return 'employees';
    }
}
