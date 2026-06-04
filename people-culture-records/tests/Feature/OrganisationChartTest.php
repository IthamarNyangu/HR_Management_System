<?php

namespace Tests\Feature;

use App\Models\District;
use App\Models\Employee;
use App\Models\JobTitle;
use App\Models\Province;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrganisationChartTest extends TestCase
{
    use RefreshDatabase;

    private Role $adminRole;
    private Role $officerRole;
    private Province $northern;
    private Province $luapula;
    private District $kasama;
    private District $mansa;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminRole = Role::create(['name' => 'Admin', 'code' => 'ADMIN', 'is_active' => true]);
        $this->officerRole = Role::create(['name' => 'HR Officer', 'code' => 'HRO', 'is_active' => true]);

        $this->northern = Province::create(['name' => 'Northern', 'code' => 'NOR', 'is_active' => true]);
        $this->luapula = Province::create(['name' => 'Luapula', 'code' => 'LUA', 'is_active' => true]);

        $this->kasama = District::create(['province_id' => $this->northern->id, 'name' => 'Kasama', 'code' => 'NOR-KAS', 'is_active' => true]);
        $this->mansa = District::create(['province_id' => $this->luapula->id, 'name' => 'Mansa', 'code' => 'LUA-MAN', 'is_active' => true]);
    }

    public function test_unauthenticated_users_cannot_access_organisation_chart(): void
    {
        $this->get(route('organisation-chart.index'))->assertRedirect('/login');
    }

    public function test_admin_can_view_organisation_chart(): void
    {
        $admin = $this->user($this->adminRole);
        $supervisor = $this->employee(['employee_no' => 'SUP-001', 'first_name' => 'Mary', 'last_name' => 'Banda']);
        $employee = $this->employee([
            'employee_no' => 'EMP-001',
            'first_name' => 'Grace',
            'last_name' => 'Mwansa',
            'supervisor_employee_id' => $supervisor->id,
            'supervisor_name' => $supervisor->full_name,
        ]);

        $this->actingAs($admin)
            ->get(route('organisation-chart.index', ['province_id' => $this->northern->id]))
            ->assertOk()
            ->assertSee('Organisation Chart')
            ->assertSee($supervisor->full_name)
            ->assertSee($employee->full_name);
    }

    public function test_organisation_chart_waits_for_search_or_filter_before_showing_employees(): void
    {
        $admin = $this->user($this->adminRole);
        $employee = $this->employee(['employee_no' => 'EMP-001', 'first_name' => 'Grace', 'last_name' => 'Mwansa']);

        $this->actingAs($admin)
            ->get(route('organisation-chart.index'))
            ->assertOk()
            ->assertSee('Search or apply a filter to view the organisation chart.')
            ->assertDontSee($employee->employee_no);
    }

    public function test_hr_officer_only_sees_assigned_province_in_organisation_chart(): void
    {
        $officer = $this->user($this->officerRole, $this->northern);

        $this->employee(['employee_no' => 'NOR-001', 'first_name' => 'Northern', 'province_id' => $this->northern->id, 'district_id' => $this->kasama->id]);
        $this->employee(['employee_no' => 'LUA-001', 'first_name' => 'Luapula', 'province_id' => $this->luapula->id, 'district_id' => $this->mansa->id]);

        $this->actingAs($officer)
            ->get(route('organisation-chart.index', ['province_id' => $this->northern->id]))
            ->assertOk()
            ->assertSee('NOR-001')
            ->assertDontSee('LUA-001');
    }

    public function test_employee_can_be_updated_with_linked_supervisor(): void
    {
        $admin = $this->user($this->adminRole);
        $jobTitle = JobTitle::create(['name' => 'HR Manager', 'code' => 'HRM', 'is_active' => true]);
        $supervisor = $this->employee(['employee_no' => 'SUP-001', 'first_name' => 'Mary', 'last_name' => 'Banda', 'job_title_id' => $jobTitle->id]);
        $employee = $this->employee(['employee_no' => 'EMP-001', 'first_name' => 'Grace', 'last_name' => 'Mwansa']);

        $this->actingAs($admin)
            ->put(route('employees.update', $employee), [
                'employee_no' => $employee->employee_no,
                'first_name' => $employee->first_name,
                'last_name' => $employee->last_name,
                'province_id' => $employee->province_id,
                'district_id' => $employee->district_id,
                'supervisor_employee_id' => $supervisor->id,
                'supervisor_name' => '',
            ])
            ->assertRedirect(route('employees.show', $employee));

        $this->assertDatabaseHas('employees', [
            'id' => $employee->id,
            'supervisor_employee_id' => $supervisor->id,
            'supervisor_name' => $supervisor->full_name,
        ]);
    }

    public function test_employee_cannot_be_their_own_supervisor(): void
    {
        $admin = $this->user($this->adminRole);
        $employee = $this->employee(['employee_no' => 'EMP-001']);

        $this->actingAs($admin)
            ->from(route('employees.edit', $employee))
            ->put(route('employees.update', $employee), [
                'employee_no' => $employee->employee_no,
                'first_name' => $employee->first_name,
                'last_name' => $employee->last_name,
                'province_id' => $employee->province_id,
                'district_id' => $employee->district_id,
                'supervisor_employee_id' => $employee->id,
                'supervisor_name' => '',
            ])
            ->assertRedirect(route('employees.edit', $employee))
            ->assertSessionHasErrors('supervisor_employee_id');
    }

    public function test_admin_can_auto_link_line_managers_by_employee_number(): void
    {
        $admin = $this->user($this->adminRole);
        $lineManager = $this->employee([
            'employee_no' => '22866',
            'first_name' => 'Paul',
            'last_name' => 'Chinyemba',
        ]);
        $employee = $this->employee([
            'employee_no' => 'EMP-001',
            'first_name' => 'Ithamar',
            'last_name' => 'Nyangu',
            'supervisor_name' => '22866 - Mr P Chinyemba',
            'supervisor_employee_id' => null,
        ]);
        $unknown = $this->employee([
            'employee_no' => 'EMP-002',
            'first_name' => 'Unknown',
            'last_name' => 'Manager',
            'supervisor_name' => '99999 - Missing Person',
            'supervisor_employee_id' => null,
        ]);

        $this->actingAs($admin)
            ->from(route('organisation-chart.index', ['province_id' => $this->northern->id]))
            ->post(route('organisation-chart.link-line-managers'))
            ->assertRedirect(route('organisation-chart.index', ['province_id' => $this->northern->id]))
            ->assertSessionHas('success');

        $this->assertSame($lineManager->id, $employee->fresh()->supervisor_employee_id);
        $this->assertSame('Paul Chinyemba', $employee->fresh()->supervisor_name);
        $this->assertNull($unknown->fresh()->supervisor_employee_id);
        $this->assertDatabaseHas('activity_logs', [
            'action' => 'line_managers_auto_linked',
            'user_id' => $admin->id,
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
