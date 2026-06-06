<?php

namespace App\Http\Controllers;

use App\Http\Requests\BulkEmployeeActionRequest;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmploymentStatus;
use App\Models\Project;
use App\Models\TerminationReason;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class EmployeeBulkActionController extends Controller
{
    public function handle(BulkEmployeeActionRequest $request, ActivityLogger $activity): RedirectResponse
    {
        $data = $request->validated();
        $user = $request->user();
        $action = $data['action'];
        $employeeIds = array_values(array_unique(array_map('intval', $data['employee_ids'])));

        $employees = Employee::query()
            ->with(['user', 'province'])
            ->whereIn('id', $employeeIds)
            ->get();

        if ($employees->count() !== count($employeeIds)) {
            return back()->withInput()->withErrors([
                'employee_ids' => 'One or more selected employees could not be found.',
            ]);
        }

        $ability = $action === 'archive' ? 'archive' : 'update';

        foreach ($employees as $employee) {
            if (! Gate::forUser($user)->allows($ability, $employee)) {
                return back()->withInput()->withErrors([
                    'employee_ids' => 'You are not authorized to update one or more selected employees.',
                ]);
            }
        }

        $linkedUserAccountsCount = $employees->filter(fn (Employee $employee) => $employee->user !== null)->count();
        $valueLabel = $this->valueLabel($action, $data);
        $employeeCount = $employees->count();
        $isTerminatedStatus = $action === 'change_employment_status'
            && $this->isTerminatedStatus($data['employment_status_id'] ?? null);

        DB::transaction(function () use ($action, $data, $employees, $user, $isTerminatedStatus): void {
            foreach ($employees as $employee) {
                match ($action) {
                    'change_employment_status' => $employee->update([
                        'employment_status_id' => $data['employment_status_id'],
                        'termination_reason_id' => $isTerminatedStatus ? ($data['termination_reason_id'] ?? null) : null,
                        'termination_date' => $isTerminatedStatus ? ($data['termination_date'] ?? null) : null,
                        'termination_comment' => $isTerminatedStatus ? ($data['termination_comment'] ?? null) : null,
                        'updated_by' => $user->id,
                    ]),
                    'change_project' => $employee->update([
                        'project_id' => $data['project_id'],
                        'updated_by' => $user->id,
                    ]),
                    'change_department' => $employee->update([
                        'department_id' => $data['department_id'],
                        'updated_by' => $user->id,
                    ]),
                    'assign_supervisor' => $employee->update([
                        'supervisor_name' => $data['supervisor_name'],
                        'updated_by' => $user->id,
                    ]),
                    'archive' => $this->archiveEmployee($employee, $user->id),
                };
            }
        });

        $description = $this->description($user->name, $action, $valueLabel, $employeeCount);

        $activity->log(
            'bulk_employee_action_completed',
            $description,
            null,
            [
                'action' => $action,
                'employee_count' => $employeeCount,
                'employee_ids' => $employees->pluck('id')->values()->all(),
            'changed_value' => $valueLabel,
            'termination_reason' => $action === 'change_employment_status' && isset($data['termination_reason_id'])
                ? TerminationReason::find($data['termination_reason_id'])?->name
                : null,
            'termination_date' => $data['termination_date'] ?? null,
            'performed_by' => $user->name,
                'linked_user_accounts_count' => $linkedUserAccountsCount,
                'province_id' => $this->singleProvinceId($employees),
                'province_name' => $this->singleProvinceName($employees),
            ],
            user: $user,
            request: $request,
        );

        return redirect()
            ->route('employees.index', $request->query())
            ->with('success', "Bulk action completed for {$employeeCount} employees.");
    }

    private function archiveEmployee(Employee $employee, int $userId): void
    {
        $employee->update([
            'archived_by' => $userId,
            'updated_by' => $userId,
        ]);
        $employee->delete();
    }

    /**
     * @param array<string, mixed> $data
     */
    private function valueLabel(string $action, array $data): ?string
    {
        return match ($action) {
            'change_employment_status' => $this->employmentStatusLabel($data),
            'change_project' => Project::find($data['project_id'])?->name,
            'change_department' => Department::find($data['department_id'])?->name,
            'assign_supervisor' => $data['supervisor_name'],
            default => null,
        };
    }

    /**
     * @param array<string, mixed> $data
     */
    private function employmentStatusLabel(array $data): ?string
    {
        $status = EmploymentStatus::find($data['employment_status_id']);

        if (! $status) {
            return null;
        }

        if (! $this->isTerminatedStatus($status->id)) {
            return $status->name;
        }

        $reason = isset($data['termination_reason_id'])
            ? TerminationReason::find($data['termination_reason_id'])?->name
            : null;
        $date = $data['termination_date'] ?? null;

        return trim($status->name.($reason ? " - {$reason}" : '').($date ? " effective {$date}" : ''));
    }

    private function isTerminatedStatus(null|int|string $statusId): bool
    {
        if (! $statusId) {
            return false;
        }

        return EmploymentStatus::whereKey($statusId)
            ->where(function ($query) {
                $query->where('code', 'TERMINATED')
                    ->orWhere('name', 'Terminated');
            })
            ->exists();
    }

    private function description(string $actor, string $action, ?string $valueLabel, int $employeeCount): string
    {
        return match ($action) {
            'change_employment_status' => "{$actor} changed employment status to {$valueLabel} for {$employeeCount} employees.",
            'change_project' => "{$actor} changed project to {$valueLabel} for {$employeeCount} employees.",
            'change_department' => "{$actor} changed department to {$valueLabel} for {$employeeCount} employees.",
            'assign_supervisor' => "{$actor} assigned line manager {$valueLabel} to {$employeeCount} employees.",
            'archive' => "{$actor} archived {$employeeCount} employees.",
            default => "{$actor} completed a bulk employee action for {$employeeCount} employees.",
        };
    }

    /**
     * @param Collection<int, Employee> $employees
     */
    private function singleProvinceId(Collection $employees): ?int
    {
        $provinceIds = $employees->pluck('province_id')->filter()->unique();

        return $provinceIds->count() === 1 ? (int) $provinceIds->first() : null;
    }

    /**
     * @param Collection<int, Employee> $employees
     */
    private function singleProvinceName(Collection $employees): ?string
    {
        $provinceNames = $employees->pluck('province.name')->filter()->unique();

        return $provinceNames->count() === 1 ? $provinceNames->first() : null;
    }
}
