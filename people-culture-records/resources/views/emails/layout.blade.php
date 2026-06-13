<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? config('app.name') }}</title>
</head>
<body style="margin:0; padding:0; background:#ffffff; color:#202124; font-family:Arial, Helvetica, sans-serif;">
    <div style="display:none; max-height:0; overflow:hidden; opacity:0;">
        {{ $preheader ?? 'People & Culture Records Management System notification.' }}
    </div>

    <div style="max-width:980px; padding:32px 48px; font-size:15px; line-height:1.6;">
        @yield('content')

        <div style="margin-top:34px;">
            <img src="{{ $message->embed(public_path('images/right-to-care-zambia-logo-transparent.png')) }}" alt="Right to Care Zambia" width="170" style="display:block; width:170px; max-width:45%; height:auto; border:0;">
        </div>

        <div style="margin-top:18px; color:#6b7280; font-size:12px; line-height:1.5;">
            <div>People &amp; Culture Records Management System</div>
            <div>Copyright &copy; {{ now()->year }} Right to Care Zambia. All rights reserved.</div>
        </div>
    </div>

</body>
</html>
