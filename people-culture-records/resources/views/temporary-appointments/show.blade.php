@extends('layouts.app')

@section('title', $appointment->reference_no)
@section('page-title', $appointment->reference_no)

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('temporary-appointments.index') }}">Temporary Appointments</a></li>
    <li class="breadcrumb-item active" aria-current="page">{{ $appointment->reference_no }}</li>
@endsection

@section('page-actions')
    <div class="d-flex flex-wrap gap-2 justify-content-end">
        @can('extend', $appointment)
            <button type="button" class="btn btn-primary btn-md" data-bs-toggle="modal" data-bs-target="#extendAppointmentModal">Extend Appointment</button>
        @endcan
        @can('update', $appointment)
            <a href="{{ route('temporary-appointments.edit', $appointment) }}" class="btn btn-primary-outline btn-md">Edit</a>
        @endcan
        @can('archive', $appointment)
            <form method="POST" action="{{ route('temporary-appointments.archive', $appointment) }}" data-confirm="true" data-confirm-title="Archive appointment?" data-confirm-message="This temporary appointment will be moved to archived records. Do you want to continue?" data-confirm-button="Archive appointment" data-confirm-variant="btn-warning">
                @csrf
                @method('PATCH')
                <button type="submit" class="btn btn-outline-warning btn-md">Archive</button>
            </form>
        @endcan
        <a href="{{ route('temporary-appointments.index') }}" class="btn btn-secondary btn-md">Back</a>
    </div>
@endsection

