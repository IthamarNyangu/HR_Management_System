<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class StaffPromotion extends Model
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
        'old_job_title_id',
        'new_job_title_id',
        'promotion_type_id',
        'promotion_date',
        'effective_date',
        'job_title_applied_at',
        'comment',
        'created_by',
        'updated_by',
        'archived_by',
    ];

    protected $appends = [
        'display_name',
        'promotion_year',
        'promotion_month',
        'is_acting_promotion',
    ];

    protected function casts(): array
    {
        return [
            'promotion_date' => 'date',
            'effective_date' => 'date',
            'job_title_applied_at' => 'datetime',
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

    public function oldJobTitle(): BelongsTo
    {
        return $this->belongsTo(JobTitle::class, 'old_job_title_id');
    }

    public function newJobTitle(): BelongsTo
    {
        return $this->belongsTo(JobTitle::class, 'new_job_title_id');
    }

    public function promotionType(): BelongsTo
    {
        return $this->belongsTo(PromotionType::class);
    }

    public function temporaryAppointment(): HasOne
    {
        return $this->hasOne(TemporaryAppointment::class);
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

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    public function getDisplayNameAttribute(): string
    {
        return trim($this->reference_no.' - '.$this->employee?->display_name);
    }

    public function getPromotionYearAttribute(): ?int
    {
        return $this->promotion_date?->year;
    }

    public function getPromotionMonthAttribute(): ?int
    {
        return $this->promotion_date?->month;
    }

    public function getIsActingPromotionAttribute(): bool
    {
        return $this->promotionType?->code === 'ACTING'
            || $this->promotionType?->name === 'Acting Promotion';
    }

    public function getApplicationDateAttribute()
    {
        return $this->effective_date ?? $this->promotion_date;
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
