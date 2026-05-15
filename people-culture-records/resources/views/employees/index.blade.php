@extends('layouts.app')

@section('title', 'Employees')
@section('page-title', 'Employees')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item active" aria-current="page">Employees</li>
@endsection

@section('page-actions')
    <div class="d-flex gap-2">
        <a href="{{ route('employees.archived') }}" class="btn btn-secondary btn-md">Archived</a>
        @can('create', App\Models\Employee::class)
            <a href="{{ route('employees.create') }}" class="btn btn-primary btn-md">New Employee</a>
        @endcan
    </div>
@endsection

@section('content')
    <div class="bg-white border rounded-2 p-3">
        <form method="GET" class="row g-2 mb-4">
            <div class="col-md-6 col-xl-3">
                <input type="search" name="search" value="{{ request('search') }}" class="form-control" placeholder="Search employees">
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
                <select name="department_id" class="form-select">
                    <option value="">All departments</option>
                    @foreach ($departments as $department)
                        <option value="{{ $department->id }}" @selected((string) request('department_id') === (string) $department->id)>{{ $department->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6 col-xl-3">
                <select name="job_title_id" class="form-select">
                    <option value="">All job titles</option>
                    @foreach ($jobTitles as $jobTitle)
                        <option value="{{ $jobTitle->id }}" @selected((string) request('job_title_id') === (string) $jobTitle->id)>{{ $jobTitle->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6 col-xl-3">
                <select name="employment_status_id" class="form-select">
                    <option value="">All statuses</option>
                    @foreach ($employmentStatuses as $employmentStatus)
                        <option value="{{ $employmentStatus->id }}" @selected((string) request('employment_status_id') === (string) $employmentStatus->id)>{{ $employmentStatus->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-primary-outline btn-md">Filter</button>
            </div>
            <div class="col-auto">
                <a href="{{ route('employees.index') }}" class="btn btn-secondary btn-md">Reset</a>
            </div>
        </form>

        <div class="table-responsive data-table-wrap">
            <table class="table table-hover align-middle data-table">
                <thead>
                    <tr>
                        <th>Employee ID</th>
                        <th>Employee Name</th>
                        <th>Province</th>
                        <th>District</th>
                        <th>Facility</th>
                        <th>Project</th>
                        <th>Job Title</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($employees as $employee)
                        <tr>
                            <td class="fw-semibold">{{ $employee->employee_no }}</td>
                            <td>{{ $employee->full_name }}</td>
                            <td>{{ $employee->province?->name }}</td>
                            <td>{{ $employee->district?->name }}</td>
                            <td>{{ $employee->facility?->name ?? '-' }}</td>
                            <td>{{ $employee->project?->name ?? '-' }}</td>
                            <td>{{ $employee->jobTitle?->name ?? '-' }}</td>
                            <td>{{ $employee->employmentStatus?->name ?? '-' }}</td>
                            <td class="text-end">
                                <div class="d-inline-flex gap-2">
                                    @can('view', $employee)
                                        <a href="{{ route('employees.show', $employee) }}" class="btn btn-sm btn-secondary">View</a>
                                    @endcan
                                    @can('update', $employee)
                                        <a href="{{ route('employees.edit', $employee) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                                    @endcan
                                    @can('archive', $employee)
                                        <form method="POST" action="{{ route('employees.archive', $employee) }}" data-confirm="true" data-confirm-title="Archive employee?" data-confirm-message="This employee will be moved to archived records and hidden from the active employee register. Do you want to continue?" data-confirm-button="Archive employee" data-confirm-variant="btn-warning">
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
                            <td colspan="9" class="text-center text-muted py-4">No employees found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-3">
            {{ $employees->links() }}
        </div>
    </div>
@endsection
