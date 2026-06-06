<?php

namespace App\Http\Requests;

use App\Models\District;
use App\Models\Employee;
use App\Models\EmploymentStatus;
use App\Models\Facility;
use App\Support\EmployeeNumber;
use Illuminate\Foundation\Http\FormRequest;

class StoreEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Employee::class) ?? false;
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
            'employee_no' => ['required', 'string', 'max:255', 'unique:employees,employee_no'],
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
            'termination_reason_id' => ['nullable', 'exists:termination_reasons,id'],
            'termination_date' => ['nullable', 'date'],
            'termination_comment' => ['nullable', 'string'],
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
            $this->validateSupervisorVisibility($validator);
            $this->validateEmploymentStatus($validator);
            $this->validateTerminationDetails($validator);
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
            $validator->errors()->add('province_id', 'HR Officers can only create employees for their assigned province.');
        }
    }

    private function validateSupervisorVisibility($validator): void
    {
        if (! $this->filled('supervisor_employee_id')) {
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

    private function validateTerminationDetails($validator): void
    {
        if (! $this->isTerminatedStatus($this->input('employment_status_id'))) {
            return;
        }

        if (! $this->filled('termination_reason_id')) {
            $validator->errors()->add('termination_reason_id', 'Choose a termination reason when employment status is Terminated.');
        }

        if (! $this->filled('termination_date')) {
            $validator->errors()->add('termination_date', 'Enter a termination date when employment status is Terminated.');
        }
    }

    private function validateEmploymentStatus($validator): void
    {
        if (! $this->filled('employment_status_id')) {
            return;
        }

        $isAllowed = EmploymentStatus::whereKey($this->input('employment_status_id'))
            ->where('is_active', true)
            ->whereIn('name', ['Active', 'Terminated'])
            ->exists();

        if (! $isAllowed) {
            $validator->errors()->add('employment_status_id', 'Employment status must be Active or Terminated.');
        }
    }

    private function isTerminatedStatus(null|int|string $statusId): bool
    {
        if (! $statusId) {
            return false;
        }

        return EmploymentStatus::whereKey($statusId)
            ->where(function ($query) {
                $query->where('code', 'TERMINATED')
                    ->orWhere('name', 'Terminated');
            })
            ->exists();
    }
}
