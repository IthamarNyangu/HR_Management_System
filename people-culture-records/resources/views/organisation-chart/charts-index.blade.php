@extends('layouts.app')

@section('title', 'Organisation Chart')
@section('page-title', 'Organisation Chart')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item active" aria-current="page">Organisation Chart</li>
@endsection

@section('page-actions')
    <div class="d-flex flex-wrap gap-2">
        @can('create', App\Models\OrganisationChart::class)
            <a href="{{ route('organisation-chart.archived') }}" class="btn btn-secondary btn-md">Archived</a>
            <a href="{{ route('organisation-chart.create') }}" class="btn btn-primary btn-md">New Chart</a>
        @endcan
    </div>
@endsection

@section('content')
    <section class="bg-white border rounded-2 p-3 mb-3">
        <form method="GET" class="row g-2 align-items-center">
            <div class="col-md-6 col-xl-4">
                <input type="search" name="search" value="{{ request('search') }}" class="form-control" placeholder="Search charts">
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
                <select name="status" class="form-select">
                    <option value="">All statuses</option>
                    @foreach ($statuses as $status)
                        <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-primary-outline btn-md">Filter</button>
            </div>
            <div class="col-auto">
                <a href="{{ route('organisation-chart.index') }}" class="btn btn-secondary btn-md">Reset</a>
            </div>
        </form>
    </section>

    <section class="bg-white border rounded-2 p-3">
        <div class="table-responsive data-table-wrap">
            <table class="table table-hover align-middle data-table">
                <thead>
                    <tr>
                        <th>Chart</th>
                        <th>Project</th>
                        <th>Status</th>
                        <th>Effective Date</th>
                        <th>Boxes</th>
                        <th>Created By</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($charts as $chart)
                        <tr>
                            <td>
                                <a href="{{ route('organisation-chart.show', $chart) }}" class="fw-semibold">{{ $chart->title }}</a>
                                @if ($chart->description)
                                    <div class="small text-muted">{{ Str::limit($chart->description, 90) }}</div>
                                @endif
                            </td>
                            <td>{{ $chart->project?->name ?? 'Organisation-wide' }}</td>
                            <td><span class="badge text-bg-{{ $chart->status === 'published' ? 'success' : 'secondary' }}">{{ ucfirst($chart->status) }}</span></td>
                            <td>{{ $chart->effective_date?->format('d M Y') ?? '-' }}</td>
                            <td>{{ $chart->nodes_count }}</td>
                            <td>{{ $chart->createdBy?->name ?? '-' }}</td>
                            <td>
                                <div class="d-inline-flex gap-2 flex-nowrap">
                                    <a href="{{ route('organisation-chart.show', $chart) }}" class="btn btn-sm btn-secondary">View</a>
                                    @can('update', $chart)
                                        <a href="{{ route('organisation-chart.edit', $chart) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                                    @endcan
                                    @can('archive', $chart)
                                        <form method="POST" action="{{ route('organisation-chart.archive', $chart) }}" data-confirm="true" data-confirm-title="Archive organisation chart?" data-confirm-message="This chart will move to archived records. Do you want to continue?" data-confirm-button="Archive chart" data-confirm-variant="btn-warning">
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
                            <td colspan="7" class="text-center text-muted py-4">No organisation charts found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-3">
            {{ $charts->links() }}
        </div>
    </section>
@endsection
