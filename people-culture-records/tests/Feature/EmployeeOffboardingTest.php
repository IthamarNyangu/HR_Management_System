<?php

namespace Tests\Feature;

use App\Models\District;
use App\Models\Employee;
use App\Models\EmployeeOffboardingCase;
use App\Models\EmploymentStatus;
use App\Models\Province;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class EmployeeOffboardingTest extends TestCase
{
    use RefreshDatabase;

    private Role $adminRole;

    private Role $viewerRole;

    private Province $province;

    private District $district;

    private EmploymentStatus $activeStatus;

    private EmploymentStatus $terminatedStatus;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.exitclear.enabled' => true,
            'services.exitclear.base_url' => 'http://exitclear.test',
            'services.exitclear.integration_token' => 'test-token',
            'services.exitclear.initiation_path' => '/api/integrations/hrms/exit-cases',
            'services.exitclear.timeout' => 5,
        ]);

        $this->adminRole = Role::create(['name' => 'Admin', 'code' => 'ADMIN', 'is_active' => true]);
        $this->viewerRole = Role::create(['name' => 'Viewer', 'code' => 'VIEWER', 'is_active' => true]);
        $this->province = Province::create(['name' => 'Lusaka', 'code' => 'LUS', 'is_active' => true]);
        $this->district = District::create(['province_id' => $this->province->id, 'name' => 'Lusaka', 'code' => 'LUS-LUS', 'is_active' => true]);
        $this->activeStatus = EmploymentStatus::query()->updateOrCreate(['code' => 'ACTIVE'], ['name' => 'Active', 'is_active' => true]);
        $this->terminatedStatus = EmploymentStatus::query()->updateOrCreate(['code' => 'TERMINATED'], ['name' => 'Terminated', 'is_active' => true]);
    }

    public function test_offboarding_panel_appears_only_for_terminated_employees(): void
    {
        $admin = $this->user($this->adminRole);
        $active = $this->employee(['employment_status_id' => $this->activeStatus->id]);
        $terminated = $this->employee([
            'employee_no' => 'EMP-002',
            'employment_status_id' => $this->terminatedStatus->id,
            'termination_date' => '2026-10-31',
        ]);

        $this->actingAs($admin)->get(route('employees.show', $active))->assertOk()->assertDontSee('Begin Offboarding');
        $this->actingAs($admin)->get(route('employees.show', $terminated))->assertOk()->assertSee('Begin Offboarding');
    }

    public function test_authorised_hr_user_can_create_and_link_an_exitclear_draft(): void
    {
        $admin = $this->user($this->adminRole, ['email' => 'hr.admin@righttocare-zambia.org']);
        $employee = $this->terminatedEmployee();
        Http::fake([
            'http://exitclear.test/api/integrations/hrms/exit-cases' => Http::response($this->caseResponse(), 201),
        ]);

        $this->actingAs($admin)
            ->post(route('employees.offboarding.store', $employee))
            ->assertRedirect()
            ->assertSessionHas('success');

        $offboardingCase = EmployeeOffboardingCase::query()->firstOrFail();
        $this->assertSame(71, $offboardingCase->exitclear_case_id);
        $this->assertSame('EXC-2026-0071', $offboardingCase->case_number);
        $this->assertSame('draft', $offboardingCase->status);
        $this->assertDatabaseHas('activity_logs', [
            'action' => 'employee_offboarding_initiated',
            'subject_type' => Employee::class,
            'subject_id' => $employee->id,
        ]);

        Http::assertSent(function (Request $request) use ($admin, $employee, $offboardingCase): bool {
            return $request->hasHeader('Authorization', 'Bearer test-token')
                && $request['request_id'] === $offboardingCase->integration_request_uuid
                && $request['employee_source_id'] === $employee->id
                && $request['last_working_date'] === '2026-10-31'
                && $request['employee']['source_id'] === $employee->id
                && $request['employee']['employee_no'] === $employee->employee_no
                && $request['employee']['full_name'] === $employee->full_name
                && $request['actor']['hrms_user_id'] === $admin->id
                && ! isset(
                    $request['employee']['national_id'],
                    $request['employee']['date_of_birth'],
                    $request['employee']['phone'],
                    $request['termination_reason'],
                    $request['termination_comment'],
                );
        });
    }

    public function test_viewer_cannot_begin_or_refresh_offboarding(): void
    {
        $viewer = $this->user($this->viewerRole);
        $employee = $this->terminatedEmployee();

        $this->actingAs($viewer)->post(route('employees.offboarding.store', $employee))->assertForbidden();
        $this->actingAs($viewer)->patch(route('employees.offboarding.refresh', $employee))->assertForbidden();
        Http::assertNothingSent();
    }

    public function test_failed_initiation_can_be_retried_without_changing_the_request_identifier(): void
    {
        $admin = $this->user($this->adminRole);
        $employee = $this->terminatedEmployee();
        $attempts = 0;
        Http::fake(function () use (&$attempts) {
            $attempts++;

            return $attempts <= 2
                ? Http::response(['message' => 'ExitClear unavailable.'], 503)
                : Http::response($this->caseResponse(), 201);
        });

        $this->actingAs($admin)->post(route('employees.offboarding.store', $employee))->assertSessionHas('error');
        $failed = EmployeeOffboardingCase::query()->firstOrFail();
        $this->assertSame(EmployeeOffboardingCase::STATUS_ERROR, $failed->status);

        $this->actingAs($admin)->post(route('employees.offboarding.store', $employee))->assertSessionHas('success');

        $this->assertDatabaseCount('employee_offboarding_cases', 1);
        $this->assertSame($failed->integration_request_uuid, EmployeeOffboardingCase::query()->firstOrFail()->integration_request_uuid);
    }

    public function test_status_can_be_refreshed_from_exitclear(): void
    {
        $admin = $this->user($this->adminRole);
        $employee = $this->terminatedEmployee();
        $offboardingCase = $employee->offboardingCases()->create([
            'integration_request_uuid' => fake()->uuid(),
            'exitclear_case_id' => 71,
            'case_number' => 'EXC-2026-0071',
            'status' => 'draft',
            'initiated_by_user_id' => $admin->id,
            'initiated_at' => now(),
        ]);
        Http::fake([
            'http://exitclear.test/api/integrations/hrms/exit-cases/71' => Http::response($this->caseResponse(['status' => 'in_progress']), 200),
        ]);

        $this->actingAs($admin)
            ->patch(route('employees.offboarding.refresh', $employee))
            ->assertSessionHas('success');

        $this->assertSame('in_progress', $offboardingCase->fresh()->status);
        $this->assertNotNull($offboardingCase->fresh()->last_synced_at);
    }

    public function test_active_employee_cannot_begin_offboarding(): void
    {
        $admin = $this->user($this->adminRole);
        $employee = $this->employee(['employment_status_id' => $this->activeStatus->id]);

        $this->actingAs($admin)
            ->post(route('employees.offboarding.store', $employee))
            ->assertSessionHas('error');

        $this->assertDatabaseCount('employee_offboarding_cases', 0);
        Http::assertNothingSent();
    }

    private function terminatedEmployee(): Employee
    {
        return $this->employee([
            'employment_status_id' => $this->terminatedStatus->id,
            'termination_date' => '2026-10-31',
            'termination_comment' => 'Sensitive internal reason.',
        ]);
    }

    private function employee(array $attributes = []): Employee
    {
        return Employee::create(array_merge([
            'employee_no' => 'EMP-001',
            'first_name' => 'Test',
            'last_name' => 'Employee',
            'province_id' => $this->province->id,
            'district_id' => $this->district->id,
        ], $attributes));
    }

    private function user(Role $role, array $attributes = []): User
    {
        return User::factory()->create(array_merge([
            'role_id' => $role->id,
            'province_id' => $this->province->id,
            'is_active' => true,
            'must_change_password' => false,
        ], $attributes));
    }

    private function caseResponse(array $overrides = []): array
    {
        return [
            'data' => array_merge([
                'id' => 71,
                'case_number' => 'EXC-2026-0071',
                'status' => 'draft',
                'initiated_at' => '2026-10-21T14:00:00+02:00',
                'updated_at' => '2026-10-21T14:00:00+02:00',
            ], $overrides),
            'meta' => ['created' => true],
        ];
    }
}
