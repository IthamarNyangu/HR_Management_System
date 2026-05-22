@extends('layouts.app')

@section('title', 'Archived Staff Promotions')
@section('page-title', 'Archived Staff Promotions')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('staff-promotions.index') }}">Staff Promotions</a></li>
    <li class="breadcrumb-item active" aria-current="page">Archived</li>
@endsection

@section('page-actions')
    <a href="{{ route('staff-promotions.index') }}" class="btn btn-secondary btn-md">Back to Promotions</a>
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
                <a href="{{ route('staff-promotions.archived') }}" class="btn btn-secondary btn-md">Reset</a>
            </div>
        </form>

        <div class="table-responsive data-table-wrap">
            <table class="table table-hover align-middle data-table">
                <thead>
                    <tr>
                        <th>Reference</th>
                        <th>Employee</th>
                        <th>Province</th>
                        <th>Promotion</th>
                        <th>Archived By</th>
                        <th>Archived At</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($promotions as $promotion)
                        <tr>
                            <td>{{ $promotion->reference_no }}</td>
                            <td>
                                <div>{{ $promotion->employee?->full_name }}</div>
                                <div class="small text-muted">{{ $promotion->employee?->employee_no }}</div>
                            </td>
                            <td>{{ $promotion->province?->name }}</td>
                            <td>{{ $promotion->oldJobTitle?->name ?? '-' }} to {{ $promotion->newJobTitle?->name ?? '-' }}</td>
                            <td>{{ $promotion->archivedBy?->name ?? '-' }}</td>
                            <td>{{ $promotion->deleted_at?->format('d M Y H:i') }}</td>
                            <td>
                                @can('restore', $promotion)
                                    <form method="POST" action="{{ route('staff-promotions.restore', $promotion->id) }}" data-confirm="true" data-confirm-title="Restore promotion?" data-confirm-message="This promotion will return to the active promotion register. Do you want to continue?" data-confirm-button="Restore promotion">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="btn btn-sm btn-outline-primary">Restore</button>
                                    </form>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">No archived staff promotions found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-3">
            {{ $promotions->links() }}
        </div>
    </div>
@endsection
