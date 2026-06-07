<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrganisationChartNode extends Model
{
    public const TYPE_CENTRAL_HEAD_OFFICE = 'central_head_office';
    public const TYPE_REGIONAL_LEVEL = 'regional_level';
    public const TYPE_PROVINCIAL_LEVEL = 'provincial_level';
    public const TYPE_HUB_FACILITY = 'hub_facility';
    public const TYPE_KEY_POSITION = 'key_position';
    public const TYPE_EXTERNAL_PARTNER = 'external_partner';
    public const TYPE_SUPPORT_UNIT = 'support_unit';

    public const TYPES = [
        self::TYPE_CENTRAL_HEAD_OFFICE => 'Central Head Office',
        self::TYPE_REGIONAL_LEVEL => 'Regional Level',
        self::TYPE_PROVINCIAL_LEVEL => 'Provincial Level',
        self::TYPE_HUB_FACILITY => 'Hub / Facility',
        self::TYPE_KEY_POSITION => 'Key Position',
        self::TYPE_EXTERNAL_PARTNER => 'External / Partner',
        self::TYPE_SUPPORT_UNIT => 'Support Unit',
    ];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'organisation_chart_id',
        'parent_id',
        'label',
        'subtitle',
        'node_type',
        'planned_positions',
        'employee_id',
        'job_title_id',
        'project_id',
        'department_id',
        'province_id',
        'district_id',
        'facility_id',
        'sort_order',
    ];

    public function organisationChart(): BelongsTo
    {
        return $this->belongsTo(OrganisationChart::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order')->orderBy('label');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
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

    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->node_type] ?? 'Chart Box';
    }
}
