@extends('layouts.app')

@section('title', 'User Management')
@section('page-title', 'User Access Register')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item active" aria-current="page">User Access Register</li>
@endsection

@section('page-actions')
    <a href="{{ route('admin.users.create') }}" class="btn btn-primary btn-md">New User</a>
@endsection

@section('content')
    <section class="bg-white border rounded-2 p-3 mb-3">
        <h2 class="h5 mb-2">System Access Overview</h2>

        <div class="row g-3">
            <div class="col-md-6 col-xl-3">
                <div class="summary-tile h-100">
                    <div class="summary-label">Admin</div>
                    <div class="small text-muted mt-2">Manages users, master data, and all HR records.</div>
                </div>
            </div>
            <div class="col-md-6 col-xl-3">
                <div class="summary-tile h-100">
                    <div class="summary-label">HR Manager</div>
                    <div class="small text-muted mt-2">Manages HR records and master data across all provinces.</div>
                </div>
            </div>
            <div class="col-md-6 col-xl-3">
                <div class="summary-tile h-100">
                    <div class="summary-label">HR Officer</div>
                    <div class="small text-muted mt-2">Creates and manages records for their assigned province.</div>
                </div>
            </div>
            <div class="col-md-6 col-xl-3">
                <div class="summary-tile h-100">
                    <div class="summary-label">Viewer</div>
                    <div class="small text-muted mt-2">Read-only access for their assigned province.</div>
                </div>
            </div>
        </div>
    </section>

    <section class="bg-white border rounded-2 p-3">
        <div class="d-flex flex-column flex-md-row justify-content-between gap-2 mb-3">
            <div>
                <h2 class="h5 mb-1">Users With System Access</h2>
                <p class="text-muted small mb-0">Only employees or special accounts with login access appear here. All other staff remain in the Employees module.</p>
            </div>
        </div>

        <form method="GET" class="row g-2 mb-3">
            <div class="col-md-6 col-xl-4">
                <input type="search" name="search" value="{{ request('search') }}" class="form-control" placeholder="Search name, Email, or Emp no.">
            </div>
            <div class="col-md-6 col-xl-3">
                <select name="role_id" class="form-select">
                    <option value="">All access profiles</option>
                    @foreach ($roles as $role)
                        <option value="{{ $role->id }}" @selected((string) request('role_id') === (string) $role->id)>{{ $role->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6 col-xl-3">
                <select name="status" class="form-select">
                    <option value="">All statuses</option>
                    <option value="active" @selected(request('status') === 'active')>Active</option>
                    <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
                    <option value="password-change-required" @selected(request('status') === 'password-change-required')>Password change required</option>
                </select>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-primary-outline btn-md">Filter</button>
            </div>
            <div class="col-auto">
                <a href="{{ route('admin.users.index') }}" class="btn btn-secondary btn-md">Reset</a>
            </div>
        </form>

        <div class="data-table-wrap">
            <div class="table-responsive">
                <table class="table data-table align-middle">
                    <thead>
                        <tr>
                            <th>Employee No</th>
                            <th>Employee Name</th>
                            <th>Job Title</th>
                            <th>Email</th>
                            <th>System Role</th>
                            <th>Province Scope</th>
                            <th>Status</th>
                            <th>Password</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($users as $user)
                            <tr>
                                <td>{{ $user->employee?->employee_no ?? 'Special account' }}</td>
                                <td>{{ $user->employee?->full_name ?? $user->name }}</td>
                                <td>{{ $user->employee?->jobTitle?->name ?? '-' }}</td>
                                <td>{{ $user->email }}</td>
                                <td>{{ $user->role?->name ?? '-' }}</td>
                                <td>{{ $user->province?->name ?? 'All provinces / HQ' }}</td>
                                <td>
                                    <span class="badge text-bg-{{ $user->is_active ? 'success' : 'secondary' }}">
                                        {{ $user->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td>
                                    @if ($user->must_change_password)
                                        <span class="badge text-bg-warning">Change required</span>
                                    @else
                                        <span class="badge text-bg-light border">Set</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="d-inline-flex gap-2">
                                        <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                                        <form method="POST" action="{{ route('admin.users.toggle-status', $user) }}" data-confirm="true" data-confirm-title="{{ $user->is_active ? 'Deactivate user?' : 'Activate user?' }}" data-confirm-message="{{ $user->is_active ? 'This user will no longer be able to log in. Do you want to continue?' : 'This user will regain access to the system. Do you want to continue?' }}" data-confirm-button="{{ $user->is_active ? 'Deactivate user' : 'Activate user' }}" data-confirm-variant="{{ $user->is_active ? 'btn-warning' : 'btn-primary' }}">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="btn btn-sm btn-outline-secondary">
                                                {{ $user->is_active ? 'Deactivate' : 'Activate' }}
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center text-muted py-4">No users found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-3">
            {{ $users->links() }}
        </div>
    </section>
@endsection
