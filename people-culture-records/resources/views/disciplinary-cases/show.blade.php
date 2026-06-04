@extends('layouts.app')

@section('title', $case->reference_no)
@section('page-title', $case->reference_no)

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('disciplinary-cases.index') }}">Disciplinary Cases</a></li>
    <li class="breadcrumb-item active" aria-current="page">{{ $case->reference_no }}</li>
@endsection

@section('page-actions')
    <div class="d-flex flex-wrap gap-2 justify-content-end">
        @can('submit', $case)
            <form method="POST" action="{{ route('disciplinary-cases.submit', $case) }}" data-confirm="true" data-confirm-title="Submit case?" data-confirm-message="This case will be sent for HR Manager/Admin approval. Do you want to continue?" data-confirm-button="Submit case">
                @csrf
                @method('PATCH')
                <button type="submit" class="btn btn-primary btn-md">Submit</button>
            </form>
        @endcan
        @can('approve', $case)
            <form method="POST" action="{{ route('disciplinary-cases.approve', $case) }}" data-confirm="true" data-confirm-title="Approve case?" data-confirm-message="This case will become active. Do you want to continue?" data-confirm-button="Approve case">
                @csrf
                @method('PATCH')
                <button type="submit" class="btn btn-primary btn-md">Approve</button>
            </form>
        @endcan
        @can('close', $case)
            <form method="POST" action="{{ route('disciplinary-cases.close', $case) }}" data-confirm="true" data-confirm-title="Close case?" data-confirm-message="This case will be marked as closed. Do you want to continue?" data-confirm-button="Close case">
                @csrf
                @method('PATCH')
                <button type="submit" class="btn btn-secondary btn-md">Close</button>
            </form>
        @endcan
        @can('update', $case)
            <a href="{{ route('disciplinary-cases.edit', $case) }}" class="btn btn-primary-outline btn-md">Edit</a>
        @endcan
        @can('archive', $case)
            <form method="POST" action="{{ route('disciplinary-cases.archive', $case) }}" data-confirm="true" data-confirm-title="Archive disciplinary case?" data-confirm-message="This case will be moved to archived records. Do you want to continue?" data-confirm-button="Archive case" data-confirm-variant="btn-warning">
                @csrf
                @method('PATCH')
                <button type="submit" class="btn btn-outline-warning btn-md">Archive</button>
            </form>
        @endcan
        <a href="{{ route('disciplinary-cases.index') }}" class="btn btn-secondary btn-md">Back</a>
    </div>
@endsection

