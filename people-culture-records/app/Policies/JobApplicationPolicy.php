<?php

namespace App\Policies;

use App\Models\JobApplication;
use App\Models\JobApplicationDocument;
use App\Models\User;

class JobApplicationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_active
            && in_array($user->role?->name, ['Admin', 'HR Manager', 'HR Officer', 'Viewer'], true);
    }

    public function view(User $user, JobApplication $jobApplication): bool
    {
        if (! $user->is_active) {
            return false;
        }

        if ($user->isAdmin() || $user->isHrManager()) {
            return true;
        }

        return $user->province_id !== null
            && ($jobApplication->jobOpening?->province_id === null || $jobApplication->jobOpening?->province_id === $user->province_id);
    }

    public function downloadDocument(User $user, JobApplication $jobApplication, JobApplicationDocument $document): bool
    {
        return (int) $document->job_application_id === (int) $jobApplication->id
            && $this->view($user, $jobApplication);
    }

    public function review(User $user, JobApplication $jobApplication): bool
    {
        return $this->canReview($user, $jobApplication);
    }

    public function updateStatus(User $user, JobApplication $jobApplication): bool
    {
        return $this->canReview($user, $jobApplication);
    }

    public function addNote(User $user, JobApplication $jobApplication): bool
    {
        return $this->canReview($user, $jobApplication);
    }

    public function shortlist(User $user, JobApplication $jobApplication): bool
    {
        return $this->canReview($user, $jobApplication);
    }

    public function reject(User $user, JobApplication $jobApplication): bool
    {
        return $this->canReview($user, $jobApplication);
    }

    public function sendEmail(User $user, JobApplication $jobApplication): bool
    {
        return $this->canReview($user, $jobApplication);
    }

    private function canReview(User $user, JobApplication $jobApplication): bool
    {
        if (! $user->is_active) {
            return false;
        }

        if ($user->isAdmin() || $user->isHrManager()) {
            return true;
        }

        if (! $user->hasRole('HR Officer') || $user->province_id === null) {
            return false;
        }

        return $jobApplication->jobOpening?->province_id === $user->province_id;
    }
}
