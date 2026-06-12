<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\JobOpening;
use Illuminate\Http\JsonResponse;

class PublicJobOpeningController extends Controller
{
    public function index(): JsonResponse
    {
        $jobs = JobOpening::query()
            ->publiclyVisible()
            ->with(['department', 'project', 'province', 'district', 'facility'])
            ->orderBy('closing_date')
            ->get()
            ->map(fn (JobOpening $job) => $this->summaryPayload($job));

        return response()->json($jobs);
    }

    public function show(string $slug): JsonResponse
    {
        $job = JobOpening::query()
            ->publiclyVisible()
            ->with(['department', 'project', 'province', 'district', 'facility', 'employmentType', 'reportingToJobTitle'])
            ->where('slug', $slug)
            ->firstOrFail();

        return response()->json($this->detailPayload($job));
    }

    /**
     * @return array<string, mixed>
     */
    private function summaryPayload(JobOpening $job): array
    {
        return [
            'reference_no' => $job->reference_no,
            'title' => $job->title,
            'slug' => $job->slug,
            'department' => $job->department?->name,
            'project' => $job->project?->name,
            'location' => $job->location_label,
            'closing_date' => $job->closing_date?->toDateString(),
            'summary' => $job->summary,
            'detail_url' => route('careers.show', $job->slug),
            'apply_url' => $job->is_publicly_applyable ? route('careers.apply', $job->slug) : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function detailPayload(JobOpening $job): array
    {
        $payload = $this->summaryPayload($job) + [
            'employment_type' => $job->employmentType?->name,
            'opening_date' => $job->opening_date?->toDateString(),
            'sections' => [
                'about_us' => JobOpening::ABOUT_US_TEXT,
                'about_the_position' => [
                    'request_to_hire_no' => $job->reference_no,
                    'date_advertised' => $job->opening_date?->toDateString(),
                    'closing_date' => $job->closing_date?->toDateString(),
                    'position' => $job->title,
                    'location' => $job->location_label,
                    'contract_duration' => $job->contract_duration,
                    'contract_type' => $job->employmentType?->name,
                    'job_grade' => $job->job_grade,
                    'reporting_to' => $job->reporting_to_label,
                    'contact_email' => $job->announcement_contact_email,
                    'contact_person' => 'People & Culture Department',
                ],
                'qualifications_and_experience' => $job->qualifications,
                'technical_and_behavioural_competencies' => $job->requirements,
                'key_performance_areas' => $job->responsibilities,
                'application_procedure' => $job->is_publicly_applyable ? route('careers.apply', $job->slug) : null,
                'disclaimer' => JobOpening::DISCLAIMER_TEXT,
            ],
        ];

        if ($job->show_number_of_positions) {
            $payload['number_of_positions'] = $job->number_of_positions;
        }

        return $payload;
    }
}
