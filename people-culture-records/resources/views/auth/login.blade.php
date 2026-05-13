<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login - {{ config('app.name') }}</title>
    <link rel="icon" type="image/png" href="{{ asset('images/RTCZ.png') }}">
    <link rel="shortcut icon" type="image/png" href="{{ asset('images/RTCZ.png') }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #eef2f7; min-height: 100vh; }
        .login-card { max-width: 440px; border: 1px solid #e1e6ef; border-radius: .5rem; }
        .login-logo { width: 148px; height: auto; }
        .btn { display: inline-flex; align-items: center; justify-content: center; gap: 8px; height: 40px; padding: 0 16px; border-radius: 6px; font-size: 15px; font-weight: 500; line-height: 1; white-space: nowrap; cursor: pointer; border: 1px solid transparent; text-align: center; transition: background-color .15s ease, border-color .15s ease, color .15s ease, box-shadow .15s ease; }
        .btn-md { height: 40px !important; padding: 0 16px !important; font-size: 15px !important; }
        .btn-primary { background: #2563eb !important; border-color: #2563eb !important; color: #fff !important; }
        .btn-primary:hover, .btn-primary:focus { background: #1d4ed8 !important; border-color: #1d4ed8 !important; color: #fff !important; }
        .btn:focus-visible { outline: 2px solid transparent; outline-offset: 2px; box-shadow: 0 0 0 3px rgba(37, 99, 235, .28); }
    </style>
</head>
<body class="d-flex align-items-center">
    <main class="container">
        <div class="login-card bg-white shadow-sm mx-auto p-4">
            <div class="mb-4 text-center">
                <img src="{{ asset('images/RTCZ.png') }}" alt="right to care zambia logo" class="login-logo mb-3">
                <h1 class="h4 mb-1">People & Culture Records</h1>
                <p class="text-muted mb-0">Sign in to continue.</p>
            </div>

            @if ($errors->any())
                <div class="alert alert-danger">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}">
                @csrf

                <div class="mb-3">
                    <label for="email" class="form-label">Email address</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" class="form-control" required autofocus autocomplete="email">
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input id="password" name="password" type="password" class="form-control" required autocomplete="current-password">
                </div>

                <div class="form-check mb-4">
                    <input id="remember" name="remember" type="checkbox" class="form-check-input">
                    <label for="remember" class="form-check-label">Remember me</label>
                </div>

                <button type="submit" class="btn btn-primary btn-md w-100">Login</button>
            </form>
        </div>
    </main>
</body>
</html>
