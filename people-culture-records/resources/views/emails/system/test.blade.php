@extends('emails.layout', [
    'title' => 'HRMS Email Test',
    'preheader' => 'Controlled SMTP test from the People & Culture Records Management System.',
])

@section('content')
    <p style="margin:0 0 18px;"><strong>Hi there,</strong></p>

    <p style="margin:0 0 18px;">
        This is a controlled test email from the People &amp; Culture Records Management System.
    </p>

    <p style="margin:0 0 24px;">
        If you received this, SMTP is configured correctly.
    </p>

    <p style="margin:0;">
        Kind regards,<br>
        The People &amp; Culture Records Management System
    </p>
@endsection
