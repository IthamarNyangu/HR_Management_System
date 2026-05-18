<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TemporaryAppointmentExtension extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'temporary_appointment_id',
        'previous_end_date',
        'new_end_date',
        'reason',
        'comment',
        'extended_by',
        'extended_at',
    ];

    protected function casts(): array
    {
        return [
            'previous_end_date' => 'date',
            'new_end_date' => 'date',
            'extended_at' => 'datetime',
        ];
    }

    public function temporaryAppointment(): BelongsTo
    {
        return $this->belongsTo(TemporaryAppointment::class);
    }

    public function extendedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'extended_by');
    }
}
