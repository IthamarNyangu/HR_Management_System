@extends('layouts.app')

@section('title', $config['label'])
@section('page-title', $config['label'])

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.index') }}">Admin Panel</a></li>
    <li class="breadcrumb-item active" aria-current="page">{{ $config['label'] }}</li>
@endsection

@section('page-actions')
    <a href="{{ route('admin.master-data.create', $type) }}" class="btn btn-primary">New Record</a>
@endsection

@section('content')
    <div class="bg-white border rounded-2 p-3">
        <form method="GET" class="row g-2 mb-3">
            <div class="col-md-6 col-lg-4">
                <input type="search" name="search" value="{{ request('search') }}" class="form-control" placeholder="Search name or code">
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-outline-primary">Search</button>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        @if (($config['parent'] ?? null) === 'province_id')
                            <th>Province</th>
                        @endif
                        @if (($config['parent'] ?? null) === 'district_id')
                            <th>District</th>
                        @endif
                        <th>Name</th>
                        <th>Code</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($records as $record)
                        <tr>
                            @if (($config['parent'] ?? null) === 'province_id')
                                <td>{{ $record->province?->name }}</td>
                            @endif
                            @if (($config['parent'] ?? null) === 'district_id')
                                <td>{{ $record->district?->name }}</td>
                            @endif
                            <td>
                                <div class="fw-semibold">{{ $record->name }}</div>
                                @if ($record->description)
                                    <div class="small text-muted">{{ $record->description }}</div>
                                @endif
                            </td>
                            <td>{{ $record->code ?? '-' }}</td>
                            <td>
                                <span class="badge text-bg-{{ $record->is_active ? 'success' : 'secondary' }}">
                                    {{ $record->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="text-end">
                                <div class="d-inline-flex gap-2">
                                    <a href="{{ route('admin.master-data.edit', [$type, $record->id]) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                                    <form method="POST" action="{{ route('admin.master-data.toggle-status', [$type, $record->id]) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="btn btn-sm btn-outline-secondary">
                                            {{ $record->is_active ? 'Deactivate' : 'Activate' }}
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">No records found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $records->links() }}
    </div>
@endsection
