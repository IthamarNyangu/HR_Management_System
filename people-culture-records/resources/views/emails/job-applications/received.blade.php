@extends('emails.layout', [
    'title' => 'Application received',
    'preheader' => 'Your application has been received by Right to Care Zambia.',
])

@section('content')
    <p style="margin:0 0 18px;"><strong>Dear {{ $application->full_name }},</strong></p>

    <p style="margin:0 0 18px;">
        Thank you for applying for the <strong>{{ $application->jobOpening?->title }}</strong> position.
    </p>

    <p style="margin:0 0 18px;">
        <strong>Application reference:</strong> {{ $application->reference_no }}<br>
        <strong>Closing date:</strong> {{ $application->jobOpening?->closing_date?->format('d M Y') }}
    </p>

    <p style="margin:0 0 18px;">
        You can withdraw your application using this secure link:
    </p>

    <p style="margin:0 0 18px;">
        <a href="{{ $withdrawalUrl }}" style="color:#2563eb; word-break:break-all;">{{ $withdrawalUrl }}</a>
    </p>

    <p style="margin:0 0 24px;">
        If you do not hear from us within 4 weeks after the closing date, please consider your application unsuccessful.
    </p>

    <p style="margin:0;">
        Regards,<br>
        Right to Care Zambia People &amp; Culture
    </p>
@endsection
