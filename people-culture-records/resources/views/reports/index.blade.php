@extends('layouts.app')

@section('title', 'Reports')
@section('page-title', 'Reports & Exports')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item active" aria-current="page">Reports</li>
@endsection

@section('content')
    <div class="row row-cols-1 row-cols-md-2 row-cols-xl-3 g-3">
        @foreach ($reports as $type => $report)
            <div class="col">
                <a href="{{ route($report['route']) }}" class="text-decoration-none text-reset">
                    <article class="admin-card h-100 p-3">
                        <div class="d-flex gap-3 align-items-start">
                            <span class="admin-card-icon">
                                <i class="bi bi-file-earmark-spreadsheet" aria-hidden="true"></i>
                            </span>
                            <div>
                                <h2 class="h6 mb-1">{{ $report['title'] }}</h2>
                                <p class="text-muted small mb-0">{{ $report['description'] }}</p>
                            </div>
                        </div>
                    </article>
                </a>
            </div>
        @endforeach
    </div>
@endsection
