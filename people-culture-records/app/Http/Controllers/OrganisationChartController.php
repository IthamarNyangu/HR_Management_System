<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Http\Requests\StoreOrganisationChartRequest;
use App\Http\Requests\UpdateOrganisationChartRequest;
use App\Models\District;
use App\Models\Employee;
use App\Models\Facility;
use App\Models\JobTitle;
use App\Models\OrganisationChart;
use App\Models\OrganisationChartNode;
use App\Models\Project;
use App\Models\Province;
use App\Services\ActivityLogger;
use App\Support\EmployeeNumber;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class OrganisationChartController extends Controller
{
    public function __invoke(Request $request): View
    {
        return $this->reportingStructure($request);
    }

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', OrganisationChart::class);

        $charts = OrganisationChart::query()
            ->visibleTo($request->user())
            ->with(['project', 'createdBy'])
            ->withCount('nodes')
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search');

                $query->where(function ($query) use ($search) {
                    $query->where('title', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhereHas('project', fn ($query) => $query->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->when($request->filled('project_id'), fn ($query) => $query->where('project_id', $request->integer('project_id')))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('organisation-chart.charts-index', $this->chartFormData($request) + compact('charts'));
    }

    public function create(Request $request): View
    {
        Gate::authorize('create', OrganisationChart::class);

        $organisationChart = new OrganisationChart([
            'status' => OrganisationChart::STATUS_DRAFT,
            'effective_date' => now()->toDateString(),
        ]);

        return view('organisation-chart.create', $this->chartFormData($request) + compact('organisationChart'));
    }

    public function store(StoreOrganisationChartRequest $request, ActivityLogger $activity): RedirectResponse
    {
        $data = $this->chartData($request->validated());
        $data['created_by'] = $request->user()->id;
        $data['updated_by'] = $request->user()->id;

        $organisationChart = OrganisationChart::create($data);

        $activity->log(
            'organisation_chart_created',
            "{$request->user()->name} created organisation chart {$organisationChart->title}.",
            $organisationChart,
            user: $request->user(),
            request: $request,
        );

        return redirect()
            ->route('organisation-chart.edit', $organisationChart)
            ->with('success', 'Organisation chart created. Add chart boxes to build the structure.');
    }

    public function show(OrganisationChart $organisationChart): View
    {
        Gate::authorize('view', $organisationChart);

        $organisationChart->load(['project', 'nodes' => fn ($query) => $query->with($this->nodeRelations())]);

        $nodes = $organisationChart->nodes;
        $childrenByParent = $nodes->whereNotNull('parent_id')->groupBy('parent_id');
        $rootNodes = $nodes->whereNull('parent_id')->values();

        return view('organisation-chart.show', [
            'organisationChart' => $organisationChart,
            'rootNodes' => $rootNodes,
            'childrenByParent' => $childrenByParent,
            'nodeTypes' => OrganisationChartNode::TYPES,
        ]);
    }

    public function edit(Request $request, OrganisationChart $organisationChart): View
    {
        Gate::authorize('update', $organisationChart);

        $organisationChart->load(['project', 'nodes' => fn ($query) => $query->with($this->nodeRelations())]);

        return view('organisation-chart.edit', $this->chartFormData($request) + compact('organisationChart'));
    }

    public function update(UpdateOrganisationChartRequest $request, OrganisationChart $organisationChart, ActivityLogger $activity): RedirectResponse
    {
        DB::transaction(function () use ($request, $organisationChart): void {
            $data = $this->chartData($request->validated());
            $data['updated_by'] = $request->user()->id;

            $organisationChart->update($data);
            $this->syncNodes($organisationChart, (array) $request->validated('nodes', []));
        });

        $activity->log(
            'organisation_chart_updated',
            "{$request->user()->name} updated organisation chart {$organisationChart->title}.",
            $organisationChart,
            user: $request->user(),
            request: $request,
        );

        return redirect()->route('organisation-chart.show', $organisationChart)->with('success', 'Organisation chart updated successfully.');
    }

    public function archive(Request $request, OrganisationChart $organisationChart, ActivityLogger $activity): RedirectResponse
    {
        Gate::authorize('archive', $organisationChart);

        $organisationChart->update(['archived_by' => $request->user()->id]);
        $organisationChart->delete();

        $activity->log(
            'organisation_chart_archived',
            "{$request->user()->name} archived organisation chart {$organisationChart->title}.",
            $organisationChart,
            user: $request->user(),
            request: $request,
        );

        return redirect()->route('organisation-chart.index')->with('success', 'Organisation chart archived successfully.');
    }

    public function archived(Request $request): View
    {
        Gate::authorize('viewAny', OrganisationChart::class);

        $charts = OrganisationChart::onlyTrashed()
            ->visibleTo($request->user())
            ->with(['project', 'archivedBy'])
            ->withCount('nodes')
            ->latest('deleted_at')
            ->paginate(10)
            ->withQueryString();

        return view('organisation-chart.archived', compact('charts'));
    }

    public function restore(Request $request, int $id, ActivityLogger $activity): RedirectResponse
    {
        $organisationChart = OrganisationChart::withTrashed()->findOrFail($id);

        Gate::authorize('restore', $organisationChart);

        $organisationChart->restore();
        $organisationChart->update([
            'archived_by' => null,
            'updated_by' => $request->user()->id,
        ]);

        $activity->log(
            'organisation_chart_restored',
            "{$request->user()->name} restored organisation chart {$organisationChart->title}.",
            $organisationChart,
            user: $request->user(),
            request: $request,
        );

        return redirect()->route('organisation-chart.show', $organisationChart)->with('success', 'Organisation chart restored successfully.');
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

    /**
     * @return array<string, mixed>
     */
    private function chartFormData(Request $request): array
    {
        return [
            'projects' => Project::where('is_active', true)->orderBy('name')->get(),
            'departments' => Department::where('is_active', true)->orderBy('name')->get(),
            'provinces' => Province::where('is_active', true)->orderBy('name')->get(),
            'districts' => District::with('province')->where('is_active', true)->orderBy('name')->get(),
            'facilities' => Facility::with('district')->where('is_active', true)->orderBy('name')->get(),
            'jobTitles' => JobTitle::where('is_active', true)->orderBy('name')->get(),
            'employees' => Employee::query()
                ->visibleTo($request->user())
                ->with(['jobTitle', 'province'])
                ->orderBy('last_name')
                ->orderBy('first_name')
                ->limit(2500)
                ->get(),
            'statuses' => OrganisationChart::STATUSES,
            'nodeTypes' => OrganisationChartNode::TYPES,
        ];
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function chartData(array $data): array
    {
        foreach (['project_id', 'effective_date', 'description'] as $field) {
            if (array_key_exists($field, $data) && blank($data[$field])) {
                $data[$field] = null;
            }
        }

        return Arr::only($data, [
            'title',
            'project_id',
            'status',
            'effective_date',
            'description',
            'created_by',
            'updated_by',
            'archived_by',
        ]);
    }

    /**
     * @return array<string>
     */
    private function nodeRelations(): array
    {
        return [
            'employee',
            'jobTitle',
            'project',
            'department',
            'province',
            'district',
            'facility',
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $nodes
     */
    private function syncNodes(OrganisationChart $organisationChart, array $nodes): void
    {
        foreach ($nodes as $index => $node) {
            $nodeId = filled($node['id'] ?? null) ? (int) $node['id'] : null;

            if ((bool) ($node['_delete'] ?? false)) {
                if ($nodeId) {
                    $organisationChart->nodes()->whereKey($nodeId)->delete();
                }

                continue;
            }

            $data = $this->nodeData($node, $index);

            if ($nodeId) {
                $organisationChart->nodes()->whereKey($nodeId)->update($data);
                continue;
            }

            $organisationChart->nodes()->create($data);
        }
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function nodeData(array $data, int $index): array
    {
        foreach ([
            'parent_id',
            'subtitle',
            'planned_positions',
            'employee_id',
            'job_title_id',
            'project_id',
            'department_id',
            'province_id',
            'district_id',
            'facility_id',
        ] as $field) {
            if (array_key_exists($field, $data) && blank($data[$field])) {
                $data[$field] = null;
            }
        }

        $data['sort_order'] = filled($data['sort_order'] ?? null) ? (int) $data['sort_order'] : ($index + 1);

        return Arr::only($data, [
            'parent_id',
            'label',
            'subtitle',
            'node_type',
            'planned_positions',
            'employee_id',
            'job_title_id',
            'project_id',
            'department_id',
            'province_id',
            'district_id',
            'facility_id',
            'sort_order',
        ]);
    }
}
