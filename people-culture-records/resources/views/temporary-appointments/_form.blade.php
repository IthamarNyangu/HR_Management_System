@php
    $isOfficer = auth()->user()->hasRole('HR Officer');
    $employeeOption = $selectedEmployeeOption ?? null;
    $supervisorOption = $selectedSupervisorOption ?? null;
    $employeeSearchValue = old('employee_search', $employeeOption['text'] ?? '');
            $supervisorSearchValue = old('supervisor_search', $supervisorOption['text'] ?? old('supervisor_name', $appointment->supervisor_name));
@endphp

@push('styles')
    <style>
        .smart-employee-select { position: relative; }
        .smart-employee-results {
            position: absolute;
            z-index: 1050;
            top: calc(100% + .35rem);
            left: 0;
            right: 0;
            max-height: 18rem;
            overflow-y: auto;
            border: 1px solid #d8e0ec;
            border-radius: .5rem;
            background: #fff;
            box-shadow: 0 .8rem 1.8rem rgba(23, 32, 51, .14);
        }
        .smart-employee-option {
            width: 100%;
            border: 0;
            background: #fff;
            padding: .75rem .9rem;
            text-align: left;
            border-bottom: 1px solid #eef2f7;
        }
        .smart-employee-option:hover,
        .smart-employee-option:focus {
            background: #f3f6fa;
            outline: none;
        }
        .smart-employee-option:last-child { border-bottom: 0; }
        .smart-employee-selected {
            border: 1px solid #d8e0ec;
            border-radius: .5rem;
            background: #f8fafc;
            padding: .75rem;
        }
    </style>
@endpush

