<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class ReconcileWorkPulse extends Command
{
    protected $signature = 'workpulse:reconcile';

    protected $description = 'Run a full HR to WorkPulse directory reconciliation';

    public function handle(): int
    {
        $baseUrl = rtrim((string) config('services.workpulse.base_url'), '/');
        $token = (string) config('services.workpulse.sync_token');
        if ($baseUrl === '' || $token === '') {
            $this->error('WorkPulse integration is not configured.');

            return self::FAILURE;
        }

        $response = Http::acceptJson()->withToken($token)->timeout(300)->post("{$baseUrl}/api/integrations/hr/sync");
        if (! $response->successful()) {
            $this->error('WorkPulse reconciliation failed: '.$response->status().' '.$response->body());

            return self::FAILURE;
        }

        $result = $response->json();
        $this->info(sprintf('WorkPulse reconciled: %d received, %d linked, %d supervisor assignments.', $result['received'] ?? 0, $result['linked_profiles'] ?? 0, $result['supervisor_assignments'] ?? 0));

        return self::SUCCESS;
    }
}
