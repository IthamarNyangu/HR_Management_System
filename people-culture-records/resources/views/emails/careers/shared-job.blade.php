@extends('emails.layout', [
    'title' => 'Right to Care Zambia job shared with you',
    'preheader' => 'A Right to Care Zambia vacancy has been shared with you.',
])

@section('content')
    <p style="margin:0 0 18px;"><strong>Dear {{ $recipientEmail }},</strong></p>

    <p style="margin:0 0 18px;">
        Someone thought you might be interested in this opportunity at Right to Care Zambia.
        You can view the full vacancy by clicking the link below.
    </p>

    <p style="margin:0 0 18px;">
        <a href="{{ route('careers.show', $jobOpening->slug) }}" style="color:#2563eb; font-weight:700;">
            {{ $jobOpening->title }}
        </a>
    </p>

    <p style="margin:0 0 18px;">
        <strong>Request to Hire No.:</strong> {{ $jobOpening->reference_no }}<br>
        <strong>Project:</strong> {{ $jobOpening->project?->name ?? '-' }}<br>
        <strong>Department:</strong> {{ $jobOpening->department?->name ?? '-' }}<br>
        <strong>Location:</strong> {{ $jobOpening->public_location_label }}<br>
        <strong>Closing date:</strong> {{ $jobOpening->closing_date?->format('d M Y') ?? '-' }}
    </p>

    <p style="margin:0 0 18px;">
        <a href="{{ route('careers.show', $jobOpening->slug) }}" style="color:#2563eb; word-break:break-all;">
            {{ route('careers.show', $jobOpening->slug) }}
        </a>
    </p>

    <p style="margin:0 0 18px;">
        Browse current vacancies at
        <a href="{{ route('careers.index') }}" style="color:#2563eb;">Right to Care Zambia Careers</a>.
    </p>

    <p style="margin:0 0 18px;">
        Please note: this message was automatically generated. Please do not reply to this email.
    </p>

    <p style="margin:0;">
        Regards,<br>
        Right to Care Zambia People &amp; Culture
    </p>
@endsection
