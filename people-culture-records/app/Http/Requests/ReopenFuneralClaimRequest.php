<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReopenFuneralClaimRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('reopen', $this->route('funeral_claim')) ?? false;
    }

    public function rules(): array
    {
        return ['reason' => ['required', 'string', 'max:5000']];
    }
}
