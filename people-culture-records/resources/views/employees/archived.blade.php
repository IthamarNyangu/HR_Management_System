@extends('layouts.app')

@section('title', 'Archived Employees')
@section('page-title', 'Archived Employees')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('employees.index') }}">Employees</a></li>
    <li class="breadcrumb-item active" aria-current="page">Archived</li>
@endsection

@section('content')
    <div class="bg-white border rounded-2 p-3">
        <form method="GET" class="row g-2 mb-4">
            <div class="col-md-6 col-lg-4">
                <input type="search" name="search" value="{{ request('search') }}" class="form-control" placeholder="Search archived employees">
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-primary-outline btn-md">Search</button>
            </div>
            <div class="col-auto">
                <a href="{{ route('employees.index') }}" class="btn btn-secondary btn-md">Active Register</a>
            </div>
        </form>

        <div class="table-responsive data-table-wrap">
            <table class="table table-hover align-middle data-table">
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Province</th>
                        <th>District</th>
                        <th>Archived By</th>
                        <th>Archived On</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($employees as $employee)
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $employee->full_name }}</div>
                                <div class="small text-muted">{{ $employee->employee_no }}</div>
                            </td>
                            <td>{{ $employee->province?->name }}</td>
                            <td>{{ $employee->district?->name }}</td>
                            <td>{{ $employee->archivedBy?->name ?? '-' }}</td>
                            <td>{{ $employee->deleted_at?->format('d M Y') ?? '-' }}</td>
                            <td class="text-end">
                                @can('restore', $employee)
                                    <form method="POST" action="{{ route('employees.restore', $employee->id) }}" data-confirm="true" data-confirm-title="Restore employee?" data-confirm-message="This employee will return to the active employee register. Do you want to continue?" data-confirm-button="Restore employee">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="btn btn-sm btn-outline-primary">Restore</button>
                                    </form>
                                @else
                                    <span class="text-muted small">No action</span>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">No archived employees found.</td>
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
