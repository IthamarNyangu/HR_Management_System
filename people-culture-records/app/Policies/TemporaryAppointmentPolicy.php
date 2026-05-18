<?php

namespace App\Policies;

use App\Models\TemporaryAppointment;
use App\Models\User;

class TemporaryAppointmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_active && in_array($user->role?->name, ['Admin', 'HR Manager', 'HR Officer', 'Viewer'], true);
    }

    public function view(User $user, TemporaryAppointment $temporaryAppointment): bool
    {
        if (! $user->is_active) {
            return false;
        }

        if ($user->isAdmin() || $user->isHrManager()) {
            return true;
        }

        return $user->province_id !== null && $temporaryAppointment->province_id === $user->province_id;
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

    public function update(User $user, TemporaryAppointment $temporaryAppointment): bool
    {
        return $this->canManage($user, $temporaryAppointment);
    }

    public function archive(User $user, TemporaryAppointment $temporaryAppointment): bool
    {
        return $this->canManage($user, $temporaryAppointment);
    }

    public function restore(User $user, TemporaryAppointment $temporaryAppointment): bool
    {
        return $user->is_active && ($user->isAdmin() || $user->isHrManager());
    }

    public function extend(User $user, TemporaryAppointment $temporaryAppointment): bool
    {
        if ($temporaryAppointment->appointmentStatus?->code === 'CANCELLED') {
            return false;
        }

        if ($temporaryAppointment->appointmentStatus?->code === 'COMPLETED') {
            return $user->is_active && ($user->isAdmin() || $user->isHrManager());
        }

        return $this->canManage($user, $temporaryAppointment);
    }

    public function uploadAttachment(User $user, TemporaryAppointment $temporaryAppointment): bool
    {
        return $this->canManage($user, $temporaryAppointment);
    }

    public function deleteAttachment(User $user, TemporaryAppointment $temporaryAppointment): bool
    {
        return $user->is_active && ($user->isAdmin() || $user->isHrManager());
    }

    private function canManage(User $user, TemporaryAppointment $temporaryAppointment): bool
    {
        if (! $user->is_active) {
            return false;
        }

        if ($user->isAdmin() || $user->isHrManager()) {
            return true;
        }

        return $user->hasRole('HR Officer')
            && $user->province_id !== null
            && $temporaryAppointment->province_id === $user->province_id;
    }
}
