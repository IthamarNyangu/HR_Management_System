<?php

namespace App\Http\Controllers;

use App\Http\Requests\UploadStaffPromotionAttachmentRequest;
use App\Models\Attachment;
use App\Models\StaffPromotion;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StaffPromotionAttachmentController extends Controller
{
    public function store(UploadStaffPromotionAttachmentRequest $request, StaffPromotion $staffPromotion, ActivityLogger $activity): RedirectResponse
    {
        $file = $request->file('document');
        $path = $file->store("staff-promotions/{$staffPromotion->id}", 'local');

        $attachment = $staffPromotion->attachments()->create([
            'document_type_id' => null,
            'original_filename' => $file->getClientOriginalName(),
            'stored_filename' => basename($path),
            'file_path' => $path,
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize() ?: 0,
            'uploaded_by' => $request->user()->id,
        ]);

        $attachment->setRelation('attachable', $staffPromotion);

        $activity->log(
            'promotion_attachment_uploaded',
            "{$request->user()->name} uploaded {$attachment->original_filename} to {$staffPromotion->reference_no}.",
            $attachment,
            user: $request->user(),
            request: $request,
        );

        return redirect()->route('staff-promotions.show', $staffPromotion)->with('success', 'Promotion document uploaded successfully.');
    }

    public function download(Request $request, StaffPromotion $staffPromotion, Attachment $attachment, ActivityLogger $activity): StreamedResponse
    {
        $this->ensureAttachmentBelongsToPromotion($staffPromotion, $attachment);

        Gate::authorize('view', $staffPromotion);

        abort_unless(Storage::disk('local')->exists($attachment->file_path), 404);

        $attachment->setRelation('attachable', $staffPromotion);

        $activity->log(
            'promotion_attachment_downloaded',
            "{$request->user()->name} downloaded {$attachment->original_filename} from {$staffPromotion->reference_no}.",
            $attachment,
            user: $request->user(),
            request: $request,
        );

        return Storage::disk('local')->download($attachment->file_path, $attachment->original_filename);
    }

    public function delete(Request $request, StaffPromotion $staffPromotion, Attachment $attachment, ActivityLogger $activity): RedirectResponse
    {
        $this->ensureAttachmentBelongsToPromotion($staffPromotion, $attachment);

        Gate::authorize('deleteAttachment', $staffPromotion);

        $filename = $attachment->original_filename;
        $attachment->setRelation('attachable', $staffPromotion);

        Storage::disk('local')->delete($attachment->file_path);
        $attachment->delete();

        $activity->log(
            'promotion_attachment_deleted',
            "{$request->user()->name} deleted {$filename} from {$staffPromotion->reference_no}.",
            $attachment,
            ['filename' => $filename],
            user: $request->user(),
            request: $request,
        );

        return redirect()->route('staff-promotions.show', $staffPromotion)->with('success', 'Promotion document deleted successfully.');
    }

    private function ensureAttachmentBelongsToPromotion(StaffPromotion $staffPromotion, Attachment $attachment): void
    {
        abort_unless(
            $attachment->attachable_type === StaffPromotion::class && (int) $attachment->attachable_id === (int) $staffPromotion->id,
            404,
        );
    }
}
