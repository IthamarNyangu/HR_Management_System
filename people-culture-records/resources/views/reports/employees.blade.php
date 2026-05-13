@extends('layouts.app')

@section('title', $report['title'])
@section('page-title', $report['title'])

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('reports.index') }}">Reports</a></li>
    <li class="breadcrumb-item active" aria-current="page">{{ $report['title'] }}</li>
@endsection

@section('content')
    @include('reports.partials.report-page')
@endsection
