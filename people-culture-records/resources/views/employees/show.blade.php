@extends('layouts.app')

@section('title', $employee->display_name)
@section('page-title', $employee->full_name)

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('employees.index') }}">Employees</a></li>
    <li class="breadcrumb-item active" aria-current="page">{{ $employee->employee_no }}</li>
@endsection

@section('page-actions')
    <div class="d-flex gap-2">
        @can('update', $employee)
            <a href="{{ route('employees.edit', $employee) }}" class="btn btn-primary">Edit Employee</a>
        @endcan
        <a href="{{ route('employees.index') }}" class="btn btn-outline-secondary">Back</a>
    </div>
@endsection

@section('content')
    <div class="row g-3">
        <div class="col-lg-4">
            <section class="bg-white border rounded-2 p-4 h-100">
                <div class="text-muted small">Employee Number</div>
                <div class="h4">{{ $employee->employee_no }}</div>
                <hr>
                <dl class="mb-0">
                    <dt>Full Name</dt>
                    <dd>{{ $employee->full_name }}</dd>
                    <dt>Gender</dt>
                    <dd>{{ $employee->gender ?? '-' }}</dd>
                    <dt>Date of Birth</dt>
                    <dd>{{ $employee->date_of_birth?->format('d M Y') ?? '-' }}</dd>
                    <dt>National ID</dt>
                    <dd>{{ $employee->national_id ?? '-' }}</dd>
                </dl>
            </section>
        </div>

        <div class="col-lg-8">
            <section class="bg-white border rounded-2 p-4 mb-3">
                <h2 class="h5">Employment Details</h2>
                <div class="row">
                    <div class="col-md-6"><strong>Project:</strong> {{ $employee->project?->name ?? '-' }}</div>
                    <div class="col-md-6"><strong>Department:</strong> {{ $employee->department?->name ?? '-' }}</div>
                    <div class="col-md-6"><strong>Job Title:</strong> {{ $employee->jobTitle?->name ?? '-' }}</div>
                    <div class="col-md-6"><strong>Status:</strong> {{ $employee->employmentStatus?->name ?? '-' }}</div>
                    <div class="col-md-6"><strong>Hire Date:</strong> {{ $employee->hire_date?->format('d M Y') ?? '-' }}</div>
                    <div class="col-md-6"><strong>Supervisor:</strong> {{ $employee->supervisor_name ?? '-' }}</div>
                </div>
            </section>

            <section class="bg-white border rounded-2 p-4 mb-3">
                <h2 class="h5">Location & Contact</h2>
                <div class="row">
                    <div class="col-md-6"><strong>Province:</strong> {{ $employee->province?->name }}</div>
                    <div class="col-md-6"><strong>District:</strong> {{ $employee->district?->name }}</div>
                    <div class="col-md-6"><strong>Facility:</strong> {{ $employee->facility?->name ?? '-' }}</div>
                    <div class="col-md-6"><strong>Email:</strong> {{ $employee->email ?? '-' }}</div>
                    <div class="col-md-6"><strong>Phone:</strong> {{ $employee->phone ?? '-' }}</div>
                </div>
            </section>

            <section class="bg-white border rounded-2 p-4 mb-3">
                <h2 class="h5">Notes</h2>
                <p class="mb-0 text-muted">{{ $employee->notes ?: 'No notes recorded.' }}</p>
            </section>

            <div class="row g-3">
                @foreach (['Disciplinary history', 'Promotion history', 'Relocation history'] as $section)
                    <div class="col-md-4">
                        <section class="bg-white border rounded-2 p-3 h-100">
                            <h3 class="h6">{{ $section }}</h3>
                            <p class="small text-muted mb-0">Coming in later phases.</p>
                        </section>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endsection
