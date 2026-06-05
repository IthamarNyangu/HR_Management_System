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
    public const STATUS_WITHDRAWN = 'withdrawn';

    public const DOCUMENT_CV = 'cv';
    public const DOCUMENT_COVER_LETTER = 'cover_letter';
    public const DOCUMENT_EDUCATION_CERTIFICATES = 'education_certificates';
    public const DOCUMENT_SUPPORTING = 'supporting_document';

    protected $fillable = [
        'reference_no',
        'job_opening_id',
        'employee_id',
        'source',
        'status',
        'first_name',
        'last_name',
        'email',
        'phone',
        'national_id',
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
    ];

    protected $appends = [
        'full_name',
        'display_name',
    ];

    protected function casts(): array
    {
        return [
            'years_of_experience' => 'decimal:1',
            'consent_given_at' => 'datetime',
            'submitted_at' => 'datetime',
            'withdrawn_at' => 'datetime',
            'last_confirmation_sent_at' => 'datetime',
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
}
