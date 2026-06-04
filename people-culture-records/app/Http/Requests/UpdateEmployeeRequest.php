<?php

namespace App\Http\Requests;

use App\Models\District;
use App\Models\Employee;
use App\Models\Facility;
use App\Support\EmployeeNumber;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('employee')) ?? false;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('employee_no')) {
            $this->merge([
                'employee_no' => EmployeeNumber::normalize($this->input('employee_no')),
            ]);
        }

        if ($this->filled('supervisor_employee_id')) {
            $supervisor = Employee::query()->find($this->input('supervisor_employee_id'));

            if ($supervisor) {
                $this->merge([
                    'supervisor_name' => $supervisor->full_name,
                ]);
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'employee_no' => [
                'required',
                'string',
                'max:255',
                Rule::unique('employees', 'employee_no')->ignore($this->route('employee')?->id),
            ],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'gender' => ['nullable', 'string', 'max:50'],
            'date_of_birth' => ['nullable', 'date'],
            'national_id' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
            'project_id' => ['nullable', 'exists:projects,id'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'job_title_id' => ['nullable', 'exists:job_titles,id'],
            'province_id' => ['required', 'exists:provinces,id'],
            'district_id' => ['required', 'exists:districts,id'],
            'facility_id' => ['nullable', 'exists:facilities,id'],
            'employment_status_id' => ['nullable', 'exists:employment_statuses,id'],
            'hire_date' => ['nullable', 'date'],
            'supervisor_name' => ['nullable', 'string', 'max:255'],
            'supervisor_employee_id' => ['nullable', 'exists:employees,id'],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $this->validateLocation($validator);
            $this->validateOfficerProvince($validator);
            $this->validateSupervisor($validator);
        });
    }

    private function validateLocation($validator): void
    {
        if ($this->filled(['province_id', 'district_id'])) {
            $districtBelongsToProvince = District::whereKey($this->input('district_id'))
                ->where('province_id', $this->input('province_id'))
                ->exists();

            if (! $districtBelongsToProvince) {
                $validator->errors()->add('district_id', 'The selected district must belong to the selected province.');
            }
        }

        if ($this->filled(['district_id', 'facility_id'])) {
            $facilityBelongsToDistrict = Facility::whereKey($this->input('facility_id'))
                ->where('district_id', $this->input('district_id'))
                ->exists();

            if (! $facilityBelongsToDistrict) {
                $validator->errors()->add('facility_id', 'The selected facility must belong to the selected district.');
            }
        }
    }

    private function validateOfficerProvince($validator): void
    {
        $user = $this->user();

        if ($user?->hasRole('HR Officer') && (int) $this->input('province_id') !== (int) $user->province_id) {
            $validator->errors()->add('province_id', 'HR Officers cannot move employees outside their assigned province.');
        }
    }

    private function validateSupervisor($validator): void
    {
        if (! $this->filled('supervisor_employee_id')) {
            return;
        }

        $employee = $this->route('employee');

        if ($employee && (int) $this->input('supervisor_employee_id') === (int) $employee->id) {
            $validator->errors()->add('supervisor_employee_id', 'An employee cannot be their own line manager.');

            return;
        }

        $supervisorIsVisible = Employee::query()
            ->visibleTo($this->user())
            ->whereKey($this->input('supervisor_employee_id'))
            ->exists();

        if (! $supervisorIsVisible) {
            $validator->errors()->add('supervisor_employee_id', 'The selected line manager is not available to your province access.');
        }
    }
}
