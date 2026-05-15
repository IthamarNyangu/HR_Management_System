<?php

namespace App\Exports;

use App\Models\ImportBatch;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class DisciplinaryCaseImportErrorReportExport implements FromCollection, ShouldAutoSize, WithHeadings, WithTitle
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
            'project',
            'province',
            'district',
            'facility',
            'nature_of_offence',
            'offence_category',
            'penalty_type',
            'case_status',
            'effective_date',
            'expiry_date',
            'errors',
            'warnings',
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
                    $raw['project'] ?? null,
                    $raw['province'] ?? null,
                    $raw['district'] ?? null,
                    $raw['facility'] ?? null,
                    $raw['nature_of_offence'] ?? null,
                    $raw['offence_category'] ?? null,
                    $raw['penalty_type'] ?? null,
                    $raw['case_status'] ?? null,
                    $raw['effective_date'] ?? null,
                    $raw['expiry_date'] ?? null,
                    implode('; ', $row->errors ?? []),
                    implode('; ', $row->warnings ?? []),
                ];
            });
    }

    public function title(): string
    {
        return 'Disciplinary_Case_Errors';
    }
}
