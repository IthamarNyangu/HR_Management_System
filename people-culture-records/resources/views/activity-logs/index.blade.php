@extends('layouts.app')

@section('title', 'Recent Activity')
@section('page-title', 'Recent Activity')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item active" aria-current="page">Recent Activity</li>
@endsection

@section('content')
    <section class="bg-white border rounded-2 p-3">
        <div class="d-flex justify-content-between align-items-center gap-3 mb-3">
            <div>
                <h2 class="h5 mb-1">Recent Activity</h2>
                <p class="text-muted small mb-0">System history visible to your role and province.</p>
            </div>
        </div>

        <div class="activity-list">
            @forelse ($activities as $activity)
                <div class="activity-item">
                    <div class="activity-dot"></div>
                    <div class="min-w-0">
                        <div class="fw-semibold">{{ $activity->description }}</div>
                        <div class="small text-muted">
                            {{ $activity->actor_name }}
                            @if ($activity->reference)
                                - {{ $activity->reference }}
                            @endif
                            - {{ $activity->created_at->format('d M Y H:i') }}
                        </div>
                        @if ($activity->location_label)
                            <div class="small text-muted mt-1">{{ $activity->location_label }}</div>
                        @endif
                    </div>
                </div>
            @empty
                <div class="text-muted small">No activity recorded yet.</div>
            @endforelse
        </div>

        <div class="mt-3">
            {{ $activities->links() }}
        </div>
    </section>
@endsection
