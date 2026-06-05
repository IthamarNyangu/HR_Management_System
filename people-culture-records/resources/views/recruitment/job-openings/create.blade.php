@extends('layouts.app')

@section('title', 'New Job Opening')
@section('page-title', 'New Job Opening')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('recruitment.index') }}">Recruitment</a></li>
    <li class="breadcrumb-item"><a href="{{ route('recruitment.job-openings.index') }}">Job Openings</a></li>
    <li class="breadcrumb-item active" aria-current="page">New Job Opening</li>
@endsection

@section('content')
    <form method="POST" action="{{ route('recruitment.job-openings.store') }}" class="bg-white border rounded-2 p-4">
        @csrf
        @include('recruitment.job-openings._form')
    </form>
@endsection
