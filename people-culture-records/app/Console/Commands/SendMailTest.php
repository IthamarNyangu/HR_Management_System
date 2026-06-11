<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendMailTest extends Command
{
    protected $signature = 'mail:test
        {to : Allowed recipient email address}
        {--subject=HRMS Email Test : Email subject line}';

    protected $description = 'Send a controlled one-recipient HRMS SMTP test email.';

    public function handle(): int
    {
        $to = mb_strtolower(trim((string) $this->argument('to')));
        $allowedRecipients = config('mail.test_allowed_recipients', []);

        if (! filter_var($to, FILTER_VALIDATE_EMAIL)) {
            $this->error('Enter a valid recipient email address.');

            return self::FAILURE;
        }

        if ($allowedRecipients === [] || ! in_array($to, $allowedRecipients, true)) {
            $this->error('This recipient is not in MAIL_TEST_ALLOWED_RECIPIENTS. Test emails are restricted.');

            return self::FAILURE;
        }

        try {
            Mail::raw(
                "This is a controlled test email from the People & Culture Records Management System.\n\nIf you received this, SMTP is configured correctly.",
                function ($message) use ($to) {
                    $message
                        ->to($to)
                        ->subject((string) $this->option('subject'));
                },
            );
        } catch (Throwable $exception) {
            $this->error('Mail test failed: '.$exception->getMessage());

            return self::FAILURE;
        }

        $this->info("Mail test sent to {$to}.");

        return self::SUCCESS;
    }
}
