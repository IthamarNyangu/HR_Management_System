@extends('emails.layout', [
    'title' => 'Reset your HRMS password',
    'preheader' => 'Use this secure link to reset your People & Culture Records password.',
])

@section('content')
    <p style="margin:0 0 18px;"><strong>Hi {{ $user->name }},</strong></p>

    <p style="margin:0 0 18px;">
        We received a request to reset the password for your People &amp; Culture Records Management System account.
    </p>

    <p style="margin:0 0 26px;">
        Click the button below to set a new password. This link will expire in {{ $expiresInMinutes }} minutes.
    </p>

    <p style="margin:0 0 28px; text-align:center;">
        <a href="{{ $resetUrl }}" style="display:inline-block; background:#2563eb; color:#ffffff; text-decoration:none; font-weight:700; border-radius:6px; padding:13px 22px;">
            Reset Password
        </a>
    </p>

    <p style="margin:0 0 18px;">
        If you did not request this password reset, you can safely ignore this email.
    </p>

    <p style="margin:0; color:#6b7280; font-size:13px;">
        If the button does not work, copy and paste this link into your browser:<br>
        <a href="{{ $resetUrl }}" style="color:#2563eb; word-break:break-all;">{{ $resetUrl }}</a>
    </p>
@endsection
