<?php

namespace App\Http\Controllers;

use App\Http\Requests\DecideFuneralClaimRequest;
use App\Http\Requests\RecordFuneralClaimPaymentRequest;
use App\Http\Requests\ReopenFuneralClaimRequest;
use App\Http\Requests\UpdateFuneralClaimProcessingRequest;
use App\Models\FuneralClaim;
use App\Models\User;
use App\Notifications\FuneralClaimDecisionNotification;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

class FuneralClaimWorkflowController extends Controller
{
    public function updateProcessing(UpdateFuneralClaimProcessingRequest $request, FuneralClaim $funeralClaim, ActivityLogger $activity): RedirectResponse
    {
        if (in_array($funeralClaim->status, [FuneralClaim::STATUS_REJECTED, FuneralClaim::STATUS_PAID], true)) {
            throw ValidationException::withMessages(['workflow' => 'Reopen this claim before changing its processing details.']);
        }

        $data = $request->validated();
        $data['grant_consent_confirmed'] = $request->boolean('grant_consent_confirmed');
        $data['banking_details_verified'] = $request->boolean('banking_details_verified');
        $data['banking_details_verified_by'] = $data['banking_details_verified'] ? $request->user()->id : null;
        $data['banking_details_verified_at'] = $data['banking_details_verified'] ? now() : null;
        $data['updated_by'] = $request->user()->id;
        $funeralClaim->update($data);
        $funeralClaim->workflowEvents()->create(['event_type' => 'processing_updated', 'performed_by' => $request->user()->id, 'comment' => 'Claim processing details updated.', 'properties' => ['meeting_status' => $funeralClaim->family_meeting_status, 'payreq_status' => $funeralClaim->payreq_status]]);
        $activity->log('funeral_claim_processing_updated', "{$request->user()->name} updated processing details for {$funeralClaim->reference_no}.", $funeralClaim, user: $request->user(), request: $request);

        return back()->with('success', 'Review and payment preparation details updated.');
    }

    public function decide(DecideFuneralClaimRequest $request, FuneralClaim $funeralClaim, ActivityLogger $activity): RedirectResponse
    {
        if ($funeralClaim->status !== FuneralClaim::STATUS_READY_FOR_APPROVAL) {
            throw ValidationException::withMessages(['decision' => 'The claim must be ready for approval before a decision is recorded.']);
        }

        $decision = $request->validated('decision');
        if ($decision === 'approve') {
            $this->ensureApprovalPrerequisites($funeralClaim);
        }
        $newStatus = $decision === 'approve' ? FuneralClaim::STATUS_APPROVED : FuneralClaim::STATUS_REJECTED;
        $amount = $decision === 'approve' ? ($request->validated('grant_amount_approved') ?? $funeralClaim->eligible_amount) : null;

        DB::transaction(function () use ($request, $funeralClaim, $decision, $newStatus, $amount) {
            $funeralClaim->update(['status' => $newStatus, 'approved_by' => $decision === 'approve' ? $request->user()->id : null, 'approved_at' => $decision === 'approve' ? now() : null, 'approval_comment' => $request->validated('comment'), 'grant_amount_approved' => $amount, 'last_status_changed_by' => $request->user()->id, 'last_status_changed_at' => now(), 'updated_by' => $request->user()->id]);
            $funeralClaim->statusHistories()->create(['from_status' => FuneralClaim::STATUS_READY_FOR_APPROVAL, 'to_status' => $newStatus, 'changed_by' => $request->user()->id, 'comment' => $request->validated('comment')]);
            $funeralClaim->workflowEvents()->create(['event_type' => $decision === 'approve' ? 'claim_approved' : 'claim_rejected', 'performed_by' => $request->user()->id, 'comment' => $request->validated('comment'), 'properties' => ['amount' => $amount]]);
        });
        $decisionVerb = $decision === 'approve' ? 'approved' : 'rejected';
        $activity->log($decision === 'approve' ? 'funeral_claim_approved' : 'funeral_claim_rejected', "{$request->user()->name} {$decisionVerb} {$funeralClaim->reference_no}.", $funeralClaim, ['grant_amount_approved' => $amount], user: $request->user(), request: $request);
        $this->notifyClaimHandlers($funeralClaim, $newStatus);

        return back()->with('success', $decision === 'approve' ? 'Claim approved successfully.' : 'Claim rejected.');
    }

    public function startPayment(FuneralClaim $funeralClaim, ActivityLogger $activity): RedirectResponse
    {
        request()->user()->can('update', $funeralClaim) || abort(403);
        if ($funeralClaim->status !== FuneralClaim::STATUS_APPROVED) {
            throw ValidationException::withMessages(['payment' => 'Only an approved claim can enter payment processing.']);
        }
        $this->transition($funeralClaim, FuneralClaim::STATUS_PAYMENT_PROCESSING, 'Payment processing started.', request()->user()->id);
        $activity->log('funeral_claim_payment_processing_started', request()->user()->name." started payment processing for {$funeralClaim->reference_no}.", $funeralClaim, user: request()->user(), request: request());

        return back()->with('success', 'Claim moved to payment processing.');
    }

