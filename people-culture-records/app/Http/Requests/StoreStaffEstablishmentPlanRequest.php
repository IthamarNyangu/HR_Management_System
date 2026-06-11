<?php

namespace App\Http\Requests;

use App\Models\District;
use App\Models\StaffEstablishmentPlan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStaffEstablishmentPlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', StaffEstablishmentPlan::class) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $effectiveMonth = $this->input('effective_month');

        if (is_string($effectiveMonth) && preg_match('/^\d{4}-\d{2}$/', $effectiveMonth)) {
            $effectiveMonth .= '-01';
        }

        $this->merge([
            'effective_month' => $effectiveMonth,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'project_id' => ['nullable', 'exists:projects,id'],
            'status' => ['required', Rule::in(StaffEstablishmentPlan::STATUSES)],
            'effective_month' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
            'matrix_generated' => ['nullable', 'boolean'],
            'matrix_created_count' => ['nullable', 'integer', 'min:0'],
            'matrix_updated_count' => ['nullable', 'integer', 'min:0'],
            'matrix_skipped_count' => ['nullable', 'integer', 'min:0'],
            'matrix_selected_job_titles' => ['nullable', 'string', 'max:10000'],
            'matrix_selected_locations' => ['nullable', 'string', 'max:10000'],
            'lines' => ['nullable', 'array'],
            'lines.*.id' => ['nullable', 'integer', 'exists:staff_establishment_lines,id'],
            'lines.*.job_title_id' => ['required', 'exists:job_titles,id'],
            'lines.*.province_id' => ['nullable', 'exists:provinces,id'],
            'lines.*.district_id' => ['nullable', 'exists:districts,id'],
            'lines.*.department_id' => ['nullable', 'exists:departments,id'],
            'lines.*.budgeted_positions' => ['required', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'lines.required' => 'Add at least one establishment line.',
            'lines.*.job_title_id.required' => 'Each establishment line needs a job title.',
            'lines.*.budgeted_positions.required' => 'Each establishment line needs a budgeted position count.',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $this->validateApprovedPlanHasLines($validator);
            $this->validateLocations($validator);
            $this->validateDuplicateLines($validator);
        });
    }

    protected function validateApprovedPlanHasLines($validator): void
    {
        if ($this->input('status') !== StaffEstablishmentPlan::STATUS_APPROVED) {
            return;
        }

        if (count($this->input('lines', [])) === 0) {
            $validator->errors()->add('lines', 'Add at least one establishment line before approving this plan.');
        }
    }

    protected function validateLocations($validator): void
    {
        foreach ($this->input('lines', []) as $index => $line) {
            $provinceId = $line['province_id'] ?? null;
            $districtId = $line['district_id'] ?? null;

            if ($districtId && ! $provinceId) {
                $validator->errors()->add("lines.{$index}.province_id", 'Choose a province before selecting a district.');
            }

            if ($provinceId && $districtId && ! District::whereKey($districtId)->where('province_id', $provinceId)->exists()) {
                $validator->errors()->add("lines.{$index}.district_id", 'The selected district must belong to the selected province.');
            }
        }
    }

    protected function validateDuplicateLines($validator): void
    {
        $seen = [];

        foreach ($this->input('lines', []) as $index => $line) {
            $key = implode('|', [
                $line['job_title_id'] ?? '',
                $line['province_id'] ?? '',
                $line['district_id'] ?? '',
                $line['department_id'] ?? '',
            ]);

            if (isset($seen[$key])) {
                $validator->errors()->add("lines.{$index}.job_title_id", 'This establishment line is already listed. Change the job title, location, or department.');
                continue;
            }

            $seen[$key] = true;
        }
    }
}
