@extends('layouts.app')

@section('title', $jobOpening->reference_no)
@section('page-title', $jobOpening->reference_no)

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('recruitment.index') }}">Recruitment</a></li>
    <li class="breadcrumb-item"><a href="{{ route('recruitment.job-openings.index') }}">Job Openings</a></li>
    <li class="breadcrumb-item active" aria-current="page">{{ $jobOpening->reference_no }}</li>
@endsection

@section('page-actions')
    @php
        $isExpiredPublished = $jobOpening->is_expired_published;
        $canPrepareForReadvertising = in_array($jobOpening->status, [App\Models\JobOpening::STATUS_CANCELLED, App\Models\JobOpening::STATUS_CLOSED], true)
            || $isExpiredPublished;
    @endphp
    <div class="d-flex flex-wrap gap-2">
        <a href="{{ route('recruitment.job-openings.announcement.pdf', $jobOpening) }}" class="btn btn-secondary btn-md">
            <i class="bi bi-download" aria-hidden="true"></i>
            <span>Announcement PDF</span>
        </a>
        @can('publish', $jobOpening)
            @if ($jobOpening->status === App\Models\JobOpening::STATUS_DRAFT)
                <form method="POST" action="{{ route('recruitment.job-openings.publish', $jobOpening) }}" data-confirm="true" data-confirm-title="Publish job opening?" data-confirm-message="Published external or both-visible jobs can appear on public careers pages if the closing date has not passed." data-confirm-button="Publish job">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="btn btn-primary btn-md">Publish</button>
                </form>
            @endif
        @endcan
        @can('update', $jobOpening)
            @if ($canPrepareForReadvertising)
                <form method="POST" action="{{ route('recruitment.job-openings.prepare-readvertising', $jobOpening) }}" data-confirm="true" data-confirm-title="Prepare for re-advertising?" data-confirm-message="This keeps the vacancy and its application history, then returns the recruitment to Draft so you can update its dates and details before publishing again." data-confirm-button="Prepare to Re-advertise">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="btn btn-primary btn-md">Re-advertise</button>
                </form>
            @endif
        @endcan
        @can('update', $jobOpening)
            @if ($jobOpening->status === App\Models\JobOpening::STATUS_PUBLISHED && $jobOpening->advertisement_round > 1 && $previousApplicantNoticeCount > 0)
                <form method="POST" action="{{ route('recruitment.job-openings.notify-previous-applicants', $jobOpening) }}" data-confirm="true" data-confirm-title="Notify previous applicants?" data-confirm-message="This will send a re-advertisement notice to {{ $previousApplicantNoticeCount }} previous applicant(s) who did not withdraw. Each email address is notified only once for this advertising round." data-confirm-button="Send Notices">
                    @csrf
                    <button type="submit" class="btn btn-primary-outline btn-md">Notify Previous Applicants ({{ $previousApplicantNoticeCount }})</button>
                </form>
            @endif
        @endcan
        @can('close', $jobOpening)
            @if ($jobOpening->status === App\Models\JobOpening::STATUS_PUBLISHED)
                <form method="POST" action="{{ route('recruitment.job-openings.close', $jobOpening) }}" data-confirm="true" data-confirm-title="Close recruitment?" data-confirm-message="This recruitment will be closed and hidden from public careers pages. The vacancy and submitted applications will be kept for review or future re-advertising." data-confirm-button="Close Recruitment">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="btn btn-secondary btn-md">Close Recruitment</button>
                </form>
            @endif
        @endcan
        @can('cancel', $jobOpening)
            @if (! $isExpiredPublished && in_array($jobOpening->status, [App\Models\JobOpening::STATUS_DRAFT, App\Models\JobOpening::STATUS_PUBLISHED], true))
                <form method="POST" action="{{ route('recruitment.job-openings.cancel', $jobOpening) }}" data-confirm="true" data-confirm-title="Cancel recruitment?" data-confirm-message="This recruitment process will be withdrawn from the careers page. The vacancy and submitted applications will be kept so People & Culture can review or re-advertise it later." data-confirm-button="Cancel Recruitment" data-confirm-variant="btn-warning">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="btn btn-outline-warning btn-md">Cancel Recruitment</button>
                </form>
            @endif
        @endcan
        @can('update', $jobOpening)
            <a href="{{ route('recruitment.job-openings.edit', $jobOpening) }}" class="btn btn-primary-outline btn-md">Edit</a>
        @endcan
        @can('archive', $jobOpening)
            <form method="POST" action="{{ route('recruitment.job-openings.archive', $jobOpening) }}" data-confirm="true" data-confirm-title="Archive job opening?" data-confirm-message="This job opening will move to archived records. Do you want to continue?" data-confirm-button="Archive job opening" data-confirm-variant="btn-warning">
                @csrf
                @method('PATCH')
                <button type="submit" class="btn btn-outline-warning btn-md">Archive</button>
            </form>
        @endcan
        <a href="{{ route('recruitment.job-openings.index') }}" class="btn btn-secondary btn-md">Back</a>
    </div>
@endsection

