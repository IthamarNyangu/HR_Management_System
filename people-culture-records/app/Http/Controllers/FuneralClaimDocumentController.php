<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateFuneralClaimDocumentRequest;
use App\Http\Requests\UploadFuneralClaimDocumentRequest;
use App\Models\Attachment;
use App\Models\FuneralClaim;
use App\Models\FuneralClaimDocument;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FuneralClaimDocumentController extends Controller
{
    public function add(Request $request, FuneralClaim $funeralClaim, ActivityLogger $activity): RedirectResponse
    {
        Gate::authorize('update', $funeralClaim);
        $data = $request->validate(['custom_label' => ['required', 'string', 'max:255'], 'is_required' => ['nullable', 'boolean']]);
        $document = $funeralClaim->documents()->create(['document_type' => 'additional_supporting_document', 'custom_label' => $data['custom_label'], 'is_required' => $request->boolean('is_required'), 'status' => FuneralClaimDocument::PENDING]);
        $activity->log('funeral_claim_document_added', "{$request->user()->name} added {$document->label} to {$funeralClaim->reference_no}.", $funeralClaim, user: $request->user(), request: $request);

        return back()->with('success', 'Additional document requirement added.');
    }

    public function update(UpdateFuneralClaimDocumentRequest $request, FuneralClaim $funeralClaim, FuneralClaimDocument $document, ActivityLogger $activity): RedirectResponse
    {
        $this->ensureOwnership($funeralClaim, $document);
        $document->update($request->validated() + ['status_updated_by' => $request->user()->id, 'status_updated_at' => now()]);
        $activity->log('funeral_claim_document_status_updated', "{$request->user()->name} updated {$document->label} for {$funeralClaim->reference_no}.", $funeralClaim, ['document' => $document->label, 'status' => $document->status], user: $request->user(), request: $request);

        return back()->with('success', 'Document status updated.');
    }

    public function upload(UploadFuneralClaimDocumentRequest $request, FuneralClaim $funeralClaim, FuneralClaimDocument $document, ActivityLogger $activity): RedirectResponse
    {
        $this->ensureOwnership($funeralClaim, $document);
        $file = $request->file('document');
        $path = $file->store("funeral-claims/{$funeralClaim->id}/{$document->id}", 'local');
        $attachment = $document->attachments()->create(['original_filename' => $file->getClientOriginalName(), 'stored_filename' => basename($path), 'file_path' => $path, 'mime_type' => $file->getMimeType(), 'file_size' => $file->getSize() ?: 0, 'uploaded_by' => $request->user()->id]);
        if ($document->status === FuneralClaimDocument::PENDING) {
            $document->update(['status' => FuneralClaimDocument::RECEIVED, 'status_updated_by' => $request->user()->id, 'status_updated_at' => now()]);
        }
        $activity->log('funeral_claim_document_uploaded', "{$request->user()->name} uploaded {$attachment->original_filename} to {$funeralClaim->reference_no}.", $funeralClaim, ['document' => $document->label], user: $request->user(), request: $request);

        return back()->with('success', 'Document uploaded securely.');
    }

    public function view(Request $request, FuneralClaim $funeralClaim, FuneralClaimDocument $document, Attachment $attachment): StreamedResponse
    {
        $this->ensureFile($funeralClaim, $document, $attachment);
        Gate::authorize('view', $funeralClaim);

        return Storage::disk('local')->response($attachment->file_path, $attachment->original_filename, ['Content-Type' => $attachment->mime_type, 'Content-Disposition' => 'inline; filename="'.$attachment->original_filename.'"']);
    }

    public function download(Request $request, FuneralClaim $funeralClaim, FuneralClaimDocument $document, Attachment $attachment): StreamedResponse
    {
        $this->ensureFile($funeralClaim, $document, $attachment);
        Gate::authorize('view', $funeralClaim);

        return Storage::disk('local')->download($attachment->file_path, $attachment->original_filename);
    }

    private function ensureOwnership(FuneralClaim $claim, FuneralClaimDocument $document): void
    {
        abort_unless($document->funeral_claim_id === $claim->id, 404);
    }

    private function ensureFile(FuneralClaim $claim, FuneralClaimDocument $document, Attachment $attachment): void
    {
        $this->ensureOwnership($claim, $document);
        abort_unless($attachment->attachable_type === FuneralClaimDocument::class && $attachment->attachable_id === $document->id && Storage::disk('local')->exists($attachment->file_path), 404);
    }
}
