<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\District;
use App\Models\Facility;
use App\Models\Province;
use Illuminate\Http\JsonResponse;

class ExitClearOrganisationDirectoryController extends Controller
{
    public function __invoke(): JsonResponse
    {
        return response()->json([
            'data' => [
                'provinces' => Province::query()
                    ->orderBy('name')
                    ->get()
                    ->map(fn (Province $province): array => $this->record($province))
                    ->values(),
                'districts' => District::query()
                    ->orderBy('name')
                    ->get()
                    ->map(fn (District $district): array => $this->record($district) + [
                        'province_source_id' => $district->province_id,
                    ])
                    ->values(),
                'facilities' => Facility::query()
                    ->with('district:id,province_id')
                    ->orderBy('name')
                    ->get()
                    ->map(fn (Facility $facility): array => $this->record($facility) + [
                        'district_source_id' => $facility->district_id,
                        'province_source_id' => $facility->district?->province_id,
                    ])
                    ->values(),
                'departments' => Department::query()
                    ->orderBy('name')
                    ->get()
                    ->map(fn (Department $department): array => $this->record($department))
                    ->values(),
            ],
            'meta' => [
                'generated_at' => now()->toIso8601String(),
            ],
        ]);
    }

    private function record($record): array
    {
        return [
            'source_id' => $record->id,
            'name' => $record->name,
            'code' => $record->code,
            'is_active' => (bool) $record->is_active,
            'updated_at' => $record->updated_at?->toIso8601String(),
        ];
    }
}
