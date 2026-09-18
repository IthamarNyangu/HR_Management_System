<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WorkPulseDirectoryController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'updated_since' => ['nullable', 'date'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:200'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        $employees = Employee::query()
            ->withTrashed()
            ->with([
                'department:id,name,code,is_active',
                'jobTitle:id,name,code,is_active',
                'facility:id,district_id,name,code,is_active',
                'employmentStatus:id,name,code,is_active',
                'supervisor:id,employee_no,first_name,last_name,email',
            ])
            ->when($validated['updated_since'] ?? null, fn ($query, $since) => $query->where('updated_at', '>=', $since))
            ->orderBy('id')
            ->paginate((int) ($validated['per_page'] ?? 100));

        return response()->json([
            'data' => $employees->getCollection()->map(fn (Employee $employee) => [
                'source_id' => $employee->id,
                'employee_no' => $employee->employee_no,
                'first_name' => $employee->first_name,
                'last_name' => $employee->last_name,
                'full_name' => $employee->full_name,
                'work_email' => $employee->email,
                'hire_date' => $employee->hire_date?->toDateString(),
                'termination_date' => $employee->termination_date?->toDateString(),
                'is_archived' => $employee->trashed(),
                'department' => $this->masterData($employee->department),
                'job_title' => $this->masterData($employee->jobTitle),
                'facility' => $this->masterData($employee->facility),
                'employment_status' => $this->masterData($employee->employmentStatus),
                'supervisor' => $employee->supervisor ? [
                    'source_id' => $employee->supervisor->id,
                    'employee_no' => $employee->supervisor->employee_no,
                    'full_name' => $employee->supervisor->full_name,
                    'work_email' => $employee->supervisor->email,
                ] : null,
                'updated_at' => $employee->updated_at?->toIso8601String(),
                'deleted_at' => $employee->deleted_at?->toIso8601String(),
            ])->values(),
            'meta' => [
                'current_page' => $employees->currentPage(),
                'last_page' => $employees->lastPage(),
                'per_page' => $employees->perPage(),
                'total' => $employees->total(),
                'generated_at' => now()->toIso8601String(),
            ],
        ]);
    }

    private function masterData($record): ?array
    {
        return $record ? [
            'source_id' => $record->id,
            'code' => $record->code,
            'name' => $record->name,
            'is_active' => (bool) $record->is_active,
        ] : null;
    }
}
