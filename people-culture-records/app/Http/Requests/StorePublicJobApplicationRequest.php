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
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('job_applications', 'email')
                    ->where(fn ($query) => $query->where('job_opening_id', $jobOpening->id)),
            ],
            'phone' => ['required', 'string', 'max:50'],
            'national_id' => ['nullable', 'string', 'max:100'],
            'province' => ['nullable', 'string', 'max:255'],
            'district' => ['nullable', 'string', 'max:255'],
            'highest_qualification' => ['required', 'string', 'max:255'],
            'field_of_study' => ['nullable', 'string', 'max:255'],
            'years_of_experience' => ['nullable', 'numeric', 'min:0', 'max:80'],
            'current_employer' => ['nullable', 'string', 'max:255'],
            'motivation' => ['required', 'string'],
            'cv' => ['required', 'file', 'mimes:pdf,doc,docx', 'max:10240'],
            'cover_letter' => ['required', 'file', 'mimes:pdf,doc,docx', 'max:10240'],
            'education_certificates' => ['required', 'file', 'mimes:pdf', 'max:10240'],
            'supporting_documents' => ['nullable', 'array', 'max:4'],
            'supporting_documents.*' => ['file', 'mimes:pdf,doc,docx,jpg,jpeg,png', 'max:10240'],
            'consent' => ['accepted'],
            'company_website' => ['prohibited'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.unique' => 'An application with this email address has already been submitted for this job.',
            'education_certificates.mimes' => 'Education certificates must be uploaded as one combined PDF file.',
            'supporting_documents.max' => 'You may upload up to 4 additional supporting documents.',
            'company_website.prohibited' => 'The application could not be submitted.',
        ];
    }
}
