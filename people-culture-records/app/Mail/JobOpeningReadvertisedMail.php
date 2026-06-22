<?php

namespace App\Mail;

use App\Models\JobApplication;
use App\Models\JobOpening;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class JobOpeningReadvertisedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public JobApplication $application,
        public JobOpening $jobOpening,
    ) {
        $this->jobOpening->loadMissing(['province', 'district', 'facility']);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Now open again: '.$this->jobOpening->title,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.job-openings.readvertised',
        );
    }
}
