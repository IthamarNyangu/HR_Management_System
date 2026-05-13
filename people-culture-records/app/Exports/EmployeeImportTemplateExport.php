<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class EmployeeImportTemplateExport implements FromArray, ShouldAutoSize, WithHeadings, WithTitle
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
            'gender',
            'date_of_birth',
            'national_id',
            'email',
            'phone',
            'project',
            'department',
            'job_title',
            'province',
            'district',
            'facility',
            'employment_status',
            'hire_date',
            'supervisor_name',
            'notes',
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
        return 'Employees_Import';
    }
}
