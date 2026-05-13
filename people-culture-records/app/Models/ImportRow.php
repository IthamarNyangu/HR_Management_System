<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImportRow extends Model
{
    protected $fillable = [
        'import_batch_id',
        'row_number',
        'raw_data',
        'normalized_data',
        'status',
        'errors',
        'warnings',
        'matched_employee_id',
    ];

    protected function casts(): array
    {
        return [
            'raw_data' => 'array',
            'normalized_data' => 'array',
            'errors' => 'array',
            'warnings' => 'array',
        ];
    }

    public function importBatch(): BelongsTo
    {
        return $this->belongsTo(ImportBatch::class);
    }

    public function matchedEmployee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'matched_employee_id');
    }
}
