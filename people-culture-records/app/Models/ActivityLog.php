<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ActivityLog extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'action',
        'description',
        'subject_type',
        'subject_id',
        'properties',
        'ip_address',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'properties' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->isAdmin() || $user->isHrManager()) {
            return $query;
        }

        if ($user->province_id) {
            return $query->where(function ($query) use ($user) {
                $query->where('properties->province_id', $user->province_id)
                    ->orWhere('properties->from_province_id', $user->province_id)
                    ->orWhere('properties->to_province_id', $user->province_id);
            });
        }

        return $query->whereRaw('1 = 0');
    }

    public function getActorNameAttribute(): string
    {
        return $this->user?->name ?? 'System';
    }

    public function getReferenceAttribute(): ?string
    {
        $properties = $this->properties ?? [];

        return $properties['reference_no']
            ?? $properties['employee_no']
            ?? $properties['filename']
            ?? $properties['user_email']
            ?? null;
    }

    public function getProvinceNameAttribute(): ?string
    {
        $properties = $this->properties ?? [];

        return $properties['province_name'] ?? null;
    }

    public function getFacilityNameAttribute(): ?string
    {
        $properties = $this->properties ?? [];

        return $properties['facility_name'] ?? null;
    }

    public function getDisplayActionAttribute(): string
    {
        return str($this->action)->replace('_', ' ')->title()->toString();
    }

    public function getModuleLabelAttribute(): string
    {
        if ($this->subject_type) {
            return match (class_basename($this->subject_type)) {
                'Employee' => 'Employees',
                'DisciplinaryCase' => 'Disciplinary Cases',
                'StaffPromotion' => 'Staff Promotions',
                'StaffRelocation' => 'Staff Relocations',
                'TemporaryAppointment' => 'Temporary Appointments',
                'JobOpening' => 'Recruitment',
                'Attachment' => 'Attachments',
                'User' => 'User Management',
                'ImportBatch', 'ImportRow' => 'Imports',
                default => str(class_basename($this->subject_type))->headline()->toString(),
            };
        }

        return match (true) {
            str_starts_with($this->action, 'report_') => 'Reports & Exports',
            str_starts_with($this->action, 'import_') => 'Imports',
            str_contains($this->action, 'employee') => 'Employees',
            str_contains($this->action, 'case') => 'Disciplinary Cases',
            str_contains($this->action, 'promotion') => 'Staff Promotions',
            str_contains($this->action, 'relocation') => 'Staff Relocations',
            str_contains($this->action, 'temporary_appointment') => 'Temporary Appointments',
            str_contains($this->action, 'job_opening') => 'Recruitment',
            str_contains($this->action, 'user') => 'User Management',
            default => 'System',
        };
    }

    public function getSubjectLabelAttribute(): ?string
    {
        $properties = $this->properties ?? [];

        return $properties['reference_no']
            ?? $properties['employee_no']
            ?? $properties['filename']
            ?? $properties['user_email']
            ?? $properties['report_type']
            ?? $properties['import_type']
            ?? null;
    }

    public function getLocationLabelAttribute(): ?string
    {
        $properties = $this->properties ?? [];

        if (isset($properties['from_province_name']) || isset($properties['to_province_name'])) {
            $from = implode(' - ', array_filter([
                $properties['from_province_name'] ?? null,
                $properties['from_facility_name'] ?? null,
            ]));
            $to = implode(' - ', array_filter([
                $properties['to_province_name'] ?? null,
                $properties['to_facility_name'] ?? null,
            ]));

            if ($from !== '' || $to !== '') {
                return trim(($from !== '' ? "From {$from}" : '').($to !== '' ? ($from !== '' ? ' to ' : 'To ').$to : ''));
            }
        }

        $parts = array_filter([
            $this->province_name,
            $this->facility_name,
        ]);

        return $parts === [] ? null : implode(' - ', $parts);
    }

    /**
     * @return array<string, string>
     */
    public function getReadablePropertiesAttribute(): array
    {
        $hidden = [
            'province_id',
            'facility_id',
            'from_province_id',
            'from_facility_id',
            'to_province_id',
            'to_facility_id',
            'employee_ids',
            'filters',
        ];

        return collect($this->properties ?? [])
            ->reject(fn ($value, string $key) => in_array($key, $hidden, true) || $value === null || $value === '')
            ->mapWithKeys(fn ($value, string $key) => [
                str($key)->replace('_', ' ')->title()->toString() => is_array($value)
                    ? json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
                    : (string) $value,
            ])
            ->all();
    }
}
