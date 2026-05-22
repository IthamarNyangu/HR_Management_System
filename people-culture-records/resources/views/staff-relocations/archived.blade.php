@extends('layouts.app')

@section('title', 'Archived Staff Relocations')
@section('page-title', 'Archived Staff Relocations')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('staff-relocations.index') }}">Staff Relocations</a></li>
    <li class="breadcrumb-item active" aria-current="page">Archived</li>
@endsection

@section('page-actions')
    <a href="{{ route('staff-relocations.index') }}" class="btn btn-secondary btn-md">Back to Relocations</a>
@endsection

@section('content')
    <div class="bg-white border rounded-2 p-3">
        <form method="GET" class="row g-2 mb-4">
            <div class="col-md-6">
                <input type="search" name="search" value="{{ request('search') }}" class="form-control" placeholder="Search reference or employee">
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-primary-outline btn-md">Search</button>
            </div>
            <div class="col-auto">
                <a href="{{ route('staff-relocations.archived') }}" class="btn btn-secondary btn-md">Reset</a>
            </div>
        </form>

        <div class="table-responsive data-table-wrap">
            <table class="table table-hover align-middle data-table">
                <thead>
                    <tr>
                        <th>Reference</th>
                        <th>Employee</th>
                        <th>Movement</th>
                        <th>Archived By</th>
                        <th>Archived At</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($relocations as $relocation)
                        <tr>
                            <td>{{ $relocation->reference_no }}</td>
                            <td>
                                <div>{{ $relocation->employee?->full_name }}</div>
                                <div class="small text-muted">{{ $relocation->employee?->employee_no }}</div>
                            </td>
                            <td>{{ $relocation->fromProvince?->name ?? '-' }} to {{ $relocation->toProvince?->name ?? '-' }}</td>
                            <td>{{ $relocation->archivedBy?->name ?? '-' }}</td>
                            <td>{{ $relocation->deleted_at?->format('d M Y H:i') }}</td>
                            <td>
                                @can('restore', $relocation)
                                    <form method="POST" action="{{ route('staff-relocations.restore', $relocation->id) }}" data-confirm="true" data-confirm-title="Restore relocation?" data-confirm-message="This relocation will return to the active relocation register. Do you want to continue?" data-confirm-button="Restore relocation">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="btn btn-sm btn-outline-primary">Restore</button>
                                    </form>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">No archived staff relocations found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-3">
            {{ $relocations->links() }}
        </div>
    </div>
@endsection
