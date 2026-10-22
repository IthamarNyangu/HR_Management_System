<?php

namespace Tests\Feature;

use App\Models\Employee;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ExitClearEmployeeDirectoryApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_lookup_requires_the_integration_token(): void
    {
        config(['services.exitclear.integration_token' => 'test-sync-token']);

        $this->getJson('/api/integrations/exitclear/employees?search=Ithamar')
            ->assertUnauthorized();
    }

    public function test_lookup_returns_only_matching_active_employees(): void
    {
        config(['services.exitclear.integration_token' => 'test-sync-token']);
        $this->employee(['employee_no' => '26496', 'first_name' => 'Ithamar', 'last_name' => 'Nyangu']);
        $this->employee(['employee_no' => '99999', 'first_name' => 'Someone', 'last_name' => 'Else']);

        $this->withToken('test-sync-token')
            ->getJson('/api/integrations/exitclear/employees?search=Ithamar')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.employee_no', '26496')
            ->assertJsonMissingPath('data.0.work_email');
    }

    public function test_lookup_can_fetch_one_employee_for_server_side_snapshot_validation(): void
    {
        config(['services.exitclear.integration_token' => 'test-sync-token']);
        $employee = $this->employee();

        $this->withToken('test-sync-token')
            ->getJson('/api/integrations/exitclear/employees?source_id='.$employee->id)
            ->assertOk()
            ->assertJsonPath('data.0.source_id', $employee->id);
    }

    public function test_lookup_resolves_a_legacy_line_manager_value_to_an_employee_snapshot(): void
    {
        config(['services.exitclear.integration_token' => 'test-sync-token']);
        $supervisor = $this->employee([
            'employee_no' => '22866',
            'first_name' => 'Paul',
            'last_name' => 'Chinyemba',
            'email' => 'paul.chinyemba@righttocare-zambia.org',
        ]);
        $employee = $this->employee(['supervisor_name' => '22866 - Paul Chinyemba']);

        $this->withToken('test-sync-token')
            ->getJson('/api/integrations/exitclear/employees?source_id='.$employee->id)
            ->assertOk()
            ->assertJsonPath('data.0.supervisor.source_id', $supervisor->id)
            ->assertJsonPath('data.0.supervisor.employee_no', '22866')
            ->assertJsonPath('data.0.supervisor.full_name', 'Paul Chinyemba')
            ->assertJsonPath('data.0.supervisor.work_email', 'paul.chinyemba@righttocare-zambia.org');
    }

    private function employee(array $attributes = []): Employee
    {
        $provinceId = DB::table('provinces')->insertGetId(['name' => fake()->unique()->city(), 'code' => fake()->unique()->lexify('???'), 'is_active' => true, 'created_at' => now(), 'updated_at' => now()]);
        $districtId = DB::table('districts')->insertGetId(['province_id' => $provinceId, 'name' => fake()->unique()->city(), 'code' => fake()->unique()->lexify('???'), 'is_active' => true, 'created_at' => now(), 'updated_at' => now()]);

        $id = DB::table('employees')->insertGetId(array_merge([
            'employee_no' => fake()->unique()->numerify('#####'),
            'first_name' => 'Test',
            'last_name' => 'Employee',
            'province_id' => $provinceId,
            'district_id' => $districtId,
            'created_at' => now(),
            'updated_at' => now(),
        ], $attributes));

        return Employee::query()->findOrFail($id);
    }
}
