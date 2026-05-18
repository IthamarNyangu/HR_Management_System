@php
    $isOfficer = auth()->user()->hasRole('HR Officer');
@endphp

<div class="row g-3" data-temporary-appointment-form>
    @if ($appointment->exists)
        <div class="col-md-4">
            <label class="form-label">Reference Number</label>
            <input type="text" class="form-control" value="{{ $appointment->reference_no }}" disabled>
        </div>
    @endif

    <div class="col-md-{{ $appointment->exists ? '8' : '6' }}">
        <label for="employee_id" class="form-label">Employee</label>
        <select id="employee_id" name="employee_id" class="form-select @error('employee_id') is-invalid @enderror" data-employee-select required>
            <option value="">Select employee</option>
            @foreach ($employees as $employee)
                <option
                    value="{{ $employee->id }}"
                    data-province-id="{{ $employee->province_id }}"
                    data-district-id="{{ $employee->district_id }}"
                    data-facility-id="{{ $employee->facility_id }}"
                    data-project-id="{{ $employee->project_id }}"
                    data-department-id="{{ $employee->department_id }}"
                    data-job-title-id="{{ $employee->job_title_id }}"
                    data-supervisor-name="{{ $employee->supervisor_name }}"
                    @selected((string) old('employee_id', $appointment->employee_id) === (string) $employee->id)
                >
                    {{ $employee->display_name }}
                </option>
            @endforeach
        </select>
        @error('employee_id')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-{{ $appointment->exists ? '4' : '6' }}">
        <label for="appointment_type_id" class="form-label">Appointment Type</label>
        <select id="appointment_type_id" name="appointment_type_id" class="form-select @error('appointment_type_id') is-invalid @enderror">
            <option value="">Select appointment type</option>
            @foreach ($appointmentTypes as $type)
                <option value="{{ $type->id }}" @selected((string) old('appointment_type_id', $appointment->appointment_type_id) === (string) $type->id)>{{ $type->name }}</option>
            @endforeach
        </select>
        @error('appointment_type_id')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-4">
        <label for="province_id" class="form-label">Province</label>
        @if ($isOfficer)
            <input type="hidden" name="province_id" value="{{ auth()->user()->province_id }}">
        @endif
        <select id="province_id" name="{{ $isOfficer ? '_province_display' : 'province_id' }}" class="form-select @error('province_id') is-invalid @enderror" data-province-select required @disabled($isOfficer)>
            <option value="">Select province</option>
            @foreach ($provinces as $province)
                <option value="{{ $province->id }}" @selected((string) old('province_id', $appointment->province_id) === (string) $province->id)>{{ $province->name }}</option>
            @endforeach
        </select>
        @error('province_id')
            <div class="invalid-feedback d-block">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-4">
        <label for="district_id" class="form-label">District</label>
        <select id="district_id" name="district_id" class="form-select @error('district_id') is-invalid @enderror" data-district-select>
            <option value="">Select district</option>
            @foreach ($districts as $district)
                <option value="{{ $district->id }}" data-province-id="{{ $district->province_id }}" @selected((string) old('district_id', $appointment->district_id) === (string) $district->id)>{{ $district->name }}</option>
            @endforeach
        </select>
        @error('district_id')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-4">
        <label for="facility_id" class="form-label">Facility</label>
        <select id="facility_id" name="facility_id" class="form-select @error('facility_id') is-invalid @enderror" data-facility-select>
            <option value="">Select facility</option>
            @foreach ($facilities as $facility)
                <option value="{{ $facility->id }}" data-district-id="{{ $facility->district_id }}" @selected((string) old('facility_id', $appointment->facility_id) === (string) $facility->id)>{{ $facility->name }}</option>
            @endforeach
        </select>
        @error('facility_id')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-6">
        <label for="project_id" class="form-label">Project</label>
        <select id="project_id" name="project_id" class="form-select @error('project_id') is-invalid @enderror" data-project-select>
            <option value="">Select project</option>
            @foreach ($projects as $project)
                <option value="{{ $project->id }}" @selected((string) old('project_id', $appointment->project_id) === (string) $project->id)>{{ $project->name }}</option>
            @endforeach
        </select>
        @error('project_id')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-6">
        <label for="department_id" class="form-label">Department</label>
        <select id="department_id" name="department_id" class="form-select @error('department_id') is-invalid @enderror" data-department-select>
            <option value="">Select department</option>
            @foreach ($departments as $department)
                <option value="{{ $department->id }}" @selected((string) old('department_id', $appointment->department_id) === (string) $department->id)>{{ $department->name }}</option>
            @endforeach
        </select>
        @error('department_id')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-6">
        <label for="current_job_title_id" class="form-label">Current Job Title</label>
        <select id="current_job_title_id" name="current_job_title_id" class="form-select @error('current_job_title_id') is-invalid @enderror" data-current-job-title-select>
            <option value="">Select current job title</option>
            @foreach ($jobTitles as $jobTitle)
                <option value="{{ $jobTitle->id }}" @selected((string) old('current_job_title_id', $appointment->current_job_title_id) === (string) $jobTitle->id)>{{ $jobTitle->name }}</option>
            @endforeach
        </select>
        @error('current_job_title_id')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-6">
        <label for="temporary_job_title_id" class="form-label">Temporary Job Title</label>
        <select id="temporary_job_title_id" name="temporary_job_title_id" class="form-select @error('temporary_job_title_id') is-invalid @enderror" required>
            <option value="">Select temporary job title</option>
            @foreach ($jobTitles as $jobTitle)
                <option value="{{ $jobTitle->id }}" @selected((string) old('temporary_job_title_id', $appointment->temporary_job_title_id) === (string) $jobTitle->id)>{{ $jobTitle->name }}</option>
            @endforeach
        </select>
        @error('temporary_job_title_id')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-4">
        <label for="appointment_status_id" class="form-label">Appointment Status</label>
        <select id="appointment_status_id" name="appointment_status_id" class="form-select @error('appointment_status_id') is-invalid @enderror" required>
            <option value="">Select status</option>
            @foreach ($appointmentStatuses as $status)
                <option value="{{ $status->id }}" @selected((string) old('appointment_status_id', $appointment->appointment_status_id) === (string) $status->id)>{{ $status->name }}</option>
            @endforeach
        </select>
        @error('appointment_status_id')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-4">
        <label for="start_date" class="form-label">Start Date</label>
        <input id="start_date" name="start_date" type="date" class="form-control @error('start_date') is-invalid @enderror" value="{{ old('start_date', $appointment->start_date?->format('Y-m-d') ?? now()->toDateString()) }}" required>
        @error('start_date')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-4">
        <label for="end_date" class="form-label">End Date</label>
        <input id="end_date" name="end_date" type="date" class="form-control @error('end_date') is-invalid @enderror" value="{{ old('end_date', $appointment->end_date?->format('Y-m-d')) }}" required>
        @error('end_date')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-6">
        <label for="supervisor_name" class="form-label">Supervisor</label>
        <input id="supervisor_name" name="supervisor_name" type="text" class="form-control @error('supervisor_name') is-invalid @enderror" value="{{ old('supervisor_name', $appointment->supervisor_name) }}" data-supervisor-input>
        @error('supervisor_name')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-6">
        <label for="reason" class="form-label">Reason</label>
        <textarea id="reason" name="reason" rows="3" class="form-control @error('reason') is-invalid @enderror">{{ old('reason', $appointment->reason) }}</textarea>
        @error('reason')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-lg-6">
        <label for="comment" class="form-label">Comment</label>
        <textarea id="comment" name="comment" rows="5" class="form-control @error('comment') is-invalid @enderror">{{ old('comment', $appointment->comment) }}</textarea>
        @error('comment')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-lg-6">
        <div class="border rounded-2 bg-light p-3" data-upload-box>
            <div class="d-flex align-items-center gap-2 mb-3">
                <i class="bi bi-paperclip text-primary" aria-hidden="true"></i>
                <div class="fw-semibold">Appointment Supporting Document</div>
            </div>
            <label for="supporting_document" class="form-label">Upload File</label>
            <input id="supporting_document" name="supporting_document" type="file" class="form-control @error('supporting_document') is-invalid @enderror" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
            @error('supporting_document')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
            <div class="d-none alert alert-danger py-2 px-3 mt-3 mb-0" data-upload-error role="alert"></div>
            <div class="d-none small text-success mt-2" data-upload-ready aria-live="polite"></div>
            <div class="small text-muted mt-2">PDF, DOC, DOCX, JPG, JPEG, or PNG. Maximum 10 MB.</div>
        </div>
    </div>

    <div class="col-12 d-flex gap-2">
        <button type="submit" class="btn btn-primary btn-md" data-submit-button>Save Appointment</button>
        <a href="{{ $appointment->exists ? route('temporary-appointments.show', $appointment) : route('temporary-appointments.index') }}" class="btn btn-secondary btn-md">Cancel</a>
    </div>
