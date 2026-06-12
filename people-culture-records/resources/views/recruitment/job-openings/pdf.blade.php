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
            bottom: 0;
            width: 70px;
            max-height: 32px;
            object-fit: contain;
        }
        .page-number {
            position: absolute;
            left: 0;
            bottom: 6px;
        }
        .page-number::after { content: counter(page); }
        .hero { text-align: center; margin-bottom: 12px; }
        .hero img { max-width: 540px; max-height: 135px; object-fit: contain; }
        .section-title {
            background: #e5e7eb;
            border: 1px solid #d1d5db;
            color: #b42318;
            text-align: center;
            font-size: 16px;
            font-weight: 700;
            padding: 7px 10px;
            margin: 14px 0 10px;
        }
        .document-title {
            text-align: center;
            font-size: 15px;
            font-weight: 700;
            margin: 8px 0 12px;
            text-transform: uppercase;
        }
        p { margin: 0 0 8px; }
        table.position { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        table.position th, table.position td {
            border: 1px solid #d1d5db;
            padding: 6px 8px;
            vertical-align: top;
        }
        table.position th {
            width: 32%;
            background: #f3f4f6;
            text-align: left;
            font-weight: 700;
        }
        ul { margin: 0; padding-left: 18px; }
        li { margin-bottom: 4px; }
        .section { page-break-inside: avoid; margin-bottom: 12px; }
        .muted { color: #6b7280; }
    </style>
</head>
<body>
    @php
        $heroFile = public_path('images/career-opportunity-rtcz.jpeg');
        $heroPath = 'file:///'.str_replace('\\', '/', $heroFile);
        $logoFile = public_path('images/right-to-care-zambia-logo-transparent.png');
        $logoPath = 'file:///'.str_replace('\\', '/', $logoFile);
        $applyUrl = $jobOpening->is_publicly_applyable ? route('careers.apply', $jobOpening->slug) : null;
    @endphp

    <div class="footer">
        <span class="page-number">Page </span>
        @if (file_exists($logoFile))
            <img src="{{ $logoPath }}" alt="Right to Care Zambia" class="footer-logo">
        @endif
    </div>

    <div class="hero">
        @if (file_exists($heroFile))
            <img src="{{ $heroPath }}" alt="Right to Care Zambia Career Opportunity">
        @endif
    </div>

    <div class="section-title">C A R E E R&nbsp;&nbsp; O P P O R T U N I T Y</div>
    <div class="document-title">{{ $jobOpening->vacancy_announcement_title }}</div>

    <section class="section">
        <div class="section-title">A B O U T&nbsp;&nbsp; U S</div>
        <p>{{ App\Models\JobOpening::ABOUT_US_TEXT }}</p>
    </section>

    <section class="section">
        <div class="section-title">A B O U T&nbsp;&nbsp; T H E&nbsp;&nbsp; P O S I T I O N</div>
        <table class="position">
            <tr>
                <th>Request to Hire No.</th>
                <td>{{ $jobOpening->reference_no }}</td>
            </tr>
            <tr>
                <th>Date advertised</th>
                <td>{{ $jobOpening->opening_date?->format('d M Y') ?? '-' }}</td>
            </tr>
            <tr>
                <th>Closing date</th>
                <td>{{ $jobOpening->closing_date?->format('d M Y') ?? '-' }}</td>
            </tr>
            <tr>
                <th>Position</th>
                <td>{{ $jobOpening->title }}</td>
            </tr>
            <tr>
                <th>Location</th>
                <td>{{ $jobOpening->location_label }}</td>
            </tr>
            <tr>
                <th>No. of Vacancies</th>
                <td>{{ $jobOpening->show_number_of_positions ? ($jobOpening->number_of_positions ?? '-') : 'Not disclosed' }}</td>
            </tr>
            <tr>
                <th>Contract duration</th>
                <td>{{ $jobOpening->contract_duration ?: '-' }}</td>
            </tr>
            <tr>
                <th>Contract type</th>
                <td>{{ $jobOpening->employmentType?->name ?? '-' }}</td>
            </tr>
            <tr>
                <th>Job grade</th>
                <td>{{ $jobOpening->job_grade ?: '-' }}</td>
            </tr>
            <tr>
                <th>Reporting to</th>
                <td>{{ $jobOpening->reporting_to_label }}</td>
            </tr>
            <tr>
                <th>Contact email</th>
                <td>{{ $jobOpening->announcement_contact_email }}</td>
            </tr>
            <tr>
                <th>Contact Person</th>
                <td>People & Culture Department</td>
            </tr>
        </table>
    </section>

    @foreach (App\Models\JobOpening::ANNOUNCEMENT_SECTIONS as $field => $label)
        @php($lines = $jobOpening->linesFor($field))
        <section class="section">
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
        <div class="section-title">A P P L I C A T I O N&nbsp;&nbsp; P R O C E D U R E</div>
        @if ($applyUrl)
            <p>Applications must be submitted through the Right to Care Zambia careers portal: {{ $applyUrl }}</p>
        @else
            <p>Applications must be submitted through the Right to Care Zambia careers portal once this vacancy is published and open.</p>
        @endif
    </section>

    <section class="section">
        <div class="section-title">D I S C L A I M E R</div>
        <p>{{ App\Models\JobOpening::DISCLAIMER_TEXT }}</p>
    </section>
</body>
</html>
