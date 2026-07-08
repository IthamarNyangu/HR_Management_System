@extends('layouts.app')

@section('title', 'Employees')
@section('page-title', 'Employees')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item active" aria-current="page">Employees</li>
@endsection

@push('styles')
    <style>
        .employee-index-table {
            min-width: 1180px;
        }

        .employee-index-table th,
        .employee-index-table td {
            white-space: nowrap;
        }

        .employee-index-table tbody tr.employee-row-clickable {
            cursor: pointer;
        }

        .employee-index-table tbody tr.employee-row-clickable:focus {
            outline: 2px solid rgba(37, 99, 235, .45);
            outline-offset: -2px;
        }

        .employee-index-table tbody tr.employee-row-terminated {
            --bs-table-bg: #fff5f5;
            --bs-table-hover-bg: #ffecec;
            --bs-table-color: #1f2937;
        }

        .employee-index-table tbody tr.employee-row-terminated td {
            border-color: #f3d4d4;
        }
    </style>
@endpush

@section('page-actions')
    <div class="d-flex flex-wrap gap-2">
        <a href="{{ route('employees.reporting-structure') }}" class="btn btn-secondary btn-md">Reporting Structure</a>
        <a href="{{ route('employees.archived') }}" class="btn btn-secondary btn-md">Archived</a>
        @include('partials.module-export-buttons', [
            'paginator' => $employees,
            'excelRoute' => 'reports.employees.export.excel',
            'pdfRoute' => 'reports.employees.export.pdf',
        ])
        @can('create', App\Models\Employee::class)
            <a href="{{ route('employees.create') }}" class="btn btn-primary btn-md">New Employee</a>
        @endcan
    </div>
@endsection

