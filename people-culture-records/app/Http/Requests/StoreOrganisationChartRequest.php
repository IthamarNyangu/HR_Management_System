<?php

namespace App\Http\Requests;

use App\Models\OrganisationChart;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOrganisationChartRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', OrganisationChart::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'project_id' => ['nullable', 'exists:projects,id'],
            'status' => ['required', Rule::in(OrganisationChart::STATUSES)],
            'effective_date' => ['nullable', 'date'],
            'description' => ['nullable', 'string'],
        ];
    }
}
