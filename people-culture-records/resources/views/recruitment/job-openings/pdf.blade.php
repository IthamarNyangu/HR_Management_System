<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $jobOpening->vacancy_announcement_title }}</title>
    <style>
        @page { margin: 30px 38px 58px; }
        body {
            font-family: "Calibri Light", Calibri, DejaVu Sans, sans-serif;
            color: #111827;
            font-size: 12px;
            line-height: 1.45;
        }
        .footer {
            position: fixed;
            left: 0;
            right: 0;
            bottom: -38px;
            height: 34px;
            color: #6b7280;
            font-size: 9px;
        }
        .footer-logo {
            position: absolute;
            right: 0;
            bottom: -2px;
            height: 48px;
            width: auto;
        }
        .hero { text-align: center; margin-bottom: 18px; }
        /* Adjust these two values when HR wants the header image larger or smaller. */
        .hero img { max-width: 100%; max-height: 340px; object-fit: contain; }
        .section-title {
            background: #e5e7eb;
            border: 1px solid #d1d5db;
            color: #b42318;
            text-align: center;
            font-size: 14px;
            font-weight: 400;
            padding: 7px 10px;
            margin: 14px 0 10px;
            white-space: pre;
            letter-spacing: 0;
            word-spacing: normal;
        }
        .document-title {
            text-align: center;
            font-size: 15px;
            font-weight: 700;
            margin: 8px 0 12px;
            text-transform: uppercase;
        }
        p { margin: 0 0 8px; }
        table.position { width: 96%; border-collapse: collapse; margin: 0 0 10px 24px; }
        table.position th, table.position td {
            border: 0;
            padding: 3px 8px 5px 0;
            vertical-align: top;
        }
        table.position th {
            width: 19%;
            background: transparent;
            text-align: left;
            font-weight: 500;
        }
        table.position td { width: 31%; }
        ul { margin: 0; padding-left: 22px; }
        li { margin-bottom: 6px; padding-left: 5px; }
        .section { page-break-inside: avoid; margin-bottom: 12px; }
        .centered-copy { text-align: center; }
        .page-break-before { page-break-before: always; }
        .disclaimer-copy p { text-align: center; }
        .underlined { text-decoration: underline; }
        .muted { color: #6b7280; }
    </style>
</head>
<body>
    @php
        $imageDataUri = function (string $path): ?string {
            if (! file_exists($path)) {
                return null;
            }

            $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
            $mime = match ($extension) {
                'jpg', 'jpeg' => 'image/jpeg',
                'png' => 'image/png',
                'gif' => 'image/gif',
                'svg' => 'image/svg+xml',
                default => 'application/octet-stream',
            };

            return 'data:'.$mime.';base64,'.base64_encode(file_get_contents($path));
        };
        $heroFile = public_path('images/career-opportunity-rtcz.jpeg');
        $logoFile = public_path('images/right-to-care-zambia-logo-transparent.png');
        $heroPath = $imageDataUri($heroFile);
        $logoPath = $imageDataUri($logoFile);
        $applyUrl = $jobOpening->is_publicly_applyable ? route('careers.apply', $jobOpening->slug) : null;
    @endphp

    <div class="footer">
        @if ($logoPath)
            <img src="{{ $logoPath }}" alt="Right to Care Zambia" class="footer-logo">
        @endif
    </div>

    <div class="hero">
        @if ($heroPath)
            <img src="{{ $heroPath }}" alt="Right to Care Zambia Career Opportunity">
        @endif
    </div>

    <div class="section-title">C A R E E R  O P P O R T U N I T Y</div>
    <div class="document-title">{{ $jobOpening->vacancy_announcement_title }}</div>

    <section class="section">
        <div class="section-title">A B O U T  U S</div>
        <div class="centered-copy">
            @foreach (preg_split('/\n{2,}/', App\Models\JobOpening::ABOUT_US_TEXT) as $paragraph)
                <p>{{ $paragraph }}</p>
            @endforeach
        </div>
    </section>

    <section class="section">
        <div class="section-title">A B O U T  T H E  P O S I T I O N</div>
        <table class="position">
            <tr>
                <th>Request to Hire No.:</th>
                <td>{{ $jobOpening->reference_no }}</td>
                <th>Contract duration:</th>
                <td>{{ $jobOpening->contract_duration ?: '-' }}</td>
            </tr>
            <tr>
                <th>Date advertised:</th>
                <td>{{ $jobOpening->opening_date?->format('d M Y') ?? '-' }}</td>
                <th>Contract type:</th>
                <td>{{ $jobOpening->employmentType?->name ?? '-' }}</td>
            </tr>
            <tr>
                <th>Closing date:</th>
                <td>{{ $jobOpening->closing_date?->format('d M Y') ?? '-' }}</td>
                <th>Job grade:</th>
                <td>{{ $jobOpening->job_grade ?: '-' }}</td>
            </tr>
            <tr>
                <th>Position:</th>
                <td>{{ $jobOpening->title }}</td>
                <th>Reporting to:</th>
                <td>{{ $jobOpening->reporting_to_label }}</td>
            </tr>
            <tr>
                <th>Location:</th>
                <td>{{ $jobOpening->location_label }}</td>
                <th>Contact email:</th>
                <td>{{ $jobOpening->announcement_contact_email }}</td>
            </tr>
            <tr>
                <th>No. of Vacancies:</th>
                <td>{{ $jobOpening->show_number_of_positions ? ($jobOpening->number_of_positions ?? '-') : 'Not disclosed' }}</td>
                <th>Contact Person:</th>
                <td>People & Culture Department</td>
            </tr>
        </table>
    </section>

    @foreach (App\Models\JobOpening::ANNOUNCEMENT_SECTIONS as $field => $label)
        @php($lines = $jobOpening->linesFor($field))
        <section class="section @if ($loop->first) page-break-before @endif">
            <div class="section-title">{{ $label }}</div>
            @if (count($lines) > 0)
                <ul>
                    @foreach ($lines as $line)
                        <li>{{ $line }}</li>
                    @endforeach
                </ul>
            @else
                <p class="muted">-</p>
            @endif
        </section>
    @endforeach

    <section class="section">
        <div class="section-title">A P P L I C A T I O N  P R O C E D U R E</div>
        @if ($applyUrl)
            <p>Applications must be submitted through the Right to Care Zambia careers portal not later than {{ $jobOpening->closing_date?->format('d M Y') ?? 'the closing date' }}: {{ $applyUrl }}</p>
        @else
            <p>Applications must be submitted through the Right to Care Zambia careers portal not later than {{ $jobOpening->closing_date?->format('d M Y') ?? 'the closing date' }} once this vacancy is published and open.</p>
        @endif
    </section>

    <section class="section">
        <div class="section-title">D I S C L A I M E R</div>
        <div class="disclaimer-copy">
            @foreach (preg_split('/\n{2,}/', App\Models\JobOpening::DISCLAIMER_TEXT) as $paragraph)
                @if (str_contains($paragraph, 'does not charge any fee'))
                    <p>{!! str_replace('does not charge any fee', '<span class="underlined">does not charge any fee</span>', e($paragraph)) !!}</p>
                @else
                    <p>{{ $paragraph }}</p>
                @endif
            @endforeach
        </div>
    </section>
</body>
</html>
