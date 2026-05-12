<?php

namespace App\Console\Commands;

use App\Services\ActivityLogger;
use App\Services\StaffPromotionApplicationService;
use Illuminate\Console\Command;

class ApplyEffectiveStaffPromotions extends Command
{
    protected $signature = 'promotions:apply-effective';

    protected $description = 'Apply due staff promotions to employee current job titles based on effective date.';

    public function handle(StaffPromotionApplicationService $promotionApplications, ActivityLogger $activity): int
    {
        $processed = 0;

        foreach ($promotionApplications->dueEmployeeIds() as $employeeId) {
            $processed += $promotionApplications->applyDuePromotionsForEmployee($employeeId, $activity);
        }

        $this->info("Processed {$processed} due staff promotion(s).");

        return self::SUCCESS;
    }
}
