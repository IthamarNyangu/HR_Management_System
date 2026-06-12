@php
    $isOfficer = auth()->user()->hasRole('HR Officer');
    $selectedReportingTo = old(
        'reporting_to_job_title_id',
        $jobOpening->reporting_to_tba ? 'tba' : $jobOpening->reporting_to_job_title_id
    );
    $contactEmail = config('mail.from.address') ?: 'hrms-noreply@righttocare-zambia.org';
@endphp

<div class="d-flex flex-column gap-4" data-job-opening-form>
    @if ($jobOpening->exists)
        <section class="border rounded-2 bg-light p-3">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Request to Hire No.</label>
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
        <div class="text-center bg-white border rounded-2 py-3 px-2 mb-3">
            <h2 class="h5 text-danger mb-0">C A R E E R&nbsp;&nbsp; O P P O R T U N I T Y</h2>
        </div>
        <div class="small text-muted">
            This form follows the RTCZ internal vacancy announcement format. Enter one bullet per line in the large content boxes.
        </div>
    </section>

    <section class="border rounded-2 bg-light p-3">
        <div class="text-center bg-white border rounded-2 py-2 px-2 mb-3">
            <h2 class="h6 text-danger mb-0">A B O U T&nbsp;&nbsp; U S</h2>
        </div>
        <p class="mb-0">{{ App\Models\JobOpening::ABOUT_US_TEXT }}</p>
        <div class="form-text">This section is prefilled for all vacancy announcements.</div>
    </section>

    <section class="border rounded-2 bg-light p-3">
        <div class="text-center bg-white border rounded-2 py-2 px-2 mb-3">
            <h2 class="h6 text-danger mb-0">A B O U T&nbsp;&nbsp; T H E&nbsp;&nbsp; P O S I T I O N</h2>
        </div>

        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Request to Hire No.</label>
                <input type="text" class="form-control" value="{{ $jobOpening->reference_no ?: 'System generated on save' }}" disabled>
            </div>
            <div class="col-md-4">
                <label for="opening_date" class="form-label">Date Advertised</label>
                <input id="opening_date" name="opening_date" type="date" class="form-control @error('opening_date') is-invalid @enderror" value="{{ old('opening_date', $jobOpening->opening_date?->format('Y-m-d') ?? now()->toDateString()) }}">
                @error('opening_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-4">
                <label for="closing_date" class="form-label">Closing Date <span class="text-danger">*</span></label>
                <input id="closing_date" name="closing_date" type="date" class="form-control @error('closing_date') is-invalid @enderror" value="{{ old('closing_date', $jobOpening->closing_date?->format('Y-m-d') ?? now()->addWeeks(2)->toDateString()) }}" required>
                @error('closing_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-lg-6">
                <label for="job_title_id" class="form-label">Position <span class="text-danger">*</span></label>
                <select id="job_title_id" name="job_title_id" class="form-select @error('job_title_id') is-invalid @enderror" required>
                    <option value="">Select job title</option>
                    @foreach ($jobTitles as $jobTitle)
                        <option value="{{ $jobTitle->id }}" @selected((string) old('job_title_id', $jobOpening->job_title_id) === (string) $jobTitle->id)>{{ $jobTitle->name }}</option>
                    @endforeach
                </select>
                @error('job_title_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                <div class="form-text d-flex flex-wrap align-items-center gap-2">
                    <span>Position must already exist in the Job Titles master table.</span>
                    @can('manage-master-data')
                        <a href="{{ route('admin.master-data.records', 'job-titles') }}">Manage job titles</a>
                    @endcan
                </div>
                <input type="hidden" name="title" value="{{ old('title', $jobOpening->title) }}">
            </div>
            <div class="col-lg-6">
                <label for="location_details" class="form-label">Location</label>
                <input id="location_details" name="location_details" type="text" class="form-control @error('location_details') is-invalid @enderror" value="{{ old('location_details', $jobOpening->location_details) }}" placeholder="e.g. Lusaka Office">
                @error('location_details')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-4">
                <label for="number_of_positions" class="form-label">No. of Vacancies</label>
                <input id="number_of_positions" name="number_of_positions" type="number" min="1" class="form-control @error('number_of_positions') is-invalid @enderror" value="{{ old('number_of_positions', $jobOpening->number_of_positions ?? 1) }}">
                @error('number_of_positions')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-4">
                <label for="contract_duration" class="form-label">Contract Duration</label>
                <input id="contract_duration" name="contract_duration" type="text" class="form-control @error('contract_duration') is-invalid @enderror" value="{{ old('contract_duration', $jobOpening->contract_duration) }}" placeholder="e.g. 12 months">
                @error('contract_duration')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-4">
                <label for="employment_type_id" class="form-label">Contract Type</label>
                <select id="employment_type_id" name="employment_type_id" class="form-select @error('employment_type_id') is-invalid @enderror">
                    <option value="">Select contract type</option>
                    @foreach ($employmentTypes as $employmentType)
                        <option value="{{ $employmentType->id }}" @selected((string) old('employment_type_id', $jobOpening->employment_type_id) === (string) $employmentType->id)>{{ $employmentType->name }}</option>
                    @endforeach
                </select>
                @error('employment_type_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-4">
                <label for="job_grade" class="form-label">Job Grade</label>
                <input id="job_grade" name="job_grade" type="text" class="form-control @error('job_grade') is-invalid @enderror" value="{{ old('job_grade', $jobOpening->job_grade) }}" placeholder="e.g. C2">
                @error('job_grade')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-4">
                <label for="reporting_to_job_title_id" class="form-label">Reporting To <span class="text-danger">*</span></label>
                <select id="reporting_to_job_title_id" name="reporting_to_job_title_id" class="form-select @error('reporting_to_job_title_id') is-invalid @enderror" required>
                    <option value="">Select reporting job title</option>
                    <option value="tba" @selected((string) $selectedReportingTo === 'tba')>TBA</option>
                    @foreach ($jobTitles as $jobTitle)
                        <option value="{{ $jobTitle->id }}" @selected((string) $selectedReportingTo === (string) $jobTitle->id)>{{ $jobTitle->name }}</option>
                    @endforeach
                </select>
                @error('reporting_to_job_title_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-4">
                <label class="form-label">Contact Person</label>
                <input type="text" class="form-control" value="People & Culture Department" disabled>
            </div>

            <div class="col-md-4">
                <label class="form-label">Contact Email</label>
                <input type="text" class="form-control" value="{{ $contactEmail }}" disabled>
            </div>
            <div class="col-md-4">
                <label for="visibility" class="form-label">Visibility <span class="text-danger">*</span></label>
                <select id="visibility" name="visibility" class="form-select @error('visibility') is-invalid @enderror" required>
                    @foreach ($visibilities as $visibility)
                        <option value="{{ $visibility }}" @selected(old('visibility', $jobOpening->visibility) === $visibility)>{{ str($visibility)->headline() }}</option>
                    @endforeach
                </select>
                @error('visibility')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-4">
                <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                <select id="status" name="status" class="form-select @error('status') is-invalid @enderror" required>
                    @foreach ($statuses as $status)
                        <option value="{{ $status }}" @selected(old('status', $jobOpening->status) === $status)>{{ str($status)->headline() }}</option>
                    @endforeach
                </select>
                @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-4">
                <label for="project_id" class="form-label">Project</label>
                <select id="project_id" name="project_id" class="form-select @error('project_id') is-invalid @enderror">
                    <option value="">Select project</option>
                    @foreach ($projects as $project)
                        <option value="{{ $project->id }}" @selected((string) old('project_id', $jobOpening->project_id) === (string) $project->id)>{{ $project->name }}</option>
                    @endforeach
                </select>
                <div class="form-text">Internal HRMS classification. Not printed as a vacancy field.</div>
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
                <div class="form-text">Internal HRMS classification. Not printed as a vacancy field.</div>
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
            <div class="col-md-4 d-flex align-items-end">
                <div class="form-check">
                    <input type="hidden" name="show_number_of_positions" value="0">
                    <input id="show_number_of_positions" name="show_number_of_positions" type="checkbox" value="1" class="form-check-input" @checked(old('show_number_of_positions', $jobOpening->show_number_of_positions ?? true))>
                    <label for="show_number_of_positions" class="form-check-label">Show number publicly</label>
                </div>
            </div>
            <div class="col-12">
                <label for="summary" class="form-label">Careers List Summary</label>
                <textarea id="summary" name="summary" rows="2" class="form-control @error('summary') is-invalid @enderror">{{ old('summary', $jobOpening->summary) }}</textarea>
                <div class="form-text">Short preview text for recruitment lists and public careers cards. Not printed as a vacancy section.</div>
                @error('summary')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>
    </section>

    @foreach ([
        'qualifications' => 'Q U A L I F I C A T I O N S&nbsp;&nbsp; A N D&nbsp;&nbsp; E X P E R I E N C E',
        'requirements' => 'T E C H N I C A L&nbsp;&nbsp; A N D&nbsp;&nbsp; B E H A V I O U R A L&nbsp;&nbsp; C O M P E T E N C I E S',
        'responsibilities' => 'K E Y&nbsp;&nbsp; P E R F O R M A N C E&nbsp;&nbsp; A R E A S',
    ] as $field => $label)
        <section class="border rounded-2 bg-light p-3">
            <div class="text-center bg-white border rounded-2 py-2 px-2 mb-3">
                <h2 class="h6 text-danger mb-0">{!! $label !!}</h2>
            </div>
            <textarea id="{{ $field }}" name="{{ $field }}" rows="7" class="form-control @error($field) is-invalid @enderror" placeholder="Enter one bullet per line">{{ old($field, $jobOpening->{$field}) }}</textarea>
            <div class="form-text">Each line will appear as a bullet point in the vacancy announcement PDF.</div>
            @error($field)<div class="invalid-feedback">{{ $message }}</div>@enderror
        </section>
    @endforeach

    <section class="border rounded-2 bg-light p-3">
        <div class="text-center bg-white border rounded-2 py-2 px-2 mb-3">
            <h2 class="h6 text-danger mb-0">A P P L I C A T I O N&nbsp;&nbsp; P R O C E D U R E</h2>
        </div>
        <p class="mb-0">Applications will be submitted through the HRMS careers portal. The generated PDF will include the application link when the vacancy is published and open.</p>
    </section>

    <section class="border rounded-2 bg-light p-3">
        <div class="text-center bg-white border rounded-2 py-2 px-2 mb-3">
            <h2 class="h6 text-danger mb-0">D I S C L A I M E R</h2>
        </div>
        <p class="mb-0">{{ App\Models\JobOpening::DISCLAIMER_TEXT }}</p>
        <div class="form-text">This section is prefilled for all vacancy announcements.</div>
    </section>

    <section class="border rounded-2 bg-light p-3">
        <h2 class="h6 mb-3">Internal Notes</h2>
        <textarea id="internal_notes" name="internal_notes" rows="3" class="form-control @error('internal_notes') is-invalid @enderror">{{ old('internal_notes', $jobOpening->internal_notes) }}</textarea>
        <div class="form-text">Internal notes are never exposed on public careers pages, API, or vacancy PDFs.</div>
        @error('internal_notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
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
