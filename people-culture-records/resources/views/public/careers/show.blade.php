<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $jobOpening->title }} - Right to Care Zambia Careers</title>
    <link rel="icon" type="image/png" href="{{ asset('images/right-to-care-zambia-logo-transparent.png') }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f7f8fb; color: #172033; }
        .career-header, .job-panel { background: #fff; border: 1px solid #e1e7f0; border-radius: .5rem; }
        .career-logo { width: 58px; height: 58px; object-fit: contain; }
        .section-title { color: #c01818; font-size: 1.05rem; font-weight: 700; margin-bottom: .7rem; }
        .text-pre-line { white-space: pre-line; }
    </style>
</head>
<body>
    <main class="container py-5">
        <header class="career-header p-4 mb-4">
            <div class="d-flex align-items-center gap-3 mb-4">
                <img src="{{ asset('images/right-to-care-zambia-logo-transparent.png') }}" alt="Right to Care Zambia" class="career-logo">
                <div>
                    <div class="fw-semibold">Right to Care Zambia</div>
                    <div class="small text-muted">Careers</div>
                </div>
            </div>

            <a href="{{ route('careers.index') }}" class="small text-decoration-none">&larr; Back to vacancies</a>
            <div class="small text-danger fw-semibold mt-3">{{ $jobOpening->reference_no }}</div>
            <h1 class="h2 mb-3">{{ $jobOpening->title }}</h1>
            <div class="row g-3 text-muted">
                <div class="col-md-3"><span class="fw-semibold text-dark">Department:</span> {{ $jobOpening->department?->name ?? '-' }}</div>
                <div class="col-md-3"><span class="fw-semibold text-dark">Project:</span> {{ $jobOpening->project?->name ?? '-' }}</div>
                <div class="col-md-3"><span class="fw-semibold text-dark">Location:</span> {{ $jobOpening->location_label }}</div>
                <div class="col-md-3"><span class="fw-semibold text-dark">Closing:</span> {{ $jobOpening->closing_date?->format('d M Y') }}</div>
                @if ($jobOpening->show_number_of_positions && $jobOpening->number_of_positions)
                    <div class="col-md-3"><span class="fw-semibold text-dark">Positions:</span> {{ $jobOpening->number_of_positions }}</div>
                @endif
            </div>
            <div class="mt-4">
                <a href="{{ route('careers.apply', $jobOpening->slug) }}" class="btn btn-danger">Apply Now</a>
            </div>
        </header>

        <article class="job-panel p-4">
            <section class="mb-4">
                <h2 class="section-title">About Us</h2>
                <div>{{ App\Models\JobOpening::ABOUT_US_TEXT }}</div>
            </section>

            <section class="mb-4">
                <h2 class="section-title">About the Position</h2>
                <div class="row g-3">
                    <div class="col-md-4"><span class="fw-semibold">Request to Hire No.:</span> {{ $jobOpening->reference_no }}</div>
                    <div class="col-md-4"><span class="fw-semibold">Date advertised:</span> {{ $jobOpening->opening_date?->format('d M Y') ?? '-' }}</div>
                    <div class="col-md-4"><span class="fw-semibold">Closing date:</span> {{ $jobOpening->closing_date?->format('d M Y') ?? '-' }}</div>
                    <div class="col-md-4"><span class="fw-semibold">Position:</span> {{ $jobOpening->title }}</div>
                    <div class="col-md-4"><span class="fw-semibold">Location:</span> {{ $jobOpening->location_label }}</div>
                    @if ($jobOpening->show_number_of_positions && $jobOpening->number_of_positions)
                        <div class="col-md-4"><span class="fw-semibold">No. of Vacancies:</span> {{ $jobOpening->number_of_positions }}</div>
                    @endif
                    <div class="col-md-4"><span class="fw-semibold">Contract duration:</span> {{ $jobOpening->contract_duration ?: '-' }}</div>
                    <div class="col-md-4"><span class="fw-semibold">Contract type:</span> {{ $jobOpening->employmentType?->name ?? '-' }}</div>
                    <div class="col-md-4"><span class="fw-semibold">Job grade:</span> {{ $jobOpening->job_grade ?: '-' }}</div>
                    <div class="col-md-4"><span class="fw-semibold">Reporting to:</span> {{ $jobOpening->reporting_to_label }}</div>
                    <div class="col-md-4"><span class="fw-semibold">Contact email:</span> {{ $jobOpening->announcement_contact_email }}</div>
                    <div class="col-md-4"><span class="fw-semibold">Contact Person:</span> People & Culture Department</div>
                </div>
            </section>

            @foreach (App\Models\JobOpening::ANNOUNCEMENT_SECTIONS as $field => $label)
                @php($lines = $jobOpening->linesFor($field))
                <section class="mb-4">
                    <h2 class="section-title">{{ str($label)->replace('  ', ' ') }}</h2>
                    @if (count($lines) > 0)
                        <ul class="mb-0">
                            @foreach ($lines as $line)
                                <li>{{ $line }}</li>
                            @endforeach
                        </ul>
                    @else
                        <div class="text-muted">-</div>
                    @endif
                </section>
            @endforeach

            <section class="mb-4">
                <h2 class="section-title">Application Procedure</h2>
                <div>Applications must be submitted through the Right to Care Zambia careers portal.</div>
            </section>
        </article>
    </main>
</body>
</html>
