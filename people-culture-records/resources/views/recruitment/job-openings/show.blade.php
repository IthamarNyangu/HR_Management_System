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
    <div class="d-flex flex-wrap gap-2">
        @can('publish', $jobOpening)
            @if ($jobOpening->status !== App\Models\JobOpening::STATUS_PUBLISHED)
                <form method="POST" action="{{ route('recruitment.job-openings.publish', $jobOpening) }}" data-confirm="true" data-confirm-title="Publish job opening?" data-confirm-message="Published external or both-visible jobs can appear on public careers pages if the closing date has not passed." data-confirm-button="Publish job">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="btn btn-primary btn-md">Publish</button>
                </form>
            @endif
        @endcan
        @can('close', $jobOpening)
            @if ($jobOpening->status !== App\Models\JobOpening::STATUS_CLOSED)
                <form method="POST" action="{{ route('recruitment.job-openings.close', $jobOpening) }}" data-confirm="true" data-confirm-title="Close job opening?" data-confirm-message="Closed jobs are hidden from public careers pages and API." data-confirm-button="Close job">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="btn btn-secondary btn-md">Close</button>
                </form>
            @endif
        @endcan
        @can('cancel', $jobOpening)
            @if ($jobOpening->status !== App\Models\JobOpening::STATUS_CANCELLED)
                <form method="POST" action="{{ route('recruitment.job-openings.cancel', $jobOpening) }}" data-confirm="true" data-confirm-title="Cancel job opening?" data-confirm-message="Cancelled jobs are hidden from public careers pages and API." data-confirm-button="Cancel job" data-confirm-variant="btn-warning">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="btn btn-outline-warning btn-md">Cancel</button>
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
            <section class="bg-white border rounded-2 p-3 h-100">
                <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
                    <div>
                        <div class="text-muted small">Job Opening</div>
                        <h2 class="h5 mb-0">{{ $jobOpening->title }}</h2>
                    </div>
                    <span class="badge text-bg-{{ $jobOpening->status === 'published' ? 'success' : ($jobOpening->status === 'cancelled' ? 'danger' : 'light') }}">{{ str($jobOpening->status)->headline() }}</span>
                </div>

                <dl class="row mb-0">
                    <dt class="col-5">Visibility</dt>
                    <dd class="col-7">{{ str($jobOpening->visibility)->headline() }}</dd>
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
                    <dt class="col-5">Opening Date</dt>
                    <dd class="col-7">{{ $jobOpening->opening_date?->format('d M Y') ?? '-' }}</dd>
                    <dt class="col-5">Closing Date</dt>
                    <dd class="col-7">{{ $jobOpening->closing_date?->format('d M Y') }}<br><span class="small text-muted">{{ $jobOpening->closing_status_label }}</span></dd>
                </dl>
            </section>
        </div>

        <div class="col-xl-8">
            <section class="bg-white border rounded-2 p-3">
                <h2 class="h5 mb-3">Structured Job Content</h2>
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
                        <div class="mb-4">
                            <h3 class="h6 text-danger">{{ $label }}</h3>
                            <div class="text-pre-line">{{ $jobOpening->{$field} }}</div>
                        </div>
                    @endif
                @endforeach
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
