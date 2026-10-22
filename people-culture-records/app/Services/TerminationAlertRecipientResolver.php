<?php

namespace App\Services;

use App\Models\Employee;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class TerminationAlertRecipientResolver
{
    /**
     * @return Collection<int, Employee>
     */
    public function resolve(): Collection
    {
        $jobTitles = collect(config('offboarding.termination_alerts.job_titles', []))
            ->filter(fn ($title) => is_string($title) && trim($title) !== '')
            ->map(fn (string $title) => mb_strtolower(trim($title)))
            ->unique()
            ->values();

        if ($jobTitles->isEmpty()) {
            return collect();
        }

        return Employee::query()
            ->with('jobTitle:id,name')
            ->whereNotNull('email')
            ->whereHas('employmentStatus', function ($query): void {
                $query->where(function ($query): void {
                    $query->whereRaw('UPPER(code) = ?', ['ACTIVE'])
                        ->orWhereRaw('LOWER(name) = ?', ['active']);
                });
            })
            ->whereHas('jobTitle', function ($query) use ($jobTitles): void {
                $query->whereIn(DB::raw('LOWER(TRIM(name))'), $jobTitles->all());
            })
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get()
            ->filter(fn (Employee $employee) => filter_var(trim((string) $employee->email), FILTER_VALIDATE_EMAIL) !== false)
            ->unique(fn (Employee $employee) => mb_strtolower(trim((string) $employee->email)))
            ->values();
    }
}
