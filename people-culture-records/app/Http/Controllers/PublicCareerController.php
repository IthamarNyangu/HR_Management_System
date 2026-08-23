<?php

namespace App\Http\Controllers;

use App\Mail\SharedJobOpeningMail;
use App\Models\JobOpening;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\HttpFoundation\Response;

class PublicCareerController extends Controller
{
    public function index(): View
    {
        $jobs = JobOpening::query()
            ->publiclyVisible()
            ->with(['department', 'project', 'province', 'provinces', 'district', 'facility', 'employmentType'])
            ->orderBy('closing_date')
            ->paginate(10);

        return view('public.careers.index', compact('jobs'));
    }

    public function show(JobOpening $jobOpening): View
    {
        abort_unless(JobOpening::query()->publiclyVisible()->whereKey($jobOpening->getKey())->exists(), 404);

        $jobOpening->load(['department', 'project', 'province', 'provinces', 'district', 'facility', 'employmentType', 'reportingToJobTitle']);

        return view('public.careers.show', compact('jobOpening'));
    }

    public function share(Request $request, JobOpening $jobOpening): RedirectResponse
    {
        abort_unless(JobOpening::query()->publiclyVisible()->whereKey($jobOpening->getKey())->exists(), 404);

        $validated = $request->validate([
            'recipient_email' => ['required', 'email', 'max:255'],
        ], [
            'recipient_email.required' => 'Enter the email address you want to share this job with.',
            'recipient_email.email' => 'Enter a valid email address.',
        ]);

        $jobOpening->load(['department', 'project', 'province', 'provinces', 'district', 'facility', 'employmentType', 'reportingToJobTitle']);

        try {
            Mail::to($validated['recipient_email'])->send(new SharedJobOpeningMail($jobOpening, $validated['recipient_email']));
        } catch (\Throwable $exception) {
            Log::warning('Shared job email failed to send.', [
                'job_opening_id' => $jobOpening->id,
                'recipient_email' => $validated['recipient_email'],
                'error' => $exception->getMessage(),
            ]);

            return back()
                ->withInput()
                ->with('share_error', 'The job could not be shared by email right now. Please try again later or copy the link.');
        }

        return back()->with('share_success', 'Job shared successfully with '.$validated['recipient_email'].'.');
    }

    public function downloadAnnouncementPdf(JobOpening $jobOpening): Response
    {
        abort_unless(JobOpening::query()->publiclyVisible()->whereKey($jobOpening->getKey())->exists(), 404);

        $jobOpening->load(['department', 'project', 'province', 'provinces', 'district', 'facility', 'employmentType', 'reportingToJobTitle']);

        $pdf = Pdf::loadView('recruitment.job-openings.pdf', [
            'jobOpening' => $jobOpening,
            'generatedAt' => now(),
        ])->setPaper('a4');

        $pdf->render();

        $dompdf = $pdf->getDomPDF();
        $font = $dompdf->getFontMetrics()->get_font('Helvetica', 'normal');

        $dompdf->getCanvas()->page_text(38, 804, 'Page {PAGE_NUM} of {PAGE_COUNT}', $font, 9, [0.42, 0.45, 0.50]);

        return $pdf->download(str($jobOpening->vacancy_announcement_title)->slug()->append('-announcement.pdf')->toString());
    }
}
