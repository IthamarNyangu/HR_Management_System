@extends('layouts.app')

@section('title', $relocation->reference_no)
@section('page-title', $relocation->reference_no)

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('staff-relocations.index') }}">Staff Relocations</a></li>
    <li class="breadcrumb-item active" aria-current="page">{{ $relocation->reference_no }}</li>
@endsection

@section('page-actions')
    <div class="d-flex flex-wrap gap-2 justify-content-end">
        @can('update', $relocation)
            <a href="{{ route('staff-relocations.edit', $relocation) }}" class="btn btn-primary-outline btn-md">Edit</a>
        @endcan
        @can('archive', $relocation)
            <form method="POST" action="{{ route('staff-relocations.archive', $relocation) }}" data-confirm="true" data-confirm-title="Archive relocation?" data-confirm-message="This relocation will be moved to archived records. Do you want to continue?" data-confirm-button="Archive relocation" data-confirm-variant="btn-warning">
                @csrf
                @method('PATCH')
                <button type="submit" class="btn btn-outline-warning btn-md">Archive</button>
            </form>
        @endcan
        <a href="{{ route('staff-relocations.index') }}" class="btn btn-secondary btn-md">Back</a>
    </div>
@endsection

@section('content')
    <div class="row g-3">
        <div class="col-lg-4">
            <section class="bg-white border rounded-2 p-4 h-100">
                <div class="text-muted small">Employee</div>
                <div class="h5 mb-1">
                    <a href="{{ route('employees.show', $relocation->employee) }}">{{ $relocation->employee?->display_name }}</a>
                </div>
                <div class="small text-muted">{{ $relocation->employee?->email ?? 'No email recorded' }}</div>
                <hr>
                <dl class="mb-0">
                    <dt>Job Title</dt>
                    <dd>{{ $relocation->jobTitle?->name ?? '-' }}</dd>
                    <dt>Project</dt>
                    <dd>{{ $relocation->project?->name ?? '-' }}</dd>
                    <dt>Department</dt>
                    <dd>{{ $relocation->department?->name ?? '-' }}</dd>
                    <dt>Created By</dt>
                    <dd>{{ $relocation->createdBy?->name ?? '-' }}</dd>
                    <dt>Updated By</dt>
                    <dd>{{ $relocation->updatedBy?->name ?? '-' }}</dd>
                </dl>
            </section>
        </div>

        <div class="col-lg-8">
            <section class="bg-white border rounded-2 p-4 mb-3">
                <h2 class="h5">Relocation Details</h2>
                <div class="row g-3">
                    <div class="col-md-6">
                        <strong>From Location:</strong>
                        <div>{{ $relocation->fromProvince?->name }} - {{ $relocation->fromDistrict?->name }}{{ $relocation->fromFacility ? ' - '.$relocation->fromFacility->name : '' }}</div>
                    </div>
                    <div class="col-md-6">
                        <strong>To Location:</strong>
                        <div>{{ $relocation->toProvince?->name }} - {{ $relocation->toDistrict?->name }}{{ $relocation->toFacility ? ' - '.$relocation->toFacility->name : '' }}</div>
                    </div>
                    <div class="col-md-6"><strong>Relocation Reason:</strong> {{ $relocation->relocationReason?->name ?? '-' }}</div>
                    <div class="col-md-6"><strong>Effective Date:</strong> {{ $relocation->effective_date?->format('d M Y') }}</div>
                    <div class="col-md-6"><strong>Relocation Amount:</strong> {{ $relocation->relocation_amount !== null ? number_format((float) $relocation->relocation_amount, 2) : '-' }}</div>
                    <div class="col-md-6"><strong>Applied To Employee Profile:</strong> {{ $relocation->location_applied_at?->format('d M Y H:i') ?? 'Scheduled / pending' }}</div>
                </div>
            </section>

            <section class="bg-white border rounded-2 p-4 mb-3">
                <h2 class="h5">Comments</h2>
                <p class="mb-0 text-muted">{{ $relocation->comment ?: 'No comments recorded.' }}</p>
            </section>

            <section class="bg-white border rounded-2 p-4">
                <div class="d-flex justify-content-between align-items-center gap-3 mb-3">
                    <h2 class="h5 mb-0">Relocation Documents</h2>
                    <span class="badge text-bg-light">{{ $relocation->attachments->count() }} file(s)</span>
                </div>

                @can('uploadAttachment', $relocation)
                    <form method="POST" action="{{ route('staff-relocations.attachments.store', $relocation) }}" enctype="multipart/form-data" class="row g-2 align-items-end mb-4" data-relocation-attachment-form>
                        @csrf
                        <div class="col-md-8">
                            <label for="document" class="form-label">Document</label>
                            <input id="document" name="document" type="file" class="form-control @error('document') is-invalid @enderror" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" required>
                            @error('document')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="d-none alert alert-danger py-2 px-3 mt-2 mb-0" data-upload-error role="alert"></div>
                            <div class="d-none small text-success mt-2" data-upload-ready aria-live="polite"></div>
                        </div>
                        <div class="col-md-4">
                            <button type="submit" class="btn btn-primary btn-md" data-upload-submit>Upload</button>
                        </div>
                    </form>
                @endcan

                <div class="table-responsive data-table-wrap">
                    <table class="table table-hover align-middle data-table">
                        <thead>
                            <tr>
                                <th>File</th>
                                <th>Uploaded By</th>
                                <th>Size</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($relocation->attachments as $attachment)
                                <tr>
                                    <td>
                                        <div class="fw-semibold">{{ $attachment->original_filename }}</div>
                                        <div class="small text-muted">{{ $attachment->created_at?->format('d M Y H:i') }}</div>
                                    </td>
                                    <td>{{ $attachment->uploadedBy?->name ?? '-' }}</td>
                                    <td>{{ number_format($attachment->file_size / 1024, 1) }} KB</td>
                                    <td>
                                        <div class="d-inline-flex gap-2">
                                            <a href="{{ route('staff-relocations.attachments.download', [$relocation, $attachment]) }}" class="btn btn-sm btn-secondary">Download</a>
                                            @can('deleteAttachment', $relocation)
                                                <form method="POST" action="{{ route('staff-relocations.attachments.delete', [$relocation, $attachment]) }}" data-confirm="true" data-confirm-title="Delete document?" data-confirm-message="This relocation document will be removed. Do you want to continue?" data-confirm-button="Delete document" data-confirm-variant="btn-danger">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                                </form>
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-4">No relocation documents uploaded.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const form = document.querySelector('[data-relocation-attachment-form]');

            if (!form) {
                return;
            }

            const input = form.querySelector('#document');
            const error = form.querySelector('[data-upload-error]');
            const ready = form.querySelector('[data-upload-ready]');
            const submit = form.querySelector('[data-upload-submit]');
            const maxUploadSize = 10 * 1024 * 1024;

            function formatFileSize(bytes) {
                const units = ['bytes', 'KB', 'MB'];
                let size = bytes || 0;
                let unitIndex = 0;

                while (size >= 1024 && unitIndex < units.length - 1) {
                    size = size / 1024;
                    unitIndex++;
                }

                return `${size.toFixed(unitIndex === 0 ? 0 : 1)} ${units[unitIndex]}`;
            }

            input?.addEventListener('change', function () {
                const file = input.files[0];

                error?.classList.add('d-none');
                ready?.classList.add('d-none');
                input.classList.remove('is-invalid');

                if (!file) {
                    return;
                }

                if (file.size > maxUploadSize) {
                    input.value = '';
                    input.classList.add('is-invalid');

                    if (error) {
                        error.textContent = `${file.name} is ${formatFileSize(file.size)}. Please choose a file smaller than 10 MB.`;
                        error.classList.remove('d-none');
                    }

                    return;
                }

                if (ready) {
                    ready.textContent = `${file.name} (${formatFileSize(file.size)}) is ready to upload.`;
                    ready.classList.remove('d-none');
                }
            });

            form.addEventListener('submit', function (event) {
                const file = input?.files[0];

                if (file && file.size > maxUploadSize) {
                    event.preventDefault();
                    input.dispatchEvent(new Event('change'));
                    return;
                }

                if (submit) {
                    submit.disabled = true;
                    submit.innerHTML = '<span class="spinner-border spinner-border-sm" aria-hidden="true"></span><span>Uploading...</span>';
                }
            });
        });
    </script>
@endpush
