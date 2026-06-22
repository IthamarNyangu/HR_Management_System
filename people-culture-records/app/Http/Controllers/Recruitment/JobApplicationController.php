<?php

namespace App\Http\Controllers\Recruitment;

use App\Http\Controllers\Controller;
use App\Http\Requests\Recruitment\AddJobApplicationNoteRequest;
use App\Http\Requests\Recruitment\RejectJobApplicationRequest;
use App\Http\Requests\Recruitment\SendJobApplicationEmailRequest;
use App\Http\Requests\Recruitment\UpdateJobApplicationReviewRequest;
use App\Http\Requests\Recruitment\UpdateJobApplicationStatusRequest;
use App\Mail\JobApplicationRejectedMail;
use App\Mail\JobApplicationVacancyWithdrawnMail;
use App\Models\District;
use App\Models\Facility;
use App\Models\JobApplication;
use App\Models\JobApplicationStatusHistory;
use App\Models\JobOpening;
use App\Models\Province;
use App\Services\ActivityLogger;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class JobApplicationController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', JobApplication::class);

        $applications = JobApplication::query()
            ->with(['jobOpening.department', 'jobOpening.province', 'jobOpening.district', 'jobOpening.facility'])
            ->visibleTo($request->user())
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search')->trim();

                $query->where(function ($query) use ($search) {
                    $query->where('reference_no', 'like', "%{$search}%")
                        ->orWhere('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhereHas('jobOpening', function ($query) use ($search) {
                            $query->where('reference_no', 'like', "%{$search}%")
                                ->orWhere('title', 'like', "%{$search}%");
                        });
                });
            })
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->when($request->filled('job_opening_id'), fn ($query) => $query->where('job_opening_id', $request->integer('job_opening_id')))
            ->when($request->filled('visibility'), fn ($query) => $query->whereHas('jobOpening', fn (Builder $query) => $query->where('visibility', $request->string('visibility'))))
            ->when($request->filled('province_id'), fn ($query) => $query->whereHas('jobOpening', fn (Builder $query) => $query->where('province_id', $request->integer('province_id'))))
            ->when($request->filled('district_id'), fn ($query) => $query->whereHas('jobOpening', fn (Builder $query) => $query->where('district_id', $request->integer('district_id'))))
            ->when($request->filled('facility_id'), fn ($query) => $query->whereHas('jobOpening', fn (Builder $query) => $query->where('facility_id', $request->integer('facility_id'))))
            ->when($request->filled('highest_qualification'), fn ($query) => $query->where('highest_qualification', $request->string('highest_qualification')))
            ->when($request->filled('experience_min'), fn ($query) => $query->where('years_of_experience', '>=', $request->float('experience_min')))
            ->when($request->filled('experience_max'), fn ($query) => $query->where('years_of_experience', '<=', $request->float('experience_max')))
            ->when($request->filled('submitted_from'), fn ($query) => $query->whereDate('submitted_at', '>=', $request->date('submitted_from')))
            ->when($request->filled('submitted_to'), fn ($query) => $query->whereDate('submitted_at', '<=', $request->date('submitted_to')))
            ->when($request->filled('score_min'), fn ($query) => $query->where('overall_score', '>=', $request->float('score_min')))
            ->when($request->filled('score_max'), fn ($query) => $query->where('overall_score', '<=', $request->float('score_max')))
            ->when($request->boolean('has_education_certificate'), fn ($query) => $query->whereHas('documents', fn (Builder $query) => $query->where('document_type', JobApplication::DOCUMENT_EDUCATION_CERTIFICATES)))
            ->when(in_array($request->query('quick'), [JobApplication::STATUS_REJECTED, JobApplication::STATUS_WITHDRAWN], true), fn ($query) => $query->where('status', $request->query('quick')))
            ->when($request->query('sort') === 'score_desc', fn ($query) => $query->orderByDesc('overall_score')->orderByDesc('submitted_at'))
            ->when($request->query('sort') === 'status', fn ($query) => $query->orderBy('status')->orderByDesc('submitted_at'))
            ->when($request->query('sort') === 'job_title', fn ($query) => $query->join('job_openings as sort_jobs', 'job_applications.job_opening_id', '=', 'sort_jobs.id')->orderBy('sort_jobs.title')->select('job_applications.*'))
            ->when($request->query('sort') === 'submitted_oldest', fn ($query) => $query->oldest('submitted_at'))
            ->when(! in_array($request->query('sort'), ['score_desc', 'status', 'job_title', 'submitted_oldest'], true), fn ($query) => $query->latest('submitted_at'))
            ->paginate(10)
            ->withQueryString();

        $jobOpenings = JobOpening::query()
            ->visibleTo($request->user())
            ->orderByDesc('created_at')
            ->get(['id', 'reference_no', 'title']);

        $provinces = Province::where('is_active', true)->orderBy('name')->get(['id', 'name']);
        $districts = District::where('is_active', true)->orderBy('name')->get(['id', 'name']);
        $facilities = Facility::where('is_active', true)->orderBy('name')->get(['id', 'name']);

        return view('recruitment.applications.index', compact('applications', 'jobOpenings', 'provinces', 'districts', 'facilities'));
    }

    public function show(JobApplication $jobApplication): View
    {
        Gate::authorize('view', $jobApplication);

        $jobApplication->load([
            'jobOpening.department',
            'jobOpening.project',
            'jobOpening.province',
            'jobOpening.district',
            'jobOpening.facility',
            'documents',
            'reviewedBy',
            'lastStatusChangedBy',
            'notes.user',
            'statusHistories.changedBy',
        ]);

        return view('recruitment.applications.show', compact('jobApplication'));
    }

    public function updateReview(UpdateJobApplicationReviewRequest $request, JobApplication $jobApplication, ActivityLogger $activity): RedirectResponse
    {
        $scores = [
            'qualification_score' => $request->input('qualification_score'),
            'experience_score' => $request->input('experience_score'),
            'screening_score' => $request->input('screening_score'),
        ];

        $jobApplication->update([
            'qualification_score' => $scores['qualification_score'],
            'experience_score' => $scores['experience_score'],
            'screening_score' => $scores['screening_score'],
            'overall_score' => $this->overallScore($scores),
            'review_notes' => $request->input('review_notes'),
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        $activity->log(
            'job_application_review_updated',
            "{$request->user()->name} updated review scores for application {$jobApplication->reference_no}.",
            $jobApplication,
            [
                'qualification_score' => $jobApplication->qualification_score,
                'experience_score' => $jobApplication->experience_score,
                'screening_score' => $jobApplication->screening_score,
                'overall_score' => $jobApplication->overall_score,
            ],
            request: $request,
        );

        return back()->with('success', 'Application review saved.');
    }

    public function updateStatus(UpdateJobApplicationStatusRequest $request, JobApplication $jobApplication, ActivityLogger $activity): RedirectResponse
    {
        $history = $this->changeStatus(
            $jobApplication,
            $request->string('status')->toString(),
            $request,
            $activity,
            $request->input('comment'),
        );

        return back()->with('success', $history ? 'Application status updated.' : 'Application already has that status.');
    }

    public function addNote(AddJobApplicationNoteRequest $request, JobApplication $jobApplication, ActivityLogger $activity): RedirectResponse
    {
        $note = $jobApplication->notes()->create([
            'user_id' => $request->user()->id,
            'note' => $request->string('note')->trim()->toString(),
            'is_private' => true,
        ]);

        $activity->log(
            'job_application_note_added',
            "{$request->user()->name} added an internal note to application {$jobApplication->reference_no}.",
            $jobApplication,
            ['note_id' => $note->id],
            request: $request,
        );

        return back()->with('success', 'Internal note added.');
    }

    public function reject(RejectJobApplicationRequest $request, JobApplication $jobApplication, ActivityLogger $activity): RedirectResponse
    {
        $history = $this->changeStatus(
            $jobApplication,
            JobApplication::STATUS_REJECTED,
            $request,
            $activity,
            $request->input('comment') ?: $request->input('rejection_reason'),
            false,
            ['rejected_at' => now(), 'rejection_reason' => $request->input('rejection_reason')],
        );

        $activity->log(
            'job_application_rejected',
            "{$request->user()->name} rejected application {$jobApplication->reference_no}.",
            $jobApplication,
            ['rejection_reason' => $request->input('rejection_reason')],
            request: $request,
        );

        $emailMessage = $this->maybeSendApplicationEmail(
            $jobApplication->fresh(['jobOpening']),
            'rejected',
            $request,
            $activity,
            $history,
            $request->boolean('send_email'),
        );

        return back()->with('success', 'Application rejected.'.$emailMessage);
    }

    public function sendEmail(SendJobApplicationEmailRequest $request, JobApplication $jobApplication, ActivityLogger $activity): RedirectResponse
    {
        if ($jobApplication->isWithdrawn()) {
            return back()->with('error', 'Withdrawn applications cannot receive review emails.');
        }

        $emailType = $request->string('email_type')->toString();

        if ($emailType === 'rejected' && $jobApplication->status !== JobApplication::STATUS_REJECTED) {
            return back()->with('error', 'Rejection emails can only be sent after an application is rejected.');
        }

        if ($emailType === 'vacancy_withdrawn' && $jobApplication->jobOpening?->status !== JobOpening::STATUS_CANCELLED) {
            return back()->with('error', 'A vacancy withdrawal notice can only be sent after the job opening is cancelled.');
        }

        $message = $this->maybeSendApplicationEmail(
            $jobApplication->loadMissing('jobOpening'),
            $emailType,
            $request,
            $activity,
            null,
            true,
        );

        return back()->with('success', trim('Email request processed.'.$message));
    }

    /**
     * @param array<string, mixed> $scores
     */
    private function overallScore(array $scores): ?float
    {
        $values = collect($scores)
            ->filter(fn ($value) => $value !== null && $value !== '')
            ->map(fn ($value) => (float) $value)
            ->values();

        if ($values->isEmpty()) {
            return null;
        }

        return round($values->avg(), 2);
    }

    /**
     * @param array<string, mixed> $extra
     */
    private function changeStatus(
        JobApplication $jobApplication,
        string $newStatus,
        Request $request,
        ActivityLogger $activity,
        ?string $comment = null,
        bool $emailSent = false,
        array $extra = [],
    ): ?JobApplicationStatusHistory {
        $oldStatus = $jobApplication->status;

        if ($oldStatus === $newStatus) {
            return null;
        }

        $jobApplication->update(array_merge($extra, [
            'status' => $newStatus,
            'last_status_changed_by' => $request->user()->id,
            'last_status_changed_at' => now(),
        ]));

        $history = $jobApplication->statusHistories()->create([
            'from_status' => $oldStatus,
            'to_status' => $newStatus,
            'changed_by' => $request->user()->id,
            'comment' => $comment,
            'email_sent' => $emailSent,
        ]);

        $activity->log(
            'job_application_status_changed',
            "{$request->user()->name} changed application {$jobApplication->reference_no} from ".str($oldStatus)->headline().' to '.str($newStatus)->headline().'.',
            $jobApplication,
            [
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'comment' => $comment,
            ],
            request: $request,
        );

        return $history;
    }

    private function maybeSendApplicationEmail(
        JobApplication $jobApplication,
        string $emailType,
        Request $request,
        ActivityLogger $activity,
        ?JobApplicationStatusHistory $history,
        bool $shouldSend,
    ): string {
        if (! $shouldSend) {
            return '';
        }

        try {
            $mailable = match ($emailType) {
                'vacancy_withdrawn' => new JobApplicationVacancyWithdrawnMail($jobApplication),
                default => new JobApplicationRejectedMail($jobApplication),
            };

            Mail::to($jobApplication->email)->send($mailable);

            $history?->update(['email_sent' => true]);
            $jobApplication->update([
                'outcome_sent_at' => now(),
                'outcome_sent_by' => $request->user()->id,
            ]);

            $action = $emailType === 'vacancy_withdrawn'
                ? 'job_application_vacancy_withdrawn_email_sent'
                : 'job_application_rejection_email_sent';

            $activity->log(
                $action,
                "{$request->user()->name} sent a {$emailType} email for application {$jobApplication->reference_no}.",
                $jobApplication,
                ['email_type' => $emailType],
                request: $request,
            );

            return ' Email sent to applicant.';
        } catch (\Throwable $exception) {
            Log::warning('Job application review email failed.', [
                'application_id' => $jobApplication->id,
                'email_type' => $emailType,
                'error' => $exception->getMessage(),
            ]);

            $activity->log(
                'job_application_email_failed',
                "Email failed for application {$jobApplication->reference_no}.",
                $jobApplication,
                [
                    'email_type' => $emailType,
                    'error' => $exception->getMessage(),
                ],
                request: $request,
            );

            return ' Email could not be sent; the status update was saved.';
        }
    }
}
