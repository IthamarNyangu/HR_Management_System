@php
    $isOfficer = auth()->user()->hasRole('HR Officer');
    $employeeOption = $selectedEmployeeOption ?? null;
    $employeeSearchValue = old('employee_search', $employeeOption['text'] ?? '');
@endphp

@include('partials.smart-employee-picker-assets')

<div class="row g-3" data-disciplinary-case-form>
    @if ($case->exists)
        <div class="col-md-4">
            <label class="form-label">Reference Number</label>
            <input type="text" class="form-control" value="{{ $case->reference_no }}" disabled>
        </div>
    @endif

    <input type="hidden" name="case_status_id" value="{{ old('case_status_id', $case->case_status_id ?: $draftStatusId) }}">

    <div class="col-md-{{ $case->exists ? '8' : '6' }}">
        <label for="employee_search" class="form-label">Employee</label>
        <div class="smart-employee-select" data-smart-employee-select data-url="{{ route('employees.search') }}" data-selected='@json($employeeOption)'>
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
            <input id="employee_id" name="employee_id" type="hidden" value="{{ old('employee_id', $case->employee_id) }}" data-smart-id>
            <div class="smart-employee-results d-none" data-smart-results role="listbox"></div>
            <div class="smart-employee-selected mt-2 {{ $employeeOption ? '' : 'd-none' }}" data-smart-selected>
                <div class="fw-semibold" data-smart-selected-text>{{ $employeeOption['text'] ?? '' }}</div>
                <div class="small text-muted" data-smart-selected-details>{{ $employeeOption['details'] ?? '' }}</div>
            </div>
        </div>
        <div class="form-text">Start typing to find the employee, then choose the correct staff member from the list.</div>
        @error('employee_id')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-{{ $case->exists ? '4' : '6' }}">
        <label for="project_id" class="form-label">Project</label>
        <select id="project_id" name="project_id" class="form-select @error('project_id') is-invalid @enderror" data-project-select>
            <option value="">Select project</option>
            @foreach ($projects as $project)
                <option value="{{ $project->id }}" @selected((string) old('project_id', $case->project_id) === (string) $project->id)>{{ $project->name }}</option>
            @endforeach
        </select>
        @error('project_id')
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
                <option value="{{ $province->id }}" @selected((string) old('province_id', $case->province_id) === (string) $province->id)>{{ $province->name }}</option>
            @endforeach
        </select>
        @error('province_id')
            <div class="invalid-feedback d-block">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-4">
        <label for="district_id" class="form-label">District</label>
        <select id="district_id" name="district_id" class="form-select @error('district_id') is-invalid @enderror" data-district-select required>
            <option value="">Select district</option>
            @foreach ($districts as $district)
                <option value="{{ $district->id }}" data-province-id="{{ $district->province_id }}" @selected((string) old('district_id', $case->district_id) === (string) $district->id)>{{ $district->name }}</option>
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
                <option value="{{ $facility->id }}" data-district-id="{{ $facility->district_id }}" @selected((string) old('facility_id', $case->facility_id) === (string) $facility->id)>{{ $facility->name }}</option>
            @endforeach
        </select>
        @error('facility_id')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-4">
        <label for="offence_category_id" class="form-label">Offence Category</label>
        <select id="offence_category_id" name="offence_category_id" class="form-select @error('offence_category_id') is-invalid @enderror">
            <option value="">Select category</option>
            @foreach ($offenceCategories as $offenceCategory)
                <option value="{{ $offenceCategory->id }}" @selected((string) old('offence_category_id', $case->offence_category_id) === (string) $offenceCategory->id)>{{ $offenceCategory->name }}</option>
            @endforeach
        </select>
        @error('offence_category_id')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-4">
        <label for="penalty_type_id" class="form-label">Penalty Type</label>
        <select id="penalty_type_id" name="penalty_type_id" class="form-select @error('penalty_type_id') is-invalid @enderror">
            <option value="">Select penalty</option>
            @foreach ($penaltyTypes as $penaltyType)
                <option value="{{ $penaltyType->id }}" @selected((string) old('penalty_type_id', $case->penalty_type_id) === (string) $penaltyType->id)>{{ $penaltyType->name }}</option>
            @endforeach
        </select>
        @error('penalty_type_id')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-4">
        <label for="supervisor_name" class="form-label">Line Manager Name</label>
        <input id="supervisor_name" name="supervisor_name" type="text" class="form-control @error('supervisor_name') is-invalid @enderror" value="{{ old('supervisor_name', $case->supervisor_name) }}">
        @error('supervisor_name')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-6">
        <label for="effective_date" class="form-label">Effective Date</label>
        <input id="effective_date" name="effective_date" type="date" class="form-control @error('effective_date') is-invalid @enderror" value="{{ old('effective_date', $case->effective_date?->format('Y-m-d')) }}" required>
        @error('effective_date')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-6">
        <label for="expiry_date" class="form-label">Expiry Date</label>
        <input id="expiry_date" name="expiry_date" type="date" class="form-control @error('expiry_date') is-invalid @enderror" value="{{ old('expiry_date', $case->expiry_date?->format('Y-m-d')) }}">
        @error('expiry_date')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-lg-6">
        <label for="nature_of_offence" class="form-label">Nature of Offence</label>
        <textarea id="nature_of_offence" name="nature_of_offence" rows="5" class="form-control @error('nature_of_offence') is-invalid @enderror" required>{{ old('nature_of_offence', $case->nature_of_offence) }}</textarea>
        @error('nature_of_offence')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror

        @unless ($case->exists)
            <div class="border rounded-2 bg-light p-3 mt-3" data-upload-box>
                <div class="d-flex align-items-center gap-2 mb-3">
                    <i class="bi bi-paperclip text-primary" aria-hidden="true"></i>
                    <div class="fw-semibold">Supporting Document</div>
                </div>

                <div>
                    <label for="supporting_document" class="form-label">Upload File</label>
                    <input id="supporting_document" name="supporting_document" type="file" class="form-control @error('supporting_document') is-invalid @enderror" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                    @error('supporting_document')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

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
        @endunless
    </div>

    <div class="col-lg-6">
        <label for="comment" class="form-label">Comment</label>
        <textarea id="comment" name="comment" rows="5" class="form-control @error('comment') is-invalid @enderror">{{ old('comment', $case->comment) }}</textarea>
        @error('comment')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-12 d-flex gap-2">
        <button type="submit" class="btn btn-primary btn-md" data-submit-button>Save Case</button>
        <a href="{{ $case->exists ? route('disciplinary-cases.show', $case) : route('disciplinary-cases.index') }}" class="btn btn-secondary btn-md">Cancel</a>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const form = document.querySelector('[data-disciplinary-case-form]');

        if (!form) {
            return;
        }

        const employeePicker = form.querySelector('[data-smart-employee-select]');
        const province = form.querySelector('[data-province-select]');
        const district = form.querySelector('[data-district-select]');
        const facility = form.querySelector('[data-facility-select]');
        const project = form.querySelector('[data-project-select]');
        const uploadBox = form.querySelector('[data-upload-box]');
        const uploadInput = form.querySelector('#supporting_document');
        const uploadPreview = form.querySelector('[data-upload-preview]');
        const uploadFilename = form.querySelector('[data-upload-filename]');
        const uploadMeta = form.querySelector('[data-upload-meta]');
        const uploadClear = form.querySelector('[data-upload-clear]');
        const uploadError = form.querySelector('[data-upload-error]');
        const submitButton = form.querySelector('[data-submit-button]');
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

        function showUploadPreview(file) {
            if (!uploadPreview || !uploadFilename || !uploadMeta) {
                return;
            }

            uploadError?.classList.add('d-none');
            uploadInput?.classList.remove('is-invalid');
            uploadFilename.textContent = file.name;
            uploadMeta.textContent = `${formatFileSize(file.size)} selected and ready to attach when you save this case.`;
            uploadPreview.classList.remove('d-none');
            uploadBox?.classList.add('border-primary');
        }

        function showUploadError(file) {
            if (uploadInput) {
                uploadInput.value = '';
                uploadInput.classList.add('is-invalid');
            }

            uploadPreview?.classList.add('d-none');
            uploadBox?.classList.remove('border-primary');

            if (uploadError) {
                uploadError.textContent = `${file.name} is ${formatFileSize(file.size)}. Please choose a file smaller than 10 MB.`;
                uploadError.classList.remove('d-none');
            }
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

        function applyEmployeeDefaults(employee) {
            if (!employee || !employee.province_id) {
                return;
            }

            if (!province.disabled) {
                province.value = employee.province_id || '';
            }

            district.value = employee.district_id || '';
            window.setSearchableFacilityValue?.(facility, employee.facility_id || '');

            if (project && employee.project_id) {
                project.value = employee.project_id;
            }

            filterDistricts();
            filterFacilities();
        }

        province.addEventListener('change', filterDistricts);
        district.addEventListener('change', filterFacilities);
        const employeePickerApi = window.setupSmartEmployeePicker?.(employeePicker, {
            provinceId: () => province?.value || '',
            onSelect: applyEmployeeDefaults,
        });
        uploadInput?.addEventListener('change', function () {
            const file = uploadInput.files[0];

            if (file) {
                if (file.size > maxUploadSize) {
                    showUploadError(file);
                    return;
                }

                showUploadPreview(file);
            } else {
                clearUploadPreview();
            }
        });
        uploadClear?.addEventListener('click', clearUploadPreview);
        form.addEventListener('submit', function (event) {
            if (employeePickerApi && !employeePickerApi.requireSelection()) {
                event.preventDefault();
                return;
            }

            if (!submitButton) {
                return;
            }

            submitButton.disabled = true;
            submitButton.innerHTML = '<span class="spinner-border spinner-border-sm" aria-hidden="true"></span><span>Saving...</span>';
        });
        filterDistricts();
        filterFacilities();
    });
</script>
