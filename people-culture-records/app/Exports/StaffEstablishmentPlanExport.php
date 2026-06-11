<?php

namespace App\Exports;

use App\Models\StaffEstablishmentPlan;
use App\Models\User;
use App\Services\StaffEstablishmentMetricsService;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class StaffEstablishmentPlanExport implements FromCollection, ShouldAutoSize, WithHeadings, WithTitle
{
    public function __construct(
        private StaffEstablishmentPlan $plan,
        private User $user,
        private StaffEstablishmentMetricsService $metrics,
    ) {}

    public function collection(): Collection
    {
        return collect($this->metrics->rowsForPlan($this->plan, $this->user))
            ->map(fn (array $row) => [
                $row['job_title'],
                $row['project'],
                $row['province'],
                $row['budgeted'],
                $row['filled'],
                $row['vacant'],
                $row['overstaffed'],
            ]);
    }

    public function headings(): array
    {
        return [
            'Job Title',
            'Project',
            'Province',
            'Budgeted Positions',
            'Filled Positions',
            'Vacancies',
            'Overstaffed',
        ];
    }

    public function title(): string
    {
        return 'Staff Establishment';
    }
}
