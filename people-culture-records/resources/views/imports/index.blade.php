@extends('layouts.app')

@section('title', 'Imports / Exports')
@section('page-title', 'Imports / Exports')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item active" aria-current="page">Imports / Exports</li>
@endsection

@section('content')
    <div class="row g-3 mb-4">
        <div class="col-md-6 col-xl-3">
            <a href="{{ route('imports.employees.create') }}" class="text-decoration-none text-reset">
                <div class="admin-card h-100 p-3">
                    <div class="d-flex align-items-start gap-3">
                        <span class="admin-card-icon"><i class="bi bi-people" aria-hidden="true"></i></span>
                        <div>
                            <h2 class="h5 mb-1">Employee Import</h2>
                            <p class="text-muted mb-0">Upload, preview, validate, and confirm employee records.</p>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-6 col-xl-3">
            <a href="{{ route('imports.disciplinary-cases.create') }}" class="text-decoration-none text-reset">
                <div class="admin-card h-100 p-3">
                    <div class="d-flex align-items-start gap-3">
                        <span class="admin-card-icon"><i class="bi bi-shield-exclamation" aria-hidden="true"></i></span>
                        <div>
                            <h2 class="h5 mb-1">Disciplinary Cases Import</h2>
                            <p class="text-muted mb-0">Upload, preview, validate, and confirm disciplinary cases.</p>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        @foreach (['Staff Promotions Import', 'Staff Relocations Import'] as $title)
            <div class="col-md-6 col-xl-3">
                <div class="admin-card h-100 p-3 opacity-75">
                    <div class="d-flex align-items-start gap-3">
                        <span class="admin-card-icon"><i class="bi bi-clock" aria-hidden="true"></i></span>
                        <div>
                            <h2 class="h5 mb-1">{{ $title }}</h2>
                            <p class="text-muted mb-0">Coming later.</p>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <section class="bg-white border rounded-2 p-3">
        <div class="d-flex justify-content-between align-items-center gap-3 mb-3">
            <div>
                <h2 class="h5 mb-1">Import History</h2>
                <p class="text-muted small mb-0">Uploaded import batches and their processing status.</p>
            </div>
        </div>

        <div class="table-responsive data-table-wrap">
            <table class="table table-hover align-middle data-table">
                <thead>
                    <tr>
                        <th>Batch</th>
                        <th>Type</th>
                        <th>File</th>
                        <th>Status</th>
                        <th>Rows</th>
                        <th>Uploaded By</th>
                        <th>Date</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($batches as $batch)
                        <tr>
                            <td class="fw-semibold">{{ $batch->reference_no }}</td>
                            <td>{{ ucfirst($batch->import_type) }}</td>
                            <td>{{ $batch->original_filename }}</td>
                            <td><span class="badge text-bg-light">{{ ucfirst($batch->status) }}</span></td>
                            <td>
                                <span class="text-success">{{ $batch->valid_rows }}</span> valid /
                                <span class="text-danger">{{ $batch->invalid_rows }}</span> invalid /
                                <span class="text-warning">{{ $batch->duplicate_rows }}</span> duplicate
                            </td>
                            <td>{{ $batch->uploadedBy?->name ?? 'System' }}</td>
                            <td>{{ $batch->created_at->format('d M Y H:i') }}</td>
                            <td class="text-end">
                                <a href="{{ route('imports.batches.show', $batch) }}" class="btn btn-sm btn-secondary">View</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">No import batches yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-3">
            {{ $batches->links() }}
        </div>
    </section>
@endsection
