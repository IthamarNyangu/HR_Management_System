@php
    $isOfficer = auth()->user()->hasRole('HR Officer');
@endphp

<div class="d-flex flex-column gap-4" data-job-opening-form>
    @if ($jobOpening->exists)
        <section class="border rounded-2 bg-light p-3">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Reference Number</label>
                    <input type="text" class="form-control" value="{{ $jobOpening->reference_no }}" disabled>
                </div>
                <div class="col-md-8">
                    <label class="form-label">Public Slug</label>
                    <input type="text" class="form-control" value="{{ $jobOpening->slug }}" disabled>
                </div>
            </div>
        </section>
    @endif

    <section class="border rounded-2 bg-light p-3">
        <h2 class="h6 mb-3">Job Summary</h2>
        <div class="row g-3">
            <div class="col-lg-8">
                <label for="title" class="form-label">Job Title <span class="text-danger">*</span></label>
                <input id="title" name="title" type="text" class="form-control @error('title') is-invalid @enderror" value="{{ old('title', $jobOpening->title) }}" required>
                @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6 col-lg-4">
                <label for="job_title_id" class="form-label">Master Job Title</label>
                <select id="job_title_id" name="job_title_id" class="form-select @error('job_title_id') is-invalid @enderror">
                    <option value="">Select job title</option>
                    @foreach ($jobTitles as $jobTitle)
                        <option value="{{ $jobTitle->id }}" @selected((string) old('job_title_id', $jobOpening->job_title_id) === (string) $jobTitle->id)>{{ $jobTitle->name }}</option>
                    @endforeach
                </select>
                @error('job_title_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-4">
                <label for="employment_type_id" class="form-label">Employment Type</label>
                <select id="employment_type_id" name="employment_type_id" class="form-select @error('employment_type_id') is-invalid @enderror">
                    <option value="">Select employment type</option>
                    @foreach ($employmentTypes as $employmentType)
                        <option value="{{ $employmentType->id }}" @selected((string) old('employment_type_id', $jobOpening->employment_type_id) === (string) $employmentType->id)>{{ $employmentType->name }}</option>
                    @endforeach
                </select>
                @error('employment_type_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-4">
                <label for="number_of_positions" class="form-label">Number of Positions</label>
                <input id="number_of_positions" name="number_of_positions" type="number" min="1" class="form-control @error('number_of_positions') is-invalid @enderror" value="{{ old('number_of_positions', $jobOpening->number_of_positions ?? 1) }}">
                @error('number_of_positions')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-4 d-flex align-items-end">
                <div class="form-check">
                    <input type="hidden" name="show_number_of_positions" value="0">
                    <input id="show_number_of_positions" name="show_number_of_positions" type="checkbox" value="1" class="form-check-input" @checked(old('show_number_of_positions', $jobOpening->show_number_of_positions ?? true))>
                    <label for="show_number_of_positions" class="form-check-label">Show number publicly</label>
                </div>
            </div>
            <div class="col-12">
                <label for="summary" class="form-label">Short Summary</label>
                <textarea id="summary" name="summary" rows="2" class="form-control @error('summary') is-invalid @enderror">{{ old('summary', $jobOpening->summary) }}</textarea>
                @error('summary')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>
    </section>

    <section class="border rounded-2 bg-light p-3">
        <h2 class="h6 mb-3">Organisation & Location</h2>
        <div class="row g-3">
            <div class="col-md-4">
                <label for="project_id" class="form-label">Project</label>
                <select id="project_id" name="project_id" class="form-select @error('project_id') is-invalid @enderror">
                    <option value="">Select project</option>
                    @foreach ($projects as $project)
                        <option value="{{ $project->id }}" @selected((string) old('project_id', $jobOpening->project_id) === (string) $project->id)>{{ $project->name }}</option>
                    @endforeach
                </select>
                @error('project_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-4">
                <label for="department_id" class="form-label">Department</label>
                <select id="department_id" name="department_id" class="form-select @error('department_id') is-invalid @enderror">
                    <option value="">Select department</option>
                    @foreach ($departments as $department)
                        <option value="{{ $department->id }}" @selected((string) old('department_id', $jobOpening->department_id) === (string) $department->id)>{{ $department->name }}</option>
                    @endforeach
                </select>
                @error('department_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-4">
                <label for="province_id" class="form-label">Province</label>
                @if ($isOfficer)
                    <input type="hidden" name="province_id" value="{{ auth()->user()->province_id }}">
                @endif
                <select id="province_id" name="{{ $isOfficer ? '_province_display' : 'province_id' }}" class="form-select @error('province_id') is-invalid @enderror" data-province-select @disabled($isOfficer)>
                    <option value="">Global / organisation-wide</option>
                    @foreach ($provinces as $province)
                        <option value="{{ $province->id }}" @selected((string) old('province_id', $jobOpening->province_id) === (string) $province->id)>{{ $province->name }}</option>
                    @endforeach
                </select>
                @error('province_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-4">
                <label for="district_id" class="form-label">District</label>
                <select id="district_id" name="district_id" class="form-select @error('district_id') is-invalid @enderror" data-district-select>
                    <option value="">Select district</option>
                    @foreach ($districts as $district)
                        <option value="{{ $district->id }}" data-province-id="{{ $district->province_id }}" @selected((string) old('district_id', $jobOpening->district_id) === (string) $district->id)>{{ $district->name }}</option>
                    @endforeach
                </select>
                @error('district_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-4">
                <label for="facility_id" class="form-label">Facility</label>
                <select id="facility_id" name="facility_id" class="form-select @error('facility_id') is-invalid @enderror" data-facility-select>
                    <option value="">Select facility</option>
                    @foreach ($facilities as $facility)
                        <option value="{{ $facility->id }}" data-district-id="{{ $facility->district_id }}" @selected((string) old('facility_id', $jobOpening->facility_id) === (string) $facility->id)>{{ $facility->name }}</option>
                    @endforeach
                </select>
                @error('facility_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-4">
                <label for="location_details" class="form-label">Location Details</label>
                <input id="location_details" name="location_details" type="text" class="form-control @error('location_details') is-invalid @enderror" value="{{ old('location_details', $jobOpening->location_details) }}" placeholder="e.g. Lusaka or field-based">
                @error('location_details')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>
    </section>

    <section class="border rounded-2 bg-light p-3">
        <h2 class="h6 mb-3">Status & Visibility</h2>
        <div class="row g-3">
            <div class="col-md-3">
                <label for="visibility" class="form-label">Visibility <span class="text-danger">*</span></label>
                <select id="visibility" name="visibility" class="form-select @error('visibility') is-invalid @enderror" required>
                    @foreach ($visibilities as $visibility)
                        <option value="{{ $visibility }}" @selected(old('visibility', $jobOpening->visibility) === $visibility)>{{ str($visibility)->headline() }}</option>
                    @endforeach
                </select>
                @error('visibility')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-3">
                <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                <select id="status" name="status" class="form-select @error('status') is-invalid @enderror" required>
                    @foreach ($statuses as $status)
                        <option value="{{ $status }}" @selected(old('status', $jobOpening->status) === $status)>{{ str($status)->headline() }}</option>
                    @endforeach
                </select>
                @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-3">
                <label for="opening_date" class="form-label">Opening Date</label>
                <input id="opening_date" name="opening_date" type="date" class="form-control @error('opening_date') is-invalid @enderror" value="{{ old('opening_date', $jobOpening->opening_date?->format('Y-m-d') ?? now()->toDateString()) }}">
                @error('opening_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-3">
                <label for="closing_date" class="form-label">Closing Date <span class="text-danger">*</span></label>
                <input id="closing_date" name="closing_date" type="date" class="form-control @error('closing_date') is-invalid @enderror" value="{{ old('closing_date', $jobOpening->closing_date?->format('Y-m-d') ?? now()->addWeeks(2)->toDateString()) }}" required>
                @error('closing_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>
    </section>

    <section class="border rounded-2 bg-light p-3">
        <h2 class="h6 mb-3">Structured Job Content</h2>
        <div class="row g-3">
            @foreach ([
                'description' => 'Description',
                'responsibilities' => 'Responsibilities',
                'requirements' => 'Requirements',
                'qualifications' => 'Qualifications',
                'experience_required' => 'Experience Required',
                'contract_details' => 'Contract Details',
                'work_level' => 'Work Level',
                'application_instructions' => 'Application Instructions',
            ] as $field => $label)
                <div class="col-lg-6">
                    <label for="{{ $field }}" class="form-label">{{ $label }}{{ in_array($field, ['description', 'application_instructions'], true) ? ' *' : '' }}</label>
                    <textarea id="{{ $field }}" name="{{ $field }}" rows="5" class="form-control @error($field) is-invalid @enderror" @required(in_array($field, ['description', 'application_instructions'], true))>{{ old($field, $jobOpening->{$field}) }}</textarea>
                    @error($field)<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            @endforeach
            <div class="col-12">
                <label for="internal_notes" class="form-label">Internal Notes</label>
                <textarea id="internal_notes" name="internal_notes" rows="3" class="form-control @error('internal_notes') is-invalid @enderror">{{ old('internal_notes', $jobOpening->internal_notes) }}</textarea>
                <div class="form-text">Internal notes are never exposed on public careers pages or API.</div>
                @error('internal_notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>
    </section>

    <div class="d-flex gap-2 pt-2">
        <button type="submit" class="btn btn-primary btn-md">Save Job Opening</button>
        <a href="{{ $jobOpening->exists ? route('recruitment.job-openings.show', $jobOpening) : route('recruitment.job-openings.index') }}" class="btn btn-secondary btn-md">Cancel</a>
    </div>
</div>

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const form = document.querySelector('[data-job-opening-form]');

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

            province?.addEventListener('change', filterDistricts);
            district?.addEventListener('change', filterFacilities);
            filterDistricts();
            filterFacilities();
        });
    </script>
@endpush
