<?php

namespace App\Http\Requests;

use App\Models\TemporaryAppointment;
use Illuminate\Validation\Rule;

class UpdateTemporaryAppointmentRequest extends StoreTemporaryAppointmentRequest
{
    public function authorize(): bool
    {
        $appointment = $this->route('temporary_appointment');

        return $appointment instanceof TemporaryAppointment
            && ($this->user()?->can('update', $appointment) ?? false);
    }

    public function rules(): array
    {
        $rules = parent::rules();
        $appointment = $this->route('temporary_appointment');

        $rules['staff_promotion_id'] = [
            'nullable',
            'exists:staff_promotions,id',
            Rule::unique('temporary_appointments', 'staff_promotion_id')->ignore($appointment?->id),
        ];

        return $rules;
    }
}
