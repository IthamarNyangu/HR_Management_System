<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadEmployeeImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('import-employees') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:10240'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'file.mimes' => 'Upload an Excel or CSV file only.',
            'file.max' => 'The import file must not be larger than 10 MB.',
        ];
    }
}
