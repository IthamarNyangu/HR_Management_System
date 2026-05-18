<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class TemporaryAppointment extends Model
{
    use SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'reference_no',
        'employee_id',
        'province_id',
        'district_id',
        'facility_id',
        'project_id',
        'department_id',
        'current_job_title_id',
        'temporary_job_title_id',
        'appointment_type_id',
        'appointment_status_id',
        'start_date',
        'end_date',
        'reason',
        'supervisor_name',
        'supervisor_employee_id',
        'comment',
        'completed_at',
        'created_by',
        'updated_by',
        'archived_by',
    ];

    protected $appends = [
        'display_name',
        'days_remaining',
        'is_active',
        'is_upcoming',
        'is_completed',
        'is_expired',
        'is_ending_soon',
        'date_status_label',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'completed_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
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

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function currentJobTitle(): BelongsTo
    {
        return $this->belongsTo(JobTitle::class, 'current_job_title_id');
    }

    public function temporaryJobTitle(): BelongsTo
    {
        return $this->belongsTo(JobTitle::class, 'temporary_job_title_id');
    }

    public function supervisorEmployee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'supervisor_employee_id');
    }

    public function appointmentType(): BelongsTo
    {
        return $this->belongsTo(AppointmentType::class);
    }

    public function appointmentStatus(): BelongsTo
    {
        return $this->belongsTo(AppointmentStatus::class);
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

    public function extensions(): HasMany
    {
        return $this->hasMany(TemporaryAppointmentExtension::class)->latest('extended_at');
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    public function getDisplayNameAttribute(): string
    {
        return trim($this->reference_no.' - '.$this->employee?->display_name);
    }

    public function getDaysRemainingAttribute(): ?int
    {
        return $this->end_date?->diffInDays(today(), false) * -1;
    }

    public function getIsActiveAttribute(): bool
    {
        return $this->appointmentStatus?->code === 'ACTIVE';
    }

    public function getIsUpcomingAttribute(): bool
    {
        return $this->appointmentStatus?->code === 'UPCOMING';
    }

    public function getIsCompletedAttribute(): bool
    {
        return $this->appointmentStatus?->code === 'COMPLETED';
    }

    public function getIsExpiredAttribute(): bool
    {
        return $this->is_active && $this->end_date !== null && $this->end_date->isBefore(today());
    }

    public function getIsEndingSoonAttribute(): bool
    {
        return $this->is_active
            && $this->end_date !== null
            && $this->end_date->isBetween(today(), today()->addDays(30));
    }

    public function getDateStatusLabelAttribute(): string
    {
        if ($this->appointmentStatus?->code === 'CANCELLED') {
            return 'Cancelled';
        }

        if ($this->is_completed) {
            return 'Completed';
        }

        if ($this->is_upcoming) {
            return 'Upcoming';
        }

        if (! $this->end_date) {
            return '-';
        }

        $days = $this->days_remaining;

        if ($days === 0) {
            return 'Ends today';
        }

        if ($days !== null && $days > 0) {
            return "Ending in {$days} ".str('day')->plural($days);
        }

        $expiredDays = abs((int) $days);

        return "Expired {$expiredDays} ".str('day')->plural($expiredDays).' ago';
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
