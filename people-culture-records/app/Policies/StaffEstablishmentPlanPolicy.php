<?php

namespace App\Policies;

use App\Models\StaffEstablishmentPlan;
use App\Models\User;

class StaffEstablishmentPlanPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_active && in_array($user->role?->name, ['Admin', 'HR Manager', 'HR Officer', 'Viewer'], true);
    }

    public function view(User $user, StaffEstablishmentPlan $staffEstablishmentPlan): bool
    {
        if (! $user->is_active) {
            return false;
        }

        if ($user->isAdmin() || $user->isHrManager()) {
            return true;
        }

        return $user->province_id !== null
            && $staffEstablishmentPlan->lines()->where('province_id', $user->province_id)->exists();
    }

    public function create(User $user): bool
    {
        return $this->canManage($user);
    }

    public function update(User $user, StaffEstablishmentPlan $staffEstablishmentPlan): bool
    {
        return $this->canManage($user);
    }

    public function archive(User $user, StaffEstablishmentPlan $staffEstablishmentPlan): bool
    {
        return $this->canManage($user);
    }

    public function restore(User $user, StaffEstablishmentPlan $staffEstablishmentPlan): bool
    {
        return $this->canManage($user);
    }

    public function export(User $user, StaffEstablishmentPlan $staffEstablishmentPlan): bool
    {
        return $this->view($user, $staffEstablishmentPlan);
    }

    private function canManage(User $user): bool
    {
        return $user->is_active && ($user->isAdmin() || $user->isHrManager());
    }
}