@section('content')
    @php
        $canBulkUpdateEmployees = auth()->user()->isAdmin() || auth()->user()->isHrManager() || auth()->user()->hasRole('HR Officer');
        $terminatedStatusIds = collect($terminatedEmploymentStatusIds ?? [])->map(fn ($id) => (string) $id)->all();
    @endphp

    <div class="bg-white border rounded-2 p-3">
        <form method="GET" class="row g-2 mb-4">
            <div class="col-md-6 col-xl-3">
                <input type="search" name="search" value="{{ request('search') }}" class="form-control" placeholder="Search employees">
            </div>
            <div class="col-md-6 col-xl-2">
                <select name="province_id" class="form-select">
                    <option value="">All provinces</option>
                    @foreach ($provinces as $province)
                        <option value="{{ $province->id }}" @selected((string) request('province_id') === (string) $province->id)>{{ $province->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6 col-xl-2">
                <select name="district_id" class="form-select">
                    <option value="">All districts</option>
                    @foreach ($districts as $district)
                        <option value="{{ $district->id }}" @selected((string) request('district_id') === (string) $district->id)>{{ $district->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6 col-xl-2">
                <select name="facility_id" class="form-select">
                    <option value="">Facilities</option>
                    @foreach ($facilities as $facility)
                        <option value="{{ $facility->id }}" @selected((string) request('facility_id') === (string) $facility->id)>{{ $facility->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6 col-xl-3">
                <select name="project_id" class="form-select">
                    <option value="">All projects</option>
                    @foreach ($projects as $project)
                        <option value="{{ $project->id }}" @selected((string) request('project_id') === (string) $project->id)>{{ $project->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6 col-xl-3">
                <select name="department_id" class="form-select">
                    <option value="">All departments</option>
                    @foreach ($departments as $department)
                        <option value="{{ $department->id }}" @selected((string) request('department_id') === (string) $department->id)>{{ $department->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6 col-xl-3">
                <select name="job_title_id" class="form-select">
                    <option value="">All job titles</option>
                    @foreach ($jobTitles as $jobTitle)
                        <option value="{{ $jobTitle->id }}" @selected((string) request('job_title_id') === (string) $jobTitle->id)>{{ $jobTitle->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6 col-xl-3">
                <select name="employment_status_id" class="form-select">
                    <option value="">All statuses</option>
                    @foreach ($employmentStatuses as $employmentStatus)
                        <option value="{{ $employmentStatus->id }}" @selected((string) request('employment_status_id') === (string) $employmentStatus->id)>{{ $employmentStatus->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-primary-outline btn-md">Filter</button>
            </div>
            <div class="col-auto">
                <a href="{{ route('employees.index') }}" class="btn btn-secondary btn-md">Reset</a>
            </div>
        </form>

        @if ($canBulkUpdateEmployees)
            <form id="bulkEmployeeForm" method="POST" action="{{ route('employees.bulk-action', request()->query()) }}" data-confirm="true" data-confirm-title="Confirm bulk employee update" data-confirm-message="Select employees and choose a bulk action before continuing." data-confirm-button="Confirm Bulk Update" data-confirm-variant="btn-primary">
                @csrf
                <div class="border rounded-2 p-3 mb-3 bg-light">
                    <div class="row g-2 align-items-center">
                        <div class="col-md-6 col-xl-3">
                            <select id="bulkAction" name="action" class="form-select" aria-label="Bulk action" required>
                                <option value="" disabled @selected(! old('action'))>Choose action for selected employees</option>
                                <option value="change_employment_status" @selected(old('action') === 'change_employment_status')>Change Employment Status</option>
                                <option value="change_project" @selected(old('action') === 'change_project')>Change Project</option>
                                <option value="change_department" @selected(old('action') === 'change_department')>Change Department</option>
                                <option value="assign_supervisor" @selected(old('action') === 'assign_supervisor')>Assign Line Manager</option>
                                <option value="archive" @selected(old('action') === 'archive')>Archive Selected Employees</option>
                            </select>
                        </div>
                        <div class="col-md-6 col-xl-3 bulk-value-field" data-bulk-field="change_employment_status">
                            <select id="bulkEmploymentStatus" name="employment_status_id" class="form-select" aria-label="Employment status">
                                <option value="">Select employment status</option>
                                @foreach ($employmentStatuses as $employmentStatus)
                                    <option
                                        value="{{ $employmentStatus->id }}"
                                        data-terminated="{{ in_array((string) $employmentStatus->id, $terminatedStatusIds, true) ? '1' : '0' }}"
                                        @selected((string) old('employment_status_id') === (string) $employmentStatus->id)
                                    >
                                        {{ $employmentStatus->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12 bulk-termination-fields d-none" data-bulk-termination-fields>
                            <div class="border rounded-2 bg-white p-3">
                                <div class="fw-semibold mb-2">Termination Details</div>
                                <div class="row g-2">
                                    <div class="col-md-4">
                                        <input type="date" name="termination_date" value="{{ old('termination_date') }}" class="form-control" aria-label="Termination date" data-bulk-termination-required>
                                    </div>
                                    <div class="col-md-4">
                                        <select name="termination_reason_id" class="form-select" aria-label="Termination reason" data-bulk-termination-required>
                                            <option value="">Select termination reason</option>
                                            @foreach ($terminationReasons as $terminationReason)
                                                <option value="{{ $terminationReason->id }}" @selected((string) old('termination_reason_id') === (string) $terminationReason->id)>{{ $terminationReason->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <input type="text" name="termination_comment" value="{{ old('termination_comment') }}" class="form-control" placeholder="Termination comment optional" aria-label="Termination comment">
                                    </div>
                                </div>
                                <div class="small text-muted mt-2">These details apply to all selected employees and do not archive their records.</div>
                            </div>
                        </div>
                        <div class="col-md-6 col-xl-3 bulk-value-field" data-bulk-field="change_project">
                            <select id="bulkProject" name="project_id" class="form-select" aria-label="Project">
                                <option value="">Select project</option>
                                @foreach ($projects as $project)
                                    <option value="{{ $project->id }}" @selected((string) old('project_id') === (string) $project->id)>{{ $project->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 col-xl-3 bulk-value-field" data-bulk-field="change_department">
                            <select id="bulkDepartment" name="department_id" class="form-select" aria-label="Department">
                                <option value="">Select department</option>
                                @foreach ($departments as $department)
                                    <option value="{{ $department->id }}" @selected((string) old('department_id') === (string) $department->id)>{{ $department->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 col-xl-3 bulk-value-field" data-bulk-field="assign_supervisor">
                            <input id="bulkSupervisor" type="text" name="supervisor_name" value="{{ old('supervisor_name') }}" class="form-control" placeholder="Line manager name" aria-label="Line manager name">
                        </div>
                        <div class="col-md-auto">
                            <button id="bulkApplyButton" type="submit" class="btn btn-primary btn-md">Apply</button>
                        </div>
                    </div>
                    <div class="small text-muted mt-2">
                        <span id="bulkSelectedCount">0 employees selected.</span>
                        <span id="bulkLinkedUserWarning" class="text-warning-emphasis d-none">Some selected employees have system login accounts. This action will update employee records only and will not deactivate user accounts.</span>
                    </div>
                </div>
            </form>
        @endif

        <div class="table-responsive data-table-wrap">
            <table class="table table-hover align-middle data-table employee-index-table">
                <thead>
                    <tr>
                        @if ($canBulkUpdateEmployees)
                            <th style="width: 48px;">
                                <input id="selectAllEmployees" type="checkbox" class="form-check-input" aria-label="Select all visible employees">
                            </th>
                        @endif
                        <th>Employee ID</th>
                        <th>Employee Name</th>
                        <th>Province</th>
                        <th>Facility</th>
                        <th>Project</th>
                        <th>Job Title</th>
                        <th>Status</th>
                        <th style="min-width: 260px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($employees as $employee)
                        @php
                            $isTerminated = in_array((string) $employee->employment_status_id, $terminatedStatusIds, true)
                                || strtoupper((string) $employee->employmentStatus?->code) === 'TERMINATED'
                                || strcasecmp((string) $employee->employmentStatus?->name, 'Terminated') === 0;
                        @endphp
                        <tr
                            class="employee-row-clickable {{ $isTerminated ? 'employee-row-terminated' : '' }}"
                            data-employee-profile-url="{{ route('employees.show', $employee) }}"
                            tabindex="0"
                            aria-label="Open profile for {{ $employee->display_name }}"
                        >
                            @if ($canBulkUpdateEmployees)
                                <td>
                                    @canany(['update', 'archive'], $employee)
                                        <input form="bulkEmployeeForm" type="checkbox" name="employee_ids[]" value="{{ $employee->id }}" class="form-check-input employee-select" data-employee-name="{{ $employee->display_name }}" data-has-linked-user="{{ $employee->user ? '1' : '0' }}" aria-label="Select {{ $employee->display_name }}">
                                    @endcanany
                                </td>
                            @endif
                            <td class="fw-semibold">{{ $employee->employee_no }}</td>
                            <td>{{ $employee->full_name }}</td>
                            <td>{{ $employee->province?->name }}</td>
                            <td>{{ $employee->facility?->name ?? '-' }}</td>
                            <td>{{ $employee->project?->name ?? '-' }}</td>
                            <td>{{ $employee->jobTitle?->name ?? '-' }}</td>
                            <td>
                                @if ($isTerminated)
                                    <span class="badge text-bg-danger-subtle text-danger-emphasis border border-danger-subtle">Terminated</span>
                                @else
                                    {{ $employee->employmentStatus?->name ?? '-' }}
                                @endif
                            </td>
                            <td>
                                <div class="d-inline-flex gap-2 flex-nowrap" data-no-row-click>
                                    @can('view', $employee)
                                        <a href="{{ route('employees.show', $employee) }}" class="btn btn-sm btn-secondary">View</a>
                                    @endcan
                                    @can('update', $employee)
                                        <a href="{{ route('employees.edit', $employee) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                                    @endcan
                                    @can('archive', $employee)
                                        <form method="POST" action="{{ route('employees.archive', $employee) }}" data-confirm="true" data-confirm-title="Archive employee?" data-confirm-message="This employee will be moved to archived records and hidden from the active employee register. Do you want to continue?" data-confirm-button="Archive employee" data-confirm-variant="btn-warning">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="btn btn-sm btn-outline-warning">Archive</button>
                                        </form>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $canBulkUpdateEmployees ? 9 : 8 }}" class="text-center text-muted py-4">No employees found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-3">
            {{ $employees->links() }}
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('[data-employee-profile-url]').forEach(function (row) {
                const openProfile = function () {
                    window.location.href = row.dataset.employeeProfileUrl;
                };

                const isInteractiveTarget = function (target) {
                    return Boolean(target.closest('a, button, input, select, textarea, form, label, [data-no-row-click]'));
                };

                row.addEventListener('click', function (event) {
                    if (isInteractiveTarget(event.target)) {
                        return;
                    }

                    openProfile();
                });

                row.addEventListener('keydown', function (event) {
                    if (!['Enter', ' '].includes(event.key) || isInteractiveTarget(event.target)) {
                        return;
                    }

                    event.preventDefault();
                    openProfile();
                });
            });

            const bulkForm = document.getElementById('bulkEmployeeForm');

            if (! bulkForm) {
                return;
            }

            const bulkAction = document.getElementById('bulkAction');
            const valueFields = document.querySelectorAll('.bulk-value-field');
            const selectedCount = document.getElementById('bulkSelectedCount');
            const linkedUserWarning = document.getElementById('bulkLinkedUserWarning');
            const selectAll = document.getElementById('selectAllEmployees');
            const applyButton = document.getElementById('bulkApplyButton');
            const checkboxes = document.querySelectorAll('.employee-select');
            const bulkEmploymentStatus = document.getElementById('bulkEmploymentStatus');
            const terminationFields = document.querySelector('[data-bulk-termination-fields]');
            const terminationRequiredFields = document.querySelectorAll('[data-bulk-termination-required]');

            const actionLabels = {
                change_employment_status: 'Change Employment Status',
                change_project: 'Change Project',
                change_department: 'Change Department',
                assign_supervisor: 'Assign Line Manager',
                archive: 'Archive Selected Employees',
            };

            function fieldValueLabel(action) {
                if (action === 'archive') {
                    return 'Archive selected employees';
                }

                const field = document.querySelector(`[data-bulk-field="${action}"]`);
                const control = field ? field.querySelector('select, input') : null;

                if (! control) {
                    return '';
                }

                if (control.tagName === 'SELECT') {
                    return control.options[control.selectedIndex]?.text || '';
                }

                return control.value || '';
            }

            function updateValueFields() {
                valueFields.forEach(function (field) {
                    const isActive = field.dataset.bulkField === bulkAction.value;
                    field.classList.toggle('d-none', ! isActive);
                });
                updateTerminationFields();
            }

            function updateTerminationFields() {
                if (!terminationFields || !bulkEmploymentStatus) return;

                const isVisible = bulkAction.value === 'change_employment_status'
                    && bulkEmploymentStatus.selectedOptions[0]?.dataset.terminated === '1';

                terminationFields.classList.toggle('d-none', !isVisible);
                terminationRequiredFields.forEach(function (field) {
                    field.required = isVisible;
                });
            }

            function updateSelectionSummary() {
                const selected = Array.from(checkboxes).filter(function (checkbox) {
                    return checkbox.checked;
                });
                const linkedUserCount = selected.filter(function (checkbox) {
                    return checkbox.dataset.hasLinkedUser === '1';
                }).length;

                selectedCount.textContent = `${selected.length} ${selected.length === 1 ? 'employee' : 'employees'} selected.`;
                linkedUserWarning.classList.toggle('d-none', linkedUserCount === 0);

                if (selectAll) {
                    selectAll.checked = selected.length > 0 && selected.length === checkboxes.length;
                    selectAll.indeterminate = selected.length > 0 && selected.length < checkboxes.length;
                }
            }

            function activeValueControl(action) {
                const field = document.querySelector(`[data-bulk-field="${action}"]`);

                return field ? field.querySelector('select, input') : null;
            }

            function updateRequiredFields() {
                valueFields.forEach(function (field) {
                    const control = field.querySelector('select, input');

                    if (control) {
                        control.required = field.dataset.bulkField === bulkAction.value;
                    }
                });
            }

            function updateConfirmationDetails() {
                const selected = Array.from(checkboxes).filter(function (checkbox) {
                    return checkbox.checked;
                });
                const action = bulkAction.value;
                const actionLabel = actionLabels[action] || 'Bulk action';
                const valueLabel = fieldValueLabel(action);
                const linkedUserCount = selected.filter(function (checkbox) {
                    return checkbox.dataset.hasLinkedUser === '1';
                }).length;
                let message = `${selected.length} ${selected.length === 1 ? 'employee' : 'employees'} will be affected by ${actionLabel}.`;

                if (valueLabel) {
                    message += ` Value: ${valueLabel}.`;
                }

                if (action === 'change_employment_status' && bulkEmploymentStatus?.selectedOptions[0]?.dataset.terminated === '1') {
                    const terminationDate = document.querySelector('[name="termination_date"]')?.value || '';
                    const terminationReason = document.querySelector('[name="termination_reason_id"]')?.selectedOptions[0]?.text || '';
                    message += ` Termination date: ${terminationDate || 'not set'}. Reason: ${terminationReason || 'not set'}.`;
                }

                if (linkedUserCount > 0) {
                    message += ' Some selected employees have system login accounts. This action will update employee records only and will not deactivate user accounts.';
                }

                message += ' Do you want to continue?';

                bulkForm.dataset.confirmMessage = message;
                bulkForm.dataset.confirmVariant = action === 'archive' ? 'btn-warning' : 'btn-primary';
            }

            bulkAction.addEventListener('change', updateValueFields);
            bulkAction.addEventListener('change', updateRequiredFields);
            bulkEmploymentStatus?.addEventListener('change', updateTerminationFields);
            checkboxes.forEach(function (checkbox) {
                checkbox.addEventListener('change', updateSelectionSummary);
            });

            if (selectAll) {
                selectAll.addEventListener('change', function () {
                    checkboxes.forEach(function (checkbox) {
                        checkbox.checked = selectAll.checked;
                    });
                    updateSelectionSummary();
                });
            }

            applyButton.addEventListener('click', updateConfirmationDetails);
            bulkForm.addEventListener('submit', function (event) {
                const selected = Array.from(checkboxes).filter(function (checkbox) {
                    return checkbox.checked;
                });
                const valueControl = activeValueControl(bulkAction.value);

                if (selected.length === 0) {
                    event.preventDefault();
                    event.stopImmediatePropagation();
                    alert('Please select at least one employee before applying a bulk action.');
                    return;
                }

                if (! bulkAction.value) {
                    event.preventDefault();
                    event.stopImmediatePropagation();
                    bulkAction.reportValidity();
                    return;
                }

                if (valueControl && ! valueControl.value) {
                    event.preventDefault();
                    event.stopImmediatePropagation();
                    valueControl.reportValidity();
                    return;
                }

                updateConfirmationDetails();
            }, true);
            updateValueFields();
            updateRequiredFields();
            updateSelectionSummary();
        });
    </script>
@endpush
