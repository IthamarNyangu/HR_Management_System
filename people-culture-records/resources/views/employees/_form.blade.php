@php
    $isOfficer = auth()->user()->hasRole('HR Officer');
    $canViewSensitivePersonalData = auth()->user()->can('viewSensitivePersonalData', $employee);
    $supervisorOption = $selectedSupervisorOption ?? null;
    $supervisorSearchValue = old('supervisor_search', $supervisorOption['text'] ?? old('supervisor_name', $employee->supervisor_name));
    $selectedEmploymentStatusId = old('employment_status_id', $employee->employment_status_id ?? $activeEmploymentStatusId ?? null);
    $terminatedStatusIds = collect($terminatedEmploymentStatusIds ?? [])->map(fn ($id) => (string) $id)->all();
    $isTerminatedSelected = in_array((string) $selectedEmploymentStatusId, $terminatedStatusIds, true);
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

<div class="row g-3" data-employee-form>
    <div class="col-md-4">
        <label for="employee_no" class="form-label">Employee Number</label>
        <input id="employee_no" name="employee_no" type="text" class="form-control" value="{{ old('employee_no', $employee->employee_no) }}" required>
    </div>
    <div class="col-md-4">
        <label for="first_name" class="form-label">First Name</label>
        <input id="first_name" name="first_name" type="text" class="form-control" value="{{ old('first_name', $employee->first_name) }}" required>
    </div>
    <div class="col-md-4">
        <label for="last_name" class="form-label">Last Name</label>
        <input id="last_name" name="last_name" type="text" class="form-control" value="{{ old('last_name', $employee->last_name) }}" required>
    </div>

    <div class="col-md-4">
        <label for="gender" class="form-label">Gender</label>
        <select id="gender" name="gender" class="form-select">
            <option value="">Not specified</option>
            @foreach (['Female', 'Male', 'Other'] as $gender)
                <option value="{{ $gender }}" @selected(old('gender', $employee->gender) === $gender)>{{ $gender }}</option>
            @endforeach
        </select>
    </div>
    @if ($canViewSensitivePersonalData)
        <div class="col-md-4">
            <label for="date_of_birth" class="form-label">Date of Birth</label>
            <input id="date_of_birth" name="date_of_birth" type="date" class="form-control" value="{{ old('date_of_birth', $employee->date_of_birth?->format('Y-m-d')) }}">
            <div class="form-text">Encrypted at rest.</div>
        </div>
        <div class="col-md-4">
            <label for="national_id" class="form-label">National ID</label>
            <input id="national_id" name="national_id" type="text" class="form-control" value="{{ old('national_id', $employee->national_id) }}">
            <div class="form-text">Encrypted at rest.</div>
        </div>
    @else
        <div class="col-md-8">
            <div class="border rounded-2 bg-light p-3 h-100">
                <div class="fw-semibold">Sensitive personal data</div>
                <div class="small text-muted">Date of birth and National ID are restricted to Admin and HR Manager.</div>
            </div>
        </div>
    @endif

    <div class="col-md-4">
        <label for="email" class="form-label">Email</label>
        <input id="email" name="email" type="email" class="form-control" value="{{ old('email', $employee->email) }}">
    </div>
    <div class="col-md-4">
        <label for="phone" class="form-label">Phone</label>
        <input id="phone" name="phone" type="text" class="form-control" value="{{ old('phone', $employee->phone) }}">
    </div>
    <div class="col-md-4">
        <label for="hire_date" class="form-label">Hire Date</label>
        <input id="hire_date" name="hire_date" type="date" class="form-control" value="{{ old('hire_date', $employee->hire_date?->format('Y-m-d')) }}">
    </div>

    <div class="col-md-4">
        <label for="province_id" class="form-label">Province</label>
        @if ($isOfficer)
            <input type="hidden" name="province_id" value="{{ auth()->user()->province_id }}">
        @endif
        <select id="province_id" name="{{ $isOfficer ? '_province_display' : 'province_id' }}" class="form-select" data-province-select required @disabled($isOfficer)>
            <option value="">Select province</option>
            @foreach ($provinces as $province)
                <option value="{{ $province->id }}" @selected((string) old('province_id', $employee->province_id) === (string) $province->id)>{{ $province->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4">
        <label for="district_id" class="form-label">District</label>
        <select id="district_id" name="district_id" class="form-select" data-district-select required>
            <option value="">Select district</option>
            @foreach ($districts as $district)
                <option value="{{ $district->id }}" data-province-id="{{ $district->province_id }}" @selected((string) old('district_id', $employee->district_id) === (string) $district->id)>{{ $district->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4">
        <label for="facility_id" class="form-label">Facility</label>
        <select id="facility_id" name="facility_id" class="form-select" data-facility-select>
            <option value="">Select facility</option>
            @foreach ($facilities as $facility)
                <option value="{{ $facility->id }}" data-district-id="{{ $facility->district_id }}" @selected((string) old('facility_id', $employee->facility_id) === (string) $facility->id)>{{ $facility->name }}</option>
            @endforeach
        </select>
    </div>

    <div class="col-md-4">
        <label for="project_id" class="form-label">Project</label>
        <select id="project_id" name="project_id" class="form-select">
            <option value="">Select project</option>
            @foreach ($projects as $project)
                <option value="{{ $project->id }}" @selected((string) old('project_id', $employee->project_id) === (string) $project->id)>{{ $project->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4">
        <label for="department_id" class="form-label">Department</label>
        <select id="department_id" name="department_id" class="form-select">
            <option value="">Select department</option>
            @foreach ($departments as $department)
                <option value="{{ $department->id }}" @selected((string) old('department_id', $employee->department_id) === (string) $department->id)>{{ $department->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4">
        <label for="job_title_id" class="form-label">Job Title</label>
        <select id="job_title_id" name="job_title_id" class="form-select">
            <option value="">Select job title</option>
            @foreach ($jobTitles as $jobTitle)
                <option value="{{ $jobTitle->id }}" @selected((string) old('job_title_id', $employee->job_title_id) === (string) $jobTitle->id)>{{ $jobTitle->name }}</option>
            @endforeach
        </select>
    </div>

    <div class="col-md-4">
        <label for="employment_status_id" class="form-label">Employment Status</label>
        <select id="employment_status_id" name="employment_status_id" class="form-select">
            <option value="">Select status</option>
            @foreach ($employmentStatuses as $employmentStatus)
                <option
                    value="{{ $employmentStatus->id }}"
                    data-terminated="{{ in_array((string) $employmentStatus->id, $terminatedStatusIds, true) ? '1' : '0' }}"
                    @selected((string) $selectedEmploymentStatusId === (string) $employmentStatus->id)
                >
                    {{ $employmentStatus->name }}
                </option>
            @endforeach
        </select>
    </div>
    <div class="col-md-8">
        <label for="supervisor_search" class="form-label">Line Manager</label>
        <div class="smart-employee-select" data-smart-employee-select data-url="{{ route('employees.search') }}" data-selected='@json($supervisorOption)'>
            <input
                id="supervisor_search"
                name="supervisor_search"
                type="search"
                class="form-control @error('supervisor_employee_id') is-invalid @enderror @error('supervisor_name') is-invalid @enderror"
                value="{{ $supervisorSearchValue }}"
                placeholder="Search line manager number, name, email, job title, or location"
                autocomplete="off"
                data-smart-input
            >
            <input name="supervisor_employee_id" type="hidden" value="{{ old('supervisor_employee_id', $employee->supervisor_employee_id) }}" data-smart-id>
            <input name="supervisor_name" type="hidden" value="{{ old('supervisor_name', $employee->supervisor_name) }}" data-smart-name>
            <div class="smart-employee-results d-none" data-smart-results role="listbox"></div>
            <div class="smart-employee-selected mt-2 {{ $supervisorOption ? '' : 'd-none' }}" data-smart-selected>
                <div class="fw-semibold" data-smart-selected-text>{{ $supervisorOption['text'] ?? '' }}</div>
                <div class="small text-muted" data-smart-selected-details>{{ $supervisorOption['details'] ?? '' }}</div>
            </div>
        </div>
        <div class="form-text">Select a line manager from employees where possible. Typed names are kept as text if no employee is selected.</div>
        @error('supervisor_employee_id')
            <div class="invalid-feedback d-block">{{ $message }}</div>
        @enderror
        @error('supervisor_name')
            <div class="invalid-feedback d-block">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-12 {{ $isTerminatedSelected ? '' : 'd-none' }}" data-termination-panel>
        <div class="border rounded-2 bg-light p-3">
            <div class="d-flex flex-column flex-md-row justify-content-between gap-2 mb-3">
                <div>
                    <div class="fw-semibold">Termination Details</div>
                    <div class="small text-muted">Required when employment status is Terminated. This does not archive the employee record.</div>
                </div>
            </div>
            <div class="row g-3">
                <div class="col-md-4">
                    <label for="termination_date" class="form-label">Termination Date</label>
                    <input id="termination_date" name="termination_date" type="date" class="form-control @error('termination_date') is-invalid @enderror" value="{{ old('termination_date', $employee->termination_date?->format('Y-m-d')) }}" data-termination-required>
                    @error('termination_date')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-4">
                    <label for="termination_reason_id" class="form-label">Termination Reason</label>
                    <select id="termination_reason_id" name="termination_reason_id" class="form-select @error('termination_reason_id') is-invalid @enderror" data-termination-required>
                        <option value="">Select reason</option>
                        @foreach ($terminationReasons as $terminationReason)
                            <option value="{{ $terminationReason->id }}" @selected((string) old('termination_reason_id', $employee->termination_reason_id) === (string) $terminationReason->id)>{{ $terminationReason->name }}</option>
                        @endforeach
                    </select>
                    @error('termination_reason_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-4">
                    <label for="termination_comment" class="form-label">Termination Comment</label>
                    <textarea id="termination_comment" name="termination_comment" rows="2" class="form-control @error('termination_comment') is-invalid @enderror">{{ old('termination_comment', $employee->termination_comment) }}</textarea>
                    @error('termination_comment')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>
    </div>

    @if ($canViewSensitivePersonalData)
        <div class="col-12">
            <label for="notes" class="form-label">Notes</label>
            <textarea id="notes" name="notes" rows="4" class="form-control">{{ old('notes', $employee->notes) }}</textarea>
            <div class="form-text">Notes are encrypted at rest. Avoid storing unnecessary sensitive details.</div>
        </div>
    @else
        <div class="col-12">
            <div class="border rounded-2 bg-light p-3">
                <div class="fw-semibold">Notes restricted</div>
                <div class="small text-muted">Employee notes are restricted to Admin and HR Manager.</div>
            </div>
        </div>
    @endif

    <div class="col-12 d-flex gap-2">
        <button type="submit" class="btn btn-primary btn-md">Save Employee</button>
        <a href="{{ route('employees.index') }}" class="btn btn-secondary btn-md">Cancel</a>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const form = document.querySelector('[data-employee-form]');

        if (!form) {
            return;
        }

        const province = form.querySelector('[data-province-select]');
        const district = form.querySelector('[data-district-select]');
        const facility = form.querySelector('[data-facility-select]');
        const supervisorPicker = form.querySelector('[data-smart-employee-select]');
        const employmentStatus = form.querySelector('#employment_status_id');
        const terminationPanel = form.querySelector('[data-termination-panel]');
        const terminationRequiredFields = form.querySelectorAll('[data-termination-required]');

        function filterDistricts() {
            const provinceId = province.value;

            district.querySelectorAll('option[data-province-id]').forEach(function (option) {
                const visible = !provinceId || option.dataset.provinceId === provinceId;
                option.hidden = !visible;
                option.disabled = !visible;
            });

            if (district.selectedOptions[0]?.disabled) {
                district.value = '';
            }

            filterFacilities();
        }

        function filterFacilities() {
            const districtId = district.value;

            facility.querySelectorAll('option[data-district-id]').forEach(function (option) {
                const visible = !districtId || option.dataset.districtId === districtId;
                option.hidden = !visible;
                option.disabled = !visible;
            });

            if (facility.selectedOptions[0]?.disabled) {
                window.setSearchableFacilityValue?.(facility, '');
            }
        }

        province.addEventListener('change', filterDistricts);
        district.addEventListener('change', filterFacilities);
        filterDistricts();

        function toggleTerminationPanel() {
            if (!employmentStatus || !terminationPanel) return;

            const selectedOption = employmentStatus.selectedOptions[0];
            const isTerminated = selectedOption?.dataset.terminated === '1';

            terminationPanel.classList.toggle('d-none', !isTerminated);
            terminationRequiredFields.forEach(function (field) {
                field.required = isTerminated;
            });
        }

        employmentStatus?.addEventListener('change', toggleTerminationPanel);
        toggleTerminationPanel();

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
                    const input = picker.querySelector('[data-smart-input]');
                    const idInput = picker.querySelector('[data-smart-id]');
                    const nameInput = picker.querySelector('[data-smart-name]');

                    input.value = employee.text || '';
                    idInput.value = employee.id || '';
                    if (nameInput) nameInput.value = employee.name || '';
                    renderSelected(picker, employee);
                    results.classList.add('d-none');
                });
                results.appendChild(button);
            });

            results.classList.remove('d-none');
        }

        function setupSupervisorPicker(picker) {
            if (!picker) return;

            const input = picker.querySelector('[data-smart-input]');
            const idInput = picker.querySelector('[data-smart-id]');
            const nameInput = picker.querySelector('[data-smart-name]');
            const url = picker.dataset.url;
            let abortController = null;
            let searchTimer = null;

            input.addEventListener('input', function () {
                const query = input.value.trim();
                idInput.value = '';
                if (nameInput) nameInput.value = query;
                renderSelected(picker, null);
                clearTimeout(searchTimer);

                if (query.length < 1) {
                    picker.querySelector('[data-smart-results]')?.classList.add('d-none');
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

            document.addEventListener('click', function (event) {
                if (!picker.contains(event.target)) {
                    picker.querySelector('[data-smart-results]')?.classList.add('d-none');
                }
            });
        }

        setupSupervisorPicker(supervisorPicker);
    });
</script>
