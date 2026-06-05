<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class JobOpening extends Model
{
    use SoftDeletes;

    public const VISIBILITY_EXTERNAL = 'external';
    public const VISIBILITY_INTERNAL = 'internal';
    public const VISIBILITY_BOTH = 'both';

    public const STATUS_DRAFT = 'draft';
    public const STATUS_PUBLISHED = 'published';
    public const STATUS_CLOSED = 'closed';
    public const STATUS_CANCELLED = 'cancelled';

    public const VISIBILITIES = [
        self::VISIBILITY_EXTERNAL,
        self::VISIBILITY_INTERNAL,
        self::VISIBILITY_BOTH,
    ];

    public const STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_PUBLISHED,
        self::STATUS_CLOSED,
        self::STATUS_CANCELLED,
    ];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'reference_no',
        'job_title_id',
        'title',
        'slug',
        'project_id',
        'department_id',
        'province_id',
        'district_id',
        'facility_id',
        'employment_type_id',
        'visibility',
        'status',
        'number_of_positions',
        'show_number_of_positions',
        'summary',
        'description',
        'responsibilities',
        'requirements',
        'qualifications',
        'experience_required',
        'contract_details',
        'work_level',
        'location_details',
        'application_instructions',
        'internal_notes',
        'opening_date',
        'closing_date',
        'published_at',
        'closed_at',
        'created_by',
        'updated_by',
        'archived_by',
    ];

    protected $appends = [
        'display_name',
        'is_published',
        'is_closed',
        'is_external',
        'is_internal',
        'days_until_closing',
        'closing_status_label',
    ];

    protected function casts(): array
    {
        return [
            'show_number_of_positions' => 'boolean',
            'opening_date' => 'date',
            'closing_date' => 'date',
            'published_at' => 'datetime',
            'closed_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
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

    public function employmentType(): BelongsTo
    {
        return $this->belongsTo(EmploymentType::class);
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

    public function jobApplications(): HasMany
    {
        return $this->hasMany(JobApplication::class);
    }

    public function getDisplayNameAttribute(): string
    {
        return trim($this->reference_no.' - '.$this->title);
    }

    public function getIsPublishedAttribute(): bool
    {
        return $this->status === self::STATUS_PUBLISHED;
    }

    public function getIsClosedAttribute(): bool
    {
        return $this->status === self::STATUS_CLOSED;
    }

    public function getIsExternalAttribute(): bool
    {
        return in_array($this->visibility, [self::VISIBILITY_EXTERNAL, self::VISIBILITY_BOTH], true);
    }

    public function getIsInternalAttribute(): bool
    {
        return in_array($this->visibility, [self::VISIBILITY_INTERNAL, self::VISIBILITY_BOTH], true);
    }

    public function getDaysUntilClosingAttribute(): ?int
    {
        return $this->closing_date ? today()->diffInDays($this->closing_date, false) : null;
    }

    public function getClosingStatusLabelAttribute(): string
    {
        if (! $this->closing_date) {
            return 'No closing date';
        }

        if ($this->closing_date->isToday()) {
            return 'Closes today';
        }

        if ($this->closing_date->isPast()) {
            return 'Expired '.$this->closing_date->diffInDays(today()).' day(s) ago';
        }

        return 'Closes in '.$this->closing_date->diffInDays(today()).' day(s)';
    }

    public function getIsPubliclyApplyableAttribute(): bool
    {
        return ! $this->trashed()
            && $this->status === self::STATUS_PUBLISHED
            && in_array($this->visibility, [self::VISIBILITY_EXTERNAL, self::VISIBILITY_BOTH], true)
            && $this->closing_date !== null
            && $this->closing_date->toDateString() >= today()->toDateString();
    }

    public function getLocationLabelAttribute(): string
    {
        if ($this->location_details) {
            return $this->location_details;
        }

        return collect([$this->province?->name, $this->district?->name, $this->facility?->name])
            ->filter()
            ->implode(' / ') ?: 'Not specified';
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PUBLISHED);
    }

    public function scopeExternal(Builder $query): Builder
    {
        return $query->whereIn('visibility', [self::VISIBILITY_EXTERNAL, self::VISIBILITY_BOTH]);
    }

    public function scopeInternal(Builder $query): Builder
    {
        return $query->whereIn('visibility', [self::VISIBILITY_INTERNAL, self::VISIBILITY_BOTH]);
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->published()
            ->whereDate('closing_date', '>=', today());
    }

    public function scopePubliclyVisible(Builder $query): Builder
    {
        return $query->published()
            ->external()
            ->whereDate('closing_date', '>=', today());
    }

    public function scopePubliclyApplyable(Builder $query): Builder
    {
        return $query->publiclyVisible();
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->isAdmin() || $user->isHrManager()) {
            return $query;
        }

        if ($user->province_id) {
            return $query->where(function (Builder $query) use ($user) {
                $query->where('province_id', $user->province_id)
                    ->orWhereNull('province_id');
            });
        }

        return $query->whereRaw('1 = 0');
    }
}
