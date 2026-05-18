<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class EmployeeSearchController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', Employee::class);

        $term = trim((string) $request->query('q', ''));
        $limit = min(max((int) $request->query('limit', 10), 1), 25);

        if (mb_strlen($term) < 1) {
            return response()->json([]);
        }

        $employees = Employee::query()
            ->visibleTo($request->user())
            ->with(['jobTitle', 'province', 'district', 'facility', 'project', 'department'])
            ->when($request->filled('province_id'), fn ($query) => $query->where('province_id', $request->integer('province_id')))
            ->where(function ($query) use ($term) {
                $query->where('employee_no', 'like', "%{$term}%")
                    ->orWhere('first_name', 'like', "%{$term}%")
                    ->orWhere('last_name', 'like', "%{$term}%")
                    ->orWhere('email', 'like', "%{$term}%")
                    ->orWhereHas('jobTitle', fn ($query) => $query->where('name', 'like', "%{$term}%")->orWhere('code', 'like', "%{$term}%"))
                    ->orWhereHas('province', fn ($query) => $query->where('name', 'like', "%{$term}%")->orWhere('code', 'like', "%{$term}%"))
                    ->orWhereHas('district', fn ($query) => $query->where('name', 'like', "%{$term}%")->orWhere('code', 'like', "%{$term}%"))
                    ->orWhereHas('facility', fn ($query) => $query->where('name', 'like', "%{$term}%")->orWhere('code', 'like', "%{$term}%"));
            })
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->limit($limit)
            ->get()
            ->map(fn (Employee $employee) => $this->formatEmployee($employee));

        return response()->json($employees);
    }

    /**
     * @return array<string, mixed>
     */
    private function formatEmployee(Employee $employee): array
    {
        return [
            'id' => $employee->id,
            'text' => $employee->display_name,
            'details' => collect([
                $employee->jobTitle?->name,
                $employee->province?->name,
                $employee->district?->name,
                $employee->facility?->name,
            ])->filter()->implode(' | '),
            'employee_no' => $employee->employee_no,
            'name' => $employee->full_name,
            'email' => $employee->email,
            'job_title' => $employee->jobTitle?->name,
            'province' => $employee->province?->name,
            'district' => $employee->district?->name,
            'facility' => $employee->facility?->name,
            'province_id' => $employee->province_id,
            'district_id' => $employee->district_id,
            'facility_id' => $employee->facility_id,
            'project_id' => $employee->project_id,
            'department_id' => $employee->department_id,
            'job_title_id' => $employee->job_title_id,
        ];
    }
}
