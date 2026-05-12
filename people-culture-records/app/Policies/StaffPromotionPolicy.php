<?php

namespace App\Policies;

use App\Models\StaffPromotion;
use App\Models\User;

class StaffPromotionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_active && in_array($user->role?->name, ['Admin', 'HR Manager', 'HR Officer', 'Viewer'], true);
    }

    public function view(User $user, StaffPromotion $staffPromotion): bool
    {
        if (! $user->is_active) {
            return false;
        }

        if ($user->isAdmin() || $user->isHrManager()) {
            return true;
        }

        return $user->province_id !== null && $staffPromotion->province_id === $user->province_id;
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

    public function update(User $user, StaffPromotion $staffPromotion): bool
    {
        return $this->canManagePromotion($user, $staffPromotion);
    }

    public function archive(User $user, StaffPromotion $staffPromotion): bool
    {
        return $this->canManagePromotion($user, $staffPromotion);
    }

    public function restore(User $user, StaffPromotion $staffPromotion): bool
    {
        return $user->is_active && ($user->isAdmin() || $user->isHrManager());
    }

    public function uploadAttachment(User $user, StaffPromotion $staffPromotion): bool
    {
        return $this->canManagePromotion($user, $staffPromotion);
    }

    public function deleteAttachment(User $user, StaffPromotion $staffPromotion): bool
    {
        return $user->is_active && ($user->isAdmin() || $user->isHrManager());
    }

    private function canManagePromotion(User $user, StaffPromotion $staffPromotion): bool
    {
        if (! $user->is_active) {
            return false;
        }

        if ($user->isAdmin() || $user->isHrManager()) {
            return true;
        }

        return $user->hasRole('HR Officer')
            && $user->province_id !== null
            && $staffPromotion->province_id === $user->province_id;
    }
}
