<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\AppointmentStatus;
use App\Models\AppointmentType;
use App\Models\District;
use App\Models\Employee;
use App\Models\JobTitle;
use App\Models\Province;
use App\Models\Role;
use App\Models\TemporaryAppointment;
use App\Models\TemporaryAppointmentExtension;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TemporaryAppointmentModuleTest extends TestCase
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
    private JobTitle $currentTitle;
    private JobTitle $temporaryTitle;
    private AppointmentType $appointmentType;
    private AppointmentStatus $draftStatus;
    private AppointmentStatus $upcomingStatus;
    private AppointmentStatus $activeStatus;
    private AppointmentStatus $completedStatus;
    private AppointmentStatus $cancelledStatus;
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

        $this->currentTitle = JobTitle::create(['name' => 'HR Assistant', 'code' => 'HRA', 'is_active' => true]);
        $this->temporaryTitle = JobTitle::create(['name' => 'Acting HR Officer', 'code' => 'AHRO', 'is_active' => true]);
        $this->appointmentType = AppointmentType::create(['name' => 'Acting Appointment', 'code' => 'ACTING', 'is_active' => true]);

        $this->draftStatus = AppointmentStatus::create(['name' => 'Draft', 'code' => 'DRAFT', 'is_active' => true]);
        $this->upcomingStatus = AppointmentStatus::create(['name' => 'Upcoming', 'code' => 'UPCOMING', 'is_active' => true]);
        $this->activeStatus = AppointmentStatus::create(['name' => 'Active', 'code' => 'ACTIVE', 'is_active' => true]);
        $this->completedStatus = AppointmentStatus::create(['name' => 'Completed', 'code' => 'COMPLETED', 'is_active' => true]);
        $this->cancelledStatus = AppointmentStatus::create(['name' => 'Cancelled', 'code' => 'CANCELLED', 'is_active' => true]);

        $this->northernEmployee = $this->employee([
            'employee_no' => 'NOR-001',
            'province_id' => $this->northern->id,
            'district_id' => $this->kasama->id,
            'job_title_id' => $this->currentTitle->id,
        ]);

        $this->luapulaEmployee = $this->employee([
            'employee_no' => 'LUA-001',
            'province_id' => $this->luapula->id,
            'district_id' => $this->mansa->id,
            'job_title_id' => $this->currentTitle->id,
        ]);
    }

    public function test_unauthenticated_users_cannot_access_module(): void
    {
        $this->get('/temporary-appointments')->assertRedirect('/login');
    }

    public function test_employee_search_requires_authentication(): void
    {
        $this->getJson(route('employees.search', ['q' => 'NOR']))
            ->assertUnauthorized();
    }

    public function test_employee_search_returns_matching_employee_by_name(): void
    {
        $employee = $this->employee([
            'employee_no' => 'RTC001',
            'first_name' => 'Mary',
            'last_name' => 'Banda',
            'email' => 'mary.banda@example.org',
            'job_title_id' => $this->currentTitle->id,
        ]);

        $this->actingAs($this->user($this->adminRole))
            ->getJson(route('employees.search', ['q' => 'Mary']))
            ->assertOk()
            ->assertJsonFragment([
                'id' => $employee->id,
                'text' => 'RTC001 - Mary Banda',
                'job_title' => 'HR Assistant',
                'province' => 'Northern',
                'district' => 'Kasama',
            ]);
    }

    public function test_employee_search_returns_matching_employee_by_employee_number(): void
    {
        $this->actingAs($this->user($this->adminRole))
            ->getJson(route('employees.search', ['q' => 'LUA-001']))
            ->assertOk()
            ->assertJsonFragment([
                'id' => $this->luapulaEmployee->id,
                'text' => $this->luapulaEmployee->display_name,
            ]);
    }

    public function test_employee_search_respects_hr_officer_province_restriction(): void
    {
        $this->actingAs($this->user($this->officerRole, $this->northern))
            ->getJson(route('employees.search', ['q' => 'LUA-001']))
            ->assertOk()
            ->assertExactJson([]);
    }

    public function test_admin_can_manage_all_appointments(): void
    {
        $appointment = $this->appointment([
            'employee_id' => $this->luapulaEmployee->id,
            'province_id' => $this->luapula->id,
            'district_id' => $this->mansa->id,
        ]);

        $this->actingAs($this->user($this->adminRole))
            ->get(route('temporary-appointments.show', $appointment))
            ->assertOk()
            ->assertSee($appointment->reference_no);
    }

    public function test_hr_manager_can_manage_all_appointments(): void
    {
        $manager = $this->user($this->managerRole);

        $this->actingAs($manager)
            ->post(route('temporary-appointments.store'), $this->appointmentPayload([
                'employee_id' => $this->luapulaEmployee->id,
                'province_id' => $this->luapula->id,
                'district_id' => $this->mansa->id,
            ]))
            ->assertRedirect();

        $this->assertDatabaseHas('temporary_appointments', [
            'employee_id' => $this->luapulaEmployee->id,
            'province_id' => $this->luapula->id,
        ]);
    }

    public function test_hr_officer_can_manage_only_assigned_province_appointments(): void
    {
        $officer = $this->user($this->officerRole, $this->northern);
        $outsideAppointment = $this->appointment([
            'employee_id' => $this->luapulaEmployee->id,
            'province_id' => $this->luapula->id,
            'district_id' => $this->mansa->id,
        ]);

        $this->actingAs($officer)
            ->post(route('temporary-appointments.store'), $this->appointmentPayload())
            ->assertRedirect();

        $this->actingAs($officer)
            ->get(route('temporary-appointments.show', $outsideAppointment))
            ->assertForbidden();

        $this->actingAs($officer)
            ->from(route('temporary-appointments.create'))
            ->post(route('temporary-appointments.store'), $this->appointmentPayload([
                'employee_id' => $this->luapulaEmployee->id,
                'province_id' => $this->luapula->id,
                'district_id' => $this->mansa->id,
            ]))
            ->assertRedirect(route('temporary-appointments.create'))
            ->assertSessionHasErrors('province_id');
    }

    public function test_viewer_can_view_but_cannot_create_edit_archive_or_extend(): void
    {
        $viewer = $this->user($this->viewerRole, $this->northern);
        $appointment = $this->appointment();

        $this->actingAs($viewer)->get(route('temporary-appointments.show', $appointment))->assertOk();
        $this->actingAs($viewer)->get(route('temporary-appointments.create'))->assertForbidden();
        $this->actingAs($viewer)->get(route('temporary-appointments.edit', $appointment))->assertForbidden();
        $this->actingAs($viewer)->patch(route('temporary-appointments.archive', $appointment))->assertForbidden();
        $this->actingAs($viewer)->patch(route('temporary-appointments.extend', $appointment), [
            'new_end_date' => now()->addMonths(2)->toDateString(),
        ])->assertForbidden();
    }

    public function test_reference_number_is_generated(): void
    {
        $this->actingAs($this->user($this->adminRole))
            ->post(route('temporary-appointments.store'), $this->appointmentPayload())
            ->assertRedirect();

        $appointment = TemporaryAppointment::firstOrFail();

        $this->assertSame('TEMP-'.now()->year.'-0001', $appointment->reference_no);
    }

    public function test_temporary_appointment_can_save_selected_supervisor_employee(): void
    {
        $supervisor = $this->employee([
            'employee_no' => 'SUP-001',
            'first_name' => 'Grace',
            'last_name' => 'Mwansa',
        ]);

        $this->actingAs($this->user($this->adminRole))
            ->post(route('temporary-appointments.store'), $this->appointmentPayload([
                'supervisor_employee_id' => $supervisor->id,
                'supervisor_name' => '',
            ]))
            ->assertRedirect();

        $this->assertDatabaseHas('temporary_appointments', [
            'supervisor_employee_id' => $supervisor->id,
            'supervisor_name' => 'Grace Mwansa',
        ]);
    }

    public function test_edit_page_preloads_selected_employee_and_supervisor(): void
    {
        $supervisor = $this->employee([
            'employee_no' => 'SUP-002',
            'first_name' => 'Joseph',
            'last_name' => 'Phiri',
        ]);
        $appointment = $this->appointment([
            'supervisor_employee_id' => $supervisor->id,
            'supervisor_name' => $supervisor->full_name,
        ]);

        $this->actingAs($this->user($this->adminRole))
            ->get(route('temporary-appointments.edit', $appointment))
            ->assertOk()
            ->assertSee($this->northernEmployee->display_name)
            ->assertSee($supervisor->display_name);
    }

    public function test_end_date_cannot_be_before_start_date(): void
    {
        $this->actingAs($this->user($this->adminRole))
            ->from(route('temporary-appointments.create'))
            ->post(route('temporary-appointments.store'), $this->appointmentPayload([
                'start_date' => now()->toDateString(),
                'end_date' => now()->subDay()->toDateString(),
            ]))
            ->assertRedirect(route('temporary-appointments.create'))
            ->assertSessionHasErrors('end_date');
    }

    public function test_active_appointment_shows_days_remaining_and_ending_soon_filter_works(): void
    {
        $appointment = $this->appointment([
            'end_date' => now()->addDays(7)->toDateString(),
        ]);

        $this->assertSame(7, $appointment->fresh()->days_remaining);

        $this->actingAs($this->user($this->adminRole))
            ->get(route('temporary-appointments.index', ['ending_soon' => 1]))
            ->assertOk()
            ->assertSee($appointment->reference_no);
    }

    public function test_appointment_can_be_extended_and_history_is_shown(): void
    {
        $admin = $this->user($this->adminRole);
        $appointment = $this->appointment([
            'end_date' => now()->addDays(10)->toDateString(),
        ]);
        $newEndDate = now()->addDays(40)->toDateString();

        $this->actingAs($admin)
            ->patch(route('temporary-appointments.extend', $appointment), [
                'new_end_date' => $newEndDate,
                'extension_reason' => 'Project continuation',
                'extension_comment' => 'Approved by HR.',
            ])
            ->assertRedirect(route('temporary-appointments.show', $appointment));

        $this->assertDatabaseHas('temporary_appointment_extensions', [
            'temporary_appointment_id' => $appointment->id,
            'reason' => 'Project continuation',
            'extended_by' => $admin->id,
        ]);

        $extension = TemporaryAppointmentExtension::firstOrFail();
        $this->assertSame(now()->addDays(10)->toDateString(), $extension->previous_end_date->toDateString());
        $this->assertSame($newEndDate, $extension->new_end_date->toDateString());

        $this->assertSame($newEndDate, $appointment->fresh()->end_date->toDateString());

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'temporary_appointment_extended',
        ]);

        $this->actingAs($admin)
            ->get(route('temporary-appointments.show', $appointment))
            ->assertOk()
            ->assertSee('Extension History')
            ->assertSee('Project continuation');
    }

    public function test_extension_new_end_date_must_be_after_current_end_date(): void
    {
        $appointment = $this->appointment([
            'end_date' => now()->addDays(10)->toDateString(),
        ]);

        $this->actingAs($this->user($this->adminRole))
            ->from(route('temporary-appointments.show', $appointment))
            ->patch(route('temporary-appointments.extend', $appointment), [
                'new_end_date' => now()->addDays(5)->toDateString(),
            ])
            ->assertRedirect(route('temporary-appointments.show', $appointment))
            ->assertSessionHasErrors('new_end_date');
    }

    public function test_hr_officer_cannot_extend_outside_assigned_province(): void
    {
        $officer = $this->user($this->officerRole, $this->northern);
        $outsideAppointment = $this->appointment([
            'employee_id' => $this->luapulaEmployee->id,
            'province_id' => $this->luapula->id,
            'district_id' => $this->mansa->id,
        ]);

        $this->actingAs($officer)
            ->patch(route('temporary-appointments.extend', $outsideAppointment), [
                'new_end_date' => now()->addMonths(2)->toDateString(),
            ])
            ->assertForbidden();
    }

    public function test_auto_complete_completes_expired_active_appointments_but_not_cancelled(): void
    {
        $active = $this->appointment([
            'reference_no' => 'TEMP-2026-0101',
            'appointment_status_id' => $this->activeStatus->id,
            'end_date' => now()->subDay()->toDateString(),
        ]);
        $cancelled = $this->appointment([
            'reference_no' => 'TEMP-2026-0102',
            'appointment_status_id' => $this->cancelledStatus->id,
            'end_date' => now()->subDay()->toDateString(),
        ]);

        $this->artisan('appointments:auto-complete')
            ->expectsOutput('Completed 1 expired temporary appointment(s).')
            ->assertExitCode(0);

        $this->assertDatabaseHas('temporary_appointments', [
            'id' => $active->id,
            'appointment_status_id' => $this->completedStatus->id,
        ]);
        $this->assertNotNull($active->fresh()->completed_at);
        $this->assertDatabaseHas('temporary_appointments', [
            'id' => $cancelled->id,
            'appointment_status_id' => $this->cancelledStatus->id,
        ]);
    }

    public function test_completed_appointment_extension_is_admin_or_manager_only_and_reactivates_status(): void
    {
        $completed = $this->appointment([
            'appointment_status_id' => $this->completedStatus->id,
            'completed_at' => now()->subDay(),
            'end_date' => now()->subDay()->toDateString(),
        ]);

        $this->actingAs($this->user($this->officerRole, $this->northern))
            ->patch(route('temporary-appointments.extend', $completed), [
                'new_end_date' => now()->addMonth()->toDateString(),
            ])
            ->assertForbidden();

        $this->actingAs($this->user($this->managerRole))
            ->patch(route('temporary-appointments.extend', $completed), [
                'new_end_date' => now()->addMonth()->toDateString(),
            ])
            ->assertRedirect(route('temporary-appointments.show', $completed));

        $this->assertDatabaseHas('temporary_appointments', [
            'id' => $completed->id,
            'appointment_status_id' => $this->activeStatus->id,
            'completed_at' => null,
        ]);
    }

    public function test_archive_and_restore_work(): void
    {
        $admin = $this->user($this->adminRole);
        $appointment = $this->appointment();

        $this->actingAs($admin)
            ->patch(route('temporary-appointments.archive', $appointment))
            ->assertRedirect(route('temporary-appointments.index'));

        $this->assertSoftDeleted('temporary_appointments', ['id' => $appointment->id]);

        $this->actingAs($admin)
            ->patch(route('temporary-appointments.restore', $appointment->id))
            ->assertRedirect(route('temporary-appointments.show', $appointment->id));

        $this->assertDatabaseHas('temporary_appointments', [
            'id' => $appointment->id,
            'deleted_at' => null,
            'archived_by' => null,
        ]);
    }

    public function test_employee_profile_shows_temporary_appointment_history(): void
    {
        $appointment = $this->appointment();

        $this->actingAs($this->user($this->adminRole))
            ->get(route('employees.show', $this->northernEmployee))
            ->assertOk()
            ->assertSee('Temporary Appointment History')
            ->assertSee($appointment->reference_no)
            ->assertSee('Active Temporary Appointment');
    }

    public function test_dashboard_counts_respect_province_visibility(): void
    {
        $this->appointment([
            'reference_no' => 'TEMP-2026-0201',
            'end_date' => now()->addDays(10)->toDateString(),
        ]);
        $this->appointment([
            'reference_no' => 'TEMP-2026-0202',
            'employee_id' => $this->luapulaEmployee->id,
            'province_id' => $this->luapula->id,
            'district_id' => $this->mansa->id,
            'end_date' => now()->addDays(10)->toDateString(),
        ]);

        $this->actingAs($this->user($this->officerRole, $this->northern))
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Active Temporary Appointments')
            ->assertSee('TEMP-2026-0201')
            ->assertDontSee('TEMP-2026-0202');
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
    private function appointment(array $attributes = []): TemporaryAppointment
    {
        return TemporaryAppointment::create(array_merge([
            'reference_no' => fake()->unique()->bothify('TEMP-2026-####'),
            'employee_id' => $this->northernEmployee->id,
            'province_id' => $this->northern->id,
            'district_id' => $this->kasama->id,
            'current_job_title_id' => $this->currentTitle->id,
            'temporary_job_title_id' => $this->temporaryTitle->id,
            'appointment_type_id' => $this->appointmentType->id,
            'appointment_status_id' => $this->activeStatus->id,
            'start_date' => now()->subDay()->toDateString(),
            'end_date' => now()->addDays(30)->toDateString(),
            'reason' => 'Acting role coverage.',
        ], $attributes));
    }

    /**
     * @param array<string, mixed> $overrides
     * @return array<string, mixed>
     */
    private function appointmentPayload(array $overrides = []): array
    {
        return array_merge([
            'employee_id' => $this->northernEmployee->id,
            'province_id' => $this->northern->id,
            'district_id' => $this->kasama->id,
            'current_job_title_id' => $this->currentTitle->id,
            'temporary_job_title_id' => $this->temporaryTitle->id,
            'appointment_type_id' => $this->appointmentType->id,
            'appointment_status_id' => $this->activeStatus->id,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addMonth()->toDateString(),
            'reason' => 'Temporary coverage.',
            'comment' => 'Approved.',
        ], $overrides);
    }
}
