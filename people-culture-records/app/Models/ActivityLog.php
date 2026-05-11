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
            return $query->where('properties->province_id', $user->province_id);
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
}
