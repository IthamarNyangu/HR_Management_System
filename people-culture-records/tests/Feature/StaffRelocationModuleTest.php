<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\District;
use App\Models\Employee;
use App\Models\Facility;
use App\Models\JobTitle;
use App\Models\Project;
use App\Models\Province;
use App\Models\RelocationReason;
use App\Models\Role;
use App\Models\StaffRelocation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StaffRelocationModuleTest extends TestCase
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
    private Facility $chinsaliFacility;
    private Department $peopleCulture;
    private Project $generalOperations;
    private JobTitle $jobTitle;
    private RelocationReason $relocationReason;
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

        $this->kasamaFacility = Facility::create(['district_id' => $this->kasama->id, 'name' => 'Kasama General Hospital', 'code' => 'KGH', 'is_active' => true]);
        $this->mansaFacility = Facility::create(['district_id' => $this->mansa->id, 'name' => 'Mansa District Hospital', 'code' => 'MDH', 'is_active' => true]);
        $this->chinsaliFacility = Facility::create(['district_id' => $this->chinsali->id, 'name' => 'Chinsali Hospital', 'code' => 'CHI', 'is_active' => true]);

        $this->peopleCulture = Department::create(['name' => 'People & Culture', 'code' => 'PC', 'is_active' => true]);
        $this->generalOperations = Project::create(['name' => 'General Operations', 'code' => 'OPS', 'is_active' => true]);
        $this->jobTitle = JobTitle::create(['name' => 'HR Officer', 'code' => 'HRO-JT', 'is_active' => true]);
        $this->relocationReason = RelocationReason::create(['name' => 'Operational Need', 'code' => 'OPS-NEED', 'is_active' => true]);

        $this->northernEmployee = $this->employee([
            'employee_no' => 'NOR-001',
            'province_id' => $this->northern->id,
            'district_id' => $this->kasama->id,
            'facility_id' => $this->kasamaFacility->id,
            'department_id' => $this->peopleCulture->id,
            'project_id' => $this->generalOperations->id,
            'job_title_id' => $this->jobTitle->id,
        ]);

        $this->luapulaEmployee = $this->employee([
            'employee_no' => 'LUA-001',
            'province_id' => $this->luapula->id,
            'district_id' => $this->mansa->id,
            'facility_id' => $this->mansaFacility->id,
            'department_id' => $this->peopleCulture->id,
            'project_id' => $this->generalOperations->id,
            'job_title_id' => $this->jobTitle->id,
        ]);

        $this->muchingaEmployee = $this->employee([
            'employee_no' => 'MUC-001',
            'province_id' => $this->muchinga->id,
            'district_id' => $this->chinsali->id,
            'facility_id' => $this->chinsaliFacility->id,
            'department_id' => $this->peopleCulture->id,
            'project_id' => $this->generalOperations->id,
            'job_title_id' => $this->jobTitle->id,
        ]);
    }

    public function test_unauthenticated_users_cannot_access_staff_relocations(): void
    {
        $this->get('/staff-relocations')->assertRedirect('/login');
    }

    public function test_admin_can_manage_all_relocations(): void
    {
        $relocation = $this->relocation([
            'employee_id' => $this->luapulaEmployee->id,
            'from_province_id' => $this->luapula->id,
            'from_district_id' => $this->mansa->id,
            'from_facility_id' => $this->mansaFacility->id,
            'to_province_id' => $this->northern->id,
            'to_district_id' => $this->kasama->id,
            'to_facility_id' => $this->kasamaFacility->id,
        ]);

        $this->actingAs($this->user($this->adminRole))
            ->get(route('staff-relocations.show', $relocation))
            ->assertOk()
            ->assertSee($relocation->reference_no);
    }

    public function test_hr_manager_can_manage_all_relocations(): void
    {
        $manager = $this->user($this->managerRole);

        $this->actingAs($manager)
            ->post(route('staff-relocations.store'), $this->relocationPayload([
                'employee_id' => $this->luapulaEmployee->id,
                'from_province_id' => $this->luapula->id,
                'from_district_id' => $this->mansa->id,
                'from_facility_id' => $this->mansaFacility->id,
                'to_province_id' => $this->northern->id,
                'to_district_id' => $this->kasama->id,
                'to_facility_id' => $this->kasamaFacility->id,
            ]))
            ->assertRedirect();

        $this->assertDatabaseHas('staff_relocations', [
            'employee_id' => $this->luapulaEmployee->id,
            'from_province_id' => $this->luapula->id,
            'to_province_id' => $this->northern->id,
        ]);
    }

    public function test_hr_officer_can_manage_relocation_where_their_province_is_from_province(): void
    {
        $officer = $this->user($this->officerRole, $this->northern);

        $this->actingAs($officer)
            ->post(route('staff-relocations.store'), $this->relocationPayload())
            ->assertRedirect();

        $this->assertDatabaseHas('staff_relocations', [
            'employee_id' => $this->northernEmployee->id,
            'from_province_id' => $this->northern->id,
            'to_province_id' => $this->luapula->id,
        ]);
    }

    public function test_hr_officer_can_manage_relocation_where_their_province_is_to_province(): void
    {
        $officer = $this->user($this->officerRole, $this->northern);

        $this->actingAs($officer)
            ->post(route('staff-relocations.store'), $this->relocationPayload([
                'employee_id' => $this->luapulaEmployee->id,
                'from_province_id' => $this->luapula->id,
                'from_district_id' => $this->mansa->id,
                'from_facility_id' => $this->mansaFacility->id,
                'to_province_id' => $this->northern->id,
                'to_district_id' => $this->kasama->id,
                'to_facility_id' => $this->kasamaFacility->id,
            ]))
            ->assertRedirect();

        $relocation = StaffRelocation::firstOrFail();

        $this->actingAs($officer)
            ->get(route('staff-relocations.show', $relocation))
            ->assertOk()
            ->assertSee($relocation->reference_no);
    }

    public function test_hr_officer_cannot_manage_unrelated_relocation(): void
    {
        $officer = $this->user($this->officerRole, $this->northern);
        $unrelated = $this->relocation([
            'employee_id' => $this->muchingaEmployee->id,
            'from_province_id' => $this->muchinga->id,
            'from_district_id' => $this->chinsali->id,
            'from_facility_id' => $this->chinsaliFacility->id,
            'to_province_id' => $this->luapula->id,
            'to_district_id' => $this->mansa->id,
            'to_facility_id' => $this->mansaFacility->id,
        ]);

        $this->actingAs($officer)
            ->get(route('staff-relocations.show', $unrelated))
            ->assertForbidden();

        $this->actingAs($officer)
            ->from(route('staff-relocations.create'))
            ->post(route('staff-relocations.store'), $this->relocationPayload([
                'employee_id' => $this->muchingaEmployee->id,
                'from_province_id' => $this->muchinga->id,
                'from_district_id' => $this->chinsali->id,
                'from_facility_id' => $this->chinsaliFacility->id,
                'to_province_id' => $this->luapula->id,
                'to_district_id' => $this->mansa->id,
                'to_facility_id' => $this->mansaFacility->id,
            ]))
            ->assertRedirect(route('staff-relocations.create'))
            ->assertSessionHasErrors('to_province_id');
    }

    public function test_viewer_can_view_but_cannot_create_edit_or_archive(): void
    {
        $viewer = $this->user($this->viewerRole, $this->northern);
        $relocation = $this->relocation();

        $this->actingAs($viewer)->get(route('staff-relocations.show', $relocation))->assertOk();
        $this->actingAs($viewer)->get(route('staff-relocations.create'))->assertForbidden();
        $this->actingAs($viewer)->get(route('staff-relocations.edit', $relocation))->assertForbidden();
        $this->actingAs($viewer)->patch(route('staff-relocations.archive', $relocation))->assertForbidden();
    }

    public function test_reference_number_is_generated_and_from_location_defaults_from_employee(): void
    {
        $this->actingAs($this->user($this->adminRole))
            ->post(route('staff-relocations.store'), $this->relocationPayload([
                'job_title_id' => '',
                'project_id' => '',
                'department_id' => '',
                'from_province_id' => '',
                'from_district_id' => '',
                'from_facility_id' => '',
            ]))
            ->assertRedirect();

        $relocation = StaffRelocation::firstOrFail();

        $this->assertSame('REL-'.now()->year.'-0001', $relocation->reference_no);
        $this->assertSame($this->northern->id, $relocation->from_province_id);
        $this->assertSame($this->kasama->id, $relocation->from_district_id);
        $this->assertSame($this->kasamaFacility->id, $relocation->from_facility_id);
        $this->assertSame($this->jobTitle->id, $relocation->job_title_id);
        $this->assertSame($this->generalOperations->id, $relocation->project_id);
        $this->assertSame($this->peopleCulture->id, $relocation->department_id);
    }

    public function test_update_employee_location_updates_employee_current_location_only_when_checked(): void
    {
        $this->actingAs($this->user($this->adminRole))
            ->post(route('staff-relocations.store'), $this->relocationPayload([
                'update_employee_location' => '1',
            ]))
            ->assertRedirect();

        $this->assertDatabaseHas('employees', [
            'id' => $this->northernEmployee->id,
            'province_id' => $this->luapula->id,
            'district_id' => $this->mansa->id,
            'facility_id' => $this->mansaFacility->id,
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'employee_location_updated_from_relocation',
        ]);
    }

    public function test_unchecked_update_employee_location_does_not_update_employee_current_location(): void
    {
        $this->actingAs($this->user($this->adminRole))
            ->post(route('staff-relocations.store'), $this->relocationPayload([
                'update_employee_location' => '0',
            ]))
            ->assertRedirect();

        $this->assertDatabaseHas('employees', [
            'id' => $this->northernEmployee->id,
            'province_id' => $this->northern->id,
            'district_id' => $this->kasama->id,
            'facility_id' => $this->kasamaFacility->id,
        ]);
    }

    public function test_validation_rejects_from_and_to_district_and_facility_mismatches(): void
    {
        $this->actingAs($this->user($this->adminRole))
            ->from(route('staff-relocations.create'))
            ->post(route('staff-relocations.store'), $this->relocationPayload([
                'from_district_id' => $this->mansa->id,
                'from_facility_id' => $this->chinsaliFacility->id,
                'to_district_id' => $this->kasama->id,
                'to_facility_id' => $this->mansaFacility->id,
            ]))
            ->assertRedirect(route('staff-relocations.create'))
            ->assertSessionHasErrors(['from_district_id', 'from_facility_id', 'to_facility_id']);
    }

    public function test_archive_and_restore_work(): void
    {
        $admin = $this->user($this->adminRole);
        $relocation = $this->relocation();

        $this->actingAs($admin)
            ->patch(route('staff-relocations.archive', $relocation))
            ->assertRedirect(route('staff-relocations.index'));

        $this->assertSoftDeleted('staff_relocations', ['id' => $relocation->id]);

        $this->actingAs($admin)
            ->patch(route('staff-relocations.restore', $relocation->id))
            ->assertRedirect(route('staff-relocations.show', $relocation->id));

        $this->assertDatabaseHas('staff_relocations', [
            'id' => $relocation->id,
            'deleted_at' => null,
            'archived_by' => null,
        ]);
    }

    public function test_attachment_upload_is_validated(): void
    {
        Storage::fake('local');

        $admin = $this->user($this->adminRole);
        $relocation = $this->relocation();

        $this->actingAs($admin)
            ->from(route('staff-relocations.show', $relocation))
            ->post(route('staff-relocations.attachments.store', $relocation), [
                'document' => UploadedFile::fake()->create('notes.txt', 1, 'text/plain'),
            ])
            ->assertRedirect(route('staff-relocations.show', $relocation))
            ->assertSessionHasErrors('document');

        $this->actingAs($admin)
            ->post(route('staff-relocations.attachments.store', $relocation), [
                'document' => UploadedFile::fake()->create('relocation-letter.pdf', 10, 'application/pdf'),
            ])
            ->assertRedirect(route('staff-relocations.show', $relocation));

        $this->assertDatabaseHas('attachments', [
            'attachable_type' => StaffRelocation::class,
            'attachable_id' => $relocation->id,
            'original_filename' => 'relocation-letter.pdf',
        ]);
    }

    public function test_employee_profile_shows_relocation_history(): void
    {
        $relocation = $this->relocation();

        $this->actingAs($this->user($this->adminRole))
            ->get(route('employees.show', $this->northernEmployee))
            ->assertOk()
            ->assertSee('Relocation History')
            ->assertSee($relocation->reference_no);
    }

    public function test_dashboard_relocation_counts_respect_from_to_visibility_and_amounts(): void
    {
        $this->relocation([
            'relocation_amount' => 1200.50,
        ]);

        $this->relocation([
            'reference_no' => 'REL-'.now()->year.'-0099',
            'employee_id' => $this->luapulaEmployee->id,
            'from_province_id' => $this->luapula->id,
            'from_district_id' => $this->mansa->id,
            'from_facility_id' => $this->mansaFacility->id,
            'to_province_id' => $this->northern->id,
            'to_district_id' => $this->kasama->id,
            'to_facility_id' => $this->kasamaFacility->id,
            'relocation_amount' => 300.00,
        ]);

        $this->relocation([
            'reference_no' => 'REL-'.now()->year.'-0100',
            'employee_id' => $this->muchingaEmployee->id,
            'from_province_id' => $this->muchinga->id,
            'from_district_id' => $this->chinsali->id,
            'from_facility_id' => $this->chinsaliFacility->id,
            'to_province_id' => $this->luapula->id,
            'to_district_id' => $this->mansa->id,
            'to_facility_id' => $this->mansaFacility->id,
            'relocation_amount' => 500.00,
        ]);

        $this->actingAs($this->user($this->officerRole, $this->northern))
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Relocations This Year')
            ->assertSee('REL-'.now()->year.'-0099')
            ->assertDontSee('REL-'.now()->year.'-0100')
            ->assertSee('1,500.50');
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

    /**
     * @param array<string, mixed> $attributes
     */
    private function relocation(array $attributes = []): StaffRelocation
    {
        return StaffRelocation::create(array_merge([
            'reference_no' => fake()->unique()->bothify('REL-2026-####'),
            'employee_id' => $this->northernEmployee->id,
            'job_title_id' => $this->jobTitle->id,
            'project_id' => $this->generalOperations->id,
            'department_id' => $this->peopleCulture->id,
            'from_province_id' => $this->northern->id,
            'from_district_id' => $this->kasama->id,
            'from_facility_id' => $this->kasamaFacility->id,
            'to_province_id' => $this->luapula->id,
            'to_district_id' => $this->mansa->id,
            'to_facility_id' => $this->mansaFacility->id,
            'relocation_reason_id' => $this->relocationReason->id,
            'effective_date' => now()->toDateString(),
            'relocation_amount' => 800.00,
        ], $attributes));
    }

    /**
     * @param array<string, mixed> $overrides
     * @return array<string, mixed>
     */
    private function relocationPayload(array $overrides = []): array
    {
        return array_merge([
            'employee_id' => $this->northernEmployee->id,
            'job_title_id' => $this->jobTitle->id,
            'project_id' => $this->generalOperations->id,
            'department_id' => $this->peopleCulture->id,
            'from_province_id' => $this->northern->id,
            'from_district_id' => $this->kasama->id,
            'from_facility_id' => $this->kasamaFacility->id,
            'to_province_id' => $this->luapula->id,
            'to_district_id' => $this->mansa->id,
            'to_facility_id' => $this->mansaFacility->id,
            'relocation_reason_id' => $this->relocationReason->id,
            'effective_date' => now()->toDateString(),
            'relocation_amount' => 2500.00,
            'comment' => 'Relocation approved.',
        ], $overrides);
    }
}
