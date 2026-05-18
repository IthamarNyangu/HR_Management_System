<?php

namespace App\Http\Requests;

use App\Models\TemporaryAppointment;

class UpdateTemporaryAppointmentRequest extends StoreTemporaryAppointmentRequest
{
    public function authorize(): bool
    {
        $appointment = $this->route('temporary_appointment');

        return $appointment instanceof TemporaryAppointment
            && ($this->user()?->can('update', $appointment) ?? false);
    }
}
