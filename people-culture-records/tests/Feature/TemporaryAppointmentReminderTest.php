<?php

namespace Tests\Feature;

use App\Mail\TemporaryAppointmentEndingSoonMail;
use App\Models\AppointmentStatus;
use App\Models\AppointmentType;
use App\Models\District;
use App\Models\Employee;
use App\Models\JobTitle;
use App\Models\Province;
use App\Models\Role;
use App\Models\TemporaryAppointment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class TemporaryAppointmentReminderTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_emails_global_hr_users_and_line_manager_once_at_60_days(): void
    {
        Mail::fake();

        $managerRole = Role::create(['name' => 'HR Manager', 'code' => 'HRM', 'is_active' => true]);
        $officerRole = Role::create(['name' => 'HR Officer', 'code' => 'HRO', 'is_active' => true]);
        $adminRole = Role::create(['name' => 'Admin', 'code' => 'ADMIN', 'is_active' => true]);
        $northern = Province::create(['name' => 'Northern', 'code' => 'NOR', 'is_active' => true]);
        $luapula = Province::create(['name' => 'Luapula', 'code' => 'LUA', 'is_active' => true]);
        $kasama = District::create([
            'province_id' => $northern->id,
            'name' => 'Kasama',
            'code' => 'NOR-KAS',
            'is_active' => true,
        ]);

        $manager = User::factory()->create([
            'name' => 'Global HR Manager',
            'email' => 'manager@example.org',
            'role_id' => $managerRole->id,
            'is_active' => true,
        ]);
        $officer = User::factory()->create([
            'name' => 'Luapula HR Officer',
            'email' => 'officer@example.org',
            'role_id' => $officerRole->id,
            'province_id' => $luapula->id,
            'is_active' => true,
        ]);
        User::factory()->create([
            'email' => 'admin@example.org',
            'role_id' => $adminRole->id,
            'is_active' => true,
        ]);

        $lineManager = Employee::create([
            'employee_no' => 'LM-001',
            'first_name' => 'Paul',
            'last_name' => 'Manager',
            'email' => 'line.manager@example.org',
            'province_id' => $northern->id,
            'district_id' => $kasama->id,
        ]);
        $employee = Employee::create([
            'employee_no' => 'EMP-001',
            'first_name' => 'Ithamar',
            'last_name' => 'Nyangu',
            'province_id' => $northern->id,
            'district_id' => $kasama->id,
            'supervisor_employee_id' => $lineManager->id,
        ]);
        $currentTitle = JobTitle::create(['name' => 'Developer', 'code' => 'DEV', 'is_active' => true]);
        $actingTitle = JobTitle::create(['name' => 'Acting Team Lead', 'code' => 'ATL', 'is_active' => true]);
        $active = AppointmentStatus::create(['name' => 'Active', 'code' => 'ACTIVE', 'is_active' => true]);
        $type = AppointmentType::create(['name' => 'Interim / Acting Appointment', 'code' => 'INTERIM_ACTING', 'is_active' => true]);

        $appointment = TemporaryAppointment::create([
            'reference_no' => 'TEMP-2026-0001',
            'employee_id' => $employee->id,
            'province_id' => $northern->id,
            'district_id' => $kasama->id,
            'current_job_title_id' => $currentTitle->id,
            'temporary_job_title_id' => $actingTitle->id,
            'appointment_type_id' => $type->id,
            'appointment_status_id' => $active->id,
            'start_date' => today()->subDay(),
            'end_date' => today()->addDays(60),
        ]);

        $this->artisan('appointments:notify-ending-soon')
            ->expectsOutput('Sent 3 ending-soon email(s) for 1 active temporary appointment reminder(s).')
            ->assertSuccessful();

        Mail::assertSent(TemporaryAppointmentEndingSoonMail::class, 3);
        Mail::assertSent(TemporaryAppointmentEndingSoonMail::class, fn ($mail) => $mail->hasTo('manager@example.org'));
        Mail::assertSent(TemporaryAppointmentEndingSoonMail::class, fn ($mail) => $mail->hasTo('officer@example.org'));
        Mail::assertSent(TemporaryAppointmentEndingSoonMail::class, fn ($mail) => $mail->hasTo('line.manager@example.org'));
        Mail::assertNotSent(TemporaryAppointmentEndingSoonMail::class, fn ($mail) => $mail->hasTo('admin@example.org'));

        $this->assertDatabaseCount('temporary_appointment_reminders', 3);
        $this->assertDatabaseHas('temporary_appointment_reminders', [
            'temporary_appointment_id' => $appointment->id,
            'threshold_days' => 60,
            'recipient_email' => 'officer@example.org',
        ]);
        $this->assertDatabaseCount('notifications', 2);

        $this->artisan('appointments:notify-ending-soon')
            ->expectsOutput('Sent 0 ending-soon email(s) for 0 active temporary appointment reminder(s).')
            ->assertSuccessful();

        Mail::assertSent(TemporaryAppointmentEndingSoonMail::class, 3);
        $this->assertDatabaseCount('temporary_appointment_reminders', 3);

        $this->assertTrue($manager->fresh()->notifications()->exists());
        $this->assertTrue($officer->fresh()->notifications()->exists());
    }

    public function test_command_ignores_non_active_and_non_threshold_appointments(): void
    {
        Mail::fake();

        $managerRole = Role::create(['name' => 'HR Manager', 'code' => 'HRM', 'is_active' => true]);
        User::factory()->create([
            'email' => 'manager@example.org',
            'role_id' => $managerRole->id,
            'is_active' => true,
        ]);
        $province = Province::create(['name' => 'Northern', 'code' => 'NOR', 'is_active' => true]);
        $district = District::create([
            'province_id' => $province->id,
            'name' => 'Kasama',
            'code' => 'NOR-KAS',
            'is_active' => true,
        ]);
        $employee = Employee::create([
            'employee_no' => 'EMP-001',
            'first_name' => 'Test',
            'last_name' => 'Employee',
            'province_id' => $province->id,
            'district_id' => $district->id,
        ]);
        $title = JobTitle::create(['name' => 'Officer', 'code' => 'OFF', 'is_active' => true]);
        $active = AppointmentStatus::create(['name' => 'Active', 'code' => 'ACTIVE', 'is_active' => true]);
        $upcoming = AppointmentStatus::create(['name' => 'Upcoming', 'code' => 'UPCOMING', 'is_active' => true]);
        $type = AppointmentType::create(['name' => 'Interim / Acting Appointment', 'code' => 'INTERIM_ACTING', 'is_active' => true]);

        foreach ([
            ['status' => $upcoming->id, 'days' => 60, 'reference' => 'TEMP-2026-0002'],
            ['status' => $active->id, 'days' => 59, 'reference' => 'TEMP-2026-0003'],
        ] as $item) {
            TemporaryAppointment::create([
                'reference_no' => $item['reference'],
                'employee_id' => $employee->id,
                'province_id' => $province->id,
                'district_id' => $district->id,
                'temporary_job_title_id' => $title->id,
                'appointment_type_id' => $type->id,
                'appointment_status_id' => $item['status'],
                'start_date' => today(),
                'end_date' => today()->addDays($item['days']),
            ]);
        }

        $this->artisan('appointments:notify-ending-soon')
            ->expectsOutput('Sent 0 ending-soon email(s) for 0 active temporary appointment reminder(s).')
            ->assertSuccessful();

        Mail::assertNothingSent();
        $this->assertDatabaseCount('temporary_appointment_reminders', 0);
    }
}
