<?php

namespace App\Http\Requests;

use App\Models\JobOpening;
use Illuminate\Validation\Rule;

class UpdateJobOpeningRequest extends StoreJobOpeningRequest
{
    public function authorize(): bool
    {
        $jobOpening = $this->route('job_opening');

        return $jobOpening instanceof JobOpening
            && ($this->user()?->can('update', $jobOpening) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rules = parent::rules();
        $rules['status'] = ['nullable', Rule::in([
            JobOpening::STATUS_DRAFT,
            JobOpening::STATUS_PUBLISHED,
            JobOpening::STATUS_CLOSED,
            JobOpening::STATUS_CANCELLED,
        ])];

        return $rules;
    }
}
