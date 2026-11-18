<?php

namespace App\Http\Requests;

use App\Models\FuneralClaim;
use Illuminate\Foundation\Http\FormRequest;

class UpdateEmployeeFuneralGrantAmountRequest extends FormRequest
{
    public function authorize(): bool
    {
        $claim = $this->route('funeral_claim');

        return $claim instanceof FuneralClaim
            && ($this->user()?->can('update', $claim) ?? false);
    }

    public function rules(): array
    {
        return [
            'eligible_amount' => ['required', 'numeric', 'min:0.01', 'max:9999999999.99'],
        ];
    }

    public function attributes(): array
    {
        return [
            'eligible_amount' => 'proposed funeral grant amount',
        ];
    }
}
