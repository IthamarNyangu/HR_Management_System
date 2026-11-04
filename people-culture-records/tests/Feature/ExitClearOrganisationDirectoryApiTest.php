<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\District;
use App\Models\Facility;
use App\Models\Province;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExitClearOrganisationDirectoryApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_organisation_lookup_requires_the_integration_token(): void
    {
        config(['services.exitclear.integration_token' => 'test-sync-token']);

        $this->getJson('/api/integrations/exitclear/organisation')
            ->assertUnauthorized();
    }

    public function test_organisation_lookup_returns_hrms_master_data_without_personal_information(): void
    {
        config(['services.exitclear.integration_token' => 'test-sync-token']);
        $province = Province::query()->create(['name' => 'Lusaka', 'code' => 'LUS', 'is_active' => true]);
        $district = District::query()->create(['province_id' => $province->id, 'name' => 'Lusaka', 'code' => 'LUS-LUS', 'is_active' => true]);
        $facility = Facility::query()->create(['district_id' => $district->id, 'name' => 'Lusaka Office', 'code' => 'LSK-HQ', 'is_active' => true]);
        $department = Department::query()->create(['name' => 'People & Culture', 'code' => 'PC', 'is_active' => true]);

        $this->withToken('test-sync-token')
            ->getJson('/api/integrations/exitclear/organisation')
            ->assertOk()
            ->assertJsonPath('data.provinces.0.source_id', $province->id)
            ->assertJsonPath('data.districts.0.province_source_id', $province->id)
            ->assertJsonPath('data.facilities.0.source_id', $facility->id)
            ->assertJsonPath('data.facilities.0.district_source_id', $district->id)
            ->assertJsonPath('data.facilities.0.province_source_id', $province->id)
            ->assertJsonPath('data.departments.0.source_id', $department->id)
            ->assertJsonMissingPath('data.employees');
    }
}
