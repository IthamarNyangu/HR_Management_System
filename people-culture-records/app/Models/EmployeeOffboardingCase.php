<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeOffboardingCase extends Model
{
    public const STATUS_INITIATING = 'initiating';

    public const STATUS_ERROR = 'error';

    protected $fillable = [
        'employee_id',
        'integration_request_uuid',
        'exitclear_case_id',
        'case_number',
        'status',
        'initiated_by_user_id',
        'initiated_at',
        'last_synced_at',
        'failure_message',
    ];

    protected function casts(): array
    {
        return [
            'initiated_at' => 'datetime',
            'last_synced_at' => 'datetime',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function initiatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'initiated_by_user_id');
    }

    public function isLinked(): bool
    {
        return $this->exitclear_case_id !== null;
    }
}
