<?php

namespace App\Http\Requests\Recruitment;

use App\Models\JobApplication;
use Illuminate\Foundation\Http\FormRequest;

class UpdateJobApplicationReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('review', $this->route('jobApplication')) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'qualification_score' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'experience_score' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'screening_score' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'review_notes' => ['nullable', 'string'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $application = $this->route('jobApplication');

            if ($application instanceof JobApplication && $application->isWithdrawn()) {
                $validator->errors()->add('review_notes', 'Withdrawn applications cannot be reviewed.');
            }
        });
    }
}
