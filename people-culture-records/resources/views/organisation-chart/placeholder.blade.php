@extends('layouts.app')

@section('title', 'Organisation Chart')
@section('page-title', 'Organisation Chart')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item active" aria-current="page">Organisation Chart</li>
@endsection

@section('content')
    <section class="bg-white border rounded-2 p-4">
        <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
            <div>
                <h2 class="h5 mb-2">Organisation Chart</h2>
                <p class="text-muted mb-0">This module is reserved for the formal organisation chart. Employee reporting lines are now managed from Reporting Structure.</p>
            </div>
            <a href="{{ route('employees.reporting-structure') }}" class="btn btn-primary btn-md">Open Reporting Structure</a>
        </div>
    </section>
@endsection
