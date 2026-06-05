<?php

namespace App\Mail;

use App\Models\JobApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class JobApplicationWithdrawalLinkMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public JobApplication $application,
        public string $withdrawalUrl,
    ) {
        $this->application->loadMissing('jobOpening');
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Withdrawal link for '.$this->application->reference_no,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.job-applications.withdrawal-link',
        );
    }
}
