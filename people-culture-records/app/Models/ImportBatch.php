<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ImportBatch extends Model
{
    protected $fillable = [
        'reference_no',
        'import_type',
        'original_filename',
        'stored_filename',
        'file_path',
        'uploaded_by',
        'status',
        'total_rows',
        'valid_rows',
        'invalid_rows',
        'duplicate_rows',
        'imported_rows',
        'error_summary',
        'confirmed_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'error_summary' => 'array',
            'confirmed_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function rows(): HasMany
    {
        return $this->hasMany(ImportRow::class);
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function isFinal(): bool
    {
        return in_array($this->status, ['imported', 'cancelled'], true);
    }
}
