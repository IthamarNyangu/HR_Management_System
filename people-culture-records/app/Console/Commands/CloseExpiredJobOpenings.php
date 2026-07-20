<?php

namespace App\Console\Commands;

use App\Services\Recruitment\ExpiredJobOpeningCloser;
use Illuminate\Console\Command;

class CloseExpiredJobOpenings extends Command
{
    protected $signature = 'recruitment:close-expired-jobs';

    protected $description = 'Close published recruitment jobs whose closing date has passed.';

    public function handle(ExpiredJobOpeningCloser $closer): int
    {
        $closedCount = $closer->closeExpired();

        $this->info("Closed {$closedCount} expired recruitment job(s).");

        return self::SUCCESS;
    }
}