@section('content')
    @php
        $statusCode = strtoupper((string) $case->caseStatus?->code);
        $statusClass = match ($statusCode) {
            'ACTIVE' => 'success',
            'SUBMITTED' => 'primary',
            'CLOSED' => 'secondary',
            default => 'light',
        };
    @endphp

    <div class="row g-3">
        <div class="col-lg-4">
            <section class="bg-white border rounded-2 p-4 h-100">
                <div class="d-flex justify-content-between align-items-start gap-3">
                    <div>
                        <div class="text-muted small">Workflow Status</div>
                        <div class="h5 mb-0"><span class="badge text-bg-{{ $statusClass }}">{{ $case->caseStatus?->name ?? '-' }}</span></div>
                    </div>
                    @if ($case->is_expired)
                        <span class="badge text-bg-danger">Expired</span>
                    @elseif ($case->expires_soon)
                        <span class="badge text-bg-warning">Expiring soon</span>
                    @endif
                </div>
                <hr>
                <dl class="mb-0">
                    <dt>Employee</dt>
                    <dd>
                        <a href="{{ route('employees.show', $case->employee) }}">{{ $case->employee?->display_name }}</a>
                    </dd>
                    <dt>Province</dt>
                    <dd>{{ $case->province?->name }}</dd>
                    <dt>District</dt>
                    <dd>{{ $case->district?->name }}</dd>
                    <dt>Facility</dt>
                    <dd>{{ $case->facility?->name ?? '-' }}</dd>
                    <dt>Project</dt>
                    <dd>{{ $case->project?->name ?? '-' }}</dd>
                </dl>
            </section>
        </div>

        <div class="col-lg-8">
            <section class="bg-white border rounded-2 p-4 mb-3">
                <h2 class="h5">Case Details</h2>
                <div class="row g-3">
                    <div class="col-md-6"><strong>Offence Category:</strong> {{ $case->offenceCategory?->name ?? '-' }}</div>
                    <div class="col-md-6"><strong>Penalty Type:</strong> {{ $case->penaltyType?->name ?? '-' }}</div>
                    <div class="col-md-6"><strong>Effective Date:</strong> {{ $case->effective_date?->format('d M Y') }}</div>
                    <div class="col-md-6"><strong>Expiry Date:</strong> {{ $case->expiry_date?->format('d M Y') ?? '-' }}</div>
                    <div class="col-md-6"><strong>Line Manager:</strong> {{ $case->supervisor_name ?? '-' }}</div>
                    <div class="col-md-6"><strong>Created By:</strong> {{ $case->createdBy?->name ?? '-' }}</div>
                </div>
                <hr>
                <h3 class="h6">Nature of Offence</h3>
                <p class="mb-0">{{ $case->nature_of_offence }}</p>
            </section>

            <section class="bg-white border rounded-2 p-4 mb-3">
                <h2 class="h5">Approval & Closure</h2>
                <div class="row g-3">
                    <div class="col-md-6"><strong>Submitted At:</strong> {{ $case->submitted_at?->format('d M Y H:i') ?? '-' }}</div>
                    <div class="col-md-6"><strong>Approved By:</strong> {{ $case->approvedBy?->name ?? '-' }}</div>
                    <div class="col-md-6"><strong>Approved At:</strong> {{ $case->approved_at?->format('d M Y H:i') ?? '-' }}</div>
                    <div class="col-md-6"><strong>Closed At:</strong> {{ $case->closed_at?->format('d M Y H:i') ?? '-' }}</div>
                </div>
            </section>

            <section class="bg-white border rounded-2 p-4 mb-3">
                <h2 class="h5">Comments</h2>
                <p class="mb-0 text-muted">{{ $case->comment ?: 'No comments recorded.' }}</p>
            </section>

            <section class="bg-white border rounded-2 p-4">
                <div class="d-flex justify-content-between align-items-center gap-3 mb-3">
                    <h2 class="h5 mb-0">Supporting Documents</h2>
                    <span class="badge text-bg-light">{{ $case->attachments->count() }} file(s)</span>
                </div>

                @can('uploadAttachment', $case)
                    <form method="POST" action="{{ route('disciplinary-cases.attachments.store', $case) }}" enctype="multipart/form-data" class="row g-2 align-items-end mb-4" data-case-attachment-form>
                        @csrf
                        <div class="col-md-4">
                            <label for="document_type_id" class="form-label">Document Type</label>
                            <select id="document_type_id" name="document_type_id" class="form-select @error('document_type_id') is-invalid @enderror">
                                <option value="">Not specified</option>
                                @foreach ($documentTypes as $documentType)
                                    <option value="{{ $documentType->id }}" @selected((string) old('document_type_id') === (string) $documentType->id)>{{ $documentType->name }}</option>
                                @endforeach
                            </select>
                            @error('document_type_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-5">
                            <label for="document" class="form-label">Document</label>
                            <input id="document" name="document" type="file" class="form-control @error('document') is-invalid @enderror" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" required>
                            @error('document')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="d-none alert alert-danger py-2 px-3 mt-2 mb-0" data-upload-error role="alert"></div>
                            <div class="d-none small text-success mt-2" data-upload-ready aria-live="polite"></div>
                        </div>
                        <div class="col-md-3">
                            <button type="submit" class="btn btn-primary btn-md" data-upload-submit>Upload</button>
                        </div>
                    </form>
                @endcan

                <div class="table-responsive data-table-wrap">
                    <table class="table table-hover align-middle data-table">
                        <thead>
                            <tr>
                                <th>File</th>
                                <th>Type</th>
                                <th>Uploaded By</th>
                                <th>Size</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($case->attachments as $attachment)
                                <tr>
                                    <td>
                                        <div class="fw-semibold">{{ $attachment->original_filename }}</div>
                                        <div class="small text-muted">{{ $attachment->created_at?->format('d M Y H:i') }}</div>
                                    </td>
                                    <td>{{ $attachment->documentType?->name ?? '-' }}</td>
                                    <td>{{ $attachment->uploadedBy?->name ?? '-' }}</td>
                                    <td>{{ number_format($attachment->file_size / 1024, 1) }} KB</td>
                                    <td>
                                        <div class="d-inline-flex gap-2">
                                            <a href="{{ route('disciplinary-cases.attachments.download', [$case, $attachment]) }}" class="btn btn-sm btn-secondary">Download</a>
                                            @can('deleteAttachment', $case)
                                                <form method="POST" action="{{ route('disciplinary-cases.attachments.delete', [$case, $attachment]) }}" data-confirm="true" data-confirm-title="Delete document?" data-confirm-message="This supporting document will be removed from the case. Do you want to continue?" data-confirm-button="Delete document" data-confirm-variant="btn-danger">
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
                                    <td colspan="5" class="text-center text-muted py-4">No supporting documents uploaded.</td>
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
            const form = document.querySelector('[data-case-attachment-form]');

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
