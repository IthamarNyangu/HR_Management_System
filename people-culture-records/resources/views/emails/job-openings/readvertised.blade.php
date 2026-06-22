@extends('emails.layout', [
    'title' => 'Vacancy re-advertised',
    'preheader' => 'A Right to Care Zambia vacancy you previously applied for is open again.',
])

@section('content')
    <p style="margin:0 0 18px;">Dear {{ $application->first_name }},</p>

    <p style="margin:0 0 18px;">
        You previously applied for the <strong>{{ $jobOpening->title }}</strong> position with Right to Care Zambia.
        This vacancy has now been re-advertised and is open for applications again.
    </p>

    <p style="margin:0 0 18px;">
        Job reference: <strong>{{ $jobOpening->reference_no }}</strong><br>
        Closing date: <strong>{{ $jobOpening->closing_date?->format('d M Y') }}</strong>
    </p>

    <p style="margin:0 0 18px;">
        If you remain interested, please submit a new application before the closing date:
        <a href="{{ route('careers.apply', $jobOpening->slug) }}" style="color:#2563eb;">Apply for this vacancy</a>.
    </p>

    <p style="margin:0 0 18px;">
        Kind regards,<br>
        Right to Care Zambia People &amp; Culture Team
    </p>

    <p style="margin:0; color:#6b7280;">
        This email box is not monitored. Please do not reply to this message.
    </p>
@endsection
