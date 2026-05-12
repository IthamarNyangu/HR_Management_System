<?php

namespace App\Services;

use App\Models\StaffRelocation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class StaffRelocationApplicationService
{
    public function applyForRelocation(StaffRelocation $relocation, ActivityLogger $activity, ?User $user = null, ?Request $request = null): int
    {
        if (! $relocation->employee_id) {
            return 0;
        }

        return $this->applyDueRelocationsForEmployee($relocation->employee_id, $activity, $user, $request);
    }

    public function applyDueRelocationsForEmployee(int $employeeId, ActivityLogger $activity, ?User $user = null, ?Request $request = null): int
    {
        $dueRelocations = $this->dueRelocationsQuery($employeeId)
            ->whereNull('location_applied_at')
            ->with(['employee', 'fromProvince', 'fromFacility', 'toProvince', 'toDistrict', 'toFacility'])
            ->orderBy('effective_date')
            ->orderBy('id')
            ->get();

        if ($dueRelocations->isEmpty()) {
            return 0;
        }

        $latestDueRelocation = $this->dueRelocationsQuery($employeeId)
            ->with(['employee', 'fromProvince', 'fromFacility', 'toProvince', 'toDistrict', 'toFacility'])
            ->orderByDesc('effective_date')
            ->orderByDesc('id')
            ->first();

        if (! $latestDueRelocation || ! $latestDueRelocation->employee) {
            return 0;
        }

        $employee = $latestDueRelocation->employee;
        $appliedAt = now();
        $changed = (int) $employee->province_id !== (int) $latestDueRelocation->to_province_id
            || (int) $employee->district_id !== (int) $latestDueRelocation->to_district_id
            || (int) ($employee->facility_id ?? 0) !== (int) ($latestDueRelocation->to_facility_id ?? 0);

        DB::transaction(function () use ($dueRelocations, $latestDueRelocation, $employee, $user, $appliedAt, $changed) {
            foreach ($dueRelocations as $relocation) {
                $relocation->forceFill([
                    'location_applied_at' => $appliedAt,
                    'updated_by' => $user?->id ?? $relocation->updated_by,
                ])->save();
            }

            if ($changed) {
                $employee->update([
                    'province_id' => $latestDueRelocation->to_province_id,
                    'district_id' => $latestDueRelocation->to_district_id,
                    'facility_id' => $latestDueRelocation->to_facility_id,
                    'updated_by' => $user?->id ?? $employee->updated_by,
                ]);
            }
        });

        if ($changed) {
            $activity->log(
                'employee_location_updated_from_relocation',
                $this->descriptionFor($latestDueRelocation, $employee, $user),
                $latestDueRelocation,
                user: $user,
                request: $request,
            );
        }

        return $dueRelocations->count();
    }

    /**
     * @return Collection<int, int>
     */
    public function dueEmployeeIds(): Collection
    {
        return StaffRelocation::query()
            ->whereNull('deleted_at')
            ->whereNull('location_applied_at')
            ->whereDate('effective_date', '<=', today())
            ->orderBy('employee_id')
            ->pluck('employee_id')
            ->filter()
            ->unique()
            ->values();
    }

    private function dueRelocationsQuery(int $employeeId)
    {
        return StaffRelocation::query()
            ->whereNull('deleted_at')
            ->where('employee_id', $employeeId)
            ->whereDate('effective_date', '<=', today());
    }

    private function descriptionFor(StaffRelocation $relocation, $employee, ?User $user): string
    {
        $actor = $user?->name ?? 'System';

        return "{$actor} applied relocation {$relocation->reference_no} and updated {$employee->display_name}'s current location effective {$relocation->effective_date?->format('d M Y')}.";
    }
}
