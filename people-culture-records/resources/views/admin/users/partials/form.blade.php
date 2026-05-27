@php
    $selectedEmployeeId = old('employee_id', $user?->employee_id);
    $selectedEmployee = $employees->firstWhere('id', (int) $selectedEmployeeId) ?? $user?->employee;
@endphp

<div class="row g-4" data-user-form>
    <section class="col-12">
        <div class="border rounded-2 p-3 bg-light">
            <h2 class="h5 mb-1">Account Source</h2>
            <p class="text-muted small mb-3">Select an employee to create a login account for an existing staff member. Usually select People & Culture / HR employees only.</p>

            <div class="row g-3">
                <div class="col-lg-5">
                    <label for="employee_search" class="form-label">Search employees</label>
                    <input id="employee_search" type="search" class="form-control" placeholder="Filter by employee number, name, email, province, or department" data-employee-search>
                </div>
                <div class="col-lg-7">
                    <label for="employee_id" class="form-label">Employee</label>
                    <select id="employee_id" name="employee_id" class="form-select @error('employee_id') is-invalid @enderror" data-employee-select>
                        <option value="">No employee link / special account</option>
                        @foreach ($employees as $employee)
                            <option
                                value="{{ $employee->id }}"
                                data-search="{{ \Illuminate\Support\Str::lower($employee->employee_no.' '.$employee->full_name.' '.$employee->email.' '.$employee->province?->name.' '.$employee->department?->name.' '.$employee->jobTitle?->name) }}"
                                data-name="{{ $employee->full_name }}"
                                data-email="{{ $employee->email }}"
                                data-province-id="{{ $employee->province_id }}"
                                data-province-name="{{ $employee->province?->name }}"
                                data-employee-no="{{ $employee->employee_no }}"
                                data-department="{{ $employee->department?->name ?? '-' }}"
                                data-job-title="{{ $employee->jobTitle?->name ?? '-' }}"
                                @selected((string) $selectedEmployeeId === (string) $employee->id)
                            >
                                {{ $employee->employee_no }} - {{ $employee->full_name }}{{ $employee->email ? ' - '.$employee->email : '' }} - {{ $employee->province?->name ?? 'No province' }}{{ $employee->department?->name ? ' - '.$employee->department->name : '' }}
                            </option>
                        @endforeach
                    </select>
                    @error('employee_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>
    </section>

    <section class="col-12">
        <div class="border rounded-2 p-3" data-employee-summary>
            <div class="d-flex justify-content-between align-items-center gap-3 mb-3">
                <h2 class="h5 mb-0">Selected Employee Summary</h2>
                @if ($user?->employee)
                    <span class="badge text-bg-light">Currently linked</span>
                @endif
            </div>
            <div class="row g-3">
                <div class="col-md-3">
                    <div class="text-muted small">Employee No</div>
                    <div class="fw-semibold" data-summary-employee-no>{{ $selectedEmployee?->employee_no ?? '-' }}</div>
                </div>
                <div class="col-md-3">
                    <div class="text-muted small">Name</div>
                    <div class="fw-semibold" data-summary-name>{{ $selectedEmployee?->full_name ?? '-' }}</div>
                </div>
                <div class="col-md-3">
                    <div class="text-muted small">Email</div>
                    <div class="fw-semibold" data-summary-email>{{ $selectedEmployee?->email ?? '-' }}</div>
                </div>
                <div class="col-md-3">
                    <div class="text-muted small">Province</div>
                    <div class="fw-semibold" data-summary-province>{{ $selectedEmployee?->province?->name ?? '-' }}</div>
                </div>
                <div class="col-md-3">
                    <div class="text-muted small">Department</div>
                    <div class="fw-semibold" data-summary-department>{{ $selectedEmployee?->department?->name ?? '-' }}</div>
                </div>
                <div class="col-md-3">
                    <div class="text-muted small">Job Title</div>
                    <div class="fw-semibold" data-summary-job-title>{{ $selectedEmployee?->jobTitle?->name ?? '-' }}</div>
                </div>
            </div>
        </div>
    </section>

    <section class="col-12">
        <div class="border rounded-2 p-3">
            <h2 class="h5 mb-1">Account Details</h2>
            <p class="text-muted small mb-3">System role controls what this user can do. It is separate from the employee's HR job title.</p>

            <div class="row g-3">
                <div class="col-md-6">
                    <label for="name" class="form-label">Name</label>
                    <input id="name" name="name" type="text" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $user?->name) }}" required data-name-input>
                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6">
                    <label for="email" class="form-label">Email</label>
                    <input id="email" name="email" type="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $user?->email) }}" required data-email-input>
                    @error('email')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6">
                    <label for="role_id" class="form-label">System Role</label>
                    <select id="role_id" name="role_id" class="form-select @error('role_id') is-invalid @enderror" required data-role-select>
                        <option value="">Select access profile</option>
                        @foreach ($roles as $role)
                            <option value="{{ $role->id }}" data-requires-province="{{ in_array($role->name, ['HR Officer', 'Viewer'], true) ? '1' : '0' }}" @selected((string) old('role_id', $user?->role_id) === (string) $role->id)>
                                {{ $role->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('role_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6">
                    <label for="province_id" class="form-label">Province</label>
                    <select id="province_id" name="province_id" class="form-select @error('province_id') is-invalid @enderror" data-province-select>
                        <option value="">All provinces / HQ</option>
                        @foreach ($provinces as $province)
                            <option value="{{ $province->id }}" @selected((string) old('province_id', $user?->province_id) === (string) $province->id)>
                                {{ $province->name }}
                            </option>
                        @endforeach
                    </select>
                    <div class="form-text" data-province-help>Required for HR Officer and Viewer access profiles.</div>
                    @error('province_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6">
                    <label for="password" class="form-label">Password</label>
                    <input id="password" name="password" type="password" class="form-control @error('password') is-invalid @enderror" data-password-input>
                    @error('password')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <div class="form-text">{{ $user ? 'Leave blank to keep the current password.' : 'Leave blank when generating a temporary password automatically.' }}</div>
                </div>

                <div class="col-md-6">
                    <label for="password_confirmation" class="form-label">Confirm Password</label>
                    <input id="password_confirmation" name="password_confirmation" type="password" class="form-control" data-password-confirmation-input>
                </div>

                @unless ($user)
                    <div class="col-12">
                        <div class="form-check">
                            <input id="generate_password" name="generate_password" type="checkbox" class="form-check-input" value="1" @checked(old('generate_password', true)) data-generate-password-toggle>
                            <label for="generate_password" class="form-check-label">Generate temporary password automatically</label>
                        </div>
                        <div class="form-text">The temporary password will be shown once after saving, and the user will be required to change it on first login.</div>
                    </div>
                @endunless

                <div class="col-12">
                    <div class="form-check">
                        <input id="is_active" name="is_active" type="checkbox" class="form-check-input" value="1" @checked(old('is_active', $user?->is_active ?? true))>
                        <label for="is_active" class="form-check-label">Active</label>
                    </div>
                </div>

                <div class="col-12">
                    <div class="form-check">
                        <input id="must_change_password" name="must_change_password" type="checkbox" class="form-check-input" value="1" @checked(old('must_change_password', $user?->must_change_password ?? true))>
                        <label for="must_change_password" class="form-check-label">Require password change on next login</label>
                    </div>
                    <div class="form-text">Use this when creating an account with a temporary password or resetting a user's password.</div>
                </div>
            </div>
        </div>
    </section>

    <div class="col-12 d-flex gap-2">
        <button type="submit" class="btn btn-primary btn-md">Save User</button>
        <a href="{{ route('admin.users.index') }}" class="btn btn-secondary btn-md">Cancel</a>
    </div>
</div>

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const form = document.querySelector('[data-user-form]');

            if (!form) {
                return;
            }

            const employeeSelect = form.querySelector('[data-employee-select]');
            const employeeSearch = form.querySelector('[data-employee-search]');
            const nameInput = form.querySelector('[data-name-input]');
            const emailInput = form.querySelector('[data-email-input]');
            const provinceSelect = form.querySelector('[data-province-select]');
            const roleSelect = form.querySelector('[data-role-select]');
            const generatePasswordToggle = form.querySelector('[data-generate-password-toggle]');
            const passwordInput = form.querySelector('[data-password-input]');
            const passwordConfirmationInput = form.querySelector('[data-password-confirmation-input]');
            const summary = {
                employeeNo: form.querySelector('[data-summary-employee-no]'),
                name: form.querySelector('[data-summary-name]'),
                email: form.querySelector('[data-summary-email]'),
                province: form.querySelector('[data-summary-province]'),
                department: form.querySelector('[data-summary-department]'),
                jobTitle: form.querySelector('[data-summary-job-title]'),
            };

            function setText(element, value) {
                if (element) {
                    element.textContent = value || '-';
                }
            }

            function selectedEmployeeOption() {
                const option = employeeSelect?.selectedOptions[0];

                return option && option.value ? option : null;
            }

            function applyEmployeeSelection() {
                const option = selectedEmployeeOption();

                if (!option) {
                    Object.values(summary).forEach((element) => setText(element, '-'));
                    return;
                }

                setText(summary.employeeNo, option.dataset.employeeNo);
                setText(summary.name, option.dataset.name);
                setText(summary.email, option.dataset.email);
                setText(summary.province, option.dataset.provinceName);
                setText(summary.department, option.dataset.department);
                setText(summary.jobTitle, option.dataset.jobTitle);

                if (option.dataset.name) {
                    nameInput.value = option.dataset.name;
                }

                if (option.dataset.email) {
                    emailInput.value = option.dataset.email;
                }

                if (option.dataset.provinceId) {
                    provinceSelect.value = option.dataset.provinceId;
                }
            }

            function filterEmployees() {
                const search = (employeeSearch.value || '').trim().toLowerCase();

                employeeSelect.querySelectorAll('option[data-search]').forEach(function (option) {
                    const visible = !search || option.dataset.search.includes(search);
                    option.hidden = !visible;
                    option.disabled = !visible;
                });
            }

            function updateProvinceRequirement() {
                const option = roleSelect.selectedOptions[0];
                const required = option?.dataset.requiresProvince === '1';

                provinceSelect.required = required;
            }

            function updatePasswordFields() {
                if (!generatePasswordToggle || !passwordInput || !passwordConfirmationInput) {
                    return;
                }

                const generated = generatePasswordToggle.checked;
                passwordInput.disabled = generated;
                passwordConfirmationInput.disabled = generated;
                passwordInput.required = !generated;
                passwordConfirmationInput.required = !generated;

                if (generated) {
                    passwordInput.value = '';
                    passwordConfirmationInput.value = '';
                }
            }

            employeeSelect?.addEventListener('change', applyEmployeeSelection);
            employeeSearch?.addEventListener('input', filterEmployees);
            roleSelect?.addEventListener('change', updateProvinceRequirement);
            generatePasswordToggle?.addEventListener('change', updatePasswordFields);
            updateProvinceRequirement();
            updatePasswordFields();
        });
    </script>
@endpush
