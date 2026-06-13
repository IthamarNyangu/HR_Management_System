@extends('emails.layout', [
    'title' => 'Application outcome',
    'preheader' => 'An update on your Right to Care Zambia application.',
])

@section('content')
    <p style="margin:0 0 18px;">Dear {{ $application->first_name }},</p>

    <p style="margin:0 0 18px;">
        Thank you for taking the time to apply for the {{ $application->jobOpening?->title }} role with Right to Care Zambia.
    </p>

    <p style="margin:0 0 18px;">
        Unfortunately, your application for this role will not be progressed further.
    </p>

    <p style="margin:0 0 18px;">
        While we know this may not be the outcome you were hoping for, we sincerely believe this is not the end of the road, just a step toward the right opportunity for you.
    </p>

    <p style="margin:0 0 18px;">
        We encourage you to stay connected and explore future opportunities with Right to Care Zambia. Please visit
        <a href="{{ route('careers.index') }}" style="color:#2563eb;">{{ route('careers.index') }}</a>
        to check our current vacancies.
    </p>

    <p style="margin:0 0 18px;">
        You can also follow us on
        <a href="https://www.linkedin.com/company/right-to-care-zambia" style="color:#2563eb;">LinkedIn</a>
        and
        <a href="https://web.facebook.com/p/Right-to-Care-Zambia-61559119114255/" style="color:#2563eb;">Facebook</a>.
    </p>

    <p style="margin:0 0 18px;">
        Kind regards,<br>
        Right to Care Zambia People &amp; Culture
    </p>

    <p style="margin:0; color:#6b7280;">
        This email box is not monitored. Please do not reply to this message.
    </p>
@endsection
