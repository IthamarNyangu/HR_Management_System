<?php

namespace App\Http\Controllers\Recruitment;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreJobOpeningRequest;
use App\Http\Requests\UpdateJobOpeningRequest;
use App\Models\Department;
use App\Models\District;
use App\Models\EmploymentType;
use App\Models\Facility;
use App\Models\JobOpening;
use App\Models\JobTitle;
use App\Models\Project;
use App\Models\Province;
use App\Services\ActivityLogger;
use App\Services\ReferenceNumberService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class JobOpeningController extends Controller
{
    public function dashboard(Request $request): View
    {
        Gate::authorize('viewAny', JobOpening::class);

        $jobs = JobOpening::query()->visibleTo($request->user());

        $cards = [
            ['label' => 'Published Jobs', 'value' => (clone $jobs)->where('status', JobOpening::STATUS_PUBLISHED)->count()],
            ['label' => 'Internal Jobs', 'value' => (clone $jobs)->whereIn('visibility', [JobOpening::VISIBILITY_INTERNAL, JobOpening::VISIBILITY_BOTH])->count()],
            ['label' => 'Closing Soon', 'value' => (clone $jobs)->where('status', JobOpening::STATUS_PUBLISHED)->whereBetween('closing_date', [today(), today()->addDays(14)])->count()],
            ['label' => 'Closed This Month', 'value' => (clone $jobs)->where('status', JobOpening::STATUS_CLOSED)->whereYear('closed_at', now()->year)->whereMonth('closed_at', now()->month)->count()],
        ];

        $latestJobs = JobOpening::query()
            ->visibleTo($request->user())
            ->with(['department', 'province'])
            ->latest()
            ->take(5)
            ->get();

        return view('recruitment.index', compact('cards', 'latestJobs'));
    }

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', JobOpening::class);

        $perPage = (int) $request->input('per_page', 10);
        $perPage = in_array($perPage, [5, 10, 25, 50], true) ? $perPage : 10;

        $jobOpenings = JobOpening::query()
            ->with(['department', 'project', 'province', 'district', 'facility', 'jobTitle', 'employmentType'])
            ->visibleTo($request->user())
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search');

                $query->where(function ($query) use ($search) {
                    $query->where('reference_no', 'like', "%{$search}%")
                        ->orWhere('title', 'like', "%{$search}%")
                        ->orWhere('summary', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->when($request->filled('visibility'), fn ($query) => $query->where('visibility', $request->string('visibility')))
            ->when($request->filled('province_id'), fn ($query) => $query->where('province_id', $request->integer('province_id')))
            ->when($request->filled('department_id'), fn ($query) => $query->where('department_id', $request->integer('department_id')))
            ->when($request->filled('project_id'), fn ($query) => $query->where('project_id', $request->integer('project_id')))
            ->when($request->filled('closing_from'), fn ($query) => $query->whereDate('closing_date', '>=', $request->date('closing_from')))
            ->when($request->filled('closing_to'), fn ($query) => $query->whereDate('closing_date', '<=', $request->date('closing_to')))
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        return view('recruitment.job-openings.index', $this->formData($request) + compact('jobOpenings', 'perPage'));
    }

    public function create(Request $request): View
    {
        Gate::authorize('create', JobOpening::class);

        $jobOpening = new JobOpening([
            'status' => JobOpening::STATUS_DRAFT,
            'visibility' => JobOpening::VISIBILITY_INTERNAL,
            'province_id' => $request->user()->hasRole('HR Officer') ? $request->user()->province_id : null,
            'opening_date' => now()->toDateString(),
            'closing_date' => now()->addWeeks(2)->toDateString(),
            'number_of_positions' => 1,
            'show_number_of_positions' => true,
        ]);

        return view('recruitment.job-openings.create', $this->formData($request) + compact('jobOpening'));
    }

    public function store(
        StoreJobOpeningRequest $request,
        ReferenceNumberService $referenceNumbers,
        ActivityLogger $activity,
    ): RedirectResponse {
        $jobOpening = DB::transaction(function () use ($request, $referenceNumbers) {
            $data = $this->jobOpeningData($request->validated());
            $data['reference_no'] = $referenceNumbers->generate('JOB', 'job_openings');
            $data['slug'] = $this->uniqueSlug($data['title'], $data['reference_no']);
            $data['created_by'] = $request->user()->id;
            $data['updated_by'] = $request->user()->id;

            if ($request->user()->hasRole('HR Officer')) {
                $data['province_id'] = $request->user()->province_id;
            }

            return JobOpening::create($data);
        });

        $activity->log(
            'job_opening_created',
            "{$request->user()->name} created job opening {$jobOpening->reference_no}.",
            $jobOpening,
            user: $request->user(),
            request: $request,
        );

        return redirect()->route('recruitment.job-openings.show', $jobOpening)->with('success', 'Job opening created successfully.');
    }

    public function show(JobOpening $jobOpening): View
    {
        Gate::authorize('view', $jobOpening);

        $jobOpening->load($this->relations());

        return view('recruitment.job-openings.show', compact('jobOpening'));
    }

    public function edit(Request $request, JobOpening $jobOpening): View
    {
        Gate::authorize('update', $jobOpening);

        return view('recruitment.job-openings.edit', $this->formData($request) + compact('jobOpening'));
    }

    public function update(UpdateJobOpeningRequest $request, JobOpening $jobOpening, ActivityLogger $activity): RedirectResponse
    {
        $data = $this->jobOpeningData($request->validated());
        $data['updated_by'] = $request->user()->id;

        if ($request->user()->hasRole('HR Officer')) {
            $data['province_id'] = $request->user()->province_id;
        }

        $jobOpening->update($data);

        $activity->log(
            'job_opening_updated',
            "{$request->user()->name} updated job opening {$jobOpening->reference_no}.",
            $jobOpening,
            user: $request->user(),
            request: $request,
        );

        return redirect()->route('recruitment.job-openings.show', $jobOpening)->with('success', 'Job opening updated successfully.');
    }

    public function publish(Request $request, JobOpening $jobOpening, ActivityLogger $activity): RedirectResponse
    {
        Gate::authorize('publish', $jobOpening);

        $jobOpening->update([
            'status' => JobOpening::STATUS_PUBLISHED,
            'published_at' => now(),
            'closed_at' => null,
            'updated_by' => $request->user()->id,
        ]);

        $activity->log('job_opening_published', "{$request->user()->name} published job opening {$jobOpening->reference_no}.", $jobOpening, user: $request->user(), request: $request);

        return back()->with('success', 'Job opening published successfully.');
    }

    public function close(Request $request, JobOpening $jobOpening, ActivityLogger $activity): RedirectResponse
    {
        Gate::authorize('close', $jobOpening);

        $jobOpening->update([
            'status' => JobOpening::STATUS_CLOSED,
            'closed_at' => now(),
            'updated_by' => $request->user()->id,
        ]);

        $activity->log('job_opening_closed', "{$request->user()->name} closed job opening {$jobOpening->reference_no}.", $jobOpening, user: $request->user(), request: $request);

        return back()->with('success', 'Job opening closed successfully.');
    }

    public function cancel(Request $request, JobOpening $jobOpening, ActivityLogger $activity): RedirectResponse
    {
        Gate::authorize('cancel', $jobOpening);

        $jobOpening->update([
            'status' => JobOpening::STATUS_CANCELLED,
            'updated_by' => $request->user()->id,
        ]);

        $activity->log('job_opening_cancelled', "{$request->user()->name} cancelled job opening {$jobOpening->reference_no}.", $jobOpening, user: $request->user(), request: $request);

        return back()->with('success', 'Job opening cancelled successfully.');
    }

    public function archive(Request $request, JobOpening $jobOpening, ActivityLogger $activity): RedirectResponse
    {
        Gate::authorize('archive', $jobOpening);

        $jobOpening->update(['archived_by' => $request->user()->id]);
        $jobOpening->delete();

        $activity->log('job_opening_archived', "{$request->user()->name} archived job opening {$jobOpening->reference_no}.", $jobOpening, user: $request->user(), request: $request);

        return redirect()->route('recruitment.job-openings.index')->with('success', 'Job opening archived successfully.');
    }

    public function archived(Request $request): View
    {
        Gate::authorize('viewAny', JobOpening::class);

        $jobOpenings = JobOpening::onlyTrashed()
            ->with(['department', 'province', 'archivedBy'])
            ->visibleTo($request->user())
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search');

                $query->where('reference_no', 'like', "%{$search}%")
                    ->orWhere('title', 'like', "%{$search}%");
            })
            ->latest('deleted_at')
            ->paginate(10)
            ->withQueryString();

        return view('recruitment.job-openings.archived', compact('jobOpenings'));
    }

    public function restore(Request $request, int $id, ActivityLogger $activity): RedirectResponse
    {
        $jobOpening = JobOpening::withTrashed()->findOrFail($id);

        Gate::authorize('restore', $jobOpening);

        $jobOpening->restore();
        $jobOpening->update([
            'archived_by' => null,
            'updated_by' => $request->user()->id,
        ]);

        $activity->log('job_opening_restored', "{$request->user()->name} restored job opening {$jobOpening->reference_no}.", $jobOpening, user: $request->user(), request: $request);

        return redirect()->route('recruitment.job-openings.show', $jobOpening)->with('success', 'Job opening restored successfully.');
    }

    private function uniqueSlug(string $title, string $referenceNo): string
    {
        $base = Str::slug($title.' '.$referenceNo);
        $slug = $base;
        $suffix = 2;

        while (JobOpening::withTrashed()->where('slug', $slug)->exists()) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }

    /**
     * @return array<string>
     */
    private function relations(): array
    {
        return [
            'jobTitle',
            'project',
            'department',
            'province',
            'district',
            'facility',
            'employmentType',
            'createdBy',
            'updatedBy',
            'archivedBy',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(Request $request): array
    {
        $user = $request->user();

        return [
            'provinces' => Province::where('is_active', true)
                ->when($user->hasRole('HR Officer'), fn ($query) => $query->whereKey($user->province_id))
                ->orderBy('name')
                ->get(),
            'districts' => District::with('province')->where('is_active', true)->orderBy('name')->get(),
            'facilities' => Facility::with('district')->where('is_active', true)->orderBy('name')->get(),
            'projects' => Project::where('is_active', true)->orderBy('name')->get(),
            'departments' => Department::where('is_active', true)->orderBy('name')->get(),
            'jobTitles' => JobTitle::where('is_active', true)->orderBy('name')->get(),
            'employmentTypes' => EmploymentType::where('is_active', true)->orderBy('name')->get(),
            'statuses' => JobOpening::STATUSES,
            'visibilities' => JobOpening::VISIBILITIES,
        ];
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function jobOpeningData(array $data): array
    {
        foreach ([
            'job_title_id',
            'project_id',
            'department_id',
            'province_id',
            'district_id',
            'facility_id',
            'employment_type_id',
            'number_of_positions',
            'summary',
            'location_details',
            'internal_notes',
        ] as $field) {
            if (array_key_exists($field, $data) && blank($data[$field])) {
                $data[$field] = null;
            }
        }

        $data['show_number_of_positions'] = (bool) ($data['show_number_of_positions'] ?? false);

        return Arr::only($data, [
            'reference_no',
            'slug',
            'title',
            'job_title_id',
            'project_id',
            'department_id',
            'province_id',
            'district_id',
            'facility_id',
            'employment_type_id',
            'visibility',
            'status',
            'number_of_positions',
            'show_number_of_positions',
            'opening_date',
            'closing_date',
            'summary',
            'description',
            'responsibilities',
            'requirements',
            'qualifications',
            'experience_required',
            'contract_details',
            'work_level',
            'location_details',
            'application_instructions',
            'internal_notes',
            'published_at',
            'closed_at',
            'created_by',
            'updated_by',
            'archived_by',
        ]);
    }
}
