@extends('layouts.app')

@section('title', 'Archived Job Openings')
@section('page-title', 'Archived Job Openings')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('recruitment.index') }}">Recruitment</a></li>
    <li class="breadcrumb-item"><a href="{{ route('recruitment.job-openings.index') }}">Job Openings</a></li>
    <li class="breadcrumb-item active" aria-current="page">Archived</li>
@endsection

@section('content')
    <div class="bg-white border rounded-2 p-3">
        <form method="GET" class="row g-2 mb-4">
            <div class="col-md-6 col-xl-4">
                <input type="search" name="search" value="{{ request('search') }}" class="form-control" placeholder="Search reference or title">
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-primary-outline btn-md">Filter</button>
            </div>
            <div class="col-auto">
                <a href="{{ route('recruitment.job-openings.archived') }}" class="btn btn-secondary btn-md">Reset</a>
            </div>
        </form>

        <div class="table-responsive data-table-wrap">
            <table class="table table-hover align-middle data-table">
                <thead>
                    <tr>
                        <th>Reference</th>
                        <th>Title</th>
                        <th>Department</th>
                        <th>Province</th>
                        <th>Status</th>
                        <th>Archived</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($jobOpenings as $job)
                        <tr>
                            <td>{{ $job->reference_no }}</td>
                            <td>{{ $job->title }}</td>
                            <td>{{ $job->department?->name ?? '-' }}</td>
                            <td>{{ $job->province_list_label }}</td>
                            <td>{{ str($job->status)->headline() }}</td>
                            <td>
                                <div>{{ $job->deleted_at?->format('d M Y') }}</div>
                                <div class="small text-muted">{{ $job->archivedBy?->name ?? '-' }}</div>
                            </td>
                            <td>
                                @can('restore', $job)
                                    <form method="POST" action="{{ route('recruitment.job-openings.restore', $job->id) }}" data-confirm="true" data-confirm-title="Restore job opening?" data-confirm-message="This job opening will return to the active recruitment register. Do you want to continue?" data-confirm-button="Restore job opening">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="btn btn-sm btn-primary-outline">Restore</button>
                                    </form>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">No archived job openings found.</td>
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
