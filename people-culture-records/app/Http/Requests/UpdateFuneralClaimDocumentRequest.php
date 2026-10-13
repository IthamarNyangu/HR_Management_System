<?php

namespace App\Http\Requests;

use App\Models\FuneralClaimDocument;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateFuneralClaimDocumentRequest extends FormRequest
{
    protected $errorBag = 'documents';

    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('funeral_claim')) ?? false;
    }

    public function rules(): array
    {
        return ['status' => ['required', Rule::in(array_keys(FuneralClaimDocument::statuses()))], 'notes' => ['nullable', 'string', 'max:3000']];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $document = $this->route('document');

            if ($this->input('status') === FuneralClaimDocument::RECEIVED
                && $document instanceof FuneralClaimDocument
                && ! $document->attachments()->exists()) {
                $validator->errors()->add("document_status_{$document->id}", 'Upload the supporting document before marking it as received.');
            }
        });
    }
}
