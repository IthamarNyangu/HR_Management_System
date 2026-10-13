<?php

namespace App\Http\Requests;

use App\Models\FuneralClaim;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateFuneralClaimStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('funeral_claim')) ?? false;
    }

    public function rules(): array
    {
        return ['status' => ['required', Rule::in(array_keys(FuneralClaim::statuses()))], 'comment' => ['nullable', 'string', 'max:2000']];
    }
}
