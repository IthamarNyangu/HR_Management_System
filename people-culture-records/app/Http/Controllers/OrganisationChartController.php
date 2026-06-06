<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Employee;
use App\Models\Facility;
use App\Models\Project;
use App\Models\Province;
use App\Services\ActivityLogger;
use App\Support\EmployeeNumber;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class OrganisationChartController extends Controller
{
    public function __invoke(Request $request): View
    {
        return $this->reportingStructure($request);
    }

    public function placeholder(Request $request): View
    {
        Gate::authorize('viewAny', Employee::class);

        return view('organisation-chart.placeholder');
    }

    public function reportingStructure(Request $request): View
    {
        Gate::authorize('viewAny', Employee::class);

        $hasFilters = collect(['search', 'province_id', 'department_id', 'project_id', 'facility_id'])
            ->contains(fn (string $field) => $request->filled($field));

        $employees = $hasFilters
            ? Employee::query()
                ->visibleTo($request->user())
                ->with(['jobTitle', 'department', 'province', 'district', 'facility', 'supervisor'])
                ->when($request->filled('search'), function ($query) use ($request) {
                    $search = $request->string('search');

                    $query->where(function ($query) use ($search) {
                        $query->where('employee_no', 'like', "%{$search}%")
                            ->orWhere('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%")
                            ->orWhere('supervisor_name', 'like', "%{$search}%")
                            ->orWhereHas('jobTitle', fn ($query) => $query->where('name', 'like', "%{$search}%"))
                            ->orWhereHas('department', fn ($query) => $query->where('name', 'like', "%{$search}%"))
                            ->orWhereHas('province', fn ($query) => $query->where('name', 'like', "%{$search}%"))
                            ->orWhereHas('district', fn ($query) => $query->where('name', 'like', "%{$search}%"))
                            ->orWhereHas('facility', fn ($query) => $query->where('name', 'like', "%{$search}%"));
                    });
                })
                ->when($request->filled('province_id'), fn ($query) => $query->where('province_id', $request->integer('province_id')))
                ->when($request->filled('department_id'), fn ($query) => $query->where('department_id', $request->integer('department_id')))
                ->when($request->filled('project_id'), fn ($query) => $query->where('project_id', $request->integer('project_id')))
                ->when($request->filled('facility_id'), fn ($query) => $query->where('facility_id', $request->integer('facility_id')))
                ->orderBy('last_name')
                ->orderBy('first_name')
                ->get()
            : collect();

        $employeeIds = $employees->pluck('id')->all();
        $childrenBySupervisor = $employees
            ->filter(fn (Employee $employee) => $employee->supervisor_employee_id && in_array($employee->supervisor_employee_id, $employeeIds, true))
            ->groupBy('supervisor_employee_id');

        $rootEmployees = $employees
            ->filter(fn (Employee $employee) => ! $employee->supervisor_employee_id || ! in_array($employee->supervisor_employee_id, $employeeIds, true))
            ->values();
        $page = LengthAwarePaginator::resolveCurrentPage();
        $paginatedRootEmployees = new LengthAwarePaginator(
            $rootEmployees->forPage($page, 4)->values(),
            $rootEmployees->count(),
            4,
            $page,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );

        return view('organisation-chart.index', [
            'pageTitle' => 'Reporting Structure',
            'indexRoute' => 'employees.reporting-structure',
            'linkRoute' => 'employees.reporting-structure.link-line-managers',
            'employees' => $employees,
            'rootEmployees' => $paginatedRootEmployees,
            'childrenBySupervisor' => $childrenBySupervisor,
            'stats' => $this->stats($employees, $rootEmployees),
            'hasFilters' => $hasFilters,
            'provinces' => Province::where('is_active', true)
                ->when(
                    ! $request->user()->isAdmin() && ! $request->user()->isHrManager(),
                    fn ($query) => $query->whereKey($request->user()->province_id)
                )
                ->orderBy('name')
                ->get(),
            'departments' => Department::where('is_active', true)->orderBy('name')->get(),
            'projects' => Project::where('is_active', true)->orderBy('name')->get(),
            'facilities' => Facility::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function linkLineManagers(Request $request, ActivityLogger $activity): RedirectResponse
    {
        $user = $request->user();

        abort_unless($user->isAdmin() || $user->isHrManager(), 403);

        $employees = Employee::query()
            ->whereNull('supervisor_employee_id')
            ->whereNotNull('supervisor_name')
            ->where('supervisor_name', '<>', '')
            ->get();

        $employeeNumbers = $employees
            ->map(fn (Employee $employee) => $this->lineManagerEmployeeNumber($employee->supervisor_name))
            ->filter()
            ->unique()
            ->values();

        $lineManagersByNumber = Employee::query()
            ->whereIn('employee_no', $employeeNumbers)
            ->get()
            ->keyBy('employee_no');

        $linkedCount = 0;
        $skippedCount = 0;

        DB::transaction(function () use ($employees, $lineManagersByNumber, $user, &$linkedCount, &$skippedCount): void {
            foreach ($employees as $employee) {
                $employeeNo = $this->lineManagerEmployeeNumber($employee->supervisor_name);
                $lineManager = $employeeNo ? $lineManagersByNumber->get($employeeNo) : null;

                if (! $lineManager || (int) $lineManager->id === (int) $employee->id) {
                    $skippedCount++;
                    continue;
                }

                $employee->update([
                    'supervisor_employee_id' => $lineManager->id,
                    'supervisor_name' => $lineManager->full_name,
                    'updated_by' => $user->id,
                ]);

                $linkedCount++;
            }
        });

        $activity->log(
            'line_managers_auto_linked',
            "{$user->name} auto-linked {$linkedCount} line manager records by employee number.",
            null,
            [
                'linked_count' => $linkedCount,
                'skipped_count' => $skippedCount,
            ],
            user: $user,
            request: $request,
        );

        return back()->with('success', "Auto-linked {$linkedCount} line manager record(s). {$skippedCount} record(s) remain unlinked.");
    }

    private function lineManagerEmployeeNumber(?string $lineManagerText): ?string
    {
        $lineManagerText = trim((string) $lineManagerText);

        if ($lineManagerText === '') {
            return null;
        }

        if (! preg_match('/^([A-Za-z0-9][A-Za-z0-9_-]*)/u', $lineManagerText, $matches)) {
            return null;
        }

        return EmployeeNumber::normalize($matches[1]);
    }

    /**
     * @param Collection<int, Employee> $employees
     * @param Collection<int, Employee> $rootEmployees
     * @return array<string, int>
     */
    private function stats(Collection $employees, Collection $rootEmployees): array
    {
        return [
            'visible_employees' => $employees->count(),
            'linked_supervisors' => $employees->whereNotNull('supervisor_employee_id')->count(),
            'text_only_supervisors' => $employees
                ->filter(fn (Employee $employee) => blank($employee->supervisor_employee_id) && filled($employee->supervisor_name))
                ->count(),
            'top_level' => $rootEmployees->count(),
        ];
    }
}
