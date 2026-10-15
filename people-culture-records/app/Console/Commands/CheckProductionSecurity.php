<?php

namespace App\Console\Commands;

use App\Support\ProductionSecurity;
use Illuminate\Console\Command;

class CheckProductionSecurity extends Command
{
    protected $signature = 'security:production-check';

    protected $description = 'Verify that required production privacy and security controls are configured';

    public function handle(ProductionSecurity $security): int
    {
        $failures = $security->failures();

        if ($failures === []) {
            $this->info('Production security check passed.');

            return self::SUCCESS;
        }

        $this->error('Production security check failed:');
        foreach ($failures as $failure) {
            $this->line(' - '.$failure);
        }

        return self::FAILURE;
    }
}
