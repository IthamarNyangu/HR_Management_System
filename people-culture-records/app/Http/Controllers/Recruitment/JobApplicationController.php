<?php

namespace App\Http\Controllers\Recruitment;

use App\Http\Controllers\Controller;
use App\Mail\JobApplicationOutcomeMail;
use App\Models\JobApplication;
use App\Models\JobOpening;
use App\Services\ActivityLogger;
use Illuminate\Contracts\View\View;
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
            ->with(['jobOpening.department', 'jobOpening.province'])
            ->visibleTo($request->user())
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search')->trim();

                $query->where(function ($query) use ($search) {
                    $query->where('reference_no', 'like', "%{$search}%")
                        ->orWhere('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhereHas('jobOpening', function ($query) use ($search) {
                            $query->where('reference_no', 'like', "%{$search}%")
                                ->orWhere('title', 'like', "%{$search}%");
                        });
                });
            })
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->when($request->filled('job_opening_id'), fn ($query) => $query->where('job_opening_id', $request->integer('job_opening_id')))
            ->oldest('submitted_at')
            ->paginate(10)
            ->withQueryString();

        $jobOpenings = JobOpening::query()
            ->visibleTo($request->user())
            ->orderByDesc('created_at')
            ->get(['id', 'reference_no', 'title']);

        return view('recruitment.applications.index', compact('applications', 'jobOpenings'));
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
        ]);

        return view('recruitment.applications.show', compact('jobApplication'));
    }

    public function sendOutcome(JobApplication $jobApplication, Request $request, ActivityLogger $activity): RedirectResponse
    {
        Gate::authorize('sendOutcome', $jobApplication);

        $jobApplication->loadMissing('jobOpening');

        if (! $jobApplication->isSubmitted()) {
            return back()->with('error', 'Only submitted applications can receive a not progressed outcome email.');
        }

        try {
            Mail::to($jobApplication->email)->send(new JobApplicationOutcomeMail($jobApplication));
        } catch (\Throwable $exception) {
            Log::warning('Job application outcome email failed.', [
                'application_id' => $jobApplication->id,
                'error' => $exception->getMessage(),
            ]);

            return back()->with('error', 'The outcome email could not be sent. Please check mail settings and try again.');
        }

        $jobApplication->update([
            'status' => JobApplication::STATUS_NOT_PROGRESSED,
            'outcome_sent_at' => now(),
            'outcome_sent_by' => $request->user()->id,
        ]);

        $activity->log(
            'job_application_outcome_sent',
            "{$request->user()->name} sent a not progressed outcome for application {$jobApplication->reference_no}.",
            $jobApplication,
            request: $request,
        );

        return back()->with('success', 'Application outcome email sent and the application was marked as not progressed.');
    }
}
