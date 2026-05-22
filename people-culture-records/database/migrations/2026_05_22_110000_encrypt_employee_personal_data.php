<?php

use Carbon\Carbon;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE employees MODIFY date_of_birth TEXT NULL, MODIFY national_id TEXT NULL');
        }

        DB::table('employees')->orderBy('id')->chunkById(100, function ($employees) {
            foreach ($employees as $employee) {
                DB::table('employees')
                    ->where('id', $employee->id)
                    ->update([
                        'date_of_birth' => $this->encryptDateValue($employee->date_of_birth),
                        'national_id' => $this->encryptStringValue($employee->national_id),
                        'notes' => $this->encryptStringValue($employee->notes),
                    ]);
            }
        });
    }

    public function down(): void
    {
        DB::table('employees')->orderBy('id')->chunkById(100, function ($employees) {
            foreach ($employees as $employee) {
                DB::table('employees')
                    ->where('id', $employee->id)
                    ->update([
                        'date_of_birth' => $this->decryptStringValue($employee->date_of_birth),
                        'national_id' => $this->decryptStringValue($employee->national_id),
                        'notes' => $this->decryptStringValue($employee->notes),
                    ]);
            }
        });

        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE employees MODIFY date_of_birth DATE NULL, MODIFY national_id VARCHAR(255) NULL');
        }
    }

    private function encryptDateValue(mixed $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        try {
            Crypt::decryptString((string) $value);

            return (string) $value;
        } catch (DecryptException) {
            return Crypt::encryptString(Carbon::parse((string) $value)->toDateString());
        }
    }

    private function encryptStringValue(mixed $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        try {
            Crypt::decryptString((string) $value);

            return (string) $value;
        } catch (DecryptException) {
            return Crypt::encryptString((string) $value);
        }
    }

    private function decryptStringValue(mixed $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        try {
            return Crypt::decryptString((string) $value);
        } catch (DecryptException) {
            return (string) $value;
        }
    }
};
