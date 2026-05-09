<?php

namespace App\Models;

use App\Models\Concerns\HasMasterDataFields;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    use HasMasterDataFields;

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
