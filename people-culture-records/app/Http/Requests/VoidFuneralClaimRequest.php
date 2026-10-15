<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VoidFuneralClaimRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('void', $this->route('funeral_claim')) ?? false;
    }

    public function rules(): array
    {
        return [
            'void_reason' => ['required', 'string', 'max:5000'],
        ];
    }
}
