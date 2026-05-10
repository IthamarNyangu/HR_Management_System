<?php

namespace Tests\Feature;

use App\Models\District;
use App\Models\Employee;
use App\Models\Province;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
