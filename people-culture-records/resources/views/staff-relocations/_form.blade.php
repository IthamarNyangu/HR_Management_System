@php
    $isOfficer = auth()->user()->hasRole('HR Officer');
@endphp

<div class="row g-3" data-relocation-form>
    @if ($relocation->exists)
        <div class="col-md-4">
            <label class="form-label">Reference Number</label>
            <input type="text" class="form-control" value="{{ $relocation->reference_no }}" disabled>
        </div>
    @endif

    <div class="col-md-{{ $relocation->exists ? '8' : '6' }}">
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
                    @selected((string) old('employee_id', $relocation->employee_id) === (string) $employee->id)
                >
                    {{ $employee->display_name }}{{ $employee->province ? ' - '.$employee->province->name : '' }}
                </option>
            @endforeach
        </select>
        @if ($isOfficer)
            <div class="form-text">Employees from your assigned province appear first. Change the from province if you are recording an incoming relocation.</div>
        @endif
        @error('employee_id')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-{{ $relocation->exists ? '4' : '6' }}">
        <label for="relocation_reason_id" class="form-label">Relocation Reason</label>
        <select id="relocation_reason_id" name="relocation_reason_id" class="form-select @error('relocation_reason_id') is-invalid @enderror">
            <option value="">Select relocation reason</option>
            @foreach ($relocationReasons as $reason)
                <option value="{{ $reason->id }}" @selected((string) old('relocation_reason_id', $relocation->relocation_reason_id) === (string) $reason->id)>{{ $reason->name }}</option>
            @endforeach
        </select>
        @error('relocation_reason_id')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-lg-6">
        <section class="border rounded-2 p-3 h-100">
            <h2 class="h6 mb-3">From Location</h2>
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="from_province_id" class="form-label">From Province</label>
                    <select id="from_province_id" name="from_province_id" class="form-select @error('from_province_id') is-invalid @enderror" data-from-province-select required>
                        <option value="">Select province</option>
                        @foreach ($provinces as $province)
                            <option value="{{ $province->id }}" @selected((string) old('from_province_id', $relocation->from_province_id) === (string) $province->id)>{{ $province->name }}</option>
                        @endforeach
                    </select>
                    @error('from_province_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6">
                    <label for="from_district_id" class="form-label">From District</label>
                    <select id="from_district_id" name="from_district_id" class="form-select @error('from_district_id') is-invalid @enderror" data-from-district-select required>
                        <option value="">Select district</option>
                        @foreach ($districts as $district)
                            <option value="{{ $district->id }}" data-province-id="{{ $district->province_id }}" @selected((string) old('from_district_id', $relocation->from_district_id) === (string) $district->id)>{{ $district->name }}</option>
                        @endforeach
                    </select>
                    @error('from_district_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-12">
                    <label for="from_facility_id" class="form-label">From Facility</label>
                    <select id="from_facility_id" name="from_facility_id" class="form-select @error('from_facility_id') is-invalid @enderror" data-from-facility-select>
                        <option value="">Select facility</option>
                        @foreach ($facilities as $facility)
                            <option value="{{ $facility->id }}" data-district-id="{{ $facility->district_id }}" @selected((string) old('from_facility_id', $relocation->from_facility_id) === (string) $facility->id)>{{ $facility->name }}</option>
                        @endforeach
                    </select>
                    @error('from_facility_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </section>
    </div>

    <div class="col-lg-6">
        <section class="border rounded-2 p-3 h-100">
            <h2 class="h6 mb-3">To Location</h2>
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="to_province_id" class="form-label">To Province</label>
                    <select id="to_province_id" name="to_province_id" class="form-select @error('to_province_id') is-invalid @enderror" data-to-province-select required>
                        <option value="">Select province</option>
                        @foreach ($provinces as $province)
                            <option value="{{ $province->id }}" @selected((string) old('to_province_id', $relocation->to_province_id) === (string) $province->id)>{{ $province->name }}</option>
                        @endforeach
                    </select>
                    @error('to_province_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6">
                    <label for="to_district_id" class="form-label">To District</label>
                    <select id="to_district_id" name="to_district_id" class="form-select @error('to_district_id') is-invalid @enderror" data-to-district-select required>
                        <option value="">Select district</option>
                        @foreach ($districts as $district)
                            <option value="{{ $district->id }}" data-province-id="{{ $district->province_id }}" @selected((string) old('to_district_id', $relocation->to_district_id) === (string) $district->id)>{{ $district->name }}</option>
                        @endforeach
                    </select>
                    @error('to_district_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-12">
                    <label for="to_facility_id" class="form-label">To Facility</label>
                    <select id="to_facility_id" name="to_facility_id" class="form-select @error('to_facility_id') is-invalid @enderror" data-to-facility-select>
                        <option value="">Select facility</option>
                        @foreach ($facilities as $facility)
                            <option value="{{ $facility->id }}" data-district-id="{{ $facility->district_id }}" @selected((string) old('to_facility_id', $relocation->to_facility_id) === (string) $facility->id)>{{ $facility->name }}</option>
                        @endforeach
                    </select>
                    @error('to_facility_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </section>
    </div>

    <div class="col-md-4">
        <label for="job_title_id" class="form-label">Job Title</label>
        <select id="job_title_id" name="job_title_id" class="form-select @error('job_title_id') is-invalid @enderror" data-job-title-select>
            <option value="">Select job title</option>
            @foreach ($jobTitles as $jobTitle)
                <option value="{{ $jobTitle->id }}" @selected((string) old('job_title_id', $relocation->job_title_id) === (string) $jobTitle->id)>{{ $jobTitle->name }}</option>
            @endforeach
        </select>
        @error('job_title_id')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-4">
        <label for="project_id" class="form-label">Project</label>
        <select id="project_id" name="project_id" class="form-select @error('project_id') is-invalid @enderror" data-project-select>
            <option value="">Select project</option>
            @foreach ($projects as $project)
                <option value="{{ $project->id }}" @selected((string) old('project_id', $relocation->project_id) === (string) $project->id)>{{ $project->name }}</option>
            @endforeach
        </select>
        @error('project_id')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-4">
        <label for="department_id" class="form-label">Department</label>
        <select id="department_id" name="department_id" class="form-select @error('department_id') is-invalid @enderror" data-department-select>
            <option value="">Select department</option>
            @foreach ($departments as $department)
                <option value="{{ $department->id }}" @selected((string) old('department_id', $relocation->department_id) === (string) $department->id)>{{ $department->name }}</option>
            @endforeach
        </select>
        @error('department_id')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-4">
        <label for="effective_date" class="form-label">Effective Date</label>
        <input id="effective_date" name="effective_date" type="date" class="form-control @error('effective_date') is-invalid @enderror" value="{{ old('effective_date', $relocation->effective_date?->format('Y-m-d') ?? now()->toDateString()) }}" required>
        @error('effective_date')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-4">
        <label for="relocation_amount" class="form-label">Relocation Amount</label>
        <input id="relocation_amount" name="relocation_amount" type="number" step="0.01" min="0" class="form-control @error('relocation_amount') is-invalid @enderror" value="{{ old('relocation_amount', $relocation->relocation_amount) }}">
        @error('relocation_amount')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-4">
        <div class="form-check mt-md-4 pt-md-2">
            <input id="update_employee_location" name="update_employee_location" type="checkbox" value="1" class="form-check-input" @checked(old('update_employee_location', $relocation->update_employee_location))>
            <label for="update_employee_location" class="form-check-label">Update employee current location now</label>
            <div class="form-text">If checked, the employee profile will move to the selected destination after this record is saved.</div>
        </div>
    </div>

    <div class="col-lg-6">
        <label for="comment" class="form-label">Comment</label>
        <textarea id="comment" name="comment" rows="5" class="form-control @error('comment') is-invalid @enderror">{{ old('comment', $relocation->comment) }}</textarea>
        @error('comment')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-lg-6">
        <div class="border rounded-2 bg-light p-3" data-upload-box>
            <div class="d-flex align-items-center gap-2 mb-3">
                <i class="bi bi-paperclip text-primary" aria-hidden="true"></i>
                <div class="fw-semibold">Relocation Document</div>
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
        <button type="submit" class="btn btn-primary btn-md" data-submit-button>Save Relocation</button>
        <a href="{{ $relocation->exists ? route('staff-relocations.show', $relocation) : route('staff-relocations.index') }}" class="btn btn-secondary btn-md">Cancel</a>
    </div>
</div>

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const form = document.querySelector('[data-relocation-form]');

            if (!form) {
                return;
            }

            const employee = form.querySelector('[data-employee-select]');
            const fromProvince = form.querySelector('[data-from-province-select]');
            const fromDistrict = form.querySelector('[data-from-district-select]');
            const fromFacility = form.querySelector('[data-from-facility-select]');
            const toProvince = form.querySelector('[data-to-province-select]');
            const toDistrict = form.querySelector('[data-to-district-select]');
            const toFacility = form.querySelector('[data-to-facility-select]');
            const jobTitle = form.querySelector('[data-job-title-select]');
            const project = form.querySelector('[data-project-select]');
            const department = form.querySelector('[data-department-select]');
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

            function filterSelectByParent(select, parentValue, dataKey) {
                select.querySelectorAll(`option[${dataKey}]`).forEach(function (option) {
                    const visible = !parentValue || option.getAttribute(dataKey) === parentValue;
                    option.hidden = !visible;
                    option.disabled = !visible;
                });

                if (select.selectedOptions[0]?.disabled) {
                    select.value = '';
                }
            }

            function filterFromDistricts() {
                filterSelectByParent(fromDistrict, fromProvince.value, 'data-province-id');
                filterFromFacilities();
                filterEmployees();
            }

            function filterFromFacilities() {
                filterSelectByParent(fromFacility, fromDistrict.value, 'data-district-id');
            }

            function filterToDistricts() {
                filterSelectByParent(toDistrict, toProvince.value, 'data-province-id');
                filterToFacilities();
            }

            function filterToFacilities() {
                filterSelectByParent(toFacility, toDistrict.value, 'data-district-id');
            }

            function filterEmployees() {
                const provinceId = fromProvince.value;

                employee.querySelectorAll('option[data-province-id]').forEach(function (option) {
                    const visible = !provinceId || option.dataset.provinceId === provinceId;
                    option.hidden = !visible;
                    option.disabled = !visible;
                });

                if (employee.selectedOptions[0]?.disabled) {
                    employee.value = '';
                }
            }

            function applyEmployeeDefaults() {
                const option = employee.selectedOptions[0];

                if (!option || !option.dataset.provinceId) {
                    return;
                }

                fromProvince.value = option.dataset.provinceId || '';
                fromDistrict.value = option.dataset.districtId || '';
                fromFacility.value = option.dataset.facilityId || '';
                project.value = option.dataset.projectId || '';
                department.value = option.dataset.departmentId || '';
                jobTitle.value = option.dataset.jobTitleId || '';

                filterFromDistricts();
                filterFromFacilities();
            }

            uploadInput?.addEventListener('change', function () {
                const file = uploadInput.files[0];

                uploadError?.classList.add('d-none');
                uploadReady?.classList.add('d-none');
                uploadInput.classList.remove('is-invalid');

                if (!file) {
                    return;
                }

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
                if (!submitButton) {
                    return;
                }

                submitButton.disabled = true;
                submitButton.innerHTML = '<span class="spinner-border spinner-border-sm" aria-hidden="true"></span><span>Saving...</span>';
            });

            fromProvince.addEventListener('change', filterFromDistricts);
            fromDistrict.addEventListener('change', filterFromFacilities);
            toProvince.addEventListener('change', filterToDistricts);
            toDistrict.addEventListener('change', filterToFacilities);
            employee.addEventListener('change', applyEmployeeDefaults);

            filterFromDistricts();
            filterFromFacilities();
            filterToDistricts();
            filterToFacilities();
        });
    </script>
@endpush
