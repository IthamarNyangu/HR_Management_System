@extends('layouts.app')

@section('title', 'Archived Organisation Charts')
@section('page-title', 'Archived Organisation Charts')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('organisation-chart.index') }}">Organisation Chart</a></li>
    <li class="breadcrumb-item active" aria-current="page">Archived</li>
@endsection

@section('page-actions')
    <a href="{{ route('organisation-chart.index') }}" class="btn btn-secondary btn-md">Back</a>
@endsection

@section('content')
    <section class="bg-white border rounded-2 p-3">
        <div class="table-responsive data-table-wrap">
            <table class="table table-hover align-middle data-table">
                <thead>
                    <tr>
                        <th>Chart</th>
                        <th>Project</th>
                        <th>Boxes</th>
                        <th>Archived Date</th>
                        <th>Archived By</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($charts as $chart)
                        <tr>
                            <td>{{ $chart->title }}</td>
                            <td>{{ $chart->project?->name ?? 'Organisation-wide' }}</td>
                            <td>{{ $chart->nodes_count }}</td>
                            <td>{{ $chart->deleted_at?->format('d M Y H:i') }}</td>
                            <td>{{ $chart->archivedBy?->name ?? '-' }}</td>
                            <td>
                                @can('restore', $chart)
                                    <form method="POST" action="{{ route('organisation-chart.restore', $chart->id) }}" data-confirm="true" data-confirm-title="Restore organisation chart?" data-confirm-message="This chart will return to the active organisation chart list. Do you want to continue?" data-confirm-button="Restore chart">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="btn btn-sm btn-outline-primary">Restore</button>
                                    </form>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">No archived organisation charts found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-3">
            {{ $charts->links() }}
        </div>
    </section>
@endsection
