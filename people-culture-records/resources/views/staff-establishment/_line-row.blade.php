@php
    $disabled = $isTemplate ?? false;
@endphp

<div class="border rounded-2 bg-light p-3" data-establishment-line>
    <input type="hidden" name="lines[{{ $index }}][id]" value="{{ data_get($line, 'id') }}" @disabled($disabled)>

    <div class="row g-3 align-items-end">
        <div class="col-md-6 col-xl-3">
            <label class="form-label">Job Title <span class="text-danger">*</span></label>
            <select name="lines[{{ $index }}][job_title_id]" class="form-select @error("lines.$index.job_title_id") is-invalid @enderror" data-line-job-title @disabled($disabled)>
                <option value="">Select job title</option>
                @foreach ($jobTitles as $jobTitle)
                    <option value="{{ $jobTitle->id }}" @selected((string) data_get($line, 'job_title_id') === (string) $jobTitle->id)>{{ $jobTitle->name }}</option>
                @endforeach
            </select>
            @error("lines.$index.job_title_id")<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-6 col-xl-2">
            <label class="form-label">Province</label>
            <select name="lines[{{ $index }}][province_id]" class="form-select @error("lines.$index.province_id") is-invalid @enderror" data-line-province @disabled($disabled)>
                <option value="">Organisation-wide</option>
                @foreach ($provinces as $province)
                    <option value="{{ $province->id }}" @selected((string) data_get($line, 'province_id') === (string) $province->id)>{{ $province->name }}</option>
                @endforeach
            </select>
            @error("lines.$index.province_id")<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-6 col-xl-2">
            <label class="form-label">District</label>
            <select name="lines[{{ $index }}][district_id]" class="form-select @error("lines.$index.district_id") is-invalid @enderror" data-line-district @disabled($disabled)>
                <option value="">All districts</option>
                @foreach ($districts as $district)
                    <option value="{{ $district->id }}" data-province-id="{{ $district->province_id }}" @selected((string) data_get($line, 'district_id') === (string) $district->id)>{{ $district->name }}</option>
                @endforeach
            </select>
            @error("lines.$index.district_id")<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-6 col-xl-2">
            <label class="form-label">Department</label>
            <select name="lines[{{ $index }}][department_id]" class="form-select @error("lines.$index.department_id") is-invalid @enderror" data-line-department @disabled($disabled)>
                <option value="">All departments</option>
                @foreach ($departments as $department)
                    <option value="{{ $department->id }}" @selected((string) data_get($line, 'department_id') === (string) $department->id)>{{ $department->name }}</option>
                @endforeach
            </select>
            @error("lines.$index.department_id")<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-6 col-xl-2">
            <label class="form-label">Budgeted Positions <span class="text-danger">*</span></label>
            <input name="lines[{{ $index }}][budgeted_positions]" type="number" min="0" class="form-control @error("lines.$index.budgeted_positions") is-invalid @enderror" value="{{ data_get($line, 'budgeted_positions', 0) }}" @disabled($disabled)>
            @error("lines.$index.budgeted_positions")<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-4 col-xl-1">
            <button type="button" class="btn btn-secondary btn-md w-100" data-remove-establishment-line @disabled($disabled)>Remove</button>
        </div>
    </div>
</div>
