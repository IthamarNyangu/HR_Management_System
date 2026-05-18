<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{
    use SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'employee_no',
        'first_name',
        'last_name',
        'gender',
        'date_of_birth',
        'national_id',
        'email',
        'phone',
        'project_id',
        'department_id',
        'job_title_id',
        'province_id',
        'district_id',
        'facility_id',
        'employment_status_id',
        'hire_date',
        'supervisor_name',
        'notes',
        'created_by',
        'updated_by',
        'archived_by',
    ];

    protected $appends = [
        'full_name',
        'display_name',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'hire_date' => 'date',
            'deleted_at' => 'datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
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

    public function employmentStatus(): BelongsTo
    {
        return $this->belongsTo(EmploymentStatus::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function archivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'archived_by');
    }

    public function disciplinaryCases(): HasMany
    {
        return $this->hasMany(DisciplinaryCase::class);
    }

    public function staffPromotions(): HasMany
    {
        return $this->hasMany(StaffPromotion::class);
    }

    public function staffRelocations(): HasMany
    {
        return $this->hasMany(StaffRelocation::class);
    }

    public function temporaryAppointments(): HasMany
    {
        return $this->hasMany(TemporaryAppointment::class);
    }

    public function user(): HasOne
    {
        return $this->hasOne(User::class);
    }

    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    public function getDisplayNameAttribute(): string
    {
        return "{$this->employee_no} - {$this->full_name}";
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
