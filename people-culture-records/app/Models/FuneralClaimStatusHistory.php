<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FuneralClaimStatusHistory extends Model
{
    protected $fillable = ['funeral_claim_id', 'from_status', 'to_status', 'changed_by', 'comment'];

    public function claim(): BelongsTo
    {
        return $this->belongsTo(FuneralClaim::class, 'funeral_claim_id');
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
