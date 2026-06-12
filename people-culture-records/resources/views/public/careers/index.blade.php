<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Careers - Right to Care Zambia</title>
    <link rel="icon" type="image/png" href="{{ asset('images/right-to-care-zambia-logo-transparent.png') }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f7f8fb; color: #172033; }
        .career-header { background: #fff; border-bottom: 1px solid #e7eaf0; }
        .career-logo { width: 82px; height: 82px; object-fit: contain; }
        .job-card { border: 1px solid #e1e7f0; border-radius: .5rem; background: #fff; transition: border-color .15s ease, box-shadow .15s ease; }
        .job-card:hover { border-color: #b8c7dd; box-shadow: 0 .6rem 1.4rem rgba(23, 32, 51, .08); }
        .job-action { min-width: 118px; }
        .kpa-snippet {
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .text-danger { color: #c01818 !important; }
    </style>
</head>
<body>
    <header class="career-header">
        <div class="container py-3 text-center">
            <img src="{{ asset('images/right-to-care-zambia-logo-transparent.png') }}" alt="Right to Care Zambia" class="career-logo">
        </div>
    </header>

    <main class="container py-5">
        <div class="mb-4 text-center">
            <h1 class="display-5 fw-semibold mb-2">Careers</h1>
            <p class="text-muted mb-0">Published external opportunities currently open for applications.</p>
        </div>

        <div class="d-flex flex-column gap-3">
            @forelse ($jobs as $job)
                <article class="job-card p-4">
                    <div class="d-flex flex-column flex-lg-row justify-content-between gap-3">
                        <div>
                            <div class="small text-danger fw-semibold">{{ $job->reference_no }}</div>
                            <h2 class="h5 mb-2">
                                <a href="{{ route('careers.show', $job->slug) }}" class="text-decoration-none">{{ $job->title }}</a>
                            </h2>
                            <div class="text-muted kpa-snippet">
                                {{ collect($job->linesFor('responsibilities'))->take(3)->implode(' ') ?: ($job->summary ?? 'View details for key performance areas and role requirements.') }}
                            </div>
                            <div class="small text-muted mt-3">{{ $job->public_location_label }}</div>
                        </div>
                        <div class="text-lg-end flex-shrink-0">
                            <div class="small text-muted">Closing date</div>
                            <div class="fw-semibold">{{ $job->closing_date?->format('d M Y') }}</div>
                            <div class="d-flex flex-lg-column gap-2 align-items-lg-end mt-3">
                                <a href="{{ route('careers.show', $job->slug) }}" class="btn btn-outline-danger btn-sm job-action">View Details</a>
                                <a href="{{ route('careers.apply', $job->slug) }}" class="btn btn-danger btn-sm job-action">Apply Now</a>
                            </div>
                        </div>
                    </div>
                </article>
            @empty
                <div class="job-card p-4 text-muted">No public vacancies are currently available.</div>
            @endforelse
        </div>

        <div class="mt-4">
            {{ $jobs->links() }}
        </div>
    </main>
</body>
</html>
