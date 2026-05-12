<?php

namespace App\Http\Requests;

use App\Models\District;
use App\Models\Employee;
use App\Models\Facility;
use Illuminate\Foundation\Http\FormRequest;

class UpdateStaffRelocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('staff_relocation')) ?? false;
    }

    protected function prepareForValidation(): void
    {
        if (! $this->filled('employee_id')) {
            return;
        }

        $employee = Employee::find($this->input('employee_id'));

        if (! $employee) {
            return;
        }

        $this->merge([
            'job_title_id' => $this->input('job_title_id') ?: $employee->job_title_id,
            'project_id' => $this->input('project_id') ?: $employee->project_id,
            'department_id' => $this->input('department_id') ?: $employee->department_id,
            'from_province_id' => $this->input('from_province_id') ?: $employee->province_id,
            'from_district_id' => $this->input('from_district_id') ?: $employee->district_id,
            'from_facility_id' => $this->input('from_facility_id') ?: $employee->facility_id,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'employee_id' => ['required', 'exists:employees,id'],
            'job_title_id' => ['nullable', 'exists:job_titles,id'],
            'project_id' => ['nullable', 'exists:projects,id'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'from_province_id' => ['required', 'exists:provinces,id'],
            'from_district_id' => ['required', 'exists:districts,id'],
            'from_facility_id' => ['nullable', 'exists:facilities,id'],
            'to_province_id' => ['required', 'exists:provinces,id'],
            'to_district_id' => ['required', 'exists:districts,id'],
            'to_facility_id' => ['nullable', 'exists:facilities,id'],
            'relocation_reason_id' => ['nullable', 'exists:relocation_reasons,id'],
            'effective_date' => ['required', 'date'],
            'relocation_amount' => ['nullable', 'numeric', 'min:0'],
            'comment' => ['nullable', 'string'],
            'supporting_document' => ['nullable', 'file', 'mimes:pdf,doc,docx,jpg,jpeg,png', 'max:10240'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $this->validateLocations($validator);
            $this->validateEmployeeSourceProvince($validator);
            $this->validateOfficerProvince($validator);
        });
    }

    private function validateLocations($validator): void
    {
        if ($this->filled(['from_province_id', 'from_district_id'])) {
            $valid = District::whereKey($this->input('from_district_id'))
                ->where('province_id', $this->input('from_province_id'))
                ->exists();

            if (! $valid) {
                $validator->errors()->add('from_district_id', 'The selected from district must belong to the selected from province.');
            }
        }

        if ($this->filled(['from_district_id', 'from_facility_id'])) {
            $valid = Facility::whereKey($this->input('from_facility_id'))
                ->where('district_id', $this->input('from_district_id'))
                ->exists();

            if (! $valid) {
                $validator->errors()->add('from_facility_id', 'The selected from facility must belong to the selected from district.');
            }
        }

        if ($this->filled(['to_province_id', 'to_district_id'])) {
            $valid = District::whereKey($this->input('to_district_id'))
                ->where('province_id', $this->input('to_province_id'))
                ->exists();

            if (! $valid) {
                $validator->errors()->add('to_district_id', 'The selected to district must belong to the selected to province.');
            }
        }

        if ($this->filled(['to_district_id', 'to_facility_id'])) {
            $valid = Facility::whereKey($this->input('to_facility_id'))
                ->where('district_id', $this->input('to_district_id'))
                ->exists();

            if (! $valid) {
                $validator->errors()->add('to_facility_id', 'The selected to facility must belong to the selected to district.');
            }
        }
    }

    private function validateEmployeeSourceProvince($validator): void
    {
        $user = $this->user();

        if (! $user?->hasRole('HR Officer') || ! $this->filled(['employee_id', 'from_province_id'])) {
            return;
        }

        $valid = Employee::whereKey($this->input('employee_id'))
            ->where('province_id', $this->input('from_province_id'))
            ->exists();

        if (! $valid) {
            $validator->errors()->add('employee_id', 'The selected employee must belong to the selected from province for HR Officer initiated relocations.');
        }
    }

    private function validateOfficerProvince($validator): void
    {
        $user = $this->user();

        if (! $user?->hasRole('HR Officer')) {
            return;
        }

        $assignedProvince = (int) $user->province_id;
        $fromProvince = (int) $this->input('from_province_id');
        $toProvince = (int) $this->input('to_province_id');

        if ($assignedProvince !== $fromProvince && $assignedProvince !== $toProvince) {
            $validator->errors()->add('to_province_id', 'HR Officers can only manage relocations where their assigned province is either the from province or the to province.');
        }
    }
}
