<?php

namespace App\Services\Reports;

use App\Models\CaseStatus;
use App\Models\Department;
use App\Models\DisciplinaryCase;
use App\Models\District;
use App\Models\Employee;
use App\Models\EmploymentStatus;
use App\Models\Facility;
use App\Models\JobTitle;
use App\Models\OffenceCategory;
use App\Models\PenaltyType;
use App\Models\Project;
use App\Models\PromotionType;
use App\Models\Province;
use App\Models\RelocationReason;
use App\Models\StaffPromotion;
use App\Models\StaffRelocation;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;
use Illuminate\Support\Collection;

class ReportQueryService
{
    /**
     * @return array<string, array<string, mixed>>
     */
    public function definitions(): array
    {
        return [
            'employees' => [
                'title' => 'Employee List Report',
                'route' => 'reports.employees',
                'excel_route' => 'reports.employees.export.excel',
                'pdf_route' => 'reports.employees.export.pdf',
                'description' => 'Staff register by location, project, job title, and employment status.',
                'columns' => [
                    'employee_no' => 'Employee No',
                    'employee_name' => 'Full Name',
                    'email' => 'Email',
                    'phone' => 'Phone',
                    'project' => 'Project',
                    'department' => 'Department',
                    'job_title' => 'Job Title',
                    'province' => 'Province',
                    'district' => 'District',
                    'facility' => 'Facility',
                    'employment_status' => 'Status',
                    'hire_date' => 'Hire Date',
                ],
            ],
            'disciplinary-cases' => [
                'title' => 'Disciplinary Cases Report',
                'route' => 'reports.disciplinary-cases',
                'excel_route' => 'reports.disciplinary-cases.export.excel',
                'pdf_route' => 'reports.disciplinary-cases.export.pdf',
                'description' => 'Case register by status, offence, penalty, dates, and employee.',
                'columns' => [
                    'reference_no' => 'Reference No',
                    'employee_no' => 'Employee No',
                    'employee_name' => 'Employee Name',
                    'province' => 'Province',
                    'district' => 'District',
                    'facility' => 'Facility',
                    'offence_category' => 'Offence Category',
                    'penalty_type' => 'Penalty Type',
                    'status' => 'Status',
                    'effective_date' => 'Effective Date',
                    'expiry_date' => 'Expiry Date',
                    'approved_by' => 'Approved By',
                    'closed_at' => 'Closed At',
                ],
            ],
            'promotions' => [
                'title' => 'Staff Promotions Report',
                'route' => 'reports.promotions',
                'excel_route' => 'reports.promotions.export.excel',
                'pdf_route' => 'reports.promotions.export.pdf',
                'description' => 'Promotion history by employee, job title, location, and date.',
                'columns' => [
                    'reference_no' => 'Reference No',
                    'employee_no' => 'Employee No',
                    'employee_name' => 'Employee Name',
                    'province' => 'Province',
                    'district' => 'District',
                    'facility' => 'Facility',
                    'old_job_title' => 'Old Job Title',
                    'new_job_title' => 'New Job Title',
                    'promotion_type' => 'Promotion Type',
                    'promotion_date' => 'Promotion Date',
                    'effective_date' => 'Effective Date',
                ],
            ],
            'relocations' => [
                'title' => 'Staff Relocations Report',
                'route' => 'reports.relocations',
                'excel_route' => 'reports.relocations.export.excel',
                'pdf_route' => 'reports.relocations.export.pdf',
                'description' => 'Relocation movement register by source, destination, amount, and date.',
                'columns' => [
                    'reference_no' => 'Reference No',
                    'employee_no' => 'Employee No',
                    'employee_name' => 'Employee Name',
                    'job_title' => 'Job Title',
                    'from_province' => 'From Province',
                    'from_district' => 'From District',
                    'from_facility' => 'From Facility',
                    'to_province' => 'To Province',
                    'to_district' => 'To District',
                    'to_facility' => 'To Facility',
                    'relocation_reason' => 'Reason',
                    'effective_date' => 'Effective Date',
                    'relocation_amount' => 'Amount',
                ],
            ],
            'expiring-cases' => [
                'title' => 'Expiring Disciplinary Cases Report',
                'route' => 'reports.expiring-cases',
                'excel_route' => 'reports.expiring-cases.export.excel',
                'pdf_route' => 'reports.expiring-cases.export.pdf',
                'description' => 'Active disciplinary cases approaching expiry.',
                'columns' => [
                    'reference_no' => 'Reference No',
                    'employee_no' => 'Employee No',
                    'employee_name' => 'Employee Name',
                    'province' => 'Province',
                    'district' => 'District',
                    'facility' => 'Facility',
                    'offence_category' => 'Offence Category',
                    'penalty_type' => 'Penalty Type',
                    'effective_date' => 'Effective Date',
                    'expiry_date' => 'Expiry Date',
                    'days_remaining' => 'Days Remaining',
                ],
            ],
            'archived-records' => [
                'title' => 'Archived Records Report',
                'route' => 'reports.archived-records',
                'excel_route' => 'reports.archived-records.export.excel',
                'pdf_route' => 'reports.archived-records.export.pdf',
                'description' => 'Archived employees and HR records across active modules.',
                'columns' => [
                    'module' => 'Module',
                    'reference' => 'Reference',
                    'employee_name' => 'Employee Name',
                    'province' => 'Province',
                    'archived_date' => 'Archived Date',
                    'archived_by' => 'Archived By',
                    'restore_url' => 'Restore Link',
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function definition(string $type): array
    {
        abort_unless(array_key_exists($type, $this->definitions()), 404);

        return $this->definitions()[$type];
    }

    /**
     * @param array<string, mixed> $filters
     */
    public function rows(string $type, User $user, array $filters = []): Collection
    {
        return match ($type) {
            'employees' => $this->employeesQuery($user, $filters)->get()->map(fn (Employee $employee) => $this->employeeRow($employee)),
            'disciplinary-cases' => $this->disciplinaryCasesQuery($user, $filters)->get()->map(fn (DisciplinaryCase $case) => $this->disciplinaryCaseRow($case)),
            'promotions' => $this->promotionsQuery($user, $filters)->get()->map(fn (StaffPromotion $promotion) => $this->promotionRow($promotion)),
            'relocations' => $this->relocationsQuery($user, $filters)->get()->map(fn (StaffRelocation $relocation) => $this->relocationRow($relocation)),
            'expiring-cases' => $this->expiringCasesQuery($user, $filters)->get()->map(fn (DisciplinaryCase $case) => $this->expiringCaseRow($case)),
            'archived-records' => $this->archivedRows($user, $filters),
            default => collect(),
        };
    }

    /**
     * @param array<string, mixed> $filters
     */
    public function paginate(string $type, User $user, array $filters = [], int $perPage = 5): LengthAwarePaginator
    {
        $rows = $this->rows($type, $user, $filters)->values();
        $page = Paginator::resolveCurrentPage();
        $items = $rows->slice(($page - 1) * $perPage, $perPage)->values();

        return new Paginator($items, $rows->count(), $perPage, $page, [
            'path' => request()->url(),
            'query' => request()->query(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function options(): array
    {
        return [
            'provinces' => Province::where('is_active', true)->orderBy('name')->get(),
            'districts' => District::where('is_active', true)->orderBy('name')->get(),
            'facilities' => Facility::where('is_active', true)->orderBy('name')->get(),
            'projects' => Project::where('is_active', true)->orderBy('name')->get(),
            'departments' => Department::where('is_active', true)->orderBy('name')->get(),
            'jobTitles' => JobTitle::where('is_active', true)->orderBy('name')->get(),
            'employmentStatuses' => EmploymentStatus::where('is_active', true)->orderBy('name')->get(),
            'offenceCategories' => OffenceCategory::where('is_active', true)->orderBy('name')->get(),
            'penaltyTypes' => PenaltyType::where('is_active', true)->orderBy('name')->get(),
            'caseStatuses' => CaseStatus::where('is_active', true)->orderBy('name')->get(),
            'promotionTypes' => PromotionType::where('is_active', true)->orderBy('name')->get(),
            'relocationReasons' => RelocationReason::where('is_active', true)->orderBy('name')->get(),
        ];
    }

    /**
     * @param array<string, mixed> $filters
     * @return array<string, string>
     */
    public function filterSummary(array $filters): array
    {
        return collect($filters)
            ->filter(fn ($value) => filled($value))
            ->mapWithKeys(fn ($value, $key) => [str_replace('_', ' ', (string) $key) => (string) $value])
            ->all();
    }

    /**
     * @param array<string, mixed> $filters
     */
    public function employeesQuery(User $user, array $filters): Builder
    {
        return Employee::query()
            ->with(['project', 'department', 'jobTitle', 'province', 'district', 'facility', 'employmentStatus'])
            ->visibleTo($user)
            ->when(filled($filters['search'] ?? null), function ($query) use ($filters) {
                $search = $filters['search'];
                $query->where(function ($query) use ($search) {
                    $query->where('employee_no', 'like', "%{$search}%")
                        ->orWhere('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->when(filled($filters['province_id'] ?? null), fn ($query) => $query->where('province_id', $filters['province_id']))
            ->when(filled($filters['district_id'] ?? null), fn ($query) => $query->where('district_id', $filters['district_id']))
            ->when(filled($filters['facility_id'] ?? null), fn ($query) => $query->where('facility_id', $filters['facility_id']))
            ->when(filled($filters['project_id'] ?? null), fn ($query) => $query->where('project_id', $filters['project_id']))
            ->when(filled($filters['department_id'] ?? null), fn ($query) => $query->where('department_id', $filters['department_id']))
            ->when(filled($filters['job_title_id'] ?? null), fn ($query) => $query->where('job_title_id', $filters['job_title_id']))
            ->when(filled($filters['employment_status_id'] ?? null), fn ($query) => $query->where('employment_status_id', $filters['employment_status_id']))
            ->orderBy('last_name')
            ->orderBy('first_name');
    }

    /**
     * @param array<string, mixed> $filters
     */
    public function disciplinaryCasesQuery(User $user, array $filters): Builder
    {
        return DisciplinaryCase::query()
            ->with(['employee', 'project', 'province', 'district', 'facility', 'offenceCategory', 'penaltyType', 'caseStatus', 'approvedBy'])
            ->visibleTo($user)
            ->when(filled($filters['search'] ?? null), fn ($query) => $this->caseSearch($query, $filters['search']))
            ->when(filled($filters['project_id'] ?? null), fn ($query) => $query->where('project_id', $filters['project_id']))
            ->when(filled($filters['province_id'] ?? null), fn ($query) => $query->where('province_id', $filters['province_id']))
            ->when(filled($filters['district_id'] ?? null), fn ($query) => $query->where('district_id', $filters['district_id']))
            ->when(filled($filters['facility_id'] ?? null), fn ($query) => $query->where('facility_id', $filters['facility_id']))
            ->when(filled($filters['offence_category_id'] ?? null), fn ($query) => $query->where('offence_category_id', $filters['offence_category_id']))
            ->when(filled($filters['penalty_type_id'] ?? null), fn ($query) => $query->where('penalty_type_id', $filters['penalty_type_id']))
            ->when(filled($filters['case_status_id'] ?? null), fn ($query) => $query->where('case_status_id', $filters['case_status_id']))
            ->when(filled($filters['effective_from'] ?? null), fn ($query) => $query->whereDate('effective_date', '>=', $filters['effective_from']))
            ->when(filled($filters['effective_to'] ?? null), fn ($query) => $query->whereDate('effective_date', '<=', $filters['effective_to']))
            ->when(filled($filters['expiry_from'] ?? null), fn ($query) => $query->whereDate('expiry_date', '>=', $filters['expiry_from']))
            ->when(filled($filters['expiry_to'] ?? null), fn ($query) => $query->whereDate('expiry_date', '<=', $filters['expiry_to']))
            ->latest('effective_date');
    }

    /**
     * @param array<string, mixed> $filters
     */
    public function expiringCasesQuery(User $user, array $filters): Builder
    {
        $filters['expiry_from'] = $filters['expiry_from'] ?? today()->toDateString();
        $filters['expiry_to'] = $filters['expiry_to'] ?? today()->addDays(30)->toDateString();
        $activeStatus = CaseStatus::where('code', 'ACTIVE')->orWhere('name', 'Active')->first();

        return $this->disciplinaryCasesQuery($user, $filters)
            ->when($activeStatus, fn ($query) => $query->where('case_status_id', $activeStatus->id))
            ->whereNotNull('expiry_date')
            ->orderBy('expiry_date');
    }

    /**
     * @param array<string, mixed> $filters
     */
    public function promotionsQuery(User $user, array $filters): Builder
    {
        return StaffPromotion::query()
            ->with(['employee', 'province', 'district', 'facility', 'project', 'department', 'oldJobTitle', 'newJobTitle', 'promotionType'])
            ->visibleTo($user)
            ->when(filled($filters['search'] ?? null), fn ($query) => $this->referenceEmployeeSearch($query, $filters['search']))
            ->when(filled($filters['province_id'] ?? null), fn ($query) => $query->where('province_id', $filters['province_id']))
            ->when(filled($filters['district_id'] ?? null), fn ($query) => $query->where('district_id', $filters['district_id']))
            ->when(filled($filters['facility_id'] ?? null), fn ($query) => $query->where('facility_id', $filters['facility_id']))
            ->when(filled($filters['project_id'] ?? null), fn ($query) => $query->where('project_id', $filters['project_id']))
            ->when(filled($filters['department_id'] ?? null), fn ($query) => $query->where('department_id', $filters['department_id']))
            ->when(filled($filters['old_job_title_id'] ?? null), fn ($query) => $query->where('old_job_title_id', $filters['old_job_title_id']))
            ->when(filled($filters['new_job_title_id'] ?? null), fn ($query) => $query->where('new_job_title_id', $filters['new_job_title_id']))
            ->when(filled($filters['promotion_type_id'] ?? null), fn ($query) => $query->where('promotion_type_id', $filters['promotion_type_id']))
            ->when(filled($filters['promotion_from'] ?? null), fn ($query) => $query->whereDate('promotion_date', '>=', $filters['promotion_from']))
            ->when(filled($filters['promotion_to'] ?? null), fn ($query) => $query->whereDate('promotion_date', '<=', $filters['promotion_to']))
            ->when(filled($filters['year'] ?? null), fn ($query) => $query->whereYear('promotion_date', $filters['year']))
            ->latest('promotion_date');
    }

    /**
     * @param array<string, mixed> $filters
     */
    public function relocationsQuery(User $user, array $filters): Builder
    {
        return StaffRelocation::query()
            ->with(['employee', 'jobTitle', 'project', 'department', 'fromProvince', 'fromDistrict', 'fromFacility', 'toProvince', 'toDistrict', 'toFacility', 'relocationReason'])
            ->visibleTo($user)
            ->when(filled($filters['search'] ?? null), fn ($query) => $this->referenceEmployeeSearch($query, $filters['search']))
            ->when(filled($filters['from_province_id'] ?? null), fn ($query) => $query->where('from_province_id', $filters['from_province_id']))
            ->when(filled($filters['from_district_id'] ?? null), fn ($query) => $query->where('from_district_id', $filters['from_district_id']))
            ->when(filled($filters['from_facility_id'] ?? null), fn ($query) => $query->where('from_facility_id', $filters['from_facility_id']))
            ->when(filled($filters['to_province_id'] ?? null), fn ($query) => $query->where('to_province_id', $filters['to_province_id']))
            ->when(filled($filters['to_district_id'] ?? null), fn ($query) => $query->where('to_district_id', $filters['to_district_id']))
            ->when(filled($filters['to_facility_id'] ?? null), fn ($query) => $query->where('to_facility_id', $filters['to_facility_id']))
            ->when(filled($filters['project_id'] ?? null), fn ($query) => $query->where('project_id', $filters['project_id']))
            ->when(filled($filters['department_id'] ?? null), fn ($query) => $query->where('department_id', $filters['department_id']))
            ->when(filled($filters['job_title_id'] ?? null), fn ($query) => $query->where('job_title_id', $filters['job_title_id']))
            ->when(filled($filters['relocation_reason_id'] ?? null), fn ($query) => $query->where('relocation_reason_id', $filters['relocation_reason_id']))
            ->when(filled($filters['effective_from'] ?? null), fn ($query) => $query->whereDate('effective_date', '>=', $filters['effective_from']))
            ->when(filled($filters['effective_to'] ?? null), fn ($query) => $query->whereDate('effective_date', '<=', $filters['effective_to']))
            ->when(filled($filters['relocation_amount_min'] ?? null), fn ($query) => $query->where('relocation_amount', '>=', $filters['relocation_amount_min']))
            ->when(filled($filters['relocation_amount_max'] ?? null), fn ($query) => $query->where('relocation_amount', '<=', $filters['relocation_amount_max']))
            ->when(filled($filters['year'] ?? null), fn ($query) => $query->whereYear('effective_date', $filters['year']))
            ->latest('effective_date');
    }

    private function caseSearch(Builder $query, string $search): void
    {
        $this->referenceEmployeeSearch($query, $search);
    }

    private function referenceEmployeeSearch(Builder $query, string $search): void
    {
        $query->where(function ($query) use ($search) {
            $query->where('reference_no', 'like', "%{$search}%")
                ->orWhereHas('employee', function ($query) use ($search) {
                    $query->where('employee_no', 'like', "%{$search}%")
                        ->orWhere('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%");
                });
        });
    }

    /**
     * @param array<string, mixed> $filters
     */
    private function archivedRows(User $user, array $filters): Collection
    {
        $module = $filters['module'] ?? null;
        $rows = collect();

        if (! $module || $module === 'employees') {
            $rows = $rows->merge(Employee::onlyTrashed()->with(['province', 'archivedBy'])->visibleTo($user)->get()->map(fn (Employee $employee) => [
                'module' => 'Employees',
                'reference' => $employee->employee_no,
                'employee_name' => $employee->full_name,
                'province' => $employee->province?->name ?? '-',
                'archived_date' => $this->formatDateTime($employee->deleted_at),
                'archived_by' => $employee->archivedBy?->name ?? '-',
                'restore_url' => $user->can('restore', $employee) ? route('employees.restore', $employee->id) : '',
                '_search' => "{$employee->employee_no} {$employee->full_name}",
                '_province_id' => $employee->province_id,
                '_deleted_at' => $employee->deleted_at,
            ]));
        }

        if (! $module || $module === 'disciplinary-cases') {
            $rows = $rows->merge(DisciplinaryCase::onlyTrashed()->with(['employee', 'province', 'archivedBy'])->visibleTo($user)->get()->map(fn (DisciplinaryCase $case) => [
                'module' => 'Disciplinary Cases',
                'reference' => $case->reference_no,
                'employee_name' => $case->employee?->full_name ?? '-',
                'province' => $case->province?->name ?? '-',
                'archived_date' => $this->formatDateTime($case->deleted_at),
                'archived_by' => $case->archivedBy?->name ?? '-',
                'restore_url' => $user->can('restore', $case) ? route('disciplinary-cases.restore', $case->id) : '',
                '_search' => "{$case->reference_no} {$case->employee?->employee_no} {$case->employee?->full_name}",
                '_province_id' => $case->province_id,
                '_deleted_at' => $case->deleted_at,
            ]));
        }

        if (! $module || $module === 'promotions') {
            $rows = $rows->merge(StaffPromotion::onlyTrashed()->with(['employee', 'province', 'archivedBy'])->visibleTo($user)->get()->map(fn (StaffPromotion $promotion) => [
                'module' => 'Staff Promotions',
                'reference' => $promotion->reference_no,
                'employee_name' => $promotion->employee?->full_name ?? '-',
                'province' => $promotion->province?->name ?? '-',
                'archived_date' => $this->formatDateTime($promotion->deleted_at),
                'archived_by' => $promotion->archivedBy?->name ?? '-',
                'restore_url' => $user->can('restore', $promotion) ? route('staff-promotions.restore', $promotion->id) : '',
                '_search' => "{$promotion->reference_no} {$promotion->employee?->employee_no} {$promotion->employee?->full_name}",
                '_province_id' => $promotion->province_id,
                '_deleted_at' => $promotion->deleted_at,
            ]));
        }

        if (! $module || $module === 'relocations') {
            $rows = $rows->merge(StaffRelocation::onlyTrashed()->with(['employee', 'fromProvince', 'archivedBy'])->visibleTo($user)->get()->map(fn (StaffRelocation $relocation) => [
                'module' => 'Staff Relocations',
                'reference' => $relocation->reference_no,
                'employee_name' => $relocation->employee?->full_name ?? '-',
                'province' => $relocation->fromProvince?->name ?? '-',
                'archived_date' => $this->formatDateTime($relocation->deleted_at),
                'archived_by' => $relocation->archivedBy?->name ?? '-',
                'restore_url' => $user->can('restore', $relocation) ? route('staff-relocations.restore', $relocation->id) : '',
                '_search' => "{$relocation->reference_no} {$relocation->employee?->employee_no} {$relocation->employee?->full_name}",
                '_province_id' => $relocation->from_province_id,
                '_deleted_at' => $relocation->deleted_at,
            ]));
        }

        return $rows
            ->when(filled($filters['province_id'] ?? null), fn ($rows) => $rows->where('_province_id', (int) $filters['province_id']))
            ->when(filled($filters['search'] ?? null), fn ($rows) => $rows->filter(fn ($row) => str_contains(strtolower($row['_search']), strtolower((string) $filters['search']))))
            ->when(filled($filters['archived_from'] ?? null), fn ($rows) => $rows->filter(fn ($row) => $row['_deleted_at']?->toDateString() >= $filters['archived_from']))
            ->when(filled($filters['archived_to'] ?? null), fn ($rows) => $rows->filter(fn ($row) => $row['_deleted_at']?->toDateString() <= $filters['archived_to']))
            ->sortByDesc('_deleted_at')
            ->map(fn ($row) => collect($row)->except(['_search', '_province_id', '_deleted_at'])->all())
            ->values();
    }

    /**
     * @return array<string, string>
     */
    private function employeeRow(Employee $employee): array
    {
        return [
            'employee_no' => $employee->employee_no,
            'employee_name' => $employee->full_name,
            'email' => $employee->email ?? '-',
            'phone' => $employee->phone ?? '-',
            'project' => $employee->project?->name ?? '-',
            'department' => $employee->department?->name ?? '-',
            'job_title' => $employee->jobTitle?->name ?? '-',
            'province' => $employee->province?->name ?? '-',
            'district' => $employee->district?->name ?? '-',
            'facility' => $employee->facility?->name ?? '-',
            'employment_status' => $employee->employmentStatus?->name ?? '-',
            'hire_date' => $this->formatDate($employee->hire_date),
        ];
    }

    private function disciplinaryCaseRow(DisciplinaryCase $case): array
    {
        return [
            'reference_no' => $case->reference_no,
            'employee_no' => $case->employee?->employee_no ?? '-',
            'employee_name' => $case->employee?->full_name ?? '-',
            'project' => $case->project?->name ?? '-',
            'province' => $case->province?->name ?? '-',
            'district' => $case->district?->name ?? '-',
            'facility' => $case->facility?->name ?? '-',
            'offence_category' => $case->offenceCategory?->name ?? '-',
            'penalty_type' => $case->penaltyType?->name ?? '-',
            'status' => $case->caseStatus?->name ?? '-',
            'effective_date' => $this->formatDate($case->effective_date),
            'expiry_date' => $this->formatDate($case->expiry_date),
            'approved_by' => $case->approvedBy?->name ?? '-',
            'closed_at' => $this->formatDateTime($case->closed_at),
        ];
    }

    private function expiringCaseRow(DisciplinaryCase $case): array
    {
        return [
            'reference_no' => $case->reference_no,
            'employee_no' => $case->employee?->employee_no ?? '-',
            'employee_name' => $case->employee?->full_name ?? '-',
            'province' => $case->province?->name ?? '-',
            'district' => $case->district?->name ?? '-',
            'facility' => $case->facility?->name ?? '-',
            'offence_category' => $case->offenceCategory?->name ?? '-',
            'penalty_type' => $case->penaltyType?->name ?? '-',
            'effective_date' => $this->formatDate($case->effective_date),
            'expiry_date' => $this->formatDate($case->expiry_date),
            'days_remaining' => $case->expiry_date ? now()->startOfDay()->diffInDays($case->expiry_date->startOfDay(), false) : '-',
        ];
    }

    private function promotionRow(StaffPromotion $promotion): array
    {
        return [
            'reference_no' => $promotion->reference_no,
            'employee_no' => $promotion->employee?->employee_no ?? '-',
            'employee_name' => $promotion->employee?->full_name ?? '-',
            'province' => $promotion->province?->name ?? '-',
            'district' => $promotion->district?->name ?? '-',
            'facility' => $promotion->facility?->name ?? '-',
            'old_job_title' => $promotion->oldJobTitle?->name ?? '-',
            'new_job_title' => $promotion->newJobTitle?->name ?? '-',
            'promotion_type' => $promotion->promotionType?->name ?? '-',
            'promotion_date' => $this->formatDate($promotion->promotion_date),
            'effective_date' => $this->formatDate($promotion->effective_date),
        ];
    }

    private function relocationRow(StaffRelocation $relocation): array
    {
        return [
            'reference_no' => $relocation->reference_no,
            'employee_no' => $relocation->employee?->employee_no ?? '-',
            'employee_name' => $relocation->employee?->full_name ?? '-',
            'job_title' => $relocation->jobTitle?->name ?? '-',
            'from_province' => $relocation->fromProvince?->name ?? '-',
            'from_district' => $relocation->fromDistrict?->name ?? '-',
            'from_facility' => $relocation->fromFacility?->name ?? '-',
            'to_province' => $relocation->toProvince?->name ?? '-',
            'to_district' => $relocation->toDistrict?->name ?? '-',
            'to_facility' => $relocation->toFacility?->name ?? '-',
            'relocation_reason' => $relocation->relocationReason?->name ?? '-',
            'effective_date' => $this->formatDate($relocation->effective_date),
            'relocation_amount' => $relocation->relocation_amount !== null ? number_format((float) $relocation->relocation_amount, 2) : '-',
        ];
    }

    private function formatDate($date): string
    {
        return $date ? $date->format('d M Y') : '-';
    }

    private function formatDateTime($date): string
    {
        return $date ? $date->format('d M Y H:i') : '-';
    }
}
