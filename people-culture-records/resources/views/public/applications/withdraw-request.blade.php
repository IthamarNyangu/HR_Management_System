<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Withdraw Application - Right to Care Zambia</title>
    <link rel="icon" type="image/png" href="{{ asset('images/right-to-care-zambia-logo-transparent.png') }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            min-height: 100vh;
            background:
                linear-gradient(120deg, rgba(247, 248, 251, .96), rgba(247, 248, 251, .88)),
                url("{{ asset('images/career-opportunity-rtcz.jpeg') }}") center/cover fixed;
            color: #172033;
        }
        .panel { background: rgba(255,255,255,.96); border: 1px solid #e1e7f0; border-radius: .75rem; box-shadow: 0 18px 50px rgba(15,23,42,.10); }
        .career-logo { width: 58px; height: 58px; object-fit: contain; }
        .required::after { content: " *"; color: #c01818; font-weight: 700; }
        .visually-hidden-field { position: absolute; left: -9999px; opacity: 0; }
    </style>
</head>
<body>
    <main class="container py-5">
        <section class="panel p-4 p-md-5 mx-auto" style="max-width: 860px;">
            <div class="d-flex align-items-center gap-3 mb-4">
                <img src="{{ asset('images/right-to-care-zambia-logo-transparent.png') }}" alt="Right to Care Zambia" class="career-logo">
                <div>
                    <div class="fw-semibold">Right to Care Zambia</div>
                    <div class="small text-muted">Careers</div>
                </div>
            </div>
            <h1 class="h3">Request withdrawal link</h1>
            <p class="text-muted">We understand that plans can change. Enter your application reference and email address, and if they match our records we will send a secure withdrawal link.</p>

            @if (session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            <form method="POST" action="{{ route('applications.withdraw.link') }}" class="row g-3">
                @csrf
                <input type="text" name="company_website" value="" tabindex="-1" autocomplete="off" class="visually-hidden-field">
                <div class="col-md-6">
                    <label for="reference_no" class="form-label required">Application Reference</label>
                    <input id="reference_no" type="text" name="reference_no" value="{{ old('reference_no') }}" class="form-control @error('reference_no') is-invalid @enderror" required>
                    @error('reference_no') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-6">
                    <label for="email" class="form-label required">Email</label>
                    <input id="email" type="email" name="email" value="{{ old('email') }}" class="form-control @error('email') is-invalid @enderror" required>
                    @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-12 d-flex gap-2">
                    <a href="{{ route('careers.index') }}" class="btn btn-outline-secondary">Back to careers</a>
                    <button type="submit" class="btn btn-danger">Send withdrawal link</button>
                </div>
            </form>
        </section>
    </main>
</body>
</html>
