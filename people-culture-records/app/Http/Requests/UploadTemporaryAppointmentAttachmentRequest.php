<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadTemporaryAppointmentAttachmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('uploadAttachment', $this->route('temporary_appointment')) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'document' => ['required', 'file', 'mimes:pdf,doc,docx,jpg,jpeg,png', 'max:10240'],
        ];
    }
}
