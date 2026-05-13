@extends('layouts.app')

@section('title', 'Employee Import')
@section('page-title', 'Employee Import')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('imports.index') }}">Imports / Exports</a></li>
    <li class="breadcrumb-item active" aria-current="page">Employee Import</li>
@endsection

@section('content')
    <div class="row g-4">
        <div class="col-xl-7">
            <section class="bg-white border rounded-2 p-3">
                <h2 class="h5 mb-2">Upload Employee File</h2>
                <p class="text-muted mb-4">Upload Excel or CSV data for preview. Employees will not be created until you confirm the validated rows.</p>

                <form method="POST" action="{{ route('imports.employees.upload') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="mb-3">
                        <label for="file" class="form-label">Employee import file</label>
                        <input type="file" name="file" id="file" class="form-control @error('file') is-invalid @enderror" accept=".xlsx,.xls,.csv" required>
                        <div class="form-text">Allowed file types: XLSX, XLS, CSV. Maximum 10 MB.</div>
                        @error('file')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary btn-md">Upload and Preview</button>
                        <a href="{{ route('imports.index') }}" class="btn btn-secondary btn-md">Cancel</a>
                    </div>
                </form>
            </section>
        </div>

        <div class="col-xl-5">
            <section class="bg-white border rounded-2 p-3">
                <h2 class="h5 mb-3">Expected Columns</h2>
                <div class="row row-cols-1 row-cols-sm-2 g-2 small">
                    @foreach (['employee_no', 'first_name', 'last_name', 'gender', 'date_of_birth', 'national_id', 'email', 'phone', 'project', 'department', 'job_title', 'province', 'district', 'facility', 'employment_status', 'hire_date', 'supervisor_name', 'notes'] as $column)
                        <div class="col"><code>{{ $column }}</code></div>
                    @endforeach
                </div>
                <hr>
                <p class="text-muted small mb-0">Master data such as province, district, facility, project, department, job title, and employment status must already exist in the system.</p>
            </section>
        </div>
    </div>
@endsection
