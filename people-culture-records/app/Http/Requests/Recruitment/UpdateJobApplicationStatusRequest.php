<?php

namespace App\Http\Requests\Recruitment;

use App\Models\JobApplication;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateJobApplicationStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('updateStatus', $this->route('jobApplication')) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status' => [
                'required',
                Rule::in([
                    JobApplication::STATUS_SUBMITTED,
                    JobApplication::STATUS_UNDER_REVIEW,
                    JobApplication::STATUS_LONGLISTED,
                ]),
            ],
            'comment' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $application = $this->route('jobApplication');

            if ($application instanceof JobApplication && $application->isWithdrawn()) {
                $validator->errors()->add('status', 'Withdrawn applications cannot be moved through the review workflow.');
            }
        });
    }
}
