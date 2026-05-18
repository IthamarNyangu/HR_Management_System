<?php

namespace App\Http\Requests;

use App\Models\TemporaryAppointment;
use Illuminate\Foundation\Http\FormRequest;

class ExtendTemporaryAppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $appointment = $this->route('temporary_appointment');

        return $appointment instanceof TemporaryAppointment
            && ($this->user()?->can('extend', $appointment) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $appointment = $this->route('temporary_appointment');
        $currentEndDate = $appointment instanceof TemporaryAppointment
            ? $appointment->end_date?->format('Y-m-d')
            : null;

        return [
            'new_end_date' => ['required', 'date', $currentEndDate ? 'after:'.$currentEndDate : 'after:today'],
            'extension_reason' => ['nullable', 'string'],
            'extension_comment' => ['nullable', 'string'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'new_end_date.after' => 'The new end date must be after the current end date.',
        ];
    }
}
