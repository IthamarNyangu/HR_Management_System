<?php

namespace App\Policies;

use App\Models\FuneralClaim;
use App\Models\User;

class FuneralClaimPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_active && in_array($user->role?->name, ['Admin', 'HR Manager', 'HR Officer'], true);
    }

    public function view(User $user, FuneralClaim $claim): bool
    {
        return $this->manage($user, $claim);
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, FuneralClaim $claim): bool
    {
        return $this->manage($user, $claim);
    }

    public function approve(User $user, FuneralClaim $claim): bool
    {
        return $user->is_active && ($user->isAdmin() || $user->isHrManager());
    }

    public function reopen(User $user, FuneralClaim $claim): bool
    {
        return $this->approve($user, $claim);
    }

    private function manage(User $user, FuneralClaim $claim): bool
    {
        if (! $user->is_active) {
            return false;
        }
        if ($user->isAdmin() || $user->isHrManager()) {
            return true;
        }

        return $user->hasRole('HR Officer') && $user->province_id && $claim->province_id === $user->province_id;
    }
}
