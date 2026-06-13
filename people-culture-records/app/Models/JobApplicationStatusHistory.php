<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JobApplicationStatusHistory extends Model
{
    protected $fillable = [
        'job_application_id',
        'from_status',
        'to_status',
        'changed_by',
        'comment',
        'email_sent',
    ];

    protected function casts(): array
    {
        return [
            'email_sent' => 'boolean',
        ];
    }

    public function jobApplication(): BelongsTo
    {
        return $this->belongsTo(JobApplication::class);
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
