<?php

namespace App\Http\Controllers;

use App\Http\Requests\UploadDisciplinaryCaseAttachmentRequest;
use App\Models\Attachment;
use App\Models\DisciplinaryCase;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DisciplinaryCaseAttachmentController extends Controller
{
    public function store(UploadDisciplinaryCaseAttachmentRequest $request, DisciplinaryCase $disciplinaryCase, ActivityLogger $activity): RedirectResponse
    {
        $file = $request->file('document');
        $path = $file->store("disciplinary-cases/{$disciplinaryCase->id}", 'local');

        $attachment = $disciplinaryCase->attachments()->create([
            'document_type_id' => $request->input('document_type_id') ?: null,
            'original_filename' => $file->getClientOriginalName(),
            'stored_filename' => basename($path),
            'file_path' => $path,
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize() ?: 0,
            'uploaded_by' => $request->user()->id,
        ]);

        $attachment->setRelation('attachable', $disciplinaryCase);

        $activity->log(
            'attachment_uploaded',
            "{$request->user()->name} uploaded {$attachment->original_filename} to {$disciplinaryCase->reference_no}.",
            $attachment,
            user: $request->user(),
            request: $request,
        );

        return redirect()->route('disciplinary-cases.show', $disciplinaryCase)->with('success', 'Supporting document uploaded successfully.');
    }

    public function download(Request $request, DisciplinaryCase $disciplinaryCase, Attachment $attachment, ActivityLogger $activity): StreamedResponse
    {
        $this->ensureAttachmentBelongsToCase($disciplinaryCase, $attachment);

        Gate::authorize('view', $disciplinaryCase);

        abort_unless(Storage::disk('local')->exists($attachment->file_path), 404);

        $attachment->setRelation('attachable', $disciplinaryCase);

        $activity->log(
            'attachment_downloaded',
            "{$request->user()->name} downloaded {$attachment->original_filename} from {$disciplinaryCase->reference_no}.",
            $attachment,
            user: $request->user(),
            request: $request,
        );

        return Storage::disk('local')->download($attachment->file_path, $attachment->original_filename);
    }

    public function delete(Request $request, DisciplinaryCase $disciplinaryCase, Attachment $attachment, ActivityLogger $activity): RedirectResponse
    {
        $this->ensureAttachmentBelongsToCase($disciplinaryCase, $attachment);

        Gate::authorize('deleteAttachment', $disciplinaryCase);

        $filename = $attachment->original_filename;
        $attachment->setRelation('attachable', $disciplinaryCase);

        Storage::disk('local')->delete($attachment->file_path);
        $attachment->delete();

        $activity->log(
            'attachment_deleted',
            "{$request->user()->name} deleted {$filename} from {$disciplinaryCase->reference_no}.",
            $attachment,
            ['filename' => $filename],
            user: $request->user(),
            request: $request,
        );

        return redirect()->route('disciplinary-cases.show', $disciplinaryCase)->with('success', 'Supporting document deleted successfully.');
    }

    private function ensureAttachmentBelongsToCase(DisciplinaryCase $disciplinaryCase, Attachment $attachment): void
    {
        abort_unless(
            $attachment->attachable_type === DisciplinaryCase::class && (int) $attachment->attachable_id === (int) $disciplinaryCase->id,
            404,
        );
    }
}
