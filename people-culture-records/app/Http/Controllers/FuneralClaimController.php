<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreFuneralClaimRequest;
use App\Http\Requests\UpdateFuneralClaimRequest;
use App\Http\Requests\UpdateFuneralClaimStatusRequest;
use App\Models\Employee;
use App\Models\EmployeeDependant;
use App\Models\FuneralClaim;
use App\Models\FuneralClaimDocument;
use App\Models\Province;
use App\Models\User;
use App\Notifications\FuneralClaimApprovalRequiredNotification;
use App\Services\ActivityLogger;
use App\Services\FuneralClaimEligibilityService;
use App\Services\ReferenceNumberService;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

class FuneralClaimController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', FuneralClaim::class);
        $claims = FuneralClaim::query()->visibleTo($request->user())->with(['employee', 'province'])
            ->when($request->filled('search'), fn ($q) => $q->where(fn ($inner) => $inner->where('reference_no', 'like', '%'.$request->search.'%')->orWhere('deceased_name', 'like', '%'.$request->search.'%')->orWhereHas('employee', fn ($employee) => $employee->where('first_name', 'like', '%'.$request->search.'%')->orWhere('last_name', 'like', '%'.$request->search.'%')->orWhere('employee_no', 'like', '%'.$request->search.'%'))))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('deceased_category'), fn ($q) => $q->where('deceased_category', $request->deceased_category))
            ->when($request->filled('province_id'), fn ($q) => $q->where('province_id', $request->province_id))
            ->latest('notification_date')->latest('id')->paginate(10)->withQueryString();

        return view('funeral-claims.index', ['claims' => $claims, 'statuses' => FuneralClaim::statuses(), 'categories' => FuneralClaim::categories(), 'provinces' => Province::where('is_active', true)->orderBy('name')->get()]);
    }

    public function create(Request $request): View
    {
        Gate::authorize('create', FuneralClaim::class);

        return view('funeral-claims.create', ['claim' => new FuneralClaim(['notification_date' => today(), 'date_of_death' => today()])] + $this->formData($request));
    }

    public function store(StoreFuneralClaimRequest $request, FuneralClaimEligibilityService $eligibility, ReferenceNumberService $references, ActivityLogger $activity): RedirectResponse
    {
        $claim = DB::transaction(function () use ($request, $eligibility, $references) {
            $this->preventConcurrentDuplicate($request->validated());
            $data = $this->claimData($request->validated(), $eligibility);
            $data += ['reference_no' => $references->generate('FCL', 'funeral_claims'), 'status' => FuneralClaim::STATUS_AWAITING_DOCUMENTS, 'last_status_changed_by' => $request->user()->id, 'last_status_changed_at' => now(), 'created_by' => $request->user()->id, 'updated_by' => $request->user()->id];
            $claim = FuneralClaim::create($data);
            $this->createDocumentChecklist($claim);
            $claim->statusHistories()->create(['to_status' => $claim->status, 'changed_by' => $request->user()->id, 'comment' => 'Claim registered.']);

            return $claim;
        });
        $activity->log('funeral_claim_created', "{$request->user()->name} registered funeral claim {$claim->reference_no}.", $claim, user: $request->user(), request: $request);

        return redirect()->route('funeral-claims.show', $claim)->with('success', 'Funeral claim registered successfully.');
    }

    public function show(FuneralClaim $funeralClaim): View
    {
        Gate::authorize('view', $funeralClaim);
        $funeralClaim->load(['employee', 'dependant', 'province', 'benefitRule', 'assignedTo', 'approvedBy', 'bankingDetailsVerifiedBy', 'documents.attachments.uploadedBy', 'documents.statusUpdatedBy', 'statusHistories.changedBy', 'workflowEvents.performedBy']);

        return view('funeral-claims.show', ['claim' => $funeralClaim, 'statuses' => FuneralClaim::statuses(), 'reviewStatuses' => FuneralClaim::reviewStatuses(), 'documentStatuses' => FuneralClaimDocument::statuses()]);
    }

    public function edit(Request $request, FuneralClaim $funeralClaim): View
    {
        Gate::authorize('update', $funeralClaim);

        return view('funeral-claims.edit', ['claim' => $funeralClaim] + $this->formData($request));
    }

    public function update(UpdateFuneralClaimRequest $request, FuneralClaim $funeralClaim, FuneralClaimEligibilityService $eligibility, ActivityLogger $activity): RedirectResponse
    {
        $data = $this->claimData($request->validated(), $eligibility);
        if ((bool) $data['family_meeting_required'] === $funeralClaim->family_meeting_required) {
            unset($data['family_meeting_status']);
        }
        $data['updated_by'] = $request->user()->id;
        $funeralClaim->update($data);
        $activity->log('funeral_claim_updated', "{$request->user()->name} updated {$funeralClaim->reference_no}.", $funeralClaim, user: $request->user(), request: $request);

        return redirect()->route('funeral-claims.show', $funeralClaim)->with('success', 'Funeral claim updated and eligibility recalculated.');
    }

    public function updateStatus(UpdateFuneralClaimStatusRequest $request, FuneralClaim $funeralClaim, ActivityLogger $activity): RedirectResponse
    {
        if (! array_key_exists($funeralClaim->status, FuneralClaim::reviewStatuses())) {
            throw ValidationException::withMessages(['status' => 'Use the available workflow action to change this claim status.']);
        }
        $newStatus = $request->validated('status');
        if ($newStatus === FuneralClaim::STATUS_READY_FOR_APPROVAL) {
            $managerOverride = $funeralClaim->eligibility_status === FuneralClaim::REQUIRES_REVIEW
                && $request->user()->can('approve', $funeralClaim);
            if ($funeralClaim->eligibility_status !== FuneralClaim::ELIGIBLE && ! $managerOverride) {
                throw ValidationException::withMessages(['status' => 'Only an eligible claim can be marked ready for approval.']);
            }
            if ($funeralClaim->documents()->where('is_required', true)->where('status', '!=', FuneralClaimDocument::APPROVED_CLOSED)->exists()) {
                throw ValidationException::withMessages(['status' => 'Complete all required document checks before marking the claim ready for approval.']);
            }
        }
        $oldStatus = $funeralClaim->status;
        if ($oldStatus !== $newStatus) {
            DB::transaction(function () use ($funeralClaim, $request, $oldStatus, $newStatus) {
                $funeralClaim->update(['status' => $newStatus, 'last_status_changed_by' => $request->user()->id, 'last_status_changed_at' => now(), 'updated_by' => $request->user()->id]);
                $funeralClaim->statusHistories()->create(['from_status' => $oldStatus, 'to_status' => $newStatus, 'changed_by' => $request->user()->id, 'comment' => $request->validated('comment')]);
            });
            $activity->log('funeral_claim_status_changed', "{$request->user()->name} changed {$funeralClaim->reference_no} from {$oldStatus} to {$newStatus}.", $funeralClaim, ['old_status' => $oldStatus, 'new_status' => $newStatus], user: $request->user(), request: $request);
            if ($newStatus === FuneralClaim::STATUS_READY_FOR_APPROVAL) {
                $managers = User::where('is_active', true)->whereHas('role', fn ($query) => $query->whereIn('name', ['Admin', 'HR Manager']))->get();
                Notification::send($managers, new FuneralClaimApprovalRequiredNotification($funeralClaim));
            }
        }

        return back()->with('success', 'Claim status updated.');
    }

    private function claimData(array $data, FuneralClaimEligibilityService $eligibility): array
    {
        $employee = Employee::findOrFail($data['employee_id']);
        $dependant = isset($data['employee_dependant_id']) ? EmployeeDependant::find($data['employee_dependant_id']) : null;
        $deathDate = Carbon::parse($data['date_of_death']);
        $manualDob = ! empty($data['deceased_date_of_birth']) ? Carbon::parse($data['deceased_date_of_birth']) : null;
        $calculation = $eligibility->calculate($employee, $data['deceased_category'], $deathDate, $dependant, $data['deceased_name'] ?? null, $manualDob, isset($data['gestational_age_weeks']) ? (float) $data['gestational_age_weeks'] : null);

        $familyMeetingRequired = $calculation['claimant_context'] === 'former_employee'
            && $data['deceased_category'] !== FuneralClaim::CATEGORY_EMPLOYEE;

        return array_merge($data, $calculation, [
            'province_id' => $employee->province_id,
            'family_meeting_required' => $familyMeetingRequired,
            'family_meeting_status' => $familyMeetingRequired ? 'required' : 'not_required',
        ]);
    }

    private function createDocumentChecklist(FuneralClaim $claim): void
    {
        $documents = [['document_type' => 'death_certificate', 'is_required' => true], ['document_type' => 'certified_nrc', 'is_required' => false]];
        if ($claim->deceased_category === FuneralClaim::CATEGORY_CHILD) {
            $documents[] = ['document_type' => 'birth_confirmation', 'is_required' => true];
        }
        if ($claim->deceased_category === FuneralClaim::CATEGORY_SPOUSE) {
            $documents[] = ['document_type' => 'marriage_certificate', 'is_required' => false];
        }
        $claim->documents()->createMany(array_map(fn ($document) => $document + ['status' => FuneralClaimDocument::PENDING], $documents));
    }

    private function formData(Request $request): array
    {
        $employees = Employee::query()
            ->visibleTo($request->user())
            ->with(['dependants' => fn ($q) => $q->where('is_active', true)->with('latestFuneralClaim')])
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();

        return ['employees' => $employees, 'categories' => FuneralClaim::categories(), 'assignees' => User::where('is_active', true)->whereHas('role', fn ($q) => $q->whereIn('name', ['Admin', 'HR Manager', 'HR Officer']))->orderBy('name')->get()];
    }

    private function preventConcurrentDuplicate(array $data): void
    {
        if (! empty($data['employee_dependant_id'])) {
            EmployeeDependant::query()->whereKey($data['employee_dependant_id'])->lockForUpdate()->firstOrFail();
            $existingClaim = FuneralClaim::query()
                ->where('employee_dependant_id', $data['employee_dependant_id'])
                ->latest('id')
                ->first();

            if ($existingClaim) {
                throw ValidationException::withMessages([
                    'employee_dependant_id' => "A funeral claim already exists for this dependant ({$existingClaim->reference_no}). Open the existing claim instead.",
                ]);
            }

            return;
        }

        if (($data['deceased_category'] ?? null) === FuneralClaim::CATEGORY_EMPLOYEE || empty($data['deceased_name'])) {
            return;
        }

        $existingClaim = FuneralClaim::query()
            ->where('employee_id', $data['employee_id'])
            ->where('deceased_category', $data['deceased_category'])
            ->where('deceased_name', trim((string) $data['deceased_name']))
            ->whereDate('date_of_death', $data['date_of_death'])
            ->latest('id')
            ->first();

        if ($existingClaim) {
            throw ValidationException::withMessages([
                'deceased_name' => "A matching funeral claim already exists ({$existingClaim->reference_no}). Open the existing claim instead.",
            ]);
        }
    }
}
