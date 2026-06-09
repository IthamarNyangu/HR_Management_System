@extends('layouts.app')

@section('title', 'New Staff Establishment')
@section('page-title', 'New Staff Establishment')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('staff-establishment.index') }}">Staff Establishment</a></li>
    <li class="breadcrumb-item active" aria-current="page">New Plan</li>
@endsection

@section('content')
    @include('staff-establishment._form', [
        'action' => route('staff-establishment.store'),
        'method' => 'POST',
    ])
@endsection
