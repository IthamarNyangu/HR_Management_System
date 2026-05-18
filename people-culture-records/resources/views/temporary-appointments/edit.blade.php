@extends('layouts.app')

@section('title', 'Edit Temporary Appointment')
@section('page-title', 'Edit Temporary Appointment')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('temporary-appointments.index') }}">Temporary Appointments</a></li>
    <li class="breadcrumb-item"><a href="{{ route('temporary-appointments.show', $appointment) }}">{{ $appointment->reference_no }}</a></li>
    <li class="breadcrumb-item active" aria-current="page">Edit</li>
@endsection

@section('content')
    <form method="POST" action="{{ route('temporary-appointments.update', $appointment) }}" enctype="multipart/form-data" class="bg-white border rounded-2 p-4">
        @csrf
        @method('PUT')
        @include('temporary-appointments._form')
    </form>
@endsection
