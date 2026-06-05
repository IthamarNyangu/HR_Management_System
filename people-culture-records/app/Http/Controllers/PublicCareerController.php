<?php

namespace App\Http\Controllers;

use App\Models\JobOpening;
use Illuminate\Contracts\View\View;

class PublicCareerController extends Controller
{
    public function index(): View
    {
        $jobs = JobOpening::query()
            ->publiclyVisible()
            ->with(['department', 'project', 'province', 'district', 'facility', 'employmentType'])
            ->orderBy('closing_date')
            ->paginate(10);

        return view('public.careers.index', compact('jobs'));
    }

    public function show(JobOpening $jobOpening): View
    {
        abort_unless(JobOpening::query()->publiclyVisible()->whereKey($jobOpening->getKey())->exists(), 404);

        $jobOpening->load(['department', 'project', 'province', 'district', 'facility', 'employmentType']);

        return view('public.careers.show', compact('jobOpening'));
    }
}