    public function recordPayment(RecordFuneralClaimPaymentRequest $request, FuneralClaim $funeralClaim, ActivityLogger $activity): RedirectResponse
    {
        if ($funeralClaim->status !== FuneralClaim::STATUS_PAYMENT_PROCESSING) {
            throw ValidationException::withMessages(['payment' => 'Start payment processing before recording payment.']);
        }
        DB::transaction(function () use ($request, $funeralClaim) {
            $funeralClaim->update($request->validated() + ['payment_status' => 'paid', 'status' => FuneralClaim::STATUS_PAID, 'last_status_changed_by' => $request->user()->id, 'last_status_changed_at' => now(), 'updated_by' => $request->user()->id]);
            $funeralClaim->statusHistories()->create(['from_status' => FuneralClaim::STATUS_PAYMENT_PROCESSING, 'to_status' => FuneralClaim::STATUS_PAID, 'changed_by' => $request->user()->id, 'comment' => 'Funeral Grant payment recorded.']);
            $funeralClaim->workflowEvents()->create(['event_type' => 'payment_recorded', 'performed_by' => $request->user()->id, 'comment' => 'Funeral Grant payment recorded.', 'properties' => ['payment_date' => $request->validated('payment_date'), 'payment_reference' => $request->validated('payment_reference')]]);
        });
        $activity->log('funeral_claim_payment_recorded', "{$request->user()->name} recorded payment for {$funeralClaim->reference_no}.", $funeralClaim, ['payment_reference' => $funeralClaim->payment_reference], user: $request->user(), request: $request);

        return back()->with('success', 'Funeral Grant payment recorded.');
    }

    public function reopen(ReopenFuneralClaimRequest $request, FuneralClaim $funeralClaim, ActivityLogger $activity): RedirectResponse
    {
        if (! in_array($funeralClaim->status, [FuneralClaim::STATUS_REJECTED, FuneralClaim::STATUS_INELIGIBLE], true)) {
            throw ValidationException::withMessages(['reason' => 'Only rejected or ineligible claims can be reopened in this stage.']);
        }
        $oldStatus = $funeralClaim->status;
        DB::transaction(function () use ($request, $funeralClaim, $oldStatus) {
            $funeralClaim->update(['status' => FuneralClaim::STATUS_UNDER_VERIFICATION, 'reopened_by' => $request->user()->id, 'reopened_at' => now(), 'reopen_reason' => $request->validated('reason'), 'last_status_changed_by' => $request->user()->id, 'last_status_changed_at' => now(), 'updated_by' => $request->user()->id]);
            $funeralClaim->statusHistories()->create(['from_status' => $oldStatus, 'to_status' => FuneralClaim::STATUS_UNDER_VERIFICATION, 'changed_by' => $request->user()->id, 'comment' => $request->validated('reason')]);
            $funeralClaim->workflowEvents()->create(['event_type' => 'claim_reopened', 'performed_by' => $request->user()->id, 'comment' => $request->validated('reason')]);
        });
        $activity->log('funeral_claim_reopened', "{$request->user()->name} reopened {$funeralClaim->reference_no}.", $funeralClaim, user: $request->user(), request: $request);

        return back()->with('success', 'Claim reopened for verification.');
    }

    private function ensureApprovalPrerequisites(FuneralClaim $claim): void
    {
        if ($claim->family_meeting_required && $claim->family_meeting_status !== 'completed') {
            throw ValidationException::withMessages(['decision' => 'Complete and record the required family meeting first.']);
        }
        if (! $claim->grant_consent_confirmed) {
            throw ValidationException::withMessages(['decision' => 'Confirm the signed Funeral Grant consent first.']);
        }
        if (! $claim->banking_details_verified) {
            throw ValidationException::withMessages(['decision' => 'Verify the beneficiary banking details first.']);
        }
        if ($claim->payreq_status !== 'approved') {
            throw ValidationException::withMessages(['decision' => 'Record the externally approved PayReq first.']);
        }
    }

    private function transition(FuneralClaim $claim, string $status, string $comment, int $userId): void
    {
        $old = $claim->status;
        DB::transaction(function () use ($claim, $status, $comment, $userId, $old) {
            $claim->update(['status' => $status, 'last_status_changed_by' => $userId, 'last_status_changed_at' => now(), 'updated_by' => $userId]);
            $claim->statusHistories()->create(['from_status' => $old, 'to_status' => $status, 'changed_by' => $userId, 'comment' => $comment]);
            $claim->workflowEvents()->create(['event_type' => $status, 'performed_by' => $userId, 'comment' => $comment]);
        });
    }

    private function notifyClaimHandlers(FuneralClaim $claim, string $decision): void
    {
        $users = User::where('is_active', true)->where(function ($query) use ($claim) {
            $query->whereKey($claim->created_by)->orWhereKey($claim->assigned_to);
        })->get();
        Notification::send($users, new FuneralClaimDecisionNotification($claim, $decision));
    }
}
