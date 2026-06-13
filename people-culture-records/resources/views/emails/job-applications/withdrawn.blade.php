@extends('emails.layout', [
    'title' => 'Application withdrawn',
    'preheader' => 'Your application withdrawal has been recorded by Right to Care Zambia.',
])

@section('content')
    <p style="margin:0 0 18px;">Dear {{ $application->first_name }},</p>

    <p style="margin:0 0 18px;">
        Thank you for applying for the {{ $application->jobOpening?->title }} role at Right to Care Zambia.
        You have told us that you no longer wish to be considered for this opportunity.
    </p>

    <p style="margin:0 0 18px;">
        If you would like to read more about Right to Care Zambia or explore future opportunities, please visit
        <a href="{{ route('careers.index') }}" style="color:#2563eb;">{{ route('careers.index') }}</a>
        or follow us on
        <a href="https://www.linkedin.com/company/right-to-care-zambia" style="color:#2563eb;">LinkedIn</a>.
    </p>

    <p style="margin:0 0 18px;">
        All the best in your future endeavours.
    </p>

    <p style="margin:0;">
        Kind regards,<br>
        Right to Care Zambia People &amp; Culture Team
    </p>
@endsection
