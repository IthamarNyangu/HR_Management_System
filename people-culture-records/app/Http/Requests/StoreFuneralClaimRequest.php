<?php

namespace App\Http\Requests;

use App\Models\Employee;
use App\Models\EmployeeDependant;
use App\Models\FuneralClaim;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreFuneralClaimRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', FuneralClaim::class) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'employee_dependant_id' => $this->filled('employee_dependant_id') ? $this->input('employee_dependant_id') : null,
            'deceased_name' => $this->filled('deceased_name') ? trim((string) $this->input('deceased_name')) : null,
            'gestational_age_weeks' => $this->filled('gestational_age_weeks') ? $this->input('gestational_age_weeks') : null,
            'assigned_to' => $this->filled('assigned_to') ? $this->input('assigned_to') : null,
        ]);
    }

    public function rules(): array
    {
        return [
            'employee_id' => ['required', 'integer', 'exists:employees,id'],
            'employee_dependant_id' => ['nullable', 'integer', 'exists:employee_dependants,id'],
            'deceased_category' => ['required', Rule::in(array_keys(FuneralClaim::categories()))],
            'notification_date' => ['required', 'date', 'before_or_equal:today', 'after_or_equal:date_of_death'],
            'date_of_death' => ['required', 'date', 'before_or_equal:today'],
            'deceased_name' => ['nullable', 'string', 'max:255'],
            'deceased_date_of_birth' => ['nullable', 'date', 'before_or_equal:date_of_death'],
            'gestational_age_weeks' => ['nullable', 'numeric', 'min:1', 'max:45'],
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
            'internal_notes' => ['nullable', 'string', 'max:10000'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $employee = Employee::find($this->integer('employee_id'));
            if (! $employee || ! Employee::query()->visibleTo($this->user())->whereKey($employee)->exists()) {
                $validator->errors()->add('employee_id', 'You cannot register a claim for this employee.');

                return;
            }
            $category = $this->input('deceased_category');
            $dependant = $this->filled('employee_dependant_id') ? EmployeeDependant::find($this->integer('employee_dependant_id')) : null;
            if ($category === FuneralClaim::CATEGORY_EMPLOYEE && $dependant) {
                $validator->errors()->add('employee_dependant_id', 'An employee claim cannot be linked to a dependant.');
            }
            if ($dependant && ($dependant->employee_id !== $employee->id || $dependant->category !== $category || ! $dependant->is_active)) {
                $validator->errors()->add('employee_dependant_id', 'Select an active dependant in the matching category.');
            }
            if ($dependant && $this->isNewDependantSelection($dependant)) {
                $existingClaim = FuneralClaim::query()
                    ->where('employee_dependant_id', $dependant->id)
                    ->latest('id')
                    ->first();

                if ($existingClaim) {
                    $validator->errors()->add(
                        'employee_dependant_id',
                        "A funeral claim already exists for this dependant ({$existingClaim->reference_no}). Open the existing claim instead."
                    );
                }
            }
            if ($category !== FuneralClaim::CATEGORY_EMPLOYEE && ! $dependant && ! $this->filled('deceased_name')) {
                $validator->errors()->add('deceased_name', 'Enter the deceased person name when no registered dependant is selected.');
            }
            if ($category !== FuneralClaim::CATEGORY_EMPLOYEE && ! $dependant && $this->filled('deceased_name') && $this->isNewManualDeceasedIdentity()) {
                $existingClaim = FuneralClaim::query()
                    ->where('employee_id', $employee->id)
                    ->where('deceased_category', $category)
                    ->where('deceased_name', trim((string) $this->input('deceased_name')))
                    ->whereDate('date_of_death', $this->input('date_of_death'))
                    ->latest('id')
                    ->first();

                if ($existingClaim) {
                    $validator->errors()->add(
                        'deceased_name',
                        "A matching funeral claim already exists ({$existingClaim->reference_no}). Open the existing claim instead."
                    );
                }
            }
            if ($category === FuneralClaim::CATEGORY_CHILD && ! $dependant?->date_of_birth && ! $this->filled('deceased_date_of_birth') && ! $this->filled('gestational_age_weeks')) {
                $validator->errors()->add('deceased_date_of_birth', 'Enter a date of birth or gestational age to assess child eligibility.');
            }
        });
    }

    private function isNewDependantSelection(EmployeeDependant $dependant): bool
    {
        $claim = $this->route('funeral_claim');

        return ! $claim instanceof FuneralClaim || $claim->employee_dependant_id !== $dependant->id;
    }

    private function isNewManualDeceasedIdentity(): bool
    {
        $claim = $this->route('funeral_claim');

        if (! $claim instanceof FuneralClaim) {
            return true;
        }

        return $claim->employee_dependant_id !== null
            || $claim->employee_id !== $this->integer('employee_id')
            || $claim->deceased_category !== $this->input('deceased_category')
            || $claim->deceased_name !== trim((string) $this->input('deceased_name'))
            || $claim->date_of_death?->format('Y-m-d') !== $this->input('date_of_death');
    }
}
