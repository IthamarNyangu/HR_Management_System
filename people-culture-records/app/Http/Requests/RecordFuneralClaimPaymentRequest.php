<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RecordFuneralClaimPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('funeral_claim')) ?? false;
    }

    public function rules(): array
    {
        return ['payment_date' => ['required', 'date', 'before_or_equal:today'], 'payment_reference' => ['required', 'string', 'max:150'], 'family_payment_confirmed_date' => ['nullable', 'date', 'before_or_equal:today', 'after_or_equal:payment_date']];
    }
}
