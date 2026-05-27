<?php

namespace App\Support;

class EmployeeNumber
{
    public static function normalize(mixed $value): string
    {
        return str_replace('_', '', trim((string) $value));
    }
}
