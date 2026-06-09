@extends('layouts.app')

@section('title', 'Edit '.$plan->reference_no)
@section('page-title', 'Edit '.$plan->reference_no)

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('staff-establishment.index') }}">Staff Establishment</a></li>
    <li class="breadcrumb-item"><a href="{{ route('staff-establishment.show', $plan) }}">{{ $plan->reference_no }}</a></li>
    <li class="breadcrumb-item active" aria-current="page">Edit</li>
@endsection

@section('content')
    @include('staff-establishment._form', [
        'action' => route('staff-establishment.update', $plan),
        'method' => 'PUT',
    ])
@endsection