</div>

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const form = document.querySelector('[data-temporary-appointment-form]');

            if (!form) return;

            const employee = form.querySelector('[data-employee-select]');
            const province = form.querySelector('[data-province-select]');
            const district = form.querySelector('[data-district-select]');
            const facility = form.querySelector('[data-facility-select]');
            const project = form.querySelector('[data-project-select]');
            const department = form.querySelector('[data-department-select]');
            const currentJobTitle = form.querySelector('[data-current-job-title-select]');
            const supervisor = form.querySelector('[data-supervisor-input]');
            const uploadInput = form.querySelector('#supporting_document');
            const uploadError = form.querySelector('[data-upload-error]');
            const uploadReady = form.querySelector('[data-upload-ready]');
            const submitButton = form.querySelector('[data-submit-button]');
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

            function filterEmployees() {
                const provinceId = province.value;
                employee.querySelectorAll('option[data-province-id]').forEach(function (option) {
                    const visible = !provinceId || option.dataset.provinceId === provinceId;
                    option.hidden = !visible;
                    option.disabled = !visible;
                });
                if (employee.selectedOptions[0]?.disabled) employee.value = '';
            }

            function filterDistricts() {
                const provinceId = province.value;
                district.querySelectorAll('option[data-province-id]').forEach(function (option) {
                    const visible = !provinceId || option.dataset.provinceId === provinceId;
                    option.hidden = !visible;
                    option.disabled = !visible;
                });
                if (district.selectedOptions[0]?.disabled) district.value = '';
                filterFacilities();
                filterEmployees();
            }

            function filterFacilities() {
                const districtId = district.value;
                facility.querySelectorAll('option[data-district-id]').forEach(function (option) {
                    const visible = !districtId || option.dataset.districtId === districtId;
                    option.hidden = !visible;
                    option.disabled = !visible;
                });
                if (facility.selectedOptions[0]?.disabled) facility.value = '';
            }

            function applyEmployeeDefaults() {
                const option = employee.selectedOptions[0];
                if (!option || !option.dataset.provinceId) return;
                if (!province.disabled) province.value = option.dataset.provinceId || '';
                district.value = option.dataset.districtId || '';
                facility.value = option.dataset.facilityId || '';
                project.value = option.dataset.projectId || '';
                department.value = option.dataset.departmentId || '';
                currentJobTitle.value = option.dataset.jobTitleId || '';
                supervisor.value = option.dataset.supervisorName || supervisor.value || '';
                filterDistricts();
                filterFacilities();
            }

            uploadInput?.addEventListener('change', function () {
                const file = uploadInput.files[0];
                uploadError?.classList.add('d-none');
                uploadReady?.classList.add('d-none');
                uploadInput.classList.remove('is-invalid');
                if (!file) return;
                if (file.size > maxUploadSize) {
                    uploadInput.value = '';
                    uploadInput.classList.add('is-invalid');
                    if (uploadError) {
                        uploadError.textContent = `${file.name} is ${formatFileSize(file.size)}. Please choose a file smaller than 10 MB.`;
                        uploadError.classList.remove('d-none');
                    }
                    return;
                }
                if (uploadReady) {
                    uploadReady.textContent = `${file.name} (${formatFileSize(file.size)}) is selected and ready to attach when you save.`;
                    uploadReady.classList.remove('d-none');
                }
            });

            form.addEventListener('submit', function () {
                if (!submitButton) return;
                submitButton.disabled = true;
                submitButton.innerHTML = '<span class="spinner-border spinner-border-sm" aria-hidden="true"></span><span>Saving...</span>';
            });

            province.addEventListener('change', filterDistricts);
            district.addEventListener('change', filterFacilities);
            employee.addEventListener('change', applyEmployeeDefaults);
            filterDistricts();
            filterFacilities();
        });
    </script>
@endpush
