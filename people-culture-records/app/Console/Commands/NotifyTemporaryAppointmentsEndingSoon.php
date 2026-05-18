<?php

namespace App\Console\Commands;

use App\Models\AppointmentStatus;
use App\Models\TemporaryAppointment;
use App\Models\User;
use App\Notifications\TemporaryAppointmentNotification;
use App\Services\ActivityLogger;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

class NotifyTemporaryAppointmentsEndingSoon extends Command
{
    protected $signature = 'appointments:notify-ending-soon';

    protected $description = 'Notify HR users about active temporary appointments ending within 30 days and 7 days.';

    public function handle(ActivityLogger $activity): int
    {
        $activeStatus = AppointmentStatus::where('code', 'ACTIVE')->orWhere('name', 'Active')->first();

        if (! $activeStatus) {
            $this->error('Active appointment status must exist before sending ending-soon notifications.');

            return self::FAILURE;
        }

        $notified = 0;

        foreach ([30, 7] as $threshold) {
            $appointments = TemporaryAppointment::query()
                ->with(['employee', 'province', 'facility'])
                ->where('appointment_status_id', $activeStatus->id)
                ->whereDate('end_date', '>=', today())
                ->whereDate('end_date', '<=', today()->addDays($threshold))
                ->get();

            foreach ($appointments as $appointment) {
                $recipients = $this->recipients($appointment);

                if ($recipients->isEmpty()) {
                    continue;
                }

                Notification::send($recipients, new TemporaryAppointmentNotification(
                    $appointment,
                    "Temporary appointment ending within {$threshold} days",
                    "{$appointment->reference_no} ends on {$appointment->end_date?->format('d M Y')}.",
                ));

                $activity->log(
                    'temporary_appointment_ending_soon_notification_sent',
                    "System sent {$threshold}-day ending-soon notification for {$appointment->reference_no}.",
                    $appointment,
                    ['threshold_days' => $threshold],
                    user: null,
                    request: null,
                );

                $notified++;
            }
        }

        $this->info("Sent {$notified} temporary appointment ending-soon notification(s).");

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
