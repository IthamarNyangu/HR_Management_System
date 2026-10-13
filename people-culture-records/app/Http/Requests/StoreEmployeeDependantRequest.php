<?php

namespace App\Http\Requests;

use App\Models\Employee;
use App\Models\EmployeeDependant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEmployeeDependantRequest extends FormRequest
{
    public function authorize(): bool
    {
        $employee = $this->route('employee');

        return $employee instanceof Employee
            && ($this->user()?->can('create', [EmployeeDependant::class, $employee]) ?? false);
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'full_name' => trim((string) $this->input('full_name')),
            'national_id' => $this->filled('national_id') ? trim((string) $this->input('national_id')) : null,
            'relationship_detail' => $this->filled('relationship_detail') ? $this->input('relationship_detail') : null,
            'is_active' => $this->boolean('is_active'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'category' => ['required', Rule::in(array_keys(EmployeeDependant::categories()))],
            'relationship_detail' => ['nullable', Rule::in(array_keys(EmployeeDependant::relationshipDetails()))],
            'full_name' => ['required', 'string', 'max:255'],
            'gender' => ['nullable', 'string', 'max:50'],
            'date_of_birth' => ['nullable', 'date', 'before_or_equal:today'],
            'national_id' => ['nullable', 'string', 'max:255'],
            'relationship_start_date' => ['nullable', 'required_with:relationship_end_date', 'date'],
            'relationship_end_date' => ['nullable', 'date', 'after_or_equal:relationship_start_date'],
            'verification_status' => ['required', Rule::in(array_keys(EmployeeDependant::verificationStatuses()))],
            'is_active' => ['required', 'boolean'],
            'internal_notes' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $category = $this->input('category');
            $detail = $this->input('relationship_detail');

            if ($category === EmployeeDependant::CATEGORY_SPOUSE && $detail !== 'spouse') {
                $validator->errors()->add('relationship_detail', 'A spouse must use the spouse relationship detail.');
            }

            if ($category === EmployeeDependant::CATEGORY_CHILD && ! in_array($detail, ['biological_child', 'adopted_child', 'stepchild', 'other'], true)) {
                $validator->errors()->add('relationship_detail', 'Select the type of child relationship.');
            }
        });
    }
}
