<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ActivityLog extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'action',
        'description',
        'subject_type',
        'subject_id',
        'properties',
        'ip_address',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'properties' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->isAdmin() || $user->isHrManager()) {
            return $query;
        }

        if ($user->province_id) {
            return $query->where(function ($query) use ($user) {
                $query->where('properties->province_id', $user->province_id)
                    ->orWhere('properties->from_province_id', $user->province_id)
                    ->orWhere('properties->to_province_id', $user->province_id);
            });
        }

        return $query->whereRaw('1 = 0');
    }

    public function getActorNameAttribute(): string
    {
        return $this->user?->name ?? 'System';
    }

    public function getReferenceAttribute(): ?string
    {
        $properties = $this->properties ?? [];

        return $properties['reference_no']
            ?? $properties['employee_no']
            ?? $properties['filename']
            ?? $properties['user_email']
            ?? null;
    }

    public function getProvinceNameAttribute(): ?string
    {
        $properties = $this->properties ?? [];

        return $properties['province_name'] ?? null;
    }

    public function getFacilityNameAttribute(): ?string
    {
        $properties = $this->properties ?? [];

        return $properties['facility_name'] ?? null;
    }

    public function getLocationLabelAttribute(): ?string
    {
        $properties = $this->properties ?? [];

        if (isset($properties['from_province_name']) || isset($properties['to_province_name'])) {
            $from = implode(' - ', array_filter([
                $properties['from_province_name'] ?? null,
                $properties['from_facility_name'] ?? null,
            ]));
            $to = implode(' - ', array_filter([
                $properties['to_province_name'] ?? null,
                $properties['to_facility_name'] ?? null,
            ]));

            if ($from !== '' || $to !== '') {
                return trim(($from !== '' ? "From {$from}" : '').($to !== '' ? ($from !== '' ? ' to ' : 'To ').$to : ''));
            }
        }

        $parts = array_filter([
            $this->province_name,
            $this->facility_name,
        ]);

        return $parts === [] ? null : implode(' - ', $parts);
    }
}
