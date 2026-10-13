<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateFuneralClaimProcessingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('funeral_claim')) ?? false;
    }

    public function rules(): array
    {
        return [
            'family_meeting_status' => ['required', Rule::in(['not_required', 'required', 'scheduled', 'completed'])],
            'family_meeting_scheduled_at' => ['nullable', 'date'],
            'family_meeting_completed_at' => ['nullable', 'date', 'after_or_equal:family_meeting_scheduled_at'],
            'family_meeting_outcome' => ['nullable', 'string', 'max:5000'],
            'grant_consent_confirmed' => ['nullable', 'boolean'],
            'grant_consent_date' => ['nullable', 'date', 'before_or_equal:today'],
            'banking_details_verified' => ['nullable', 'boolean'],
            'payreq_reference' => ['nullable', 'string', 'max:100'],
            'payreq_raised_date' => ['nullable', 'date', 'before_or_equal:today'],
            'payreq_status' => ['required', Rule::in(['not_raised', 'pending', 'approved', 'rejected'])],
            'payreq_approved_date' => ['nullable', 'date', 'before_or_equal:today', 'after_or_equal:payreq_raised_date'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $claim = $this->route('funeral_claim');
            if ($claim?->family_meeting_required && $this->input('family_meeting_status') === 'not_required') {
                $validator->errors()->add('family_meeting_status', 'A family meeting is required for this former employee dependant claim.');
            }
            if ($this->input('family_meeting_status') === 'scheduled' && ! $this->filled('family_meeting_scheduled_at')) {
                $validator->errors()->add('family_meeting_scheduled_at', 'Enter the scheduled meeting date and time.');
            }
            if ($this->input('family_meeting_status') === 'completed' && (! $this->filled('family_meeting_completed_at') || ! $this->filled('family_meeting_outcome'))) {
                $validator->errors()->add('family_meeting_outcome', 'Record the completed meeting date and outcome.');
            }
            if ($this->boolean('grant_consent_confirmed') && ! $this->filled('grant_consent_date')) {
                $validator->errors()->add('grant_consent_date', 'Enter the date the consent was confirmed.');
            }
            if ($this->input('payreq_status') !== 'not_raised' && (! $this->filled('payreq_reference') || ! $this->filled('payreq_raised_date'))) {
                $validator->errors()->add('payreq_reference', 'Enter the PayReq reference and raised date.');
            }
            if ($this->input('payreq_status') === 'approved' && ! $this->filled('payreq_approved_date')) {
                $validator->errors()->add('payreq_approved_date', 'Enter the external PayReq approval date.');
            }
        });
    }
}
