@extends('emails.layout', [
    'title' => 'Vacancy withdrawn',
    'preheader' => 'An update on a Right to Care Zambia recruitment process.',
])

@section('content')
    <p style="margin:0 0 18px;">Dear {{ $application->first_name }},</p>

    <p style="margin:0 0 18px;">
        We regret to inform you that the recruitment process for the
        <strong>{{ $application->jobOpening?->title }}</strong>
        @if ($application->jobOpening?->job_grade)
            ({{ $application->jobOpening->job_grade }})
        @endif
        position at {{ $application->jobOpening?->location_label }} has been cancelled or withdrawn.
    </p>

    <p style="margin:0 0 18px;">
        Job reference: <strong>{{ $application->jobOpening?->reference_no }}</strong>.
    </p>

    <p style="margin:0 0 18px;">
        We regret any inconvenience this may cause. We encourage you to visit
        <a href="{{ route('careers.index') }}" style="color:#2563eb;">{{ route('careers.index') }}</a>
        to review other vacancies. We wish you all the best with any other ongoing applications you may have.
    </p>

    <p style="margin:0 0 18px;">
        Sincerely,<br>
        Right to Care Zambia People &amp; Culture Team
    </p>

    <p style="margin:0; color:#6b7280;">
        This email box is not monitored. Please do not reply to this message.
    </p>
@endsection
