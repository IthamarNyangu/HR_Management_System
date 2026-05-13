<?php

namespace App\Exports;

use App\Exports\Concerns\BuildsReportSheets;

class StaffPromotionsReportExport extends BuildsReportSheets
{
    protected function type(): string
    {
        return 'promotions';
    }
}
