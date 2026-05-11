<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class DisciplinaryCase extends Model
{
    use SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'reference_no',
        'employee_id',
        'project_id',
        'province_id',
        'district_id',
        'facility_id',
        'supervisor_name',
        'nature_of_offence',
        'offence_category_id',
        'penalty_type_id',
        'case_status_id',
        'effective_date',
        'expiry_date',
        'comment',
        'submitted_at',
        'approved_by',
        'approved_at',
        'closed_at',
        'created_by',
        'updated_by',
        'archived_by',
    ];

    protected $appends = [
        'display_name',
        'is_expired',
        'expires_soon',
    ];

    protected function casts(): array
    {
        return [
            'effective_date' => 'date',
            'expiry_date' => 'date',
            'submitted_at' => 'datetime',
            'approved_at' => 'datetime',
            'closed_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
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

    public function offenceCategory(): BelongsTo
    {
        return $this->belongsTo(OffenceCategory::class);
    }

    public function penaltyType(): BelongsTo
    {
        return $this->belongsTo(PenaltyType::class);
    }

    public function caseStatus(): BelongsTo
    {
        return $this->belongsTo(CaseStatus::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
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

    public function getIsExpiredAttribute(): bool
    {
        return $this->expiry_date !== null
            && $this->expiry_date->isPast()
            && ! $this->hasStatusCode('CLOSED');
    }

    public function getExpiresSoonAttribute(): bool
    {
        return $this->expiry_date !== null
            && ! $this->hasStatusCode('CLOSED')
            && $this->expiry_date->isBetween(now()->startOfDay(), now()->addDays(30)->endOfDay());
    }

    public function hasStatusCode(string $code): bool
    {
        $status = $this->relationLoaded('caseStatus') ? $this->caseStatus : $this->caseStatus()->first();

        return strtoupper((string) $status?->code) === strtoupper($code);
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
