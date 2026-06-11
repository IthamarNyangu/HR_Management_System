@extends('layouts.app')

@section('title', 'Disciplinary Cases')
@section('page-title', 'Disciplinary Cases')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item active" aria-current="page">Disciplinary Cases</li>
@endsection

@section('page-actions')
    <div class="d-flex flex-wrap gap-2">
        <a href="{{ route('disciplinary-cases.archived') }}" class="btn btn-secondary btn-md">Archived</a>
        @include('partials.module-export-buttons', [
            'paginator' => $cases,
            'excelRoute' => 'reports.disciplinary-cases.export.excel',
            'pdfRoute' => 'reports.disciplinary-cases.export.pdf',
        ])
        @can('create', App\Models\DisciplinaryCase::class)
            <a href="{{ route('disciplinary-cases.create') }}" class="btn btn-primary btn-md">New Case</a>
        @endcan
    </div>
@endsection

@section('content')
    <div class="bg-white border rounded-2 p-3">
        <form method="GET" class="row g-2 mb-4">
            <div class="col-md-6 col-xl-3">
                <input type="search" name="search" value="{{ request('search') }}" class="form-control" placeholder="Search reference or employee">
            </div>
            <div class="col-md-6 col-xl-2">
                <select name="province_id" class="form-select">
                    <option value="">All provinces</option>
                    @foreach ($provinces as $province)
                        <option value="{{ $province->id }}" @selected((string) request('province_id') === (string) $province->id)>{{ $province->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6 col-xl-2">
                <select name="district_id" class="form-select">
                    <option value="">All districts</option>
                    @foreach ($districts as $district)
                        <option value="{{ $district->id }}" @selected((string) request('district_id') === (string) $district->id)>{{ $district->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6 col-xl-2">
                <select name="facility_id" class="form-select">
                    <option value="">All facilities</option>
                    @foreach ($facilities as $facility)
                        <option value="{{ $facility->id }}" @selected((string) request('facility_id') === (string) $facility->id)>{{ $facility->name }}</option>
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
            <div class="col-md-6 col-xl-3">
                <select name="offence_category_id" class="form-select">
                    <option value="">All offence categories</option>
                    @foreach ($offenceCategories as $offenceCategory)
                        <option value="{{ $offenceCategory->id }}" @selected((string) request('offence_category_id') === (string) $offenceCategory->id)>{{ $offenceCategory->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6 col-xl-3">
                <select name="penalty_type_id" class="form-select">
                    <option value="">All penalties</option>
                    @foreach ($penaltyTypes as $penaltyType)
                        <option value="{{ $penaltyType->id }}" @selected((string) request('penalty_type_id') === (string) $penaltyType->id)>{{ $penaltyType->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6 col-xl-3">
                <select name="case_status_id" class="form-select">
                    <option value="">All statuses</option>
                    @foreach ($caseStatuses as $caseStatus)
                        <option value="{{ $caseStatus->id }}" @selected((string) request('case_status_id') === (string) $caseStatus->id)>{{ $caseStatus->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6 col-xl-3">
                <input type="date" name="effective_from" value="{{ request('effective_from') }}" class="form-control" aria-label="Effective date from">
            </div>
            <div class="col-md-6 col-xl-3">
                <input type="date" name="effective_to" value="{{ request('effective_to') }}" class="form-control" aria-label="Effective date to">
            </div>
            <div class="col-md-6 col-xl-3">
                <input type="date" name="expiry_from" value="{{ request('expiry_from') }}" class="form-control" aria-label="Expiry date from">
            </div>
            <div class="col-md-6 col-xl-3">
                <input type="date" name="expiry_to" value="{{ request('expiry_to') }}" class="form-control" aria-label="Expiry date to">
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-primary-outline btn-md">Filter</button>
            </div>
            <div class="col-auto">
                <a href="{{ route('disciplinary-cases.index') }}" class="btn btn-secondary btn-md">Reset</a>
            </div>
        </form>

        <div class="table-responsive data-table-wrap">
            <table class="table table-hover align-middle data-table">
                <thead>
                    <tr>
                        <th>Reference</th>
                        <th>Employee</th>
                        <th>Province</th>
                        <th>Offence</th>
                        <th>Penalty</th>
                        <th>Status</th>
                        <th>Expiry</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($cases as $case)
                        @php
                            $statusCode = strtoupper((string) $case->caseStatus?->code);
                            $statusClass = match ($statusCode) {
                                'ACTIVE' => 'success',
                                'SUBMITTED' => 'primary',
                                'CLOSED' => 'secondary',
                                default => 'light',
                            };
                        @endphp
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $case->reference_no }}</div>
                                <div class="small text-muted">{{ $case->effective_date?->format('d M Y') }}</div>
                            </td>
                            <td>
                                <div>{{ $case->employee?->full_name }}</div>
                                <div class="small text-muted">{{ $case->employee?->employee_no }}</div>
                            </td>
                            <td>{{ $case->province?->name }}</td>
                            <td>{{ $case->offenceCategory?->name ?? '-' }}</td>
                            <td>{{ $case->penaltyType?->name ?? '-' }}</td>
                            <td><span class="badge text-bg-{{ $statusClass }}">{{ $case->caseStatus?->name ?? '-' }}</span></td>
                            <td>
                                <div>{{ $case->expiry_date?->format('d M Y') ?? '-' }}</div>
                                @if ($case->is_expired)
                                    <span class="badge text-bg-danger">Expired</span>
                                @elseif ($case->expires_soon)
                                    <span class="badge text-bg-warning">Expiring soon</span>
                                @endif
                            </td>
                            <td>
                                <div class="d-inline-flex gap-2">
                                    @can('view', $case)
                                        <a href="{{ route('disciplinary-cases.show', $case) }}" class="btn btn-sm btn-secondary">View</a>
                                    @endcan
                                    @can('update', $case)
                                        <a href="{{ route('disciplinary-cases.edit', $case) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                                    @endcan
                                    @can('archive', $case)
                                        <form method="POST" action="{{ route('disciplinary-cases.archive', $case) }}" data-confirm="true" data-confirm-title="Archive disciplinary case?" data-confirm-message="This case will be moved to archived records and hidden from the active case register. Do you want to continue?" data-confirm-button="Archive case" data-confirm-variant="btn-warning">
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
                            <td colspan="8" class="text-center text-muted py-4">No disciplinary cases found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-3">
            {{ $cases->links() }}
        </div>
    </div>
@endsection
