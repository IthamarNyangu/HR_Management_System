<?php

namespace App\Mail;

use App\Models\JobApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class JobApplicationRejectedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public JobApplication $application)
    {
        $this->application->loadMissing('jobOpening');
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Application outcome: '.$this->application->jobOpening?->title,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.job-applications.rejected',
        );
    }
}