<div class="d-grid gap-4" data-temporary-appointment-form>
    <section class="border rounded-2 bg-white p-4">
        <div class="mb-3">
            <h2 class="h5 mb-1">Employee</h2>
            <p class="text-muted mb-0">Select the staff member for the interim or acting appointment.</p>
        </div>
        <div class="row g-3">
            @if ($appointment->exists)
                <div class="col-md-6 col-xl-4">
                    <label class="form-label">Reference Number</label>
                    <input type="text" class="form-control" value="{{ $appointment->reference_no }}" disabled>
                </div>
            @endif

            <div class="col-md-6 col-xl-4">
                <label for="employee_search" class="form-label">Employee <span class="text-danger">*</span></label>
                <div class="smart-employee-select" data-smart-employee-select data-role="employee" data-url="{{ route('employees.search') }}" data-selected='@json($employeeOption)'>
                    <input
                        id="employee_search"
                        name="employee_search"
                        type="search"
                        class="form-control @error('employee_id') is-invalid @enderror"
                        value="{{ $employeeSearchValue }}"
                        placeholder="Search employee number, name, email, job title, or location"
                        autocomplete="off"
                        data-smart-input
                        required
                    >
                    <input id="employee_id" name="employee_id" type="hidden" value="{{ old('employee_id', $appointment->employee_id) }}" data-smart-id>
                    <div class="smart-employee-results d-none" data-smart-results role="listbox"></div>
                    <div class="smart-employee-selected mt-2 {{ $employeeOption ? '' : 'd-none' }}" data-smart-selected>
                        <div class="fw-semibold" data-smart-selected-text>{{ $employeeOption['text'] ?? '' }}</div>
                        <div class="small text-muted" data-smart-selected-details>{{ $employeeOption['details'] ?? '' }}</div>
                    </div>
                </div>
                <div class="form-text">Choosing an employee will auto-fill their current location and job details where available.</div>
                @error('employee_id')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
                <div class="invalid-feedback d-none" data-employee-feedback>Please choose an employee from the list.</div>
            </div>
        </div>
    </section>

    <section class="border rounded-2 bg-white p-4">
        <div class="mb-3">
            <h2 class="h5 mb-1">Location / Organisation Details</h2>
            <p class="text-muted mb-0">Confirm where the temporary appointment is linked in the organisation.</p>
        </div>
        <div class="row g-3">
            <div class="col-md-6 col-xl-4">
                <label for="province_id" class="form-label">Province <span class="text-danger">*</span></label>
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

            <div class="col-md-6 col-xl-4">
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

            <div class="col-md-6 col-xl-4">
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
        </div>
    </section>

    <section class="border rounded-2 bg-white p-4">
        <div class="mb-3">
            <h2 class="h5 mb-1">Appointment Role Details</h2>
            <p class="text-muted mb-0">The employee's permanent job title will not be changed by this temporary appointment.</p>
        </div>
        <div class="row g-3">
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
                <label for="temporary_job_title_id" class="form-label">Temporary Job Title <span class="text-danger">*</span></label>
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
        </div>
    </section>

    <section class="border rounded-2 bg-white p-4">
        <div class="mb-3">
            <h2 class="h5 mb-1">Appointment Period & Status</h2>
            <p class="text-muted mb-0">The system uses the end date to show appointments ending soon and to complete expired active appointments.</p>
        </div>
        <div class="row g-3">
            <div class="col-md-4">
                <label for="appointment_status_id" class="form-label">Appointment Status <span class="text-danger">*</span></label>
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
                <label for="start_date" class="form-label">Start Date <span class="text-danger">*</span></label>
                <input id="start_date" name="start_date" type="date" class="form-control @error('start_date') is-invalid @enderror" value="{{ old('start_date', $appointment->start_date?->format('Y-m-d') ?? now()->toDateString()) }}" required>
                @error('start_date')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-md-4">
                <label for="end_date" class="form-label">End Date <span class="text-danger">*</span></label>
                <input id="end_date" name="end_date" type="date" class="form-control @error('end_date') is-invalid @enderror" value="{{ old('end_date', $appointment->end_date?->format('Y-m-d')) }}" required>
                @error('end_date')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>
    </section>

    <div class="row g-4 align-items-stretch">
        <div class="col-lg-6">
            <section class="border rounded-2 bg-white p-4 h-100">
                <div class="mb-3">
                    <h2 class="h5 mb-1">Line Manager, Reason & Comments</h2>
                    <p class="text-muted mb-0">Record the appointment line manager and why it is being made.</p>
                </div>
                <div class="row g-3">
                    <div class="col-12">
                        <label for="supervisor_name" class="form-label">Line Manager</label>
                        <div class="smart-employee-select" data-smart-employee-select data-role="supervisor" data-url="{{ route('employees.search') }}" data-selected='@json($supervisorOption)'>
                            <input
                                id="supervisor_name"
                                name="supervisor_search"
                                type="search"
                                class="form-control @error('supervisor_employee_id') is-invalid @enderror @error('supervisor_name') is-invalid @enderror"
                                value="{{ $supervisorSearchValue }}"
                                placeholder="Search line manager number, name, email, job title, or location"
                                autocomplete="off"
                                data-smart-input
                            >
                            <input name="supervisor_employee_id" type="hidden" value="{{ old('supervisor_employee_id', $appointment->supervisor_employee_id) }}" data-smart-id>
                            <input name="supervisor_name" type="hidden" value="{{ old('supervisor_name', $appointment->supervisor_name) }}" data-smart-name>
                            <div class="smart-employee-results d-none" data-smart-results role="listbox"></div>
                            <div class="smart-employee-selected mt-2 {{ $supervisorOption ? '' : 'd-none' }}" data-smart-selected>
                                <div class="fw-semibold" data-smart-selected-text>{{ $supervisorOption['text'] ?? '' }}</div>
                                <div class="small text-muted" data-smart-selected-details>{{ $supervisorOption['details'] ?? '' }}</div>
                            </div>
                        </div>
                        @error('supervisor_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        @error('supervisor_employee_id')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12">
                        <label for="reason" class="form-label">Reason / Comment</label>
                        <textarea id="reason" name="reason" rows="7" class="form-control @error('reason') is-invalid @enderror" style="min-height: 190px;">{{ old('reason', $appointment->reason ?: $appointment->comment) }}</textarea>
                        @error('reason')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </section>
        </div>

        <div class="col-lg-6">
            <section class="border rounded-2 bg-white p-4 h-100" data-upload-box>
                <div class="mb-3">
                    <h2 class="h5 mb-1">Supporting Documents</h2>
                    <p class="text-muted mb-0">Attach an appointment letter, approval, or related support document.</p>
                </div>
                <div class="border rounded-2 bg-light p-3">
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
                    <div class="d-none border rounded-2 bg-white p-3 mt-3" data-upload-preview aria-live="polite">
                        <div class="d-flex align-items-start justify-content-between gap-3">
                            <div class="d-flex align-items-start gap-3 min-w-0">
                                <div class="rounded-2 d-inline-flex align-items-center justify-content-center flex-shrink-0" style="width: 40px; height: 40px; background: #eef4ff; color: #2563eb;">
                                    <i class="bi bi-file-earmark-check" aria-hidden="true"></i>
                                </div>
                                <div class="min-w-0">
                                    <div class="d-flex align-items-center gap-2 flex-wrap">
                                        <span class="fw-semibold text-truncate" data-upload-filename></span>
                                        <span class="badge text-bg-success">Attached</span>
                                    </div>
                                    <div class="small text-muted mt-1" data-upload-meta></div>
                                </div>
                            </div>
                            <button type="button" class="btn btn-sm btn-secondary" data-upload-clear>Remove</button>
                        </div>
                    </div>
                    <div class="small text-muted mt-2">PDF, DOC, DOCX, JPG, JPEG, or PNG. Maximum 10 MB.</div>
                </div>
            </section>
        </div>
    </div>

    <div class="border-top pt-4 d-flex flex-column flex-sm-row gap-2 justify-content-end">
        <a href="{{ $appointment->exists ? route('temporary-appointments.show', $appointment) : route('temporary-appointments.index') }}" class="btn btn-secondary btn-md">Cancel</a>
        <button type="submit" class="btn btn-primary btn-md" data-submit-button>Save Appointment</button>
    </div>
</div>

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const form = document.querySelector('[data-temporary-appointment-form]');

            if (!form) return;

            const province = form.querySelector('[data-province-select]');
            const district = form.querySelector('[data-district-select]');
            const facility = form.querySelector('[data-facility-select]');
            const project = form.querySelector('[data-project-select]');
            const department = form.querySelector('[data-department-select]');
            const currentJobTitle = form.querySelector('[data-current-job-title-select]');
            const uploadBox = form.querySelector('[data-upload-box]');
            const uploadInput = form.querySelector('#supporting_document');
            const uploadError = form.querySelector('[data-upload-error]');
            const uploadPreview = form.querySelector('[data-upload-preview]');
            const uploadFilename = form.querySelector('[data-upload-filename]');
            const uploadMeta = form.querySelector('[data-upload-meta]');
            const uploadClear = form.querySelector('[data-upload-clear]');
            const submitButton = form.querySelector('[data-submit-button]');
            const maxUploadSize = 10 * 1024 * 1024;
            const employeePicker = form.querySelector('[data-smart-employee-select][data-role="employee"]');
            const supervisorPicker = form.querySelector('[data-smart-employee-select][data-role="supervisor"]');

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

            function filterDistricts() {
                const provinceId = province.value;
                district.querySelectorAll('option[data-province-id]').forEach(function (option) {
                    const visible = !provinceId || option.dataset.provinceId === provinceId;
                    option.hidden = !visible;
                    option.disabled = !visible;
                });
                if (district.selectedOptions[0]?.disabled) district.value = '';
                filterFacilities();
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

            function applyEmployeeDefaults(employee) {
                if (!employee) return;

                if (!province.disabled) province.value = employee.province_id || '';
                district.value = employee.district_id || '';
                facility.value = employee.facility_id || '';
                project.value = employee.project_id || '';
                department.value = employee.department_id || '';
                currentJobTitle.value = employee.job_title_id || '';
                filterDistricts();
                filterFacilities();
            }

            function setPickerValidity(picker, isValid, message) {
                const input = picker.querySelector('[data-smart-input]');
                input?.classList.toggle('is-invalid', !isValid);
                input?.setCustomValidity(isValid ? '' : message);
            }

            function renderSelected(picker, employee) {
                const selected = picker.querySelector('[data-smart-selected]');
                const selectedText = picker.querySelector('[data-smart-selected-text]');
                const selectedDetails = picker.querySelector('[data-smart-selected-details]');

                if (!selected || !selectedText || !selectedDetails) return;

                if (!employee) {
                    selected.classList.add('d-none');
                    selectedText.textContent = '';
                    selectedDetails.textContent = '';
                    return;
                }

                selectedText.textContent = employee.text || '';
                selectedDetails.textContent = employee.details || '';
                selected.classList.remove('d-none');
            }

            function renderResults(picker, employees) {
                const results = picker.querySelector('[data-smart-results]');

                if (!results) return;

                results.innerHTML = '';

                if (!employees.length) {
                    results.innerHTML = '<div class="px-3 py-2 text-muted small">No employees found.</div>';
                    results.classList.remove('d-none');
                    return;
                }

                employees.forEach(function (employee) {
                    const button = document.createElement('button');
                    button.type = 'button';
                    button.className = 'smart-employee-option';
                    button.innerHTML = `
                        <div class="fw-semibold">${employee.text}</div>
                        <div class="small text-muted">${employee.details || 'No job/location details recorded'}</div>
                        ${employee.email ? `<div class="small text-muted">${employee.email}</div>` : ''}
                    `;
                    button.addEventListener('click', function () {
                        selectEmployee(picker, employee);
                    });
                    results.appendChild(button);
                });

                results.classList.remove('d-none');
            }

            function clearResults(picker) {
                picker.querySelector('[data-smart-results]')?.classList.add('d-none');
            }

            function selectEmployee(picker, employee) {
                const input = picker.querySelector('[data-smart-input]');
                const idInput = picker.querySelector('[data-smart-id]');
                const nameInput = picker.querySelector('[data-smart-name]');

                input.value = employee.text || '';
                idInput.value = employee.id || '';
                picker.dataset.selectedEmployee = JSON.stringify(employee);
                setPickerValidity(picker, true, '');
                renderSelected(picker, employee);
                clearResults(picker);

                if (nameInput) {
                    nameInput.value = employee.name || '';
                }

                if (picker.dataset.role === 'employee') {
                    applyEmployeeDefaults(employee);
                }
            }

            function setupEmployeePicker(picker) {
                if (!picker) return;

                const input = picker.querySelector('[data-smart-input]');
                const idInput = picker.querySelector('[data-smart-id]');
                const nameInput = picker.querySelector('[data-smart-name]');
                const url = picker.dataset.url;
                let abortController = null;
                let searchTimer = null;

                try {
                    const selected = JSON.parse(picker.dataset.selected || 'null');
                    if (selected) picker.dataset.selectedEmployee = JSON.stringify(selected);
                } catch (error) {
                    picker.dataset.selectedEmployee = '';
                }

                input.addEventListener('input', function () {
                    const query = input.value.trim();
                    idInput.value = '';
                    picker.dataset.selectedEmployee = '';
                    renderSelected(picker, null);
                    setPickerValidity(picker, true, '');

                    if (nameInput) {
                        nameInput.value = query;
                    }

                    clearTimeout(searchTimer);

                    if (query.length < 1) {
                        clearResults(picker);
                        return;
                    }

                    searchTimer = setTimeout(function () {
                        if (abortController) abortController.abort();

                        abortController = new AbortController();
                        const results = picker.querySelector('[data-smart-results]');
                        results.innerHTML = '<div class="px-3 py-2 text-muted small">Searching employees...</div>';
                        results.classList.remove('d-none');

                        fetch(`${url}?q=${encodeURIComponent(query)}&limit=10`, {
                            headers: { 'Accept': 'application/json' },
                            signal: abortController.signal,
                        })
                            .then(function (response) {
                                if (!response.ok) throw new Error('Employee search failed.');
                                return response.json();
                            })
                            .then(function (employees) {
                                renderResults(picker, employees);
                            })
                            .catch(function (error) {
                                if (error.name === 'AbortError') return;
                                results.innerHTML = '<div class="px-3 py-2 text-danger small">Unable to search employees right now.</div>';
                                results.classList.remove('d-none');
                            });
                    }, 220);
                });

                input.addEventListener('focus', function () {
                    if (input.value.trim().length >= 1 && !idInput.value) {
                        input.dispatchEvent(new Event('input'));
                    }
                });
            }

            function showUploadPreview(file) {
                if (!uploadPreview || !uploadFilename || !uploadMeta) return;

                uploadError?.classList.add('d-none');
                uploadInput?.classList.remove('is-invalid');
                uploadFilename.textContent = file.name;
                uploadMeta.textContent = `${formatFileSize(file.size)} selected and ready to attach when you save this appointment.`;
                uploadPreview.classList.remove('d-none');
                uploadBox?.classList.add('border-primary');
            }

            function clearUploadPreview() {
                if (uploadInput) {
                    uploadInput.value = '';
                    uploadInput.classList.remove('is-invalid');
                }

                uploadPreview?.classList.add('d-none');
                uploadBox?.classList.remove('border-primary');
                uploadError?.classList.add('d-none');
            }

            uploadInput?.addEventListener('change', function () {
                const file = uploadInput.files[0];
                uploadError?.classList.add('d-none');
                uploadPreview?.classList.add('d-none');
                uploadBox?.classList.remove('border-primary');
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
                showUploadPreview(file);
            });

            uploadClear?.addEventListener('click', clearUploadPreview);
            setupEmployeePicker(employeePicker);
            setupEmployeePicker(supervisorPicker);
            document.addEventListener('click', function (event) {
                document.querySelectorAll('[data-smart-employee-select]').forEach(function (picker) {
                    if (!picker.contains(event.target)) {
                        clearResults(picker);
                    }
                });
            });

            form.addEventListener('submit', function (event) {
                const employeeId = employeePicker?.querySelector('[data-smart-id]');
                const employeeInput = employeePicker?.querySelector('[data-smart-input]');
                const supervisorInput = supervisorPicker?.querySelector('[data-smart-input]');
                const supervisorName = supervisorPicker?.querySelector('[data-smart-name]');

                if (!employeeId?.value) {
                    setPickerValidity(employeePicker, false, 'Please choose an employee from the search results.');
                    event.preventDefault();
                    return;
                }

                if (supervisorInput && supervisorName && !supervisorPicker.querySelector('[data-smart-id]').value) {
                    supervisorName.value = supervisorInput.value.trim();
                }

                if (!submitButton) return;
                submitButton.disabled = true;
                submitButton.innerHTML = '<span class="spinner-border spinner-border-sm" aria-hidden="true"></span><span>Saving...</span>';
            });

            province.addEventListener('change', filterDistricts);
            district.addEventListener('change', filterFacilities);
            filterDistricts();
            filterFacilities();
        });
    </script>
@endpush
