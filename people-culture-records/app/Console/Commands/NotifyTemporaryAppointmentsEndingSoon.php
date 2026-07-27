<?php

namespace App\Console\Commands;

use App\Mail\TemporaryAppointmentEndingSoonMail;
use App\Models\AppointmentStatus;
use App\Models\TemporaryAppointment;
use App\Models\TemporaryAppointmentReminder;
use App\Models\User;
use App\Notifications\TemporaryAppointmentNotification;
use App\Services\ActivityLogger;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;
use Throwable;

class NotifyTemporaryAppointmentsEndingSoon extends Command
{
    protected $signature = 'appointments:notify-ending-soon';

    protected $description = 'Email HR users and line managers when active temporary appointments have 60 or 30 days remaining.';

    public function handle(ActivityLogger $activity): int
    {
        $activeStatus = AppointmentStatus::where('code', 'ACTIVE')->orWhere('name', 'Active')->first();

        if (! $activeStatus) {
            $this->error('Active appointment status must exist before sending ending-soon reminders.');

            return self::FAILURE;
        }

        $emailsSent = 0;
        $emailsFailed = 0;
        $appointmentsNotified = 0;

        foreach ([60, 30] as $threshold) {
            $appointments = TemporaryAppointment::query()
                ->with([
                    'employee.supervisor.user.role',
                    'employee.user.role',
                    'supervisorEmployee.user.role',
                    'temporaryJobTitle',
                    'staffPromotion.promotionType',
                ])
                ->where('appointment_status_id', $activeStatus->id)
                ->whereDate('end_date', today()->addDays($threshold))
                ->get();

            foreach ($appointments as $appointment) {
                $sentForAppointment = 0;

                foreach ($this->recipients($appointment) as $recipient) {
                    $email = mb_strtolower(trim($recipient['email']));

                    if (TemporaryAppointmentReminder::query()
                        ->where('temporary_appointment_id', $appointment->id)
                        ->where('threshold_days', $threshold)
                        ->whereDate('scheduled_end_date', $appointment->end_date)
                        ->where('recipient_email', $email)
                        ->exists()) {
                        continue;
                    }

                    try {
                        Mail::to($email)->send(new TemporaryAppointmentEndingSoonMail(
                            $appointment,
                            $threshold,
                            $recipient['name'],
                            $recipient['can_manage'],
                        ));

                        TemporaryAppointmentReminder::create([
                            'temporary_appointment_id' => $appointment->id,
                            'threshold_days' => $threshold,
                            'scheduled_end_date' => $appointment->end_date,
                            'recipient_email' => $email,
                            'recipient_role' => $recipient['role'],
                            'sent_at' => now(),
                        ]);

                        if ($recipient['user']) {
                            $recipient['user']->notify(new TemporaryAppointmentNotification(
                                $appointment,
                                "Temporary appointment ending in {$threshold} days",
                                "{$appointment->reference_no} for {$appointment->employee?->full_name} ends on {$appointment->end_date?->format('d M Y')}.",
                            ));
                        }

                        $emailsSent++;
                        $sentForAppointment++;
                    } catch (Throwable $exception) {
                        report($exception);
                        $emailsFailed++;

                        $activity->log(
                            'temporary_appointment_ending_soon_email_failed',
                            "System could not send the {$threshold}-day reminder for {$appointment->reference_no} to {$email}.",
                            $appointment,
                            [
                                'threshold_days' => $threshold,
                                'recipient_email' => $email,
                                'error' => $exception->getMessage(),
                            ],
                            user: null,
                            request: null,
                        );
                    }
                }

                if ($sentForAppointment === 0) {
                    continue;
                }

                $appointmentsNotified++;

                $activity->log(
                    'temporary_appointment_ending_soon_notification_sent',
                    "System sent {$threshold}-day ending-soon reminders for {$appointment->reference_no}.",
                    $appointment,
                    [
                        'threshold_days' => $threshold,
                        'recipient_count' => $sentForAppointment,
                        'source' => $appointment->staffPromotion?->is_acting_promotion
                            ? 'acting_promotion'
                            : 'temporary_appointment',
                    ],
                    user: null,
                    request: null,
                );
            }
        }

        $this->info("Sent {$emailsSent} ending-soon email(s) for {$appointmentsNotified} active temporary appointment reminder(s).");

        if ($emailsFailed > 0) {
            $this->warn("{$emailsFailed} ending-soon email(s) failed. Review Recent Activity or Audit Logs for the recipient and mail error.");
        }

        return self::SUCCESS;
    }

    /**
     * @return Collection<int, array{email: string, name: string, role: string, user: ?User, can_manage: bool}>
     */
    private function recipients(TemporaryAppointment $appointment): Collection
    {
        $recipients = collect();

        User::query()
            ->with('role')
            ->where('is_active', true)
            ->whereNotNull('email')
            ->whereHas('role', fn ($query) => $query->whereIn('name', ['HR Manager', 'HR Officer']))
            ->get()
            ->each(function (User $user) use ($appointment, $recipients): void {
                $recipients->put(mb_strtolower(trim($user->email)), [
                    'email' => $user->email,
                    'name' => $user->name,
                    'role' => $user->role?->name ?? 'HR',
                    'user' => $user,
                    'can_manage' => $user->can('extend', $appointment),
                ]);
            });

        $lineManager = $appointment->supervisorEmployee ?: $appointment->employee?->supervisor;

        if ($lineManager?->email) {
            $key = mb_strtolower(trim($lineManager->email));
            $lineManagerUser = $lineManager->user;
            $isHrUser = in_array($lineManagerUser?->role?->name, ['HR Manager', 'HR Officer'], true);

            if (! $recipients->has($key)) {
                $recipients->put($key, [
                    'email' => $lineManager->email,
                    'name' => $lineManager->full_name,
                    'role' => $isHrUser ? $lineManagerUser->role->name : 'Line Manager',
                    'user' => $lineManagerUser,
                    'can_manage' => $isHrUser && $lineManagerUser->can('extend', $appointment),
                ]);
            }
        }

        return $recipients->values();
    }
}
