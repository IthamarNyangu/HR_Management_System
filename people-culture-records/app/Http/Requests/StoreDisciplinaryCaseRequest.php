<?php

namespace App\Http\Requests;

use App\Models\DisciplinaryCase;
use App\Models\District;
use App\Models\Employee;
use App\Models\Facility;
use Illuminate\Foundation\Http\FormRequest;

class StoreDisciplinaryCaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', DisciplinaryCase::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'employee_id' => ['required', 'exists:employees,id'],
            'project_id' => ['nullable', 'exists:projects,id'],
            'province_id' => ['required', 'exists:provinces,id'],
            'district_id' => ['required', 'exists:districts,id'],
            'facility_id' => ['nullable', 'exists:facilities,id'],
            'supervisor_name' => ['nullable', 'string', 'max:255'],
            'nature_of_offence' => ['required', 'string'],
            'offence_category_id' => ['nullable', 'exists:offence_categories,id'],
            'penalty_type_id' => ['nullable', 'exists:penalty_types,id'],
            'case_status_id' => ['required', 'exists:case_statuses,id'],
            'effective_date' => ['required', 'date'],
            'expiry_date' => ['nullable', 'date', 'after_or_equal:effective_date'],
            'comment' => ['nullable', 'string'],
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
            $validator->errors()->add('province_id', 'HR Officers can only create cases for their assigned province.');
        }
    }
}
