<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DecideFuneralClaimRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('approve', $this->route('funeral_claim')) ?? false;
    }

    public function rules(): array
    {
        return ['decision' => ['required', Rule::in(['approve', 'reject'])], 'grant_amount_approved' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'], 'comment' => ['required', 'string', 'max:5000']];
    }
}
