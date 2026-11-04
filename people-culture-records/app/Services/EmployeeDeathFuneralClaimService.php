<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\FuneralClaim;
use App\Models\FuneralClaimDocument;
use App\Models\TerminationReason;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class EmployeeDeathFuneralClaimService
{
    public function __construct(
        private readonly FuneralClaimEligibilityService $eligibility,
        private readonly ReferenceNumberService $references,
        private readonly ActivityLogger $activity,
    ) {}

    public function createIfRequired(Employee $employee): ?FuneralClaim
    {
        if (! $this->qualifies($employee)) {
            return null;
        }

        return DB::transaction(function () use ($employee): ?FuneralClaim {
            $lockedEmployee = Employee::query()->lockForUpdate()->find($employee->id);

            if (! $lockedEmployee || ! $this->qualifies($lockedEmployee)) {
                return null;
            }

            $existing = FuneralClaim::query()
                ->where('employee_id', $lockedEmployee->id)
                ->where('deceased_category', FuneralClaim::CATEGORY_EMPLOYEE)
                ->where('status', '!=', FuneralClaim::STATUS_VOIDED)
                ->latest('id')
                ->first();

            if ($existing) {
                return $existing;
            }

            $deathDate = $lockedEmployee->termination_date;
            $calculation = $this->eligibility->calculate(
                $lockedEmployee,
                FuneralClaim::CATEGORY_EMPLOYEE,
                $deathDate,
            );
            $actor = $lockedEmployee->updated_by ? User::find($lockedEmployee->updated_by) : null;

            $claim = FuneralClaim::create(array_merge($calculation, [
                'reference_no' => $this->references->generate('FCL', 'funeral_claims'),
                'employee_id' => $lockedEmployee->id,
                'province_id' => $lockedEmployee->province_id,
                'deceased_category' => FuneralClaim::CATEGORY_EMPLOYEE,
                'notification_date' => today(),
                'date_of_death' => $deathDate,
                'status' => FuneralClaim::STATUS_AWAITING_DOCUMENTS,
                'assigned_to' => $actor?->id,
                'internal_notes' => 'Automatically registered from an employee termination recorded as Deceased.',
                'family_meeting_required' => false,
                'family_meeting_status' => 'not_required',
                'last_status_changed_by' => $actor?->id,
                'last_status_changed_at' => now(),
                'created_by' => $actor?->id,
                'updated_by' => $actor?->id,
            ]));

            $claim->documents()->createMany([
                ['document_type' => 'death_certificate', 'is_required' => true, 'status' => FuneralClaimDocument::PENDING],
                ['document_type' => 'certified_nrc', 'is_required' => false, 'status' => FuneralClaimDocument::PENDING],
            ]);
            $claim->statusHistories()->create([
                'to_status' => FuneralClaim::STATUS_AWAITING_DOCUMENTS,
                'changed_by' => $actor?->id,
                'comment' => 'Claim automatically registered when the employee was terminated as deceased.',
            ]);

            $this->activity->log(
                'employee_death_funeral_claim_created',
                "System automatically registered funeral claim {$claim->reference_no} for {$lockedEmployee->display_name}.",
                $claim,
                ['source' => 'employee_termination'],
                user: $actor,
            );

            return $claim;
        });
    }

    private function qualifies(Employee $employee): bool
    {
        if (! $employee->termination_date || ! $employee->province_id || ! $employee->isTerminated()) {
            return false;
        }

        return TerminationReason::query()
            ->whereKey($employee->termination_reason_id)
            ->where(function ($query): void {
                $query->whereRaw('UPPER(code) = ?', ['DECEASED'])
                    ->orWhereRaw('LOWER(name) = ?', ['deceased']);
            })
            ->exists();
    }
}
