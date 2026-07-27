<?php

namespace App\Notifications;

use App\Models\TemporaryAppointment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\DatabaseMessage;
use Illuminate\Notifications\Notification;

class TemporaryAppointmentNotification extends Notification
{
    use Queueable;

    public function __construct(
        private TemporaryAppointment $appointment,
        private string $title,
        private string $message,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): DatabaseMessage
    {
        $url = $notifiable->can('view', $this->appointment)
            ? route('temporary-appointments.show', $this->appointment)
            : route('temporary-appointments.index');

        return new DatabaseMessage([
            'title' => $this->title,
            'message' => $this->message,
            'url' => $url,
            'reference_no' => $this->appointment->reference_no,
        ]);
    }
}
