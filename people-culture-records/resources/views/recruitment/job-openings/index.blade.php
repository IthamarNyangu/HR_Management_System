@extends('layouts.app')

@section('title', 'Job Openings')
@section('page-title', 'Job Openings')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('recruitment.index') }}">Recruitment</a></li>
    <li class="breadcrumb-item active" aria-current="page">Job Openings</li>
@endsection

@section('page-actions')
    <div class="d-flex gap-2">
        <a href="{{ route('recruitment.job-openings.archived') }}" class="btn btn-secondary btn-md">Archived</a>
        @can('create', App\Models\JobOpening::class)
            <a href="{{ route('recruitment.job-openings.create') }}" class="btn btn-primary btn-md">New Job Opening</a>
        @endcan
    </div>
@endsection

@section('content')
    <div class="bg-white border rounded-2 p-3">
        <form method="GET" class="row g-2 mb-4">
            <div class="col-md-6 col-xl-3">
                <input type="search" name="search" value="{{ request('search') }}" class="form-control" placeholder="Search reference or title">
            </div>
            <div class="col-md-6 col-xl-2">
                <select name="status" class="form-select">
                    <option value="">All statuses</option>
                    @foreach ($statuses as $status)
                        <option value="{{ $status }}" @selected(request('status') === $status)>{{ str($status)->headline() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6 col-xl-2">
                <select name="visibility" class="form-select">
                    <option value="">All visibility</option>
                    @foreach ($visibilities as $visibility)
                        <option value="{{ $visibility }}" @selected(request('visibility') === $visibility)>{{ str($visibility)->headline() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6 col-xl-2">
                <select name="province_id" class="form-select">
                    <option value="">All provinces / global</option>
                    @foreach ($provinces as $province)
                        <option value="{{ $province->id }}" @selected((string) request('province_id') === (string) $province->id)>{{ $province->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6 col-xl-3">
                <select name="department_id" class="form-select">
                    <option value="">All departments</option>
                    @foreach ($departments as $department)
                        <option value="{{ $department->id }}" @selected((string) request('department_id') === (string) $department->id)>{{ $department->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6 col-xl-3">
                <select name="project_id" class="form-select">
                    <option value="">All projects</option>
                    @foreach ($projects as $project)
                        <option value="{{ $project->id }}" @selected((string) request('project_id') === (string) $project->id)>{{ $project->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6 col-xl-2">
                <input type="date" name="closing_from" value="{{ request('closing_from') }}" class="form-control" aria-label="Closing date from">
            </div>
            <div class="col-md-6 col-xl-2">
                <input type="date" name="closing_to" value="{{ request('closing_to') }}" class="form-control" aria-label="Closing date to">
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-primary-outline btn-md">Filter</button>
            </div>
            <div class="col-auto">
                <a href="{{ route('recruitment.job-openings.index') }}" class="btn btn-secondary btn-md">Reset</a>
            </div>
        </form>

        <div class="table-responsive data-table-wrap">
            <table class="table table-hover align-middle data-table">
                <thead>
                    <tr>
                        <th>Reference</th>
                        <th>Title</th>
                        <th>Department</th>
                        <th>Location</th>
                        <th>Visibility</th>
                        <th>Status</th>
                        <th>Closing Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($jobOpenings as $job)
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $job->reference_no }}</div>
                                <div class="small text-muted">{{ $job->employmentType?->name ?? 'Not specified' }}</div>
                            </td>
                            <td>
                                <div>{{ $job->title }}</div>
                                <div class="small text-muted">{{ $job->project?->name ?? '-' }}</div>
                            </td>
                            <td>{{ $job->department?->name ?? '-' }}</td>
                            <td>{{ $job->location_label }}</td>
                            <td><span class="badge text-bg-light">{{ str($job->visibility)->headline() }}</span></td>
                            <td><span class="badge text-bg-{{ $job->status === 'published' ? 'success' : ($job->status === 'cancelled' ? 'danger' : 'light') }}">{{ str($job->status)->headline() }}</span></td>
                            <td>
                                <div>{{ $job->closing_date?->format('d M Y') }}</div>
                                <div class="small text-muted">{{ $job->closing_status_label }}</div>
                            </td>
                            <td>
                                <div class="d-inline-flex gap-2">
                                    @can('view', $job)
                                        <a href="{{ route('recruitment.job-openings.show', $job) }}" class="btn btn-sm btn-secondary">View</a>
                                    @endcan
                                    @can('update', $job)
                                        <a href="{{ route('recruitment.job-openings.edit', $job) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                                    @endcan
                                    @can('archive', $job)
                                        <form method="POST" action="{{ route('recruitment.job-openings.archive', $job) }}" data-confirm="true" data-confirm-title="Archive job opening?" data-confirm-message="This job opening will be moved to archived records and hidden from active recruitment pages. Do you want to continue?" data-confirm-button="Archive job opening" data-confirm-variant="btn-warning">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="btn btn-sm btn-outline-warning">Archive</button>
                                        </form>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">No job openings found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-3">
            {{ $jobOpenings->links() }}
        </div>
    </div>
@endsection
