<?php

namespace Tests\Feature;

use App\Exports\EmployeesReportExport;
use App\Models\CaseStatus;
use App\Models\DisciplinaryCase;
use App\Models\District;
use App\Models\Employee;
use App\Models\EmploymentStatus;
use App\Models\Facility;
use App\Models\JobTitle;
use App\Models\OffenceCategory;
use App\Models\PenaltyType;
use App\Models\Province;
use App\Models\Role;
use App\Models\StaffRelocation;
use App\Models\TerminationReason;
use App\Models\User;
use App\Services\Reports\ReportQueryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class ReportsAndExportsTest extends TestCase
{
    use RefreshDatabase;

    private Role $adminRole;
    private Role $managerRole;
    private Role $officerRole;
    private Role $viewerRole;
    private Province $northern;
    private Province $luapula;
    private Province $muchinga;
    private District $kasama;
    private District $mansa;
    private District $chinsali;
    private Facility $kasamaFacility;
    private Facility $mansaFacility;
    private JobTitle $jobTitle;
    private Employee $northernEmployee;
    private Employee $luapulaEmployee;
    private Employee $muchingaEmployee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminRole = Role::create(['name' => 'Admin', 'code' => 'ADMIN', 'is_active' => true]);
        $this->managerRole = Role::create(['name' => 'HR Manager', 'code' => 'HRM', 'is_active' => true]);
        $this->officerRole = Role::create(['name' => 'HR Officer', 'code' => 'HRO', 'is_active' => true]);
        $this->viewerRole = Role::create(['name' => 'Viewer', 'code' => 'VIEWER', 'is_active' => true]);

        $this->northern = Province::create(['name' => 'Northern', 'code' => 'NOR', 'is_active' => true]);
        $this->luapula = Province::create(['name' => 'Luapula', 'code' => 'LUA', 'is_active' => true]);
        $this->muchinga = Province::create(['name' => 'Muchinga', 'code' => 'MUC', 'is_active' => true]);

        $this->kasama = District::create(['province_id' => $this->northern->id, 'name' => 'Kasama', 'code' => 'NOR-KAS', 'is_active' => true]);
        $this->mansa = District::create(['province_id' => $this->luapula->id, 'name' => 'Mansa', 'code' => 'LUA-MAN', 'is_active' => true]);
        $this->chinsali = District::create(['province_id' => $this->muchinga->id, 'name' => 'Chinsali', 'code' => 'MUC-CHI', 'is_active' => true]);

        $this->kasamaFacility = Facility::create(['district_id' => $this->kasama->id, 'name' => 'Kasama Clinic', 'code' => 'KAS', 'is_active' => true]);
        $this->mansaFacility = Facility::create(['district_id' => $this->mansa->id, 'name' => 'Mansa Clinic', 'code' => 'MAN', 'is_active' => true]);
        $this->jobTitle = JobTitle::create(['name' => 'HR Officer', 'code' => 'HRO-JT', 'is_active' => true]);

        $this->northernEmployee = $this->employee('NOR-001', $this->northern, $this->kasama, $this->kasamaFacility);
        $this->luapulaEmployee = $this->employee('LUA-001', $this->luapula, $this->mansa, $this->mansaFacility);
        $this->muchingaEmployee = $this->employee('MUC-001', $this->muchinga, $this->chinsali, null);
    }

    public function test_unauthenticated_users_cannot_access_reports(): void
    {
        $this->get(route('reports.index'))->assertRedirect('/login');
    }

    public function test_admin_and_hr_manager_can_access_reports(): void
    {
        $this->actingAs($this->user($this->adminRole))
            ->get(route('reports.index'))
            ->assertOk()
            ->assertSee('Reports & Exports');

        $this->actingAs($this->user($this->managerRole))
            ->get(route('reports.employees'))
            ->assertOk()
            ->assertSee('Employee List Report');
    }

    public function test_hr_officer_report_data_is_limited_to_assigned_province(): void
    {
        $this->actingAs($this->user($this->officerRole, $this->northern))
            ->get(route('reports.employees'))
            ->assertOk()
            ->assertSee('NOR-001')
            ->assertDontSee('LUA-001');
    }

    public function test_viewer_report_data_is_limited_to_assigned_province(): void
    {
        $this->actingAs($this->user($this->viewerRole, $this->luapula))
            ->get(route('reports.employees'))
            ->assertOk()
            ->assertSee('LUA-001')
            ->assertDontSee('NOR-001');
    }

    public function test_relocation_report_respects_from_to_province_visibility(): void
    {
        $this->relocation('REL-2026-0001', $this->northernEmployee, $this->northern, $this->kasama, $this->luapula, $this->mansa);
        $this->relocation('REL-2026-0002', $this->luapulaEmployee, $this->luapula, $this->mansa, $this->northern, $this->kasama);
        $this->relocation('REL-2026-0003', $this->muchingaEmployee, $this->muchinga, $this->chinsali, $this->luapula, $this->mansa);

        $this->actingAs($this->user($this->officerRole, $this->northern))
            ->get(route('reports.relocations'))
            ->assertOk()
            ->assertSee('REL-2026-0001')
            ->assertSee('REL-2026-0002')
            ->assertDontSee('REL-2026-0003');
    }

    public function test_filtered_report_links_preserve_query_parameters(): void
    {
        foreach (range(1, 18) as $number) {
            $this->employee("NOR-{$number}", $this->northern, $this->kasama, $this->kasamaFacility);
        }

        $this->actingAs($this->user($this->adminRole))
            ->get(route('reports.employees', ['search' => 'NOR']))
            ->assertOk()
            ->assertSee('search=NOR')
            ->assertSee(route('reports.employees.export.excel', ['search' => 'NOR']), false);
    }

    public function test_employee_module_export_buttons_follow_current_results(): void
    {
        $admin = $this->user($this->adminRole);

        $this->actingAs($admin)
            ->get(route('employees.index', ['search' => 'NOR-001']))
            ->assertOk()
            ->assertSee('Export Excel')
            ->assertSee('Export PDF')
            ->assertSee(route('reports.employees.export.excel', ['search' => 'NOR-001']), false)
            ->assertSee(route('reports.employees.export.pdf', ['search' => 'NOR-001']), false);

        $this->actingAs($admin)
            ->get(route('employees.index', ['search' => 'NO-MATCH']))
            ->assertOk()
            ->assertSee('Export Excel')
            ->assertSee('Export PDF')
            ->assertDontSee(route('reports.employees.export.excel', ['search' => 'NO-MATCH']), false)
            ->assertDontSee(route('reports.employees.export.pdf', ['search' => 'NO-MATCH']), false);
    }

    public function test_excel_export_route_works_and_logs_activity(): void
    {
        Excel::fake();

        $admin = $this->user($this->adminRole);

        $this->actingAs($admin)
            ->get(route('reports.employees.export.excel', ['search' => 'NOR']))
            ->assertOk();

        Excel::assertDownloaded('employee-list-report-'.now()->format('Ymd-His').'.xlsx');

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'report_exported_excel',
            'user_id' => $admin->id,
        ]);
    }

    public function test_excel_export_uses_filters(): void
    {
        $export = new EmployeesReportExport(app(ReportQueryService::class), $this->user($this->adminRole), ['search' => 'NOR-001']);

        $rows = $export->collection();

        $this->assertCount(1, $rows);
        $this->assertSame('NOR-001', $rows->first()[0]);
    }

    public function test_employee_report_includes_termination_details(): void
    {
        $terminatedStatus = EmploymentStatus::firstOrCreate(
            ['code' => 'TERMINATED'],
            ['name' => 'Terminated', 'is_active' => true],
        );
        $reason = TerminationReason::firstOrCreate(
            ['code' => 'RESIGNATION'],
            ['name' => 'Resignation', 'is_active' => true],
        );

        $this->northernEmployee->update([
            'employment_status_id' => $terminatedStatus->id,
            'termination_reason_id' => $reason->id,
            'termination_date' => '2026-06-06',
            'termination_comment' => 'Employee resigned after notice period.',
        ]);

        $reports = app(ReportQueryService::class);
        $row = $reports->rows('employees', $this->user($this->adminRole), ['search' => 'NOR-001'])->first();
        $export = new EmployeesReportExport($reports, $this->user($this->adminRole), ['search' => 'NOR-001']);

        $this->assertSame('06 Jun 2026', $row['termination_date']);
        $this->assertSame('Resignation', $row['termination_reason']);
        $this->assertSame('Employee resigned after notice period.', $row['termination_comment']);
        $this->assertContains('Termination Date', $export->headings());
        $this->assertContains('Termination Reason', $export->headings());
        $this->assertContains('Termination Comment', $export->headings());
    }

    public function test_pdf_export_route_works_and_logs_activity(): void
    {
        $admin = $this->user($this->adminRole);

        $this->actingAs($admin)
            ->get(route('reports.employees.export.pdf', ['search' => 'NOR-001']))
            ->assertOk();

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'report_exported_pdf',
            'user_id' => $admin->id,
        ]);
    }

    public function test_archived_records_report_includes_soft_deleted_records(): void
    {
        $this->northernEmployee->update(['archived_by' => $this->user($this->adminRole)->id]);
        $this->northernEmployee->delete();

        $this->actingAs($this->user($this->adminRole))
            ->get(route('reports.archived-records'))
            ->assertOk()
            ->assertSee('Archived Records Report')
            ->assertSee('NOR-001');
    }

    public function test_expiring_cases_report_defaults_to_next_30_days(): void
    {
        $active = CaseStatus::create(['name' => 'Active', 'code' => 'ACTIVE', 'is_active' => true]);
        $offence = OffenceCategory::create(['name' => 'Attendance', 'code' => 'ATT', 'is_active' => true]);
        $penalty = PenaltyType::create(['name' => 'Warning', 'code' => 'WARN', 'is_active' => true]);

        $this->case('DC-2026-0001', $active, $offence, $penalty, now()->addDays(10)->toDateString());
        $this->case('DC-2026-0002', $active, $offence, $penalty, now()->addDays(45)->toDateString());

        $this->actingAs($this->user($this->adminRole))
            ->get(route('reports.expiring-cases'))
            ->assertOk()
            ->assertSee('DC-2026-0001')
            ->assertDontSee('DC-2026-0002');
    }

    private function user(Role $role, ?Province $province = null): User
    {
        return User::factory()->create([
            'role_id' => $role->id,
            'province_id' => $province?->id,
            'is_active' => true,
        ]);
    }

    private function employee(string $employeeNo, Province $province, District $district, ?Facility $facility): Employee
    {
        return Employee::create([
            'employee_no' => $employeeNo,
            'first_name' => str($employeeNo)->before('-')->toString(),
            'last_name' => 'Employee',
            'email' => strtolower($employeeNo).'@hr.test',
            'phone' => '260000000',
            'province_id' => $province->id,
            'district_id' => $district->id,
            'facility_id' => $facility?->id,
            'job_title_id' => $this->jobTitle->id,
        ]);
    }

    private function relocation(string $reference, Employee $employee, Province $fromProvince, District $fromDistrict, Province $toProvince, District $toDistrict): StaffRelocation
    {
        return StaffRelocation::create([
            'reference_no' => $reference,
            'employee_id' => $employee->id,
            'job_title_id' => $this->jobTitle->id,
            'from_province_id' => $fromProvince->id,
            'from_district_id' => $fromDistrict->id,
            'to_province_id' => $toProvince->id,
            'to_district_id' => $toDistrict->id,
            'effective_date' => now()->toDateString(),
            'relocation_amount' => 1000,
        ]);
    }

    private function case(string $reference, CaseStatus $status, OffenceCategory $offence, PenaltyType $penalty, string $expiryDate): DisciplinaryCase
    {
        return DisciplinaryCase::create([
            'reference_no' => $reference,
            'employee_id' => $this->northernEmployee->id,
            'province_id' => $this->northern->id,
            'district_id' => $this->kasama->id,
            'facility_id' => $this->kasamaFacility->id,
            'nature_of_offence' => 'Test offence',
            'offence_category_id' => $offence->id,
            'penalty_type_id' => $penalty->id,
            'case_status_id' => $status->id,
            'effective_date' => now()->toDateString(),
            'expiry_date' => $expiryDate,
        ]);
    }
}
