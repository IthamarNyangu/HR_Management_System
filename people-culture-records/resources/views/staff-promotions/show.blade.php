@extends('layouts.app')

@section('title', $promotion->reference_no)
@section('page-title', $promotion->reference_no)

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('staff-promotions.index') }}">Staff Promotions</a></li>
    <li class="breadcrumb-item active" aria-current="page">{{ $promotion->reference_no }}</li>
@endsection

@section('page-actions')
    <div class="d-flex flex-wrap gap-2 justify-content-end">
        @can('update', $promotion)
            <a href="{{ route('staff-promotions.edit', $promotion) }}" class="btn btn-primary-outline btn-md">Edit</a>
        @endcan
        @can('archive', $promotion)
            <form method="POST" action="{{ route('staff-promotions.archive', $promotion) }}" data-confirm="true" data-confirm-title="Archive promotion?" data-confirm-message="This promotion will be moved to archived records. Do you want to continue?" data-confirm-button="Archive promotion" data-confirm-variant="btn-warning">
                @csrf
                @method('PATCH')
                <button type="submit" class="btn btn-outline-warning btn-md">Archive</button>
            </form>
        @endcan
        <a href="{{ route('staff-promotions.index') }}" class="btn btn-secondary btn-md">Back</a>
    </div>
@endsection

@section('content')
    <div class="row g-3">
        <div class="col-lg-4">
            <section class="bg-white border rounded-2 p-4 h-100">
                <div class="text-muted small">Employee</div>
                <div class="h5 mb-1">
                    <a href="{{ route('employees.show', $promotion->employee) }}">{{ $promotion->employee?->display_name }}</a>
                </div>
                <div class="small text-muted">{{ $promotion->employee?->email ?? 'No email recorded' }}</div>
                <hr>
                <dl class="mb-0">
                    <dt>Province</dt>
                    <dd>{{ $promotion->province?->name }}</dd>
                    <dt>District</dt>
                    <dd>{{ $promotion->district?->name ?? '-' }}</dd>
                    <dt>Facility</dt>
                    <dd>{{ $promotion->facility?->name ?? '-' }}</dd>
                    <dt>Project</dt>
                    <dd>{{ $promotion->project?->name ?? '-' }}</dd>
                    <dt>Department</dt>
                    <dd>{{ $promotion->department?->name ?? '-' }}</dd>
                </dl>
            </section>
        </div>

        <div class="col-lg-8">
            <section class="bg-white border rounded-2 p-4 mb-3">
                <h2 class="h5">Promotion Details</h2>
                <div class="row g-3">
                    <div class="col-md-6"><strong>Old Job Title:</strong> {{ $promotion->oldJobTitle?->name ?? '-' }}</div>
                    <div class="col-md-6"><strong>New Job Title:</strong> {{ $promotion->newJobTitle?->name ?? '-' }}</div>
                    <div class="col-md-6"><strong>Promotion Type:</strong> {{ $promotion->promotionType?->name ?? '-' }}</div>
                    <div class="col-md-6"><strong>Promotion Date:</strong> {{ $promotion->promotion_date?->format('d M Y') }}</div>
                    <div class="col-md-6"><strong>Effective Date:</strong> {{ $promotion->effective_date?->format('d M Y') ?? '-' }}</div>
                    <div class="col-md-6"><strong>Applied To Employee Profile:</strong> {{ $promotion->job_title_applied_at?->format('d M Y H:i') ?? 'Scheduled / pending' }}</div>
                    <div class="col-md-6"><strong>Created By:</strong> {{ $promotion->createdBy?->name ?? '-' }}</div>
                    <div class="col-md-6"><strong>Updated By:</strong> {{ $promotion->updatedBy?->name ?? '-' }}</div>
                </div>
            </section>

            <section class="bg-white border rounded-2 p-4 mb-3">
                <h2 class="h5">Comments</h2>
                <p class="mb-0 text-muted">{{ $promotion->comment ?: 'No comments recorded.' }}</p>
            </section>

            <section class="bg-white border rounded-2 p-4">
                <div class="d-flex justify-content-between align-items-center gap-3 mb-3">
                    <h2 class="h5 mb-0">Promotion Documents</h2>
                    <span class="badge text-bg-light">{{ $promotion->attachments->count() }} file(s)</span>
                </div>

                @can('uploadAttachment', $promotion)
                    <form method="POST" action="{{ route('staff-promotions.attachments.store', $promotion) }}" enctype="multipart/form-data" class="row g-2 align-items-end mb-4" data-promotion-attachment-form>
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
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($promotion->attachments as $attachment)
                                <tr>
                                    <td>
                                        <div class="fw-semibold">{{ $attachment->original_filename }}</div>
                                        <div class="small text-muted">{{ $attachment->created_at?->format('d M Y H:i') }}</div>
                                    </td>
                                    <td>{{ $attachment->uploadedBy?->name ?? '-' }}</td>
                                    <td>{{ number_format($attachment->file_size / 1024, 1) }} KB</td>
                                    <td class="text-end">
                                        <div class="d-inline-flex gap-2">
                                            <a href="{{ route('staff-promotions.attachments.download', [$promotion, $attachment]) }}" class="btn btn-sm btn-secondary">Download</a>
                                            @can('deleteAttachment', $promotion)
                                                <form method="POST" action="{{ route('staff-promotions.attachments.delete', [$promotion, $attachment]) }}" data-confirm="true" data-confirm-title="Delete document?" data-confirm-message="This promotion document will be removed. Do you want to continue?" data-confirm-button="Delete document" data-confirm-variant="btn-danger">
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
                                    <td colspan="4" class="text-center text-muted py-4">No promotion documents uploaded.</td>
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
            const form = document.querySelector('[data-promotion-attachment-form]');

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
