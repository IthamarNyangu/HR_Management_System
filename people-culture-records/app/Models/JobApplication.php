<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class JobApplication extends Model
{
    public const SOURCE_EXTERNAL = 'external';

    public const STATUS_SUBMITTED = 'submitted';
    public const STATUS_UNDER_REVIEW = 'under_review';
    public const STATUS_LONGLISTED = 'longlisted';
    public const STATUS_SHORTLISTED = 'shortlisted';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_WITHDRAWN = 'withdrawn';

    public const STATUSES = [
        self::STATUS_SUBMITTED,
        self::STATUS_UNDER_REVIEW,
        self::STATUS_LONGLISTED,
        self::STATUS_SHORTLISTED,
        self::STATUS_REJECTED,
        self::STATUS_WITHDRAWN,
    ];

    public const DOCUMENT_CV = 'cv';
    public const DOCUMENT_COVER_LETTER = 'cover_letter';
    public const DOCUMENT_EDUCATION_CERTIFICATES = 'education_certificates';
    public const DOCUMENT_SUPPORTING = 'supporting_document';

    public const HIGHEST_QUALIFICATIONS = [
        'Doctorate / PhD',
        "Master's Degree",
        'Postgraduate Diploma / Postgraduate Certificate',
        "Bachelor's Degree",
        'Diploma',
        'Secondary School Certificate',
        'Technical/Vocational Certificate',
    ];

    protected $fillable = [
        'reference_no',
        'job_opening_id',
        'employee_id',
        'source',
        'status',
        'title',
        'first_name',
        'last_name',
        'email',
        'phone',
        'national_id',
        'gender',
        'disability',
        'province',
        'district',
        'highest_qualification',
        'field_of_study',
        'years_of_experience',
        'current_employer',
        'motivation',
        'consent_given_at',
        'submitted_at',
        'withdrawn_at',
        'withdrawal_token_hash',
        'last_confirmation_sent_at',
        'outcome_sent_at',
        'outcome_sent_by',
        'qualification_score',
        'experience_score',
        'screening_score',
        'overall_score',
        'review_notes',
        'reviewed_by',
        'reviewed_at',
        'shortlisted_at',
        'rejected_at',
        'rejection_reason',
        'last_status_changed_by',
        'last_status_changed_at',
    ];

    protected $appends = [
        'full_name',
        'display_name',
        'status_label',
        'status_badge_class',
    ];

    protected function casts(): array
    {
        return [
            'years_of_experience' => 'decimal:1',
            'consent_given_at' => 'datetime',
            'submitted_at' => 'datetime',
            'withdrawn_at' => 'datetime',
            'last_confirmation_sent_at' => 'datetime',
            'outcome_sent_at' => 'datetime',
            'qualification_score' => 'decimal:2',
            'experience_score' => 'decimal:2',
            'screening_score' => 'decimal:2',
            'overall_score' => 'decimal:2',
            'reviewed_at' => 'datetime',
            'shortlisted_at' => 'datetime',
            'rejected_at' => 'datetime',
            'last_status_changed_at' => 'datetime',
        ];
    }

    public function jobOpening(): BelongsTo
    {
        return $this->belongsTo(JobOpening::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(JobApplicationDocument::class);
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(JobApplicationStatusHistory::class)->latest();
    }

    public function notes(): HasMany
    {
        return $this->hasMany(JobApplicationNote::class)->latest();
    }

    public function outcomeSentBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'outcome_sent_by');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function lastStatusChangedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'last_status_changed_by');
    }

    public function cvDocument(): HasOne
    {
        return $this->hasOne(JobApplicationDocument::class)->where('document_type', self::DOCUMENT_CV);
    }

    public function coverLetterDocument(): HasOne
    {
        return $this->hasOne(JobApplicationDocument::class)->where('document_type', self::DOCUMENT_COVER_LETTER);
    }

    public function educationCertificatesDocument(): HasOne
    {
        return $this->hasOne(JobApplicationDocument::class)->where('document_type', self::DOCUMENT_EDUCATION_CERTIFICATES);
    }

    public function getFullNameAttribute(): string
    {
        return trim($this->first_name.' '.$this->last_name);
    }

    public function getDisplayNameAttribute(): string
    {
        return trim($this->reference_no.' - '.$this->full_name);
    }

    public function getStatusLabelAttribute(): string
    {
        return self::statusLabels()[$this->status] ?? str($this->status)->headline()->toString();
    }

    public function getStatusBadgeClassAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_WITHDRAWN => 'warning',
            self::STATUS_REJECTED => 'danger',
            self::STATUS_SHORTLISTED => 'success',
            self::STATUS_LONGLISTED => 'info',
            self::STATUS_UNDER_REVIEW => 'primary',
            default => 'secondary',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function statusLabels(): array
    {
        return [
            self::STATUS_SUBMITTED => 'Submitted',
            self::STATUS_UNDER_REVIEW => 'Under Review',
            self::STATUS_LONGLISTED => 'Longlisted',
            self::STATUS_SHORTLISTED => 'Shortlisted',
            self::STATUS_REJECTED => 'Rejected',
            self::STATUS_WITHDRAWN => 'Withdrawn',
        ];
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->isAdmin() || $user->isHrManager()) {
            return $query;
        }

        if ($user->province_id) {
            return $query->whereHas('jobOpening', fn (Builder $query) => $query->visibleTo($user));
        }

        return $query->whereRaw('1 = 0');
    }

    public function isWithdrawn(): bool
    {
        return $this->status === self::STATUS_WITHDRAWN;
    }

    public function isSubmitted(): bool
    {
        return $this->status === self::STATUS_SUBMITTED;
    }

    public function canMoveThroughReview(): bool
    {
        return ! $this->isWithdrawn();
    }
}
