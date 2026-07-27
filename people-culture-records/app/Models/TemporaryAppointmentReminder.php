<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TemporaryAppointmentReminder extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'temporary_appointment_id',
        'threshold_days',
        'scheduled_end_date',
        'recipient_email',
        'recipient_role',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_end_date' => 'date',
            'sent_at' => 'datetime',
        ];
    }

    public function temporaryAppointment(): BelongsTo
    {
        return $this->belongsTo(TemporaryAppointment::class);
    }
}
