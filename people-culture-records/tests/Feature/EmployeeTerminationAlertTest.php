<?php

namespace Tests\Feature;

use App\Jobs\SendEmployeeTerminationItAlert;
use App\Mail\EmployeeTerminationItAlertMail;
use App\Models\District;
use App\Models\Employee;
use App\Models\EmployeeTerminationAlert;
use App\Models\EmploymentStatus;
use App\Models\JobTitle;
use App\Models\Province;
use App\Services\ActivityLogger;
use App\Services\TerminationAlertRecipientResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use RuntimeException;
use Tests\TestCase;

class EmployeeTerminationAlertTest extends TestCase
{
    use RefreshDatabase;

    private EmploymentStatus $activeStatus;

    private EmploymentStatus $terminatedStatus;

    private JobTitle $securityOfficerTitle;

    private Province $province;

    private District $district;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('offboarding.termination_alerts.enabled', true);
        config()->set('offboarding.termination_alerts.job_titles', ['IT Security Officer']);

        $this->activeStatus = EmploymentStatus::query()->firstOrCreate(
            ['code' => 'ACTIVE'],
            ['name' => 'Active', 'is_active' => true],
        );
        $this->terminatedStatus = EmploymentStatus::query()->firstOrCreate(
            ['code' => 'TERMINATED'],
            ['name' => 'Terminated', 'is_active' => true],
        );
        $this->securityOfficerTitle = JobTitle::query()->firstOrCreate(
            ['code' => 'IT_SECURITY_OFFICER'],
            ['name' => 'IT Security Officer', 'is_active' => true],
        );
        $this->province = Province::query()->firstOrCreate(
            ['code' => 'TEST'],
            ['name' => 'Test Province', 'is_active' => true],
        );
        $this->district = District::query()->firstOrCreate(
            ['code' => 'TEST-DISTRICT'],
            ['province_id' => $this->province->id, 'name' => 'Test District', 'is_active' => true],
        );
    }

    public function test_transition_to_terminated_dispatches_one_alert_job(): void
    {
        Queue::fake();

        $employee = $this->employee();

        $employee->update([
            'employment_status_id' => $this->terminatedStatus->id,
            'termination_date' => '2026-10-31',
        ]);

        Queue::assertPushed(SendEmployeeTerminationItAlert::class, function (SendEmployeeTerminationItAlert $job) use ($employee): bool {
            return $job->employeeId === $employee->id
                && $job->previousEmploymentStatusId === $this->activeStatus->id;
        });

        $employee->update(['phone' => '0977000000']);

        Queue::assertPushed(SendEmployeeTerminationItAlert::class, 1);
    }

    public function test_alert_is_sent_only_to_active_occupants_of_configured_job_titles_and_is_idempotent(): void
    {
        Mail::fake();

        $recipient = $this->employee([
            'employee_no' => 'IT-001',
            'first_name' => 'Security',
            'last_name' => 'Officer',
            'email' => 'security.officer@example.org',
            'job_title_id' => $this->securityOfficerTitle->id,
        ]);
        $otherTitle = JobTitle::create([
            'name' => 'IT Help Desk Team Lead',
            'code' => 'IT_HELP_DESK_LEAD',
            'is_active' => true,
        ]);
        $this->employee([
            'employee_no' => 'IT-002',
            'first_name' => 'Helpdesk',
            'last_name' => 'Lead',
            'email' => 'helpdesk@example.org',
            'job_title_id' => $otherTitle->id,
        ]);
        $this->employee([
            'employee_no' => 'IT-003',
            'first_name' => 'Former',
            'last_name' => 'Security',
            'email' => 'former.security@example.org',
            'job_title_id' => $this->securityOfficerTitle->id,
            'employment_status_id' => $this->terminatedStatus->id,
        ]);

        $employee = $this->employee([
            'employee_no' => 'EMP-100',
            'first_name' => 'Test',
            'last_name' => 'Leaver',
            'employment_status_id' => $this->terminatedStatus->id,
            'termination_date' => '2026-10-31',
            'termination_comment' => 'Private termination commentary.',
        ]);
        $eventUuid = Str::uuid()->toString();
        $job = new SendEmployeeTerminationItAlert($employee->id, $eventUuid, $this->activeStatus->id);

        $this->handle($job);
        $this->handle($job);

        Mail::assertSent(EmployeeTerminationItAlertMail::class, 1);
        Mail::assertSent(EmployeeTerminationItAlertMail::class, function (EmployeeTerminationItAlertMail $mail) use ($recipient): bool {
            return $mail->hasTo($recipient->email)
                && ! str_contains($mail->render(), 'Private termination commentary.');
        });
        Mail::assertNotSent(EmployeeTerminationItAlertMail::class, fn (EmployeeTerminationItAlertMail $mail) => $mail->hasTo('helpdesk@example.org'));
        Mail::assertNotSent(EmployeeTerminationItAlertMail::class, fn (EmployeeTerminationItAlertMail $mail) => $mail->hasTo('former.security@example.org'));

        $this->assertDatabaseCount('employee_termination_alerts', 1);
        $this->assertDatabaseHas('employee_termination_alerts', [
            'termination_event_uuid' => $eventUuid,
            'employee_id' => $employee->id,
            'recipient_employee_id' => $recipient->id,
            'recipient_email' => 'security.officer@example.org',
            'status' => EmployeeTerminationAlert::STATUS_SENT,
            'attempts' => 1,
        ]);
        $this->assertDatabaseHas('activity_logs', [
            'action' => 'employee_termination_it_alert_sent',
            'subject_type' => Employee::class,
            'subject_id' => $employee->id,
        ]);
    }

    public function test_missing_configured_recipient_is_logged_without_sending_mail(): void
    {
        Mail::fake();

        $employee = $this->employee([
            'employment_status_id' => $this->terminatedStatus->id,
            'termination_date' => '2026-10-31',
        ]);

        $this->handle(new SendEmployeeTerminationItAlert(
            $employee->id,
            Str::uuid()->toString(),
            $this->activeStatus->id,
        ));

        Mail::assertNothingSent();
        $this->assertDatabaseCount('employee_termination_alerts', 0);
        $this->assertDatabaseHas('activity_logs', [
            'action' => 'employee_termination_it_alert_recipient_missing',
            'subject_type' => Employee::class,
            'subject_id' => $employee->id,
        ]);
    }

    public function test_reversed_termination_before_queue_processing_sends_nothing(): void
    {
        Mail::fake();

        $employee = $this->employee();

        $this->handle(new SendEmployeeTerminationItAlert(
            $employee->id,
            Str::uuid()->toString(),
            $this->terminatedStatus->id,
        ));

        Mail::assertNothingSent();
        $this->assertDatabaseCount('employee_termination_alerts', 0);
    }

    public function test_mail_failure_is_recorded_before_the_job_is_retried(): void
    {
        $this->employee([
            'employee_no' => 'IT-001',
            'first_name' => 'Security',
            'last_name' => 'Officer',
            'email' => 'security.officer@example.org',
            'job_title_id' => $this->securityOfficerTitle->id,
        ]);
        $employee = $this->employee([
            'employment_status_id' => $this->terminatedStatus->id,
            'termination_date' => '2026-10-31',
        ]);
        $eventUuid = Str::uuid()->toString();

        Mail::shouldReceive('to')
            ->once()
            ->with('security.officer@example.org')
            ->andThrow(new RuntimeException('Controlled SMTP failure.'));

        try {
            $this->handle(new SendEmployeeTerminationItAlert(
                $employee->id,
                $eventUuid,
                $this->activeStatus->id,
            ));

            $this->fail('The job should be released for retry after a mail failure.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('will be retried', $exception->getMessage());
        }

        $this->assertDatabaseHas('employee_termination_alerts', [
            'termination_event_uuid' => $eventUuid,
            'recipient_email' => 'security.officer@example.org',
            'status' => EmployeeTerminationAlert::STATUS_FAILED,
            'attempts' => 1,
        ]);
        $this->assertDatabaseHas('activity_logs', [
            'action' => 'employee_termination_it_alert_failed',
            'subject_type' => Employee::class,
            'subject_id' => $employee->id,
        ]);
    }

    private function handle(SendEmployeeTerminationItAlert $job): void
    {
        $job->handle(
            app(TerminationAlertRecipientResolver::class),
            app(ActivityLogger::class),
        );
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function employee(array $attributes = []): Employee
    {
        return Employee::create(array_merge([
            'employee_no' => 'EMP-001',
            'first_name' => 'Active',
            'last_name' => 'Employee',
            'employment_status_id' => $this->activeStatus->id,
            'province_id' => $this->province->id,
            'district_id' => $this->district->id,
        ], $attributes));
    }
}
