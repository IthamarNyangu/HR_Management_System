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
        .share-option {
            align-items: center;
            background: #f3f4f6;
            border: 1px solid #e5e7eb;
            border-radius: .5rem;
            color: #172033;
            display: inline-flex;
            flex-direction: column;
            font-weight: 600;
            gap: .35rem;
            justify-content: center;
            min-height: 86px;
            padding: .85rem;
            text-decoration: none;
            transition: background-color .15s ease, border-color .15s ease, color .15s ease;
        }
        .share-option:hover { background: #fff; border-color: #c01818; color: #c01818; }
        .share-icon { height: 28px; width: 28px; }
    </style>
</head>
<body>
    <main class="container career-shell py-5">
        <div class="d-flex justify-content-between align-items-start gap-3 mb-4">
            <img src="{{ asset('images/right-to-care-zambia-logo-transparent.png') }}" alt="Right to Care Zambia" class="career-logo">
            <a href="{{ route('careers.index') }}" class="btn btn-outline-secondary btn-sm">Back to vacancies</a>
        </div>

        @if (session('share_success'))
            <div class="alert alert-success">{{ session('share_success') }}</div>
        @endif

        @if (session('share_error'))
            <div class="alert alert-danger">{{ session('share_error') }}</div>
        @endif

        @if ($errors->has('recipient_email'))
            <div class="alert alert-danger">{{ $errors->first('recipient_email') }}</div>
        @endif

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
            <div class="d-flex flex-wrap gap-2 mt-4">
                <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#shareJobModal">Share Job</button>
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
                @php
                    $lines = $jobOpening->linesFor($field);
                @endphp
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

    @php
        $shareUrl = route('careers.show', $jobOpening->slug);
        $shareTitle = $jobOpening->title.' at Right to Care Zambia';
    @endphp

    <div class="modal fade" id="shareJobModal" tabindex="-1" aria-labelledby="shareJobTitle" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h2 class="modal-title h4" id="shareJobTitle">Share Job</h2>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4 p-md-5">
                    <form method="POST" action="{{ route('careers.share', $jobOpening->slug) }}" class="row g-2 align-items-end justify-content-center">
                        @csrf
                        <div class="col-md-6">
                            <label for="recipient_email" class="form-label fw-semibold">Share via Email</label>
                            <input id="recipient_email" type="email" name="recipient_email" value="{{ old('recipient_email') }}" class="form-control" placeholder="name@example.com" required>
                        </div>
                        <div class="col-auto">
                            <button type="submit" class="btn btn-danger">Send Email</button>
                        </div>
                    </form>

                    <hr class="my-4">

                    <div class="row g-3 justify-content-center text-center">
                        <div class="col-6 col-md-3">
                            <a class="share-option w-100" target="_blank" rel="noopener noreferrer" href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode($shareUrl) }}">
                                <svg class="share-icon" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
                                    <path d="M16 8.049C16 3.603 12.418 0 8 0S0 3.603 0 8.049c0 4.016 2.925 7.347 6.75 7.951v-5.625H4.719V8.049H6.75V6.275c0-2.017 1.194-3.131 3.022-3.131.875 0 1.791.157 1.791.157v1.98h-1.009c-.994 0-1.304.621-1.304 1.258v1.51h2.219l-.355 2.326H9.25V16C13.075 15.396 16 12.065 16 8.049z"/>
                                </svg>
                                <span>Facebook</span>
                            </a>
                        </div>
                        <div class="col-6 col-md-3">
                            <a class="share-option w-100" target="_blank" rel="noopener noreferrer" href="https://wa.me/?text={{ urlencode($shareTitle.' '.$shareUrl) }}">
                                <svg class="share-icon" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
                                    <path d="M13.601 2.326A7.85 7.85 0 0 0 7.994 0C3.627 0 .068 3.558.064 7.926c0 1.399.366 2.76 1.057 3.965L0 16l4.204-1.102a7.9 7.9 0 0 0 3.79.965h.004c4.368 0 7.927-3.558 7.93-7.93A7.9 7.9 0 0 0 13.6 2.326zM7.998 14.52a6.6 6.6 0 0 1-3.356-.92l-.24-.144-2.494.654.666-2.433-.156-.25a6.56 6.56 0 0 1-1.007-3.505c0-3.626 2.957-6.584 6.591-6.584a6.56 6.56 0 0 1 4.66 1.931 6.56 6.56 0 0 1 1.928 4.66c-.004 3.639-2.961 6.592-6.592 6.592zm3.615-4.934c-.197-.099-1.17-.578-1.353-.646-.182-.065-.315-.099-.445.099-.133.197-.513.646-.627.775-.114.133-.232.148-.43.05-.197-.1-.836-.308-1.592-.985-.59-.525-.985-1.175-1.103-1.372-.114-.198-.011-.304.088-.403.087-.088.197-.232.296-.346.1-.114.133-.198.198-.33.065-.134.034-.248-.015-.347-.05-.099-.445-1.076-.612-1.47-.16-.389-.323-.335-.445-.34-.114-.007-.247-.007-.38-.007a.73.73 0 0 0-.529.247c-.182.198-.691.677-.691 1.654s.71 1.916.81 2.049c.098.133 1.394 2.132 3.383 2.992.473.205.842.327 1.13.418.475.152.904.13 1.246.08.38-.058 1.171-.48 1.338-.943.164-.464.164-.86.114-.943-.049-.084-.182-.133-.38-.232z"/>
                                </svg>
                                <span>WhatsApp</span>
                            </a>
                        </div>
                        <div class="col-6 col-md-3">
                            <a class="share-option w-100" target="_blank" rel="noopener noreferrer" href="https://www.linkedin.com/sharing/share-offsite/?url={{ urlencode($shareUrl) }}">
                                <svg class="share-icon" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
                                    <path d="M0 1.146C0 .513.526 0 1.175 0h13.65C15.474 0 16 .513 16 1.146v13.708c0 .633-.526 1.146-1.175 1.146H1.175C.526 16 0 15.487 0 14.854zm4.943 12.248V6.169H2.542v7.225zm-1.2-8.212c.837 0 1.358-.554 1.358-1.248-.015-.709-.52-1.248-1.342-1.248S2.4 3.226 2.4 3.934c0 .694.521 1.248 1.327 1.248zm4.908 8.212V9.359c0-.216.016-.432.08-.586.173-.431.568-.878 1.232-.878.869 0 1.216.662 1.216 1.634v3.865h2.401V9.25c0-2.22-1.184-3.252-2.764-3.252-1.274 0-1.845.7-2.165 1.193v.025h-.016l.016-.025V6.169h-2.4c.03.678 0 7.225 0 7.225z"/>
                                </svg>
                                <span>LinkedIn</span>
                            </a>
                        </div>
                        <div class="col-6 col-md-3">
                            <a class="share-option w-100" target="_blank" rel="noopener noreferrer" href="https://x.com/intent/tweet?url={{ urlencode($shareUrl) }}&text={{ urlencode($shareTitle) }}">
                                <svg class="share-icon" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
                                    <path d="M12.6.75h2.454l-5.36 6.142L16 15.25h-4.937l-3.867-5.07-4.425 5.07H.316l5.733-6.57L0 .75h5.063l3.495 4.633zM11.74 13.78h1.36L4.323 2.145H2.865z"/>
                                </svg>
                                <span>X</span>
                            </a>
                        </div>
                        <div class="col-8 col-md-4">
                            <button type="button" class="share-option w-100" id="copyJobLink" data-share-url="{{ $shareUrl }}">
                                <svg class="share-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <rect x="9" y="9" width="13" height="13" rx="2"></rect>
                                    <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
                                </svg>
                                <span>Copy Link</span>
                            </button>
                        </div>
                    </div>
                    <div class="text-center small text-success mt-3 d-none" id="copyJobLinkStatus">Link copied.</div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const copyButton = document.getElementById('copyJobLink');
            const status = document.getElementById('copyJobLinkStatus');

            copyButton?.addEventListener('click', async () => {
                const url = copyButton.dataset.shareUrl;

                try {
                    await navigator.clipboard.writeText(url);
                    status?.classList.remove('d-none');
                    window.setTimeout(() => status?.classList.add('d-none'), 2200);
                } catch (error) {
                    window.prompt('Copy this job link:', url);
                }
            });
        });
    </script>
</body>
</html>
