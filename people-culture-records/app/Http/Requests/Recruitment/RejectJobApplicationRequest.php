<?php

namespace App\Http\Requests\Recruitment;

use App\Models\JobApplication;
use Illuminate\Foundation\Http\FormRequest;

class RejectJobApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('reject', $this->route('jobApplication')) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'rejection_reason' => ['required', 'string', 'max:5000'],
            'comment' => ['nullable', 'string', 'max:2000'],
            'send_email' => ['nullable', 'boolean'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $application = $this->route('jobApplication');

            if ($application instanceof JobApplication && $application->isWithdrawn()) {
                $validator->errors()->add('rejection_reason', 'Withdrawn applications cannot be rejected.');
            }
        });
    }
}
