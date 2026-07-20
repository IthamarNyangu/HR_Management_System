@extends('layouts.app')

@section('title', 'Recruitment')
@section('page-title', 'Recruitment')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item active" aria-current="page">Recruitment</li>
@endsection

@section('page-actions')
    @can('create', App\Models\JobOpening::class)
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('recruitment.applications.index') }}" class="btn btn-secondary btn-md">Applications</a>
            <a href="{{ route('recruitment.job-openings.create') }}" class="btn btn-primary btn-md">New Job Opening</a>
        </div>
    @endcan
@endsection

@section('content')
    <div class="d-flex flex-column gap-4">
        <div class="row row-cols-1 row-cols-md-2 row-cols-xl-5 g-3">
            @foreach ($cards as $card)
                <div class="col">
                    <div class="summary-tile h-100">
                        <div class="summary-label">{{ $card['label'] }}</div>
                        <div class="summary-value">{{ $card['value'] }}</div>
                    </div>
                </div>
            @endforeach
        </div>

        <section class="bg-white border rounded-2 p-3">
            <div class="d-flex justify-content-between align-items-center gap-3 mb-3">
                <h2 class="h5 mb-0">Job Openings</h2>
                <a href="{{ route('recruitment.job-openings.index') }}" class="btn btn-secondary btn-sm">View all</a>
            </div>

            <div class="table-responsive data-table-wrap">
                <table class="table table-hover align-middle data-table">
                    <thead>
                        <tr>
                            <th>Reference</th>
                            <th>Title</th>
                            <th>Department</th>
                            <th>Location</th>
                            <th>Status</th>
                            <th>Closing Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($latestJobs as $job)
                            <tr>
                                <td><a href="{{ route('recruitment.job-openings.show', $job) }}">{{ $job->reference_no }}</a></td>
                                <td>{{ $job->title }}</td>
                                <td>{{ $job->department?->name ?? '-' }}</td>
                                <td>{{ $job->location_label }}</td>
                                <td><span class="badge text-bg-light">{{ str($job->status)->headline() }}</span></td>
                                <td>{{ $job->closing_date?->format('d M Y') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">No recruitment job openings recorded yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="bg-white border rounded-2 p-3">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div>
                    <h2 class="h5 mb-1">Applications</h2>
                    <div class="text-muted">Review submitted public job applications and download documents securely.</div>
                </div>
                <a href="{{ route('recruitment.applications.index') }}" class="btn btn-primary-outline btn-md">View Applications</a>
            </div>
        </section>
    </div>
@endsection
