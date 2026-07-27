@extends('emails.layout', [
    'title' => 'Temporary appointment ending soon',
    'preheader' => $daysRemaining.' days remain on a temporary appointment.',
])

@section('content')
    @php
        $isActingPromotion = $appointment->staffPromotion?->is_acting_promotion;
        $recordLabel = $isActingPromotion ? 'acting promotion' : 'temporary appointment';
    @endphp

    <p style="margin:0 0 18px;">Dear {{ $recipientName }},</p>

    <p style="margin:0 0 18px;">
        The {{ $recordLabel }} for <strong>{{ $appointment->employee?->full_name }}</strong>
        as <strong>{{ $appointment->temporaryJobTitle?->name }}</strong> is ending soon.
    </p>

    <p style="margin:0 0 18px;">
        Reference: <strong>{{ $appointment->reference_no }}</strong><br>
        End date: <strong>{{ $appointment->end_date?->format('d M Y') }}</strong><br>
        Days remaining: <strong>{{ $daysRemaining }}</strong>
    </p>

    @if ($canManageAppointment)
        <p style="margin:0 0 18px;">
            If the appointment should continue, review it in the HRMS and record an extension before the end date.
            Otherwise, no action is required.
        </p>

        <p style="margin:0 0 18px;">
            <a href="{{ route('temporary-appointments.show', $appointment) }}" style="color:#2563eb;">
                View temporary appointment
            </a>
        </p>
    @else
        <p style="margin:0 0 18px;">
            This message is for your awareness as the employee's line manager. Please contact the People &amp; Culture team if follow-up is required.
        </p>
    @endif

    <p style="margin:0 0 18px;">
        Kind regards,<br>
        Right to Care Zambia People &amp; Culture Team
    </p>

    <p style="margin:0; color:#6b7280;">
        This email box is not monitored. Please do not reply to this message.
    </p>
@endsection
