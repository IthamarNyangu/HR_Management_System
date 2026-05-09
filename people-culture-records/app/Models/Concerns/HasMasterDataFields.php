<?php

namespace App\Models\Concerns;

trait HasMasterDataFields
{
    public function initializeHasMasterDataFields(): void
    {
        $this->fillable = [
            'name',
            'code',
            'description',
            'is_active',
        ];
    }

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }
}
