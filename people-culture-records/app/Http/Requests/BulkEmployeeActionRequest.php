<?php

namespace App\Http\Requests;

use App\Models\Employee;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BulkEmployeeActionRequest extends FormRequest
{
    /**
     * @var list<string>
     */
    public const ACTIONS = [
        'change_employment_status',
        'change_project',
        'change_department',
        'assign_supervisor',
        'archive',
    ];

    public function authorize(): bool
    {
        $user = $this->user();

        return $user?->is_active
            && ($user->isAdmin() || $user->isHrManager() || $user->hasRole('HR Officer'))
            && $user->can('viewAny', Employee::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'employee_ids' => ['required', 'array', 'min:1'],
            'employee_ids.*' => ['integer', 'exists:employees,id'],
            'action' => ['required', 'string', Rule::in(self::ACTIONS)],
            'employment_status_id' => ['required_if:action,change_employment_status', 'nullable', 'integer', 'exists:employment_statuses,id'],
            'project_id' => ['required_if:action,change_project', 'nullable', 'integer', 'exists:projects,id'],
            'department_id' => ['required_if:action,change_department', 'nullable', 'integer', 'exists:departments,id'],
            'supervisor_name' => ['required_if:action,assign_supervisor', 'nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'employee_ids.required' => 'Select at least one employee before applying a bulk action.',
            'employee_ids.min' => 'Select at least one employee before applying a bulk action.',
            'employee_ids.*.exists' => 'One or more selected employees could not be found.',
            'action.required' => 'Choose a bulk action to apply.',
            'action.in' => 'Choose a valid employee bulk action.',
            'employment_status_id.required_if' => 'Choose the employment status to apply.',
            'project_id.required_if' => 'Choose the project to apply.',
            'department_id.required_if' => 'Choose the department to apply.',
            'supervisor_name.required_if' => 'Enter the line manager name to apply.',
        ];
    }
}
