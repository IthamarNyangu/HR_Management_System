<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Application Withdrawn - Right to Care Zambia</title>
    <link rel="icon" type="image/png" href="{{ asset('images/right-to-care-zambia-logo-transparent.png') }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>body { background: #f7f8fb; color: #172033; } .panel { background: #fff; border: 1px solid #e1e7f0; border-radius: .5rem; } .career-logo { width: 58px; height: 58px; object-fit: contain; }</style>
</head>
<body>
    <main class="container py-5">
        <section class="panel p-4">
            <div class="d-flex align-items-center gap-3 mb-4">
                <img src="{{ asset('images/right-to-care-zambia-logo-transparent.png') }}" alt="Right to Care Zambia" class="career-logo">
                <div>
                    <div class="fw-semibold">Right to Care Zambia</div>
                    <div class="small text-muted">Careers</div>
                </div>
            </div>
            <h1 class="h3">Application withdrawn</h1>
            <p class="text-muted">Application {{ $jobApplication->reference_no }} has been withdrawn.</p>
            <a href="{{ route('careers.index') }}" class="btn btn-danger">Back to careers</a>
        </section>
    </main>
</body>
</html>
