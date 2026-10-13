@extends('layouts.app')

@section('title', 'Archived Dependants')
@section('page-title', 'Archived Dependants')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('employees.index') }}">Employees</a></li>
    <li class="breadcrumb-item"><a href="{{ route('employees.show', $employee) }}">{{ $employee->employee_no }}</a></li>
    <li class="breadcrumb-item active" aria-current="page">Archived Dependants</li>
@endsection

@section('page-actions')
    <a href="{{ route('employees.show', $employee) }}" class="btn btn-secondary">Back</a>
@endsection

@section('content')
    <section class="bg-white border rounded-2 p-4">
        <div class="mb-4">
            <div class="text-muted small">Employee</div>
            <div class="h5 mb-0">{{ $employee->display_name }}</div>
        </div>

        <div class="table-responsive data-table-wrap">
            <table class="table table-hover align-middle data-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Category</th>
                        <th>Archived</th>
                        <th>Archived By</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($dependants as $dependant)
                        <tr>
                            <td>{{ $dependant->full_name }}</td>
                            <td>{{ \App\Models\EmployeeDependant::categories()[$dependant->category] ?? ucfirst($dependant->category) }}</td>
                            <td>{{ $dependant->deleted_at?->format('d M Y, H:i') }}</td>
                            <td>{{ $dependant->archivedBy?->name ?? 'Unknown' }}</td>
                            <td class="text-end">
                                <form method="POST" action="{{ route('employees.dependants.restore', [$employee, $dependant->id]) }}" class="d-inline">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="btn btn-sm btn-outline-primary">Restore</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-4">No archived dependants.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $dependants->links() }}
    </section>
@endsection
