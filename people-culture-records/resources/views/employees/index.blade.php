@extends('layouts.app')

@section('title', 'Employees')
@section('page-title', 'Employees')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item active" aria-current="page">Employees</li>
@endsection

@section('page-actions')
    <div class="d-flex gap-2">
        <a href="{{ route('employees.archived') }}" class="btn btn-secondary btn-md">Archived</a>
        @can('create', App\Models\Employee::class)
            <a href="{{ route('employees.create') }}" class="btn btn-primary btn-md">New Employee</a>
        @endcan
    </div>
@endsection

@section('content')
    @php
        $canBulkUpdateEmployees = auth()->user()->isAdmin() || auth()->user()->isHrManager() || auth()->user()->hasRole('HR Officer');
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
                    <option value="">All facilities</option>
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
                                <option value="assign_supervisor" @selected(old('action') === 'assign_supervisor')>Assign Supervisor</option>
                                <option value="archive" @selected(old('action') === 'archive')>Archive Selected Employees</option>
                            </select>
                        </div>
                        <div class="col-md-6 col-xl-3 bulk-value-field" data-bulk-field="change_employment_status">
                            <select id="bulkEmploymentStatus" name="employment_status_id" class="form-select" aria-label="Employment status">
                                <option value="">Select employment status</option>
                                @foreach ($employmentStatuses as $employmentStatus)
                                    <option value="{{ $employmentStatus->id }}" @selected((string) old('employment_status_id') === (string) $employmentStatus->id)>{{ $employmentStatus->name }}</option>
                                @endforeach
                            </select>
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
                            <input id="bulkSupervisor" type="text" name="supervisor_name" value="{{ old('supervisor_name') }}" class="form-control" placeholder="Supervisor name" aria-label="Supervisor name">
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
            <table class="table table-hover align-middle data-table">
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
                        <th>District</th>
                        <th>Facility</th>
                        <th>Project</th>
                        <th>Job Title</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($employees as $employee)
                        <tr>
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
                            <td>{{ $employee->district?->name }}</td>
                            <td>{{ $employee->facility?->name ?? '-' }}</td>
                            <td>{{ $employee->project?->name ?? '-' }}</td>
                            <td>{{ $employee->jobTitle?->name ?? '-' }}</td>
                            <td>{{ $employee->employmentStatus?->name ?? '-' }}</td>
                            <td class="text-end">
                                <div class="d-inline-flex gap-2">
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
                            <td colspan="{{ $canBulkUpdateEmployees ? 10 : 9 }}" class="text-center text-muted py-4">No employees found.</td>
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

            const actionLabels = {
                change_employment_status: 'Change Employment Status',
                change_project: 'Change Project',
                change_department: 'Change Department',
                assign_supervisor: 'Assign Supervisor',
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

                if (linkedUserCount > 0) {
                    message += ' Some selected employees have system login accounts. This action will update employee records only and will not deactivate user accounts.';
                }

                message += ' Do you want to continue?';

                bulkForm.dataset.confirmMessage = message;
                bulkForm.dataset.confirmVariant = action === 'archive' ? 'btn-warning' : 'btn-primary';
            }

            bulkAction.addEventListener('change', updateValueFields);
            bulkAction.addEventListener('change', updateRequiredFields);
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
