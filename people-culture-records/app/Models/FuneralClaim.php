<?php

namespace App\Models;

use App\Casts\EncryptedDate;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FuneralClaim extends Model
{
    public const CATEGORY_EMPLOYEE = 'employee';

    public const CATEGORY_SPOUSE = 'spouse';

    public const CATEGORY_CHILD = 'child';

    public const STATUS_DRAFT = 'draft';

    public const STATUS_AWAITING_DOCUMENTS = 'awaiting_documents';

    public const STATUS_UNDER_VERIFICATION = 'under_verification';

    public const STATUS_READY_FOR_APPROVAL = 'ready_for_approval';

    public const STATUS_INELIGIBLE = 'ineligible';

    public const ELIGIBLE = 'eligible';

    public const INELIGIBLE = 'ineligible';

    public const REQUIRES_REVIEW = 'requires_review';

    protected $fillable = ['reference_no', 'employee_id', 'employee_dependant_id', 'province_id', 'deceased_category', 'claimant_context', 'notification_date', 'date_of_death', 'deceased_name', 'deceased_date_of_birth', 'gestational_age_weeks', 'age_at_death_years', 'status', 'eligibility_status', 'eligibility_reason', 'funeral_benefit_rule_id', 'eligible_amount', 'funding_source', 'assigned_to', 'internal_notes', 'last_status_changed_by', 'last_status_changed_at', 'created_by', 'updated_by'];

    protected function casts(): array
    {
        return ['notification_date' => 'date', 'date_of_death' => 'date', 'deceased_date_of_birth' => EncryptedDate::class, 'gestational_age_weeks' => 'decimal:2', 'age_at_death_years' => 'decimal:2', 'eligible_amount' => 'decimal:2', 'internal_notes' => 'encrypted', 'last_status_changed_at' => 'datetime'];
    }

    public static function categories(): array
    {
        return [self::CATEGORY_EMPLOYEE => 'Employee', self::CATEGORY_SPOUSE => 'Spouse', self::CATEGORY_CHILD => 'Child'];
    }

    public static function statuses(): array
    {
        return [self::STATUS_DRAFT => 'Draft', self::STATUS_AWAITING_DOCUMENTS => 'Awaiting Documents', self::STATUS_UNDER_VERIFICATION => 'Under Verification', self::STATUS_READY_FOR_APPROVAL => 'Ready for Approval', self::STATUS_INELIGIBLE => 'Ineligible'];
    }

    public static function eligibilityStatuses(): array
    {
        return [self::ELIGIBLE => 'Eligible', self::REQUIRES_REVIEW => 'Requires Review', self::INELIGIBLE => 'Ineligible'];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function dependant(): BelongsTo
    {
        return $this->belongsTo(EmployeeDependant::class, 'employee_dependant_id');
    }

    public function province(): BelongsTo
    {
        return $this->belongsTo(Province::class);
    }

    public function benefitRule(): BelongsTo
    {
        return $this->belongsTo(FuneralBenefitRule::class, 'funeral_benefit_rule_id');
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(FuneralClaimDocument::class);
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(FuneralClaimStatusHistory::class);
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->isAdmin() || $user->isHrManager()) {
            return $query;
        }
        if ($user->hasRole('HR Officer') && $user->province_id) {
            return $query->where('province_id', $user->province_id);
        }

        return $query->whereRaw('1 = 0');
    }
}
