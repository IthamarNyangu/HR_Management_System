<?php

namespace App\Mail;

use App\Models\TemporaryAppointment;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TemporaryAppointmentEndingSoonMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public TemporaryAppointment $appointment,
        public int $daysRemaining,
        public string $recipientName,
        public bool $canManageAppointment,
    ) {
        $this->appointment->loadMissing([
            'employee',
            'temporaryJobTitle',
            'staffPromotion.promotionType',
        ]);
    }

    public function envelope(): Envelope
    {
        $appointmentType = $this->appointment->staffPromotion?->is_acting_promotion
            ? 'Acting Promotion'
            : 'Temporary Appointment';

        return new Envelope(
            subject: "{$appointmentType} for {$this->appointment->employee?->full_name} ending soon - {$this->daysRemaining} days remaining",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.temporary-appointments.ending-soon',
        );
    }
}
