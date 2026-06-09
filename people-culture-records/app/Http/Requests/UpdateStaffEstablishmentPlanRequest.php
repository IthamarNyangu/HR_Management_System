<?php

namespace App\Http\Requests;

use App\Models\StaffEstablishmentPlan;

class UpdateStaffEstablishmentPlanRequest extends StoreStaffEstablishmentPlanRequest
{
    public function authorize(): bool
    {
        $plan = $this->route('staff_establishment_plan');

        return $plan instanceof StaffEstablishmentPlan
            && ($this->user()?->can('update', $plan) ?? false);
    }
}
