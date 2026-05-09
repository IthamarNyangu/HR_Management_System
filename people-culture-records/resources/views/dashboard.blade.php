@extends('layouts.app')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('content')
    <div class="row g-3">
        @foreach ($cards as $card)
            <div class="col-sm-6 col-xl">
                <div class="metric-card bg-white p-3 h-100">
                    <div class="text-muted small">{{ $card['label'] }}</div>
                    <div class="display-6 fw-semibold">{{ $card['value'] }}</div>
                </div>
            </div>
        @endforeach
    </div>

    <section class="bg-white border rounded-2 mt-4 p-4">
        <h2 class="h5">Recent Activity</h2>
        <p class="text-muted mb-0">No activity recorded yet.</p>
    </section>
@endsection
