@extends('emails.layout', [
    'title' => 'Application shortlisted',
    'preheader' => 'Your Right to Care Zambia application has been shortlisted.',
])

@section('content')
    <p style="margin:0 0 18px;">Dear {{ $application->first_name }},</p>

    <p style="margin:0 0 18px;">
        Thank you for applying for the {{ $application->jobOpening?->title }} position with Right to Care Zambia.
    </p>

    <p style="margin:0 0 18px;">
        We are pleased to let you know that your application has been shortlisted for the next stage of our recruitment process.
    </p>

    <p style="margin:0 0 18px;">
        <strong>Application reference:</strong> {{ $application->reference_no }}<br>
        <strong>Position:</strong> {{ $application->jobOpening?->title }}
    </p>

    <p style="margin:0 0 18px;">
        The People &amp; Culture team will contact you with the next steps in due course.
    </p>

    <p style="margin:0 0 18px;">
        Kind regards,<br>
        Right to Care Zambia People &amp; Culture
    </p>

    <p style="margin:0; color:#6b7280;">
        This email box is not monitored. Please do not reply to this message.
    </p>
@endsection
