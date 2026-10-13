<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FuneralBenefitRule extends Model
{
    protected $fillable = ['code', 'name', 'deceased_category', 'condition_type', 'minimum_age_years', 'maximum_age_years', 'amount', 'funding_source', 'requires_internal_review', 'effective_from', 'effective_to', 'is_active'];

    protected function casts(): array
    {
        return ['minimum_age_years' => 'decimal:2', 'maximum_age_years' => 'decimal:2', 'amount' => 'decimal:2', 'requires_internal_review' => 'boolean', 'effective_from' => 'date', 'effective_to' => 'date', 'is_active' => 'boolean'];
    }
}
