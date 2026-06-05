<p>Dear {{ $application->full_name }},</p>

<p>A withdrawal link was requested for application <strong>{{ $application->reference_no }}</strong>.</p>

<p><a href="{{ $withdrawalUrl }}">{{ $withdrawalUrl }}</a></p>

<p>If you did not request this link, you can ignore this email.</p>

<p>Regards,<br>Right to Care Zambia People &amp; Culture</p>
