<?php

namespace App\Http\Requests;

use App\Models\District;
use App\Models\Employee;
use App\Models\Facility;
use App\Models\TemporaryAppointment;
use Illuminate\Foundation\Http\FormRequest;

class StoreTemporaryAppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', TemporaryAppointment::class) ?? false;
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

        $supervisor = $this->filled('supervisor_employee_id')
            ? Employee::find($this->input('supervisor_employee_id'))
            : null;

        $this->merge([
            'current_job_title_id' => $this->input('current_job_title_id') ?: $employee->job_title_id,
            'province_id' => $this->input('province_id') ?: $employee->province_id,
            'district_id' => $this->input('district_id') ?: $employee->district_id,
            'facility_id' => $this->input('facility_id') ?: $employee->facility_id,
            'project_id' => $this->input('project_id') ?: $employee->project_id,
            'department_id' => $this->input('department_id') ?: $employee->department_id,
            'supervisor_name' => $supervisor?->full_name ?: ($this->input('supervisor_name') ?: $employee->supervisor_name),
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
            'current_job_title_id' => ['nullable', 'exists:job_titles,id'],
            'temporary_job_title_id' => ['required', 'exists:job_titles,id'],
            'appointment_type_id' => ['nullable', 'exists:appointment_types,id'],
            'appointment_status_id' => ['required', 'exists:appointment_statuses,id'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'reason' => ['nullable', 'string'],
            'supervisor_name' => ['nullable', 'string', 'max:255'],
            'supervisor_employee_id' => ['nullable', 'exists:employees,id'],
            'comment' => ['nullable', 'string'],
            'supporting_document' => ['nullable', 'file', 'mimes:pdf,doc,docx,jpg,jpeg,png', 'max:10240'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $this->validateLocation($validator);
            $this->validateEmployeeProvince($validator);
            $this->validateSupervisorVisibility($validator);
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
            $validator->errors()->add('province_id', 'HR Officers can only manage temporary appointments for their assigned province.');
        }
    }

    private function validateSupervisorVisibility($validator): void
    {
        if (! $this->filled('supervisor_employee_id')) {
            return;
        }

        $visible = Employee::query()
            ->visibleTo($this->user())
            ->whereKey($this->input('supervisor_employee_id'))
            ->exists();

        if (! $visible) {
            $validator->errors()->add('supervisor_employee_id', 'The selected supervisor is not available to your province access.');
        }
    }
}
