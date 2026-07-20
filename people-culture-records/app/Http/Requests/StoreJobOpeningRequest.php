<?php

namespace App\Http\Requests;

use App\Models\District;
use App\Models\Facility;
use App\Models\JobOpening;
use App\Models\JobTitle;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreJobOpeningRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', JobOpening::class) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $jobTitleName = $this->filled('job_title_id')
            ? JobTitle::whereKey($this->input('job_title_id'))->value('name')
            : null;
        $reportingTo = $this->input('reporting_to_job_title_id');

        $this->merge([
            'show_number_of_positions' => $this->boolean('show_number_of_positions'),
            'reporting_to_tba' => $reportingTo === 'tba',
            'reporting_to_job_title_id' => $reportingTo === 'tba' || blank($reportingTo) ? null : $reportingTo,
            'status' => $this->input('status') ?: JobOpening::STATUS_DRAFT,
            'title' => $jobTitleName ?: $this->input('title'),
        ]);

        if ($this->user()?->hasRole('HR Officer')) {
            $this->merge([
                'province_id' => $this->user()->province_id,
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['nullable', 'string', 'max:255'],
            'job_title_id' => ['required', 'exists:job_titles,id'],
            'project_id' => ['nullable', 'exists:projects,id'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'province_id' => ['nullable', 'exists:provinces,id'],
            'district_id' => ['nullable', 'exists:districts,id'],
            'facility_id' => ['nullable', 'exists:facilities,id'],
            'employment_type_id' => ['nullable', 'exists:employment_types,id'],
            'visibility' => ['required', Rule::in([
                JobOpening::VISIBILITY_EXTERNAL,
                JobOpening::VISIBILITY_INTERNAL,
                JobOpening::VISIBILITY_BOTH,
            ])],
            'status' => ['nullable', Rule::in([
                JobOpening::STATUS_DRAFT,
                JobOpening::STATUS_PUBLISHED,
                JobOpening::STATUS_CLOSED,
                JobOpening::STATUS_CANCELLED,
            ])],
            'number_of_positions' => ['nullable', 'integer', 'min:1'],
            'show_number_of_positions' => ['boolean'],
            'contract_duration' => ['nullable', 'string', 'max:255'],
            'job_grade' => ['nullable', 'string', 'max:255'],
            'reporting_to_job_title_id' => ['nullable', 'exists:job_titles,id'],
            'reporting_to_tba' => ['boolean'],
            'description' => ['nullable', 'string'],
            'responsibilities' => ['nullable', 'string'],
            'requirements' => ['nullable', 'string'],
            'qualifications' => ['nullable', 'string'],
            'experience_required' => ['nullable', 'string'],
            'contract_details' => ['nullable', 'string'],
            'work_level' => ['nullable', 'string'],
            'location_details' => ['nullable', 'string'],
            'application_instructions' => ['nullable', 'string'],
            'opening_date' => ['required', 'date'],
            'closing_date' => ['required', 'date', 'after_or_equal:today'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'opening_date.required' => 'Date advertised is required.',
            'closing_date.after_or_equal' => 'Closing date cannot be before today.',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $this->validateLocation($validator);
            $this->validateOfficerProvince($validator);
            $this->validateReportingTo($validator);
            $this->validateDateRange($validator);
        });
    }

    protected function validateDateRange($validator): void
    {
        if (! $this->filled(['opening_date', 'closing_date'])) {
            return;
        }

        try {
            $openingDate = \Illuminate\Support\Carbon::parse($this->input('opening_date'))->startOfDay();
            $closingDate = \Illuminate\Support\Carbon::parse($this->input('closing_date'))->startOfDay();
        } catch (\Throwable) {
            return;
        }

        if ($closingDate->lt($openingDate)) {
            $validator->errors()->add('closing_date', 'Closing date must be on or after Date Advertised.');
        }
    }

    protected function validateLocation($validator): void
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

    protected function validateOfficerProvince($validator): void
    {
        $user = $this->user();

        if (! $user?->hasRole('HR Officer')) {
            return;
        }

        if (! $user->province_id || (int) $this->input('province_id') !== (int) $user->province_id) {
            $validator->errors()->add('province_id', 'HR Officers can only create jobs for their assigned province.');
        }
    }

    protected function validateReportingTo($validator): void
    {
        if (! $this->boolean('reporting_to_tba') && blank($this->input('reporting_to_job_title_id'))) {
            $validator->errors()->add('reporting_to_job_title_id', 'Select the reporting job title or choose TBA.');
        }
    }
}
