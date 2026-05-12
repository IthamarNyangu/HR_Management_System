<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class StaffRelocation extends Model
{
    use SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'reference_no',
        'employee_id',
        'job_title_id',
        'project_id',
        'department_id',
        'from_province_id',
        'from_district_id',
        'from_facility_id',
        'to_province_id',
        'to_district_id',
        'to_facility_id',
        'relocation_reason_id',
        'effective_date',
        'location_applied_at',
        'relocation_amount',
        'comment',
        'update_employee_location',
        'created_by',
        'updated_by',
        'archived_by',
    ];

    protected $appends = [
        'display_name',
        'from_location_label',
        'to_location_label',
        'relocation_year',
        'relocation_month',
    ];

    protected function casts(): array
    {
        return [
            'effective_date' => 'date',
            'location_applied_at' => 'datetime',
            'relocation_amount' => 'decimal:2',
            'update_employee_location' => 'boolean',
            'deleted_at' => 'datetime',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function jobTitle(): BelongsTo
    {
        return $this->belongsTo(JobTitle::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function fromProvince(): BelongsTo
    {
        return $this->belongsTo(Province::class, 'from_province_id');
    }

    public function fromDistrict(): BelongsTo
    {
        return $this->belongsTo(District::class, 'from_district_id');
    }

    public function fromFacility(): BelongsTo
    {
        return $this->belongsTo(Facility::class, 'from_facility_id');
    }

    public function toProvince(): BelongsTo
    {
        return $this->belongsTo(Province::class, 'to_province_id');
    }

    public function toDistrict(): BelongsTo
    {
        return $this->belongsTo(District::class, 'to_district_id');
    }

    public function toFacility(): BelongsTo
    {
        return $this->belongsTo(Facility::class, 'to_facility_id');
    }

    public function relocationReason(): BelongsTo
    {
        return $this->belongsTo(RelocationReason::class);
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

    public function getFromLocationLabelAttribute(): string
    {
        return $this->locationLabel($this->fromProvince?->name, $this->fromDistrict?->name, $this->fromFacility?->name);
    }

    public function getToLocationLabelAttribute(): string
    {
        return $this->locationLabel($this->toProvince?->name, $this->toDistrict?->name, $this->toFacility?->name);
    }

    public function getRelocationYearAttribute(): ?int
    {
        return $this->effective_date?->year;
    }

    public function getRelocationMonthAttribute(): ?int
    {
        return $this->effective_date?->month;
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->isAdmin() || $user->isHrManager()) {
            return $query;
        }

        if ($user->province_id) {
            return $query->where(function ($query) use ($user) {
                $query->where('from_province_id', $user->province_id)
                    ->orWhere('to_province_id', $user->province_id);
            });
        }

        return $query->whereRaw('1 = 0');
    }

    private function locationLabel(?string $province, ?string $district, ?string $facility): string
    {
        $parts = array_filter([$province, $district, $facility]);

        return $parts === [] ? '-' : implode(' - ', $parts);
    }
}
