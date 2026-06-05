<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SendWithdrawalLinkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('email')) {
            $this->merge(['email' => str($this->input('email'))->lower()->trim()->toString()]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'reference_no' => ['required', 'string', 'max:50'],
            'email' => ['required', 'email', 'max:255'],
            'company_website' => ['prohibited'],
        ];
    }
}
