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
        .career-shell { max-width: 1040px; }
        .career-logo { width: 82px; height: 82px; object-fit: contain; }
        .job-panel { background: #fff; border: 1px solid #e1e7f0; border-radius: .5rem; }
        .section-title {
            background: #e5e7eb;
            border: 1px solid #d1d5db;
            color: #c01818;
            font-size: 1.05rem;
            font-weight: 400;
            letter-spacing: .08em;
            margin-bottom: 1rem;
            padding: .6rem .85rem;
            text-align: center;
            white-space: pre-wrap;
        }
        .position-label { font-weight: 600; }
    </style>
</head>
<body>
    <main class="container career-shell py-5">
        <div class="d-flex justify-content-between align-items-start gap-3 mb-4">
            <img src="{{ asset('images/right-to-care-zambia-logo-transparent.png') }}" alt="Right to Care Zambia" class="career-logo">
            <a href="{{ route('careers.index') }}" class="btn btn-outline-secondary btn-sm">Back to vacancies</a>
        </div>

        <header class="job-panel p-4 mb-4">
            <div class="small text-danger fw-semibold">{{ $jobOpening->reference_no }}</div>
            <h1 class="h2 mb-3">{{ $jobOpening->title }}</h1>
            <div class="row g-3 text-muted">
                <div class="col-md-4"><span class="position-label text-dark">Project:</span> {{ $jobOpening->project?->name ?? '-' }}</div>
                <div class="col-md-4"><span class="position-label text-dark">Location:</span> {{ $jobOpening->public_location_label }}</div>
                <div class="col-md-4"><span class="position-label text-dark">Closing:</span> {{ $jobOpening->closing_date?->format('d M Y') }}</div>
                @if ($jobOpening->show_number_of_positions && $jobOpening->number_of_positions)
                    <div class="col-md-4"><span class="position-label text-dark">Positions:</span> {{ $jobOpening->number_of_positions }}</div>
                @endif
            </div>
        </header>

        <article class="job-panel p-4">
            <section class="mb-4">
                <h2 class="section-title">A B O U T   U S</h2>
                <div>{{ App\Models\JobOpening::ABOUT_US_TEXT }}</div>
            </section>

            <section class="mb-4">
                <h2 class="section-title">A B O U T   T H E   P O S I T I O N</h2>
                <div class="row g-3">
                    <div class="col-md-4"><span class="position-label">Request to Hire No.:</span> {{ $jobOpening->reference_no }}</div>
                    <div class="col-md-4"><span class="position-label">Date advertised:</span> {{ $jobOpening->opening_date?->format('d M Y') ?? '-' }}</div>
                    <div class="col-md-4"><span class="position-label">Closing date:</span> {{ $jobOpening->closing_date?->format('d M Y') ?? '-' }}</div>
                    <div class="col-md-4"><span class="position-label">Position:</span> {{ $jobOpening->title }}</div>
                    <div class="col-md-4"><span class="position-label">Location:</span> {{ $jobOpening->public_location_label }}</div>
                    @if ($jobOpening->show_number_of_positions && $jobOpening->number_of_positions)
                        <div class="col-md-4"><span class="position-label">No. of Vacancies:</span> {{ $jobOpening->number_of_positions }}</div>
                    @endif
                    <div class="col-md-4"><span class="position-label">Contract duration:</span> {{ $jobOpening->contract_duration ?: '-' }}</div>
                    <div class="col-md-4"><span class="position-label">Contract type:</span> {{ $jobOpening->employmentType?->name ?? '-' }}</div>
                    <div class="col-md-4"><span class="position-label">Job grade:</span> {{ $jobOpening->job_grade ?: '-' }}</div>
                    <div class="col-md-4"><span class="position-label">Reporting to:</span> {{ $jobOpening->reporting_to_label }}</div>
                    <div class="col-md-4"><span class="position-label">Contact email:</span> {{ $jobOpening->announcement_contact_email }}</div>
                    <div class="col-md-4"><span class="position-label">Contact Person:</span> People & Culture Department</div>
                </div>
            </section>

            @foreach (App\Models\JobOpening::ANNOUNCEMENT_SECTIONS as $field => $label)
                @php($lines = $jobOpening->linesFor($field))
                <section class="mb-4">
                    <h2 class="section-title">{{ $label }}</h2>
                    @if (count($lines) > 0)
                        <ul class="mb-0">
                            @foreach ($lines as $line)
                                <li class="mb-2">{{ $line }}</li>
                            @endforeach
                        </ul>
                    @else
                        <div class="text-muted">-</div>
                    @endif
                </section>
            @endforeach

            <section class="mb-4">
                <h2 class="section-title">A P P L I C A T I O N   P R O C E D U R E</h2>
                <div>Applications must be submitted through the Right to Care Zambia careers portal not later than {{ $jobOpening->closing_date?->format('d M Y') ?? 'the closing date' }}.</div>
            </section>

            <div class="border-top pt-4 d-flex justify-content-end">
                <a href="{{ route('careers.apply', $jobOpening->slug) }}" class="btn btn-danger">Apply Now</a>
            </div>
        </article>
    </main>
</body>
</html>
