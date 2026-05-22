<?php

namespace App\Http\Requests\Admin;

use App\Models\Employee;
use App\Models\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage-users') ?? false;
    }

    protected function prepareForValidation(): void
    {
        if (! $this->filled('employee_id')) {
            return;
        }

        $employee = Employee::find($this->input('employee_id'));

        if (! $employee) {
            return;
        }

        $this->merge([
            'name' => $this->input('name') ?: $employee->full_name,
            'email' => $this->input('email') ?: $employee->email,
            'province_id' => $this->input('province_id') ?: $employee->province_id,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'employee_id' => ['nullable', 'exists:employees,id', 'unique:users,employee_id'],
            'role_id' => ['required', 'exists:roles,id'],
            'province_id' => ['nullable', 'exists:provinces,id'],
            'is_active' => ['nullable', 'boolean'],
            'must_change_password' => ['nullable', 'boolean'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $role = Role::find($this->input('role_id'));

            if (in_array($role?->name, ['HR Officer', 'Viewer'], true) && ! $this->filled('province_id')) {
                $validator->errors()->add('province_id', 'A province is required for HR Officer and Viewer users.');
            }
        });
    }
}
