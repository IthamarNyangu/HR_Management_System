@extends('emails.layout', [
    'title' => 'Application withdrawal link',
    'preheader' => 'A secure withdrawal link was requested for your application.',
])

@section('content')
    <p style="margin:0 0 18px;"><strong>Dear {{ $application->full_name }},</strong></p>

    <p style="margin:0 0 18px;">
        A withdrawal link was requested for application <strong>{{ $application->reference_no }}</strong>.
    </p>

    <p style="margin:0 0 18px;">
        <a href="{{ $withdrawalUrl }}" style="color:#2563eb; word-break:break-all;">{{ $withdrawalUrl }}</a>
    </p>

    <p style="margin:0 0 24px;">
        If you did not request this link, you can ignore this email.
    </p>

    <p style="margin:0;">
        Regards,<br>
        Right to Care Zambia People &amp; Culture
    </p>
@endsection
