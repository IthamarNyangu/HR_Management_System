@extends('layouts.app')

@section('title', 'Edit Organisation Chart')
@section('page-title', 'Edit Organisation Chart')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('organisation-chart.index') }}">Organisation Chart</a></li>
    <li class="breadcrumb-item"><a href="{{ route('organisation-chart.show', $organisationChart) }}">{{ $organisationChart->title }}</a></li>
    <li class="breadcrumb-item active" aria-current="page">Edit</li>
@endsection

@section('content')
    @include('organisation-chart._form', [
        'organisationChart' => $organisationChart,
        'action' => route('organisation-chart.update', $organisationChart),
        'method' => 'PUT',
    ])
@endsection
