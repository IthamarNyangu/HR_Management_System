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
        .career-header-inner { min-height: 104px; position: relative; }
        .career-logo { height: 76px; left: 0; object-fit: contain; position: absolute; top: 50%; transform: translateY(-50%); width: 126px; }
        .career-title { color: #172033; font-size: clamp(1.85rem, 3vw, 2.6rem); font-weight: 600; margin: 0; text-align: center; }
        .job-card { border: 1px solid #e1e7f0; border-radius: .5rem; background: #fff; transition: border-color .15s ease, box-shadow .15s ease; }
        .job-card:hover { border-color: #b8c7dd; box-shadow: 0 .6rem 1.4rem rgba(23, 32, 51, .08); }
        .job-action { min-width: 118px; }
        .vacancy-count { color: #172033; font-size: .9rem; font-weight: 700; white-space: nowrap; }
        .closing-pill { background: #fef2f2; border: 1px solid #fecaca; border-radius: 999px; color: #991b1b; display: inline-flex; font-size: .78rem; font-weight: 600; padding: .18rem .55rem; }
        .kpa-snippet {
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .empty-state {
            background:
                linear-gradient(rgba(255, 255, 255, .92), rgba(255, 255, 255, .94)),
                url("{{ asset('images/career-opportunity-rtcz.jpeg') }}") center 16% / min(820px, 82%) auto no-repeat;
            border: 1px solid #e1e7f0;
            border-radius: .75rem;
            min-height: 560px;
        }
        .social-link {
            align-items: center;
            border: 1px solid #d6dde8;
            border-radius: 999px;
            color: #172033;
            display: inline-flex;
            font-weight: 600;
            gap: .45rem;
            padding: .55rem .85rem;
            text-decoration: none;
        }
        .social-link:hover { border-color: #c01818; color: #c01818; }
        .social-link svg { height: 1rem; width: 1rem; }
        .text-danger { color: #c01818 !important; }
        @media (max-width: 575.98px) {
            .career-header-inner { min-height: 82px; }
            .career-logo { height: 54px; width: 86px; }
            .career-title { font-size: 2rem; }
            .empty-state { min-height: 500px; }
        }
    </style>
</head>
<body>
    <header class="career-header">
        <div class="container career-header-inner d-flex align-items-center justify-content-center">
            <img src="{{ asset('images/right-to-care-zambia-logo-transparent.png') }}" alt="Right to Care Zambia" class="career-logo">
            <h3 class="career-title">Vacancies</h3>
        </div>
    </header>

    <main class="container py-4 py-md-5">
        <div class="d-flex flex-column gap-3">
            @forelse ($jobs as $job)
                <article class="job-card p-4">
                    <div class="d-flex flex-column flex-lg-row justify-content-between gap-3">
                        <div>
                            <div class="small text-danger fw-semibold">{{ $job->reference_no }}</div>
                            <h2 class="h5 mb-2">
                                <a href="{{ route('careers.show', $job->slug) }}" class="text-decoration-none">{{ $job->title }}</a>
                                @if ($job->show_number_of_positions && $job->number_of_positions > 1)
                                    <span class="vacancy-count">x{{ $job->number_of_positions }}</span>
                                @endif
                            </h2>
                            <div class="text-muted kpa-snippet">
                                {{ collect($job->linesFor('responsibilities'))->take(3)->implode(' ') ?: ($job->summary ?? 'View details for key performance areas and role requirements.') }}
                            </div>
                            <div class="small text-muted mt-3">{{ $job->public_location_label }}</div>
                        </div>
                        <div class="text-lg-end flex-shrink-0">
                            <div class="small text-muted">Closing date</div>
                            <div class="fw-semibold">{{ $job->closing_date?->format('d M Y') }}</div>
                            <div class="mt-1">
                                @php($daysLeft = (int) $job->days_until_closing)
                                <span class="closing-pill">
                                    @if ($daysLeft === 0)
                                        Closes today
                                    @else
                                        {{ $daysLeft }} day{{ $daysLeft === 1 ? '' : 's' }} left
                                    @endif
                                </span>
                            </div>
                            <div class="d-flex flex-lg-column gap-2 align-items-lg-end mt-3">
                                <a href="{{ route('careers.show', $job->slug) }}" class="btn btn-outline-danger btn-sm job-action">View Details</a>
                                <a href="{{ route('careers.apply', $job->slug) }}" class="btn btn-danger btn-sm job-action">Apply Now</a>
                            </div>
                        </div>
                    </div>
                </article>
            @empty
                <section class="empty-state d-flex align-items-start justify-content-center p-4 p-md-5 text-center">
                    <div class="bg-white border rounded-3 p-4 shadow-sm" style="max-width: 680px;">
                        <h2 class="h4 mb-2">No current job openings. Please check back soon!</h2>
                        <h3 class="h6 text-danger text-uppercase mt-4 mb-2">Stay Connected</h3>
                        <p class="text-muted mb-4">We appreciate your interest in joining our team. Follow us on social media to stay updated on the latest career opportunities.</p>
                        <div class="d-flex flex-wrap justify-content-center gap-2">
                            <a href="https://x.com/rtczambia" target="_blank" rel="noopener noreferrer" class="social-link" aria-label="Follow Right to Care Zambia on X">
                                <svg viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
                                    <path d="M12.6.75h2.454l-5.36 6.142L16 15.25h-4.937l-3.867-5.07-4.425 5.07H.316l5.733-6.57L0 .75h5.063l3.495 4.633zM11.74 13.78h1.36L4.323 2.145H2.865z"/>
                                </svg>
                            </a>
                            <a href="https://web.facebook.com/p/Right-to-Care-Zambia-61559119114255/?_rdc=1&_rdr#" target="_blank" rel="noopener noreferrer" class="social-link" aria-label="Follow Right to Care Zambia on Facebook">
                                <svg viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
                                    <path d="M16 8.049C16 3.603 12.418 0 8 0S0 3.603 0 8.049c0 4.016 2.925 7.347 6.75 7.951v-5.625H4.719V8.049H6.75V6.275c0-2.017 1.194-3.131 3.022-3.131.875 0 1.791.157 1.791.157v1.98h-1.009c-.994 0-1.304.621-1.304 1.258v1.51h2.219l-.355 2.326H9.25V16C13.075 15.396 16 12.065 16 8.049z"/>
                                </svg>
                                <span>Facebook</span>
                            </a>
                            <a href="https://www.linkedin.com/company/right-to-care-zambia" target="_blank" rel="noopener noreferrer" class="social-link" aria-label="Follow Right to Care Zambia on LinkedIn">
                                <svg viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
                                    <path d="M0 1.146C0 .513.526 0 1.175 0h13.65C15.474 0 16 .513 16 1.146v13.708c0 .633-.526 1.146-1.175 1.146H1.175C.526 16 0 15.487 0 14.854zm4.943 12.248V6.169H2.542v7.225zm-1.2-8.212c.837 0 1.358-.554 1.358-1.248-.015-.709-.52-1.248-1.342-1.248S2.4 3.226 2.4 3.934c0 .694.521 1.248 1.327 1.248zm4.908 8.212V9.359c0-.216.016-.432.08-.586.173-.431.568-.878 1.232-.878.869 0 1.216.662 1.216 1.634v3.865h2.401V9.25c0-2.22-1.184-3.252-2.764-3.252-1.274 0-1.845.7-2.165 1.193v.025h-.016l.016-.025V6.169h-2.4c.03.678 0 7.225 0 7.225z"/>
                                </svg>
                                <span>LinkedIn</span>
                            </a>
                        </div>
                    </div>
                </section>
            @endforelse
        </div>

        <div class="mt-4">
            {{ $jobs->links() }}
        </div>
    </main>
</body>
</html>
