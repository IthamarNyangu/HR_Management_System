<?php

namespace App\Http\Requests\Recruitment;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SendJobApplicationEmailRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('sendEmail', $this->route('jobApplication')) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'email_type' => ['required', Rule::in(['shortlisted', 'rejected'])],
        ];
    }
}