@section('content')
    <div class="row g-3">
        <div class="col-lg-4">
            <section class="bg-white border rounded-2 p-4 h-100">
                <div class="text-muted small">Employee</div>
                <div class="h5 mb-1">
                    <a href="{{ route('employees.show', $appointment->employee) }}">{{ $appointment->employee?->display_name }}</a>
                </div>
                <div class="small text-muted">{{ $appointment->employee?->email ?? 'No email recorded' }}</div>
                <hr>
                <dl class="mb-0">
                    <dt>Status</dt>
                    <dd><span class="badge text-bg-primary">{{ $appointment->appointmentStatus?->name }}</span></dd>
                    <dt>Date Status</dt>
                    <dd><span class="badge text-bg-light">{{ $appointment->date_status_label }}</span></dd>
                    <dt>Province</dt>
                    <dd>{{ $appointment->province?->name }}</dd>
                    <dt>District</dt>
                    <dd>{{ $appointment->district?->name ?? '-' }}</dd>
                    <dt>Facility</dt>
                    <dd>{{ $appointment->facility?->name ?? '-' }}</dd>
                    <dt>Project</dt>
                    <dd>{{ $appointment->project?->name ?? '-' }}</dd>
                    <dt>Department</dt>
                    <dd>{{ $appointment->department?->name ?? '-' }}</dd>
                </dl>
            </section>
        </div>

        <div class="col-lg-8">
            <section class="bg-white border rounded-2 p-4 mb-3">
                <h2 class="h5">Appointment Details</h2>
                <div class="row g-3">
                    <div class="col-md-6"><strong>Current Job Title:</strong> {{ $appointment->currentJobTitle?->name ?? '-' }}</div>
                    <div class="col-md-6"><strong>Temporary Job Title:</strong> {{ $appointment->temporaryJobTitle?->name ?? '-' }}</div>
                    <div class="col-md-6"><strong>Supervisor:</strong> {{ $appointment->supervisor_name ?? '-' }}</div>
                    <div class="col-md-6"><strong>Start Date:</strong> {{ $appointment->start_date?->format('d M Y') }}</div>
                    <div class="col-md-6"><strong>Current End Date:</strong> {{ $appointment->end_date?->format('d M Y') }}</div>
                    <div class="col-md-6"><strong>Completed At:</strong> {{ $appointment->completed_at?->format('d M Y H:i') ?? '-' }}</div>
                    <div class="col-md-6"><strong>Created By:</strong> {{ $appointment->createdBy?->name ?? '-' }}</div>
                </div>
            </section>

            <section class="bg-white border rounded-2 p-4 mb-3">
                <h2 class="h5">Reason / Comment</h2>
                <p class="mb-0 text-muted">{{ $appointment->reason ?: ($appointment->comment ?: 'No reason or comment recorded.') }}</p>
            </section>

            <section class="bg-white border rounded-2 p-4 mb-3">
                <div class="d-flex justify-content-between align-items-center gap-3 mb-3">
                    <h2 class="h5 mb-0">Extension History</h2>
                    <span class="badge text-bg-light">{{ $appointment->extensions->count() }} extension(s)</span>
                </div>
                <div class="table-responsive data-table-wrap">
                    <table class="table table-hover align-middle data-table">
                        <thead>
                            <tr>
                                <th>Previous End Date</th>
                                <th>New End Date</th>
                                <th>Reason</th>
                                <th>Extended By</th>
                                <th>Extended At</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($appointment->extensions as $extension)
                                <tr>
                                    <td>{{ $extension->previous_end_date?->format('d M Y') }}</td>
                                    <td>{{ $extension->new_end_date?->format('d M Y') }}</td>
                                    <td>
                                        <div>{{ $extension->reason ?: '-' }}</div>
                                        @if ($extension->comment)
                                            <div class="small text-muted">{{ $extension->comment }}</div>
                                        @endif
                                    </td>
                                    <td>{{ $extension->extendedBy?->name ?? '-' }}</td>
                                    <td>{{ $extension->extended_at?->format('d M Y H:i') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">No extensions recorded.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="bg-white border rounded-2 p-4">
                <div class="d-flex justify-content-between align-items-center gap-3 mb-3">
                    <h2 class="h5 mb-0">Appointment Documents</h2>
                    <span class="badge text-bg-light">{{ $appointment->attachments->count() }} file(s)</span>
                </div>

                @can('uploadAttachment', $appointment)
                    <form method="POST" action="{{ route('temporary-appointments.attachments.store', $appointment) }}" enctype="multipart/form-data" class="row g-2 align-items-end mb-4" data-temporary-appointment-attachment-form>
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
                            @forelse ($appointment->attachments as $attachment)
                                <tr>
                                    <td>
                                        <div class="fw-semibold">{{ $attachment->original_filename }}</div>
                                        <div class="small text-muted">{{ $attachment->created_at?->format('d M Y H:i') }}</div>
                                    </td>
                                    <td>{{ $attachment->uploadedBy?->name ?? '-' }}</td>
                                    <td>{{ number_format($attachment->file_size / 1024, 1) }} KB</td>
                                    <td>
                                        <div class="d-inline-flex gap-2">
                                            <a href="{{ route('temporary-appointments.attachments.download', [$appointment, $attachment]) }}" class="btn btn-sm btn-secondary">Download</a>
                                            @can('deleteAttachment', $appointment)
                                                <form method="POST" action="{{ route('temporary-appointments.attachments.delete', [$appointment, $attachment]) }}" data-confirm="true" data-confirm-title="Delete document?" data-confirm-message="This appointment document will be removed. Do you want to continue?" data-confirm-button="Delete document" data-confirm-variant="btn-danger">
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
                                    <td colspan="4" class="text-center text-muted py-4">No appointment documents uploaded.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </div>

    @can('extend', $appointment)
        <div class="modal fade" id="extendAppointmentModal" tabindex="-1" aria-labelledby="extendAppointmentModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <form method="POST" action="{{ route('temporary-appointments.extend', $appointment) }}" class="modal-content">
                    @csrf
                    @method('PATCH')
                    <div class="modal-header">
                        <h2 class="modal-title h5" id="extendAppointmentModalLabel">Extend Appointment</h2>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p class="small text-muted">Current end date: {{ $appointment->end_date?->format('d M Y') }}</p>
                        <div class="mb-3">
                            <label for="new_end_date" class="form-label">New End Date</label>
                            <input id="new_end_date" name="new_end_date" type="date" class="form-control @error('new_end_date') is-invalid @enderror" min="{{ $appointment->end_date?->copy()->addDay()->format('Y-m-d') }}" required>
                            @error('new_end_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label for="extension_reason" class="form-label">Extension Reason</label>
                            <textarea id="extension_reason" name="extension_reason" rows="3" class="form-control @error('extension_reason') is-invalid @enderror"></textarea>
                            @error('extension_reason')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div>
                            <label for="extension_comment" class="form-label">Comment</label>
                            <textarea id="extension_comment" name="extension_comment" rows="3" class="form-control @error('extension_comment') is-invalid @enderror"></textarea>
                            @error('extension_comment')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary btn-md" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary btn-md">Save Extension</button>
                    </div>
                </form>
            </div>
        </div>
    @endcan
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const form = document.querySelector('[data-temporary-appointment-attachment-form]');
            if (!form) return;

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
                if (!file) return;
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
