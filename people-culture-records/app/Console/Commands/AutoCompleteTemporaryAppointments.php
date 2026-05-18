<?php

namespace App\Console\Commands;

use App\Models\AppointmentStatus;
use App\Models\TemporaryAppointment;
use App\Models\User;
use App\Notifications\TemporaryAppointmentNotification;
use App\Services\ActivityLogger;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

class AutoCompleteTemporaryAppointments extends Command
{
    protected $signature = 'appointments:auto-complete';

    protected $description = 'Complete active temporary appointments where the end date has passed.';

    public function handle(ActivityLogger $activity): int
    {
        $activeStatus = AppointmentStatus::where('code', 'ACTIVE')->orWhere('name', 'Active')->first();
        $completedStatus = AppointmentStatus::where('code', 'COMPLETED')->orWhere('name', 'Completed')->first();

        if (! $activeStatus || ! $completedStatus) {
            $this->error('Active and Completed appointment statuses must exist before auto-completing appointments.');

            return self::FAILURE;
        }

        $appointments = TemporaryAppointment::query()
            ->with(['employee', 'province', 'facility'])
            ->where('appointment_status_id', $activeStatus->id)
            ->whereDate('end_date', '<', today())
            ->get();

        foreach ($appointments as $appointment) {
            $appointment->update([
                'appointment_status_id' => $completedStatus->id,
                'completed_at' => now(),
            ]);

            $activity->log(
                'temporary_appointment_auto_completed',
                "System auto-completed temporary appointment {$appointment->reference_no}.",
                $appointment,
                user: null,
                request: null,
            );

            $recipients = $this->recipients($appointment);

            if ($recipients->isNotEmpty()) {
                Notification::send($recipients, new TemporaryAppointmentNotification(
                    $appointment,
                    'Temporary appointment completed',
                    "Temporary appointment {$appointment->reference_no} has been auto-completed.",
                ));
            }
        }

        $count = $appointments->count();

        $this->info("Completed {$count} expired temporary appointment(s).");

        return self::SUCCESS;
    }

    private function recipients(TemporaryAppointment $appointment)
    {
        return User::query()
            ->where('is_active', true)
            ->whereHas('role', fn ($query) => $query->whereIn('name', ['Admin', 'HR Manager', 'HR Officer']))
            ->where(function ($query) use ($appointment) {
                $query->whereHas('role', fn ($query) => $query->whereIn('name', ['Admin', 'HR Manager']))
                    ->orWhere('province_id', $appointment->province_id);
            })
            ->get();
    }
}
