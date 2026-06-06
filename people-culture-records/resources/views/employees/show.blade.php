@extends('layouts.app')

@section('title', $employee->display_name)
@section('page-title', $employee->full_name)

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('employees.index') }}">Employees</a></li>
    <li class="breadcrumb-item active" aria-current="page">{{ $employee->employee_no }}</li>
@endsection

@section('page-actions')
    <div class="d-flex gap-2">
        <a href="{{ route('organisation-chart.index', ['search' => $employee->employee_no]) }}" class="btn btn-secondary btn-md">View in Chart</a>
        @can('update', $employee)
            <a href="{{ route('employees.edit', $employee) }}" class="btn btn-primary btn-md">Edit Employee</a>
        @endcan
        <a href="{{ route('employees.index') }}" class="btn btn-secondary btn-md">Back</a>
    </div>
@endsection

@section('content')
    @php($canViewSensitivePersonalData = auth()->user()->can('viewSensitivePersonalData', $employee))

    <div class="row g-3">
        <div class="col-lg-4">
            <section class="bg-white border rounded-2 p-4 h-100">
                <div class="text-muted small">Employee Number</div>
                <div class="h4">{{ $employee->employee_no }}</div>
                <hr>
                <dl class="mb-0">
                    <dt>Full Name</dt>
                    <dd>{{ $employee->full_name }}</dd>
                    <dt>Gender</dt>
                    <dd>{{ $employee->gender ?? '-' }}</dd>
                    <dt>Date of Birth</dt>
                    <dd>{{ $canViewSensitivePersonalData ? ($employee->date_of_birth?->format('d M Y') ?? '-') : 'Restricted' }}</dd>
                    <dt>National ID</dt>
                    <dd>{{ $canViewSensitivePersonalData ? ($employee->national_id ?? '-') : 'Restricted' }}</dd>
                </dl>
            </section>
        </div>

        <div class="col-lg-8">
            <section class="bg-white border rounded-2 p-4 mb-3">
                <h2 class="h5">Employment Details</h2>
                @if ($activeTemporaryAppointment)
                    <div class="alert alert-info py-2 mb-3">
                        Active Temporary Appointment:
                        <a href="{{ route('temporary-appointments.show', $activeTemporaryAppointment) }}" class="alert-link">
                            {{ $activeTemporaryAppointment->temporaryJobTitle?->name }}
                        </a>
                        until {{ $activeTemporaryAppointment->end_date?->format('d M Y') }}.
                    </div>
                @endif
                <div class="row">
                    <div class="col-md-6"><strong>Project:</strong> {{ $employee->project?->name ?? '-' }}</div>
                    <div class="col-md-6"><strong>Department:</strong> {{ $employee->department?->name ?? '-' }}</div>
                    <div class="col-md-6"><strong>Job Title:</strong> {{ $employee->jobTitle?->name ?? '-' }}</div>
                    <div class="col-md-6"><strong>Status:</strong> {{ $employee->employmentStatus?->name ?? '-' }}</div>
                    <div class="col-md-6"><strong>Hire Date:</strong> {{ $employee->hire_date?->format('d M Y') ?? '-' }}</div>
                    @if ($employee->termination_date || $employee->terminationReason || $employee->termination_comment)
                        <div class="col-md-6"><strong>Termination Date:</strong> {{ $employee->termination_date?->format('d M Y') ?? '-' }}</div>
                        <div class="col-md-6"><strong>Termination Reason:</strong> {{ $employee->terminationReason?->name ?? '-' }}</div>
                        <div class="col-md-6"><strong>Termination Comment:</strong> {{ $employee->termination_comment ?: '-' }}</div>
                    @endif
                    <div class="col-md-6">
                        <strong>Line Manager:</strong>
                        @if ($employee->supervisor)
                            <a href="{{ route('employees.show', $employee->supervisor) }}">{{ $employee->supervisor->display_name }}</a>
                        @else
                            {{ $employee->supervisor_name ?? '-' }}
                        @endif
                    </div>
                </div>
            </section>

            <section class="bg-white border rounded-2 p-4 mb-3">
                <h2 class="h5">Direct Reports</h2>
                <div class="table-responsive data-table-wrap">
                    <table class="table table-hover align-middle data-table">
                        <thead>
                            <tr>
                                <th>Employee No</th>
                                <th>Employee Name</th>
                                <th>Job Title</th>
                                <th>Location</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($directReports as $report)
                                <tr>
                                    <td>{{ $report->employee_no }}</td>
                                    <td><a href="{{ route('employees.show', $report) }}">{{ $report->full_name }}</a></td>
                                    <td>{{ $report->jobTitle?->name ?? '-' }}</td>
                                    <td>{{ collect([$report->province?->name, $report->district?->name, $report->facility?->name])->filter()->implode(' / ') ?: '-' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-4">No direct reports linked yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="bg-white border rounded-2 p-4 mb-3">
                <h2 class="h5">Location & Contact</h2>
                <div class="row">
                    <div class="col-md-6"><strong>Province:</strong> {{ $employee->province?->name }}</div>
                    <div class="col-md-6"><strong>District:</strong> {{ $employee->district?->name }}</div>
                    <div class="col-md-6"><strong>Facility:</strong> {{ $employee->facility?->name ?? '-' }}</div>
                    <div class="col-md-6"><strong>Email:</strong> {{ $employee->email ?? '-' }}</div>
                    <div class="col-md-6"><strong>Phone:</strong> {{ $employee->phone ?? '-' }}</div>
                </div>
            </section>

            <section class="bg-white border rounded-2 p-4 mb-3">
                <h2 class="h5">Notes</h2>
                <p class="mb-0 text-muted">{{ $canViewSensitivePersonalData ? ($employee->notes ?: 'No notes recorded.') : 'Restricted to Admin and HR Manager.' }}</p>
            </section>

            <section class="bg-white border rounded-2 p-4 mb-3">
                <h2 class="h5">Disciplinary History</h2>
                <div class="table-responsive data-table-wrap">
                    <table class="table table-hover align-middle data-table">
                        <thead>
                            <tr>
                                <th>Reference</th>
                                <th>Offence</th>
                                <th>Penalty</th>
                                <th>Status</th>
                                <th>Effective</th>
                                <th>Expiry</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($disciplinaryCases as $case)
                                <tr>
                                    <td><a href="{{ route('disciplinary-cases.show', $case) }}">{{ $case->reference_no }}</a></td>
                                    <td>{{ $case->offenceCategory?->name ?? '-' }}</td>
                                    <td>{{ $case->penaltyType?->name ?? '-' }}</td>
                                    <td><span class="badge text-bg-secondary">{{ $case->caseStatus?->name ?? '-' }}</span></td>
                                    <td>{{ $case->effective_date?->format('d M Y') }}</td>
                                    <td>{{ $case->expiry_date?->format('d M Y') ?? '-' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">No disciplinary history recorded.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="bg-white border rounded-2 p-4 mb-3">
                <h2 class="h5">Promotion History</h2>
                <div class="table-responsive data-table-wrap">
                    <table class="table table-hover align-middle data-table">
                        <thead>
                            <tr>
                                <th>Reference</th>
                                <th>Old Job Title</th>
                                <th>New Job Title</th>
                                <th>Type</th>
                                <th>Promotion Date</th>
                                <th>Effective Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($staffPromotions as $promotion)
                                <tr>
                                    <td><a href="{{ route('staff-promotions.show', $promotion) }}">{{ $promotion->reference_no }}</a></td>
                                    <td>{{ $promotion->oldJobTitle?->name ?? '-' }}</td>
                                    <td>{{ $promotion->newJobTitle?->name ?? '-' }}</td>
                                    <td>{{ $promotion->promotionType?->name ?? '-' }}</td>
                                    <td>{{ $promotion->promotion_date?->format('d M Y') }}</td>
                                    <td>{{ $promotion->effective_date?->format('d M Y') ?? '-' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">No promotion history recorded.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="bg-white border rounded-2 p-4">
                <h2 class="h5">Relocation History</h2>
                <div class="table-responsive data-table-wrap">
                    <table class="table table-hover align-middle data-table">
                        <thead>
                            <tr>
                                <th>Reference</th>
                                <th>From</th>
                                <th>To</th>
                                <th>Reason</th>
                                <th>Effective Date</th>
                                <th>Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($staffRelocations as $relocation)
                                <tr>
                                    <td><a href="{{ route('staff-relocations.show', $relocation) }}">{{ $relocation->reference_no }}</a></td>
                                    <td>{{ $relocation->fromProvince?->name ?? '-' }}{{ $relocation->fromDistrict ? ' - '.$relocation->fromDistrict->name : '' }}</td>
                                    <td>{{ $relocation->toProvince?->name ?? '-' }}{{ $relocation->toDistrict ? ' - '.$relocation->toDistrict->name : '' }}</td>
                                    <td>{{ $relocation->relocationReason?->name ?? '-' }}</td>
                                    <td>{{ $relocation->effective_date?->format('d M Y') }}</td>
                                    <td>{{ $relocation->relocation_amount !== null ? number_format((float) $relocation->relocation_amount, 2) : '-' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">No relocation history recorded.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="bg-white border rounded-2 p-4 mt-3">
                <h2 class="h5">Temporary Appointment History</h2>
                <div class="table-responsive data-table-wrap">
                    <table class="table table-hover align-middle data-table">
                        <thead>
                            <tr>
                                <th>Reference</th>
                                <th>Temporary Job Title</th>
                                <th>Start Date</th>
                                <th>End Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($temporaryAppointments as $appointment)
                                <tr>
                                    <td><a href="{{ route('temporary-appointments.show', $appointment) }}">{{ $appointment->reference_no }}</a></td>
                                    <td>{{ $appointment->temporaryJobTitle?->name ?? '-' }}</td>
                                    <td>{{ $appointment->start_date?->format('d M Y') }}</td>
                                    <td>{{ $appointment->end_date?->format('d M Y') }}</td>
                                    <td>
                                        <span class="badge text-bg-secondary">{{ $appointment->appointmentStatus?->name ?? '-' }}</span>
                                        <div class="small text-muted">{{ $appointment->date_status_label }}</div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">No temporary appointment history recorded.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </div>
@endsection
