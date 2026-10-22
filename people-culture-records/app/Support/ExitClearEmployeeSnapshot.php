<?php

namespace App\Support;

use App\Models\Employee;

class ExitClearEmployeeSnapshot
{
    public static function make(Employee $employee): array
    {
        $employee->loadMissing([
            'department:id,name',
            'jobTitle:id,name',
            'facility:id,name',
            'district:id,name',
            'province:id,name',
            'supervisor:id,employee_no,first_name,last_name,email',
        ]);

        return [
            'source_id' => $employee->id,
            'employee_no' => $employee->employee_no,
            'full_name' => $employee->full_name,
            'hire_date' => $employee->hire_date?->toDateString(),
            'department' => self::namedRecord($employee->department),
            'job_title' => self::namedRecord($employee->jobTitle),
            'facility' => self::namedRecord($employee->facility),
            'district' => self::namedRecord($employee->district),
            'province' => self::namedRecord($employee->province),
            'supervisor' => self::supervisor($employee),
            'updated_at' => $employee->updated_at?->toIso8601String(),
        ];
    }

    private static function supervisor(Employee $employee): ?array
    {
        if ($employee->supervisor) {
            return self::supervisorRecord($employee->supervisor);
        }

        $legacyValue = trim((string) $employee->supervisor_name);

        if ($legacyValue === '') {
            return null;
        }

        [$employeeNumber, $fullName] = str_contains($legacyValue, ' - ')
            ? array_pad(explode(' - ', $legacyValue, 2), 2, null)
            : [null, $legacyValue];

        $linkedEmployee = filled($employeeNumber)
            ? Employee::withTrashed()->where('employee_no', trim($employeeNumber))->first()
            : null;

        if ($linkedEmployee) {
            return self::supervisorRecord($linkedEmployee);
        }

        return [
            'source_id' => null,
            'employee_no' => filled($employeeNumber) ? trim($employeeNumber) : null,
            'full_name' => trim((string) $fullName),
            'work_email' => null,
        ];
    }

    private static function supervisorRecord(Employee $supervisor): array
    {
        return [
            'source_id' => $supervisor->id,
            'employee_no' => $supervisor->employee_no,
            'full_name' => $supervisor->full_name,
            'work_email' => $supervisor->email,
        ];
    }

    private static function namedRecord($record): ?array
    {
        return $record ? ['source_id' => $record->id, 'name' => $record->name] : null;
    }
}
