<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JobOpeningReadvertisementNotification extends Model
{
    protected $fillable = [
        'job_opening_id',
        'job_application_id',
        'advertisement_round',
        'recipient_email',
        'sent_by',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'advertisement_round' => 'integer',
            'sent_at' => 'datetime',
        ];
    }

    public function jobOpening(): BelongsTo
    {
        return $this->belongsTo(JobOpening::class);
    }

    public function jobApplication(): BelongsTo
    {
        return $this->belongsTo(JobApplication::class);
    }

    public function sentBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_by');
    }
}
