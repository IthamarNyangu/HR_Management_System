@extends('layouts.app')

@section('title', 'Staff Promotions')
@section('page-title', 'Staff Promotions')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item active" aria-current="page">Staff Promotions</li>
@endsection

@section('page-actions')
    <div class="d-flex flex-wrap gap-2">
        <a href="{{ route('staff-promotions.archived') }}" class="btn btn-secondary btn-md">Archived</a>
        @include('partials.module-export-buttons', [
            'paginator' => $promotions,
            'excelRoute' => 'reports.promotions.export.excel',
            'pdfRoute' => 'reports.promotions.export.pdf',
        ])
        @can('create', App\Models\StaffPromotion::class)
            <a href="{{ route('staff-promotions.create') }}" class="btn btn-primary btn-md">New Promotion</a>
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
                    <option value="">Facilities</option>
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
                <select name="department_id" class="form-select">
                    <option value="">All departments</option>
                    @foreach ($departments as $department)
                        <option value="{{ $department->id }}" @selected((string) request('department_id') === (string) $department->id)>{{ $department->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6 col-xl-3">
                <select name="old_job_title_id" class="form-select">
                    <option value="">All old job titles</option>
                    @foreach ($jobTitles as $jobTitle)
                        <option value="{{ $jobTitle->id }}" @selected((string) request('old_job_title_id') === (string) $jobTitle->id)>{{ $jobTitle->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6 col-xl-3">
                <select name="new_job_title_id" class="form-select">
                    <option value="">All new job titles</option>
                    @foreach ($jobTitles as $jobTitle)
                        <option value="{{ $jobTitle->id }}" @selected((string) request('new_job_title_id') === (string) $jobTitle->id)>{{ $jobTitle->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6 col-xl-3">
                <select name="promotion_type_id" class="form-select">
                    <option value="">All promotion types</option>
                    @foreach ($promotionTypes as $promotionType)
                        <option value="{{ $promotionType->id }}" @selected((string) request('promotion_type_id') === (string) $promotionType->id)>{{ $promotionType->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6 col-xl-2">
                <input type="date" name="promotion_from" value="{{ request('promotion_from') }}" class="form-control" aria-label="Promotion date from">
            </div>
            <div class="col-md-6 col-xl-2">
                <input type="date" name="promotion_to" value="{{ request('promotion_to') }}" class="form-control" aria-label="Promotion date to">
            </div>
            <div class="col-md-6 col-xl-2">
                <input type="number" name="year" value="{{ request('year') }}" class="form-control" placeholder="Year" min="2000" max="2100">
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-primary-outline btn-md">Filter</button>
            </div>
            <div class="col-auto">
                <a href="{{ route('staff-promotions.index') }}" class="btn btn-secondary btn-md">Reset</a>
            </div>
        </form>

        <div class="table-responsive data-table-wrap">
            <table class="table table-hover align-middle data-table">
                <thead>
                    <tr>
                        <th>Reference</th>
                        <th>Employee</th>
                        <th>Province</th>
                        <th>Old Job Title</th>
                        <th>New Job Title</th>
                        <th>Type</th>
                        <th>Promotion Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($promotions as $promotion)
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $promotion->reference_no }}</div>
                                <div class="small text-muted">{{ $promotion->effective_date?->format('d M Y') ?? 'No effective date' }}</div>
                            </td>
                            <td>
                                <div>{{ $promotion->employee?->full_name }}</div>
                                <div class="small text-muted">{{ $promotion->employee?->employee_no }}</div>
                            </td>
                            <td>{{ $promotion->province?->name }}</td>
                            <td>{{ $promotion->oldJobTitle?->name ?? '-' }}</td>
                            <td>{{ $promotion->newJobTitle?->name ?? '-' }}</td>
                            <td>{{ $promotion->promotionType?->name ?? '-' }}</td>
                            <td>{{ $promotion->promotion_date?->format('d M Y') }}</td>
                            <td>
                                <div class="d-inline-flex gap-2">
                                    @can('view', $promotion)
                                        <a href="{{ route('staff-promotions.show', $promotion) }}" class="btn btn-sm btn-secondary">View</a>
                                    @endcan
                                    @can('update', $promotion)
                                        <a href="{{ route('staff-promotions.edit', $promotion) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                                    @endcan
                                    @can('archive', $promotion)
                                        <form method="POST" action="{{ route('staff-promotions.archive', $promotion) }}" data-confirm="true" data-confirm-title="Archive promotion?" data-confirm-message="This promotion will be moved to archived records and hidden from the active promotion register. Do you want to continue?" data-confirm-button="Archive promotion" data-confirm-variant="btn-warning">
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
                            <td colspan="8" class="text-center text-muted py-4">No staff promotions found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-3">
            {{ $promotions->links() }}
        </div>
    </div>
@endsection
