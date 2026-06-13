<?php

namespace App\Http\Controllers;

use App\Http\Requests\SendWithdrawalLinkRequest;
use App\Http\Requests\StorePublicJobApplicationRequest;
use App\Mail\JobApplicationReceivedMail;
use App\Mail\JobApplicationWithdrawnMail;
use App\Mail\JobApplicationWithdrawalLinkMail;
use App\Models\JobApplication;
use App\Models\JobApplicationDocument;
use App\Models\JobOpening;
use App\Services\ActivityLogger;
use App\Services\ReferenceNumberService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class PublicJobApplicationController extends Controller
{
    public function create(JobOpening $jobOpening): View
    {
        abort_unless($jobOpening->is_publicly_applyable, 404);

        $jobOpening->load(['department', 'project', 'province', 'district', 'facility', 'employmentType']);

        return view('public.applications.create', compact('jobOpening'));
    }

    public function store(
        StorePublicJobApplicationRequest $request,
        JobOpening $jobOpening,
        ReferenceNumberService $referenceNumbers,
        ActivityLogger $activity,
    ): View {
        abort_unless($jobOpening->is_publicly_applyable, 404);

        $token = Str::random(64);
        $application = DB::transaction(function () use ($request, $jobOpening, $referenceNumbers, $token) {
            $application = JobApplication::create([
                'reference_no' => $referenceNumbers->generate('APP', 'job_applications'),
                'job_opening_id' => $jobOpening->id,
                'source' => JobApplication::SOURCE_EXTERNAL,
                'status' => JobApplication::STATUS_SUBMITTED,
                'title' => $request->input('title'),
                'first_name' => $request->string('first_name')->trim()->toString(),
                'last_name' => $request->string('last_name')->trim()->toString(),
                'email' => $request->string('email')->lower()->trim()->toString(),
                'phone' => $request->string('phone')->trim()->toString(),
                'national_id' => $request->input('national_id'),
                'gender' => $request->input('gender'),
                'disability' => $request->input('disability'),
                'highest_qualification' => $request->input('highest_qualification'),
                'field_of_study' => $request->input('field_of_study'),
                'years_of_experience' => $request->input('years_of_experience'),
                'current_employer' => $request->input('current_employer'),
                'motivation' => $request->input('motivation'),
                'consent_given_at' => now(),
                'submitted_at' => now(),
                'withdrawal_token_hash' => hash('sha256', $token),
            ]);

            $this->storeDocument($application, JobApplication::DOCUMENT_CV, $request->file('cv'));
            $this->storeDocument($application, JobApplication::DOCUMENT_COVER_LETTER, $request->file('cover_letter'));
            $this->storeDocument($application, JobApplication::DOCUMENT_EDUCATION_CERTIFICATES, $request->file('education_certificates'));

            foreach ($request->file('supporting_documents', []) as $file) {
                $this->storeDocument($application, JobApplication::DOCUMENT_SUPPORTING, $file);
            }

            return $application;
        });

        $application->load(['jobOpening', 'documents']);
        $activity->log(
            'job_application_submitted',
            "{$application->full_name} submitted application {$application->reference_no} for {$jobOpening->title}.",
            $application,
            request: $request,
        );

        $withdrawalUrl = $this->signedWithdrawalUrl($application, $token);
        $mailSent = $this->sendConfirmationMail($application, $withdrawalUrl);

        if ($mailSent) {
            $application->update(['last_confirmation_sent_at' => now()]);
        }

        return view('public.applications.success', compact('application', 'mailSent'));
    }

    public function withdrawalRequest(): View
    {
        return view('public.applications.withdraw-request');
    }

    public function sendWithdrawalLink(SendWithdrawalLinkRequest $request, ActivityLogger $activity): RedirectResponse
    {
        $application = JobApplication::query()
            ->where('reference_no', $request->string('reference_no')->trim()->toString())
            ->where('email', $request->string('email')->lower()->trim()->toString())
            ->first();

        if ($application && ! $application->isWithdrawn()) {
            $token = Str::random(64);
            $application->update(['withdrawal_token_hash' => hash('sha256', $token)]);
            $this->sendWithdrawalMail($application, $this->signedWithdrawalUrl($application, $token));
        }

        $activity->log(
            'job_application_withdrawal_link_requested',
            'A withdrawal link was requested for an application.',
            $application,
            [
                'reference_no' => $request->input('reference_no'),
            ],
            request: $request,
        );

        return back()->with('success', 'If the application details match our records, a withdrawal link will be sent to the applicant email address.');
    }

    public function withdrawShow(Request $request, JobApplication $jobApplication, string $token): View
    {
        abort_unless($this->validWithdrawalToken($jobApplication, $token), 403);

        $jobApplication->load('jobOpening');

        return view('public.applications.withdraw-confirm', compact('jobApplication', 'token'));
    }

    public function withdrawConfirm(Request $request, JobApplication $jobApplication, string $token, ActivityLogger $activity): View
    {
        abort_unless($this->validWithdrawalToken($jobApplication, $token), 403);

        if (! $jobApplication->isWithdrawn()) {
            $jobApplication->update([
                'status' => JobApplication::STATUS_WITHDRAWN,
                'withdrawn_at' => now(),
                'withdrawal_token_hash' => null,
            ]);

            $activity->log(
                'job_application_withdrawn',
                "{$jobApplication->full_name} withdrew application {$jobApplication->reference_no}.",
                $jobApplication,
                request: $request,
            );

            $jobApplication->loadMissing('jobOpening');
            $this->sendWithdrawnMail($jobApplication);
        }

        return view('public.applications.withdraw-success', compact('jobApplication'));
    }

    private function storeDocument(JobApplication $application, string $type, UploadedFile $file): JobApplicationDocument
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $storedFilename = Str::uuid().($extension ? ".{$extension}" : '');
        $path = $file->storeAs("job-applications/{$application->id}", $storedFilename, 'local');

        return $application->documents()->create([
            'document_type' => $type,
            'original_filename' => $file->getClientOriginalName(),
            'stored_filename' => $storedFilename,
            'file_path' => $path,
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize() ?: 0,
            'uploaded_at' => now(),
        ]);
    }

    private function signedWithdrawalUrl(JobApplication $application, string $token): string
    {
        return URL::temporarySignedRoute(
            'applications.withdraw.show',
            now()->addDays(60),
            ['jobApplication' => $application, 'token' => $token],
        );
    }

    private function validWithdrawalToken(JobApplication $application, string $token): bool
    {
        return ! $application->isWithdrawn()
            && $application->withdrawal_token_hash
            && hash_equals($application->withdrawal_token_hash, hash('sha256', $token));
    }

    private function sendConfirmationMail(JobApplication $application, string $withdrawalUrl): bool
    {
        try {
            Mail::to($application->email)->send(new JobApplicationReceivedMail($application, $withdrawalUrl));

            return true;
        } catch (\Throwable $exception) {
            Log::warning('Job application confirmation email failed.', [
                'application_id' => $application->id,
                'error' => $exception->getMessage(),
            ]);

            return false;
        }
    }

    private function sendWithdrawalMail(JobApplication $application, string $withdrawalUrl): void
    {
        try {
            Mail::to($application->email)->send(new JobApplicationWithdrawalLinkMail($application, $withdrawalUrl));
        } catch (\Throwable $exception) {
            Log::warning('Job application withdrawal email failed.', [
                'application_id' => $application->id,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    private function sendWithdrawnMail(JobApplication $application): void
    {
        try {
            Mail::to($application->email)->send(new JobApplicationWithdrawnMail($application));
        } catch (\Throwable $exception) {
            Log::warning('Job application withdrawn confirmation email failed.', [
                'application_id' => $application->id,
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
