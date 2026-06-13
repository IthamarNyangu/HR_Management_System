<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Confirm Withdrawal - Right to Care Zambia</title>
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
    </style>
</head>
<body>
    <main class="container py-5">
        <section class="panel p-4 p-md-5 mx-auto" style="max-width: 820px;">
            <div class="d-flex align-items-center gap-3 mb-4">
                <img src="{{ asset('images/right-to-care-zambia-logo-transparent.png') }}" alt="Right to Care Zambia" class="career-logo">
                <div>
                    <div class="fw-semibold">Right to Care Zambia</div>
                    <div class="small text-muted">Careers</div>
                </div>
            </div>
            <h1 class="h3">Withdraw application?</h1>
            <p>You are about to withdraw application <strong>{{ $jobApplication->reference_no }}</strong> for <strong>{{ $jobApplication->jobOpening?->title }}</strong>.</p>
            <p class="text-muted">Once confirmed, this application will no longer be considered for the role. You can still view future opportunities on our careers page.</p>
            <form method="POST" action="{{ URL::temporarySignedRoute('applications.withdraw.confirm', now()->addMinutes(30), ['jobApplication' => $jobApplication, 'token' => $token]) }}">
                @csrf
                <div class="d-flex gap-2">
                    <a href="{{ route('careers.index') }}" class="btn btn-outline-secondary">Cancel</a>
                    <button type="submit" class="btn btn-danger">Confirm Withdrawal</button>
                </div>
            </form>
        </section>
    </main>
</body>
</html>
