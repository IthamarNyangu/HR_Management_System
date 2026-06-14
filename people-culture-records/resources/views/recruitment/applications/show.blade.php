@extends('layouts.app')

@section('title', $jobApplication->reference_no)
@section('page-title', $jobApplication->reference_no)

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('recruitment.index') }}">Recruitment</a></li>
    <li class="breadcrumb-item"><a href="{{ route('recruitment.applications.index') }}">Applications</a></li>
    <li class="breadcrumb-item active" aria-current="page">{{ $jobApplication->reference_no }}</li>
@endsection

@section('page-actions')
    <a href="{{ route('recruitment.applications.index') }}" class="btn btn-secondary btn-md">Back</a>
@endsection

@section('content')
    @php
        $canReview = auth()->user()->can('review', $jobApplication);
        $canUpdateStatus = auth()->user()->can('updateStatus', $jobApplication);
        $canAddNote = auth()->user()->can('addNote', $jobApplication);
        $canShortlist = auth()->user()->can('shortlist', $jobApplication);
        $canReject = auth()->user()->can('reject', $jobApplication);
        $canSendEmail = auth()->user()->can('sendEmail', $jobApplication);
        $isWithdrawn = $jobApplication->isWithdrawn();
        $reviewStatuses = [
            App\Models\JobApplication::STATUS_SUBMITTED => 'Submitted',
            App\Models\JobApplication::STATUS_UNDER_REVIEW => 'Under Review',
            App\Models\JobApplication::STATUS_LONGLISTED => 'Longlisted',
        ];
    @endphp

    @if ($isWithdrawn)
        <div class="alert alert-warning">
            This application was withdrawn by the applicant. Review actions and emails are locked.
        </div>
    @endif

    <div class="row g-4">
        <div class="col-xl-4">
            <section class="bg-white border rounded-2 p-3 mb-4">
                <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
                    <div>
                        <div class="text-muted small">Applicant Summary</div>
                        <h2 class="h5 mb-0">{{ trim(($jobApplication->title ? $jobApplication->title.' ' : '').$jobApplication->full_name) }}</h2>
                    </div>
                    <span class="badge text-bg-{{ $jobApplication->status_badge_class }}">
                        {{ $jobApplication->status_label }}
                    </span>
                </div>

                <dl class="row mb-0">
                    <dt class="col-5">Reference</dt>
                    <dd class="col-7">{{ $jobApplication->reference_no }}</dd>
                    <dt class="col-5">Email</dt>
                    <dd class="col-7 text-break">{{ $jobApplication->email }}</dd>
                    <dt class="col-5">Phone</dt>
                    <dd class="col-7">{{ $jobApplication->phone }}</dd>
                    <dt class="col-5">ID / Passport / Visa</dt>
                    <dd class="col-7">{{ $jobApplication->national_id ?? '-' }}</dd>
                    <dt class="col-5">Gender</dt>
                    <dd class="col-7">{{ $jobApplication->gender ?? '-' }}</dd>
                    <dt class="col-5">Disability</dt>
                    <dd class="col-7">{{ $jobApplication->disability ?? '-' }}</dd>
                    <dt class="col-5">Submitted</dt>
                    <dd class="col-7">{{ $jobApplication->submitted_at?->format('d M Y H:i') }}</dd>
                    <dt class="col-5">Withdrawn</dt>
                    <dd class="col-7">{{ $jobApplication->withdrawn_at?->format('d M Y H:i') ?? '-' }}</dd>
                    <dt class="col-5">Last Email</dt>
                    <dd class="col-7">{{ $jobApplication->outcome_sent_at?->format('d M Y H:i') ?? '-' }}</dd>
                </dl>
            </section>

            <section class="bg-white border rounded-2 p-3 mb-4">
                <h2 class="h5 mb-3">Job Summary</h2>
                <dl class="row mb-0">
                    <dt class="col-5">Reference</dt>
                    <dd class="col-7">
                        <a href="{{ route('recruitment.job-openings.show', $jobApplication->jobOpening) }}">
                            {{ $jobApplication->jobOpening?->reference_no }}
                        </a>
                    </dd>
                    <dt class="col-5">Title</dt>
                    <dd class="col-7">{{ $jobApplication->jobOpening?->title }}</dd>
                    <dt class="col-5">Project</dt>
                    <dd class="col-7">{{ $jobApplication->jobOpening?->project?->name ?? '-' }}</dd>
                    <dt class="col-5">Department</dt>
                    <dd class="col-7">{{ $jobApplication->jobOpening?->department?->name ?? '-' }}</dd>
                    <dt class="col-5">Location</dt>
                    <dd class="col-7">{{ $jobApplication->jobOpening?->location_label }}</dd>
                    <dt class="col-5">Closing Date</dt>
                    <dd class="col-7">{{ $jobApplication->jobOpening?->closing_date?->format('d M Y') ?? '-' }}</dd>
                </dl>
            </section>

            <section class="bg-white border rounded-2 p-3">
                <h2 class="h5 mb-3">Education and Experience</h2>
                <dl class="row mb-0">
                    <dt class="col-5">Qualification</dt>
                    <dd class="col-7">{{ $jobApplication->highest_qualification }}</dd>
                    <dt class="col-5">Field of Study</dt>
                    <dd class="col-7">{{ $jobApplication->field_of_study ?? '-' }}</dd>
                    <dt class="col-5">Experience</dt>
                    <dd class="col-7">{{ $jobApplication->years_of_experience ?? '-' }}</dd>
                    <dt class="col-5">Current Employer</dt>
                    <dd class="col-7">{{ $jobApplication->current_employer ?? '-' }}</dd>
                </dl>
            </section>
        </div>

        <div class="col-xl-8">
            <section class="bg-white border rounded-2 p-3 mb-4">
                <h2 class="h5 mb-3">Documents</h2>
                <div class="table-responsive data-table-wrap">
                    <table class="table table-hover align-middle data-table mb-0">
                        <thead>
                            <tr>
                                <th>Type</th>
                                <th>Filename</th>
                                <th>Size</th>
                                <th>Uploaded</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($jobApplication->documents as $document)
                                <tr>
                                    <td>{{ $document->readable_type }}</td>
                                    <td>{{ $document->original_filename }}</td>
                                    <td>{{ number_format($document->file_size / 1024, 1) }} KB</td>
                                    <td>{{ $document->uploaded_at?->format('d M Y H:i') }}</td>
                                    <td>
                                        <div class="d-flex gap-2">
                                            <a href="{{ route('recruitment.applications.documents.view', [$jobApplication, $document]) }}" target="_blank" class="btn btn-sm btn-secondary" title="View document" aria-label="View {{ $document->original_filename }}">
                                                <i class="bi bi-eye" aria-hidden="true"></i>
                                            </a>
                                            <a href="{{ route('recruitment.applications.documents.download', [$jobApplication, $document]) }}" class="btn btn-sm btn-primary-outline" title="Download document" aria-label="Download {{ $document->original_filename }}">
                                                <i class="bi bi-download" aria-hidden="true"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">No documents uploaded.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="bg-white border rounded-2 p-3 mb-4">
                <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
                    <div>
                        <h2 class="h5 mb-1">Screening / Scores</h2>
                        <p class="text-muted mb-0">Overall score is calculated from the non-empty score fields.</p>
                    </div>
                    <span class="badge text-bg-secondary">Overall: {{ $jobApplication->overall_score ?? '-' }}</span>
                </div>

                @can('review', $jobApplication)
                    @if (! $isWithdrawn)
                        <form method="POST" action="{{ route('recruitment.applications.update-review', $jobApplication) }}">
                            @csrf
                            @method('PATCH')
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label for="qualification_score" class="form-label">Qualification Score</label>
                                    <input type="number" min="0" max="100" step="0.1" name="qualification_score" id="qualification_score" value="{{ old('qualification_score', $jobApplication->qualification_score) }}" class="form-control @error('qualification_score') is-invalid @enderror">
                                    @error('qualification_score')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-4">
                                    <label for="experience_score" class="form-label">Experience Score</label>
                                    <input type="number" min="0" max="100" step="0.1" name="experience_score" id="experience_score" value="{{ old('experience_score', $jobApplication->experience_score) }}" class="form-control @error('experience_score') is-invalid @enderror">
                                    @error('experience_score')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-4">
                                    <label for="screening_score" class="form-label">Screening Score</label>
                                    <input type="number" min="0" max="100" step="0.1" name="screening_score" id="screening_score" value="{{ old('screening_score', $jobApplication->screening_score) }}" class="form-control @error('screening_score') is-invalid @enderror">
                                    @error('screening_score')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-12">
                                    <label for="review_notes" class="form-label">Internal Notes</label>
                                    <textarea name="review_notes" id="review_notes" rows="4" class="form-control @error('review_notes') is-invalid @enderror">{{ old('review_notes', $jobApplication->review_notes) }}</textarea>
                                    @error('review_notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-12">
                                    <button type="submit" class="btn btn-primary btn-md">Save Review</button>
                                </div>
                            </div>
                        </form>
                    @else
                        <div class="text-muted">Review scoring is locked for withdrawn applications.</div>
                    @endif
                @else
                    <dl class="row mb-0">
                        <dt class="col-md-4">Qualification Score</dt>
                        <dd class="col-md-8">{{ $jobApplication->qualification_score ?? '-' }}</dd>
                        <dt class="col-md-4">Experience Score</dt>
                        <dd class="col-md-8">{{ $jobApplication->experience_score ?? '-' }}</dd>
                        <dt class="col-md-4">Screening Score</dt>
                        <dd class="col-md-8">{{ $jobApplication->screening_score ?? '-' }}</dd>
                        <dt class="col-md-4">Internal Notes</dt>
                        <dd class="col-md-8">{{ $jobApplication->review_notes ?? '-' }}</dd>
                    </dl>
                @endcan
            </section>

            <section class="bg-white border rounded-2 p-3 mb-4">
                <h2 class="h5 mb-3">Status Actions</h2>

                @if (! $canUpdateStatus && ! $canShortlist && ! $canReject)
                    <div class="text-muted">You can view this application, but cannot update its review status.</div>
                @elseif ($isWithdrawn)
                    <div class="text-muted">This application was withdrawn and cannot be moved through review.</div>
                @else
                    <div class="row g-3">
                        @can('updateStatus', $jobApplication)
                            <div class="col-lg-6">
                                <form method="POST" action="{{ route('recruitment.applications.update-status', $jobApplication) }}" class="border rounded-2 p-3 h-100">
                                    @csrf
                                    @method('PATCH')
                                    <h3 class="h6">Move Review Status</h3>
                                    <div class="mb-3">
                                        <label for="status" class="form-label">Status</label>
                                        <select name="status" id="status" class="form-select @error('status') is-invalid @enderror">
                                            @foreach ($reviewStatuses as $value => $label)
                                                <option value="{{ $value }}" @selected(old('status', $jobApplication->status) === $value)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                        @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                    <button type="submit" class="btn btn-primary-outline btn-md">Update Status</button>
                                </form>
                            </div>
                        @endcan

                        @can('shortlist', $jobApplication)
                            <div class="col-lg-6">
                                <form method="POST" action="{{ route('recruitment.applications.shortlist', $jobApplication) }}" class="border rounded-2 p-3 h-100" data-confirm="Shortlist this application?">
                                    @csrf
                                    @method('PATCH')
                                    <h3 class="h6">Shortlist Candidate</h3>
                                    <div class="form-check mb-3">
                                        <input class="form-check-input" type="checkbox" name="send_email" value="1" id="shortlist_send_email">
                                        <label class="form-check-label" for="shortlist_send_email">Send shortlist email to applicant</label>
                                    </div>
                                    <button type="submit" class="btn btn-primary btn-md">Shortlist</button>
                                </form>
                            </div>
                        @endcan

                        @can('reject', $jobApplication)
                            <div class="col-lg-12">
                                <form method="POST" action="{{ route('recruitment.applications.reject', $jobApplication) }}" class="border rounded-2 p-3" data-confirm="Reject this application?">
                                    @csrf
                                    @method('PATCH')
                                    <h3 class="h6">Reject Candidate</h3>
                                    <div class="row g-3">
                                        <div class="col-lg-8">
                                            <label for="rejection_reason" class="form-label">Rejection Reason <span class="text-danger">*</span></label>
                                            <textarea name="rejection_reason" id="rejection_reason" rows="4" class="form-control @error('rejection_reason') is-invalid @enderror">{{ old('rejection_reason', $jobApplication->rejection_reason) }}</textarea>
                                            @error('rejection_reason')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                        </div>
                                        <div class="col-lg-4">
                                            <div class="form-check mt-lg-4">
                                                <input class="form-check-input" type="checkbox" name="send_email" value="1" id="reject_send_email">
                                                <label class="form-check-label" for="reject_send_email">Send rejection email to applicant</label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mt-3">
                                        <button type="submit" class="btn btn-danger btn-md">Reject</button>
                                    </div>
                                </form>
                            </div>
                        @endcan
                    </div>

                    @can('sendEmail', $jobApplication)
                        @if (in_array($jobApplication->status, [App\Models\JobApplication::STATUS_SHORTLISTED, App\Models\JobApplication::STATUS_REJECTED], true))
                            <div class="border rounded-2 p-3 mt-3">
                                <h3 class="h6">Manual Email</h3>
                                <form method="POST" action="{{ route('recruitment.applications.send-email', $jobApplication) }}" class="d-flex flex-wrap gap-2 align-items-center" data-confirm="Send this email to {{ $jobApplication->email }}?">
                                    @csrf
                                    <input type="hidden" name="email_type" value="{{ $jobApplication->status === App\Models\JobApplication::STATUS_SHORTLISTED ? 'shortlisted' : 'rejected' }}">
                                    <button type="submit" class="btn btn-primary-outline btn-md">
                                        Send {{ $jobApplication->status === App\Models\JobApplication::STATUS_SHORTLISTED ? 'Shortlist' : 'Rejection' }} Email
                                    </button>
                                    <span class="text-muted small">Email is only sent when you click this button.</span>
                                </form>
                            </div>
                        @endif
                    @endcan
                @endif
            </section>

            <section class="bg-white border rounded-2 p-3 mb-4">
                <h2 class="h5 mb-3">Motivation</h2>
                <div class="text-pre-line">{{ $jobApplication->motivation }}</div>
            </section>

            <section class="bg-white border rounded-2 p-3">
                <h2 class="h5 mb-3">Status History</h2>
                <div class="table-responsive data-table-wrap">
                    <table class="table table-hover align-middle data-table mb-0">
                        <thead>
                            <tr>
                                <th>From</th>
                                <th>To</th>
                                <th>Changed By</th>
                                <th>Email</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($jobApplication->statusHistories as $history)
                                <tr>
                                    <td>{{ $history->from_status ? str($history->from_status)->headline() : '-' }}</td>
                                    <td>{{ str($history->to_status)->headline() }}</td>
                                    <td>{{ $history->changedBy?->name ?? 'System' }}</td>
                                    <td>{{ $history->email_sent ? 'Sent' : '-' }}</td>
                                    <td>{{ $history->created_at?->format('d M Y H:i') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">No status changes recorded yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </div>
@endsection
