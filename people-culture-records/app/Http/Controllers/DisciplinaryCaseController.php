<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDisciplinaryCaseRequest;
use App\Http\Requests\UpdateDisciplinaryCaseRequest;
use App\Models\CaseStatus;
use App\Models\DisciplinaryCase;
use App\Models\District;
use App\Models\DocumentType;
use App\Models\Employee;
use App\Models\Facility;
use App\Models\OffenceCategory;
use App\Models\PenaltyType;
use App\Models\Project;
use App\Models\Province;
use App\Models\User;
use App\Notifications\DisciplinaryCaseApprovedNotification;
use App\Notifications\DisciplinaryCaseSubmittedNotification;
use App\Services\ActivityLogger;
use App\Services\ReferenceNumberService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;

class DisciplinaryCaseController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', DisciplinaryCase::class);

        $cases = DisciplinaryCase::query()
            ->with(['employee', 'province', 'district', 'facility', 'project', 'offenceCategory', 'penaltyType', 'caseStatus'])
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
            ->when($request->filled('project_id'), fn ($query) => $query->where('project_id', $request->integer('project_id')))
            ->when($request->filled('province_id'), fn ($query) => $query->where('province_id', $request->integer('province_id')))
            ->when($request->filled('district_id'), fn ($query) => $query->where('district_id', $request->integer('district_id')))
            ->when($request->filled('facility_id'), fn ($query) => $query->where('facility_id', $request->integer('facility_id')))
            ->when($request->filled('offence_category_id'), fn ($query) => $query->where('offence_category_id', $request->integer('offence_category_id')))
            ->when($request->filled('penalty_type_id'), fn ($query) => $query->where('penalty_type_id', $request->integer('penalty_type_id')))
            ->when($request->filled('case_status_id'), fn ($query) => $query->where('case_status_id', $request->integer('case_status_id')))
            ->when($request->filled('effective_from'), fn ($query) => $query->whereDate('effective_date', '>=', $request->date('effective_from')))
            ->when($request->filled('effective_to'), fn ($query) => $query->whereDate('effective_date', '<=', $request->date('effective_to')))
            ->when($request->filled('expiry_from'), fn ($query) => $query->whereDate('expiry_date', '>=', $request->date('expiry_from')))
            ->when($request->filled('expiry_to'), fn ($query) => $query->whereDate('expiry_date', '<=', $request->date('expiry_to')))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('disciplinary-cases.index', $this->formData($request) + compact('cases'));
    }

    public function create(Request $request): View
    {
        Gate::authorize('create', DisciplinaryCase::class);

        $case = new DisciplinaryCase([
            'province_id' => $request->user()->hasRole('HR Officer') ? $request->user()->province_id : null,
            'case_status_id' => $this->statusId('DRAFT'),
        ]);

        return view('disciplinary-cases.create', $this->formData($request) + compact('case'));
    }

    public function store(StoreDisciplinaryCaseRequest $request, ReferenceNumberService $referenceNumbers, ActivityLogger $activity): RedirectResponse
    {
        $case = DB::transaction(function () use ($request, $referenceNumbers) {
            $data = $this->caseData($request->validated());
            $data['reference_no'] = $referenceNumbers->generate('DC', 'disciplinary_cases');
            $data['case_status_id'] = $this->statusId('DRAFT');
            $data['created_by'] = $request->user()->id;
            $data['updated_by'] = $request->user()->id;

            if ($request->user()->hasRole('HR Officer')) {
                $data['province_id'] = $request->user()->province_id;
            }

            return DisciplinaryCase::create($data);
        });

        if ($request->hasFile('supporting_document')) {
            $this->storeInitialAttachment($request, $case, $activity);
        }

        $activity->log(
            'case_created',
            "{$request->user()->name} created disciplinary case {$case->reference_no}.",
            $case,
            user: $request->user(),
            request: $request,
        );

        return redirect()->route('disciplinary-cases.show', $case)->with('success', 'Disciplinary case created as draft.');
    }

    public function show(DisciplinaryCase $disciplinaryCase): View
    {
        Gate::authorize('view', $disciplinaryCase);

        $disciplinaryCase->load($this->caseRelations());
        $documentTypes = DocumentType::where('is_active', true)->orderBy('name')->get();

        return view('disciplinary-cases.show', ['case' => $disciplinaryCase, 'documentTypes' => $documentTypes]);
    }

    public function edit(Request $request, DisciplinaryCase $disciplinaryCase): View
    {
        Gate::authorize('update', $disciplinaryCase);

        $disciplinaryCase->load(['employee']);

        return view('disciplinary-cases.edit', $this->formData($request) + ['case' => $disciplinaryCase]);
    }

    public function update(UpdateDisciplinaryCaseRequest $request, DisciplinaryCase $disciplinaryCase, ActivityLogger $activity): RedirectResponse
    {
        $data = $this->caseData($request->validated());
        $data['updated_by'] = $request->user()->id;
        $data['case_status_id'] = $disciplinaryCase->case_status_id;

        if ($request->user()->hasRole('HR Officer')) {
            $data['province_id'] = $request->user()->province_id;
        }

        $disciplinaryCase->update($data);

        $activity->log(
            'case_updated',
            "{$request->user()->name} updated disciplinary case {$disciplinaryCase->reference_no}.",
            $disciplinaryCase,
            user: $request->user(),
            request: $request,
        );

        return redirect()->route('disciplinary-cases.show', $disciplinaryCase)->with('success', 'Disciplinary case updated successfully.');
    }

    public function submit(Request $request, DisciplinaryCase $disciplinaryCase, ActivityLogger $activity): RedirectResponse
    {
        Gate::authorize('submit', $disciplinaryCase);

        if (! $disciplinaryCase->hasStatusCode('DRAFT')) {
            return back()->with('error', 'Only draft cases can be submitted.');
        }

        $disciplinaryCase->update([
            'case_status_id' => $this->statusId('SUBMITTED'),
            'submitted_at' => now(),
            'updated_by' => $request->user()->id,
        ]);

        $activity->log(
            'case_submitted',
            "{$request->user()->name} submitted disciplinary case {$disciplinaryCase->reference_no}.",
            $disciplinaryCase,
            user: $request->user(),
            request: $request,
        );

        $this->notifyManagers(new DisciplinaryCaseSubmittedNotification($disciplinaryCase->fresh()));

        return redirect()->route('disciplinary-cases.show', $disciplinaryCase)->with('success', 'Disciplinary case submitted for approval.');
    }

    public function approve(Request $request, DisciplinaryCase $disciplinaryCase, ActivityLogger $activity): RedirectResponse
    {
        Gate::authorize('approve', $disciplinaryCase);

        if (! $disciplinaryCase->hasStatusCode('SUBMITTED')) {
            return back()->with('error', 'Only submitted cases can be approved.');
        }

        $disciplinaryCase->update([
            'case_status_id' => $this->statusId('ACTIVE'),
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
            'updated_by' => $request->user()->id,
        ]);

        $activity->log(
            'case_approved',
            "{$request->user()->name} approved disciplinary case {$disciplinaryCase->reference_no}.",
            $disciplinaryCase,
            user: $request->user(),
            request: $request,
        );

        if ($disciplinaryCase->createdBy) {
            $disciplinaryCase->createdBy->notify(new DisciplinaryCaseApprovedNotification($disciplinaryCase->fresh()));
        }

        return redirect()->route('disciplinary-cases.show', $disciplinaryCase)->with('success', 'Disciplinary case approved and activated.');
    }

    public function close(Request $request, DisciplinaryCase $disciplinaryCase, ActivityLogger $activity): RedirectResponse
    {
        Gate::authorize('close', $disciplinaryCase);

        if (! $disciplinaryCase->hasStatusCode('ACTIVE')) {
            return back()->with('error', 'Only active cases can be closed.');
        }

        $disciplinaryCase->update([
            'case_status_id' => $this->statusId('CLOSED'),
            'closed_at' => now(),
            'updated_by' => $request->user()->id,
        ]);

        $activity->log(
            'case_closed',
            "{$request->user()->name} closed disciplinary case {$disciplinaryCase->reference_no}.",
            $disciplinaryCase,
            user: $request->user(),
            request: $request,
        );

        return redirect()->route('disciplinary-cases.show', $disciplinaryCase)->with('success', 'Disciplinary case closed successfully.');
    }

    public function archive(Request $request, DisciplinaryCase $disciplinaryCase, ActivityLogger $activity): RedirectResponse
    {
        Gate::authorize('archive', $disciplinaryCase);

        $disciplinaryCase->update(['archived_by' => $request->user()->id]);
        $disciplinaryCase->delete();

        $activity->log(
            'case_archived',
            "{$request->user()->name} archived disciplinary case {$disciplinaryCase->reference_no}.",
            $disciplinaryCase,
            user: $request->user(),
            request: $request,
        );

        return redirect()->route('disciplinary-cases.index')->with('success', 'Disciplinary case archived successfully.');
    }

    public function archived(Request $request): View
    {
        Gate::authorize('viewAny', DisciplinaryCase::class);

        $cases = DisciplinaryCase::onlyTrashed()
            ->with(['employee', 'province', 'district', 'caseStatus', 'archivedBy'])
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

        return view('disciplinary-cases.archived', compact('cases'));
    }

    public function restore(Request $request, int $id, ActivityLogger $activity): RedirectResponse
    {
        $disciplinaryCase = DisciplinaryCase::withTrashed()->findOrFail($id);

        Gate::authorize('restore', $disciplinaryCase);

        $disciplinaryCase->restore();
        $disciplinaryCase->update([
            'archived_by' => null,
            'updated_by' => $request->user()->id,
        ]);

        $activity->log(
            'case_restored',
            "{$request->user()->name} restored disciplinary case {$disciplinaryCase->reference_no}.",
            $disciplinaryCase,
            user: $request->user(),
            request: $request,
        );

        return redirect()->route('disciplinary-cases.show', $disciplinaryCase)->with('success', 'Disciplinary case restored successfully.');
    }

    /**
     * @return array<string>
     */
    private function caseRelations(): array
    {
        return [
            'employee',
            'project',
            'province',
            'district',
            'facility',
            'offenceCategory',
            'penaltyType',
            'caseStatus',
            'createdBy',
            'updatedBy',
            'approvedBy',
            'archivedBy',
            'attachments.documentType',
            'attachments.uploadedBy',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(Request $request): array
    {
        $user = $request->user();

        return [
            'employees' => Employee::query()->visibleTo($user)->orderBy('last_name')->orderBy('first_name')->get(),
            'provinces' => Province::where('is_active', true)
                ->when($user->hasRole('HR Officer'), fn ($query) => $query->whereKey($user->province_id))
                ->orderBy('name')
                ->get(),
            'districts' => District::with('province')->where('is_active', true)->orderBy('name')->get(),
            'facilities' => Facility::with('district')->where('is_active', true)->orderBy('name')->get(),
            'projects' => Project::where('is_active', true)->orderBy('name')->get(),
            'offenceCategories' => OffenceCategory::where('is_active', true)->orderBy('name')->get(),
            'penaltyTypes' => PenaltyType::where('is_active', true)->orderBy('name')->get(),
            'caseStatuses' => CaseStatus::where('is_active', true)->orderBy('name')->get(),
            'documentTypes' => DocumentType::where('is_active', true)->orderBy('name')->get(),
            'draftStatusId' => $this->statusId('DRAFT'),
        ];
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function caseData(array $data): array
    {
        foreach (['project_id', 'facility_id', 'supervisor_name', 'offence_category_id', 'penalty_type_id', 'expiry_date', 'comment'] as $field) {
            if (array_key_exists($field, $data) && blank($data[$field])) {
                $data[$field] = null;
            }
        }

        return Arr::only($data, [
            'reference_no',
            'employee_id',
            'project_id',
            'province_id',
            'district_id',
            'facility_id',
            'supervisor_name',
            'nature_of_offence',
            'offence_category_id',
            'penalty_type_id',
            'case_status_id',
            'effective_date',
            'expiry_date',
            'comment',
            'submitted_at',
            'approved_by',
            'approved_at',
            'closed_at',
            'created_by',
            'updated_by',
            'archived_by',
        ]);
    }

    private function statusId(string $code): int
    {
        $code = strtoupper($code);
        $name = match ($code) {
            'DRAFT' => 'Draft',
            'SUBMITTED' => 'Submitted',
            'ACTIVE' => 'Active',
            'CLOSED' => 'Closed',
            'ARCHIVED' => 'Archived',
            default => ucfirst(strtolower($code)),
        };

        return CaseStatus::firstOrCreate(
            ['code' => $code],
            ['name' => $name, 'description' => null, 'is_active' => true],
        )->id;
    }

    private function notifyManagers(object $notification): void
    {
        $recipients = User::query()
            ->where('is_active', true)
            ->whereHas('role', fn ($query) => $query->whereIn('name', ['Admin', 'HR Manager']))
            ->get();

        if ($recipients->isNotEmpty()) {
            Notification::send($recipients, $notification);
        }
    }

    private function storeInitialAttachment(StoreDisciplinaryCaseRequest $request, DisciplinaryCase $case, ActivityLogger $activity): void
    {
        $file = $request->file('supporting_document');

        if (! $file) {
            return;
        }

        $path = $file->store("disciplinary-cases/{$case->id}", 'local');

        $attachment = $case->attachments()->create([
            'document_type_id' => null,
            'original_filename' => $file->getClientOriginalName(),
            'stored_filename' => basename($path),
            'file_path' => $path,
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize() ?: 0,
            'uploaded_by' => $request->user()->id,
        ]);

        $attachment->setRelation('attachable', $case);

        $activity->log(
            'attachment_uploaded',
            "{$request->user()->name} uploaded {$attachment->original_filename} to {$case->reference_no}.",
            $attachment,
            user: $request->user(),
            request: $request,
        );
    }
}
