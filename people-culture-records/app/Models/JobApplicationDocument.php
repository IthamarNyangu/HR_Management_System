<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JobApplicationDocument extends Model
{
    protected $fillable = [
        'job_application_id',
        'document_type',
        'original_filename',
        'stored_filename',
        'file_path',
        'mime_type',
        'file_size',
        'uploaded_at',
    ];

    protected function casts(): array
    {
        return [
            'uploaded_at' => 'datetime',
        ];
    }

    public function jobApplication(): BelongsTo
    {
        return $this->belongsTo(JobApplication::class);
    }

    public function getReadableTypeAttribute(): string
    {
        return match ($this->document_type) {
            JobApplication::DOCUMENT_CV => 'CV',
            JobApplication::DOCUMENT_COVER_LETTER => 'Cover Letter',
            JobApplication::DOCUMENT_EDUCATION_CERTIFICATES => 'Education Certificates',
            default => 'Supporting Document',
        };
    }
}
