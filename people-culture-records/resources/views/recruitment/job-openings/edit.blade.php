@extends('layouts.app')

@section('title', 'Edit Job Opening')
@section('page-title', 'Edit Job Opening')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('recruitment.index') }}">Recruitment</a></li>
    <li class="breadcrumb-item"><a href="{{ route('recruitment.job-openings.index') }}">Job Openings</a></li>
    <li class="breadcrumb-item"><a href="{{ route('recruitment.job-openings.show', $jobOpening) }}">{{ $jobOpening->reference_no }}</a></li>
    <li class="breadcrumb-item active" aria-current="page">Edit</li>
@endsection

@section('content')
    <form method="POST" action="{{ route('recruitment.job-openings.update', $jobOpening) }}" class="bg-white border rounded-2 p-4">
        @csrf
        @method('PUT')
        @include('recruitment.job-openings._form')
    </form>
@endsection
