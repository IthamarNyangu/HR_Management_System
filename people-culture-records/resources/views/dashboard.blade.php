@extends('layouts.app')

@section('title', 'Dashboard')
@section('hide-page-header', true)

@section('content')
    <div class="d-flex flex-column gap-4">
        <section>
            <div class="row row-cols-1 row-cols-md-2 row-cols-xl-4 g-3">
                @foreach ($needsAttention as $card)
                    <div class="col">
                        <a href="{{ $card['url'] }}" class="text-decoration-none text-reset">
                            <div class="dashboard-card dashboard-card-{{ $card['tone'] }} h-100">
                                <div class="d-flex justify-content-between align-items-start gap-3">
                                    <div>
                                        <div class="dashboard-label">{{ $card['label'] }}</div>
                                        <div class="dashboard-value">{{ $card['value'] }}</div>
                                    </div>
                                    <span class="dashboard-icon">
                                        <i class="bi {{ $card['icon'] }}" aria-hidden="true"></i>
                                    </span>
                                </div>
                            </div>
                        </a>
                    </div>
                @endforeach
            </div>
        </section>

        <section class="bg-white border rounded-2 p-3">
            <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
                <div>
                    <h2 class="h5 mb-1">Quick Actions</h2>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    @can('create', App\Models\Employee::class)
                        <a href="{{ route('employees.create') }}" class="btn btn-primary btn-md">
                            <i class="bi bi-person-plus" aria-hidden="true"></i>
                            Add Employee
                        </a>
                    @endcan
                    @can('create', App\Models\DisciplinaryCase::class)
                        <a href="{{ route('disciplinary-cases.create') }}" class="btn btn-primary-outline btn-md">
                            <i class="bi bi-shield-plus" aria-hidden="true"></i>
                            Create Disciplinary Case
                        </a>
                    @endcan
                    @can('create', App\Models\StaffPromotion::class)
                        <a href="{{ route('staff-promotions.create') }}" class="btn btn-primary-outline btn-md">
                            <i class="bi bi-graph-up-arrow" aria-hidden="true"></i>
                            Add Promotion
                        </a>
                    @endcan
                    @can('create', App\Models\StaffRelocation::class)
                        <a href="{{ route('staff-relocations.create') }}" class="btn btn-primary-outline btn-md">
                            <i class="bi bi-geo-alt" aria-hidden="true"></i>
                            Add Relocation
                        </a>
                    @endcan
                    @can('create', App\Models\TemporaryAppointment::class)
                        <a href="{{ route('temporary-appointments.create') }}" class="btn btn-primary-outline btn-md">
                            <i class="bi bi-calendar-event" aria-hidden="true"></i>
                            Add Temporary Appointment
                        </a>
                    @endcan
                    @can('view-reports')
                        <a href="{{ route('reports.index') }}" class="btn btn-secondary btn-md">
                            <i class="bi bi-file-earmark-spreadsheet" aria-hidden="true"></i>
                            View Reports
                        </a>
                    @endcan
                    @can('import-employees')
                        <a href="{{ route('imports.employees.create') }}" class="btn btn-secondary btn-md">
                            <i class="bi bi-cloud-arrow-up" aria-hidden="true"></i>
                            Import Employees
                        </a>
                    @endcan
                    @can('view-imports')
                        <a href="{{ route('imports.index') }}" class="btn btn-secondary btn-md">
                            <i class="bi bi-clock-history" aria-hidden="true"></i>
                            Import History
                        </a>
                    @endcan
                    <a href="{{ $submittedStatus ? route('disciplinary-cases.index', ['case_status_id' => $submittedStatus->id]) : route('disciplinary-cases.index') }}" class="btn btn-secondary btn-md">Awaiting Approval</a>
                    <a href="{{ route('disciplinary-cases.index', ['expiry_from' => today()->toDateString(), 'expiry_to' => today()->addDays(30)->toDateString()]) }}" class="btn btn-secondary btn-md">Expiring Cases</a>
                    <a href="{{ route('disciplinary-cases.archived') }}" class="btn btn-secondary btn-md">Archived Records</a>
                </div>
            </div>
        </section>

        <div class="row g-4">
            <div class="col-xl-5">
                <section class="bg-white border rounded-2 p-3 h-100">
                    <h2 class="h5 mb-3">People Overview</h2>
                    <div class="row row-cols-1 row-cols-sm-3 g-3 mb-4">
                        @foreach ($peopleOverview as $card)
                            <div class="col">
                                <div class="summary-tile h-100">
                                    <div class="summary-label">{{ $card['label'] }}</div>
                                    <div class="summary-value">{{ $card['value'] }}</div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <h3 class="h6 mb-3">Employees by Province</h3>
                    <div class="list-group list-group-flush">
                        @forelse ($employeesByProvince as $province)
                            <div class="list-group-item px-0 d-flex justify-content-between align-items-center">
                                <span>{{ $province->province_name }}</span>
                                <span class="badge text-bg-light">{{ $province->total }}</span>
                            </div>
                        @empty
                            <div class="text-muted small">No employee records available.</div>
                        @endforelse
                    </div>
                </section>
            </div>

            <div class="col-xl-7">
                <section class="bg-white border rounded-2 p-3 h-100">
                    <h2 class="h5 mb-3">Disciplinary Case Overview</h2>
                    <div class="row row-cols-1 row-cols-md-3 g-3 mb-4">
                        @foreach ($caseOverview as $card)
                            <div class="col">
                                <div class="summary-tile h-100">
                                    <div class="summary-label">{{ $card['label'] }}</div>
                                    <div class="summary-value">{{ $card['value'] }}</div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="row g-4">
                        <div class="col-md-6">
                            <h3 class="h6 mb-3">Cases by Status</h3>
                            <div class="list-group list-group-flush">
                                @forelse ($casesByStatus as $row)
                                    <div class="list-group-item px-0 d-flex justify-content-between align-items-center">
                                        <span>{{ $row->label }}</span>
                                        <span class="badge text-bg-light">{{ $row->total }}</span>
                                    </div>
                                @empty
                                    <div class="text-muted small">No case status data yet.</div>
                                @endforelse
                            </div>
                        </div>
                        <div class="col-md-6">
                            <h3 class="h6 mb-3">Cases by Offence Category</h3>
                            <div class="list-group list-group-flush">
                                @forelse ($casesByOffenceCategory as $row)
                                    <div class="list-group-item px-0 d-flex justify-content-between align-items-center">
                                        <span>{{ $row->label }}</span>
                                        <span class="badge text-bg-light">{{ $row->total }}</span>
                                    </div>
                                @empty
                                    <div class="text-muted small">No offence category data yet.</div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </div>

        <section class="bg-white border rounded-2 p-3">
            <h2 class="h5 mb-3">HR Movement</h2>
            <div class="row g-4">
                <div class="col-xl-6">
                    <div class="row row-cols-1 row-cols-sm-2 g-3 mb-4">
                        @foreach ($promotionOverview as $card)
                            <div class="col">
                                <div class="summary-tile h-100">
                                    <div class="summary-label">{{ $card['label'] }}</div>
                                    <div class="summary-value">{{ $card['value'] }}</div>
                                </div>
                            </div>
                        @endforeach
                        @foreach ($relocationOverview as $card)
                            <div class="col">
                                <div class="summary-tile h-100">
                                    <div class="summary-label">{{ $card['label'] }}</div>
                                    <div class="summary-value">{{ $card['value'] }}</div>
                                </div>
                            </div>
                        @endforeach
                        @foreach ($temporaryAppointmentOverview as $card)
                            <div class="col">
                                <div class="summary-tile h-100">
                                    <div class="summary-label">{{ $card['label'] }}</div>
                                    <div class="summary-value">{{ $card['value'] }}</div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
                <div class="col-xl-2">
                    <h3 class="h6 mb-3">Latest Promotions</h3>
                    <div class="list-group list-group-flush">
                        @forelse ($latestPromotions as $promotion)
                            <a href="{{ route('staff-promotions.show', $promotion) }}" class="list-group-item px-0 d-flex justify-content-between align-items-start gap-3 text-decoration-none">
                                <span>
                                    <span class="fw-semibold">{{ $promotion->reference_no }}</span>
                                    <span class="d-block small text-muted">{{ $promotion->employee?->display_name }} - {{ $promotion->oldJobTitle?->name ?? '-' }} to {{ $promotion->newJobTitle?->name ?? '-' }}</span>
                                </span>
                                <span class="badge text-bg-light">{{ $promotion->promotion_date?->format('d M') }}</span>
                            </a>
                        @empty
                            <div class="text-muted small">No promotions recorded yet.</div>
                        @endforelse
                    </div>
                </div>
                <div class="col-xl-2">
                    <h3 class="h6 mb-3">Latest Relocations</h3>
                    <div class="list-group list-group-flush">
                        @forelse ($latestRelocations as $relocation)
                            <a href="{{ route('staff-relocations.show', $relocation) }}" class="list-group-item px-0 d-flex justify-content-between align-items-start gap-3 text-decoration-none">
                                <span>
                                    <span class="fw-semibold">{{ $relocation->reference_no }}</span>
                                    <span class="d-block small text-muted">{{ $relocation->employee?->display_name }} - {{ $relocation->fromProvince?->name ?? '-' }} to {{ $relocation->toProvince?->name ?? '-' }}</span>
                                </span>
                                <span class="badge text-bg-light">{{ $relocation->effective_date?->format('d M') }}</span>
                            </a>
                        @empty
                            <div class="text-muted small">No relocations recorded yet.</div>
                        @endforelse
                    </div>
                </div>
                <div class="col-xl-2">
                    <h3 class="h6 mb-3">Latest Appointments</h3>
                    <div class="list-group list-group-flush">
                        @forelse ($latestTemporaryAppointments as $appointment)
                            <a href="{{ route('temporary-appointments.show', $appointment) }}" class="list-group-item px-0 d-flex justify-content-between align-items-start gap-3 text-decoration-none">
                                <span>
                                    <span class="fw-semibold">{{ $appointment->reference_no }}</span>
                                    <span class="d-block small text-muted">{{ $appointment->employee?->display_name }} - {{ $appointment->temporaryJobTitle?->name ?? '-' }}</span>
                                </span>
                                <span class="badge text-bg-light">{{ $appointment->end_date?->format('d M') }}</span>
                            </a>
                        @empty
                            <div class="text-muted small">No temporary appointments recorded yet.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </section>

        <section class="bg-white border rounded-2 p-3">
            <div class="d-flex justify-content-between align-items-center gap-3 mb-3">
                <div>
                    <h2 class="h5 mb-1">Recent Activity</h2>
                    <p class="text-muted small mb-0">Latest system history visible to your role and province.</p>
                </div>
                <a href="{{ route('activity-logs.index') }}" class="btn btn-secondary btn-sm">View all activity</a>
            </div>

            <div class="activity-list">
                @forelse ($recentActivities as $activity)
                    <div class="activity-item">
                        <div class="activity-dot"></div>
                        <div class="min-w-0">
                            <div class="fw-semibold">{{ $activity->description }}</div>
                            <div class="small text-muted">
                                {{ $activity->actor_name }}
                                @if ($activity->reference)
                                    - {{ $activity->reference }}
                                @endif
                                - {{ $activity->created_at->diffForHumans() }}
                            </div>
                            @if ($activity->location_label)
                                <div class="small text-muted mt-1">{{ $activity->location_label }}</div>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="text-muted small">No activity recorded yet.</div>
                @endforelse
            </div>
        </section>
    </div>
@endsection
