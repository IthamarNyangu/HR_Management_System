<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrganisationChart extends Model
{
    use SoftDeletes;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_PUBLISHED = 'published';

    public const STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_PUBLISHED,
    ];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'title',
        'project_id',
        'status',
        'effective_date',
        'description',
        'created_by',
        'updated_by',
        'archived_by',
    ];

    protected function casts(): array
    {
        return [
            'effective_date' => 'date',
            'deleted_at' => 'datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function nodes(): HasMany
    {
        return $this->hasMany(OrganisationChartNode::class)->orderBy('sort_order')->orderBy('label');
    }

    public function rootNodes(): HasMany
    {
        return $this->nodes()->whereNull('parent_id');
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
        if ($user->is_active) {
            return $query;
        }

        return $query->whereRaw('1 = 0');
    }
}
