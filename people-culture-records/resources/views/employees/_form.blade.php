@php
    $isOfficer = auth()->user()->hasRole('HR Officer');
@endphp

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
    <div class="col-md-4">
        <label for="date_of_birth" class="form-label">Date of Birth</label>
        <input id="date_of_birth" name="date_of_birth" type="date" class="form-control" value="{{ old('date_of_birth', $employee->date_of_birth?->format('Y-m-d')) }}">
    </div>
    <div class="col-md-4">
        <label for="national_id" class="form-label">National ID</label>
        <input id="national_id" name="national_id" type="text" class="form-control" value="{{ old('national_id', $employee->national_id) }}">
    </div>

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
                <option value="{{ $employmentStatus->id }}" @selected((string) old('employment_status_id', $employee->employment_status_id) === (string) $employmentStatus->id)>{{ $employmentStatus->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-8">
        <label for="supervisor_name" class="form-label">Supervisor Name</label>
        <input id="supervisor_name" name="supervisor_name" type="text" class="form-control" value="{{ old('supervisor_name', $employee->supervisor_name) }}">
    </div>

    <div class="col-12">
        <label for="notes" class="form-label">Notes</label>
        <textarea id="notes" name="notes" rows="4" class="form-control">{{ old('notes', $employee->notes) }}</textarea>
    </div>

    <div class="col-12 d-flex gap-2">
        <button type="submit" class="btn btn-primary">Save Employee</button>
        <a href="{{ route('employees.index') }}" class="btn btn-outline-secondary">Cancel</a>
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
                facility.value = '';
            }
        }

        province.addEventListener('change', filterDistricts);
        district.addEventListener('change', filterFacilities);
        filterDistricts();
    });
</script>
