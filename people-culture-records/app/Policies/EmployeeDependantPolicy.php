<?php

namespace App\Policies;

use App\Models\Employee;
use App\Models\EmployeeDependant;
use App\Models\User;

class EmployeeDependantPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_active
            && in_array($user->role?->name, ['Admin', 'HR Manager', 'HR Officer'], true);
    }

    public function view(User $user, EmployeeDependant $dependant): bool
    {
        return $this->canManageEmployee($user, $dependant->employee);
    }

    public function create(User $user, Employee $employee): bool
    {
        return $this->canManageEmployee($user, $employee);
    }

    public function update(User $user, EmployeeDependant $dependant): bool
    {
        return $this->canManageEmployee($user, $dependant->employee);
    }

    public function archive(User $user, EmployeeDependant $dependant): bool
    {
        return $this->canManageEmployee($user, $dependant->employee);
    }

    public function restore(User $user, EmployeeDependant $dependant): bool
    {
        return $this->canManageEmployee($user, $dependant->employee);
    }

    private function canManageEmployee(User $user, ?Employee $employee): bool
    {
        if (! $user->is_active || ! $employee) {
            return false;
        }

        if ($user->isAdmin() || $user->isHrManager()) {
            return true;
        }

        return $user->hasRole('HR Officer')
            && $user->province_id !== null
            && $employee->province_id === $user->province_id;
    }
}
