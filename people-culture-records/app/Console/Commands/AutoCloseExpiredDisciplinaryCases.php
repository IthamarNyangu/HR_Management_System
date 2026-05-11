<?php

namespace App\Console\Commands;

use App\Models\CaseStatus;
use App\Models\DisciplinaryCase;
use App\Models\User;
use App\Notifications\DisciplinaryCaseAutoClosedNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

class AutoCloseExpiredDisciplinaryCases extends Command
{
    protected $signature = 'disciplinary:auto-close-expired';

    protected $description = 'Close active disciplinary cases where the expiry date has passed.';

    public function handle(): int
    {
        $activeStatus = CaseStatus::where('code', 'ACTIVE')->orWhere('name', 'Active')->first();
        $closedStatus = CaseStatus::where('code', 'CLOSED')->orWhere('name', 'Closed')->first();

        if (! $activeStatus || ! $closedStatus) {
            $this->error('Active and Closed case statuses must exist before auto-closing cases.');

            return self::FAILURE;
        }

        $cases = DisciplinaryCase::query()
            ->with(['employee', 'caseStatus'])
            ->where('case_status_id', $activeStatus->id)
            ->whereNotNull('expiry_date')
            ->whereDate('expiry_date', '<', today())
            ->get();

        $recipients = User::query()
            ->where('is_active', true)
            ->whereHas('role', fn ($query) => $query->whereIn('name', ['Admin', 'HR Manager']))
            ->get();

        foreach ($cases as $case) {
            $case->update([
                'case_status_id' => $closedStatus->id,
                'closed_at' => now(),
            ]);

            if ($recipients->isNotEmpty()) {
                Notification::send($recipients, new DisciplinaryCaseAutoClosedNotification($case));
            }
        }

        $count = $cases->count();

        $this->info("Closed {$count} expired disciplinary case(s).");

        return self::SUCCESS;
    }
}
