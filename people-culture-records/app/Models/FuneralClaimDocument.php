<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class FuneralClaimDocument extends Model
{
    public const PENDING = 'pending';

    public const RECEIVED = 'received';

    public const SUBMITTED = 'submitted_to_insurance';

    public const ADDITIONAL_REQUESTED = 'additional_information_requested';

    public const APPROVED_CLOSED = 'approved_closed';

    protected $fillable = ['funeral_claim_id', 'document_type', 'custom_label', 'is_required', 'status', 'notes', 'status_updated_by', 'status_updated_at'];

    protected function casts(): array
    {
        return ['is_required' => 'boolean', 'notes' => 'encrypted', 'status_updated_at' => 'datetime'];
    }

    public static function statuses(): array
    {
        return [self::PENDING => 'Pending', self::RECEIVED => 'Received', self::SUBMITTED => 'Submitted to Insurance', self::ADDITIONAL_REQUESTED => 'Additional Information Requested', self::APPROVED_CLOSED => 'Approved / Closed'];
    }

    public static function types(): array
    {
        return ['death_certificate' => 'Death Certificate', 'certified_nrc' => 'Certified NRC', 'birth_confirmation' => 'Confirmation of Birth', 'marriage_certificate' => 'Marriage Certificate', 'additional_supporting_document' => 'Additional Supporting Document'];
    }

    public function claim(): BelongsTo
    {
        return $this->belongsTo(FuneralClaim::class, 'funeral_claim_id');
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    public function statusUpdatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'status_updated_by');
    }

    public function getLabelAttribute(): string
    {
        return $this->custom_label ?: (self::types()[$this->document_type] ?? str($this->document_type)->headline());
    }
}
