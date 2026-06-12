<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? config('app.name') }}</title>
</head>
<body style="margin:0; padding:0; background:#eaf0f6; color:#1f2937; font-family:Arial, Helvetica, sans-serif;">
    <div style="display:none; max-height:0; overflow:hidden; opacity:0;">
        {{ $preheader ?? 'People & Culture Records Management System notification.' }}
    </div>

    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#eaf0f6; width:100%;">
        <tr>
            <td align="center" style="padding:40px 16px 24px;">
                <img src="{{ $message->embed(public_path('images/right-to-care-zambia-logo-transparent.png')) }}" alt="Right to Care Zambia" width="210" style="display:block; width:210px; max-width:70%; height:auto; border:0;">
            </td>
        </tr>
        <tr>
            <td align="center" style="padding:0 16px;">
                <table role="presentation" width="560" cellspacing="0" cellpadding="0" style="width:560px; max-width:100%; background:#ffffff; border-radius:8px; border:1px solid #dbe3ec;">
                    <tr>
                        <td style="padding:36px 40px; font-size:15px; line-height:1.6;">
                            @yield('content')
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td align="center" style="padding:28px 16px 16px; color:#6b7280; font-size:13px;">
                <div>People &amp; Culture Records Management System</div>
                <div style="margin-top:10px;">Copyright &copy; {{ now()->year }} Right to Care Zambia. All rights reserved.</div>
            </td>
        </tr>
    </table>

</body>
</html>
