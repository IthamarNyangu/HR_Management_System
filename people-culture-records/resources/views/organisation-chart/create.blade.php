@extends('layouts.app')

@section('title', 'New Organisation Chart')
@section('page-title', 'New Organisation Chart')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('organisation-chart.index') }}">Organisation Chart</a></li>
    <li class="breadcrumb-item active" aria-current="page">New Chart</li>
@endsection

@section('content')
    @include('organisation-chart._form', [
        'organisationChart' => $organisationChart,
        'action' => route('organisation-chart.store'),
        'method' => 'POST',
    ])
@endsection
