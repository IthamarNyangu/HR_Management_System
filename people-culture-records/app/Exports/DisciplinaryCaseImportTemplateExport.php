<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class DisciplinaryCaseImportTemplateExport implements FromArray, ShouldAutoSize, WithHeadings, WithTitle
{
    /**
     * @return list<string>
     */
    public function headings(): array
    {
        return [
            'employee_no',
            'first_name',
            'last_name',
            'project',
            'province',
            'district',
            'facility',
            'supervisor_name',
            'nature_of_offence',
            'offence_category',
            'penalty_type',
            'case_status',
            'effective_date',
            'expiry_date',
            'comment',
        ];
    }

    /**
     * @return array<int, array<int, string>>
     */
    public function array(): array
    {
        return [];
    }

    public function title(): string
    {
        return 'Disciplinary_Cases_Import';
    }
}
