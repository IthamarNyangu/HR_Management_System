@extends('layouts.app')

@section('title', 'Job Applications')
@section('page-title', 'Job Applications')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('recruitment.index') }}">Recruitment</a></li>
    <li class="breadcrumb-item active" aria-current="page">Applications</li>
@endsection

@section('content')
    <div class="bg-white border rounded-2 p-3 mb-4">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-lg-4">
                <input type="search" name="search" value="{{ request('search') }}" class="form-control" placeholder="Search reference, applicant, email, or job">
            </div>
            <div class="col-lg-3">
                <select name="job_opening_id" class="form-select">
                    <option value="">All job openings</option>
                    @foreach ($jobOpenings as $jobOpening)
                        <option value="{{ $jobOpening->id }}" @selected(request('job_opening_id') == $jobOpening->id)>
                            {{ $jobOpening->reference_no }} - {{ $jobOpening->title }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-lg-2">
                <select name="status" class="form-select">
                    <option value="">All statuses</option>
                    <option value="submitted" @selected(request('status') === 'submitted')>Submitted</option>
                    <option value="withdrawn" @selected(request('status') === 'withdrawn')>Withdrawn</option>
                </select>
            </div>
            <div class="col-lg-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary-outline btn-md">Filter</button>
                <a href="{{ route('recruitment.applications.index') }}" class="btn btn-secondary btn-md">Reset</a>
            </div>
        </form>
    </div>

    <section class="bg-white border rounded-2 p-3">
        <div class="d-flex justify-content-between align-items-center gap-3 mb-3">
            <div>
                <h2 class="h5 mb-1">Applications</h2>
                <div class="text-muted">{{ $applications->total() }} record(s) found.</div>
            </div>
        </div>

        <div class="table-responsive data-table-wrap">
            <table class="table table-hover align-middle data-table">
                <thead>
                    <tr>
                        <th>Reference</th>
                        <th>Applicant</th>
                        <th>Email</th>
                        <th>Job</th>
                        <th>Location</th>
                        <th>Status</th>
                        <th>Submitted</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($applications as $application)
                        <tr>
                            <td><a href="{{ route('recruitment.applications.show', $application) }}">{{ $application->reference_no }}</a></td>
                            <td>{{ $application->full_name }}</td>
                            <td>{{ $application->email }}</td>
                            <td>
                                <div class="fw-semibold">{{ $application->jobOpening?->title }}</div>
                                <div class="small text-muted">{{ $application->jobOpening?->reference_no }}</div>
                            </td>
                            <td>{{ $application->jobOpening?->location_label }}</td>
                            <td>
                                <span class="badge text-bg-{{ $application->status === 'withdrawn' ? 'warning' : 'success' }}">
                                    {{ str($application->status)->headline() }}
                                </span>
                            </td>
                            <td>{{ $application->submitted_at?->format('d M Y H:i') }}</td>
                            <td>
                                <a href="{{ route('recruitment.applications.show', $application) }}" class="btn btn-sm btn-secondary">View</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">No applications found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-3">
            {{ $applications->links() }}
        </div>
    </section>
@endsection
