<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FuneralClaimWorkflowEvent extends Model
{
    protected $fillable = ['funeral_claim_id', 'event_type', 'performed_by', 'comment', 'properties'];

    protected function casts(): array
    {
        return ['comment' => 'encrypted', 'properties' => 'array'];
    }

    public function claim(): BelongsTo
    {
        return $this->belongsTo(FuneralClaim::class, 'funeral_claim_id');
    }

    public function performedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }
}
