@extends('layouts.app')

@section('title', $plan->reference_no)
@section('page-title', $plan->reference_no)

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('staff-establishment.index') }}">Staff Establishment</a></li>
    <li class="breadcrumb-item active" aria-current="page">{{ $plan->reference_no }}</li>
@endsection

@section('page-actions')
    <div class="d-flex flex-wrap gap-2 justify-content-end">
        <a href="{{ route('staff-establishment.export.excel', $plan) }}" class="btn btn-primary btn-md">
            <i class="bi bi-file-earmark-excel" aria-hidden="true"></i>
            Export Excel
        </a>
        <a href="{{ route('staff-establishment.export.pdf', $plan) }}" class="btn btn-secondary btn-md">
            <i class="bi bi-file-earmark-pdf" aria-hidden="true"></i>
            Export PDF
        </a>
        @can('update', $plan)
            <a href="{{ route('staff-establishment.edit', $plan) }}" class="btn btn-primary-outline btn-md">Edit</a>
        @endcan
        @can('archive', $plan)
            <form method="POST" action="{{ route('staff-establishment.archive', $plan) }}" data-confirm="true" data-confirm-title="Archive staff establishment plan?" data-confirm-message="This plan will move to archived records. Do you want to continue?" data-confirm-button="Archive plan" data-confirm-variant="btn-warning">
                @csrf
                @method('PATCH')
                <button type="submit" class="btn btn-outline-warning btn-md">Archive</button>
            </form>
        @endcan
        <a href="{{ route('staff-establishment.index') }}" class="btn btn-secondary btn-md">Back</a>
    </div>
@endsection

@section('content')
    <div class="d-flex flex-column gap-3">
        <section class="bg-white border rounded-2 p-4">
            <div class="row g-3 align-items-start">
                <div class="col-lg-8">
                    <div class="d-flex flex-wrap gap-2 mb-2">
                        <span class="badge text-bg-{{ $plan->status === 'approved' ? 'success' : 'secondary' }}">{{ str($plan->status)->headline() }}</span>
                        <span class="badge text-bg-light border">{{ $plan->project?->name ?? 'All projects' }}</span>
                        <span class="badge text-bg-light border">{{ $plan->effective_month?->format('M Y') }}</span>
                    </div>
                    <h2 class="h5 mb-1">{{ $plan->title }}</h2>
                    @if ($plan->notes)
                        <p class="text-muted mb-0">{{ $plan->notes }}</p>
                    @endif
                </div>
                <div class="col-lg-4">
                    <div class="small text-muted">Created by {{ $plan->createdBy?->name ?? '-' }}</div>
                    <div class="small text-muted">Approved by {{ $plan->approvedBy?->name ?? '-' }}</div>
                </div>
            </div>
        </section>

        <section class="bg-white border rounded-2 p-3">
            <div class="row row-cols-1 row-cols-md-5 g-3">
                <div class="col"><div class="summary-tile h-100"><div class="summary-label">Budgeted</div><div class="summary-value">{{ $summary['budgeted'] }}</div></div></div>
                <div class="col"><div class="summary-tile h-100"><div class="summary-label">Filled</div><div class="summary-value">{{ $summary['filled'] }}</div></div></div>
                <div class="col"><div class="summary-tile h-100"><div class="summary-label">Vacant</div><div class="summary-value">{{ $summary['vacant'] }}</div></div></div>
                <div class="col"><div class="summary-tile h-100"><div class="summary-label">Overstaffed</div><div class="summary-value">{{ $summary['overstaffed'] }}</div></div></div>
                <div class="col"><div class="summary-tile h-100"><div class="summary-label">Vacancy Rate</div><div class="summary-value">{{ $summary['vacancy_rate'] }}%</div></div></div>
            </div>
        </section>

        <section class="bg-white border rounded-2 p-3">
            <div class="d-flex justify-content-between align-items-center gap-3 mb-3">
                <div>
                    <h2 class="h5 mb-1">Establishment and Vacancies</h2>
                    <p class="text-muted mb-0">Filled counts are calculated from active employees matching the job title, project, department, and location.</p>
                </div>
            </div>

            <div class="table-responsive data-table-wrap">
                <table class="table table-hover align-middle data-table">
                    <thead>
                        <tr>
                            <th>Job Title</th>
                            <th>Project</th>
                            <th>Department</th>
                            <th>Province</th>
                            <th>District</th>
                            <th>Facility</th>
                            <th>Budgeted</th>
                            <th>Filled</th>
                            <th>Vacant</th>
                            <th>Overstaffed</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($rows as $row)
                            <tr @class(['table-warning' => $row['vacant'] > 0, 'table-success' => $row['vacant'] === 0 && $row['overstaffed'] === 0, 'table-danger' => $row['overstaffed'] > 0])>
                                <td class="fw-semibold">{{ $row['job_title'] }}</td>
                                <td>{{ $row['project'] }}</td>
                                <td>{{ $row['department'] }}</td>
                                <td>{{ $row['province'] }}</td>
                                <td>{{ $row['district'] }}</td>
                                <td>{{ $row['facility'] }}</td>
                                <td>{{ $row['budgeted'] }}</td>
                                <td>{{ $row['filled'] }}</td>
                                <td><span class="badge text-bg-{{ $row['vacant'] > 0 ? 'warning' : 'success' }}">{{ $row['vacant'] }}</span></td>
                                <td><span class="badge text-bg-{{ $row['overstaffed'] > 0 ? 'danger' : 'light' }}">{{ $row['overstaffed'] }}</span></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center text-muted py-4">No establishment lines visible to your role.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
@endsection
