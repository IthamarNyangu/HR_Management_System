<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\EmployeeOffboardingCase;
use App\Services\ActivityLogger;
use App\Services\ExitClearClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Throwable;

class EmployeeOffboardingController extends Controller
{
    public function store(Request $request, Employee $employee, ExitClearClient $client, ActivityLogger $activity): RedirectResponse
    {
        Gate::authorize('update', $employee);
        $employee->loadMissing('employmentStatus');

        if (! $employee->isTerminated() || ! $employee->termination_date) {
            return back()->with('error', 'Offboarding can begin only after the employee is marked Terminated with a termination date.');
        }

        $offboardingCase = $employee->offboardingCases()->latest('initiated_at')->latest('id')->first();

        if ($offboardingCase?->isLinked() && ! in_array($offboardingCase->status, ['closed', 'cancelled'], true)) {
            return back()->with('success', "ExitClear case {$offboardingCase->case_number} is already linked to this employee.");
        }

        if (! $offboardingCase || $offboardingCase->isLinked()) {
            $offboardingCase = $employee->offboardingCases()->create([
                'integration_request_uuid' => Str::uuid()->toString(),
                'status' => EmployeeOffboardingCase::STATUS_INITIATING,
                'initiated_by_user_id' => $request->user()->id,
                'initiated_at' => now(),
            ]);
        } else {
            $offboardingCase->update([
                'status' => EmployeeOffboardingCase::STATUS_INITIATING,
                'initiated_by_user_id' => $request->user()->id,
                'failure_message' => null,
            ]);
        }

        try {
            $payload = $client->initiate($employee, $request->user(), $offboardingCase);
            $offboardingCase->update([
                'exitclear_case_id' => $payload['id'],
                'case_number' => $payload['case_number'],
                'status' => $payload['status'],
                'initiated_at' => $payload['initiated_at'] ?? $offboardingCase->initiated_at,
                'last_synced_at' => now(),
                'failure_message' => null,
            ]);

            $activity->log(
                $payload['created'] ? 'employee_offboarding_initiated' : 'employee_offboarding_linked',
                $payload['created']
                    ? "{$request->user()->name} initiated ExitClear case {$payload['case_number']} for {$employee->display_name}."
                    : "{$request->user()->name} linked existing ExitClear case {$payload['case_number']} to {$employee->display_name}.",
                $employee,
                ['exitclear_case_id' => $payload['id'], 'case_number' => $payload['case_number'], 'status' => $payload['status']],
                $request->user(),
                $request,
            );

            return back()->with('success', $payload['created']
                ? "ExitClear case {$payload['case_number']} was created as a draft."
                : "Existing ExitClear case {$payload['case_number']} was linked successfully.");
        } catch (Throwable $exception) {
            $message = str($exception->getMessage())->limit(1000)->toString();
            $offboardingCase->update([
                'status' => EmployeeOffboardingCase::STATUS_ERROR,
                'failure_message' => $message,
            ]);

            $activity->log(
                'employee_offboarding_initiation_failed',
                "ExitClear case initiation failed for {$employee->display_name}.",
                $employee,
                ['integration_request_uuid' => $offboardingCase->integration_request_uuid, 'error_type' => $exception::class],
                $request->user(),
                $request,
            );

            return back()->with('error', "ExitClear could not start the offboarding case: {$message}");
        }
    }

    public function refresh(Request $request, Employee $employee, ExitClearClient $client, ActivityLogger $activity): RedirectResponse
    {
        Gate::authorize('update', $employee);

        $offboardingCase = $employee->offboardingCases()
            ->whereNotNull('exitclear_case_id')
            ->latest('initiated_at')
            ->latest('id')
            ->first();

        if (! $offboardingCase) {
            return back()->with('error', 'No linked ExitClear case was found for this employee.');
        }

        try {
            $payload = $client->status($offboardingCase);
            $oldStatus = $offboardingCase->status;
            $offboardingCase->update([
                'case_number' => $payload['case_number'],
                'status' => $payload['status'],
                'last_synced_at' => now(),
                'failure_message' => null,
            ]);

            $activity->log(
                'employee_offboarding_status_refreshed',
                "{$request->user()->name} refreshed ExitClear case {$payload['case_number']} for {$employee->display_name}.",
                $employee,
                ['case_number' => $payload['case_number'], 'old_status' => $oldStatus, 'new_status' => $payload['status']],
                $request->user(),
                $request,
            );

            return back()->with('success', "ExitClear status refreshed: {$this->statusLabel($payload['status'])}.");
        } catch (Throwable $exception) {
            return back()->with('error', 'ExitClear status could not be refreshed. Please try again.');
        }
    }

    private function statusLabel(string $status): string
    {
        return str($status)->replace('_', ' ')->title()->toString();
    }
}
