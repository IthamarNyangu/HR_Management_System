<?php

namespace App\Console\Commands;

use App\Models\JobOpening;
use App\Services\ActivityLogger;
use Illuminate\Console\Command;

class CloseExpiredJobOpenings extends Command
{
    protected $signature = 'recruitment:close-expired-jobs';

    protected $description = 'Close published recruitment jobs whose closing date has passed.';

    public function handle(ActivityLogger $activity): int
    {
        $jobs = JobOpening::query()
            ->with(['province', 'facility'])
            ->where('status', JobOpening::STATUS_PUBLISHED)
            ->whereDate('closing_date', '<', today())
            ->get();

        $jobs->each(function (JobOpening $job) use ($activity): void {
            $job->update([
                'status' => JobOpening::STATUS_CLOSED,
                'closed_at' => now(),
            ]);

            $activity->log(
                'job_opening_auto_closed',
                "System auto-closed job opening {$job->reference_no}.",
                $job->fresh(['province', 'facility']),
            );
        });

        $this->info("Closed {$jobs->count()} expired recruitment job(s).");

        return self::SUCCESS;
    }
}
