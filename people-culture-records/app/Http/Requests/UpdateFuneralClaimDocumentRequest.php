<?php

namespace App\Http\Requests;

use App\Models\FuneralClaimDocument;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateFuneralClaimDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('funeral_claim')) ?? false;
    }

    public function rules(): array
    {
        return ['status' => ['required', Rule::in(array_keys(FuneralClaimDocument::statuses()))], 'notes' => ['nullable', 'string', 'max:3000']];
    }
}
