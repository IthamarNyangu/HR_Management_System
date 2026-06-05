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
            ->with(['department', 'project', 'province', 'district', 'facility', 'employmentType'])
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
                'description' => $job->description,
                'responsibilities' => $job->responsibilities,
                'requirements' => $job->requirements,
                'qualifications' => $job->qualifications,
                'experience_required' => $job->experience_required,
                'contract_details' => $job->contract_details,
                'work_level' => $job->work_level,
                'location_details' => $job->location_details,
                'application_instructions' => $job->application_instructions,
            ],
        ];

        if ($job->show_number_of_positions) {
            $payload['number_of_positions'] = $job->number_of_positions;
        }

        return $payload;
    }
}
