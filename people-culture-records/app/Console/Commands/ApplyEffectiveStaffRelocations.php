<?php

namespace App\Console\Commands;

use App\Services\ActivityLogger;
use App\Services\StaffRelocationApplicationService;
use Illuminate\Console\Command;

class ApplyEffectiveStaffRelocations extends Command
{
    protected $signature = 'relocations:apply-effective';

    protected $description = 'Apply due staff relocations to employee current locations based on effective date.';

    public function handle(StaffRelocationApplicationService $relocationApplications, ActivityLogger $activity): int
    {
        $processed = 0;

        foreach ($relocationApplications->dueEmployeeIds() as $employeeId) {
            $processed += $relocationApplications->applyDueRelocationsForEmployee($employeeId, $activity);
        }

        $this->info("Processed {$processed} due staff relocation(s).");

        return self::SUCCESS;
    }
}
