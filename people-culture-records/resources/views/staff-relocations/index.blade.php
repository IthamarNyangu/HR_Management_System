@extends('layouts.app')

@section('title', 'Staff Relocations')
@section('page-title', 'Staff Relocations')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item active" aria-current="page">Staff Relocations</li>
@endsection

@section('page-actions')
    <div class="d-flex flex-wrap gap-2">
        <a href="{{ route('staff-relocations.archived') }}" class="btn btn-secondary btn-md">Archived</a>
        @include('partials.module-export-buttons', [
            'paginator' => $relocations,
            'excelRoute' => 'reports.relocations.export.excel',
            'pdfRoute' => 'reports.relocations.export.pdf',
        ])
        @can('create', App\Models\StaffRelocation::class)
            <a href="{{ route('staff-relocations.create') }}" class="btn btn-primary btn-md">New Relocation</a>
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
                <select name="from_province_id" class="form-select">
                    <option value="">All from provinces</option>
                    @foreach ($provinces as $province)
                        <option value="{{ $province->id }}" @selected((string) request('from_province_id') === (string) $province->id)>{{ $province->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6 col-xl-2">
                <select name="from_district_id" class="form-select">
                    <option value="">All from districts</option>
                    @foreach ($districts as $district)
                        <option value="{{ $district->id }}" @selected((string) request('from_district_id') === (string) $district->id)>{{ $district->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6 col-xl-2">
                <select name="from_facility_id" class="form-select">
                    <option value="">All from facilities</option>
                    @foreach ($facilities as $facility)
                        <option value="{{ $facility->id }}" @selected((string) request('from_facility_id') === (string) $facility->id)>{{ $facility->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6 col-xl-3">
                <select name="to_province_id" class="form-select">
                    <option value="">All to provinces</option>
                    @foreach ($provinces as $province)
                        <option value="{{ $province->id }}" @selected((string) request('to_province_id') === (string) $province->id)>{{ $province->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6 col-xl-3">
                <select name="to_district_id" class="form-select">
                    <option value="">All to districts</option>
                    @foreach ($districts as $district)
                        <option value="{{ $district->id }}" @selected((string) request('to_district_id') === (string) $district->id)>{{ $district->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6 col-xl-3">
                <select name="to_facility_id" class="form-select">
                    <option value="">All to facilities</option>
                    @foreach ($facilities as $facility)
                        <option value="{{ $facility->id }}" @selected((string) request('to_facility_id') === (string) $facility->id)>{{ $facility->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6 col-xl-2">
                <select name="project_id" class="form-select">
                    <option value="">All projects</option>
                    @foreach ($projects as $project)
                        <option value="{{ $project->id }}" @selected((string) request('project_id') === (string) $project->id)>{{ $project->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6 col-xl-2">
                <select name="department_id" class="form-select">
                    <option value="">All departments</option>
                    @foreach ($departments as $department)
                        <option value="{{ $department->id }}" @selected((string) request('department_id') === (string) $department->id)>{{ $department->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6 col-xl-2">
                <select name="job_title_id" class="form-select">
                    <option value="">All job titles</option>
                    @foreach ($jobTitles as $jobTitle)
                        <option value="{{ $jobTitle->id }}" @selected((string) request('job_title_id') === (string) $jobTitle->id)>{{ $jobTitle->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6 col-xl-3">
                <select name="relocation_reason_id" class="form-select">
                    <option value="">All relocation reasons</option>
                    @foreach ($relocationReasons as $reason)
                        <option value="{{ $reason->id }}" @selected((string) request('relocation_reason_id') === (string) $reason->id)>{{ $reason->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6 col-xl-2">
                <input type="date" name="effective_from" value="{{ request('effective_from') }}" class="form-control" aria-label="Effective date from">
            </div>
            <div class="col-md-6 col-xl-2">
                <input type="date" name="effective_to" value="{{ request('effective_to') }}" class="form-control" aria-label="Effective date to">
            </div>
            <div class="col-md-6 col-xl-2">
                <input type="number" step="0.01" min="0" name="relocation_amount_min" value="{{ request('relocation_amount_min') }}" class="form-control" placeholder="Amount min">
            </div>
            <div class="col-md-6 col-xl-2">
                <input type="number" step="0.01" min="0" name="relocation_amount_max" value="{{ request('relocation_amount_max') }}" class="form-control" placeholder="Amount max">
            </div>
            <div class="col-md-6 col-xl-2">
                <input type="number" name="year" value="{{ request('year') }}" class="form-control" placeholder="Year" min="2000" max="2100">
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-primary-outline btn-md">Filter</button>
            </div>
            <div class="col-auto">
                <a href="{{ route('staff-relocations.index') }}" class="btn btn-secondary btn-md">Reset</a>
            </div>
        </form>

        <div class="table-responsive data-table-wrap">
            <table class="table table-hover align-middle data-table">
                <thead>
                    <tr>
                        <th>Reference</th>
                        <th>Employee</th>
                        <th>From</th>
                        <th>To</th>
                        <th>Reason</th>
                        <th>Effective Date</th>
                        <th>Amount</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($relocations as $relocation)
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $relocation->reference_no }}</div>
                                <div class="small text-muted">{{ $relocation->jobTitle?->name ?? 'No job title' }}</div>
                            </td>
                            <td>
                                <div>{{ $relocation->employee?->full_name }}</div>
                                <div class="small text-muted">{{ $relocation->employee?->employee_no }}</div>
                            </td>
                            <td>
                                <div>{{ $relocation->fromProvince?->name }}</div>
                                <div class="small text-muted">{{ $relocation->fromDistrict?->name }}{{ $relocation->fromFacility ? ' - '.$relocation->fromFacility->name : '' }}</div>
                            </td>
                            <td>
                                <div>{{ $relocation->toProvince?->name }}</div>
                                <div class="small text-muted">{{ $relocation->toDistrict?->name }}{{ $relocation->toFacility ? ' - '.$relocation->toFacility->name : '' }}</div>
                            </td>
                            <td>{{ $relocation->relocationReason?->name ?? '-' }}</td>
                            <td>{{ $relocation->effective_date?->format('d M Y') }}</td>
                            <td>{{ $relocation->relocation_amount !== null ? number_format((float) $relocation->relocation_amount, 2) : '-' }}</td>
                            <td>
                                <div class="d-inline-flex gap-2">
                                    @can('view', $relocation)
                                        <a href="{{ route('staff-relocations.show', $relocation) }}" class="btn btn-sm btn-secondary">View</a>
                                    @endcan
                                    @can('update', $relocation)
                                        <a href="{{ route('staff-relocations.edit', $relocation) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                                    @endcan
                                    @can('archive', $relocation)
                                        <form method="POST" action="{{ route('staff-relocations.archive', $relocation) }}" data-confirm="true" data-confirm-title="Archive relocation?" data-confirm-message="This relocation will be moved to archived records and hidden from the active relocation register. Do you want to continue?" data-confirm-button="Archive relocation" data-confirm-variant="btn-warning">
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
                            <td colspan="8" class="text-center text-muted py-4">No staff relocations found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-3">
            {{ $relocations->links() }}
        </div>
    </div>
@endsection
