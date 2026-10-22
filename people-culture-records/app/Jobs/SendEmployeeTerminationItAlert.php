<?php

namespace App\Jobs;

use App\Mail\EmployeeTerminationItAlertMail;
use App\Models\Employee;
use App\Models\EmployeeTerminationAlert;
use App\Models\EmploymentStatus;
use App\Services\ActivityLogger;
use App\Services\TerminationAlertRecipientResolver;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;
use RuntimeException;
use Throwable;

class SendEmployeeTerminationItAlert implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public array $backoff = [60, 300, 900];

    public function __construct(
        public int $employeeId,
        public string $terminationEventUuid,
        public ?int $previousEmploymentStatusId = null,
    ) {}

    public function handle(
        TerminationAlertRecipientResolver $recipientResolver,
        ActivityLogger $activity,
    ): void {
        $employee = Employee::query()
            ->with([
                'department',
                'jobTitle',
                'province',
                'district',
                'facility',
                'employmentStatus',
                'terminationReason',
                'supervisor',
                'updatedBy',
            ])
            ->find($this->employeeId);

        if (! $employee || ! $this->isTerminatedStatus($employee->employment_status_id)) {
            return;
        }

        $recipients = $recipientResolver->resolve();

        if ($recipients->isEmpty()) {
            $activity->log(
                'employee_termination_it_alert_recipient_missing',
                "No active employee occupies a configured IT termination-alert job title for {$employee->display_name}.",
                $employee,
                $this->eventProperties($employee),
                user: $employee->updatedBy,
                request: null,
            );

            return;
        }

        $failures = 0;

        foreach ($recipients as $recipient) {
            $email = mb_strtolower(trim((string) $recipient->email));
            $alert = EmployeeTerminationAlert::query()->firstOrCreate(
                [
                    'termination_event_uuid' => $this->terminationEventUuid,
                    'recipient_email' => $email,
                ],
                [
                    'employee_id' => $employee->id,
                    'recipient_employee_id' => $recipient->id,
                    'recipient_name' => $recipient->full_name,
                    'recipient_job_title' => $recipient->jobTitle?->name ?? 'Configured IT recipient',
                    'status' => EmployeeTerminationAlert::STATUS_PENDING,
                ]
            );

            if ($alert->status === EmployeeTerminationAlert::STATUS_SENT) {
                continue;
            }

            $alert->increment('attempts');

            try {
                Mail::to($email)->send(new EmployeeTerminationItAlertMail($employee, $recipient->full_name));

                $alert->update([
                    'status' => EmployeeTerminationAlert::STATUS_SENT,
                    'sent_at' => now(),
                    'failed_at' => null,
                    'error_message' => null,
                ]);

                $activity->log(
                    'employee_termination_it_alert_sent',
                    "System sent an employee termination alert for {$employee->display_name} to {$recipient->jobTitle?->name}.",
                    $employee,
                    $this->eventProperties($employee) + [
                        'recipient_email' => $email,
                        'recipient_job_title' => $recipient->jobTitle?->name,
                    ],
                    user: $employee->updatedBy,
                    request: null,
                );
            } catch (Throwable $exception) {
                $failures++;

                $alert->update([
                    'status' => EmployeeTerminationAlert::STATUS_FAILED,
                    'failed_at' => now(),
                    'error_message' => str($exception->getMessage())->limit(1000)->toString(),
                ]);

                $activity->log(
                    'employee_termination_it_alert_failed',
                    "System could not send an employee termination alert for {$employee->display_name} to {$recipient->jobTitle?->name}.",
                    $employee,
                    $this->eventProperties($employee) + [
                        'recipient_email' => $email,
                        'recipient_job_title' => $recipient->jobTitle?->name,
                        'error_type' => $exception::class,
                    ],
                    user: $employee->updatedBy,
                    request: null,
                );
            }
        }

        if ($failures > 0) {
            throw new RuntimeException("{$failures} employee termination alert email(s) failed and will be retried.");
        }
    }

    private function isTerminatedStatus(?int $statusId): bool
    {
        if (! $statusId) {
            return false;
        }

        return EmploymentStatus::query()
            ->whereKey($statusId)
            ->where(function ($query): void {
                $query->whereRaw('UPPER(code) = ?', ['TERMINATED'])
                    ->orWhereRaw('LOWER(name) = ?', ['terminated']);
            })
            ->exists();
    }

    /**
     * @return array<string, mixed>
     */
    private function eventProperties(Employee $employee): array
    {
        return [
            'termination_event_uuid' => $this->terminationEventUuid,
            'previous_employment_status' => $this->previousEmploymentStatusId
                ? EmploymentStatus::find($this->previousEmploymentStatusId)?->name
                : null,
            'new_employment_status' => $employee->employmentStatus?->name,
            'termination_date' => $employee->termination_date?->toDateString(),
        ];
    }
}
