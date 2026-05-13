<?php

namespace App\Exports;

use App\Models\ImportBatch;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class EmployeeImportErrorReportExport implements FromCollection, ShouldAutoSize, WithHeadings, WithTitle
{
    public function __construct(private ImportBatch $batch)
    {
    }

    /**
     * @return list<string>
     */
    public function headings(): array
    {
        return [
            'row_number',
            'status',
            'employee_no',
            'first_name',
            'last_name',
            'email',
            'province',
            'district',
            'facility',
            'project',
            'department',
            'job_title',
            'employment_status',
            'errors',
        ];
    }

    public function collection(): Collection
    {
        return $this->batch->rows()
            ->whereIn('status', ['invalid', 'duplicate'])
            ->orderBy('row_number')
            ->get()
            ->map(function ($row) {
                $raw = $row->raw_data ?? [];

                return [
                    $row->row_number,
                    $row->status,
                    $raw['employee_no'] ?? null,
                    $raw['first_name'] ?? null,
                    $raw['last_name'] ?? null,
                    $raw['email'] ?? null,
                    $raw['province'] ?? null,
                    $raw['district'] ?? null,
                    $raw['facility'] ?? null,
                    $raw['project'] ?? null,
                    $raw['department'] ?? null,
                    $raw['job_title'] ?? null,
                    $raw['employment_status'] ?? null,
                    implode('; ', $row->errors ?? []),
                ];
            });
    }

    public function title(): string
    {
        return 'Employee_Import_Errors';
    }
}
