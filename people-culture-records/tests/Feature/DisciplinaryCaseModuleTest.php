<?php

namespace Tests\Feature;

use App\Models\CaseStatus;
use App\Models\DisciplinaryCase;
use App\Models\District;
use App\Models\Employee;
use App\Models\Province;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DisciplinaryCaseModuleTest extends TestCase
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
    private Employee $northernEmployee;
    private Employee $luapulaEmployee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminRole = Role::create(['name' => 'Admin', 'code' => 'ADMIN', 'is_active' => true]);
        $this->managerRole = Role::create(['name' => 'HR Manager', 'code' => 'HRM', 'is_active' => true]);
        $this->officerRole = Role::create(['name' => 'HR Officer', 'code' => 'HRO', 'is_active' => true]);
        $this->viewerRole = Role::create(['name' => 'Viewer', 'code' => 'VIEWER', 'is_active' => true]);

        foreach ([
            ['Draft', 'DRAFT'],
            ['Submitted', 'SUBMITTED'],
            ['Active', 'ACTIVE'],
            ['Closed', 'CLOSED'],
            ['Archived', 'ARCHIVED'],
        ] as [$name, $code]) {
            CaseStatus::create(['name' => $name, 'code' => $code, 'is_active' => true]);
        }

        $this->northern = Province::create(['name' => 'Northern', 'code' => 'NOR', 'is_active' => true]);
        $this->luapula = Province::create(['name' => 'Luapula', 'code' => 'LUA', 'is_active' => true]);

        $this->kasama = District::create(['province_id' => $this->northern->id, 'name' => 'Kasama', 'code' => 'NOR-KAS', 'is_active' => true]);
        $this->mansa = District::create(['province_id' => $this->luapula->id, 'name' => 'Mansa', 'code' => 'LUA-MAN', 'is_active' => true]);

        $this->northernEmployee = $this->employee(['employee_no' => 'NOR-001', 'province_id' => $this->northern->id, 'district_id' => $this->kasama->id]);
        $this->luapulaEmployee = $this->employee(['employee_no' => 'LUA-001', 'province_id' => $this->luapula->id, 'district_id' => $this->mansa->id]);
    }

    public function test_unauthenticated_users_cannot_access_disciplinary_cases(): void
    {
        $this->get('/disciplinary-cases')->assertRedirect('/login');
    }

    public function test_admin_can_manage_all_disciplinary_cases(): void
    {
        $case = $this->disciplinaryCase(['province_id' => $this->luapula->id, 'district_id' => $this->mansa->id, 'employee_id' => $this->luapulaEmployee->id]);

        $this->actingAs($this->user($this->adminRole))
            ->get(route('disciplinary-cases.show', $case))
            ->assertOk()
            ->assertSee($case->reference_no);
    }

    public function test_hr_manager_can_approve_submitted_case(): void
    {
        $manager = $this->user($this->managerRole);
        $case = $this->disciplinaryCase(['case_status_id' => $this->caseStatus('SUBMITTED')->id]);

        $this->actingAs($manager)
            ->patch(route('disciplinary-cases.approve', $case))
            ->assertRedirect(route('disciplinary-cases.show', $case));

        $this->assertDatabaseHas('disciplinary_cases', [
            'id' => $case->id,
            'case_status_id' => $this->caseStatus('ACTIVE')->id,
            'approved_by' => $manager->id,
        ]);
    }

    public function test_hr_officer_can_create_and_submit_case_in_assigned_province(): void
    {
        $officer = $this->user($this->officerRole, $this->northern);

        $response = $this->actingAs($officer)
            ->post(route('disciplinary-cases.store'), $this->casePayload([
                'employee_id' => $this->northernEmployee->id,
                'province_id' => $this->northern->id,
                'district_id' => $this->kasama->id,
            ]));

        $response->assertRedirect();

        $case = DisciplinaryCase::firstOrFail();

        $this->assertSame('DC-'.now()->year.'-0001', $case->reference_no);

        $this->actingAs($officer)
            ->patch(route('disciplinary-cases.submit', $case))
            ->assertRedirect(route('disciplinary-cases.show', $case));

        $this->assertDatabaseHas('disciplinary_cases', [
            'id' => $case->id,
            'case_status_id' => $this->caseStatus('SUBMITTED')->id,
        ]);
    }

    public function test_hr_officer_cannot_approve_case(): void
    {
        $officer = $this->user($this->officerRole, $this->northern);
        $case = $this->disciplinaryCase(['case_status_id' => $this->caseStatus('SUBMITTED')->id]);

        $this->actingAs($officer)
            ->patch(route('disciplinary-cases.approve', $case))
            ->assertForbidden();
    }

    public function test_hr_officer_cannot_access_cases_outside_assigned_province(): void
    {
        $officer = $this->user($this->officerRole, $this->northern);
        $case = $this->disciplinaryCase(['province_id' => $this->luapula->id, 'district_id' => $this->mansa->id, 'employee_id' => $this->luapulaEmployee->id]);

        $this->actingAs($officer)
            ->get(route('disciplinary-cases.show', $case))
            ->assertForbidden();
    }

    public function test_viewer_can_view_but_cannot_manage_cases(): void
    {
        $viewer = $this->user($this->viewerRole, $this->northern);
        $case = $this->disciplinaryCase();

        $this->actingAs($viewer)->get(route('disciplinary-cases.show', $case))->assertOk();
        $this->actingAs($viewer)->get(route('disciplinary-cases.create'))->assertForbidden();
        $this->actingAs($viewer)->get(route('disciplinary-cases.edit', $case))->assertForbidden();
        $this->actingAs($viewer)->patch(route('disciplinary-cases.submit', $case))->assertForbidden();
        $this->actingAs($viewer)->patch(route('disciplinary-cases.archive', $case))->assertForbidden();
    }

    public function test_expiry_date_cannot_be_before_effective_date(): void
    {
        $admin = $this->user($this->adminRole);

        $this->actingAs($admin)
            ->from(route('disciplinary-cases.create'))
            ->post(route('disciplinary-cases.store'), $this->casePayload([
                'effective_date' => '2026-05-10',
                'expiry_date' => '2026-05-09',
            ]))
            ->assertRedirect(route('disciplinary-cases.create'))
            ->assertSessionHasErrors('expiry_date');
    }

    public function test_archive_and_restore_work(): void
    {
        $admin = $this->user($this->adminRole);
        $case = $this->disciplinaryCase();

        $this->actingAs($admin)
            ->patch(route('disciplinary-cases.archive', $case))
            ->assertRedirect(route('disciplinary-cases.index'));

        $this->assertSoftDeleted('disciplinary_cases', ['id' => $case->id]);

        $this->actingAs($admin)
            ->patch(route('disciplinary-cases.restore', $case->id))
            ->assertRedirect(route('disciplinary-cases.show', $case->id));

        $this->assertDatabaseHas('disciplinary_cases', [
            'id' => $case->id,
            'deleted_at' => null,
            'archived_by' => null,
        ]);
    }

    public function test_auto_close_command_closes_expired_active_cases(): void
    {
        $this->user($this->managerRole);
        $case = $this->disciplinaryCase([
            'case_status_id' => $this->caseStatus('ACTIVE')->id,
            'expiry_date' => now()->subDay()->toDateString(),
        ]);

        $this->artisan('disciplinary:auto-close-expired')
            ->expectsOutput('Closed 1 expired disciplinary case(s).')
            ->assertExitCode(0);

        $this->assertDatabaseHas('disciplinary_cases', [
            'id' => $case->id,
            'case_status_id' => $this->caseStatus('CLOSED')->id,
        ]);

        $this->assertDatabaseHas('notifications', [
            'notifiable_type' => User::class,
        ]);
    }

    public function test_attachment_upload_is_validated(): void
    {
        Storage::fake('local');

        $admin = $this->user($this->adminRole);
        $case = $this->disciplinaryCase();

        $this->actingAs($admin)
            ->from(route('disciplinary-cases.show', $case))
            ->post(route('disciplinary-cases.attachments.store', $case), [
                'document' => UploadedFile::fake()->create('notes.txt', 1, 'text/plain'),
            ])
            ->assertRedirect(route('disciplinary-cases.show', $case))
            ->assertSessionHasErrors('document');

        $this->actingAs($admin)
            ->post(route('disciplinary-cases.attachments.store', $case), [
                'document' => UploadedFile::fake()->create('letter.pdf', 10, 'application/pdf'),
            ])
            ->assertRedirect(route('disciplinary-cases.show', $case));

        $this->assertDatabaseHas('attachments', [
            'attachable_type' => DisciplinaryCase::class,
            'attachable_id' => $case->id,
            'original_filename' => 'letter.pdf',
        ]);
    }

    public function test_supporting_document_can_be_uploaded_while_creating_case(): void
    {
        Storage::fake('local');

        $admin = $this->user($this->adminRole);

        $this->actingAs($admin)
            ->post(route('disciplinary-cases.store'), $this->casePayload([
                'supporting_document' => UploadedFile::fake()->create('initial-letter.pdf', 20, 'application/pdf'),
            ]))
            ->assertRedirect();

        $case = DisciplinaryCase::with('attachments')->firstOrFail();
        $attachment = $case->attachments->first();

        $this->assertNotNull($attachment);
        $this->assertSame('initial-letter.pdf', $attachment->original_filename);
        Storage::disk('local')->assertExists($attachment->file_path);

        $this->actingAs($admin)
            ->get(route('disciplinary-cases.show', $case))
            ->assertOk()
            ->assertSee('initial-letter.pdf');
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
    private function disciplinaryCase(array $attributes = []): DisciplinaryCase
    {
        return DisciplinaryCase::create(array_merge([
            'reference_no' => fake()->unique()->bothify('DC-2026-####'),
            'employee_id' => $this->northernEmployee->id,
            'province_id' => $this->northern->id,
            'district_id' => $this->kasama->id,
            'nature_of_offence' => 'Attendance concern',
            'case_status_id' => $this->caseStatus('DRAFT')->id,
            'effective_date' => now()->toDateString(),
            'expiry_date' => now()->addDays(30)->toDateString(),
        ], $attributes));
    }

    /**
     * @param array<string, mixed> $overrides
     * @return array<string, mixed>
     */
    private function casePayload(array $overrides = []): array
    {
        return array_merge([
            'employee_id' => $this->northernEmployee->id,
            'province_id' => $this->northern->id,
            'district_id' => $this->kasama->id,
            'case_status_id' => $this->caseStatus('DRAFT')->id,
            'nature_of_offence' => 'Attendance concern',
            'effective_date' => now()->toDateString(),
            'expiry_date' => now()->addDays(30)->toDateString(),
        ], $overrides);
    }

    private function caseStatus(string $code): CaseStatus
    {
        return CaseStatus::where('code', $code)->firstOrFail();
    }
}
