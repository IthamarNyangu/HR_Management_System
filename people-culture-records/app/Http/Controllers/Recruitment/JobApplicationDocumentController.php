<?php

namespace App\Http\Controllers\Recruitment;

use App\Http\Controllers\Controller;
use App\Models\JobApplication;
use App\Models\JobApplicationDocument;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class JobApplicationDocumentController extends Controller
{
    public function download(
        Request $request,
        JobApplication $jobApplication,
        JobApplicationDocument $document,
        ActivityLogger $activity,
    ): StreamedResponse {
        Gate::authorize('downloadDocument', [$jobApplication, $document]);

        abort_unless((int) $document->job_application_id === (int) $jobApplication->id, 404);
        abort_unless(Storage::disk('local')->exists($document->file_path), 404);

        $document->load('jobApplication.jobOpening.province', 'jobApplication.jobOpening.facility');

        $activity->log(
            'job_application_document_downloaded',
            "{$request->user()->name} downloaded {$document->original_filename} from application {$jobApplication->reference_no}.",
            $document,
            user: $request->user(),
            request: $request,
        );

        return Storage::disk('local')->download($document->file_path, $document->original_filename);
    }
}
