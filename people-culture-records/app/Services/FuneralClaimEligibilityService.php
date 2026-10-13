<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\EmployeeDependant;
use App\Models\FuneralBenefitRule;
use App\Models\FuneralClaim;
use Carbon\CarbonInterface;

class FuneralClaimEligibilityService
{
    public function calculate(Employee $employee, string $category, CarbonInterface $deathDate, ?EmployeeDependant $dependant = null, ?string $manualName = null, ?CarbonInterface $manualDob = null, ?float $gestationalWeeks = null): array
    {
        $dob = $category === FuneralClaim::CATEGORY_EMPLOYEE ? $employee->date_of_birth : ($dependant?->date_of_birth ?? $manualDob);
        $name = $category === FuneralClaim::CATEGORY_EMPLOYEE ? $employee->display_name : ($dependant?->full_name ?? trim((string) $manualName));
        $age = $dob ? $dob->diffInYears($deathDate) : null;
        $status = FuneralClaim::ELIGIBLE;
        $reason = '';
        $ruleCode = null;

        if ($category === FuneralClaim::CATEGORY_EMPLOYEE) {
            if ($age === null) {
                [$status, $reason, $ruleCode] = [FuneralClaim::REQUIRES_REVIEW, 'Employee date of birth is missing; age eligibility requires review.', 'EMPLOYEE_STANDARD'];
            } elseif ($age > 70) {
                [$status, $reason, $ruleCode] = [FuneralClaim::REQUIRES_REVIEW, 'Employee was over 70 and requires internal approval for the organisational funeral grant.', 'EMPLOYEE_OVER_70'];
            } else {
                [$reason, $ruleCode] = ['Employee qualifies for the organisational funeral grant advance.', 'EMPLOYEE_STANDARD'];
            }
        } elseif ($category === FuneralClaim::CATEGORY_SPOUSE) {
            [$reason, $ruleCode] = ['Spouse qualifies for the funeral benefit.', 'SPOUSE_STANDARD'];
        } elseif ($gestationalWeeks !== null) {
            [$reason, $ruleCode] = ['Preterm child qualifies for the funeral benefit.', 'CHILD_PRETERM'];
        } elseif ($age === null) {
            [$status, $reason] = [FuneralClaim::REQUIRES_REVIEW, 'A date of birth or gestational age is required to confirm child eligibility.'];
        } elseif ($age <= 6) {
            [$reason, $ruleCode] = ['Child aged from birth to 6 years qualifies for the funeral benefit.', 'CHILD_0_6'];
        } elseif ($age <= 21) {
            [$reason, $ruleCode] = ['Child aged 7 to 21 years qualifies for the funeral benefit.', 'CHILD_7_21'];
        } else {
            [$status, $reason] = [FuneralClaim::INELIGIBLE, 'Child was over the maximum eligible age of 21 years.'];
        }

        if ($category !== FuneralClaim::CATEGORY_EMPLOYEE && (! $dependant || $dependant->verification_status !== EmployeeDependant::VERIFICATION_VERIFIED)) {
            if ($status !== FuneralClaim::INELIGIBLE) {
                $status = FuneralClaim::REQUIRES_REVIEW;
            }
            $reason .= ' The dependant is not registered and verified and requires internal review.';
        }

        $rule = $ruleCode ? FuneralBenefitRule::where('code', $ruleCode)->where('is_active', true)->first() : null;

        return [
            'deceased_name' => $name,
            'deceased_date_of_birth' => $dob?->toDateString(),
            'age_at_death_years' => $age,
            'eligibility_status' => $status,
            'eligibility_reason' => trim($reason),
            'funeral_benefit_rule_id' => $rule?->id,
            'eligible_amount' => $rule?->amount,
            'funding_source' => $rule?->funding_source,
            'claimant_context' => $employee->termination_date && $employee->termination_date->lte($deathDate) ? 'former_employee' : 'current_employee',
        ];
    }
}
