<?php

namespace App\Models;

use App\Casts\EncryptedDate;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmployeeDependant extends Model
{
    use SoftDeletes;

    public const CATEGORY_SPOUSE = 'spouse';

    public const CATEGORY_CHILD = 'child';

    public const VERIFICATION_UNVERIFIED = 'unverified';

    public const VERIFICATION_PENDING = 'pending_verification';

    public const VERIFICATION_VERIFIED = 'verified';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'employee_id',
        'category',
        'relationship_detail',
        'full_name',
        'gender',
        'date_of_birth',
        'national_id',
        'relationship_start_date',
        'relationship_end_date',
        'verification_status',
        'is_active',
        'internal_notes',
        'verified_by',
        'verified_at',
        'created_by',
        'updated_by',
        'archived_by',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => EncryptedDate::class,
            'national_id' => 'encrypted',
            'internal_notes' => 'encrypted',
            'relationship_start_date' => 'date',
            'relationship_end_date' => 'date',
            'is_active' => 'boolean',
            'verified_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function categories(): array
    {
        return [
            self::CATEGORY_SPOUSE => 'Spouse',
            self::CATEGORY_CHILD => 'Child',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function relationshipDetails(): array
    {
        return [
            'spouse' => 'Spouse',
            'biological_child' => 'Biological Child',
            'adopted_child' => 'Adopted Child',
            'stepchild' => 'Stepchild',
            'other' => 'Other',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function verificationStatuses(): array
    {
        return [
            self::VERIFICATION_UNVERIFIED => 'Unverified',
            self::VERIFICATION_PENDING => 'Pending Verification',
            self::VERIFICATION_VERIFIED => 'Verified',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
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

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->isAdmin() || $user->isHrManager()) {
            return $query;
        }

        if ($user->hasRole('HR Officer') && $user->province_id) {
            return $query->whereHas('employee', fn (Builder $employeeQuery) => $employeeQuery->where('province_id', $user->province_id));
        }

        return $query->whereRaw('1 = 0');
    }
}
