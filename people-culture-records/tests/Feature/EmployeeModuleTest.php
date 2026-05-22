<?php

namespace Tests\Feature;

use App\Models\District;
use App\Models\Employee;
use App\Models\EmploymentStatus;
use App\Models\ActivityLog;
use App\Models\Department;
use App\Models\Province;
use App\Models\Project;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class EmployeeModuleTest extends TestCase
{
    use RefreshDatabase;

    private Role $adminRole;
    private Role $managerRole;
    private Role $officerRole;
    private Role $viewerRole;
    private Province $northern;
    private Province $luapula;
    private District $kasama;
    private District $mansa;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminRole = Role::create(['name' => 'Admin', 'code' => 'ADMIN', 'is_active' => true]);
        $this->managerRole = Role::create(['name' => 'HR Manager', 'code' => 'HRM', 'is_active' => true]);
        $this->officerRole = Role::create(['name' => 'HR Officer', 'code' => 'HRO', 'is_active' => true]);
        $this->viewerRole = Role::create(['name' => 'Viewer', 'code' => 'VIEWER', 'is_active' => true]);

        $this->northern = Province::create(['name' => 'Northern', 'code' => 'NOR', 'is_active' => true]);
        $this->luapula = Province::create(['name' => 'Luapula', 'code' => 'LUA', 'is_active' => true]);

        $this->kasama = District::create(['province_id' => $this->northern->id, 'name' => 'Kasama', 'code' => 'NOR-KAS', 'is_active' => true]);
        $this->mansa = District::create(['province_id' => $this->luapula->id, 'name' => 'Mansa', 'code' => 'LUA-MAN', 'is_active' => true]);
    }

    public function test_unauthenticated_users_cannot_access_employees(): void
    {
        $this->get('/employees')->assertRedirect('/login');
    }

    public function test_admin_can_access_employees(): void
    {
        $this->actingAs($this->user($this->adminRole))
            ->get('/employees')
            ->assertOk()
            ->assertSee('Employees');
    }

    public function test_hr_manager_can_access_employees(): void
    {
        $this->actingAs($this->user($this->managerRole))
            ->get('/employees')
            ->assertOk()
            ->assertSee('Employees');
    }

    public function test_hr_officer_only_sees_employees_in_assigned_province(): void
    {
        $officer = $this->user($this->officerRole, $this->northern);

        $this->employee(['employee_no' => 'NOR-001', 'first_name' => 'Mary', 'province_id' => $this->northern->id, 'district_id' => $this->kasama->id]);
        $this->employee(['employee_no' => 'LUA-001', 'first_name' => 'Jane', 'province_id' => $this->luapula->id, 'district_id' => $this->mansa->id]);

        $this->actingAs($officer)
            ->get('/employees')
            ->assertOk()
            ->assertSee('NOR-001')
            ->assertDontSee('LUA-001');
    }

    public function test_viewer_can_view_but_cannot_create_edit_or_archive(): void
    {
        $viewer = $this->user($this->viewerRole, $this->northern);
        $employee = $this->employee(['province_id' => $this->northern->id, 'district_id' => $this->kasama->id]);

        $this->actingAs($viewer)->get(route('employees.show', $employee))->assertOk();
        $this->actingAs($viewer)->get(route('employees.create'))->assertForbidden();
        $this->actingAs($viewer)->get(route('employees.edit', $employee))->assertForbidden();
        $this->actingAs($viewer)->patch(route('employees.archive', $employee))->assertForbidden();
        $this->actingAs($viewer)
            ->post(route('employees.bulk-action'), [
                'employee_ids' => [$employee->id],
                'action' => 'assign_supervisor',
                'supervisor_name' => 'Mary Banda',
            ])
            ->assertForbidden();
    }

    public function test_employee_creation_works(): void
    {
        $admin = $this->user($this->adminRole);

        $this->actingAs($admin)
            ->post(route('employees.store'), [
                'employee_no' => 'RTC-001',
                'first_name' => 'Grace',
                'last_name' => 'Banda',
                'province_id' => $this->northern->id,
                'district_id' => $this->kasama->id,
                'email' => 'grace@example.com',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('employees', [
            'employee_no' => 'RTC-001',
            'first_name' => 'Grace',
            'province_id' => $this->northern->id,
            'district_id' => $this->kasama->id,
            'created_by' => $admin->id,
        ]);
    }

    public function test_sensitive_employee_personal_data_is_encrypted_at_rest(): void
    {
        $employee = $this->employee([
            'date_of_birth' => '1998-05-31',
            'national_id' => '544832/10/1',
            'notes' => 'Sensitive HR note',
        ]);

        $raw = DB::table('employees')->where('id', $employee->id)->first();

        $this->assertNotSame('1998-05-31', $raw->date_of_birth);
        $this->assertNotSame('544832/10/1', $raw->national_id);
        $this->assertNotSame('Sensitive HR note', $raw->notes);

        $employee->refresh();

        $this->assertSame('1998-05-31', $employee->date_of_birth->toDateString());
        $this->assertSame('544832/10/1', $employee->national_id);
        $this->assertSame('Sensitive HR note', $employee->notes);
    }

    public function test_only_admin_and_hr_manager_can_view_sensitive_employee_personal_data(): void
    {
        $employee = $this->employee([
            'province_id' => $this->northern->id,
            'district_id' => $this->kasama->id,
            'date_of_birth' => '1998-05-31',
            'national_id' => '544832/10/1',
            'notes' => 'Sensitive HR note',
        ]);

        $this->actingAs($this->user($this->managerRole))
            ->get(route('employees.show', $employee))
            ->assertOk()
            ->assertSee('31 May 1998')
            ->assertSee('544832/10/1')
            ->assertSee('Sensitive HR note');

        $this->actingAs($this->user($this->officerRole, $this->northern))
            ->get(route('employees.show', $employee))
            ->assertOk()
            ->assertSee('Restricted')
            ->assertDontSee('544832/10/1')
            ->assertDontSee('Sensitive HR note');
    }

    public function test_employee_validation_rejects_district_outside_selected_province(): void
    {
        $admin = $this->user($this->adminRole);

        $this->actingAs($admin)
            ->from(route('employees.create'))
            ->post(route('employees.store'), [
                'employee_no' => 'RTC-002',
                'first_name' => 'Wrong',
                'last_name' => 'District',
                'province_id' => $this->northern->id,
                'district_id' => $this->mansa->id,
            ])
            ->assertRedirect(route('employees.create'))
            ->assertSessionHasErrors('district_id');
    }

    public function test_archive_and_restore_work(): void
    {
        $admin = $this->user($this->adminRole);
        $employee = $this->employee(['province_id' => $this->northern->id, 'district_id' => $this->kasama->id]);

        $this->actingAs($admin)
            ->patch(route('employees.archive', $employee))
            ->assertRedirect(route('employees.index'));

        $this->assertSoftDeleted('employees', ['id' => $employee->id]);

        $this->actingAs($admin)
            ->patch(route('employees.restore', $employee->id))
            ->assertRedirect(route('employees.show', $employee->id));

        $this->assertDatabaseHas('employees', [
            'id' => $employee->id,
            'deleted_at' => null,
            'archived_by' => null,
        ]);
    }

    public function test_admin_can_bulk_change_employment_status(): void
    {
        $admin = $this->user($this->adminRole);
        $status = EmploymentStatus::create(['name' => 'Separated', 'code' => 'SEP', 'is_active' => true]);
        $employees = [
            $this->employee(['province_id' => $this->northern->id, 'district_id' => $this->kasama->id]),
            $this->employee(['province_id' => $this->luapula->id, 'district_id' => $this->mansa->id]),
        ];

        $this->actingAs($admin)
            ->post(route('employees.bulk-action'), [
                'employee_ids' => collect($employees)->pluck('id')->all(),
                'action' => 'change_employment_status',
                'employment_status_id' => $status->id,
            ])
            ->assertRedirect(route('employees.index'));

        foreach ($employees as $employee) {
            $this->assertDatabaseHas('employees', [
                'id' => $employee->id,
                'employment_status_id' => $status->id,
                'updated_by' => $admin->id,
            ]);
        }
    }

    public function test_hr_manager_can_bulk_update_employees(): void
    {
        $manager = $this->user($this->managerRole);
        $department = Department::create(['name' => 'People and Culture', 'code' => 'PC', 'is_active' => true]);
        $employee = $this->employee(['province_id' => $this->northern->id, 'district_id' => $this->kasama->id]);

        $this->actingAs($manager)
            ->post(route('employees.bulk-action'), [
                'employee_ids' => [$employee->id],
                'action' => 'change_department',
                'department_id' => $department->id,
            ])
            ->assertRedirect(route('employees.index'));

        $this->assertDatabaseHas('employees', [
            'id' => $employee->id,
            'department_id' => $department->id,
            'updated_by' => $manager->id,
        ]);
    }

    public function test_hr_officer_can_bulk_update_employees_in_assigned_province(): void
    {
        $officer = $this->user($this->officerRole, $this->northern);
        $project = Project::create(['name' => 'General Operations', 'code' => 'GO', 'is_active' => true]);
        $employee = $this->employee(['province_id' => $this->northern->id, 'district_id' => $this->kasama->id]);

        $this->actingAs($officer)
            ->post(route('employees.bulk-action'), [
                'employee_ids' => [$employee->id],
                'action' => 'change_project',
                'project_id' => $project->id,
            ])
            ->assertRedirect(route('employees.index'));

        $this->assertDatabaseHas('employees', [
            'id' => $employee->id,
            'project_id' => $project->id,
            'updated_by' => $officer->id,
        ]);
    }

    public function test_hr_officer_cannot_bulk_update_employees_outside_assigned_province(): void
    {
        $officer = $this->user($this->officerRole, $this->northern);
        $status = EmploymentStatus::create(['name' => 'Separated', 'code' => 'SEP', 'is_active' => true]);
        $northernEmployee = $this->employee(['province_id' => $this->northern->id, 'district_id' => $this->kasama->id]);
        $luapulaEmployee = $this->employee(['province_id' => $this->luapula->id, 'district_id' => $this->mansa->id]);

        $this->actingAs($officer)
            ->from(route('employees.index'))
            ->post(route('employees.bulk-action'), [
                'employee_ids' => [$northernEmployee->id, $luapulaEmployee->id],
                'action' => 'change_employment_status',
                'employment_status_id' => $status->id,
            ])
            ->assertRedirect(route('employees.index'))
            ->assertSessionHasErrors('employee_ids');

        $this->assertDatabaseMissing('employees', [
            'id' => $northernEmployee->id,
            'employment_status_id' => $status->id,
        ]);
        $this->assertDatabaseMissing('employees', [
            'id' => $luapulaEmployee->id,
            'employment_status_id' => $status->id,
        ]);
    }

    public function test_bulk_archive_soft_deletes_selected_employees(): void
    {
        $admin = $this->user($this->adminRole);
        $employee = $this->employee(['province_id' => $this->northern->id, 'district_id' => $this->kasama->id]);

        $this->actingAs($admin)
            ->post(route('employees.bulk-action'), [
                'employee_ids' => [$employee->id],
                'action' => 'archive',
            ])
            ->assertRedirect(route('employees.index'));

        $this->assertSoftDeleted('employees', ['id' => $employee->id]);
        $this->assertDatabaseHas('employees', [
            'id' => $employee->id,
            'archived_by' => $admin->id,
        ]);
    }

    public function test_bulk_action_requires_selected_employees(): void
    {
        $admin = $this->user($this->adminRole);

        $this->actingAs($admin)
            ->from(route('employees.index'))
            ->post(route('employees.bulk-action'), [
                'action' => 'assign_supervisor',
                'supervisor_name' => 'Mary Banda',
            ])
            ->assertRedirect(route('employees.index'))
            ->assertSessionHasErrors('employee_ids');
    }

    public function test_invalid_bulk_action_is_rejected(): void
    {
        $admin = $this->user($this->adminRole);
        $employee = $this->employee(['province_id' => $this->northern->id, 'district_id' => $this->kasama->id]);

        $this->actingAs($admin)
            ->from(route('employees.index'))
            ->post(route('employees.bulk-action'), [
                'employee_ids' => [$employee->id],
                'action' => 'deactivate_employee',
            ])
            ->assertRedirect(route('employees.index'))
            ->assertSessionHasErrors('action');
    }

    public function test_bulk_action_logs_summary_activity_and_linked_user_count(): void
    {
        $admin = $this->user($this->adminRole);
        $employee = $this->employee(['province_id' => $this->northern->id, 'district_id' => $this->kasama->id]);
        User::factory()->create([
            'employee_id' => $employee->id,
            'role_id' => $this->officerRole->id,
            'province_id' => $this->northern->id,
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->post(route('employees.bulk-action'), [
                'employee_ids' => [$employee->id],
                'action' => 'assign_supervisor',
                'supervisor_name' => 'Mary Banda',
            ])
            ->assertRedirect(route('employees.index'));

        $log = ActivityLog::where('action', 'bulk_employee_action_completed')->first();

        $this->assertNotNull($log);
        $this->assertSame(1, $log->properties['employee_count']);
        $this->assertSame(1, $log->properties['linked_user_accounts_count']);
        $this->assertSame('Mary Banda', $log->properties['changed_value']);
    }

    private function user(Role $role, ?Province $province = null): User
    {
        return User::factory()->create([
            'role_id' => $role->id,
            'province_id' => $province?->id,
            'is_active' => true,
        ]);
    }

    /**
     * @param array<string, mixed> $attributes
     */
    private function employee(array $attributes = []): Employee
    {
        return Employee::create(array_merge([
            'employee_no' => fake()->unique()->bothify('EMP-###'),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'province_id' => $this->northern->id,
            'district_id' => $this->kasama->id,
        ], $attributes));
    }
}
