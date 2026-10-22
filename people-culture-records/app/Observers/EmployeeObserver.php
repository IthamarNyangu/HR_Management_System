<?php

namespace App\Observers;

use App\Jobs\SendEmployeeTerminationItAlert;
use App\Jobs\SyncEmployeeToWorkPulse;
use App\Models\Employee;
use App\Models\EmploymentStatus;
use Illuminate\Support\Str;

class EmployeeObserver
{
    public function saved(Employee $employee): void
    {
        if (config('services.workpulse.enabled', false)) {
            SyncEmployeeToWorkPulse::dispatch($employee->id)->afterCommit();
        }
    }

    public function updated(Employee $employee): void
    {
        if (! config('offboarding.termination_alerts.enabled', true)
            || ! $employee->wasChanged('employment_status_id')
            || ! $this->isTerminatedStatus($employee->employment_status_id)) {
            return;
        }

        SendEmployeeTerminationItAlert::dispatch(
            $employee->id,
            Str::uuid()->toString(),
            $employee->getOriginal('employment_status_id'),
        )->afterCommit();
    }

    public function deleted(Employee $employee): void
    {
        if (config('services.workpulse.enabled', false)) {
            SyncEmployeeToWorkPulse::dispatch($employee->id)->afterCommit();
        }
    }

    public function restored(Employee $employee): void
    {
        if (config('services.workpulse.enabled', false)) {
            SyncEmployeeToWorkPulse::dispatch($employee->id)->afterCommit();
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
}
