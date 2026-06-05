<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $jobOpening->title }} - Right to Care Zambia Careers</title>
    <link rel="icon" type="image/png" href="{{ asset('images/RTCZ.png') }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f7f8fb; color: #172033; }
        .career-header, .job-panel { background: #fff; border: 1px solid #e1e7f0; border-radius: .5rem; }
        .career-logo { width: 46px; height: 46px; object-fit: contain; }
        .section-title { color: #c01818; font-size: 1.05rem; font-weight: 700; margin-bottom: .7rem; }
        .text-pre-line { white-space: pre-line; }
    </style>
</head>
<body>
    <main class="container py-5">
        <header class="career-header p-4 mb-4">
            <div class="d-flex align-items-center gap-3 mb-4">
                <img src="{{ asset('images/RTCZ.png') }}" alt="Right to Care Zambia" class="career-logo">
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
        </header>

        <article class="job-panel p-4">
            @foreach ([
                'description' => 'Description',
                'responsibilities' => 'Responsibilities',
                'requirements' => 'Requirements',
                'qualifications' => 'Qualifications',
                'experience_required' => 'Experience Required',
                'contract_details' => 'Contract Details',
                'work_level' => 'Work Level',
                'location_details' => 'Location Details',
                'application_instructions' => 'Application Instructions',
            ] as $field => $label)
                @if (filled($jobOpening->{$field}))
                    <section class="mb-4">
                        <h2 class="section-title">{{ $label }}</h2>
                        <div class="text-pre-line">{{ $jobOpening->{$field} }}</div>
                    </section>
                @endif
            @endforeach
        </article>
    </main>
</body>
</html>
