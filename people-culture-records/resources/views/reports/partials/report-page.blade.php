@php
    $query = request()->query();
@endphp

<div class="d-flex flex-column gap-3">
    <section class="bg-white border rounded-2 p-3">
        <form method="GET" class="row g-2 align-items-end">
            @if (in_array($type, ['employees', 'disciplinary-cases', 'promotions', 'relocations', 'archived-records'], true))
                <div class="col-md-6 col-xl-3">
                    <label class="form-label" for="search">Search</label>
                    <input id="search" name="search" type="search" value="{{ request('search') }}" class="form-control" placeholder="Reference, employee, name, email">
                </div>
            @endif

            @if ($type === 'archived-records')
                <div class="col-md-6 col-xl-2">
                    <label class="form-label" for="module">Module</label>
                    <select id="module" name="module" class="form-select">
                        <option value="">All modules</option>
                        <option value="employees" @selected(request('module') === 'employees')>Employees</option>
                        <option value="disciplinary-cases" @selected(request('module') === 'disciplinary-cases')>Disciplinary Cases</option>
                        <option value="promotions" @selected(request('module') === 'promotions')>Promotions</option>
                        <option value="relocations" @selected(request('module') === 'relocations')>Relocations</option>
                    </select>
                </div>
            @endif

            @if (in_array($type, ['employees', 'disciplinary-cases', 'promotions', 'expiring-cases', 'archived-records'], true))
                <div class="col-md-6 col-xl-2">
                    <label class="form-label" for="province_id">Province</label>
                    <select id="province_id" name="province_id" class="form-select">
                        <option value="">All provinces</option>
                        @foreach ($options['provinces'] as $province)
                            <option value="{{ $province->id }}" @selected((string) request('province_id') === (string) $province->id)>{{ $province->name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif

            @if ($type === 'relocations')
                <div class="col-md-6 col-xl-2">
                    <label class="form-label" for="from_province_id">From Province</label>
                    <select id="from_province_id" name="from_province_id" class="form-select">
                        <option value="">All from provinces</option>
                        @foreach ($options['provinces'] as $province)
                            <option value="{{ $province->id }}" @selected((string) request('from_province_id') === (string) $province->id)>{{ $province->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6 col-xl-2">
                    <label class="form-label" for="to_province_id">To Province</label>
                    <select id="to_province_id" name="to_province_id" class="form-select">
                        <option value="">All to provinces</option>
                        @foreach ($options['provinces'] as $province)
                            <option value="{{ $province->id }}" @selected((string) request('to_province_id') === (string) $province->id)>{{ $province->name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif

            @if (in_array($type, ['employees', 'disciplinary-cases', 'promotions', 'expiring-cases'], true))
                <div class="col-md-6 col-xl-2">
                    <label class="form-label" for="district_id">District</label>
                    <select id="district_id" name="district_id" class="form-select">
                        <option value="">All districts</option>
                        @foreach ($options['districts'] as $district)
                            <option value="{{ $district->id }}" @selected((string) request('district_id') === (string) $district->id)>{{ $district->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6 col-xl-2">
                    <label class="form-label" for="facility_id">Facility</label>
                    <select id="facility_id" name="facility_id" class="form-select">
                        <option value="">All facilities</option>
                        @foreach ($options['facilities'] as $facility)
                            <option value="{{ $facility->id }}" @selected((string) request('facility_id') === (string) $facility->id)>{{ $facility->name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif

            @if ($type === 'relocations')
                <div class="col-md-6 col-xl-2">
                    <label class="form-label" for="from_district_id">From District</label>
                    <select id="from_district_id" name="from_district_id" class="form-select">
                        <option value="">All from districts</option>
                        @foreach ($options['districts'] as $district)
                            <option value="{{ $district->id }}" @selected((string) request('from_district_id') === (string) $district->id)>{{ $district->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6 col-xl-2">
                    <label class="form-label" for="to_district_id">To District</label>
                    <select id="to_district_id" name="to_district_id" class="form-select">
                        <option value="">All to districts</option>
                        @foreach ($options['districts'] as $district)
                            <option value="{{ $district->id }}" @selected((string) request('to_district_id') === (string) $district->id)>{{ $district->name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif

            @if (in_array($type, ['employees', 'disciplinary-cases', 'promotions', 'relocations'], true))
                <div class="col-md-6 col-xl-2">
                    <label class="form-label" for="project_id">Project</label>
                    <select id="project_id" name="project_id" class="form-select">
                        <option value="">All projects</option>
                        @foreach ($options['projects'] as $project)
                            <option value="{{ $project->id }}" @selected((string) request('project_id') === (string) $project->id)>{{ $project->name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif

            @if (in_array($type, ['employees', 'promotions', 'relocations'], true))
                <div class="col-md-6 col-xl-2">
                    <label class="form-label" for="department_id">Department</label>
                    <select id="department_id" name="department_id" class="form-select">
                        <option value="">All departments</option>
                        @foreach ($options['departments'] as $department)
                            <option value="{{ $department->id }}" @selected((string) request('department_id') === (string) $department->id)>{{ $department->name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif

            @if ($type === 'employees')
                <div class="col-md-6 col-xl-2">
                    <label class="form-label" for="job_title_id">Job Title</label>
                    <select id="job_title_id" name="job_title_id" class="form-select">
                        <option value="">All job titles</option>
                        @foreach ($options['jobTitles'] as $jobTitle)
                            <option value="{{ $jobTitle->id }}" @selected((string) request('job_title_id') === (string) $jobTitle->id)>{{ $jobTitle->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6 col-xl-2">
                    <label class="form-label" for="employment_status_id">Status</label>
                    <select id="employment_status_id" name="employment_status_id" class="form-select">
                        <option value="">All statuses</option>
                        @foreach ($options['employmentStatuses'] as $status)
                            <option value="{{ $status->id }}" @selected((string) request('employment_status_id') === (string) $status->id)>{{ $status->name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif

            @if (in_array($type, ['disciplinary-cases', 'expiring-cases'], true))
                <div class="col-md-6 col-xl-2">
                    <label class="form-label" for="offence_category_id">Offence</label>
                    <select id="offence_category_id" name="offence_category_id" class="form-select">
                        <option value="">All offences</option>
                        @foreach ($options['offenceCategories'] as $category)
                            <option value="{{ $category->id }}" @selected((string) request('offence_category_id') === (string) $category->id)>{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6 col-xl-2">
                    <label class="form-label" for="penalty_type_id">Penalty</label>
                    <select id="penalty_type_id" name="penalty_type_id" class="form-select">
                        <option value="">All penalties</option>
                        @foreach ($options['penaltyTypes'] as $penalty)
                            <option value="{{ $penalty->id }}" @selected((string) request('penalty_type_id') === (string) $penalty->id)>{{ $penalty->name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif

            @if ($type === 'disciplinary-cases')
                <div class="col-md-6 col-xl-2">
                    <label class="form-label" for="case_status_id">Status</label>
                    <select id="case_status_id" name="case_status_id" class="form-select">
                        <option value="">All statuses</option>
                        @foreach ($options['caseStatuses'] as $status)
                            <option value="{{ $status->id }}" @selected((string) request('case_status_id') === (string) $status->id)>{{ $status->name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif

            @if ($type === 'promotions')
                <div class="col-md-6 col-xl-2">
                    <label class="form-label" for="old_job_title_id">Old Title</label>
                    <select id="old_job_title_id" name="old_job_title_id" class="form-select">
                        <option value="">All old titles</option>
                        @foreach ($options['jobTitles'] as $jobTitle)
                            <option value="{{ $jobTitle->id }}" @selected((string) request('old_job_title_id') === (string) $jobTitle->id)>{{ $jobTitle->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6 col-xl-2">
                    <label class="form-label" for="new_job_title_id">New Title</label>
                    <select id="new_job_title_id" name="new_job_title_id" class="form-select">
                        <option value="">All new titles</option>
                        @foreach ($options['jobTitles'] as $jobTitle)
                            <option value="{{ $jobTitle->id }}" @selected((string) request('new_job_title_id') === (string) $jobTitle->id)>{{ $jobTitle->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6 col-xl-2">
                    <label class="form-label" for="promotion_type_id">Type</label>
                    <select id="promotion_type_id" name="promotion_type_id" class="form-select">
                        <option value="">All types</option>
                        @foreach ($options['promotionTypes'] as $promotionType)
                            <option value="{{ $promotionType->id }}" @selected((string) request('promotion_type_id') === (string) $promotionType->id)>{{ $promotionType->name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif

            @if ($type === 'relocations')
                <div class="col-md-6 col-xl-2">
                    <label class="form-label" for="job_title_id">Job Title</label>
                    <select id="job_title_id" name="job_title_id" class="form-select">
                        <option value="">All job titles</option>
                        @foreach ($options['jobTitles'] as $jobTitle)
                            <option value="{{ $jobTitle->id }}" @selected((string) request('job_title_id') === (string) $jobTitle->id)>{{ $jobTitle->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6 col-xl-2">
                    <label class="form-label" for="relocation_reason_id">Reason</label>
                    <select id="relocation_reason_id" name="relocation_reason_id" class="form-select">
                        <option value="">All reasons</option>
                        @foreach ($options['relocationReasons'] as $reason)
                            <option value="{{ $reason->id }}" @selected((string) request('relocation_reason_id') === (string) $reason->id)>{{ $reason->name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif

            @if ($type === 'promotions')
                <div class="col-md-6 col-xl-2">
                    <label class="form-label" for="promotion_from">Promotion From</label>
                    <input id="promotion_from" name="promotion_from" type="date" value="{{ request('promotion_from') }}" class="form-control">
                </div>
                <div class="col-md-6 col-xl-2">
                    <label class="form-label" for="promotion_to">Promotion To</label>
                    <input id="promotion_to" name="promotion_to" type="date" value="{{ request('promotion_to') }}" class="form-control">
                </div>
            @endif

            @if (in_array($type, ['disciplinary-cases'], true))
                <div class="col-md-6 col-xl-2">
                    <label class="form-label" for="effective_from">Effective From</label>
                    <input id="effective_from" name="effective_from" type="date" value="{{ request('effective_from') }}" class="form-control">
                </div>
                <div class="col-md-6 col-xl-2">
                    <label class="form-label" for="effective_to">Effective To</label>
                    <input id="effective_to" name="effective_to" type="date" value="{{ request('effective_to') }}" class="form-control">
                </div>
            @endif

            @if (in_array($type, ['disciplinary-cases', 'expiring-cases'], true))
                <div class="col-md-6 col-xl-2">
                    <label class="form-label" for="expiry_from">Expiry From</label>
                    <input id="expiry_from" name="expiry_from" type="date" value="{{ request('expiry_from') }}" class="form-control">
                </div>
                <div class="col-md-6 col-xl-2">
                    <label class="form-label" for="expiry_to">Expiry To</label>
                    <input id="expiry_to" name="expiry_to" type="date" value="{{ request('expiry_to') }}" class="form-control">
                </div>
            @endif

            @if ($type === 'relocations')
                <div class="col-md-6 col-xl-2">
                    <label class="form-label" for="effective_from">Effective From</label>
                    <input id="effective_from" name="effective_from" type="date" value="{{ request('effective_from') }}" class="form-control">
                </div>
                <div class="col-md-6 col-xl-2">
                    <label class="form-label" for="effective_to">Effective To</label>
                    <input id="effective_to" name="effective_to" type="date" value="{{ request('effective_to') }}" class="form-control">
                </div>
                <div class="col-md-6 col-xl-2">
                    <label class="form-label" for="relocation_amount_min">Amount Min</label>
                    <input id="relocation_amount_min" name="relocation_amount_min" type="number" min="0" step="0.01" value="{{ request('relocation_amount_min') }}" class="form-control">
                </div>
                <div class="col-md-6 col-xl-2">
                    <label class="form-label" for="relocation_amount_max">Amount Max</label>
                    <input id="relocation_amount_max" name="relocation_amount_max" type="number" min="0" step="0.01" value="{{ request('relocation_amount_max') }}" class="form-control">
                </div>
            @endif

            @if (in_array($type, ['promotions', 'relocations'], true))
                <div class="col-md-6 col-xl-2">
                    <label class="form-label" for="year">Year</label>
                    <input id="year" name="year" type="number" min="2000" max="2100" value="{{ request('year') }}" class="form-control">
                </div>
            @endif

            @if ($type === 'archived-records')
                <div class="col-md-6 col-xl-2">
                    <label class="form-label" for="archived_from">Archived From</label>
                    <input id="archived_from" name="archived_from" type="date" value="{{ request('archived_from') }}" class="form-control">
                </div>
                <div class="col-md-6 col-xl-2">
                    <label class="form-label" for="archived_to">Archived To</label>
                    <input id="archived_to" name="archived_to" type="date" value="{{ request('archived_to') }}" class="form-control">
                </div>
            @endif

            <div class="col-auto">
                <button type="submit" class="btn btn-primary-outline btn-md">Filter</button>
            </div>
            <div class="col-auto">
                <a href="{{ route($report['route']) }}" class="btn btn-secondary btn-md">Reset</a>
            </div>
        </form>
    </section>

    <section class="bg-white border rounded-2 p-3">
        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-2 mb-3">
            <div>
                <h2 class="h5 mb-1">{{ $report['title'] }}</h2>
                <p class="text-muted small mb-0">{{ $rows->total() }} record(s) found.</p>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route($report['excel_route'], $query) }}" class="btn btn-primary btn-md">Export Excel</a>
                <a href="{{ route($report['pdf_route'], $query) }}" class="btn btn-secondary btn-md">Export PDF</a>
            </div>
        </div>

        <div class="table-responsive data-table-wrap">
            <table class="table table-hover align-middle data-table">
                <thead>
                    <tr>
                        @foreach ($report['columns'] as $label)
                            <th>{{ $label }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rows as $row)
                        <tr>
                            @foreach (array_keys($report['columns']) as $key)
                                <td>
                                    @if ($key === 'restore_url' && filled($row[$key] ?? null))
                                        <form method="POST" action="{{ $row[$key] }}" data-confirm="true" data-confirm-title="Restore archived record?" data-confirm-message="This archived record will return to the active register. Do you want to continue?" data-confirm-button="Restore record">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="btn btn-sm btn-outline-primary">Restore</button>
                                        </form>
                                    @else
                                        {{ $row[$key] ?? '-' }}
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ count($report['columns']) }}" class="text-center text-muted py-4">No records found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-3">
            {{ $rows->withQueryString()->links() }}
        </div>
    </section>
</div>
