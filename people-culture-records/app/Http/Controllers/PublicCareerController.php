<?php

namespace App\Http\Controllers;

use App\Models\JobOpening;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\View\View;
use Symfony\Component\HttpFoundation\Response;

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

        $jobOpening->load(['department', 'project', 'province', 'district', 'facility', 'employmentType', 'reportingToJobTitle']);

        return view('public.careers.show', compact('jobOpening'));
    }

    public function downloadAnnouncementPdf(JobOpening $jobOpening): Response
    {
        abort_unless(JobOpening::query()->publiclyVisible()->whereKey($jobOpening->getKey())->exists(), 404);

        $jobOpening->load(['department', 'project', 'province', 'district', 'facility', 'employmentType', 'reportingToJobTitle']);

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
