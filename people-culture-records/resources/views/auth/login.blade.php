<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login - {{ config('app.name') }}</title>
    <link rel="icon" type="image/png" href="{{ asset('images/right-to-care-zambia-logo-transparent.png') }}">
    <link rel="shortcut icon" type="image/png" href="{{ asset('images/right-to-care-zambia-logo-transparent.png') }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root { --rtcz-red: #c8102e; --rtcz-red-dark: #9f1028; --navy: #12233f; }
        * { box-sizing: border-box; }
        html, body { width: 100%; height: 100%; min-height: 100%; overflow: hidden; overscroll-behavior: none; }
        html { overflow-x: hidden; background: var(--navy); }
        body { margin: 0; background: #f4f6f6; color: #101828; }
        .login-page { width: 100%; height: 100vh; height: 100dvh; overflow: hidden; display: grid; grid-template-columns: 1.15fr .85fr; }
        .login-identity { position: relative; height: 100%; overflow: hidden; padding: clamp(32px, 4.5vw, 72px); color: #fff; background: radial-gradient(circle at 85% 14%, rgba(200,16,46,.31), transparent 34%), linear-gradient(145deg, #111f38 0%, #172945 68%, #841426 145%); }
        .identity-message { position: relative; z-index: 2; display: flex; justify-content: center; padding-top: clamp(16px, 4vh, 42px); text-align: center; }
        .identity-message h1 { max-width: 690px; margin: 0; font-size: clamp(39px, 4.25vw, 61px); line-height: 1.03; letter-spacing: -2.4px; font-weight: 750; }
        .identity-illustration { position: absolute; z-index: 3; width: min(89%, 850px); max-height: 72vh; object-fit: contain; object-position: center bottom; left: 50%; bottom: 2%; transform: translateX(-50%); filter: drop-shadow(0 24px 30px rgba(4,14,30,.2)); }
        .login-form-area { min-width: 0; display: grid; place-items: center; padding: 32px; background: #f4f6f6; }
        .login-card { width: min(520px, 100%); padding: clamp(32px, 4vw, 48px); border: 1px solid #e1e6e4; border-radius: 22px; background: #fff; box-shadow: 0 22px 65px rgba(15,30,53,.13); }
        .form-brand { display: grid; justify-items: center; gap: 7px; margin-bottom: 22px; text-align: center; }
        .form-brand img { width: 82px; height: 62px; object-fit: contain; }
        .form-brand strong { color: #18243a; font-size: 19px; line-height: 1.2; letter-spacing: -.35px; }
        .form-brand span { color: #667085; font-size: 11px; }
        .login-card .eyebrow { margin: 0; color: #08733e; font-size: 11px; font-weight: 800; letter-spacing: 1.25px; }
        .login-card h2 { margin: 10px 0 8px; font-size: 32px; line-height: 1.15; letter-spacing: -1px; font-weight: 750; }
        .login-card .intro { margin: 0 0 30px; color: #667085; line-height: 1.55; }
        .form-label { color: #344054; font-size: 13px; font-weight: 700; }
        .form-control { min-height: 50px; border-color: #d0d8d4; border-radius: 9px; }
        .form-control:focus { border-color: var(--rtcz-red); box-shadow: 0 0 0 3px rgba(200,16,46,.12); }
        .form-check-input:checked { border-color: var(--rtcz-red); background-color: var(--rtcz-red); }
        .forgot-link { color: #08733e; font-weight: 650; text-decoration: none; }
        .forgot-link:hover { color: #055c31; text-decoration: underline; }
        .btn { display: inline-flex; align-items: center; justify-content: center; gap: 8px; min-height: 50px; padding: 0 16px; border-radius: 9px; font-size: 15px; font-weight: 700; line-height: 1; white-space: nowrap; cursor: pointer; border: 1px solid transparent; transition: background-color .15s ease, border-color .15s ease, box-shadow .15s ease; }
        .btn-primary { background: var(--rtcz-red) !important; border-color: var(--rtcz-red) !important; color: #fff !important; }
        .btn-primary:hover, .btn-primary:focus { background: var(--rtcz-red-dark) !important; border-color: var(--rtcz-red-dark) !important; }
        .btn:focus-visible { outline: 2px solid transparent; outline-offset: 2px; box-shadow: 0 0 0 3px rgba(200,16,46,.22); }
        .required-field-label::after { content: " *"; color: var(--rtcz-red); font-weight: 700; }
        .alert { border-radius: 9px; font-size: 13px; }
        @media (max-width: 820px) {
            html, body { height: auto; overflow-y: auto; }
            .login-page { grid-template-columns: 1fr; }
            .login-identity { min-height: 500px; padding: 32px 28px; }
            .identity-message { padding-top: 12px; }
            .identity-message h1 { max-width: 390px; font-size: 42px; }
            .identity-illustration { width: min(86%, 520px); max-height: 66%; left: 50%; }
            .login-form-area { padding: 30px 20px; }
        }
    </style>
</head>
<body>
    <main class="login-page">
        <section class="login-identity">
            <div class="identity-message"><h1>Manage employee records with confidence.</h1></div>
            <img src="{{ asset('images/people-culture-login-illustration.png') }}" alt="People and Culture employee organising workforce records" class="identity-illustration">
        </section>

        <section class="login-form-area">
            <div class="login-card">
                <div class="form-brand">
                    <img src="{{ asset('images/right-to-care-zambia-logo-transparent.png') }}" alt="Right to Care Zambia">
                    <strong>People &amp; Culture</strong>
                    <span>Records Management System</span>
                </div>
                <p class="eyebrow mb-4">SECURE SIGN IN</p>

                @if ($errors->any())
                    <div class="alert alert-danger">{{ $errors->first() }}</div>
                @endif

                <form method="POST" action="{{ route('login') }}">
                    @csrf
                    <div class="mb-3">
                        <label for="email" class="form-label required-field-label">Email address</label>
                        <input id="email" name="email" type="email" value="{{ old('email') }}" class="form-control" required autofocus autocomplete="email">
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label required-field-label">Password</label>
                        <input id="password" name="password" type="password" class="form-control" required autocomplete="current-password">
                    </div>
                    <div class="d-flex justify-content-between align-items-center gap-3 mb-4">
                        <div class="form-check mb-0">
                            <input id="remember" name="remember" type="checkbox" class="form-check-input">
                            <label for="remember" class="form-check-label">Remember me</label>
                        </div>
                        <a href="{{ route('password.request') }}" class="small forgot-link">Forgot password?</a>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Sign in</button>
                </form>
            </div>
        </section>
    </main>
</body>
</html>
