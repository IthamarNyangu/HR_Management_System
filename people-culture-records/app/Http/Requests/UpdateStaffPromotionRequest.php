<?php

namespace App\Http\Requests;

use App\Models\District;
use App\Models\Employee;
use App\Models\Facility;
use Illuminate\Foundation\Http\FormRequest;

class UpdateStaffPromotionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('staff_promotion')) ?? false;
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
            'old_job_title_id' => $this->input('old_job_title_id') ?: $employee->job_title_id,
            'province_id' => $this->input('province_id') ?: $employee->province_id,
            'district_id' => $this->input('district_id') ?: $employee->district_id,
            'facility_id' => $this->input('facility_id') ?: $employee->facility_id,
            'project_id' => $this->input('project_id') ?: $employee->project_id,
            'department_id' => $this->input('department_id') ?: $employee->department_id,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'employee_id' => ['required', 'exists:employees,id'],
            'province_id' => ['required', 'exists:provinces,id'],
            'district_id' => ['nullable', 'exists:districts,id'],
            'facility_id' => ['nullable', 'exists:facilities,id'],
            'project_id' => ['nullable', 'exists:projects,id'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'old_job_title_id' => ['nullable', 'exists:job_titles,id'],
            'new_job_title_id' => ['required', 'exists:job_titles,id'],
            'promotion_type_id' => ['nullable', 'exists:promotion_types,id'],
            'promotion_date' => ['required', 'date'],
            'effective_date' => ['nullable', 'date'],
            'comment' => ['nullable', 'string'],
            'supporting_document' => ['nullable', 'file', 'mimes:pdf,doc,docx,jpg,jpeg,png', 'max:10240'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $this->validateLocation($validator);
            $this->validateEmployeeProvince($validator);
            $this->validateOfficerProvince($validator);
        });
    }

    private function validateLocation($validator): void
    {
        if ($this->filled(['province_id', 'district_id'])) {
            $valid = District::whereKey($this->input('district_id'))
                ->where('province_id', $this->input('province_id'))
                ->exists();

            if (! $valid) {
                $validator->errors()->add('district_id', 'The selected district must belong to the selected province.');
            }
        }

        if ($this->filled(['district_id', 'facility_id'])) {
            $valid = Facility::whereKey($this->input('facility_id'))
                ->where('district_id', $this->input('district_id'))
                ->exists();

            if (! $valid) {
                $validator->errors()->add('facility_id', 'The selected facility must belong to the selected district.');
            }
        }
    }

    private function validateEmployeeProvince($validator): void
    {
        if (! $this->filled(['employee_id', 'province_id'])) {
            return;
        }

        $valid = Employee::whereKey($this->input('employee_id'))
            ->where('province_id', $this->input('province_id'))
            ->exists();

        if (! $valid) {
            $validator->errors()->add('employee_id', 'The selected employee must belong to the selected province.');
        }
    }

    private function validateOfficerProvince($validator): void
    {
        $user = $this->user();

        if ($user?->hasRole('HR Officer') && (int) $this->input('province_id') !== (int) $user->province_id) {
            $validator->errors()->add('province_id', 'HR Officers can only update promotions for their assigned province.');
        }
    }
}
