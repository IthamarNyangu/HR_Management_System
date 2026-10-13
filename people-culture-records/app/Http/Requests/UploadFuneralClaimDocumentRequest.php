<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadFuneralClaimDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('funeral_claim')) ?? false;
    }

    public function rules(): array
    {
        return ['document' => ['required', 'file', 'mimes:pdf,doc,docx,jpg,jpeg,png', 'max:10240']];
    }
}
