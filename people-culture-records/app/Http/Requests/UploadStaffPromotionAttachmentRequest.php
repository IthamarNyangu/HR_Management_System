<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadStaffPromotionAttachmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('uploadAttachment', $this->route('staff_promotion')) ?? false;
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
