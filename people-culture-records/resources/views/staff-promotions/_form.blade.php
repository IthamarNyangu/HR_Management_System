@php
    $isOfficer = auth()->user()->hasRole('HR Officer');
@endphp

<div class="row g-3" data-promotion-form>
    @if ($promotion->exists)
        <div class="col-md-4">
            <label class="form-label">Reference Number</label>
            <input type="text" class="form-control" value="{{ $promotion->reference_no }}" disabled>
        </div>
    @endif

    <div class="col-md-{{ $promotion->exists ? '8' : '6' }}">
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
                    @selected((string) old('employee_id', $promotion->employee_id) === (string) $employee->id)
                >
                    {{ $employee->display_name }}
                </option>
            @endforeach
        </select>
        @error('employee_id')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-{{ $promotion->exists ? '4' : '6' }}">
        <label for="promotion_type_id" class="form-label">Promotion Type</label>
        <select id="promotion_type_id" name="promotion_type_id" class="form-select @error('promotion_type_id') is-invalid @enderror">
            <option value="">Select promotion type</option>
            @foreach ($promotionTypes as $promotionType)
                <option value="{{ $promotionType->id }}" @selected((string) old('promotion_type_id', $promotion->promotion_type_id) === (string) $promotionType->id)>{{ $promotionType->name }}</option>
            @endforeach
        </select>
        @error('promotion_type_id')
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
                <option value="{{ $province->id }}" @selected((string) old('province_id', $promotion->province_id) === (string) $province->id)>{{ $province->name }}</option>
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
                <option value="{{ $district->id }}" data-province-id="{{ $district->province_id }}" @selected((string) old('district_id', $promotion->district_id) === (string) $district->id)>{{ $district->name }}</option>
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
                <option value="{{ $facility->id }}" data-district-id="{{ $facility->district_id }}" @selected((string) old('facility_id', $promotion->facility_id) === (string) $facility->id)>{{ $facility->name }}</option>
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
                <option value="{{ $project->id }}" @selected((string) old('project_id', $promotion->project_id) === (string) $project->id)>{{ $project->name }}</option>
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
                <option value="{{ $department->id }}" @selected((string) old('department_id', $promotion->department_id) === (string) $department->id)>{{ $department->name }}</option>
            @endforeach
        </select>
        @error('department_id')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-6">
        <label for="old_job_title_id" class="form-label">Old Job Title</label>
        <select id="old_job_title_id" name="old_job_title_id" class="form-select @error('old_job_title_id') is-invalid @enderror" data-old-job-title-select>
            <option value="">Select old job title</option>
            @foreach ($jobTitles as $jobTitle)
                <option value="{{ $jobTitle->id }}" @selected((string) old('old_job_title_id', $promotion->old_job_title_id) === (string) $jobTitle->id)>{{ $jobTitle->name }}</option>
            @endforeach
        </select>
        @error('old_job_title_id')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-6">
        <label for="new_job_title_id" class="form-label">New Job Title</label>
        <select id="new_job_title_id" name="new_job_title_id" class="form-select @error('new_job_title_id') is-invalid @enderror" required>
            <option value="">Select new job title</option>
            @foreach ($jobTitles as $jobTitle)
                <option value="{{ $jobTitle->id }}" @selected((string) old('new_job_title_id', $promotion->new_job_title_id) === (string) $jobTitle->id)>{{ $jobTitle->name }}</option>
            @endforeach
        </select>
        @error('new_job_title_id')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-6">
        <label for="promotion_date" class="form-label">Promotion Date</label>
        <input id="promotion_date" name="promotion_date" type="date" class="form-control @error('promotion_date') is-invalid @enderror" value="{{ old('promotion_date', $promotion->promotion_date?->format('Y-m-d') ?? now()->toDateString()) }}" required>
        @error('promotion_date')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-6">
        <label for="effective_date" class="form-label">Effective Date</label>
        <input id="effective_date" name="effective_date" type="date" class="form-control @error('effective_date') is-invalid @enderror" value="{{ old('effective_date', $promotion->effective_date?->format('Y-m-d')) }}">
        <div class="form-text">If this date is today or earlier, the employee profile will update automatically. If it is in the future, the new title will be applied automatically on that date.</div>
        @error('effective_date')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-lg-6">
        <label for="comment" class="form-label">Comment</label>
        <textarea id="comment" name="comment" rows="5" class="form-control @error('comment') is-invalid @enderror">{{ old('comment', $promotion->comment) }}</textarea>
        @error('comment')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-lg-6">
        

        <div class="border rounded-2 bg-light p-3" data-upload-box>
            <div class="d-flex align-items-center gap-2 mb-3">
                <i class="bi bi-paperclip text-primary" aria-hidden="true"></i>
                <div class="fw-semibold">Promotion Document</div>
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
        <button type="submit" class="btn btn-primary btn-md" data-submit-button>Save Promotion</button>
        <a href="{{ $promotion->exists ? route('staff-promotions.show', $promotion) : route('staff-promotions.index') }}" class="btn btn-secondary btn-md">Cancel</a>
    </div>
</div>

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const form = document.querySelector('[data-promotion-form]');

            if (!form) {
                return;
            }

            const employee = form.querySelector('[data-employee-select]');
            const province = form.querySelector('[data-province-select]');
            const district = form.querySelector('[data-district-select]');
            const facility = form.querySelector('[data-facility-select]');
            const project = form.querySelector('[data-project-select]');
            const department = form.querySelector('[data-department-select]');
            const oldJobTitle = form.querySelector('[data-old-job-title-select]');
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

                if (employee.selectedOptions[0]?.disabled) {
                    employee.value = '';
                }
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
                filterEmployees();
            }

            function filterFacilities() {
                const districtId = district.value;

                facility.querySelectorAll('option[data-district-id]').forEach(function (option) {
                    const visible = !districtId || option.dataset.districtId === districtId;
                    option.hidden = !visible;
                    option.disabled = !visible;
                });

                if (facility.selectedOptions[0]?.disabled) {
                    facility.value = '';
                }
            }

            function applyEmployeeDefaults() {
                const option = employee.selectedOptions[0];

                if (!option || !option.dataset.provinceId) {
                    return;
                }

                if (!province.disabled) {
                    province.value = option.dataset.provinceId || '';
                }

                district.value = option.dataset.districtId || '';
                facility.value = option.dataset.facilityId || '';
                project.value = option.dataset.projectId || '';
                department.value = option.dataset.departmentId || '';
                oldJobTitle.value = option.dataset.jobTitleId || '';

                filterDistricts();
                filterFacilities();
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

            province.addEventListener('change', filterDistricts);
            district.addEventListener('change', filterFacilities);
            employee.addEventListener('change', applyEmployeeDefaults);
            filterDistricts();
            filterFacilities();
        });
    </script>
@endpush
