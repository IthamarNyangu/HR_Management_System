<?php

namespace App\Http\Requests;

use App\Models\Employee;
use App\Models\EmployeeDependant;

class UpdateEmployeeDependantRequest extends StoreEmployeeDependantRequest
{
    public function authorize(): bool
    {
        $employee = $this->route('employee');
        $dependant = $this->route('dependant');

        return $employee instanceof Employee
            && $dependant instanceof EmployeeDependant
            && $dependant->employee_id === $employee->id
            && ($this->user()?->can('update', $dependant) ?? false);
    }
}
