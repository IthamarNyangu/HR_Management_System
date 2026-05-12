<?php

namespace App\Http\Controllers;

use App\Http\Requests\UploadStaffRelocationAttachmentRequest;
use App\Models\Attachment;
use App\Models\StaffRelocation;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StaffRelocationAttachmentController extends Controller
{
    public function store(UploadStaffRelocationAttachmentRequest $request, StaffRelocation $staffRelocation, ActivityLogger $activity): RedirectResponse
    {
        $file = $request->file('document');
        $staffRelocation->loadMissing(['fromProvince', 'fromFacility', 'toProvince', 'toFacility']);
        $path = $file->store("staff-relocations/{$staffRelocation->id}", 'local');

        $attachment = $staffRelocation->attachments()->create([
            'document_type_id' => null,
            'original_filename' => $file->getClientOriginalName(),
            'stored_filename' => basename($path),
            'file_path' => $path,
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize() ?: 0,
            'uploaded_by' => $request->user()->id,
        ]);

        $attachment->setRelation('attachable', $staffRelocation);

        $activity->log(
            'relocation_attachment_uploaded',
            "{$request->user()->name} uploaded {$attachment->original_filename} to {$staffRelocation->reference_no}.",
            $attachment,
            user: $request->user(),
            request: $request,
        );

        return redirect()->route('staff-relocations.show', $staffRelocation)->with('success', 'Relocation document uploaded successfully.');
    }

    public function download(Request $request, StaffRelocation $staffRelocation, Attachment $attachment, ActivityLogger $activity): StreamedResponse
    {
        $this->ensureAttachmentBelongsToRelocation($staffRelocation, $attachment);

        Gate::authorize('view', $staffRelocation);

        abort_unless(Storage::disk('local')->exists($attachment->file_path), 404);

        $attachment->setRelation('attachable', $staffRelocation);
        $staffRelocation->loadMissing(['fromProvince', 'fromFacility', 'toProvince', 'toFacility']);

        $activity->log(
            'relocation_attachment_downloaded',
            "{$request->user()->name} downloaded {$attachment->original_filename} from {$staffRelocation->reference_no}.",
            $attachment,
            user: $request->user(),
            request: $request,
        );

        return Storage::disk('local')->download($attachment->file_path, $attachment->original_filename);
    }

    public function delete(Request $request, StaffRelocation $staffRelocation, Attachment $attachment, ActivityLogger $activity): RedirectResponse
    {
        $this->ensureAttachmentBelongsToRelocation($staffRelocation, $attachment);

        Gate::authorize('deleteAttachment', $staffRelocation);

        $filename = $attachment->original_filename;
        $attachment->setRelation('attachable', $staffRelocation);
        $staffRelocation->loadMissing(['fromProvince', 'fromFacility', 'toProvince', 'toFacility']);

        Storage::disk('local')->delete($attachment->file_path);
        $attachment->delete();

        $activity->log(
            'relocation_attachment_deleted',
            "{$request->user()->name} deleted {$filename} from {$staffRelocation->reference_no}.",
            $attachment,
            ['filename' => $filename],
            user: $request->user(),
            request: $request,
        );

        return redirect()->route('staff-relocations.show', $staffRelocation)->with('success', 'Relocation document deleted successfully.');
    }

    private function ensureAttachmentBelongsToRelocation(StaffRelocation $staffRelocation, Attachment $attachment): void
    {
        abort_unless(
            $attachment->attachable_type === StaffRelocation::class && (int) $attachment->attachable_id === (int) $staffRelocation->id,
            404,
        );
    }
}
