<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\EmploymentStatus;
use App\Models\StaffEstablishmentLine;
use App\Models\StaffEstablishmentPlan;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

class StaffEstablishmentMetricsService
{
    public function filledCount(StaffEstablishmentLine $line, ?User $user = null): int
    {
        $activeStatusId = $this->activeEmploymentStatusId();

        if (! $activeStatusId) {
            return 0;
        }

        $query = Employee::query()
            ->where('employment_status_id', $activeStatusId)
            ->where('job_title_id', $line->job_title_id);

        if ($user) {
            $query->visibleTo($user);
        }

        if ($line->plan?->project_id) {
            $query->where('project_id', $line->plan->project_id);
        }

        foreach (['province_id', 'district_id', 'facility_id', 'department_id'] as $field) {
            if ($line->{$field}) {
                $query->where($field, $line->{$field});
            }
        }

        return $query->count();
    }

    /**
     * @return array{budgeted:int, filled:int, vacant:int, overstaffed:int, vacancy_rate:float}
     */
    public function summaryForPlan(StaffEstablishmentPlan $plan, User $user): array
    {
        $lines = $this->visibleLines($plan, $user);

        return $this->summaryForLines($lines, $user);
    }

    /**
     * @param EloquentCollection<int, StaffEstablishmentLine>|Collection<int, StaffEstablishmentLine> $lines
     * @return array{budgeted:int, filled:int, vacant:int, overstaffed:int, vacancy_rate:float}
     */
    public function summaryForLines(EloquentCollection|Collection $lines, User $user): array
    {
        $budgeted = 0;
        $filled = 0;
        $vacant = 0;
        $overstaffed = 0;

        foreach ($lines as $line) {
            $lineFilled = $this->filledCount($line, $user);
            $budgeted += (int) $line->budgeted_positions;
            $filled += $lineFilled;
            $vacant += max((int) $line->budgeted_positions - $lineFilled, 0);
            $overstaffed += max($lineFilled - (int) $line->budgeted_positions, 0);
        }

        return [
            'budgeted' => $budgeted,
            'filled' => $filled,
            'vacant' => $vacant,
            'overstaffed' => $overstaffed,
            'vacancy_rate' => $budgeted > 0 ? round(($vacant / $budgeted) * 100, 1) : 0.0,
        ];
    }

    /**
     * @return EloquentCollection<int, StaffEstablishmentLine>
     */
    public function visibleLines(StaffEstablishmentPlan $plan, User $user): EloquentCollection
    {
        return $plan->lines()
            ->with(['jobTitle', 'province', 'district', 'facility', 'department', 'plan.project'])
            ->visibleTo($user)
            ->get();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function rowsForPlan(StaffEstablishmentPlan $plan, User $user): array
    {
        return $this->visibleLines($plan, $user)
            ->map(function (StaffEstablishmentLine $line) use ($user) {
                $filled = $this->filledCount($line, $user);

                return [
                    'job_title' => $line->jobTitle?->name ?? '-',
                    'project' => $line->plan?->project?->name ?? 'All projects',
                    'department' => $line->department?->name ?? 'All departments',
                    'province' => $line->province?->name ?? 'Organisation-wide',
                    'district' => $line->district?->name ?? '-',
                    'facility' => $line->facility?->name ?? '-',
                    'budgeted' => (int) $line->budgeted_positions,
                    'filled' => $filled,
                    'vacant' => max((int) $line->budgeted_positions - $filled, 0),
                    'overstaffed' => max($filled - (int) $line->budgeted_positions, 0),
                    'notes' => $line->notes ?? '',
                ];
            })
            ->all();
    }

    public function latestVisiblePlan(User $user): ?StaffEstablishmentPlan
    {
        return StaffEstablishmentPlan::query()
            ->visibleTo($user)
            ->with(['project'])
            ->where('status', StaffEstablishmentPlan::STATUS_APPROVED)
            ->latest('effective_month')
            ->latest('id')
            ->first()
            ?? StaffEstablishmentPlan::query()
                ->visibleTo($user)
                ->with(['project'])
                ->latest('effective_month')
                ->latest('id')
                ->first();
    }

    private function activeEmploymentStatusId(): ?int
    {
        return EmploymentStatus::query()
            ->where('code', 'ACTIVE')
            ->orWhere('name', 'Active')
            ->value('id');
    }
}
