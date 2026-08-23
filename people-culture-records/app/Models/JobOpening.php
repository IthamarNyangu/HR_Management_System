<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
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
        'advertisement_round',
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
        'contract_duration',
        'job_grade',
        'reporting_to_job_title_id',
        'reporting_to_tba',
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
        'is_expired_published',
        'days_until_closing',
        'closing_status_label',
    ];

    public const ABOUT_US_TEXT = "Right to Care Zambia (RTCZ) has been at the forefront of public health innovation since our incorporation in 2016, providing high-impact Technical Assistance and Direct Service Delivery in partnership with the Ministry of Health. Headquartered in Lusaka, we currently operate across four provinces i.e., Lusaka, Luapula, Muchinga, and Northern, bringing quality healthcare closer to communities.\n\nGuided by our 2023-2027 strategic plan, we are committed to strengthening health systems and improving lives through comprehensive HIV/AIDS prevention, care, and treatment, maternal and child health, and rapid epidemic response. Our integrated approach also includes malaria and TB control, public health research, Social Behaviour Change, pharmaceutical supply chain management, and addressing non-communicable diseases. As we continue to grow, we remain dedicated to excellence, collaboration, and sustainable impact in every community we serve.";

    public const DISCLAIMER_TEXT = "By applying for the above-mentioned position, you consent to Right to Care to conduct qualification, ID, criminal and reference checks (internal and external) which forms part of the Company's recruitment policy and procedure. Should you not receive a response to your application from Right to Care within one month of this advert being placed, kindly consider your application as being unsuccessful.\n\nOnly applicants meeting the strict criteria outlined above will be contacted as part of the shortlisting process. Right to Care reserves the right to withdraw the vacancy at any time for whatever reason.\n\nRight to Care is an equal opportunity affirmative action employer. The Company's approved Employment Equity Plan and Targets will be considered as part of the recruitment process. As an Equal Opportunities Employer, we actively encourage and welcome people with various disabilities to apply.\n\nRight to Care Zambia is aware of fraudulent activities by certain individuals claiming to be representatives of the organization.\n\nBe advised that Right to Care does not charge any fee at any stage of the recruitment process, and as such Right to Care Zambia assumes no responsibility for any announcements or activities by such individuals or entities.";

    public const ANNOUNCEMENT_SECTIONS = [
        'qualifications' => 'Q U A L I F I C A T I O N S  A N D  E X P E R I E N C E',
        'requirements' => 'T E C H N I C A L  A N D  B E H A V I O U R A L  C O M P E T E N C I E S',
        'responsibilities' => 'K E Y  P E R F O R M A N C E  A R E A S',
    ];

    protected function casts(): array
    {
        return [
            'show_number_of_positions' => 'boolean',
            'advertisement_round' => 'integer',
            'reporting_to_tba' => 'boolean',
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

    public function provinces(): BelongsToMany
    {
        return $this->belongsToMany(Province::class, 'job_opening_province')->withTimestamps();
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

    public function reportingToJobTitle(): BelongsTo
    {
        return $this->belongsTo(JobTitle::class, 'reporting_to_job_title_id');
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

    public function readvertisementNotifications(): HasMany
    {
        return $this->hasMany(JobOpeningReadvertisementNotification::class);
    }

    public function previousApplicantsForCurrentRound(): HasMany
    {
        $notifiedApplicationIds = JobOpeningReadvertisementNotification::query()
            ->where('job_opening_id', $this->id)
            ->where('advertisement_round', $this->advertisement_round)
            ->select('job_application_id');

        return $this->jobApplications()
            ->where('advertisement_round', '<', $this->advertisement_round)
            ->where('status', '!=', JobApplication::STATUS_WITHDRAWN)
            ->whereNotIn('id', $notifiedApplicationIds);
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

    public function getIsExpiredPublishedAttribute(): bool
    {
        return $this->status === self::STATUS_PUBLISHED
            && $this->closing_date !== null
            && $this->closing_date->isPast()
            && ! $this->closing_date->isToday();
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

        $provinceLabel = $this->province_list_label;

        return collect([$provinceLabel !== 'Zambia' ? $provinceLabel : null, $this->district?->name, $this->facility?->name])
            ->filter()
            ->implode(' / ') ?: 'Zambia';
    }

    public function getPublicLocationLabelAttribute(): string
    {
        $provinceLabel = $this->province_list_label;

        return collect([$provinceLabel !== 'Zambia' ? $provinceLabel : 'Zambia', $this->district?->name])
            ->filter()
            ->implode(' / ');
    }

    public function getProvinceListLabelAttribute(): string
    {
        if ($this->relationLoaded('provinces') && $this->provinces->isNotEmpty()) {
            return $this->provinces->pluck('name')->implode(', ');
        }

        if (! $this->relationLoaded('provinces') && $this->exists) {
            $names = $this->provinces()->orderBy('name')->pluck('name');

            if ($names->isNotEmpty()) {
                return $names->implode(', ');
            }
        }

        return $this->province?->name ?? 'Zambia';
    }

    public function getVacancyAnnouncementTitleAttribute(): string
    {
        $audience = match ($this->visibility) {
            self::VISIBILITY_INTERNAL => 'INTERNAL ',
            self::VISIBILITY_EXTERNAL => 'EXTERNAL ',
            default => '',
        };

        return 'RTCZ '.$audience.'VACANCY ANNOUNCEMENT - '.str($this->title)->upper();
    }

    public function getReportingToLabelAttribute(): string
    {
        if ($this->reporting_to_tba) {
            return 'TBA';
        }

        return $this->reportingToJobTitle?->name ?? 'TBA';
    }

    public function getAnnouncementContactPersonAttribute(): string
    {
        return 'People & Culture';
    }

    public function getAnnouncementContactEmailAttribute(): string
    {
        return config('mail.from.address') ?: 'hrms-noreply@righttocare-zambia.org';
    }

    /**
     * @return list<string>
     */
    public function linesFor(string $field): array
    {
        return collect(preg_split('/\r\n|\r|\n/', (string) $this->{$field}))
            ->map(fn (string $line): string => trim(preg_replace('/^\s*[-*]\s*/', '', $line) ?? ''))
            ->filter()
            ->values()
            ->all();
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
                $query->whereHas('provinces', fn (Builder $query) => $query->whereKey($user->province_id))
                    ->orWhere('province_id', $user->province_id)
                    ->orWhere(function (Builder $query): void {
                        $query->whereNull('province_id')
                            ->whereDoesntHave('provinces');
                    });
            });
        }

        return $query->whereRaw('1 = 0');
    }
}
