<?php

namespace App\Mail;

use App\Models\JobOpening;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SharedJobOpeningMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public JobOpening $jobOpening,
        public string $recipientEmail,
    ) {
        $this->jobOpening->loadMissing(['department', 'project', 'province', 'district', 'facility', 'employmentType', 'reportingToJobTitle']);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Someone shared a Right to Care Zambia job with you',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.careers.shared-job',
        );
    }
}
