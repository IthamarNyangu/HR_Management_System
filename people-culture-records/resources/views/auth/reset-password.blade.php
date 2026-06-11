<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Set New Password - {{ config('app.name') }}</title>
    <link rel="icon" type="image/png" href="{{ asset('images/right-to-care-zambia-logo.png') }}">
    <link rel="shortcut icon" type="image/png" href="{{ asset('images/right-to-care-zambia-logo.png') }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #eef2f7; min-height: 100vh; }
        .auth-card { max-width: 460px; border: 1px solid #e1e6ef; border-radius: .5rem; }
        .auth-logo { width: 190px; height: auto; }
        .btn { display: inline-flex; align-items: center; justify-content: center; gap: 8px; height: 40px; padding: 0 16px; border-radius: 6px; font-size: 15px; font-weight: 500; line-height: 1; white-space: nowrap; cursor: pointer; border: 1px solid transparent; text-align: center; transition: background-color .15s ease, border-color .15s ease, color .15s ease, box-shadow .15s ease; }
        .btn-md { height: 40px !important; padding: 0 16px !important; font-size: 15px !important; }
        .btn-primary { background: #2563eb !important; border-color: #2563eb !important; color: #fff !important; }
        .btn-primary:hover, .btn-primary:focus { background: #1d4ed8 !important; border-color: #1d4ed8 !important; color: #fff !important; }
        .required-field-label::after { content: " *"; color: #dc2626; font-weight: 700; }
    </style>
</head>
<body class="d-flex align-items-center">
    <main class="container">
        <div class="auth-card bg-white shadow-sm mx-auto p-4">
            <div class="mb-4 text-center">
                <img src="{{ asset('images/right-to-care-zambia-logo.png') }}" alt="right to care zambia logo" class="auth-logo mb-3">
                <h1 class="h4 mb-1">Set New Password</h1>
                <p class="text-muted mb-0">Choose a strong password for your HRMS account.</p>
            </div>

            @if ($errors->any())
                <div class="alert alert-danger">{{ $errors->first() }}</div>
            @endif

            <form method="POST" action="{{ route('password.update') }}">
                @csrf

                <input type="hidden" name="token" value="{{ $request->route('token') }}">

                <div class="mb-3">
                    <label for="email" class="form-label required-field-label">Email address</label>
                    <input id="email" name="email" type="email" value="{{ old('email', $request->email) }}" class="form-control" required autofocus autocomplete="email">
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label required-field-label">New Password</label>
                    <input id="password" name="password" type="password" class="form-control" required autocomplete="new-password">
                </div>

                <div class="mb-4">
                    <label for="password_confirmation" class="form-label required-field-label">Confirm New Password</label>
                    <input id="password_confirmation" name="password_confirmation" type="password" class="form-control" required autocomplete="new-password">
                </div>

                <button type="submit" class="btn btn-primary btn-md w-100">Update Password</button>
            </form>
        </div>
    </main>
</body>
</html>
