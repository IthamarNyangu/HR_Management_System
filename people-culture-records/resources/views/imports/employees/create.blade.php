@extends('layouts.app')

@section('title', 'Employee Import')
@section('page-title', 'Employee Import')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('imports.index') }}">Imports / Exports</a></li>
    <li class="breadcrumb-item active" aria-current="page">Employee Import</li>
@endsection

@section('page-actions')
    <a href="{{ route('imports.employees.template') }}" class="btn btn-secondary btn-md">
        <i class="bi bi-download" aria-hidden="true"></i>
        Download Employee Import Template
    </a>
@endsection

@section('content')
    <div class="row g-4">
        <div class="col-xl-7">
            <section class="bg-white border rounded-2 p-3">
                <h2 class="h5 mb-2">Upload Employee File</h2>
                <p class="text-muted mb-4">Upload Excel or CSV data for preview. Employees will not be created until you confirm the validated rows.</p>

                <div class="alert alert-info small">
                    Use a single sheet named <strong>Employees_Import</strong>. If your workbook has multiple sheets, only <strong>Employees_Import</strong> will be processed.
                </div>

                <form method="POST" action="{{ route('imports.employees.upload') }}" enctype="multipart/form-data" data-employee-import-form>
                    @csrf
                    <div class="border rounded-2 bg-light p-3 mb-3" data-import-upload-box>
                        <div class="d-flex align-items-center gap-2 mb-3">
                            <i class="bi bi-cloud-arrow-up text-primary" aria-hidden="true"></i>
                            <div class="fw-semibold">Employee Import File</div>
                        </div>

                        <div>
                            <label for="file" class="form-label">Upload File</label>
                            <input type="file" name="file" id="file" class="form-control @error('file') is-invalid @enderror" accept=".xlsx,.xls,.csv" required>
                        </div>

                        @error('file')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror

                        <div class="d-none alert alert-danger py-2 px-3 mt-3 mb-0" data-import-upload-error role="alert"></div>

                        <div class="d-none border rounded-2 bg-white p-3 mt-3" data-import-upload-preview aria-live="polite">
                            <div class="d-flex align-items-start justify-content-between gap-3">
                                <div class="d-flex align-items-start gap-3 min-w-0">
                                    <div class="rounded-2 d-inline-flex align-items-center justify-content-center flex-shrink-0" style="width: 40px; height: 40px; background: #eef4ff; color: #2563eb;">
                                        <i class="bi bi-file-earmark-spreadsheet" aria-hidden="true"></i>
                                    </div>
                                    <div class="min-w-0">
                                        <div class="d-flex align-items-center gap-2 flex-wrap">
                                            <span class="fw-semibold text-truncate" data-import-upload-filename></span>
                                            <span class="badge text-bg-success">Attached</span>
                                        </div>
                                        <div class="small text-muted mt-1" data-import-upload-meta></div>
                                    </div>
                                </div>
                                <button type="button" class="btn btn-sm btn-secondary" data-import-upload-clear>Remove</button>
                            </div>
                        </div>

                        <div class="small text-muted mt-2">XLSX, XLS, or CSV. Maximum 10 MB. The file will be previewed before any employees are created.</div>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary btn-md" data-import-submit>Upload and Preview</button>
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

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const form = document.querySelector('[data-employee-import-form]');

            if (!form) {
                return;
            }

            const uploadBox = form.querySelector('[data-import-upload-box]');
            const input = form.querySelector('#file');
            const preview = form.querySelector('[data-import-upload-preview]');
            const filename = form.querySelector('[data-import-upload-filename]');
            const meta = form.querySelector('[data-import-upload-meta]');
            const clear = form.querySelector('[data-import-upload-clear]');
            const error = form.querySelector('[data-import-upload-error]');
            const submit = form.querySelector('[data-import-submit]');
            const maxUploadSize = 10 * 1024 * 1024;

            function formatFileSize(bytes) {
                if (!bytes) {
                    return '0 KB';
                }

                const units = ['bytes', 'KB', 'MB'];
                let size = bytes;
                let unitIndex = 0;

                while (size >= 1024 && unitIndex < units.length - 1) {
                    size = size / 1024;
                    unitIndex++;
                }

                return `${size.toFixed(unitIndex === 0 ? 0 : 1)} ${units[unitIndex]}`;
            }

            function clearUpload() {
                input.value = '';
                input.classList.remove('is-invalid');
                preview.classList.add('d-none');
                uploadBox.classList.remove('border-primary');
                error.classList.add('d-none');
                error.textContent = '';
            }

            function showError(file) {
                input.value = '';
                input.classList.add('is-invalid');
                preview.classList.add('d-none');
                uploadBox.classList.remove('border-primary');
                error.textContent = `${file.name} is ${formatFileSize(file.size)}. Please choose a file smaller than 10 MB.`;
                error.classList.remove('d-none');
            }

            input.addEventListener('change', function () {
                const file = input.files[0];

                if (!file) {
                    clearUpload();
                    return;
                }

                if (file.size > maxUploadSize) {
                    showError(file);
                    return;
                }

                error.classList.add('d-none');
                input.classList.remove('is-invalid');
                filename.textContent = file.name;
                meta.textContent = `${formatFileSize(file.size)} selected and ready to preview.`;
                preview.classList.remove('d-none');
                uploadBox.classList.add('border-primary');
            });

            clear.addEventListener('click', clearUpload);

            form.addEventListener('submit', function () {
                if (!submit) {
                    return;
                }

                submit.disabled = true;
                submit.innerHTML = '<span class="spinner-border spinner-border-sm" aria-hidden="true"></span><span>Uploading...</span>';
            });
        });
    </script>
@endpush
