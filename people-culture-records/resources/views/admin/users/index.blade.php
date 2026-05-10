@extends('layouts.app')

@section('title', 'User Management')
@section('page-title', 'User Management')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item active" aria-current="page">Users</li>
@endsection

@section('page-actions')
    <a href="{{ route('admin.users.create') }}" class="btn btn-primary">New User</a>
@endsection

@section('content')
    <div class="bg-white border rounded-2 p-3">
        <form method="GET" class="row g-2 mb-3">
            <div class="col-md-6 col-lg-4">
                <input type="search" name="search" value="{{ request('search') }}" class="form-control" placeholder="Search name or email">
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-outline-primary">Search</button>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Province</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($users as $user)
                        <tr>
                            <td>{{ $user->name }}</td>
                            <td>{{ $user->email }}</td>
                            <td>{{ $user->role?->name ?? '-' }}</td>
                            <td>{{ $user->province?->name ?? 'All provinces' }}</td>
                            <td>
                                <span class="badge text-bg-{{ $user->is_active ? 'success' : 'secondary' }}">
                                    {{ $user->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="text-end">
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
                            <td colspan="6" class="text-center text-muted py-4">No users found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $users->links() }}
    </div>
@endsection
