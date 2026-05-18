@extends('layouts.app')

@section('title', 'New Temporary Appointment')
@section('page-title', 'New Temporary Appointment')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('temporary-appointments.index') }}">Temporary Appointments</a></li>
    <li class="breadcrumb-item active" aria-current="page">New Appointment</li>
@endsection

@section('content')
    <form method="POST" action="{{ route('temporary-appointments.store') }}" enctype="multipart/form-data" class="bg-white border rounded-2 p-4">
        @csrf
        @include('temporary-appointments._form')
    </form>
@endsection
