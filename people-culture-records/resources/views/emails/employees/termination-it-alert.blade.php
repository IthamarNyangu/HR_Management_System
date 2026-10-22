@extends('emails.layout', [
    'title' => 'Employee termination notice',
    'preheader' => 'Early IT access-review notice for a terminated employee.',
])

@section('content')
    <p style="margin:0 0 18px;">Dear {{ $recipientName }},</p>

    <p style="margin:0 0 18px;">
        This is an early notification that the employment status of
        <strong>{{ $employee->full_name }}</strong> has been changed to <strong>Terminated</strong>
        in the People &amp; Culture Records Management System.
    </p>

    <p style="margin:0 0 18px;">
        Please review the employee's organisational systems, accounts, devices, and access rights,
        and prepare any actions required for offboarding.
    </p>

    <p style="margin:0 0 18px;">
        Employee number: <strong>{{ $employee->employee_no }}</strong><br>
        Job title: <strong>{{ $employee->jobTitle?->name ?? 'Not recorded' }}</strong><br>
        Department: <strong>{{ $employee->department?->name ?? 'Not recorded' }}</strong><br>
        Location: <strong>{{ collect([$employee->province?->name, $employee->district?->name, $employee->facility?->name])->filter()->implode(' / ') ?: 'Not recorded' }}</strong><br>
        Line manager: <strong>{{ $employee->supervisor?->full_name ?? $employee->supervisor_name ?? 'Not recorded' }}</strong><br>
        Last working date: <strong>{{ $employee->termination_date?->format('d M Y') ?? 'Not recorded' }}</strong>
    </p>

    <p style="margin:0 0 18px;">
        <a href="{{ route('employees.show', $employee) }}" style="color:#2563eb;">
            View employee record
        </a>
    </p>

    <p style="margin:0 0 18px;">
        This notice is for early action and awareness. It does not confirm that the employee's
        ExitClear clearance has been initiated or completed.
    </p>

    <p style="margin:0 0 18px;">
        Kind regards,<br>
        Right to Care Zambia People &amp; Culture Team
    </p>

    <p style="margin:0; color:#6b7280;">
        This email box is not monitored. Please do not reply to this message.
    </p>
@endsection
