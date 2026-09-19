<?php

namespace App\Observers;

use App\Jobs\SyncEmployeeToWorkPulse;
use App\Models\Employee;

class EmployeeObserver
{
    public function saved(Employee $employee): void
    {
        SyncEmployeeToWorkPulse::dispatch($employee->id)->afterCommit();
    }

    public function deleted(Employee $employee): void
    {
        SyncEmployeeToWorkPulse::dispatch($employee->id)->afterCommit();
    }

    public function restored(Employee $employee): void
    {
        SyncEmployeeToWorkPulse::dispatch($employee->id)->afterCommit();
    }
}
