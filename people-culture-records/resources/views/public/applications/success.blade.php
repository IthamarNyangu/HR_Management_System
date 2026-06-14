<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Application Submitted - Right to Care Zambia</title>
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
        <section class="panel p-4 p-md-5 mx-auto" style="max-width: 840px;">
            <div class="d-flex align-items-center gap-3 mb-4">
                <img src="{{ asset('images/right-to-care-zambia-logo-transparent.png') }}" alt="Right to Care Zambia" class="career-logo">
                <div>
                    <div class="fw-semibold">Right to Care Zambia</div>
                    <div class="small text-muted">Careers</div>
                </div>
            </div>
            <h1 class="h3">Application submitted</h1>
            <p class="mb-2">Thank you, {{ $application->full_name }}. Your application reference is <strong>{{ $application->reference_no }}</strong>.</p>
            <p class="text-muted">A confirmation email {{ $mailSent ? 'has been sent' : 'could not be sent locally, but your application was saved successfully' }}.</p>
            <div class="d-flex flex-wrap gap-2 mt-4">
                <a href="{{ route('careers.index') }}" class="btn btn-danger">Back to careers</a>
                <a href="{{ route('applications.withdraw.request') }}" class="btn btn-outline-secondary">Need to withdraw later?</a>
            </div>
        </section>
    </main>
</body>
</html>
