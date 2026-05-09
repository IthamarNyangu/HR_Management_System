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
                <a href="{{ route('admin.master-data.records', $type) }}" class="text-decoration-none">
                    <div class="bg-white border rounded-2 p-3 h-100">
                        <div class="fw-semibold text-dark">{{ $config['label'] }}</div>
                        <div class="small text-muted">Manage records</div>
                    </div>
                </a>
            </div>
        @endforeach
    </div>
@endsection
