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
    <div class="row g-4">
        <div class="col-xl-4">
            <section class="bg-white border rounded-2 p-3 h-100">
                <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
                    <div>
                        <div class="text-muted small">Applicant</div>
                        <h2 class="h5 mb-0">{{ $jobApplication->full_name }}</h2>
                    </div>
                    <span class="badge text-bg-{{ $jobApplication->status === 'withdrawn' ? 'warning' : 'success' }}">{{ str($jobApplication->status)->headline() }}</span>
                </div>

                <dl class="row mb-0">
                    <dt class="col-5">Email</dt>
                    <dd class="col-7">{{ $jobApplication->email }}</dd>
                    <dt class="col-5">Phone</dt>
                    <dd class="col-7">{{ $jobApplication->phone }}</dd>
                    <dt class="col-5">Province</dt>
                    <dd class="col-7">{{ $jobApplication->province ?? '-' }}</dd>
                    <dt class="col-5">District</dt>
                    <dd class="col-7">{{ $jobApplication->district ?? '-' }}</dd>
                    <dt class="col-5">Submitted</dt>
                    <dd class="col-7">{{ $jobApplication->submitted_at?->format('d M Y H:i') }}</dd>
                    <dt class="col-5">Withdrawn</dt>
                    <dd class="col-7">{{ $jobApplication->withdrawn_at?->format('d M Y H:i') ?? '-' }}</dd>
                </dl>
            </section>
        </div>

        <div class="col-xl-8">
            <section class="bg-white border rounded-2 p-3 mb-4">
                <h2 class="h5 mb-3">Job Applied For</h2>
                <dl class="row mb-0">
                    <dt class="col-md-3">Reference</dt>
                    <dd class="col-md-9"><a href="{{ route('recruitment.job-openings.show', $jobApplication->jobOpening) }}">{{ $jobApplication->jobOpening?->reference_no }}</a></dd>
                    <dt class="col-md-3">Title</dt>
                    <dd class="col-md-9">{{ $jobApplication->jobOpening?->title }}</dd>
                    <dt class="col-md-3">Department</dt>
                    <dd class="col-md-9">{{ $jobApplication->jobOpening?->department?->name ?? '-' }}</dd>
                    <dt class="col-md-3">Location</dt>
                    <dd class="col-md-9">{{ $jobApplication->jobOpening?->location_label }}</dd>
                </dl>
            </section>

            <section class="bg-white border rounded-2 p-3 mb-4">
                <h2 class="h5 mb-3">Education and Experience</h2>
                <dl class="row mb-0">
                    <dt class="col-md-3">Qualification</dt>
                    <dd class="col-md-9">{{ $jobApplication->highest_qualification }}</dd>
                    <dt class="col-md-3">Field of Study</dt>
                    <dd class="col-md-9">{{ $jobApplication->field_of_study ?? '-' }}</dd>
                    <dt class="col-md-3">Experience</dt>
                    <dd class="col-md-9">{{ $jobApplication->years_of_experience ?? '-' }}</dd>
                    <dt class="col-md-3">Current Employer</dt>
                    <dd class="col-md-9">{{ $jobApplication->current_employer ?? '-' }}</dd>
                </dl>
            </section>

            <section class="bg-white border rounded-2 p-3 mb-4">
                <h2 class="h5 mb-3">Motivation</h2>
                <div class="text-pre-line">{{ $jobApplication->motivation }}</div>
            </section>

            <section class="bg-white border rounded-2 p-3">
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
                            @foreach ($jobApplication->documents as $document)
                                <tr>
                                    <td>{{ $document->readable_type }}</td>
                                    <td>{{ $document->original_filename }}</td>
                                    <td>{{ number_format($document->file_size / 1024, 1) }} KB</td>
                                    <td>{{ $document->uploaded_at?->format('d M Y H:i') }}</td>
                                    <td>
                                        <a href="{{ route('recruitment.applications.documents.download', [$jobApplication, $document]) }}" class="btn btn-sm btn-primary-outline">Download</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </div>
@endsection
