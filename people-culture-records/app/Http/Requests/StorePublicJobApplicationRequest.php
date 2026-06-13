<?php

namespace App\Http\Requests;

use App\Models\JobApplication;
use App\Models\JobOpening;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePublicJobApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $jobOpening = $this->route('jobOpening');

        return $jobOpening instanceof JobOpening && $jobOpening->is_publicly_applyable;
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
        /** @var JobOpening $jobOpening */
        $jobOpening = $this->route('jobOpening');

        return [
            'title' => ['required', 'string', Rule::in(['Mr', 'Mrs', 'Miss', 'Sir', 'Doctor', 'Professor', 'Advocate', 'Judge', 'Pastor', 'Rabbi', 'Reverend'])],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('job_applications', 'email')
                    ->where(fn ($query) => $query
                        ->where('job_opening_id', $jobOpening->id)
                        ->where('status', '!=', JobApplication::STATUS_WITHDRAWN)),
            ],
            'phone' => ['required', 'string', 'max:50', 'regex:/^[0-9]+$/'],
            'national_id' => ['nullable', 'string', 'max:100'],
            'gender' => ['required', 'string', Rule::in(['Male', 'Female', 'Other'])],
            'disability' => ['required', 'string', Rule::in(['Yes', 'No'])],
            'highest_qualification' => ['required', 'string', Rule::in(JobApplication::HIGHEST_QUALIFICATIONS)],
            'field_of_study' => ['nullable', 'string', 'max:255'],
            'years_of_experience' => ['nullable', 'numeric', 'min:0', 'max:80'],
            'current_employer' => ['nullable', 'string', 'max:255'],
            'motivation' => ['required', 'string'],
            'cv' => ['required', 'file', 'mimes:pdf,doc,docx', 'max:5120'],
            'cover_letter' => ['required', 'file', 'mimes:pdf,doc,docx', 'max:5120'],
            'education_certificates' => ['required', 'file', 'mimes:pdf', 'max:10240'],
            'supporting_documents' => ['nullable', 'array', 'max:4'],
            'supporting_documents.*' => ['file', 'mimes:pdf,doc,docx,jpg,jpeg,png', 'max:10240'],
            'privacy_consent' => ['required', Rule::in(['yes'])],
            'terms_confirmed' => ['required', Rule::in(['yes'])],
            'company_website' => ['prohibited'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.unique' => 'An active application with this email address already exists for this job. You can apply again only after withdrawing the earlier application.',
            'phone.regex' => 'Phone must contain numbers only.',
            'education_certificates.mimes' => 'Education certificates must be uploaded as one combined PDF file.',
            'cv.max' => 'CV must not be larger than 5 MB.',
            'cover_letter.max' => 'Cover letter must not be larger than 5 MB.',
            'supporting_documents.max' => 'You may upload up to 4 additional supporting documents.',
            'privacy_consent.in' => 'You must consent to personal information processing before submitting.',
            'terms_confirmed.in' => 'You must confirm the application terms before submitting.',
            'company_website.prohibited' => 'The application could not be submitted.',
        ];
    }
}
