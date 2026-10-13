<?php

namespace App\Http\Requests;

use App\Models\FuneralClaim;

class UpdateFuneralClaimRequest extends StoreFuneralClaimRequest
{
    public function authorize(): bool
    {
        $claim = $this->route('funeral_claim');

        return $claim instanceof FuneralClaim && ($this->user()?->can('update', $claim) ?? false);
    }
}
