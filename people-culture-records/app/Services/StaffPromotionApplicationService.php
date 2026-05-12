<?php

namespace App\Services;

use App\Models\StaffPromotion;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class StaffPromotionApplicationService
{
    public function applyForPromotion(StaffPromotion $promotion, ActivityLogger $activity, ?User $user = null, ?Request $request = null): int
    {
        if (! $promotion->employee_id) {
            return 0;
        }

        return $this->applyDuePromotionsForEmployee($promotion->employee_id, $activity, $user, $request);
    }

    public function applyDuePromotionsForEmployee(int $employeeId, ActivityLogger $activity, ?User $user = null, ?Request $request = null): int
    {
        $duePromotions = $this->duePromotionsQuery($employeeId)
            ->whereNull('job_title_applied_at')
            ->with(['employee', 'newJobTitle'])
            ->orderByRaw('coalesce(effective_date, promotion_date)')
            ->orderBy('id')
            ->get();

        if ($duePromotions->isEmpty()) {
            return 0;
        }

        $latestDuePromotion = $this->duePromotionsQuery($employeeId)
            ->with(['employee', 'newJobTitle'])
            ->orderByRaw('coalesce(effective_date, promotion_date) desc')
            ->orderByDesc('id')
            ->first();

        if (! $latestDuePromotion || ! $latestDuePromotion->employee) {
            return 0;
        }

        $employee = $latestDuePromotion->employee;
        $appliedAt = now();
        $changed = (int) $employee->job_title_id !== (int) $latestDuePromotion->new_job_title_id;

        DB::transaction(function () use ($duePromotions, $latestDuePromotion, $employee, $user, $appliedAt, $changed) {
            foreach ($duePromotions as $promotion) {
                $promotion->forceFill([
                    'job_title_applied_at' => $appliedAt,
                    'updated_by' => $user?->id ?? $promotion->updated_by,
                ])->save();
            }

            if ($changed) {
                $employee->update([
                    'job_title_id' => $latestDuePromotion->new_job_title_id,
                    'updated_by' => $user?->id ?? $employee->updated_by,
                ]);
            }
        });

        if ($changed) {
            $activity->log(
                'employee_job_title_updated_from_promotion',
                $this->descriptionFor($latestDuePromotion, $employee, $user),
                $latestDuePromotion,
                user: $user,
                request: $request,
            );
        }

        return $duePromotions->count();
    }

    /**
     * @return Collection<int, int>
     */
    public function dueEmployeeIds(): Collection
    {
        return StaffPromotion::query()
            ->whereNull('deleted_at')
            ->whereNull('job_title_applied_at')
            ->whereDate(DB::raw('coalesce(effective_date, promotion_date)'), '<=', today())
            ->orderBy('employee_id')
            ->pluck('employee_id')
            ->filter()
            ->unique()
            ->values();
    }

    private function duePromotionsQuery(int $employeeId)
    {
        return StaffPromotion::query()
            ->whereNull('deleted_at')
            ->where('employee_id', $employeeId)
            ->whereDate(DB::raw('coalesce(effective_date, promotion_date)'), '<=', today());
    }

    private function descriptionFor(StaffPromotion $promotion, $employee, ?User $user): string
    {
        $actor = $user?->name ?? 'System';
        $triggerDate = $promotion->effective_date ?? $promotion->promotion_date;

        return "{$actor} applied promotion {$promotion->reference_no} and updated {$employee->display_name}'s current job title effective {$triggerDate?->format('d M Y')}.";
    }
}
