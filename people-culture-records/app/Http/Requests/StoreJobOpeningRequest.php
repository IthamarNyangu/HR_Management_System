<?php

namespace App\Http\Requests;

use App\Models\District;
use App\Models\Facility;
use App\Models\JobOpening;
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
        $this->merge([
            'show_number_of_positions' => $this->boolean('show_number_of_positions'),
            'status' => $this->input('status') ?: JobOpening::STATUS_DRAFT,
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
            'title' => ['required', 'string', 'max:255'],
            'job_title_id' => ['nullable', 'exists:job_titles,id'],
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
            'description' => ['nullable', 'string'],
            'responsibilities' => ['nullable', 'string'],
            'requirements' => ['nullable', 'string'],
            'qualifications' => ['nullable', 'string'],
            'experience_required' => ['nullable', 'string'],
            'contract_details' => ['nullable', 'string'],
            'work_level' => ['nullable', 'string'],
            'location_details' => ['nullable', 'string'],
            'application_instructions' => ['nullable', 'string'],
            'opening_date' => ['nullable', 'date'],
            'closing_date' => ['required', 'date', 'after_or_equal:today'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $this->validateLocation($validator);
            $this->validateOfficerProvince($validator);
        });
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
}
