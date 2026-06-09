<?php

namespace App\Models;

use App\Services\StaffEstablishmentMetricsService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StaffEstablishmentLine extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'staff_establishment_plan_id',
        'job_title_id',
        'province_id',
        'district_id',
        'facility_id',
        'department_id',
        'budgeted_positions',
        'notes',
    ];

    protected $appends = [
        'location_label',
        'organisation_label',
    ];

    protected function casts(): array
    {
        return [
            'budgeted_positions' => 'integer',
        ];
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(StaffEstablishmentPlan::class, 'staff_establishment_plan_id');
    }

    public function jobTitle(): BelongsTo
    {
        return $this->belongsTo(JobTitle::class);
    }

    public function province(): BelongsTo
    {
        return $this->belongsTo(Province::class);
    }

    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class);
    }

    public function facility(): BelongsTo
    {
        return $this->belongsTo(Facility::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function getLocationLabelAttribute(): string
    {
        return collect([$this->province?->name, $this->district?->name, $this->facility?->name])
            ->filter()
            ->implode(' / ') ?: 'Organisation-wide';
    }

    public function getOrganisationLabelAttribute(): string
    {
        return collect([$this->plan?->project?->name, $this->department?->name])
            ->filter()
            ->implode(' / ') ?: 'All departments';
    }

    public function filledCount(?User $user = null): int
    {
        return app(StaffEstablishmentMetricsService::class)->filledCount($this, $user);
    }

    public function vacancyCount(?User $user = null): int
    {
        return max($this->budgeted_positions - $this->filledCount($user), 0);
    }

    public function overstaffedCount(?User $user = null): int
    {
        return max($this->filledCount($user) - $this->budgeted_positions, 0);
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->isAdmin() || $user->isHrManager()) {
            return $query;
        }

        if ($user->province_id) {
            return $query->where('province_id', $user->province_id);
        }

        return $query->whereRaw('1 = 0');
    }
}
