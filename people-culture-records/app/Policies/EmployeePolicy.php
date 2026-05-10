<?php

namespace App\Policies;

use App\Models\Employee;
use App\Models\User;

class EmployeePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_active && in_array($user->role?->name, ['Admin', 'HR Manager', 'HR Officer', 'Viewer'], true);
    }

    public function view(User $user, Employee $employee): bool
    {
        if (! $user->is_active) {
            return false;
        }

        if ($user->isAdmin() || $user->isHrManager()) {
            return true;
        }

        return $user->province_id !== null && $employee->province_id === $user->province_id;
    }

    public function create(User $user): bool
    {
        if (! $user->is_active) {
            return false;
        }

        if ($user->isAdmin() || $user->isHrManager()) {
            return true;
        }

        return $user->hasRole('HR Officer') && $user->province_id !== null;
    }

    public function update(User $user, Employee $employee): bool
    {
        if (! $user->is_active) {
            return false;
        }

        if ($user->isAdmin() || $user->isHrManager()) {
            return true;
        }

        return $user->hasRole('HR Officer')
            && $user->province_id !== null
            && $employee->province_id === $user->province_id;
    }

    public function archive(User $user, Employee $employee): bool
    {
        return $this->update($user, $employee);
    }

    public function restore(User $user, Employee $employee): bool
    {
        return $this->update($user, $employee);
    }
}