@section('content')
    <div class="row g-4">
        <div class="col-xl-4">
            @php
                $statusLabel = $jobOpening->is_expired_published ? 'Expired' : str($jobOpening->status)->headline();
                $statusClass = $jobOpening->is_expired_published
                    ? 'warning'
                    : ($jobOpening->status === App\Models\JobOpening::STATUS_PUBLISHED
                        ? 'success'
                        : ($jobOpening->status === App\Models\JobOpening::STATUS_CANCELLED ? 'danger' : 'light'));
            @endphp
            <section class="bg-white border rounded-2 p-3 h-100">
                <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
                    <div>
                        <div class="text-muted small">Job Opening</div>
                        <h2 class="h5 mb-0">{{ $jobOpening->title }}</h2>
                    </div>
                    <span class="badge text-bg-{{ $statusClass }}">{{ $statusLabel }}</span>
                </div>

                <dl class="row mb-0">
                    <dt class="col-5">Visibility</dt>
                    <dd class="col-7">{{ str($jobOpening->visibility)->headline() }}</dd>
                    <dt class="col-5">Request to Hire No.</dt>
                    <dd class="col-7">{{ $jobOpening->reference_no }}</dd>
                    <dt class="col-5">Advertising Round</dt>
                    <dd class="col-7">{{ $jobOpening->advertisement_round }}</dd>
                    <dt class="col-5">Department</dt>
                    <dd class="col-7">{{ $jobOpening->department?->name ?? '-' }}</dd>
                    <dt class="col-5">Project</dt>
                    <dd class="col-7">{{ $jobOpening->project?->name ?? '-' }}</dd>
                    <dt class="col-5">Location</dt>
                    <dd class="col-7">{{ $jobOpening->location_label }}</dd>
                    <dt class="col-5">Employment Type</dt>
                    <dd class="col-7">{{ $jobOpening->employmentType?->name ?? '-' }}</dd>
                    <dt class="col-5">Positions</dt>
                    <dd class="col-7">{{ $jobOpening->show_number_of_positions ? ($jobOpening->number_of_positions ?? '-') : 'Hidden publicly' }}</dd>
                    <dt class="col-5">Contract Duration</dt>
                    <dd class="col-7">{{ $jobOpening->contract_duration ?: '-' }}</dd>
                    <dt class="col-5">Job Grade</dt>
                    <dd class="col-7">{{ $jobOpening->job_grade ?: '-' }}</dd>
                    <dt class="col-5">Reporting To</dt>
                    <dd class="col-7">{{ $jobOpening->reporting_to_label }}</dd>
                    <dt class="col-5">Opening Date</dt>
                    <dd class="col-7">{{ $jobOpening->opening_date?->format('d M Y') ?? '-' }}</dd>
                    <dt class="col-5">Closing Date</dt>
                    <dd class="col-7">{{ $jobOpening->closing_date?->format('d M Y') }}<br><span class="small text-muted">{{ $jobOpening->closing_status_label }}</span></dd>
                </dl>
            </section>
        </div>

        <div class="col-xl-8">
            <section class="bg-white border rounded-2 p-3">
                <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
                    <div>
                        <h2 class="h5 mb-1">Vacancy Announcement Preview</h2>
                        <div class="text-muted small">{{ $jobOpening->vacancy_announcement_title }}</div>
                    </div>
                    <a href="{{ route('recruitment.job-openings.announcement.pdf', $jobOpening) }}" class="btn btn-sm btn-secondary" title="Download vacancy announcement PDF" aria-label="Download vacancy announcement PDF">
                        <i class="bi bi-download" aria-hidden="true"></i>
                    </a>
                </div>
                <div class="mb-4">
                    <h3 class="h6 text-danger">A B O U T&nbsp;&nbsp; U S</h3>
                    <div>{{ App\Models\JobOpening::ABOUT_US_TEXT }}</div>
                </div>
                @foreach (App\Models\JobOpening::ANNOUNCEMENT_SECTIONS as $field => $label)
                    @php($lines = $jobOpening->linesFor($field))
                    @php($sectionTitle = str_replace('  ', '&nbsp;&nbsp;', e($label)))
                    <div class="mb-4">
                        <h3 class="h6 text-danger">{!! $sectionTitle !!}</h3>
                        @if (count($lines) > 0)
                            <ul class="mb-0">
                                @foreach ($lines as $line)
                                    <li>{{ $line }}</li>
                                @endforeach
                            </ul>
                        @else
                            <div class="text-muted">-</div>
                        @endif
                    </div>
                @endforeach
                <div class="border rounded-2 bg-light p-3">
                    <div class="fw-semibold mb-1">How to Apply</div>
                    @if ($jobOpening->is_publicly_applyable)
                        <div>Applicants submit through the HRMS careers portal: <a href="{{ route('careers.apply', $jobOpening->slug) }}">{{ route('careers.apply', $jobOpening->slug) }}</a></div>
                    @else
                        <div class="text-muted">The system will show the application link after this vacancy is published, externally visible, and still open.</div>
                    @endif
                    <div class="small text-muted mt-2">Contact: {{ $jobOpening->announcement_contact_person }} &lt;{{ $jobOpening->announcement_contact_email }}&gt;</div>
                </div>
            </section>

            @if (filled($jobOpening->internal_notes))
                <section class="bg-white border rounded-2 p-3 mt-4">
                    <h2 class="h5 mb-3">Internal Notes</h2>
                    <div class="text-pre-line">{{ $jobOpening->internal_notes }}</div>
                </section>
            @endif
        </div>
    </div>
@endsection
