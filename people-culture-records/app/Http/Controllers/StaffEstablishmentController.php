<?php

namespace App\Http\Controllers;

use App\Exports\StaffEstablishmentPlanExport;
use App\Http\Requests\StoreStaffEstablishmentPlanRequest;
use App\Http\Requests\UpdateStaffEstablishmentPlanRequest;
use App\Models\Department;
use App\Models\District;
use App\Models\Employee;
use App\Models\JobTitle;
use App\Models\Project;
use App\Models\Province;
use App\Models\StaffEstablishmentLine;
use App\Models\StaffEstablishmentPlan;
use App\Services\ActivityLogger;
use App\Services\ReferenceNumberService;
use App\Services\StaffEstablishmentMetricsService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;

class StaffEstablishmentController extends Controller
{
    public function index(Request $request, StaffEstablishmentMetricsService $metrics): View
    {
        Gate::authorize('viewAny', StaffEstablishmentPlan::class);

        $plans = StaffEstablishmentPlan::query()
            ->with(['project', 'createdBy'])
            ->withCount('lines')
            ->visibleTo($request->user())
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search');

                $query->where(function ($query) use ($search) {
                    $query->where('reference_no', 'like', "%{$search}%")
                        ->orWhere('title', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->when($request->filled('project_id'), fn ($query) => $query->where('project_id', $request->integer('project_id')))
            ->latest('effective_month')
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $latestPlan = $metrics->latestVisiblePlan($request->user());
        $latestSummary = $latestPlan ? $metrics->summaryForPlan($latestPlan, $request->user()) : null;

        return view('staff-establishment.index', $this->formData() + compact('plans', 'latestPlan', 'latestSummary'));
    }

    public function create(): View
    {
        Gate::authorize('create', StaffEstablishmentPlan::class);

        $plan = new StaffEstablishmentPlan([
            'status' => StaffEstablishmentPlan::STATUS_DRAFT,
            'effective_month' => now()->startOfMonth(),
        ]);

        return view('staff-establishment.create', $this->formData() + compact('plan'));
    }

    public function store(
        StoreStaffEstablishmentPlanRequest $request,
        ReferenceNumberService $referenceNumbers,
        ActivityLogger $activity,
    ): RedirectResponse {
        $plan = DB::transaction(function () use ($request, $referenceNumbers) {
            $data = $this->planData($request->validated());
            $data['reference_no'] = $referenceNumbers->generate('EST', 'staff_establishment_plans');
            $data['created_by'] = $request->user()->id;
            $data['updated_by'] = $request->user()->id;
            $this->applyApprovalFields($data, $request->user()->id);

            $plan = StaffEstablishmentPlan::create($data);
            $this->syncLines($plan, $request->validated('lines', []));

            return $plan;
        });

        $activity->log(
            'staff_establishment_created',
            "{$request->user()->name} created staff establishment plan {$plan->reference_no}.",
            $plan,
            user: $request->user(),
            request: $request,
        );
        $this->logMatrixActivity($request, $plan, $activity);

        return redirect()->route('staff-establishment.show', $plan)->with('success', 'Staff establishment plan created successfully.');
    }

    public function show(Request $request, StaffEstablishmentPlan $staffEstablishmentPlan, StaffEstablishmentMetricsService $metrics): View
    {
        Gate::authorize('view', $staffEstablishmentPlan);

        $staffEstablishmentPlan->load(['project', 'createdBy', 'updatedBy', 'approvedBy']);
        $lines = $metrics->visibleLines($staffEstablishmentPlan, $request->user());
        $summary = $metrics->summaryForLines($lines, $request->user());
        $viewMode = in_array($request->query('view', 'all'), ['all', 'activity', 'vacancies'], true)
            ? $request->query('view', 'all')
            : 'all';
        $allRows = $metrics->rowsForPlan($staffEstablishmentPlan, $request->user());
        $filteredRows = match ($viewMode) {
            'activity' => array_values(array_filter($allRows, fn (array $row) => $row['budgeted'] > 0 || $row['filled'] > 0 || $row['overstaffed'] > 0)),
            'vacancies' => array_values(array_filter($allRows, fn (array $row) => $row['vacant'] > 0)),
            default => $allRows,
        };
        $rows = $this->paginateRows($filteredRows, $request);

        return view('staff-establishment.show', [
            'plan' => $staffEstablishmentPlan,
            'lines' => $lines,
            'summary' => $summary,
            'rows' => $rows,
            'viewMode' => $viewMode,
        ]);
    }

    public function edit(StaffEstablishmentPlan $staffEstablishmentPlan): View
    {
        Gate::authorize('update', $staffEstablishmentPlan);

        $staffEstablishmentPlan->load(['lines']);

        return view('staff-establishment.edit', $this->formData() + ['plan' => $staffEstablishmentPlan]);
    }

    public function update(UpdateStaffEstablishmentPlanRequest $request, StaffEstablishmentPlan $staffEstablishmentPlan, ActivityLogger $activity): RedirectResponse
    {
        DB::transaction(function () use ($request, $staffEstablishmentPlan) {
            $data = $this->planData($request->validated());
            $data['updated_by'] = $request->user()->id;
            $this->applyApprovalFields($data, $request->user()->id, $staffEstablishmentPlan);

            $staffEstablishmentPlan->update($data);
            $this->syncLines($staffEstablishmentPlan, $request->validated('lines', []));
        });

        $activity->log(
            'staff_establishment_updated',
            "{$request->user()->name} updated staff establishment plan {$staffEstablishmentPlan->reference_no}.",
            $staffEstablishmentPlan,
            user: $request->user(),
            request: $request,
        );
        $this->logMatrixActivity($request, $staffEstablishmentPlan, $activity);

        return redirect()->route('staff-establishment.show', $staffEstablishmentPlan)->with('success', 'Staff establishment plan updated successfully.');
    }

    public function archive(Request $request, StaffEstablishmentPlan $staffEstablishmentPlan, ActivityLogger $activity): RedirectResponse
    {
        Gate::authorize('archive', $staffEstablishmentPlan);

        $staffEstablishmentPlan->update(['archived_by' => $request->user()->id]);
        $staffEstablishmentPlan->delete();

        $activity->log(
            'staff_establishment_archived',
            "{$request->user()->name} archived staff establishment plan {$staffEstablishmentPlan->reference_no}.",
            $staffEstablishmentPlan,
            user: $request->user(),
            request: $request,
        );

        return redirect()->route('staff-establishment.index')->with('success', 'Staff establishment plan archived successfully.');
    }

    public function archived(Request $request): View
    {
        Gate::authorize('viewAny', StaffEstablishmentPlan::class);

        $plans = StaffEstablishmentPlan::onlyTrashed()
            ->with(['project', 'archivedBy'])
            ->withCount('lines')
            ->visibleTo($request->user())
            ->latest('deleted_at')
            ->paginate(10)
            ->withQueryString();

        return view('staff-establishment.archived', compact('plans'));
    }

    public function restore(Request $request, int $id, ActivityLogger $activity): RedirectResponse
    {
        $plan = StaffEstablishmentPlan::withTrashed()->findOrFail($id);

        Gate::authorize('restore', $plan);

        $plan->restore();
        $plan->update([
            'archived_by' => null,
            'updated_by' => $request->user()->id,
        ]);

        $activity->log(
            'staff_establishment_restored',
            "{$request->user()->name} restored staff establishment plan {$plan->reference_no}.",
            $plan,
            user: $request->user(),
            request: $request,
        );

        return redirect()->route('staff-establishment.show', $plan)->with('success', 'Staff establishment plan restored successfully.');
    }

    public function exportExcel(Request $request, StaffEstablishmentPlan $staffEstablishmentPlan, StaffEstablishmentMetricsService $metrics, ActivityLogger $activity): BinaryFileResponse
    {
        Gate::authorize('export', $staffEstablishmentPlan);

        $this->logExport('Excel', $request, $staffEstablishmentPlan, $activity);

        return Excel::download(
            new StaffEstablishmentPlanExport($staffEstablishmentPlan, $request->user(), $metrics),
            str($staffEstablishmentPlan->title)->slug()->append('-staff-establishment-')->append(now()->format('Ymd-His'))->append('.xlsx')->toString(),
        );
    }

    public function exportPdf(Request $request, StaffEstablishmentPlan $staffEstablishmentPlan, StaffEstablishmentMetricsService $metrics, ActivityLogger $activity): Response
    {
        Gate::authorize('export', $staffEstablishmentPlan);

        $staffEstablishmentPlan->load(['project', 'createdBy', 'approvedBy']);
        $rows = $metrics->rowsForPlan($staffEstablishmentPlan, $request->user());
        $summary = $metrics->summaryForPlan($staffEstablishmentPlan, $request->user());
        $this->logExport('PDF', $request, $staffEstablishmentPlan, $activity);

        return Pdf::loadView('staff-establishment.pdf', [
            'plan' => $staffEstablishmentPlan,
            'rows' => $rows,
            'summary' => $summary,
            'generatedBy' => $request->user(),
            'generatedAt' => now(),
        ])->setPaper('a4', 'landscape')
            ->download(str($staffEstablishmentPlan->title)->slug()->append('-staff-establishment-')->append(now()->format('Ymd-His'))->append('.pdf')->toString());
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(): array
    {
        return [
            'projects' => Project::where('is_active', true)->orderBy('name')->get(),
            'departments' => Department::where('is_active', true)->orderBy('name')->get(),
            'jobTitles' => JobTitle::where('is_active', true)->orderBy('name')->get(),
            'provinces' => Province::where('is_active', true)->orderBy('name')->get(),
            'districts' => District::with('province')->where('is_active', true)->orderBy('name')->get(),
            'statuses' => StaffEstablishmentPlan::STATUSES,
            'matrixCurrentEstablishment' => $this->matrixCurrentEstablishment(),
        ];
    }

    /**
     * @return array{all: array<string, int>, projects: array<int|string, array<string, int>>}
     */
    private function matrixCurrentEstablishment(): array
    {
        $counts = [
            'all' => [],
            'projects' => [],
        ];

        Employee::query()
            ->selectRaw('job_title_id, province_id, project_id, COUNT(*) as total')
            ->whereNotNull('job_title_id')
            ->whereNotNull('province_id')
            ->whereHas('employmentStatus', function ($query) {
                $query->where('code', 'ACTIVE')->orWhere('name', 'Active');
            })
            ->groupBy('job_title_id', 'province_id', 'project_id')
            ->get()
            ->each(function ($row) use (&$counts) {
                $key = "{$row->job_title_id}|{$row->province_id}";
                $total = (int) $row->total;

                $counts['all'][$key] = ($counts['all'][$key] ?? 0) + $total;

                if ($row->project_id) {
                    $counts['projects'][$row->project_id][$key] = ($counts['projects'][$row->project_id][$key] ?? 0) + $total;
                }
            });

        return $counts;
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function planData(array $data): array
    {
        foreach (['project_id', 'notes'] as $field) {
            if (array_key_exists($field, $data) && blank($data[$field])) {
                $data[$field] = null;
            }
        }

        return Arr::only($data, [
            'reference_no',
            'title',
            'project_id',
            'status',
            'effective_month',
            'notes',
            'approved_by',
            'approved_at',
            'created_by',
            'updated_by',
            'archived_by',
        ]);
    }

    /**
     * @param array<int, array<string, mixed>> $lines
     */
    private function syncLines(StaffEstablishmentPlan $plan, array $lines): void
    {
        $keptIds = [];

        foreach ($lines as $line) {
            $lineData = $this->lineData($line);

            if (! empty($line['id'])) {
                $existingLine = $plan->lines()->whereKey($line['id'])->first();

                if ($existingLine) {
                    $existingLine->update($lineData);
                    $keptIds[] = $existingLine->id;
                    continue;
                }
            }

            $created = $plan->lines()->create($lineData);
            $keptIds[] = $created->id;
        }

        $plan->lines()
            ->when($keptIds !== [], fn ($query) => $query->whereNotIn('id', $keptIds))
            ->delete();
    }

    /**
     * @param array<string, mixed> $line
     * @return array<string, mixed>
     */
    private function lineData(array $line): array
    {
        foreach (['province_id', 'district_id', 'department_id'] as $field) {
            if (array_key_exists($field, $line) && blank($line[$field])) {
                $line[$field] = null;
            }
        }

        return Arr::only($line, [
            'job_title_id',
            'province_id',
            'district_id',
            'department_id',
            'budgeted_positions',
        ]) + [
            'facility_id' => null,
            'notes' => null,
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    private function applyApprovalFields(array &$data, int $userId, ?StaffEstablishmentPlan $existingPlan = null): void
    {
        if (($data['status'] ?? null) === StaffEstablishmentPlan::STATUS_APPROVED && ! $existingPlan?->approved_at) {
            $data['approved_by'] = $userId;
            $data['approved_at'] = now();
        }

        if (($data['status'] ?? null) === StaffEstablishmentPlan::STATUS_DRAFT) {
            $data['approved_by'] = null;
            $data['approved_at'] = null;
        }
    }

    private function logExport(string $format, Request $request, StaffEstablishmentPlan $plan, ActivityLogger $activity): void
    {
        $activity->log(
            'staff_establishment_exported_'.strtolower($format),
            "{$request->user()->name} exported staff establishment plan {$plan->reference_no} to {$format}.",
            $plan,
            [
                'format' => strtolower($format),
            ],
            user: $request->user(),
            request: $request,
        );
    }

    private function logMatrixActivity(Request $request, StaffEstablishmentPlan $plan, ActivityLogger $activity): void
    {
        if (! $request->boolean('matrix_generated')) {
            return;
        }

        $createdCount = max((int) $request->input('matrix_created_count', 0), 0);
        $updatedCount = max((int) $request->input('matrix_updated_count', 0), 0);
        $skippedCount = max((int) $request->input('matrix_skipped_count', 0), 0);
        $properties = [
            'plan_id' => $plan->id,
            'created_count' => $createdCount,
            'updated_count' => $updatedCount,
            'skipped_count' => $skippedCount,
            'selected_job_titles' => $request->input('matrix_selected_job_titles'),
            'selected_locations' => $request->input('matrix_selected_locations'),
        ];

        if ($createdCount > 0) {
            $activity->log(
                'establishment_lines_matrix_generated',
                "{$request->user()->name} generated {$createdCount} establishment line".($createdCount === 1 ? '' : 's')." for {$plan->reference_no}.",
                $plan,
                $properties,
                user: $request->user(),
                request: $request,
            );
        }

        if ($updatedCount > 0) {
            $activity->log(
                'establishment_lines_bulk_updated',
                "{$request->user()->name} updated {$updatedCount} establishment line".($updatedCount === 1 ? '' : 's')." for {$plan->reference_no}.",
                $plan,
                $properties,
                user: $request->user(),
                request: $request,
            );
        }
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @return LengthAwarePaginator<int, array<string, mixed>>
     */
    private function paginateRows(array $rows, Request $request): LengthAwarePaginator
    {
        $perPage = 10;
        $page = max((int) $request->query('page', 1), 1);
        $items = collect($rows);
        $query = $request->query();
        unset($query['page']);

        return new LengthAwarePaginator(
            $items->forPage($page, $perPage)->values(),
            $items->count(),
            $perPage,
            $page,
            [
                'path' => $request->url(),
                'query' => $query,
            ],
        );
    }
}
