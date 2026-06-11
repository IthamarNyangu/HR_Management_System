@extends('layouts.app')

@section('title', 'Staff Establishment')
@section('page-title', 'Staff Establishment')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item active" aria-current="page">Staff Establishment</li>
@endsection

@section('page-actions')
    <div class="d-flex flex-wrap gap-2">
        @can('create', App\Models\StaffEstablishmentPlan::class)
            <a href="{{ route('staff-establishment.archived') }}" class="btn btn-secondary btn-md">Archived</a>
            <a href="{{ route('staff-establishment.create') }}" class="btn btn-primary btn-md">New Establishment Plan</a>
        @endcan
    </div>
@endsection

@section('content')
    @if ($latestPlan && $latestSummary)
        <section class="bg-white border rounded-2 p-3 mb-3">
            <div class="d-flex flex-column flex-lg-row justify-content-between gap-3 mb-3">
                <div>
                    <h2 class="h5 mb-1">Latest Visible Establishment</h2>
                    <div class="text-muted">{{ $latestPlan->title }} - {{ $latestPlan->effective_month?->format('M Y') }}</div>
                </div>
                <div class="d-flex flex-wrap gap-2 align-self-start">
                    <a href="{{ route('staff-establishment.show', $latestPlan) }}" class="btn btn-secondary btn-sm">Open plan</a>
                    <a href="{{ route('staff-establishment.export.excel', $latestPlan) }}" class="btn btn-primary btn-sm">
                        <i class="bi bi-file-earmark-excel" aria-hidden="true"></i>
                        Export Excel
                    </a>
                    <a href="{{ route('staff-establishment.export.pdf', $latestPlan) }}" class="btn btn-secondary btn-sm">
                        <i class="bi bi-file-earmark-pdf" aria-hidden="true"></i>
                        Export PDF
                    </a>
                </div>
            </div>
            <div class="row row-cols-1 row-cols-md-5 g-3">
                <div class="col"><div class="summary-tile h-100"><div class="summary-label">Budgeted</div><div class="summary-value">{{ $latestSummary['budgeted'] }}</div></div></div>
                <div class="col"><div class="summary-tile h-100"><div class="summary-label">Filled</div><div class="summary-value">{{ $latestSummary['filled'] }}</div></div></div>
                <div class="col"><div class="summary-tile h-100"><div class="summary-label">Vacant</div><div class="summary-value">{{ $latestSummary['vacant'] }}</div></div></div>
                <div class="col"><div class="summary-tile h-100"><div class="summary-label">Overstaffed</div><div class="summary-value">{{ $latestSummary['overstaffed'] }}</div></div></div>
                <div class="col"><div class="summary-tile h-100"><div class="summary-label">Vacancy Rate</div><div class="summary-value">{{ $latestSummary['vacancy_rate'] }}%</div></div></div>
            </div>
        </section>
    @endif

    <section class="bg-white border rounded-2 p-3">
        <form method="GET" class="row g-2 mb-4">
            <div class="col-md-6 col-xl-4">
                <input type="search" name="search" value="{{ request('search') }}" class="form-control" placeholder="Search reference or title">
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
                        <option value="{{ $status }}" @selected(request('status') === $status)>{{ str($status)->headline() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-primary-outline btn-md">Filter</button>
            </div>
            <div class="col-auto">
                <a href="{{ route('staff-establishment.index') }}" class="btn btn-secondary btn-md">Reset</a>
            </div>
        </form>

        <div class="table-responsive data-table-wrap">
            <table class="table table-hover align-middle data-table">
                <thead>
                    <tr>
                        <th>Reference</th>
                        <th>Plan</th>
                        <th>Project</th>
                        <th>Status</th>
                        <th>Effective Month</th>
                        <th>Lines</th>
                        <th>Created By</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($plans as $plan)
                        <tr>
                            <td class="fw-semibold">{{ $plan->reference_no }}</td>
                            <td>
                                <a href="{{ route('staff-establishment.show', $plan) }}" class="fw-semibold">{{ $plan->title }}</a>
                                @if ($plan->notes)
                                    <div class="small text-muted">{{ Str::limit($plan->notes, 80) }}</div>
                                @endif
                            </td>
                            <td>{{ $plan->project?->name ?? 'All projects' }}</td>
                            <td><span class="badge text-bg-{{ $plan->status === 'approved' ? 'success' : 'secondary' }}">{{ str($plan->status)->headline() }}</span></td>
                            <td>{{ $plan->effective_month?->format('M Y') }}</td>
                            <td>{{ $plan->lines_count }}</td>
                            <td>{{ $plan->createdBy?->name ?? '-' }}</td>
                            <td>
                                <div class="d-inline-flex gap-2 flex-nowrap">
                                    <a href="{{ route('staff-establishment.show', $plan) }}" class="btn btn-sm btn-secondary">View</a>
                                    @can('update', $plan)
                                        <a href="{{ route('staff-establishment.edit', $plan) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                                    @endcan
                                    @can('archive', $plan)
                                        <form method="POST" action="{{ route('staff-establishment.archive', $plan) }}" data-confirm="true" data-confirm-title="Archive staff establishment plan?" data-confirm-message="This plan will move to archived records. Do you want to continue?" data-confirm-button="Archive plan" data-confirm-variant="btn-warning">
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
                            <td colspan="8" class="text-center text-muted py-4">No staff establishment plans found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-3">
            {{ $plans->links() }}
        </div>
    </section>
@endsection
