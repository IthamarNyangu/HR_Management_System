@extends('layouts.app')

@section('title', 'Admin Panel')
@section('page-title', 'Admin Panel')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item active" aria-current="page">Admin Panel</li>
@endsection

@section('content')
    <div class="row g-3">
        @foreach ($types as $type => $config)
            <div class="col-sm-6 col-xl-4">
                <a href="{{ route('admin.master-data.records', $type) }}" class="text-decoration-none d-block h-100">
                    <div class="admin-card p-3 h-100">
                        <div class="d-flex align-items-center gap-3">
                            <span class="admin-card-icon">
                                <i class="bi {{ $config['icon'] }}" aria-hidden="true"></i>
                            </span>
                            <span>
                                <span class="d-block fw-semibold text-dark">{{ $config['label'] }}</span>
                                <span class="d-block small text-muted">Manage records</span>
                            </span>
                        </div>
                    </div>
                </a>
            </div>
        @endforeach
    </div>
@endsection
