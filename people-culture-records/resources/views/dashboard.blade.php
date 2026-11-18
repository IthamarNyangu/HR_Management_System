@extends('layouts.app')

@section('title', 'Dashboard')
@section('hide-page-header', true)

@section('content')
    @php
        $dashboardTabIcons = [
            'overview' => 'bi-grid',
            'recruitment' => 'bi-briefcase',
            'promotions' => 'bi-graph-up-arrow',
            'relocations' => 'bi-geo-alt',
            'temporary-appointments' => 'bi-calendar2-week',
            'disciplinary' => 'bi-shield-exclamation',
            'funeral-benefits' => 'bi-heart-pulse',
        ];
        $dashboardTabTones = [
            'overview' => 'navy',
            'recruitment' => 'blue',
            'promotions' => 'green',
            'relocations' => 'teal',
            'temporary-appointments' => 'amber',
            'disciplinary' => 'red',
            'funeral-benefits' => 'violet',
        ];
    @endphp
    <div class="dashboard-analytics d-flex flex-column gap-4">
        <nav class="analytics-nav" aria-label="Dashboard analytics">
            @foreach ($dashboardTabs as $tab => $label)
                <a href="{{ route('dashboard', ['tab' => $tab]) }}"
                   data-tone="{{ $dashboardTabTones[$tab] }}"
                   class="analytics-tab {{ $activeDashboardTab === $tab ? 'active' : '' }}"
                   @if ($activeDashboardTab === $tab) aria-current="page" @endif>
                    <span class="analytics-tab-icon"><i class="bi {{ $dashboardTabIcons[$tab] }}" aria-hidden="true"></i></span>
                    <span class="analytics-tab-label">{{ $label }}</span>
                </a>
            @endforeach
        </nav>

        @if ($activeDashboardTab === 'overview')
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
                                        <span class="dashboard-icon"><i class="bi {{ $card['icon'] }}" aria-hidden="true"></i></span>
                                    </div>
                                </div>
                            </a>
                        </div>
                    @endforeach
                </div>
            </section>

            <section class="bg-white border rounded-2 p-3">
                <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
                    <h2 class="h5 mb-0">Quick Actions</h2>
                    <div class="d-flex flex-wrap gap-2">
                        @can('create', App\Models\Employee::class)
                            <a href="{{ route('employees.create') }}" class="btn btn-primary btn-md"><i class="bi bi-person-plus" aria-hidden="true"></i> Add Employee</a>
                        @endcan
                        @can('create', App\Models\DisciplinaryCase::class)
                            <a href="{{ route('disciplinary-cases.create') }}" class="btn btn-primary-outline btn-md"><i class="bi bi-shield-plus" aria-hidden="true"></i> Create Disciplinary Case</a>
                        @endcan
                        @can('create', App\Models\JobOpening::class)
                            <a href="{{ route('recruitment.job-openings.create') }}" class="btn btn-primary-outline btn-md"><i class="bi bi-briefcase" aria-hidden="true"></i> Add Job Opening</a>
                        @endcan
                        @can('view-reports')
                            <a href="{{ route('reports.index') }}" class="btn btn-secondary btn-md"><i class="bi bi-file-earmark-spreadsheet" aria-hidden="true"></i> View Reports</a>
                        @endcan
                    </div>
                </div>
            </section>

            <div class="row g-4">
                <div class="col-xl-6">
                    <section class="bg-white border rounded-2 p-3 h-100">
                        <h2 class="h5 mb-3">People Overview</h2>
                        <div class="row row-cols-1 row-cols-sm-3 g-3 mb-4">
                            @foreach ($peopleOverview as $card)
                                <div class="col"><div class="summary-tile h-100"><div class="summary-label">{{ $card['label'] }}</div><div class="summary-value">{{ $card['value'] }}</div></div></div>
                            @endforeach
                        </div>
                        <h3 class="h6 mb-3">Employees by Province</h3>
                        <div class="list-group list-group-flush">
                            @forelse ($employeesByProvince as $province)
                                <div class="list-group-item px-0 d-flex justify-content-between"><span>{{ $province->province_name }}</span><span class="badge text-bg-light">{{ $province->total }}</span></div>
                            @empty
                                <div class="text-muted small">No employee records available.</div>
                            @endforelse
                        </div>
                    </section>
                </div>
                <div class="col-xl-6">
                    <section class="bg-white border rounded-2 p-3 h-100">
                        <div class="d-flex justify-content-between align-items-center gap-3 mb-3">
                            <h2 class="h5 mb-0">Staff Establishment Overview</h2>
                            @can('viewAny', App\Models\StaffEstablishmentPlan::class)
                                <a href="{{ route('staff-establishment.index') }}" class="btn btn-secondary btn-sm">Open</a>
                            @endcan
                        </div>
                        <div class="row row-cols-1 row-cols-sm-2 g-3">
                            @foreach ($staffEstablishmentOverview as $card)
                                <div class="col"><div class="summary-tile h-100"><div class="summary-label">{{ $card['label'] }}</div><div class="summary-value">{{ $card['value'] }}</div></div></div>
                            @endforeach
                        </div>
                    </section>
                </div>
            </div>

            <section class="bg-white border rounded-2 p-3">
                <div class="d-flex justify-content-between align-items-center gap-3 mb-3">
                    <h2 class="h5 mb-0">Recent Activity</h2>
                    @can('view-audit-logs')<a href="{{ route('activity-logs.index') }}" class="btn btn-secondary btn-sm">View all activity</a>@endcan
                </div>
                <div class="activity-list">
                    @forelse ($recentActivities as $activity)
                        <div class="activity-item"><div class="activity-dot"></div><div class="min-w-0"><div class="fw-semibold">{{ $activity->description }}</div><div class="small text-muted">{{ $activity->actor_name }}@if ($activity->reference) - {{ $activity->reference }}@endif - {{ $activity->created_at->diffForHumans() }}</div>@if ($activity->location_label)<div class="small text-muted mt-1">{{ $activity->location_label }}</div>@endif</div></div>
                    @empty
                        <div class="text-muted small">No activity recorded yet.</div>
                    @endforelse
                </div>
            </section>
        @endif

        @if ($activeDashboardTab === 'disciplinary')
            <section class="bg-white border rounded-2 p-3">
                <div class="d-flex justify-content-between align-items-center gap-3 mb-3"><h2 class="h5 mb-0">Disciplinary Case Overview</h2><a href="{{ route('disciplinary-cases.index') }}" class="btn btn-secondary btn-sm">Open cases</a></div>
                @include('dashboard.partials.metric-strip', ['metrics' => $caseOverview])
                <div class="insight-grid">
                    @include('dashboard.partials.trend-chart', ['title' => 'Cases opened', 'points' => $dashboardTrends['disciplinary']])
                    @include('dashboard.partials.donut-chart', ['title' => 'Cases by status', 'rows' => $casesByStatus, 'labels' => []])
                    @include('dashboard.partials.distribution-chart', ['title' => 'Offence categories', 'rows' => $casesByOffenceCategory, 'labels' => []])
                </div>
            </section>
        @endif

        @if ($activeDashboardTab === 'recruitment')
            <section class="bg-white border rounded-2 p-3">
                <div class="d-flex justify-content-between align-items-center gap-3 mb-3"><h2 class="h5 mb-0">Recruitment Overview</h2><a href="{{ route('recruitment.index') }}" class="btn btn-secondary btn-sm">Open recruitment</a></div>
                @include('dashboard.partials.metric-strip', ['metrics' => $recruitmentOverview])
                <div class="insight-grid">
                    @include('dashboard.partials.trend-chart', ['title' => 'Applications received', 'points' => $dashboardTrends['recruitment']])
                    @include('dashboard.partials.donut-chart', ['title' => 'Applications by status', 'rows' => $recruitmentApplicationsByStatus, 'labels' => \App\Models\JobApplication::statusLabels()])
                    @include('dashboard.partials.distribution-chart', ['title' => 'Open jobs by visibility', 'rows' => $openJobsByVisibility, 'labels' => []])
                </div>
            </section>
        @endif

        @if ($activeDashboardTab === 'promotions')
            <section class="bg-white border rounded-2 p-3">
                <div class="d-flex justify-content-between align-items-center gap-3 mb-3"><h2 class="h5 mb-0">Promotions Overview</h2><a href="{{ route('staff-promotions.index') }}" class="btn btn-secondary btn-sm">Open promotions</a></div>
                @include('dashboard.partials.metric-strip', ['metrics' => $promotionOverview])
                <div class="insight-grid">
                    @include('dashboard.partials.trend-chart', ['title' => 'Promotion trend', 'points' => $dashboardTrends['promotions']])
                    <div class="insight-panel"><div class="insight-heading"><div><h3>Latest promotions</h3><span>Most recent changes</span></div></div>@forelse ($latestPromotions as $promotion)<a href="{{ route('staff-promotions.show', $promotion) }}" class="compact-record"><span><strong>{{ $promotion->reference_no }}</strong><small>{{ $promotion->employee?->display_name }} - {{ $promotion->oldJobTitle?->name ?? '-' }} to {{ $promotion->newJobTitle?->name ?? '-' }}</small></span><time>{{ $promotion->promotion_date?->format('d M Y') }}</time></a>@empty<div class="empty-insight">No promotions recorded yet.</div>@endforelse</div>
                </div>
            </section>
        @endif

        @if ($activeDashboardTab === 'relocations')
            <section class="bg-white border rounded-2 p-3">
                <div class="d-flex justify-content-between align-items-center gap-3 mb-3"><h2 class="h5 mb-0">Relocations Overview</h2><a href="{{ route('staff-relocations.index') }}" class="btn btn-secondary btn-sm">Open relocations</a></div>
                @include('dashboard.partials.metric-strip', ['metrics' => $relocationOverview])
                <div class="insight-grid">
                    @include('dashboard.partials.trend-chart', ['title' => 'Relocation trend', 'points' => $dashboardTrends['relocations']])
                    <div class="insight-panel"><div class="insight-heading"><div><h3>Latest relocations</h3><span>Most recent movements</span></div></div>@forelse ($latestRelocations as $relocation)<a href="{{ route('staff-relocations.show', $relocation) }}" class="compact-record"><span><strong>{{ $relocation->reference_no }}</strong><small>{{ $relocation->employee?->display_name }} - {{ $relocation->fromProvince?->name ?? '-' }} to {{ $relocation->toProvince?->name ?? '-' }}</small></span><time>{{ $relocation->effective_date?->format('d M Y') }}</time></a>@empty<div class="empty-insight">No relocations recorded yet.</div>@endforelse</div>
                </div>
            </section>
        @endif

        @if ($activeDashboardTab === 'temporary-appointments')
            <section class="bg-white border rounded-2 p-3">
                <div class="d-flex justify-content-between align-items-center gap-3 mb-3"><h2 class="h5 mb-0">Temporary Appointments Overview</h2><a href="{{ route('temporary-appointments.index') }}" class="btn btn-secondary btn-sm">Open appointments</a></div>
                @include('dashboard.partials.metric-strip', ['metrics' => $temporaryAppointmentOverview])
                <div class="insight-grid">
                    @include('dashboard.partials.trend-chart', ['title' => 'Appointment trend', 'points' => $dashboardTrends['temporary-appointments']])
                    <div class="insight-panel"><div class="insight-heading"><div><h3>Latest appointments</h3><span>Current end-date outlook</span></div></div>@forelse ($latestTemporaryAppointments as $appointment)<a href="{{ route('temporary-appointments.show', $appointment) }}" class="compact-record"><span><strong>{{ $appointment->reference_no }}</strong><small>{{ $appointment->employee?->display_name }} - {{ $appointment->temporaryJobTitle?->name ?? '-' }}</small></span><time>{{ $appointment->end_date?->format('d M Y') }}</time></a>@empty<div class="empty-insight">No temporary appointments recorded yet.</div>@endforelse</div>
                </div>
            </section>
        @endif

        @if ($activeDashboardTab === 'funeral-benefits')
            <section class="bg-white border rounded-2 p-3">
                <div class="d-flex justify-content-between align-items-center gap-3 mb-3"><div><h2 class="h5 mb-1">Funeral Benefits Overview</h2><p class="text-muted small mb-0">Claims, approvals, payments and insurance follow-up visible to your role.</p></div><a href="{{ route('funeral-claims.index') }}" class="btn btn-secondary btn-sm">Open funeral claims</a></div>
                @include('dashboard.partials.metric-strip', ['metrics' => $funeralBenefitOverview])
                <div class="insight-grid">
                    @include('dashboard.partials.trend-chart', ['title' => 'Claims registered', 'points' => $dashboardTrends['funeral-benefits']])
                    @include('dashboard.partials.donut-chart', ['title' => 'Claims by category', 'rows' => $funeralClaimsByCategory, 'labels' => \App\Models\FuneralClaim::categories()])
                    @include('dashboard.partials.distribution-chart', ['title' => 'Claims by status', 'rows' => $funeralClaimsByStatus, 'labels' => \App\Models\FuneralClaim::statuses()])
                </div>
            </section>
            <section class="bg-white border rounded-2 overflow-hidden">
                <div class="d-flex justify-content-between align-items-center p-3 border-bottom"><h3 class="h6 mb-0">Recent Funeral Claims</h3><span class="text-muted small">Latest {{ $latestFuneralClaims->count() }}</span></div>
                <div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th>Reference</th><th>Employee</th><th>Deceased</th><th>Category</th><th>Status</th><th>Notified</th><th></th></tr></thead><tbody>@forelse ($latestFuneralClaims as $claim)<tr><td class="fw-semibold">{{ $claim->reference_no }}</td><td>{{ $claim->employee?->display_name ?? '-' }}</td><td>{{ $claim->deceased_name }}</td><td>{{ \App\Models\FuneralClaim::categories()[$claim->deceased_category] ?? str($claim->deceased_category)->headline() }}</td><td><span class="badge text-bg-light">{{ \App\Models\FuneralClaim::statuses()[$claim->status] ?? str($claim->status)->headline() }}</span></td><td>{{ $claim->notification_date?->format('d M Y') }}</td><td><a href="{{ route('funeral-claims.show', $claim) }}" class="btn btn-secondary btn-sm">View</a></td></tr>@empty<tr><td colspan="7" class="text-center text-muted py-4">No funeral claims recorded yet.</td></tr>@endforelse</tbody></table></div>
            </section>
        @endif
    </div>
