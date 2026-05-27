<?php

namespace App\Console\Commands;

use App\Models\Department;
use App\Models\District;
use App\Models\Facility;
use App\Models\ImportBatch;
use App\Models\Province;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class SyncEmployeeImportMasterData extends Command
{
    protected $signature = 'imports:sync-employee-master-data {batch? : Import batch ID or reference number}';

    protected $description = 'Create missing employee import departments and facilities from a previewed employee import batch.';

    public function handle(): int
    {
        $batch = $this->batch();

        if (! $batch) {
            $this->error('No employee import batch was found.');

            return self::FAILURE;
        }

        $this->info("Using {$batch->reference_no} ({$batch->original_filename}).");

        $departmentsCreated = 0;
        $facilitiesCreated = 0;
        $unresolvedLocations = [];

        $this->fixKnownDistrictNames();

        foreach ($batch->rows()->cursor() as $row) {
            $raw = $row->raw_data ?? [];
            $departmentName = $this->cleanName($raw['department'] ?? null);

            if ($departmentName !== null && ! Department::where('name', $departmentName)->exists()) {
                Department::create([
                    'name' => $departmentName,
                    'code' => $this->code($departmentName),
                    'description' => null,
                    'is_active' => true,
                ]);

                $departmentsCreated++;
            }

            $facilityName = $this->cleanName($raw['facility'] ?? null);

            if ($facilityName === null) {
                continue;
            }

            $district = $this->resolveDistrict($raw['province'] ?? null, $raw['district'] ?? null);

            if (! $district) {
                $unresolvedLocations[$this->cleanName($raw['province'] ?? null).' / '.$this->cleanName($raw['district'] ?? null)] = true;

                continue;
            }

            if (! Facility::where('district_id', $district->id)->where('name', $facilityName)->exists()) {
                Facility::create([
                    'district_id' => $district->id,
                    'name' => $facilityName,
                    'code' => $this->code($facilityName, 20),
                    'description' => null,
                    'is_active' => true,
                ]);

                $facilitiesCreated++;
            }
        }

        $this->info("Departments created: {$departmentsCreated}");
        $this->info("Facilities created: {$facilitiesCreated}");

        if ($unresolvedLocations !== []) {
            $this->warn('Some facility rows were skipped because province/district could not be resolved:');

            foreach (array_keys($unresolvedLocations) as $location) {
                $this->line("- {$location}");
            }
        }

        $this->info('Done. Cancel this preview batch, upload the employee file again, and review the new validation results.');

        return self::SUCCESS;
    }

    private function batch(): ?ImportBatch
    {
        $identifier = $this->argument('batch');

        return ImportBatch::query()
            ->where('import_type', 'employees')
            ->when($identifier, function ($query) use ($identifier) {
                $query->where(function ($query) use ($identifier) {
                    $query->where('id', $identifier)
                        ->orWhere('reference_no', $identifier);
                });
            })
            ->latest()
            ->first();
    }

    private function resolveDistrict(mixed $provinceValue, mixed $districtValue): ?District
    {
        $provinceKey = $this->lookupKey($provinceValue);
        $districtKey = $this->districtAlias($this->lookupKey($districtValue));

        if ($districtKey === '') {
            return null;
        }

        $province = Province::query()
            ->get()
            ->first(fn (Province $province) => $this->lookupKey($province->name) === $provinceKey || $this->lookupKey($province->code) === $provinceKey);

        return District::query()
            ->with('province')
            ->get()
            ->first(function (District $district) use ($districtKey, $province) {
                $matchesDistrict = $this->districtAlias($this->lookupKey($district->name)) === $districtKey
                    || $this->lookupKey($district->code) === $districtKey;

                if (! $matchesDistrict) {
                    return false;
                }

                return ! $province || (int) $district->province_id === (int) $province->id;
            });
    }

    private function fixKnownDistrictNames(): void
    {
        District::query()
            ->where('name', 'Shiwangâ€™andu')
            ->update(['name' => "Shiwang'andu", 'code' => 'MUC-SHI']);
    }

    private function districtAlias(string $value): string
    {
        return match ($value) {
            'senga' => 'senga hill',
            default => $value,
        };
    }

    private function cleanName(mixed $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        return trim((string) preg_replace('/\s+/', ' ', (string) $value));
    }

    private function lookupKey(mixed $value): string
    {
        $value = str_replace(['â€™', '’', '‘', '`'], "'", (string) $value);
        $value = trim((string) preg_replace('/\s+/', ' ', mb_strtolower($value)));

        return trim((string) preg_replace('/\s+(province|district)$/', '', $value));
    }

    private function code(string $name, int $limit = 30): string
    {
        return Str::of($name)
            ->ascii()
            ->upper()
            ->replaceMatches('/[^A-Z0-9]+/', '-')
            ->trim('-')
            ->limit($limit, '')
            ->toString();
    }
}
