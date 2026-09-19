<?php

namespace App\Jobs;

use App\Models\Employee;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class SyncEmployeeToWorkPulse implements ShouldQueue
{
    use Queueable;

    public int $tries = 5;

    public array $backoff = [30, 120, 600, 1800];

    public function __construct(public int $employeeId) {}

    public function handle(): void
    {
        $employee = Employee::withTrashed()->with(['department', 'jobTitle', 'facility', 'employmentStatus', 'supervisor'])->find($this->employeeId);
        if (! $employee) {
            return;
        }
        $baseUrl = rtrim((string) config('services.workpulse.base_url'), '/');
        $token = (string) config('services.workpulse.sync_token');
        if ($baseUrl === '' || $token === '') {
            throw new RuntimeException('WorkPulse integration is not configured.');
        }

        $response = Http::acceptJson()->withToken($token)->timeout(20)->post("{$baseUrl}/api/integrations/hr/sync/employee", [
            'source_id' => $employee->id, 'employee_no' => $employee->employee_no, 'first_name' => $employee->first_name,
            'last_name' => $employee->last_name, 'full_name' => $employee->full_name, 'work_email' => $employee->email,
            'hire_date' => $employee->hire_date?->toDateString(), 'termination_date' => $employee->termination_date?->toDateString(),
            'is_archived' => $employee->trashed(), 'department' => $this->reference($employee->department),
            'job_title' => $this->reference($employee->jobTitle), 'facility' => $this->reference($employee->facility),
            'employment_status' => $this->reference($employee->employmentStatus),
            'supervisor' => $employee->supervisor ? ['source_id' => $employee->supervisor->id, 'employee_no' => $employee->supervisor->employee_no] : null,
            'updated_at' => $employee->updated_at?->toIso8601String(),
        ]);
        if (! $response->successful()) {
            throw new RuntimeException('WorkPulse rejected employee sync: '.$response->status().' '.$response->body());
        }
    }

    private function reference($record): ?array
    {
        return $record ? ['source_id' => $record->id, 'code' => $record->code, 'name' => $record->name, 'is_active' => (bool) $record->is_active] : null;
    }
}
