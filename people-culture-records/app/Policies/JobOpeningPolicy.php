<?php

namespace App\Policies;

use App\Models\JobOpening;
use App\Models\User;

class JobOpeningPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_active && in_array($user->role?->name, ['Admin', 'HR Manager', 'HR Officer', 'Viewer'], true);
    }

    public function view(User $user, JobOpening $jobOpening): bool
    {
        if (! $user->is_active) {
            return false;
        }

        if ($user->isAdmin() || $user->isHrManager()) {
            return true;
        }

        return $user->province_id !== null
            && (
                $jobOpening->provinces()->whereKey($user->province_id)->exists()
                || (int) $jobOpening->province_id === (int) $user->province_id
                || ($jobOpening->province_id === null && ! $jobOpening->provinces()->exists())
            );
    }

    public function create(User $user): bool
    {
        return $user->is_active && ($user->isAdmin() || $user->isHrManager());
    }

    public function update(User $user, JobOpening $jobOpening): bool
    {
        return $this->canManage($user, $jobOpening);
    }

    public function publish(User $user, JobOpening $jobOpening): bool
    {
        return $this->canManage($user, $jobOpening);
    }

    public function close(User $user, JobOpening $jobOpening): bool
    {
        return $this->canManage($user, $jobOpening);
    }

    public function cancel(User $user, JobOpening $jobOpening): bool
    {
        return $this->canManage($user, $jobOpening);
    }

    public function archive(User $user, JobOpening $jobOpening): bool
    {
        return $this->canManage($user, $jobOpening);
    }

    public function restore(User $user, JobOpening $jobOpening): bool
    {
        return $user->is_active && ($user->isAdmin() || $user->isHrManager());
    }

    private function canManage(User $user, JobOpening $jobOpening): bool
    {
        if (! $user->is_active) {
            return false;
        }

        if ($user->isAdmin() || $user->isHrManager()) {
            return true;
        }

        return false;
    }
}
