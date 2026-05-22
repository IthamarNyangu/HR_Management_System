@extends('layouts.app')

@section('title', 'Audit Logs')
@section('page-title', 'Audit Logs')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item active" aria-current="page">Audit Logs</li>
@endsection

@section('page-actions')
    <a href="{{ route('activity-logs.export.pdf', request()->query()) }}" class="btn btn-primary btn-md">
        <i class="bi bi-file-earmark-pdf" aria-hidden="true"></i>
        Export PDF
    </a>
@endsection

@section('content')
    <section class="bg-white border rounded-2 p-3 mb-3">
        <div class="mb-3">
            <h2 class="h5 mb-1">Audit Trail Filters</h2>
            <p class="text-muted small mb-0">Filter the system history before reviewing or exporting it for audit purposes.</p>
        </div>

        <form method="GET" action="{{ route('activity-logs.index') }}" class="row g-3">
            <div class="col-md-6 col-xl-3">
                <input name="search" value="{{ request('search') }}" type="search" class="form-control" placeholder="Search action, description, reference">
            </div>
            <div class="col-md-6 col-xl-3">
                <select name="module" class="form-select">
                    <option value="">All modules</option>
                    @foreach ($modules as $value => $label)
                        <option value="{{ $value }}" @selected(request('module') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6 col-xl-3">
                <select name="action" class="form-select">
                    <option value="">All actions</option>
                    @foreach ($actions as $action)
                        <option value="{{ $action }}" @selected(request('action') === $action)>{{ str($action)->replace('_', ' ')->title() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6 col-xl-3">
                <select name="user_id" class="form-select">
                    <option value="">All actors</option>
                    @foreach ($users as $user)
                        <option value="{{ $user->id }}" @selected((string) request('user_id') === (string) $user->id)>{{ $user->name }} - {{ $user->email }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6 col-xl-3">
                <select name="province_id" class="form-select">
                    <option value="">All provinces</option>
                    @foreach ($provinces as $province)
                        <option value="{{ $province->id }}" @selected((string) request('province_id') === (string) $province->id)>{{ $province->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6 col-xl-3">
                <input name="date_from" value="{{ request('date_from') }}" type="date" class="form-control" aria-label="Date from">
            </div>
            <div class="col-md-6 col-xl-3">
                <input name="date_to" value="{{ request('date_to') }}" type="date" class="form-control" aria-label="Date to">
            </div>
            <div class="col-md-6 col-xl-3 d-flex align-items-center">
                <div class="form-check">
                    <input id="system_only" name="system_only" value="1" type="checkbox" class="form-check-input" @checked(request()->boolean('system_only'))>
                    <label for="system_only" class="form-check-label">System actions only</label>
                </div>
            </div>
            <div class="col-12 d-flex gap-2">
                <button type="submit" class="btn btn-primary btn-md">Filter</button>
                <a href="{{ route('activity-logs.index') }}" class="btn btn-secondary btn-md">Reset</a>
            </div>
        </form>
    </section>

    <section class="bg-white border rounded-2 p-3">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-3">
            <div>
                <h2 class="h5 mb-1">Audit Trail</h2>
                <p class="text-muted small mb-0">{{ $activities->total() }} record(s) found. Export uses the same filters shown here.</p>
            </div>
        </div>

        <div class="data-table-wrap">
            <div class="table-responsive">
                <table class="table data-table align-middle">
                    <thead>
                        <tr>
                            <th>Date / Time</th>
                            <th>Actor</th>
                            <th>Module</th>
                            <th>Action</th>
                            <th>Related Record</th>
                            <th>Province / Facility</th>
                            <th>Description</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($activities as $activity)
                            <tr>
                                <td class="text-nowrap">{{ $activity->created_at->format('d M Y H:i') }}</td>
                                <td>{{ $activity->actor_name }}</td>
                                <td>{{ $activity->module_label }}</td>
                                <td><span class="badge text-bg-light border">{{ $activity->display_action }}</span></td>
                                <td>{{ $activity->subject_label ?? '-' }}</td>
                                <td>{{ $activity->location_label ?? '-' }}</td>
                                <td style="min-width: 22rem;">
                                    <div class="fw-semibold">{{ $activity->description }}</div>
                                    @if ($activity->readable_properties)
                                        <details class="small text-muted mt-1">
                                            <summary>View captured details</summary>
                                            <dl class="row mb-0 mt-2">
                                                @foreach ($activity->readable_properties as $label => $value)
                                                    <dt class="col-sm-4">{{ $label }}</dt>
                                                    <dd class="col-sm-8">{{ $value }}</dd>
                                                @endforeach
                                            </dl>
                                        </details>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">No audit log records found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-3">
            {{ $activities->links() }}
        </div>
    </section>
@endsection
