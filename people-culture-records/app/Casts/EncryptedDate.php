<?php

namespace App\Casts;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

/**
 * @implements CastsAttributes<Model, CarbonImmutable|null>
 */
class EncryptedDate implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): ?CarbonImmutable
    {
        if (blank($value)) {
            return null;
        }

        try {
            $value = Crypt::decryptString((string) $value);
        } catch (DecryptException) {
            // Allows older plaintext values to be read until they are migrated.
        }

        return CarbonImmutable::parse((string) $value)->startOfDay();
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if (blank($value)) {
            return null;
        }

        if ($value instanceof CarbonInterface) {
            $value = $value->toDateString();
        } else {
            $value = CarbonImmutable::parse((string) $value)->toDateString();
        }

        return Crypt::encryptString($value);
    }
}
