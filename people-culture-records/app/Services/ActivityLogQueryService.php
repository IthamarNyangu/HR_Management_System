<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Attachment;
use App\Models\DisciplinaryCase;
use App\Models\Employee;
use App\Models\ImportBatch;
use App\Models\StaffPromotion;
use App\Models\StaffRelocation;
use App\Models\TemporaryAppointment;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class ActivityLogQueryService
{
    /**
     * @param array<string, mixed> $filters
     */
    public function query(User $user, array $filters = []): Builder
    {
        return ActivityLog::query()
            ->with('user')
            ->visibleTo($user)
            ->when($filters['search'] ?? null, function (Builder $query, string $search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('description', 'like', "%{$search}%")
                        ->orWhere('action', 'like', "%{$search}%")
                        ->orWhere('properties->reference_no', 'like', "%{$search}%")
                        ->orWhere('properties->employee_no', 'like', "%{$search}%")
                        ->orWhere('properties->filename', 'like', "%{$search}%")
                        ->orWhere('properties->user_email', 'like', "%{$search}%");
                });
            })
            ->when($filters['action'] ?? null, fn (Builder $query, string $action) => $query->where('action', $action))
            ->when($filters['module'] ?? null, function (Builder $query, string $module): void {
                $this->applyModuleFilter($query, $module);
            })
            ->when($filters['user_id'] ?? null, fn (Builder $query, int|string $userId) => $query->where('user_id', $userId))
            ->when(($filters['system_only'] ?? null), fn (Builder $query) => $query->whereNull('user_id'))
            ->when($filters['province_id'] ?? null, function (Builder $query, int|string $provinceId): void {
                $query->where(function (Builder $query) use ($provinceId): void {
                    $query->where('properties->province_id', (int) $provinceId)
                        ->orWhere('properties->from_province_id', (int) $provinceId)
                        ->orWhere('properties->to_province_id', (int) $provinceId);
                });
            })
            ->when($filters['date_from'] ?? null, fn (Builder $query, string $date) => $query->whereDate('created_at', '>=', $date))
            ->when($filters['date_to'] ?? null, fn (Builder $query, string $date) => $query->whereDate('created_at', '<=', $date))
            ->latest();
    }

    /**
     * @return array<string, string>
     */
    public function modules(): array
    {
        return [
            'employees' => 'Employees',
            'disciplinary-cases' => 'Disciplinary Cases',
            'staff-promotions' => 'Staff Promotions',
            'staff-relocations' => 'Staff Relocations',
            'temporary-appointments' => 'Temporary Appointments',
            'attachments' => 'Attachments',
            'users' => 'User Management',
            'reports' => 'Reports & Exports',
            'imports' => 'Imports',
            'system' => 'System',
        ];
    }

    /**
     * @return array<string>
     */
    public function actions(User $user): array
    {
        return ActivityLog::query()
            ->visibleTo($user)
            ->select('action')
            ->distinct()
            ->orderBy('action')
            ->pluck('action')
            ->all();
    }

    /**
     * @param array<string, mixed> $filters
     * @return array<string, string>
     */
    public function filterSummary(array $filters): array
    {
        return collect($filters)
            ->reject(fn ($value) => $value === null || $value === '' || $value === [])
            ->mapWithKeys(fn ($value, string $key) => [
                str($key)->replace('_', ' ')->title()->toString() => is_array($value)
                    ? json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
                    : (string) $value,
            ])
            ->all();
    }

    private function applyModuleFilter(Builder $query, string $module): void
    {
        $query->where(function (Builder $query) use ($module): void {
            match ($module) {
                'employees' => $query->where('subject_type', Employee::class)->orWhere('action', 'like', '%employee%'),
                'disciplinary-cases' => $query->where('subject_type', DisciplinaryCase::class)->orWhere('action', 'like', '%case%'),
                'staff-promotions' => $query->where('subject_type', StaffPromotion::class)->orWhere('action', 'like', '%promotion%'),
                'staff-relocations' => $query->where('subject_type', StaffRelocation::class)->orWhere('action', 'like', '%relocation%'),
                'temporary-appointments' => $query->where('subject_type', TemporaryAppointment::class)->orWhere('action', 'like', '%temporary_appointment%'),
                'attachments' => $query->where('subject_type', Attachment::class)->orWhere('action', 'like', '%attachment%'),
                'users' => $query->where('subject_type', User::class)->orWhere('action', 'like', '%user%'),
                'reports' => $query->where('action', 'like', 'report_%'),
                'imports' => $query->where('subject_type', ImportBatch::class)->orWhere('action', 'like', 'import_%'),
                'system' => $query->whereNull('user_id'),
                default => null,
            };
        });
    }
}
