<p>Dear {{ $application->full_name }},</p>

<p>Thank you for applying for <strong>{{ $application->jobOpening?->title }}</strong>.</p>

<p>
    <strong>Application reference:</strong> {{ $application->reference_no }}<br>
    <strong>Closing date:</strong> {{ $application->jobOpening?->closing_date?->format('d M Y') }}
</p>

<p>You can withdraw your application using this secure link:</p>
<p><a href="{{ $withdrawalUrl }}">{{ $withdrawalUrl }}</a></p>

<p>If you do not hear from us within 4 weeks after the closing date, please consider your application unsuccessful.</p>

<p>Regards,<br>Right to Care Zambia People &amp; Culture</p>
