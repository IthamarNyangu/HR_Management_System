<?php

namespace App\Http\Requests\Recruitment;

use Illuminate\Foundation\Http\FormRequest;

class AddJobApplicationNoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('addNote', $this->route('jobApplication')) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'note' => ['required', 'string', 'max:5000'],
        ];
    }
}
