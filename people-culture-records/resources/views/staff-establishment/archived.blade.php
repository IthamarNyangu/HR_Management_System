@extends('layouts.app')

@section('title', 'Archived Staff Establishment')
@section('page-title', 'Archived Staff Establishment')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('staff-establishment.index') }}">Staff Establishment</a></li>
    <li class="breadcrumb-item active" aria-current="page">Archived</li>
@endsection

@section('page-actions')
    <a href="{{ route('staff-establishment.index') }}" class="btn btn-secondary btn-md">Back to Staff Establishment</a>
@endsection

@section('content')
    <section class="bg-white border rounded-2 p-3">
        <div class="table-responsive data-table-wrap">
            <table class="table table-hover align-middle data-table">
                <thead>
                    <tr>
                        <th>Reference</th>
                        <th>Plan</th>
                        <th>Project</th>
                        <th>Effective Month</th>
                        <th>Lines</th>
                        <th>Archived By</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($plans as $plan)
                        <tr>
                            <td class="fw-semibold">{{ $plan->reference_no }}</td>
                            <td>{{ $plan->title }}</td>
                            <td>{{ $plan->project?->name ?? 'All projects' }}</td>
                            <td>{{ $plan->effective_month?->format('M Y') }}</td>
                            <td>{{ $plan->lines_count }}</td>
                            <td>
                                <div>{{ $plan->archivedBy?->name ?? '-' }}</div>
                                <div class="small text-muted">{{ $plan->deleted_at?->format('d M Y H:i') }}</div>
                            </td>
                            <td>
                                @can('restore', $plan)
                                    <form method="POST" action="{{ route('staff-establishment.restore', $plan->id) }}" data-confirm="true" data-confirm-title="Restore staff establishment plan?" data-confirm-message="This plan will return to the active staff establishment list. Do you want to continue?" data-confirm-button="Restore plan">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="btn btn-sm btn-secondary">Restore</button>
                                    </form>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">No archived staff establishment plans found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-3">
            {{ $plans->links() }}
        </div>
    </section>
@endsection
