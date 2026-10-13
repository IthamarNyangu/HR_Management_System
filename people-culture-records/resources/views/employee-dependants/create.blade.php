@extends('layouts.app')

@section('title', 'Add Dependant')
@section('page-title', 'Add Dependant')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('employees.index') }}">Employees</a></li>
    <li class="breadcrumb-item"><a href="{{ route('employees.show', $employee) }}">{{ $employee->employee_no }}</a></li>
    <li class="breadcrumb-item active" aria-current="page">Add Dependant</li>
@endsection

@section('page-actions')
    <a href="{{ route('employees.show', $employee) }}" class="btn btn-secondary">Back</a>
@endsection

@section('content')
    <section class="bg-white border rounded-2 p-4">
        <div class="mb-4">
            <div class="text-muted small">Employee</div>
            <div class="h5 mb-0">{{ $employee->display_name }}</div>
        </div>

        <form method="POST" action="{{ route('employees.dependants.store', $employee) }}">
            @csrf
            @include('employee-dependants._form')

            <div class="d-flex justify-content-end gap-2 mt-4">
                <a href="{{ route('employees.show', $employee) }}" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Add Dependant</button>
            </div>
        </form>
    </section>
@endsection
