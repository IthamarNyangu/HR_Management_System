<?php

namespace App\Policies;

use App\Models\OrganisationChart;
use App\Models\User;

class OrganisationChartPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_active && in_array($user->role?->name, ['Admin', 'HR Manager', 'HR Officer', 'Viewer'], true);
    }

    public function view(User $user, OrganisationChart $organisationChart): bool
    {
        return $user->is_active && $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->is_active && ($user->isAdmin() || $user->isHrManager());
    }

    public function update(User $user, OrganisationChart $organisationChart): bool
    {
        return $user->is_active && ($user->isAdmin() || $user->isHrManager());
    }

    public function archive(User $user, OrganisationChart $organisationChart): bool
    {
        return $this->update($user, $organisationChart);
    }

    public function restore(User $user, OrganisationChart $organisationChart): bool
    {
        return $user->is_active && ($user->isAdmin() || $user->isHrManager());
    }
}
