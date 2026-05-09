<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MasterDataRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage-master-data') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $type = $this->route('type');
        $id = $this->route('id');
        $table = str_replace('-', '_', $type);

        $rules = [
            'name' => ['required', 'string', 'max:255', Rule::unique($table, 'name')->ignore($id)],
            'code' => ['nullable', 'string', 'max:50', Rule::unique($table, 'code')->ignore($id)],
            'description' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['nullable', 'boolean'],
        ];

        if ($type === 'districts') {
            $rules['province_id'] = ['required', 'exists:provinces,id'];
        }

        if ($type === 'facilities') {
            $rules['district_id'] = ['required', 'exists:districts,id'];
        }

        return $rules;
    }
}
