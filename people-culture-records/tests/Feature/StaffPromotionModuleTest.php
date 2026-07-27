<?php

namespace Tests\Feature;

use App\Models\AppointmentStatus;
use App\Models\Department;
use App\Models\District;
use App\Models\Employee;
use App\Models\JobTitle;
use App\Models\PromotionType;
use App\Models\Province;
use App\Models\Role;
use App\Models\StaffPromotion;
use App\Models\TemporaryAppointment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StaffPromotionModuleTest extends TestCase
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

    private Department $peopleCulture;

    private JobTitle $oldTitle;

    private JobTitle $newTitle;

    private PromotionType $promotionType;

    private Employee $northernEmployee;

    private Employee $luapulaEmployee;

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
        $this->peopleCulture = Department::create(['name' => 'People & Culture', 'code' => 'PC', 'is_active' => true]);
        $this->oldTitle = JobTitle::create(['name' => 'HR Assistant', 'code' => 'HRA', 'is_active' => true]);
        $this->newTitle = JobTitle::create(['name' => 'HR Officer', 'code' => 'HRO-JT', 'is_active' => true]);
        $this->promotionType = PromotionType::create(['name' => 'Merit', 'code' => 'MERIT', 'is_active' => true]);

        $this->northernEmployee = $this->employee([
            'employee_no' => 'NOR-001',
            'province_id' => $this->northern->id,
            'district_id' => $this->kasama->id,
            'department_id' => $this->peopleCulture->id,
            'job_title_id' => $this->oldTitle->id,
        ]);

        $this->luapulaEmployee = $this->employee([
            'employee_no' => 'LUA-001',
            'province_id' => $this->luapula->id,
            'district_id' => $this->mansa->id,
            'job_title_id' => $this->oldTitle->id,
        ]);
    }

    public function test_unauthenticated_users_cannot_access_staff_promotions(): void
    {
        $this->get('/staff-promotions')->assertRedirect('/login');
    }

    public function test_admin_can_manage_all_promotions(): void
    {
        $promotion = $this->promotion([
            'employee_id' => $this->luapulaEmployee->id,
            'province_id' => $this->luapula->id,
            'district_id' => $this->mansa->id,
        ]);

        $this->actingAs($this->user($this->adminRole))
            ->get(route('staff-promotions.show', $promotion))
            ->assertOk()
            ->assertSee($promotion->reference_no);
    }

    public function test_hr_manager_can_manage_all_promotions(): void
    {
        $manager = $this->user($this->managerRole);

        $this->actingAs($manager)
            ->post(route('staff-promotions.store'), $this->promotionPayload([
                'employee_id' => $this->luapulaEmployee->id,
                'province_id' => $this->luapula->id,
                'district_id' => $this->mansa->id,
            ]))
            ->assertRedirect();

        $this->assertDatabaseHas('staff_promotions', [
            'employee_id' => $this->luapulaEmployee->id,
            'province_id' => $this->luapula->id,
        ]);
    }

    public function test_hr_officer_can_only_manage_promotions_in_assigned_province(): void
    {
        $officer = $this->user($this->officerRole, $this->northern);
        $outsidePromotion = $this->promotion([
            'employee_id' => $this->luapulaEmployee->id,
            'province_id' => $this->luapula->id,
            'district_id' => $this->mansa->id,
        ]);

        $this->actingAs($officer)
            ->post(route('staff-promotions.store'), $this->promotionPayload())
            ->assertRedirect();

        $this->actingAs($officer)
            ->get(route('staff-promotions.show', $outsidePromotion))
            ->assertForbidden();

        $this->actingAs($officer)
            ->from(route('staff-promotions.create'))
            ->post(route('staff-promotions.store'), $this->promotionPayload([
                'employee_id' => $this->luapulaEmployee->id,
                'province_id' => $this->luapula->id,
                'district_id' => $this->mansa->id,
            ]))
            ->assertRedirect(route('staff-promotions.create'))
            ->assertSessionHasErrors('province_id');
    }

    public function test_viewer_can_view_but_cannot_manage_promotions(): void
    {
        $viewer = $this->user($this->viewerRole, $this->northern);
        $promotion = $this->promotion();

        $this->actingAs($viewer)->get(route('staff-promotions.show', $promotion))->assertOk();
        $this->actingAs($viewer)->get(route('staff-promotions.create'))->assertForbidden();
        $this->actingAs($viewer)->get(route('staff-promotions.edit', $promotion))->assertForbidden();
        $this->actingAs($viewer)->patch(route('staff-promotions.archive', $promotion))->assertForbidden();
    }

    public function test_reference_number_is_generated_and_old_job_title_defaults_from_employee(): void
    {
        $this->actingAs($this->user($this->adminRole))
            ->post(route('staff-promotions.store'), $this->promotionPayload(['old_job_title_id' => '']))
            ->assertRedirect();

        $promotion = StaffPromotion::firstOrFail();

        $this->assertSame('PROM-'.now()->year.'-0001', $promotion->reference_no);
        $this->assertSame($this->oldTitle->id, $promotion->old_job_title_id);
    }

    public function test_creating_promotion_updates_employee_current_job_title_when_effective_today(): void
    {
        $this->actingAs($this->user($this->adminRole))
            ->post(route('staff-promotions.store'), $this->promotionPayload())
            ->assertRedirect();

        $this->assertDatabaseHas('employees', [
            'id' => $this->northernEmployee->id,
            'job_title_id' => $this->newTitle->id,
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'employee_job_title_updated_from_promotion',
        ]);

        $this->assertDatabaseMissing('staff_promotions', [
            'employee_id' => $this->northernEmployee->id,
            'job_title_applied_at' => null,
        ]);
    }

    public function test_future_dated_promotion_waits_for_command_before_updating_employee_job_title(): void
    {
        $this->actingAs($this->user($this->adminRole))
            ->post(route('staff-promotions.store'), $this->promotionPayload([
                'effective_date' => now()->addDays(7)->toDateString(),
            ]))
            ->assertRedirect();

        $this->assertDatabaseHas('employees', [
            'id' => $this->northernEmployee->id,
            'job_title_id' => $this->oldTitle->id,
        ]);

        $promotion = StaffPromotion::firstOrFail();

        $this->assertNull($promotion->job_title_applied_at);

        $this->travel(7)->days();

        $this->artisan('promotions:apply-effective')
            ->expectsOutput('Processed 1 due staff promotion(s).')
            ->assertExitCode(0);

        $this->assertDatabaseHas('employees', [
            'id' => $this->northernEmployee->id,
            'job_title_id' => $this->newTitle->id,
        ]);

        $this->assertNotNull($promotion->fresh()->job_title_applied_at);
    }

    public function test_acting_promotion_preserves_permanent_title_and_can_create_linked_temporary_appointment_later(): void
    {
        $actingType = PromotionType::where('code', 'ACTING')->firstOrFail();
        $admin = $this->user($this->adminRole);
        $activeStatusId = AppointmentStatus::create([
            'name' => 'Active',
            'code' => 'ACTIVE',
            'is_active' => true,
        ])->id;

        $this->actingAs($admin)
            ->post(route('staff-promotions.store'), $this->promotionPayload([
                'promotion_type_id' => $actingType->id,
            ]))
            ->assertRedirect();

        $promotion = StaffPromotion::firstOrFail();

        $this->assertDatabaseHas('employees', [
            'id' => $this->northernEmployee->id,
            'job_title_id' => $this->oldTitle->id,
        ]);
        $this->assertNull($promotion->job_title_applied_at);

        $this->actingAs($admin)
            ->get(route('staff-promotions.show', $promotion))
            ->assertOk()
            ->assertSee('Create Temporary Appointment')
            ->assertSee('Acting Job Title');

        $createResponse = $this->actingAs($admin)
            ->get(route('temporary-appointments.create', ['promotion_id' => $promotion->id]));

        $createResponse
            ->assertOk()
            ->assertSee("Creating from Acting Promotion {$promotion->reference_no}")
            ->assertSee('name="staff_promotion_id"', false)
            ->assertSee('value="'.$promotion->id.'"', false);

        $this->actingAs($admin)
            ->post(route('temporary-appointments.store'), [
                'staff_promotion_id' => $promotion->id,
                'employee_id' => $this->northernEmployee->id,
                'province_id' => $this->northern->id,
                'district_id' => $this->kasama->id,
                'current_job_title_id' => $this->oldTitle->id,
                'temporary_job_title_id' => $this->newTitle->id,
                'appointment_status_id' => $activeStatusId,
                'start_date' => now()->toDateString(),
                'end_date' => now()->addMonths(3)->toDateString(),
                'reason' => 'Acting promotion period.',
            ])
            ->assertRedirect();

        $appointment = TemporaryAppointment::firstOrFail();

        $this->assertSame($promotion->id, $appointment->staff_promotion_id);
        $this->assertSame($promotion->id, $appointment->staffPromotion->id);
        $this->assertSame($appointment->id, $promotion->fresh()->temporaryAppointment->id);
    }

    public function test_editing_an_already_applied_old_promotion_does_not_overwrite_employee_current_job_title_again(): void
    {
        $promotion = $this->promotion([
            'job_title_applied_at' => now()->subDay(),
        ]);

        $this->northernEmployee->update([
            'job_title_id' => $this->newTitle->id,
        ]);

        $laterTitle = JobTitle::create(['name' => 'Senior HR Officer', 'code' => 'SHRO', 'is_active' => true]);

        $this->actingAs($this->user($this->adminRole))
            ->put(route('staff-promotions.update', $promotion), $this->promotionPayload([
                'new_job_title_id' => $laterTitle->id,
            ]))
            ->assertRedirect(route('staff-promotions.show', $promotion));

        $this->assertDatabaseHas('employees', [
            'id' => $this->northernEmployee->id,
            'job_title_id' => $this->newTitle->id,
        ]);
    }

    public function test_archive_and_restore_work(): void
    {
        $admin = $this->user($this->adminRole);
        $promotion = $this->promotion();

        $this->actingAs($admin)
            ->patch(route('staff-promotions.archive', $promotion))
            ->assertRedirect(route('staff-promotions.index'));

        $this->assertSoftDeleted('staff_promotions', ['id' => $promotion->id]);

        $this->actingAs($admin)
            ->patch(route('staff-promotions.restore', $promotion->id))
            ->assertRedirect(route('staff-promotions.show', $promotion->id));

        $this->assertDatabaseHas('staff_promotions', [
            'id' => $promotion->id,
            'deleted_at' => null,
            'archived_by' => null,
        ]);
    }

    public function test_attachment_upload_is_validated(): void
    {
        Storage::fake('local');

        $admin = $this->user($this->adminRole);
        $promotion = $this->promotion();

        $this->actingAs($admin)
            ->from(route('staff-promotions.show', $promotion))
            ->post(route('staff-promotions.attachments.store', $promotion), [
                'document' => UploadedFile::fake()->create('notes.txt', 1, 'text/plain'),
            ])
            ->assertRedirect(route('staff-promotions.show', $promotion))
            ->assertSessionHasErrors('document');

        $this->actingAs($admin)
            ->post(route('staff-promotions.attachments.store', $promotion), [
                'document' => UploadedFile::fake()->create('promotion-letter.pdf', 10, 'application/pdf'),
            ])
            ->assertRedirect(route('staff-promotions.show', $promotion));

        $this->assertDatabaseHas('attachments', [
            'attachable_type' => StaffPromotion::class,
            'attachable_id' => $promotion->id,
            'original_filename' => 'promotion-letter.pdf',
        ]);
    }

    public function test_employee_profile_shows_promotion_history(): void
    {
        $promotion = $this->promotion();

        $this->actingAs($this->user($this->adminRole))
            ->get(route('employees.show', $this->northernEmployee))
            ->assertOk()
            ->assertSee('Promotion History')
            ->assertSee($promotion->reference_no);
    }

    public function test_dashboard_promotion_counts_respect_province_restrictions(): void
    {
        $this->promotion();
        $this->promotion([
            'reference_no' => 'PROM-2026-0099',
            'employee_id' => $this->luapulaEmployee->id,
            'province_id' => $this->luapula->id,
            'district_id' => $this->mansa->id,
        ]);

        $this->actingAs($this->user($this->officerRole, $this->northern))
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Promotions This Year')
            ->assertSee('NOR-001')
            ->assertDontSee('LUA-001');
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
     * @param  array<string, mixed>  $attributes
     */
    private function promotion(array $attributes = []): StaffPromotion
    {
        return StaffPromotion::create(array_merge([
            'reference_no' => fake()->unique()->bothify('PROM-2026-####'),
            'employee_id' => $this->northernEmployee->id,
            'province_id' => $this->northern->id,
            'district_id' => $this->kasama->id,
            'department_id' => $this->peopleCulture->id,
            'old_job_title_id' => $this->oldTitle->id,
            'new_job_title_id' => $this->newTitle->id,
            'promotion_type_id' => $this->promotionType->id,
            'promotion_date' => now()->toDateString(),
        ], $attributes));
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function promotionPayload(array $overrides = []): array
    {
        return array_merge([
            'employee_id' => $this->northernEmployee->id,
            'province_id' => $this->northern->id,
            'district_id' => $this->kasama->id,
            'department_id' => $this->peopleCulture->id,
            'old_job_title_id' => $this->oldTitle->id,
            'new_job_title_id' => $this->newTitle->id,
            'promotion_type_id' => $this->promotionType->id,
            'promotion_date' => now()->toDateString(),
            'effective_date' => now()->toDateString(),
            'comment' => 'Promotion approved.',
        ], $overrides);
    }
}
