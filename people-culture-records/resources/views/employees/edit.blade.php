@extends('layouts.app')

@section('title', 'Edit Employee')
@section('page-title', 'Edit Employee')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('employees.index') }}">Employees</a></li>
    <li class="breadcrumb-item"><a href="{{ route('employees.show', $employee) }}">{{ $employee->employee_no }}</a></li>
    <li class="breadcrumb-item active" aria-current="page">Edit</li>
@endsection

@section('content')
    <div class="bg-white border rounded-2 p-4">
        <form method="POST" action="{{ route('employees.update', $employee) }}" data-confirm="true" data-confirm-title="Save employee changes?" data-confirm-message="You are about to update this employee's profile. Do you want to continue?" data-confirm-button="Save changes">
            @csrf
            @method('PUT')
            @include('employees._form')
        </form>
    </div>
@endsection