@endsection

@push('styles')
<style>
    .analytics-nav {
        display: grid;
        grid-template-columns: repeat(7, minmax(0, 1fr));
        gap: .6rem;
        padding: .65rem;
        overflow: visible;
        background: #fff;
        border: 1px solid #dbe3ee;
        border-radius: .5rem;
        box-shadow: 0 8px 24px rgba(15, 23, 42, .06);
    }

    .analytics-tab {
        --tab-color: #17355f;
        --tab-soft: #e9eff8;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        min-width: 0;
        min-height: 5.35rem;
        gap: .4rem;
        padding: .6rem .35rem;
        text-align: center;
        color: #334155;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: .4rem;
        text-decoration: none;
        transition: border-color .16s ease, box-shadow .16s ease, transform .16s ease;
    }

    .analytics-tab:hover {
        color: var(--tab-color);
        border-color: var(--tab-color);
        box-shadow: 0 6px 15px rgba(15, 23, 42, .08);
        transform: translateY(-1px);
    }

    .analytics-tab.active {
        color: #fff;
        background: var(--tab-color);
        border-color: var(--tab-color);
        box-shadow: 0 8px 18px rgba(15, 23, 42, .16);
    }

    .analytics-tab[data-tone="navy"] { --tab-color: #17355f; --tab-soft: #e9eff8; }
    .analytics-tab[data-tone="blue"] { --tab-color: #2563eb; --tab-soft: #eaf1ff; }
    .analytics-tab[data-tone="green"] { --tab-color: #15803d; --tab-soft: #eaf7ef; }
    .analytics-tab[data-tone="teal"] { --tab-color: #0f766e; --tab-soft: #e8f7f5; }
    .analytics-tab[data-tone="amber"] { --tab-color: #a75808; --tab-soft: #fff4dc; }
    .analytics-tab[data-tone="red"] { --tab-color: #b42318; --tab-soft: #fff0ef; }
    .analytics-tab[data-tone="violet"] { --tab-color: #6645a8; --tab-soft: #f1edfa; }

    .analytics-tab-icon {
        display: inline-grid;
        flex: 0 0 2.15rem;
        width: 2.15rem;
        height: 2.15rem;
        place-items: center;
        color: var(--tab-color);
        background: var(--tab-soft);
        border-radius: .35rem;
        font-size: 1.05rem;
    }

    .analytics-tab.active .analytics-tab-icon {
        color: #fff;
        background: rgba(255, 255, 255, .16);
    }

    .analytics-tab-label {
        min-width: 0;
        max-width: 100%;
        font-size: .76rem;
        font-weight: 650;
        line-height: 1.18;
        overflow-wrap: normal;
        word-break: normal;
        hyphens: none;
    }

    .metric-strip {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(8rem, 1fr));
        overflow: hidden;
        background: #fff;
        border: 1px solid #dbe3ee;
        border-radius: .45rem;
    }

    .metric-item {
        min-width: 0;
        min-height: 4.8rem;
        padding: .72rem .9rem;
        border-top: 3px solid #2563eb;
        border-right: 1px solid #e2e8f0;
    }

    .metric-item:nth-child(4n + 2) { border-top-color: #15803d; }
    .metric-item:nth-child(4n + 3) { border-top-color: #a75808; }
    .metric-item:nth-child(4n + 4) { border-top-color: #b42318; }
    .metric-item:last-child { border-right: 0; }

    .metric-label {
        display: block;
        color: #64748b;
        font-size: .68rem;
        font-weight: 700;
        line-height: 1.25;
        text-transform: uppercase;
    }

    .metric-value {
        display: block;
        margin-top: .28rem;
        color: #0f172a;
        font-size: 1.55rem;
        font-weight: 750;
        line-height: 1;
    }

    .insight-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 1rem;
        margin-top: 1rem;
    }

    .insight-grid > :last-child:nth-child(odd) { grid-column: 1 / -1; }

    .insight-panel {
        min-width: 0;
        padding: .9rem 1rem;
        background: #f8fafc;
        border: 1px solid #dbe3ee;
        border-radius: .45rem;
    }

    .insight-heading {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 1rem;
        margin-bottom: .8rem;
    }

    .insight-heading h3 {
        margin: 0;
        color: #172033;
        font-size: .92rem;
        font-weight: 700;
    }

    .insight-heading span,
    .compact-record small,
    .empty-insight {
        color: #64748b;
        font-size: .73rem;
    }

    .insight-heading strong {
        color: #17355f;
        font-size: 1.45rem;
        line-height: 1;
    }

    .trend-chart {
        display: grid;
        grid-template-columns: repeat(6, minmax(0, 1fr));
        align-items: end;
        gap: .55rem;
        height: 10rem;
        padding-top: .35rem;
    }

    .trend-column {
        display: grid;
        grid-template-rows: 1rem 1fr 1rem;
        align-items: end;
        gap: .22rem;
        height: 100%;
        text-align: center;
    }

    .trend-number,
    .trend-label {
        color: #64748b;
        font-size: .65rem;
        font-weight: 650;
    }

    .trend-track {
        display: flex;
        align-items: flex-end;
        width: min(2.15rem, 70%);
        height: 100%;
        margin-inline: auto;
        overflow: hidden;
        background: #e6edf6;
        border-radius: .25rem .25rem 0 0;
    }

    .trend-bar {
        display: block;
        width: 100%;
        min-height: 3px;
        background: #2563eb;
        border-radius: .25rem .25rem 0 0;
    }

    .distribution-chart { display: grid; gap: .72rem; }
    .distribution-row { display: grid; gap: .28rem; }
    .distribution-meta { display: flex; justify-content: space-between; gap: 1rem; font-size: .75rem; }
    .distribution-meta span { min-width: 0; color: #334155; }
    .distribution-meta strong { color: #0f172a; }
    .distribution-track { height: .48rem; overflow: hidden; background: #e2e8f0; border-radius: 99px; }
    .distribution-track span { display: block; height: 100%; min-width: 4px; background: #0f766e; border-radius: inherit; }

    .donut-layout {
        display: grid;
        grid-template-columns: 7.5rem minmax(0, 1fr);
        align-items: center;
        gap: 1.2rem;
    }

    .donut-chart {
        display: grid;
        width: 7.5rem;
        aspect-ratio: 1;
        place-items: center;
        border-radius: 50%;
    }

    .donut-chart > div {
        display: grid;
        width: 67%;
        aspect-ratio: 1;
        place-content: center;
        text-align: center;
        background: #f8fafc;
        border-radius: 50%;
    }

    .donut-chart strong { color: #0f172a; font-size: 1.25rem; line-height: 1; }
    .donut-chart span { margin-top: .2rem; color: #64748b; font-size: .62rem; text-transform: uppercase; }
    .donut-legend { display: grid; gap: .45rem; min-width: 0; }
    .donut-legend-item { display: grid; grid-template-columns: .55rem minmax(0, 1fr) auto; align-items: center; gap: .48rem; font-size: .72rem; }
    .donut-swatch { width: .55rem; height: .55rem; border-radius: 2px; }

    .compact-record {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 1rem;
        padding: .65rem 0;
        color: #172033;
        border-bottom: 1px solid #e2e8f0;
        text-decoration: none;
    }

    .compact-record:last-child { border-bottom: 0; }
    .compact-record > span { display: grid; min-width: 0; gap: .12rem; }
    .compact-record small { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .compact-record time { flex: 0 0 auto; color: #64748b; font-size: .7rem; }

    .dashboard-analytics .summary-tile {
        position: relative;
        min-height: 5.25rem;
        padding: .75rem .9rem;
        overflow: hidden;
        background: #fff;
        border: 1px solid #e2e8f0;
        border-top: 3px solid #2563eb;
        border-radius: .45rem;
        box-shadow: 0 7px 18px rgba(15, 23, 42, .055);
    }

    .dashboard-analytics .col:nth-child(4n + 2) .summary-tile { border-top-color: #15803d; }
    .dashboard-analytics .col:nth-child(4n + 3) .summary-tile { border-top-color: #a75808; }
    .dashboard-analytics .col:nth-child(4n + 4) .summary-tile { border-top-color: #b42318; }
    .dashboard-analytics .summary-label { color: #64748b; font-size: .78rem; font-weight: 650; text-transform: uppercase; }
    .dashboard-analytics .summary-value { margin-top: .28rem; color: #0f172a; font-size: 1.55rem; font-weight: 750; line-height: 1.05; }

    @media (max-width: 1199.98px) {
        .analytics-nav { grid-template-columns: repeat(4, minmax(0, 1fr)); }
    }

    @media (max-width: 767.98px) {
        .analytics-nav { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .insight-grid { grid-template-columns: 1fr; }
        .insight-grid > * { grid-column: 1 !important; }
        .metric-strip { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .metric-item { border-bottom: 1px solid #e2e8f0; }
    }

    @media (max-width: 419.98px) {
        .analytics-nav { grid-template-columns: 1fr; }
        .donut-layout { grid-template-columns: 1fr; }
        .donut-chart { margin-inline: auto; }
    }
</style>
@endpush
