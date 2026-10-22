<?php

namespace App\Mail;

use App\Models\Employee;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EmployeeTerminationItAlertMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Employee $employee,
        public string $recipientName,
    ) {
        $this->employee->loadMissing([
            'department',
            'jobTitle',
            'province',
            'district',
            'facility',
            'supervisor',
        ]);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Employee termination notice: {$this->employee->full_name} ({$this->employee->employee_no})",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.employees.termination-it-alert',
        );
    }
}
