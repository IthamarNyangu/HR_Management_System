<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreStaffPromotionRequest;
use App\Http\Requests\UpdateStaffPromotionRequest;
use App\Models\Department;
use App\Models\District;
use App\Models\Employee;
use App\Models\Facility;
use App\Models\JobTitle;
use App\Models\Project;
use App\Models\PromotionType;
use App\Models\Province;
use App\Models\StaffPromotion;
use App\Services\ActivityLogger;
use App\Services\ReferenceNumberService;
use App\Services\StaffPromotionApplicationService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class StaffPromotionController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', StaffPromotion::class);

        $promotions = StaffPromotion::query()
            ->with(['employee', 'province', 'district', 'facility', 'project', 'department', 'oldJobTitle', 'newJobTitle', 'promotionType'])
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
            ->when($request->filled('province_id'), fn ($query) => $query->where('province_id', $request->integer('province_id')))
            ->when($request->filled('district_id'), fn ($query) => $query->where('district_id', $request->integer('district_id')))
            ->when($request->filled('facility_id'), fn ($query) => $query->where('facility_id', $request->integer('facility_id')))
            ->when($request->filled('project_id'), fn ($query) => $query->where('project_id', $request->integer('project_id')))
            ->when($request->filled('department_id'), fn ($query) => $query->where('department_id', $request->integer('department_id')))
            ->when($request->filled('old_job_title_id'), fn ($query) => $query->where('old_job_title_id', $request->integer('old_job_title_id')))
            ->when($request->filled('new_job_title_id'), fn ($query) => $query->where('new_job_title_id', $request->integer('new_job_title_id')))
            ->when($request->filled('promotion_type_id'), fn ($query) => $query->where('promotion_type_id', $request->integer('promotion_type_id')))
            ->when($request->filled('promotion_from'), fn ($query) => $query->whereDate('promotion_date', '>=', $request->date('promotion_from')))
            ->when($request->filled('promotion_to'), fn ($query) => $query->whereDate('promotion_date', '<=', $request->date('promotion_to')))
            ->when($request->filled('year'), fn ($query) => $query->whereYear('promotion_date', $request->integer('year')))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('staff-promotions.index', $this->formData($request) + compact('promotions'));
    }

    public function create(Request $request): View
    {
        Gate::authorize('create', StaffPromotion::class);

        $promotion = new StaffPromotion([
            'province_id' => $request->user()->hasRole('HR Officer') ? $request->user()->province_id : null,
            'promotion_date' => now()->toDateString(),
        ]);

        return view('staff-promotions.create', $this->formData($request, $promotion) + compact('promotion'));
    }

    public function store(
        StoreStaffPromotionRequest $request,
        ReferenceNumberService $referenceNumbers,
        ActivityLogger $activity,
        StaffPromotionApplicationService $promotionApplications,
    ): RedirectResponse
    {
        $promotion = DB::transaction(function () use ($request, $referenceNumbers) {
            $data = $this->promotionData($request->validated());
            $data['reference_no'] = $referenceNumbers->generate('PROM', 'staff_promotions');
            $data['created_by'] = $request->user()->id;
            $data['updated_by'] = $request->user()->id;

            if ($request->user()->hasRole('HR Officer')) {
                $data['province_id'] = $request->user()->province_id;
            }

            return StaffPromotion::create($data);
        });

        $promotionApplications->applyForPromotion($promotion, $activity, $request->user(), $request);

        if ($request->hasFile('supporting_document')) {
            $this->storeInitialAttachment($request, $promotion, $activity);
        }

        $activity->log(
            'promotion_created',
            "{$request->user()->name} created staff promotion {$promotion->reference_no}.",
            $promotion,
            user: $request->user(),
            request: $request,
        );

        return redirect()->route('staff-promotions.show', $promotion)->with('success', 'Staff promotion created successfully.');
    }

    public function show(StaffPromotion $staffPromotion): View
    {
        Gate::authorize('view', $staffPromotion);

        $staffPromotion->load($this->promotionRelations());

        return view('staff-promotions.show', ['promotion' => $staffPromotion]);
    }

    public function edit(Request $request, StaffPromotion $staffPromotion): View
    {
        Gate::authorize('update', $staffPromotion);

        $staffPromotion->load(['employee']);

        return view('staff-promotions.edit', $this->formData($request, $staffPromotion) + ['promotion' => $staffPromotion]);
    }

    public function update(
        UpdateStaffPromotionRequest $request,
        StaffPromotion $staffPromotion,
        ActivityLogger $activity,
        StaffPromotionApplicationService $promotionApplications,
    ): RedirectResponse
    {
        $data = $this->promotionData($request->validated());
        $data['updated_by'] = $request->user()->id;

        if ($request->user()->hasRole('HR Officer')) {
            $data['province_id'] = $request->user()->province_id;
        }

        $staffPromotion->update($data);

        $promotionApplications->applyForPromotion($staffPromotion->fresh(), $activity, $request->user(), $request);

        if ($request->hasFile('supporting_document')) {
            $this->storeInitialAttachment($request, $staffPromotion->fresh(), $activity);
        }

        $activity->log(
            'promotion_updated',
            "{$request->user()->name} updated staff promotion {$staffPromotion->reference_no}.",
            $staffPromotion,
            user: $request->user(),
            request: $request,
        );

        return redirect()->route('staff-promotions.show', $staffPromotion)->with('success', 'Staff promotion updated successfully.');
    }

    public function archive(Request $request, StaffPromotion $staffPromotion, ActivityLogger $activity): RedirectResponse
    {
        Gate::authorize('archive', $staffPromotion);

        $staffPromotion->update(['archived_by' => $request->user()->id]);
        $staffPromotion->delete();

        $activity->log(
            'promotion_archived',
            "{$request->user()->name} archived staff promotion {$staffPromotion->reference_no}.",
            $staffPromotion,
            user: $request->user(),
            request: $request,
        );

        return redirect()->route('staff-promotions.index')->with('success', 'Staff promotion archived successfully.');
    }

    public function archived(Request $request): View
    {
        Gate::authorize('viewAny', StaffPromotion::class);

        $promotions = StaffPromotion::onlyTrashed()
            ->with(['employee', 'province', 'oldJobTitle', 'newJobTitle', 'promotionType', 'archivedBy'])
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

        return view('staff-promotions.archived', compact('promotions'));
    }

    public function restore(Request $request, int $id, ActivityLogger $activity): RedirectResponse
    {
        $promotion = StaffPromotion::withTrashed()->findOrFail($id);

        Gate::authorize('restore', $promotion);

        $promotion->restore();
        $promotion->update([
            'archived_by' => null,
            'updated_by' => $request->user()->id,
        ]);

        $activity->log(
            'promotion_restored',
            "{$request->user()->name} restored staff promotion {$promotion->reference_no}.",
            $promotion,
            user: $request->user(),
            request: $request,
        );

        return redirect()->route('staff-promotions.show', $promotion)->with('success', 'Staff promotion restored successfully.');
    }

    /**
     * @return array<string>
     */
    private function promotionRelations(): array
    {
        return [
            'employee',
            'province',
            'district',
            'facility',
            'project',
            'department',
            'oldJobTitle',
            'newJobTitle',
            'promotionType',
            'createdBy',
            'updatedBy',
            'archivedBy',
            'attachments.uploadedBy',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(Request $request, ?StaffPromotion $promotion = null): array
    {
        $user = $request->user();
        $selectedEmployee = $this->selectedEmployeeForForm($request, $promotion?->employee_id);

        return [
            'selectedEmployeeOption' => $selectedEmployee ? $this->employeeSearchPayload($selectedEmployee) : null,
            'employees' => Employee::query()->visibleTo($user)->with(['province', 'district', 'facility', 'project', 'department', 'jobTitle'])->orderBy('last_name')->orderBy('first_name')->get(),
            'provinces' => Province::where('is_active', true)
                ->when($user->hasRole('HR Officer'), fn ($query) => $query->whereKey($user->province_id))
                ->orderBy('name')
                ->get(),
            'districts' => District::with('province')->where('is_active', true)->orderBy('name')->get(),
            'facilities' => Facility::with('district')->where('is_active', true)->orderBy('name')->get(),
            'projects' => Project::where('is_active', true)->orderBy('name')->get(),
            'departments' => Department::where('is_active', true)->orderBy('name')->get(),
            'jobTitles' => JobTitle::where('is_active', true)->orderBy('name')->get(),
            'promotionTypes' => PromotionType::where('is_active', true)->orderBy('name')->get(),
        ];
    }

    private function selectedEmployeeForForm(Request $request, ?int $fallbackId): ?Employee
    {
        $employeeId = $request->old('employee_id', $fallbackId);

        if (! $employeeId) {
            return null;
        }

        return Employee::query()
            ->visibleTo($request->user())
            ->with(['jobTitle', 'province', 'district', 'facility', 'project', 'department'])
            ->find($employeeId);
    }

    /**
     * @return array<string, mixed>
     */
    private function employeeSearchPayload(Employee $employee): array
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
            'province_id' => $employee->province_id,
            'district_id' => $employee->district_id,
            'facility_id' => $employee->facility_id,
            'project_id' => $employee->project_id,
            'department_id' => $employee->department_id,
            'job_title_id' => $employee->job_title_id,
        ];
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function promotionData(array $data): array
    {
        foreach (['district_id', 'facility_id', 'project_id', 'department_id', 'old_job_title_id', 'promotion_type_id', 'effective_date', 'comment'] as $field) {
            if (array_key_exists($field, $data) && blank($data[$field])) {
                $data[$field] = null;
            }
        }

        return Arr::only($data, [
            'reference_no',
            'employee_id',
            'province_id',
            'district_id',
            'facility_id',
            'project_id',
            'department_id',
            'old_job_title_id',
            'new_job_title_id',
            'promotion_type_id',
            'promotion_date',
            'effective_date',
            'comment',
            'job_title_applied_at',
            'created_by',
            'updated_by',
            'archived_by',
        ]);
    }

    private function storeInitialAttachment(Request $request, StaffPromotion $promotion, ActivityLogger $activity): void
    {
        $file = $request->file('supporting_document');

        if (! $file) {
            return;
        }

        $path = $file->store("staff-promotions/{$promotion->id}", 'local');

        $attachment = $promotion->attachments()->create([
            'document_type_id' => null,
            'original_filename' => $file->getClientOriginalName(),
            'stored_filename' => basename($path),
            'file_path' => $path,
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize() ?: 0,
            'uploaded_by' => $request->user()->id,
        ]);

        $attachment->setRelation('attachable', $promotion);

        $activity->log(
            'promotion_attachment_uploaded',
            "{$request->user()->name} uploaded {$attachment->original_filename} to {$promotion->reference_no}.",
            $attachment,
            user: $request->user(),
            request: $request,
        );
    }
}
