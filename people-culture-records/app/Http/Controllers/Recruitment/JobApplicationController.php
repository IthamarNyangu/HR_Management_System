<?php

namespace App\Http\Controllers\Recruitment;

use App\Http\Controllers\Controller;
use App\Models\JobApplication;
use App\Models\JobOpening;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

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
            ->latest('submitted_at')
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
}
