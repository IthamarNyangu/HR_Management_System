<?php

namespace Database\Seeders;

use App\Models\CaseStatus;
use App\Models\Department;
use App\Models\DocumentType;
use App\Models\EmploymentStatus;
use App\Models\JobTitle;
use App\Models\OffenceCategory;
use App\Models\AppointmentStatus;
use App\Models\AppointmentType;
use App\Models\PenaltyType;
use App\Models\Project;
use App\Models\PromotionType;
use App\Models\RelocationReason;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;

class MasterDataSeeder extends Seeder
{
    /**
     * @var array<class-string<Model>, array<int, array{name: string, code: string|null}>>
     */
    private array $values = [
        Project::class => [
            ['name' => 'General Operations', 'code' => 'GEN'],
        ],
        Department::class => [
            ['name' => 'People & Culture', 'code' => 'PC'],
            ['name' => 'Finance', 'code' => 'FIN'],
            ['name' => 'Programs', 'code' => 'PROG'],
        ],
        JobTitle::class => [
            ['name' => 'HR Officer', 'code' => 'HRO'],
            ['name' => 'HR Manager', 'code' => 'HRM'],
            ['name' => 'Program Officer', 'code' => 'PO'],
        ],
        EmploymentStatus::class => [
            ['name' => 'Active', 'code' => 'ACTIVE'],
            ['name' => 'On Leave', 'code' => 'LEAVE'],
            ['name' => 'Separated', 'code' => 'SEP'],
        ],
        OffenceCategory::class => [
            ['name' => 'Attendance', 'code' => 'ATT'],
            ['name' => 'Conduct', 'code' => 'COND'],
            ['name' => 'Performance', 'code' => 'PERF'],
        ],
        PenaltyType::class => [
            ['name' => 'Verbal Warning', 'code' => 'VW'],
            ['name' => 'Written Warning', 'code' => 'WW'],
            ['name' => 'Final Warning', 'code' => 'FW'],
        ],
        CaseStatus::class => [
            ['name' => 'Draft', 'code' => 'DRAFT'],
            ['name' => 'Submitted', 'code' => 'SUBMITTED'],
            ['name' => 'Active', 'code' => 'ACTIVE'],
            ['name' => 'Closed', 'code' => 'CLOSED'],
            ['name' => 'Archived', 'code' => 'ARCHIVED'],
        ],
        PromotionType::class => [
            ['name' => 'Merit', 'code' => 'MERIT'],
            ['name' => 'Acting Appointment', 'code' => 'ACTING'],
        ],
        RelocationReason::class => [
            ['name' => 'Operational Need', 'code' => 'OPS'],
            ['name' => 'Employee Request', 'code' => 'REQ'],
        ],
        AppointmentStatus::class => [
            ['name' => 'Draft', 'code' => 'DRAFT'],
            ['name' => 'Upcoming', 'code' => 'UPCOMING'],
            ['name' => 'Active', 'code' => 'ACTIVE'],
            ['name' => 'Completed', 'code' => 'COMPLETED'],
            ['name' => 'Cancelled', 'code' => 'CANCELLED'],
        ],
        AppointmentType::class => [
            ['name' => 'Acting Appointment', 'code' => 'ACTING'],
            ['name' => 'Temporary Assignment', 'code' => 'TEMP_ASSIGN'],
            ['name' => 'Secondment', 'code' => 'SECOND'],
            ['name' => 'Interim Appointment', 'code' => 'INTERIM'],
            ['name' => 'Short-term Appointment', 'code' => 'SHORT_TERM'],
        ],
        DocumentType::class => [
            ['name' => 'Letter', 'code' => 'LETTER'],
            ['name' => 'Approval', 'code' => 'APPROVAL'],
            ['name' => 'Supporting Document', 'code' => 'SUPPORT'],
        ],
    ];

    public function run(): void
    {
        foreach ($this->values as $model => $rows) {
            foreach ($rows as $row) {
                $model::updateOrCreate(
                    ['name' => $row['name']],
                    [
                        'code' => $row['code'],
                        'description' => null,
                        'is_active' => true,
                    ],
                );
            }
        }
    }
}
