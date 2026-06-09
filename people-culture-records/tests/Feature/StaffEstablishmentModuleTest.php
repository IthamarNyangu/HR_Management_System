<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\District;
use App\Models\Employee;
use App\Models\EmploymentStatus;
use App\Models\JobTitle;
use App\Models\Project;
use App\Models\Province;
use App\Models\Role;
use App\Models\StaffEstablishmentPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffEstablishmentModuleTest extends TestCase
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
    private Project $project;
    private Department $department;
    private JobTitle $jobTitle;
    private EmploymentStatus $activeStatus;

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
        $this->project = Project::create(['name' => 'RTC Zamb - Action HIV', 'code' => 'AHIV', 'is_active' => true]);
        $this->department = Department::create(['name' => 'Technical', 'code' => 'TECH', 'is_active' => true]);
        $this->jobTitle = JobTitle::create(['name' => 'Lay Counsellor', 'code' => 'LC', 'is_active' => true]);
        $this->activeStatus = EmploymentStatus::firstOrCreate(
            ['code' => 'ACTIVE'],
            ['name' => 'Active', 'is_active' => true],
        );
    }

    public function test_unauthenticated_users_cannot_access_staff_establishment(): void
    {
        $this->get(route('staff-establishment.index'))->assertRedirect('/login');
    }

    public function test_admin_can_create_plan_and_see_filled_and_vacancy_counts(): void
    {
        $admin = $this->user($this->adminRole);
        $this->employee(['employee_no' => 'EMP-001']);

        $this->actingAs($admin)
            ->post(route('staff-establishment.store'), $this->payload())
            ->assertRedirect();

        $plan = StaffEstablishmentPlan::firstOrFail();

        $this->assertDatabaseHas('staff_establishment_plans', [
            'id' => $plan->id,
            'reference_no' => 'EST-2026-0001',
            'status' => StaffEstablishmentPlan::STATUS_APPROVED,
        ]);

        $this->actingAs($admin)
            ->get(route('staff-establishment.show', $plan))
            ->assertOk()
            ->assertSee('Budgeted')
            ->assertSee('Filled')
            ->assertSee('Vacant')
            ->assertSee('Lay Counsellor')
            ->assertSee('3')
            ->assertSee('1')
            ->assertSee('2');
    }

    public function test_hr_officer_only_sees_assigned_province_lines(): void
    {
        $admin = $this->user($this->adminRole);
        $officer = $this->user($this->officerRole, $this->northern);
        $otherTitle = JobTitle::create(['name' => 'Driver', 'code' => 'DRV', 'is_active' => true]);
        $plan = $this->plan($admin);
        $plan->lines()->create([
            'job_title_id' => $this->jobTitle->id,
            'province_id' => $this->northern->id,
            'district_id' => $this->kasama->id,
            'department_id' => $this->department->id,
            'budgeted_positions' => 3,
        ]);
        $plan->lines()->create([
            'job_title_id' => $otherTitle->id,
            'province_id' => $this->luapula->id,
            'district_id' => $this->mansa->id,
            'budgeted_positions' => 5,
        ]);

        $this->actingAs($officer)
            ->get(route('staff-establishment.show', $plan))
            ->assertOk()
            ->assertSee('Lay Counsellor')
            ->assertDontSee('Driver');
    }

    public function test_viewer_can_view_but_cannot_create_or_edit(): void
    {
        $admin = $this->user($this->adminRole);
        $viewer = $this->user($this->viewerRole, $this->northern);
        $plan = $this->plan($admin);
        $plan->lines()->create([
            'job_title_id' => $this->jobTitle->id,
            'province_id' => $this->northern->id,
            'district_id' => $this->kasama->id,
            'budgeted_positions' => 1,
        ]);

        $this->actingAs($viewer)->get(route('staff-establishment.show', $plan))->assertOk();
        $this->actingAs($viewer)->get(route('staff-establishment.create'))->assertForbidden();
        $this->actingAs($viewer)->get(route('staff-establishment.edit', $plan))->assertForbidden();
        $this->actingAs($viewer)->patch(route('staff-establishment.archive', $plan))->assertForbidden();
    }

    public function test_archive_restore_and_exports_work(): void
    {
        $manager = $this->user($this->managerRole);
        $plan = $this->plan($manager);
        $plan->lines()->create([
            'job_title_id' => $this->jobTitle->id,
            'province_id' => $this->northern->id,
            'district_id' => $this->kasama->id,
            'budgeted_positions' => 1,
        ]);

        $this->actingAs($manager)
            ->get(route('staff-establishment.export.excel', $plan))
            ->assertOk();

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'staff_establishment_exported_excel',
            'user_id' => $manager->id,
        ]);

        $this->actingAs($manager)
            ->get(route('staff-establishment.export.pdf', $plan))
            ->assertOk();

        $this->actingAs($manager)
            ->patch(route('staff-establishment.archive', $plan))
            ->assertRedirect(route('staff-establishment.index'));

        $this->assertSoftDeleted('staff_establishment_plans', ['id' => $plan->id]);

        $this->actingAs($manager)
            ->patch(route('staff-establishment.restore', $plan->id))
            ->assertRedirect(route('staff-establishment.show', $plan->id));

        $this->assertNotSoftDeleted('staff_establishment_plans', ['id' => $plan->id]);
    }

    public function test_dashboard_shows_staff_establishment_summary(): void
    {
        $admin = $this->user($this->adminRole);
        $this->employee(['employee_no' => 'EMP-001']);
        $plan = $this->plan($admin);
        $plan->lines()->create([
            'job_title_id' => $this->jobTitle->id,
            'province_id' => $this->northern->id,
            'district_id' => $this->kasama->id,
            'department_id' => $this->department->id,
            'budgeted_positions' => 3,
        ]);

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Staff Establishment Overview')
            ->assertSee('Budgeted Positions')
            ->assertSee('Vacancies')
            ->assertSee('2');
    }

    public function test_establishment_and_vacancies_table_is_paginated_to_ten_rows(): void
    {
        $admin = $this->user($this->adminRole);
        $plan = $this->plan($admin);

        foreach (range(1, 11) as $number) {
            $jobTitle = JobTitle::create([
                'name' => sprintf('Establishment Role %02d', $number),
                'code' => sprintf('ER%02d', $number),
                'is_active' => true,
            ]);

            $plan->lines()->create([
                'job_title_id' => $jobTitle->id,
                'province_id' => $this->northern->id,
                'district_id' => $this->kasama->id,
                'budgeted_positions' => 1,
            ]);
        }

        $this->actingAs($admin)
            ->get(route('staff-establishment.show', $plan))
            ->assertOk()
            ->assertSee('Establishment Role 01')
            ->assertSee('Establishment Role 10')
            ->assertDontSee('Establishment Role 11');

        $this->actingAs($admin)
            ->get(route('staff-establishment.show', ['staff_establishment_plan' => $plan, 'page' => 2]))
            ->assertOk()
            ->assertSee('Establishment Role 11')
            ->assertDontSee('Establishment Role 01');
    }

    public function test_location_validation_rejects_district_outside_province(): void
    {
        $admin = $this->user($this->adminRole);

        $this->actingAs($admin)
            ->from(route('staff-establishment.create'))
            ->post(route('staff-establishment.store'), $this->payload([
                'lines' => [
                    [
                        'job_title_id' => $this->jobTitle->id,
                        'province_id' => $this->northern->id,
                        'district_id' => $this->mansa->id,
                        'budgeted_positions' => 3,
                    ],
                ],
            ]))
            ->assertRedirect(route('staff-establishment.create'))
            ->assertSessionHasErrors('lines.0.district_id');
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
            'project_id' => $this->project->id,
            'department_id' => $this->department->id,
            'job_title_id' => $this->jobTitle->id,
            'employment_status_id' => $this->activeStatus->id,
        ], $attributes));
    }

    private function plan(User $user): StaffEstablishmentPlan
    {
        return StaffEstablishmentPlan::create([
            'reference_no' => 'EST-2026-0001',
            'title' => 'May 2026 Staff Establishment',
            'project_id' => $this->project->id,
            'status' => StaffEstablishmentPlan::STATUS_APPROVED,
            'effective_month' => '2026-05-01',
            'created_by' => $user->id,
            'updated_by' => $user->id,
            'approved_by' => $user->id,
            'approved_at' => now(),
        ]);
    }

    /**
     * @param array<string, mixed> $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_replace_recursive([
            'title' => 'May 2026 Staff Establishment',
            'project_id' => $this->project->id,
            'status' => StaffEstablishmentPlan::STATUS_APPROVED,
            'effective_month' => '2026-05',
            'notes' => 'Approved establishment.',
            'lines' => [
                [
                    'job_title_id' => $this->jobTitle->id,
                    'province_id' => $this->northern->id,
                    'district_id' => $this->kasama->id,
                    'department_id' => $this->department->id,
                    'budgeted_positions' => 3,
                    'notes' => null,
                ],
            ],
        ], $overrides);
    }
}
