<?php

namespace App\Http\Controllers;

use App\Http\Requests\UploadTemporaryAppointmentAttachmentRequest;
use App\Models\Attachment;
use App\Models\TemporaryAppointment;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TemporaryAppointmentAttachmentController extends Controller
{
    public function store(UploadTemporaryAppointmentAttachmentRequest $request, TemporaryAppointment $temporaryAppointment, ActivityLogger $activity): RedirectResponse
    {
        $file = $request->file('document');
        $path = $file->store("temporary-appointments/{$temporaryAppointment->id}", 'local');

        $attachment = $temporaryAppointment->attachments()->create([
            'document_type_id' => null,
            'original_filename' => $file->getClientOriginalName(),
            'stored_filename' => basename($path),
            'file_path' => $path,
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize() ?: 0,
            'uploaded_by' => $request->user()->id,
        ]);

        $attachment->setRelation('attachable', $temporaryAppointment);

        $activity->log(
            'temporary_appointment_attachment_uploaded',
            "{$request->user()->name} uploaded {$attachment->original_filename} to {$temporaryAppointment->reference_no}.",
            $attachment,
            user: $request->user(),
            request: $request,
        );

        return redirect()->route('temporary-appointments.show', $temporaryAppointment)->with('success', 'Temporary appointment document uploaded successfully.');
    }

    public function download(Request $request, TemporaryAppointment $temporaryAppointment, Attachment $attachment, ActivityLogger $activity): StreamedResponse
    {
        $this->ensureAttachmentBelongsToAppointment($temporaryAppointment, $attachment);

        Gate::authorize('view', $temporaryAppointment);

        abort_unless(Storage::disk('local')->exists($attachment->file_path), 404);

        $attachment->setRelation('attachable', $temporaryAppointment);

        $activity->log(
            'temporary_appointment_attachment_downloaded',
            "{$request->user()->name} downloaded {$attachment->original_filename} from {$temporaryAppointment->reference_no}.",
            $attachment,
            user: $request->user(),
            request: $request,
        );

        return Storage::disk('local')->download($attachment->file_path, $attachment->original_filename);
    }

    public function delete(Request $request, TemporaryAppointment $temporaryAppointment, Attachment $attachment, ActivityLogger $activity): RedirectResponse
    {
        $this->ensureAttachmentBelongsToAppointment($temporaryAppointment, $attachment);

        Gate::authorize('deleteAttachment', $temporaryAppointment);

        $filename = $attachment->original_filename;
        $attachment->setRelation('attachable', $temporaryAppointment);

        Storage::disk('local')->delete($attachment->file_path);
        $attachment->delete();

        $activity->log(
            'temporary_appointment_attachment_deleted',
            "{$request->user()->name} deleted {$filename} from {$temporaryAppointment->reference_no}.",
            $attachment,
            ['filename' => $filename],
            user: $request->user(),
            request: $request,
        );

        return redirect()->route('temporary-appointments.show', $temporaryAppointment)->with('success', 'Temporary appointment document deleted successfully.');
    }

    private function ensureAttachmentBelongsToAppointment(TemporaryAppointment $temporaryAppointment, Attachment $attachment): void
    {
        abort_unless(
            $attachment->attachable_type === TemporaryAppointment::class && (int) $attachment->attachable_id === (int) $temporaryAppointment->id,
            404,
        );
    }
}
