@php
    $isOfficer = auth()->user()->hasRole('HR Officer');
@endphp

<div class="row g-3" data-disciplinary-case-form>
    @if ($case->exists)
        <div class="col-md-4">
            <label class="form-label">Reference Number</label>
            <input type="text" class="form-control" value="{{ $case->reference_no }}" disabled>
        </div>
    @endif

    <input type="hidden" name="case_status_id" value="{{ old('case_status_id', $case->case_status_id ?: $draftStatusId) }}">

    <div class="col-md-{{ $case->exists ? '8' : '6' }}">
        <label for="employee_id" class="form-label">Employee</label>
        <select id="employee_id" name="employee_id" class="form-select @error('employee_id') is-invalid @enderror" data-employee-select required>
            <option value="">Select employee</option>
            @foreach ($employees as $employee)
                <option value="{{ $employee->id }}" data-province-id="{{ $employee->province_id }}" data-district-id="{{ $employee->district_id }}" data-facility-id="{{ $employee->facility_id }}" data-project-id="{{ $employee->project_id }}" @selected((string) old('employee_id', $case->employee_id) === (string) $employee->id)>
                    {{ $employee->display_name }}
                </option>
            @endforeach
        </select>
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
        <label for="supervisor_name" class="form-label">Supervisor Name</label>
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

    <div class="col-12">
        <label for="nature_of_offence" class="form-label">Nature of Offence</label>
        <textarea id="nature_of_offence" name="nature_of_offence" rows="4" class="form-control @error('nature_of_offence') is-invalid @enderror" required>{{ old('nature_of_offence', $case->nature_of_offence) }}</textarea>
        @error('nature_of_offence')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-12">
        <label for="comment" class="form-label">Comment</label>
        <textarea id="comment" name="comment" rows="3" class="form-control @error('comment') is-invalid @enderror">{{ old('comment', $case->comment) }}</textarea>
        @error('comment')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-12 d-flex gap-2">
        <button type="submit" class="btn btn-primary btn-md">Save Case</button>
        <a href="{{ $case->exists ? route('disciplinary-cases.show', $case) : route('disciplinary-cases.index') }}" class="btn btn-secondary btn-md">Cancel</a>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const form = document.querySelector('[data-disciplinary-case-form]');

        if (!form) {
            return;
        }

        const employee = form.querySelector('[data-employee-select]');
        const province = form.querySelector('[data-province-select]');
        const district = form.querySelector('[data-district-select]');
        const facility = form.querySelector('[data-facility-select]');
        const project = form.querySelector('[data-project-select]');

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

            if (project && option.dataset.projectId) {
                project.value = option.dataset.projectId;
            }

            filterDistricts();
            filterFacilities();
        }

        province.addEventListener('change', filterDistricts);
        district.addEventListener('change', filterFacilities);
        employee.addEventListener('change', applyEmployeeDefaults);
        filterDistricts();
        filterFacilities();
    });
</script>
