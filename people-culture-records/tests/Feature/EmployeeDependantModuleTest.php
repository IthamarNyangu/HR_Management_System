<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\District;
use App\Models\Employee;
use App\Models\EmployeeDependant;
use App\Models\Province;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class EmployeeDependantModuleTest extends TestCase
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

        Queue::fake();
        Http::fake([
            '*' => Http::response(['status' => 'not_enabled'], 200),
        ]);

        $this->adminRole = Role::create(['name' => 'Admin', 'code' => 'ADMIN', 'is_active' => true]);
        $this->managerRole = Role::create(['name' => 'HR Manager', 'code' => 'HRM', 'is_active' => true]);
        $this->officerRole = Role::create(['name' => 'HR Officer', 'code' => 'HRO', 'is_active' => true]);
        $this->viewerRole = Role::create(['name' => 'Viewer', 'code' => 'VIEWER', 'is_active' => true]);

        $this->northern = Province::create(['name' => 'Northern', 'code' => 'NOR', 'is_active' => true]);
        $this->luapula = Province::create(['name' => 'Luapula', 'code' => 'LUA', 'is_active' => true]);
        $this->kasama = District::create(['province_id' => $this->northern->id, 'name' => 'Kasama', 'code' => 'NOR-KAS', 'is_active' => true]);
        $this->mansa = District::create(['province_id' => $this->luapula->id, 'name' => 'Mansa', 'code' => 'LUA-MAN', 'is_active' => true]);
    }

    public function test_unauthenticated_user_cannot_access_dependant_routes(): void
    {
        $employee = $this->employee();

        $this->get(route('employees.dependants.create', $employee))->assertRedirect('/login');
    }

    public function test_admin_can_create_dependant_and_sensitive_fields_are_encrypted(): void
    {
        $admin = $this->user($this->adminRole);
        $employee = $this->employee();

        $this->actingAs($admin)
            ->post(route('employees.dependants.store', $employee), $this->dependantPayload())
            ->assertRedirect(route('employees.show', $employee));

        $dependant = EmployeeDependant::firstOrFail();
        $raw = DB::table('employee_dependants')->where('id', $dependant->id)->first();

        $this->assertSame('Jane Nyambe', $dependant->full_name);
        $this->assertSame('2015-04-06', $dependant->date_of_birth->toDateString());
        $this->assertSame('123456/78/1', $dependant->national_id);
        $this->assertSame('Sensitive family note.', $dependant->internal_notes);
        $this->assertNotSame('2015-04-06', $raw->date_of_birth);
        $this->assertNotSame('123456/78/1', $raw->national_id);
        $this->assertNotSame('Sensitive family note.', $raw->internal_notes);
        $this->assertDatabaseHas('activity_logs', [
            'action' => 'employee_dependant_created',
            'subject_type' => EmployeeDependant::class,
            'subject_id' => $dependant->id,
        ]);
    }

    public function test_hr_manager_can_manage_dependants_in_any_province(): void
    {
        $manager = $this->user($this->managerRole);
        $employee = $this->employee([
            'province_id' => $this->luapula->id,
            'district_id' => $this->mansa->id,
        ]);

        $this->actingAs($manager)
            ->post(route('employees.dependants.store', $employee), $this->dependantPayload())
            ->assertRedirect(route('employees.show', $employee));
    }

    public function test_hr_officer_can_manage_only_dependants_in_assigned_province(): void
    {
        $officer = $this->user($this->officerRole, $this->northern);
        $assignedEmployee = $this->employee();
        $outsideEmployee = $this->employee([
            'employee_no' => 'EMP-LUA',
            'province_id' => $this->luapula->id,
            'district_id' => $this->mansa->id,
        ]);

        $this->actingAs($officer)
            ->post(route('employees.dependants.store', $assignedEmployee), $this->dependantPayload())
            ->assertRedirect(route('employees.show', $assignedEmployee));

        $this->actingAs($officer)
            ->post(route('employees.dependants.store', $outsideEmployee), $this->dependantPayload(['full_name' => 'Outside Province']))
            ->assertForbidden();

        $this->assertDatabaseMissing('employee_dependants', ['full_name' => 'Outside Province']);
    }

    public function test_viewer_cannot_see_or_manage_employee_dependants(): void
    {
        $viewer = $this->user($this->viewerRole, $this->northern);
        $employee = $this->employee();
        $this->dependant($employee);

        $this->actingAs($viewer)
            ->get(route('employees.show', $employee))
            ->assertOk()
            ->assertDontSee('Employee Dependants')
            ->assertDontSee('Jane Nyambe');

        $this->actingAs($viewer)
            ->get(route('employees.dependants.create', $employee))
            ->assertForbidden();
    }

    public function test_authorized_hr_user_can_see_dependants_on_employee_profile(): void
    {
        $manager = $this->user($this->managerRole);
        $employee = $this->employee();
        $this->dependant($employee);

        $this->actingAs($manager)
            ->get(route('employees.show', $employee))
            ->assertOk()
            ->assertSee('Employee Dependants')
            ->assertSee('Jane Nyambe')
            ->assertSee('Pending Verification')
            ->assertDontSee('WorkPulse Access')
            ->assertDontSee('Validate WorkPulse readiness');
    }

    public function test_relationship_and_date_rules_are_enforced(): void
    {
        $admin = $this->user($this->adminRole);
        $employee = $this->employee();

        $this->actingAs($admin)
            ->from(route('employees.dependants.create', $employee))
            ->post(route('employees.dependants.store', $employee), $this->dependantPayload([
                'category' => 'child',
                'relationship_detail' => 'spouse',
                'date_of_birth' => today()->addDay()->toDateString(),
            ]))
            ->assertRedirect(route('employees.dependants.create', $employee))
            ->assertSessionHasErrors(['relationship_detail', 'date_of_birth']);
    }

    public function test_spouse_category_normalizes_relationship_to_spouse(): void
    {
        $admin = $this->user($this->adminRole);
        $employee = $this->employee();

        $this->actingAs($admin)
            ->post(route('employees.dependants.store', $employee), $this->dependantPayload([
                'category' => 'spouse',
                'relationship_detail' => 'biological_child',
            ]))
            ->assertRedirect(route('employees.show', $employee));

        $this->assertDatabaseHas('employee_dependants', [
            'employee_id' => $employee->id,
            'category' => 'spouse',
            'relationship_detail' => 'spouse',
        ]);
    }

    public function test_verifying_dependant_records_verifier_and_time(): void
    {
        $manager = $this->user($this->managerRole);
        $employee = $this->employee();
        $dependant = $this->dependant($employee);

        $this->actingAs($manager)
            ->put(route('employees.dependants.update', [$employee, $dependant]), $this->dependantPayload([
                'verification_status' => EmployeeDependant::VERIFICATION_VERIFIED,
            ]))
            ->assertRedirect(route('employees.show', $employee));

        $dependant->refresh();

        $this->assertSame(EmployeeDependant::VERIFICATION_VERIFIED, $dependant->verification_status);
        $this->assertSame($manager->id, $dependant->verified_by);
        $this->assertNotNull($dependant->verified_at);
        $this->assertDatabaseHas('activity_logs', [
            'action' => 'employee_dependant_updated',
            'subject_id' => $dependant->id,
        ]);
    }

    public function test_dependant_cannot_be_updated_through_another_employee(): void
    {
        $admin = $this->user($this->adminRole);
        $employee = $this->employee();
        $otherEmployee = $this->employee(['employee_no' => 'EMP-OTHER']);
        $dependant = $this->dependant($employee);

        $this->actingAs($admin)
            ->put(route('employees.dependants.update', [$otherEmployee, $dependant]), $this->dependantPayload())
            ->assertForbidden();
    }

    public function test_dependant_can_be_archived_and_restored(): void
    {
        $admin = $this->user($this->adminRole);
        $employee = $this->employee();
        $dependant = $this->dependant($employee);

        $this->actingAs($admin)
            ->patch(route('employees.dependants.archive', [$employee, $dependant]))
            ->assertRedirect(route('employees.show', $employee));

        $this->assertSoftDeleted('employee_dependants', ['id' => $dependant->id]);

        $this->actingAs($admin)
            ->patch(route('employees.dependants.restore', [$employee, $dependant->id]))
            ->assertRedirect(route('employees.show', $employee));

        $this->assertDatabaseHas('employee_dependants', [
            'id' => $dependant->id,
            'deleted_at' => null,
            'archived_by' => null,
        ]);

        $this->assertSame(1, ActivityLog::where('action', 'employee_dependant_archived')->count());
        $this->assertSame(1, ActivityLog::where('action', 'employee_dependant_restored')->count());
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
     * @param  array<string, mixed>  $attributes
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

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function dependantPayload(array $overrides = []): array
    {
        return array_merge([
            'category' => EmployeeDependant::CATEGORY_CHILD,
            'relationship_detail' => 'stepchild',
            'full_name' => 'Jane Nyambe',
            'gender' => 'Female',
            'date_of_birth' => '2015-04-06',
            'national_id' => '123456/78/1',
            'verification_status' => EmployeeDependant::VERIFICATION_PENDING,
            'is_active' => true,
            'internal_notes' => 'Sensitive family note.',
        ], $overrides);
    }

    private function dependant(Employee $employee, array $attributes = []): EmployeeDependant
    {
        return $employee->dependants()->create(array_merge($this->dependantPayload(), $attributes));
    }
}
