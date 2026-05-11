<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadDisciplinaryCaseAttachmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('uploadAttachment', $this->route('disciplinary_case')) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'document_type_id' => ['nullable', 'exists:document_types,id'],
            'document' => ['required', 'file', 'mimes:pdf,doc,docx,jpg,jpeg,png', 'max:5120'],
        ];
    }
}
