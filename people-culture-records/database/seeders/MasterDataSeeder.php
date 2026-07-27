<?php

namespace Database\Seeders;

use App\Models\AppointmentStatus;
use App\Models\AppointmentType;
use App\Models\CaseStatus;
use App\Models\Department;
use App\Models\District;
use App\Models\DocumentType;
use App\Models\EmploymentStatus;
use App\Models\EmploymentType;
use App\Models\Facility;
use App\Models\JobTitle;
use App\Models\OffenceCategory;
use App\Models\PenaltyType;
use App\Models\Project;
use App\Models\PromotionType;
use App\Models\RelocationReason;
use App\Models\TerminationReason;
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
            ['name' => 'RTC Zamb - Action HIV', 'code' => 'ACTION-HIV'],
            ['name' => 'RTC Zambia-Right to Care', 'code' => 'RTC-RIGHT-CARE'],
        ],
        Department::class => [
            ['name' => 'People & Culture', 'code' => 'PC'],
            ['name' => 'Finance', 'code' => 'FIN'],
            ['name' => 'Programs', 'code' => 'PROG'],
            ['name' => 'IT', 'code' => 'IT'],
        ],
        JobTitle::class => [
            ['name' => 'HR Officer', 'code' => 'HRO'],
            ['name' => 'HR Manager', 'code' => 'HRM'],
            ['name' => 'Program Officer', 'code' => 'PO'],
        ],
        EmploymentStatus::class => [
            ['name' => 'Active', 'code' => 'ACTIVE'],
            ['name' => 'Terminated', 'code' => 'TERMINATED'],
        ],
        TerminationReason::class => [
            ['name' => 'Resignation', 'code' => 'RESIGNATION'],
            ['name' => 'End of Contract', 'code' => 'END_OF_CONTRACT'],
            ['name' => 'Deceased', 'code' => 'DECEASED'],
            ['name' => 'Redundancy', 'code' => 'REDUNDANCY'],
            ['name' => 'Dismissed', 'code' => 'DISMISSED'],
            ['name' => 'Discharged', 'code' => 'DISCHARGED'],
            ['name' => 'Ill Health', 'code' => 'ILL_HEALTH'],
        ],
        EmploymentType::class => [
            ['name' => 'Full-time', 'code' => 'FULL_TIME'],
            ['name' => 'Part-time', 'code' => 'PART_TIME'],
            ['name' => 'Fixed-term Contract', 'code' => 'FIXED_TERM'],
            ['name' => 'Temporary', 'code' => 'TEMPORARY'],
            ['name' => 'Internship', 'code' => 'INTERNSHIP'],
            ['name' => 'Consultancy', 'code' => 'CONSULTANCY'],
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
            ['name' => 'Permanent Promotion', 'code' => 'PERMANENT'],
            ['name' => 'Acting Promotion', 'code' => 'ACTING'],
        ],
        RelocationReason::class => [
            ['name' => 'Employee Request', 'code' => 'EMPLOYEE_REQUEST'],
            ['name' => 'Lateral Movement', 'code' => 'LATERAL_MOVEMENT'],
            ['name' => 'Temporal Movement', 'code' => 'TEMPORAL_MOVEMENT'],
            ['name' => 'Operation Movement', 'code' => 'OPERATION_MOVEMENT'],
            ['name' => 'Amount List', 'code' => 'AMOUNT_LIST'],
        ],
        AppointmentStatus::class => [
            ['name' => 'Draft', 'code' => 'DRAFT'],
            ['name' => 'Upcoming', 'code' => 'UPCOMING'],
            ['name' => 'Active', 'code' => 'ACTIVE'],
            ['name' => 'Completed', 'code' => 'COMPLETED'],
            ['name' => 'Cancelled', 'code' => 'CANCELLED'],
        ],
        AppointmentType::class => [
            ['name' => 'Interim / Acting Appointment', 'code' => 'INTERIM_ACTING'],
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

        EmploymentStatus::whereNotIn('name', ['Active', 'Terminated'])
            ->update(['is_active' => false]);

        AppointmentType::where('name', '!=', 'Interim / Acting Appointment')
            ->update(['is_active' => false]);

        PromotionType::whereNotIn('code', ['PERMANENT', 'ACTING'])
            ->update(['is_active' => false]);

        RelocationReason::whereNotIn('code', [
            'EMPLOYEE_REQUEST',
            'LATERAL_MOVEMENT',
            'TEMPORAL_MOVEMENT',
            'OPERATION_MOVEMENT',
            'AMOUNT_LIST',
        ])->update(['is_active' => false]);

        $lusakaDistrict = District::whereHas('province', fn ($query) => $query->where('name', 'Lusaka'))
            ->where('name', 'Lusaka')
            ->first();

        if ($lusakaDistrict) {
            Facility::updateOrCreate(
                [
                    'district_id' => $lusakaDistrict->id,
                    'name' => 'Lusaka Office',
                ],
                [
                    'code' => 'LUS-OFF',
                    'description' => null,
                    'is_active' => true,
                ],
            );
        }
    }
}
