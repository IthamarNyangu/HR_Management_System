<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreStaffRelocationRequest;
use App\Http\Requests\UpdateStaffRelocationRequest;
use App\Models\Department;
use App\Models\District;
use App\Models\Employee;
use App\Models\Facility;
use App\Models\JobTitle;
use App\Models\Project;
use App\Models\Province;
use App\Models\RelocationReason;
use App\Models\StaffRelocation;
use App\Services\ActivityLogger;
use App\Services\ReferenceNumberService;
use App\Services\StaffRelocationApplicationService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class StaffRelocationController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', StaffRelocation::class);

        $relocations = StaffRelocation::query()
            ->with(['employee', 'jobTitle', 'project', 'department', 'fromProvince', 'fromDistrict', 'fromFacility', 'toProvince', 'toDistrict', 'toFacility', 'relocationReason'])
            ->visibleTo($request->user())
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search');

                $query->where(function ($query) use ($search) {
                    $query->where('reference_no', 'like', "%{$search}%")
                        ->orWhereHas('employee', function ($query) use ($search) {
                            $query->where('employee_no', 'like', "%{$search}%")
                                ->orWhere('first_name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%");
                        });
                });
            })
            ->when($request->filled('from_province_id'), fn ($query) => $query->where('from_province_id', $request->integer('from_province_id')))
            ->when($request->filled('from_district_id'), fn ($query) => $query->where('from_district_id', $request->integer('from_district_id')))
            ->when($request->filled('from_facility_id'), fn ($query) => $query->where('from_facility_id', $request->integer('from_facility_id')))
            ->when($request->filled('to_province_id'), fn ($query) => $query->where('to_province_id', $request->integer('to_province_id')))
            ->when($request->filled('to_district_id'), fn ($query) => $query->where('to_district_id', $request->integer('to_district_id')))
            ->when($request->filled('to_facility_id'), fn ($query) => $query->where('to_facility_id', $request->integer('to_facility_id')))
            ->when($request->filled('project_id'), fn ($query) => $query->where('project_id', $request->integer('project_id')))
            ->when($request->filled('department_id'), fn ($query) => $query->where('department_id', $request->integer('department_id')))
            ->when($request->filled('job_title_id'), fn ($query) => $query->where('job_title_id', $request->integer('job_title_id')))
            ->when($request->filled('relocation_reason_id'), fn ($query) => $query->where('relocation_reason_id', $request->integer('relocation_reason_id')))
            ->when($request->filled('effective_from'), fn ($query) => $query->whereDate('effective_date', '>=', $request->date('effective_from')))
            ->when($request->filled('effective_to'), fn ($query) => $query->whereDate('effective_date', '<=', $request->date('effective_to')))
            ->when($request->filled('relocation_amount_min'), fn ($query) => $query->where('relocation_amount', '>=', $request->input('relocation_amount_min')))
            ->when($request->filled('relocation_amount_max'), fn ($query) => $query->where('relocation_amount', '<=', $request->input('relocation_amount_max')))
            ->when($request->filled('year'), fn ($query) => $query->whereYear('effective_date', $request->integer('year')))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('staff-relocations.index', $this->formData($request) + compact('relocations'));
    }

    public function create(Request $request): View
    {
        Gate::authorize('create', StaffRelocation::class);

        $relocation = new StaffRelocation([
            'from_province_id' => $request->user()->hasRole('HR Officer') ? $request->user()->province_id : null,
            'effective_date' => now()->toDateString(),
        ]);

        return view('staff-relocations.create', $this->formData($request) + compact('relocation'));
    }

    public function store(
        StoreStaffRelocationRequest $request,
        ReferenceNumberService $referenceNumbers,
        ActivityLogger $activity,
        StaffRelocationApplicationService $relocationApplications,
    ): RedirectResponse
    {
        $relocation = DB::transaction(function () use ($request, $referenceNumbers) {
            $data = $this->relocationData($request->validated());
            $data['reference_no'] = $referenceNumbers->generate('REL', 'staff_relocations');
            $data['created_by'] = $request->user()->id;
            $data['updated_by'] = $request->user()->id;

            return StaffRelocation::create($data);
        });

        $relocation->loadMissing(['employee', 'fromProvince', 'fromFacility', 'toProvince', 'toFacility']);

        $relocationApplications->applyForRelocation($relocation, $activity, $request->user(), $request);

        if ($request->hasFile('supporting_document')) {
            $this->storeInitialAttachment($request, $relocation, $activity);
        }

        $activity->log(
            'relocation_created',
            "{$request->user()->name} created staff relocation {$relocation->reference_no}.",
            $relocation,
            user: $request->user(),
            request: $request,
        );

        return redirect()->route('staff-relocations.show', $relocation)->with('success', 'Staff relocation created successfully.');
    }

    public function show(StaffRelocation $staffRelocation): View
    {
        Gate::authorize('view', $staffRelocation);

        $staffRelocation->load($this->relocationRelations());

        return view('staff-relocations.show', ['relocation' => $staffRelocation]);
    }

    public function edit(Request $request, StaffRelocation $staffRelocation): View
    {
        Gate::authorize('update', $staffRelocation);

        $staffRelocation->load(['employee']);

        return view('staff-relocations.edit', $this->formData($request) + ['relocation' => $staffRelocation]);
    }

    public function update(
        UpdateStaffRelocationRequest $request,
        StaffRelocation $staffRelocation,
        ActivityLogger $activity,
        StaffRelocationApplicationService $relocationApplications,
    ): RedirectResponse
    {
        DB::transaction(function () use ($request, $staffRelocation) {
            $data = $this->relocationData($request->validated());
            $data['updated_by'] = $request->user()->id;

            $staffRelocation->update($data);
        });

        $staffRelocation->loadMissing(['employee', 'fromProvince', 'fromFacility', 'toProvince', 'toFacility']);

        $relocationApplications->applyForRelocation($staffRelocation->fresh(), $activity, $request->user(), $request);

        if ($request->hasFile('supporting_document')) {
            $this->storeInitialAttachment($request, $staffRelocation->fresh(), $activity);
        }

        $activity->log(
            'relocation_updated',
            "{$request->user()->name} updated staff relocation {$staffRelocation->reference_no}.",
            $staffRelocation,
            user: $request->user(),
            request: $request,
        );

        return redirect()->route('staff-relocations.show', $staffRelocation)->with('success', 'Staff relocation updated successfully.');
    }

    public function archive(Request $request, StaffRelocation $staffRelocation, ActivityLogger $activity): RedirectResponse
    {
        Gate::authorize('archive', $staffRelocation);

        $staffRelocation->update(['archived_by' => $request->user()->id]);
        $staffRelocation->delete();

        $activity->log(
            'relocation_archived',
            "{$request->user()->name} archived staff relocation {$staffRelocation->reference_no}.",
            $staffRelocation,
            user: $request->user(),
            request: $request,
        );

        return redirect()->route('staff-relocations.index')->with('success', 'Staff relocation archived successfully.');
    }

    public function archived(Request $request): View
    {
        Gate::authorize('viewAny', StaffRelocation::class);

        $relocations = StaffRelocation::onlyTrashed()
            ->with(['employee', 'fromProvince', 'toProvince', 'relocationReason', 'archivedBy'])
            ->visibleTo($request->user())
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search');

                $query->where(function ($query) use ($search) {
                    $query->where('reference_no', 'like', "%{$search}%")
                        ->orWhereHas('employee', function ($query) use ($search) {
                            $query->where('employee_no', 'like', "%{$search}%")
                                ->orWhere('first_name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%");
                        });
                });
            })
            ->latest('deleted_at')
            ->paginate(10)
            ->withQueryString();

        return view('staff-relocations.archived', compact('relocations'));
    }

    public function restore(Request $request, int $id, ActivityLogger $activity): RedirectResponse
    {
        $relocation = StaffRelocation::withTrashed()->findOrFail($id);

        Gate::authorize('restore', $relocation);

        $relocation->restore();
        $relocation->update([
            'archived_by' => null,
            'updated_by' => $request->user()->id,
        ]);

        $activity->log(
            'relocation_restored',
            "{$request->user()->name} restored staff relocation {$relocation->reference_no}.",
            $relocation,
            user: $request->user(),
            request: $request,
        );

        return redirect()->route('staff-relocations.show', $relocation)->with('success', 'Staff relocation restored successfully.');
    }

    /**
     * @return array<string>
     */
    private function relocationRelations(): array
    {
        return [
            'employee',
            'jobTitle',
            'project',
            'department',
            'fromProvince',
            'fromDistrict',
            'fromFacility',
            'toProvince',
            'toDistrict',
            'toFacility',
            'relocationReason',
            'createdBy',
            'updatedBy',
            'archivedBy',
            'attachments.uploadedBy',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(Request $request): array
    {
        $user = $request->user();
        $employees = Employee::query()
            ->with(['province', 'district', 'facility', 'project', 'department', 'jobTitle'])
            ->when($user->hasRole('HR Officer'), function ($query) use ($user) {
                $query->orderByRaw('case when province_id = ? then 0 else 1 end', [$user->province_id]);
            })
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        return [
            'employees' => $employees,
            'provinces' => Province::where('is_active', true)->orderBy('name')->get(),
            'districts' => District::with('province')->where('is_active', true)->orderBy('name')->get(),
            'facilities' => Facility::with('district')->where('is_active', true)->orderBy('name')->get(),
            'projects' => Project::where('is_active', true)->orderBy('name')->get(),
            'departments' => Department::where('is_active', true)->orderBy('name')->get(),
            'jobTitles' => JobTitle::where('is_active', true)->orderBy('name')->get(),
            'relocationReasons' => RelocationReason::where('is_active', true)->orderBy('name')->get(),
        ];
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function relocationData(array $data): array
    {
        foreach (['job_title_id', 'project_id', 'department_id', 'from_facility_id', 'to_facility_id', 'relocation_reason_id', 'relocation_amount', 'comment'] as $field) {
            if (array_key_exists($field, $data) && blank($data[$field])) {
                $data[$field] = null;
            }
        }

        return Arr::only($data, [
            'reference_no',
            'employee_id',
            'job_title_id',
            'project_id',
            'department_id',
            'from_province_id',
            'from_district_id',
            'from_facility_id',
            'to_province_id',
            'to_district_id',
            'to_facility_id',
            'relocation_reason_id',
            'effective_date',
            'location_applied_at',
            'relocation_amount',
            'comment',
            'created_by',
            'updated_by',
            'archived_by',
        ]);
    }

    private function storeInitialAttachment(Request $request, StaffRelocation $relocation, ActivityLogger $activity): void
    {
        $file = $request->file('supporting_document');

        if (! $file) {
            return;
        }

        $relocation->loadMissing(['fromProvince', 'fromFacility', 'toProvince', 'toFacility']);

        $path = $file->store("staff-relocations/{$relocation->id}", 'local');

        $attachment = $relocation->attachments()->create([
            'document_type_id' => null,
            'original_filename' => $file->getClientOriginalName(),
            'stored_filename' => basename($path),
            'file_path' => $path,
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize() ?: 0,
            'uploaded_by' => $request->user()->id,
        ]);

        $attachment->setRelation('attachable', $relocation);

        $activity->log(
            'relocation_attachment_uploaded',
            "{$request->user()->name} uploaded {$attachment->original_filename} to {$relocation->reference_no}.",
            $attachment,
            user: $request->user(),
            request: $request,
        );
    }
}
