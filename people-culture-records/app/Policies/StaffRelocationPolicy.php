<?php

namespace App\Policies;

use App\Models\StaffRelocation;
use App\Models\User;

class StaffRelocationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_active && in_array($user->role?->name, ['Admin', 'HR Manager', 'HR Officer', 'Viewer'], true);
    }

    public function view(User $user, StaffRelocation $staffRelocation): bool
    {
        if (! $user->is_active) {
            return false;
        }

        if ($user->isAdmin() || $user->isHrManager()) {
            return true;
        }

        return $this->matchesAssignedProvince($user, $staffRelocation);
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

    public function update(User $user, StaffRelocation $staffRelocation): bool
    {
        return $this->canManageRelocation($user, $staffRelocation);
    }

    public function archive(User $user, StaffRelocation $staffRelocation): bool
    {
        return $this->canManageRelocation($user, $staffRelocation);
    }

    public function restore(User $user, StaffRelocation $staffRelocation): bool
    {
        return $user->is_active && ($user->isAdmin() || $user->isHrManager());
    }

    public function uploadAttachment(User $user, StaffRelocation $staffRelocation): bool
    {
        return $this->canManageRelocation($user, $staffRelocation);
    }

    public function deleteAttachment(User $user, StaffRelocation $staffRelocation): bool
    {
        return $user->is_active && ($user->isAdmin() || $user->isHrManager());
    }

    private function canManageRelocation(User $user, StaffRelocation $staffRelocation): bool
    {
        if (! $user->is_active) {
            return false;
        }

        if ($user->isAdmin() || $user->isHrManager()) {
            return true;
        }

        return $user->hasRole('HR Officer') && $this->matchesAssignedProvince($user, $staffRelocation);
    }

    private function matchesAssignedProvince(User $user, StaffRelocation $staffRelocation): bool
    {
        return $user->province_id !== null
            && in_array($user->province_id, [$staffRelocation->from_province_id, $staffRelocation->to_province_id], true);
    }
}
