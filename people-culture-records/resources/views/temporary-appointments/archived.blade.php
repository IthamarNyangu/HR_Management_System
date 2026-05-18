@extends('layouts.app')

@section('title', 'Archived Temporary Appointments')
@section('page-title', 'Archived Temporary Appointments')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('temporary-appointments.index') }}">Temporary Appointments</a></li>
    <li class="breadcrumb-item active" aria-current="page">Archived</li>
@endsection

@section('content')
    <div class="bg-white border rounded-2 p-3">
        <form method="GET" class="row g-2 mb-4">
            <div class="col-md-6 col-xl-3">
                <input type="search" name="search" value="{{ request('search') }}" class="form-control" placeholder="Search archived appointments">
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-primary-outline btn-md">Search</button>
            </div>
            <div class="col-auto">
                <a href="{{ route('temporary-appointments.archived') }}" class="btn btn-secondary btn-md">Reset</a>
            </div>
        </form>

        <div class="table-responsive data-table-wrap">
            <table class="table table-hover align-middle data-table">
                <thead>
                    <tr>
                        <th>Reference</th>
                        <th>Employee</th>
                        <th>Temporary Job Title</th>
                        <th>Status</th>
                        <th>Archived By</th>
                        <th>Archived Date</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($appointments as $appointment)
                        <tr>
                            <td class="fw-semibold">{{ $appointment->reference_no }}</td>
                            <td>{{ $appointment->employee?->display_name }}</td>
                            <td>{{ $appointment->temporaryJobTitle?->name }}</td>
                            <td>{{ $appointment->appointmentStatus?->name }}</td>
                            <td>{{ $appointment->archivedBy?->name ?? '-' }}</td>
                            <td>{{ $appointment->deleted_at?->format('d M Y H:i') }}</td>
                            <td class="text-end">
                                @can('restore', $appointment)
                                    <form method="POST" action="{{ route('temporary-appointments.restore', $appointment->id) }}" data-confirm="true" data-confirm-title="Restore appointment?" data-confirm-message="This temporary appointment will return to the active register. Do you want to continue?" data-confirm-button="Restore appointment">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="btn btn-sm btn-outline-primary">Restore</button>
                                    </form>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">No archived temporary appointments found.</td>
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
