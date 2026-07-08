@extends('layouts.app')

@section('title', 'Temporary Appointments')
@section('page-title', 'Temporary Appointments')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item active" aria-current="page">Temporary Appointments</li>
@endsection

@section('page-actions')
    <div class="d-flex gap-2">
        <a href="{{ route('temporary-appointments.archived') }}" class="btn btn-secondary btn-md">Archived</a>
        @can('create', App\Models\TemporaryAppointment::class)
            <a href="{{ route('temporary-appointments.create') }}" class="btn btn-primary btn-md">New Appointment</a>
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
                <select name="current_job_title_id" class="form-select">
                    <option value="">All current job titles</option>
                    @foreach ($jobTitles as $jobTitle)
                        <option value="{{ $jobTitle->id }}" @selected((string) request('current_job_title_id') === (string) $jobTitle->id)>{{ $jobTitle->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6 col-xl-3">
                <select name="temporary_job_title_id" class="form-select">
                    <option value="">All temporary job titles</option>
                    @foreach ($jobTitles as $jobTitle)
                        <option value="{{ $jobTitle->id }}" @selected((string) request('temporary_job_title_id') === (string) $jobTitle->id)>{{ $jobTitle->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6 col-xl-3">
                <select name="appointment_status_id" class="form-select">
                    <option value="">All statuses</option>
                    @foreach ($appointmentStatuses as $status)
                        <option value="{{ $status->id }}" @selected((string) request('appointment_status_id') === (string) $status->id)>{{ $status->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6 col-xl-2">
                <input type="date" name="start_from" value="{{ request('start_from') }}" class="form-control" aria-label="Start date from">
            </div>
            <div class="col-md-6 col-xl-2">
                <input type="date" name="start_to" value="{{ request('start_to') }}" class="form-control" aria-label="Start date to">
            </div>
            <div class="col-md-6 col-xl-2">
                <input type="date" name="end_from" value="{{ request('end_from') }}" class="form-control" aria-label="End date from">
            </div>
            <div class="col-md-6 col-xl-2">
                <input type="date" name="end_to" value="{{ request('end_to') }}" class="form-control" aria-label="End date to">
            </div>
            <div class="col-auto">
                <div class="form-check pt-2">
                    <input id="active" name="active" type="checkbox" value="1" class="form-check-input" @checked(request()->boolean('active'))>
                    <label for="active" class="form-check-label">Active</label>
                </div>
            </div>
            <div class="col-auto">
                <div class="form-check pt-2">
                    <input id="ending_soon" name="ending_soon" type="checkbox" value="1" class="form-check-input" @checked(request()->boolean('ending_soon'))>
                    <label for="ending_soon" class="form-check-label">Ending soon</label>
                </div>
            </div>
            <div class="col-auto">
                <div class="form-check pt-2">
                    <input id="expired" name="expired" type="checkbox" value="1" class="form-check-input" @checked(request()->boolean('expired'))>
                    <label for="expired" class="form-check-label">Expired</label>
                </div>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-primary-outline btn-md">Filter</button>
            </div>
            <div class="col-auto">
                <a href="{{ route('temporary-appointments.index') }}" class="btn btn-secondary btn-md">Reset</a>
            </div>
        </form>

        <div class="table-responsive data-table-wrap">
            <table class="table table-hover align-middle data-table">
                <thead>
                    <tr>
                        <th>Reference</th>
                        <th>Employee</th>
                        <th>Temporary Job Title</th>
                        <th>Start Date</th>
                        <th>End Date</th>
                        <th>Days Remaining</th>
                        <th>Status</th>
                        <th>Province</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($appointments as $appointment)
                        <tr>
                            <td class="fw-semibold">{{ $appointment->reference_no }}</td>
                            <td>
                                <div>{{ $appointment->employee?->full_name }}</div>
                                <div class="small text-muted">{{ $appointment->employee?->employee_no }}</div>
                            </td>
                            <td>{{ $appointment->temporaryJobTitle?->name }}</td>
                            <td>{{ $appointment->start_date?->format('d M Y') }}</td>
                            <td>{{ $appointment->end_date?->format('d M Y') }}</td>
                            <td><span class="badge text-bg-light">{{ $appointment->date_status_label }}</span></td>
                            <td>{{ $appointment->appointmentStatus?->name }}</td>
                            <td>{{ $appointment->province?->name }}</td>
                            <td>
                                <div class="d-inline-flex gap-2">
                                    @can('view', $appointment)
                                        <a href="{{ route('temporary-appointments.show', $appointment) }}" class="btn btn-sm btn-secondary">View</a>
                                    @endcan
                                    @can('update', $appointment)
                                        <a href="{{ route('temporary-appointments.edit', $appointment) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                                    @endcan
                                    @can('archive', $appointment)
                                        <form method="POST" action="{{ route('temporary-appointments.archive', $appointment) }}" data-confirm="true" data-confirm-title="Archive appointment?" data-confirm-message="This temporary appointment will be moved to archived records. Do you want to continue?" data-confirm-button="Archive appointment" data-confirm-variant="btn-warning">
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
                            <td colspan="9" class="text-center text-muted py-4">No temporary appointments found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-3">
            {{ $appointments->links() }}
        </div>
    </div>
@endsection
