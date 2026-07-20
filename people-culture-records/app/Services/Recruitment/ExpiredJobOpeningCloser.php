<?php

namespace App\Services\Recruitment;

use App\Models\JobOpening;
use App\Services\ActivityLogger;

class ExpiredJobOpeningCloser
{
    public function __construct(private readonly ActivityLogger $activity)
    {
    }

    public function closeExpired(): int
    {
        $jobs = JobOpening::query()
            ->with(['province', 'facility'])
            ->where('status', JobOpening::STATUS_PUBLISHED)
            ->whereDate('closing_date', '<', today())
            ->get();

        $jobs->each(function (JobOpening $job): void {
            $job->update([
                'status' => JobOpening::STATUS_CLOSED,
                'closed_at' => now(),
            ]);

            $this->activity->log(
                'job_opening_auto_closed',
                "System auto-closed job opening {$job->reference_no}.",
                $job->fresh(['province', 'facility']),
            );
        });

        return $jobs->count();
    }
}
