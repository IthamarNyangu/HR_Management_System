@extends('layouts.app')

@section('title', 'Archived Disciplinary Cases')
@section('page-title', 'Archived Disciplinary Cases')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('disciplinary-cases.index') }}">Disciplinary Cases</a></li>
    <li class="breadcrumb-item active" aria-current="page">Archived</li>
@endsection

@section('content')
    <div class="bg-white border rounded-2 p-3">
        <form method="GET" class="row g-2 mb-4">
            <div class="col-md-6 col-lg-4">
                <input type="search" name="search" value="{{ request('search') }}" class="form-control" placeholder="Search archived cases">
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-primary-outline btn-md">Search</button>
            </div>
            <div class="col-auto">
                <a href="{{ route('disciplinary-cases.index') }}" class="btn btn-secondary btn-md">Active Register</a>
            </div>
        </form>

        <div class="table-responsive data-table-wrap">
            <table class="table table-hover align-middle data-table">
                <thead>
                    <tr>
                        <th>Reference</th>
                        <th>Employee</th>
                        <th>Province</th>
                        <th>Status</th>
                        <th>Archived By</th>
                        <th>Archived On</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($cases as $case)
                        <tr>
                            <td>{{ $case->reference_no }}</td>
                            <td>
                                <div>{{ $case->employee?->full_name }}</div>
                                <div class="small text-muted">{{ $case->employee?->employee_no }}</div>
                            </td>
                            <td>{{ $case->province?->name }}</td>
                            <td><span class="badge text-bg-secondary">{{ $case->caseStatus?->name ?? '-' }}</span></td>
                            <td>{{ $case->archivedBy?->name ?? '-' }}</td>
                            <td>{{ $case->deleted_at?->format('d M Y') ?? '-' }}</td>
                            <td class="text-end">
                                @can('restore', $case)
                                    <form method="POST" action="{{ route('disciplinary-cases.restore', $case->id) }}" data-confirm="true" data-confirm-title="Restore disciplinary case?" data-confirm-message="This case will return to the active disciplinary register. Do you want to continue?" data-confirm-button="Restore case">
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
                            <td colspan="7" class="text-center text-muted py-4">No archived disciplinary cases found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-3">
            {{ $cases->links() }}
        </div>
    </div>
@endsection
