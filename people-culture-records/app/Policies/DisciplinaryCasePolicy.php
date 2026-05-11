<?php

namespace App\Policies;

use App\Models\DisciplinaryCase;
use App\Models\User;

class DisciplinaryCasePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_active && in_array($user->role?->name, ['Admin', 'HR Manager', 'HR Officer', 'Viewer'], true);
    }

    public function view(User $user, DisciplinaryCase $disciplinaryCase): bool
    {
        if (! $user->is_active) {
            return false;
        }

        if ($user->isAdmin() || $user->isHrManager()) {
            return true;
        }

        return $user->province_id !== null && $disciplinaryCase->province_id === $user->province_id;
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

    public function update(User $user, DisciplinaryCase $disciplinaryCase): bool
    {
        if (! $this->canManageCaseRecord($user, $disciplinaryCase)) {
            return false;
        }

        if ($disciplinaryCase->hasStatusCode('CLOSED') && ! ($user->isAdmin() || $user->isHrManager())) {
            return false;
        }

        return true;
    }

    public function submit(User $user, DisciplinaryCase $disciplinaryCase): bool
    {
        return $disciplinaryCase->hasStatusCode('DRAFT') && $this->canManageCaseRecord($user, $disciplinaryCase);
    }

    public function approve(User $user, DisciplinaryCase $disciplinaryCase): bool
    {
        return $user->is_active
            && ($user->isAdmin() || $user->isHrManager())
            && $disciplinaryCase->hasStatusCode('SUBMITTED');
    }

    public function close(User $user, DisciplinaryCase $disciplinaryCase): bool
    {
        return $user->is_active
            && ($user->isAdmin() || $user->isHrManager())
            && $disciplinaryCase->hasStatusCode('ACTIVE');
    }

    public function archive(User $user, DisciplinaryCase $disciplinaryCase): bool
    {
        return $this->canManageCaseRecord($user, $disciplinaryCase);
    }

    public function restore(User $user, DisciplinaryCase $disciplinaryCase): bool
    {
        if (! $user->is_active) {
            return false;
        }

        return $user->isAdmin() || $user->isHrManager();
    }

    public function uploadAttachment(User $user, DisciplinaryCase $disciplinaryCase): bool
    {
        return $this->canManageCaseRecord($user, $disciplinaryCase);
    }

    public function deleteAttachment(User $user, DisciplinaryCase $disciplinaryCase): bool
    {
        if (! $user->is_active) {
            return false;
        }

        return $user->isAdmin() || $user->isHrManager();
    }

    private function canManageCaseRecord(User $user, DisciplinaryCase $disciplinaryCase): bool
    {
        if (! $user->is_active) {
            return false;
        }

        if ($user->isAdmin() || $user->isHrManager()) {
            return true;
        }

        return $user->hasRole('HR Officer')
            && $user->province_id !== null
            && $disciplinaryCase->province_id === $user->province_id;
    }
}
