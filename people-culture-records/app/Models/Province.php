<?php

namespace App\Models;

use App\Models\Concerns\HasMasterDataFields;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Province extends Model
{
    use HasMasterDataFields;

    public function districts(): HasMany
    {
        return $this->hasMany(District::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
