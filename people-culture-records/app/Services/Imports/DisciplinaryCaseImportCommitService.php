<?php

namespace App\Services\Imports;

use App\Models\DisciplinaryCase;
use App\Models\ImportBatch;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\ReferenceNumberService;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class DisciplinaryCaseImportCommitService
{
    public function __construct(
        private ReferenceNumberService $referenceNumberService,
        private ActivityLogger $activityLogger,
    ) {
    }

    public function commit(ImportBatch $batch, User $user): ImportBatch
    {
        return DB::transaction(function () use ($batch, $user) {
            $batch = ImportBatch::query()->lockForUpdate()->with('rows')->findOrFail($batch->id);

            if ($batch->isFinal()) {
                return $batch;
            }

            $validRows = $batch->rows()->where('status', 'valid')->lockForUpdate()->get();

            $this->activityLogger->log(
                'import_confirmed',
                "{$user->name} confirmed Disciplinary Cases Import file {$batch->original_filename}.",
                $batch,
                $this->logProperties($batch, $user)
            );

            $imported = 0;

            foreach ($validRows as $row) {
                $data = $row->normalized_data ?? [];
                $caseData = Arr::only($data, [
                    'employee_id',
                    'project_id',
                    'province_id',
                    'district_id',
                    'facility_id',
                    'supervisor_name',
                    'nature_of_offence',
                    'offence_category_id',
                    'penalty_type_id',
                    'case_status_id',
                    'effective_date',
                    'expiry_date',
                    'comment',
                ]);

                DisciplinaryCase::create(array_merge($caseData, [
                    'reference_no' => $this->referenceNumberService->generate('DC', 'disciplinary_cases'),
                    'created_by' => $user->id,
                    'updated_by' => $user->id,
                ]));

                $row->update(['status' => 'imported']);
                $imported++;
            }

            $batch->update([
                'status' => 'imported',
                'imported_rows' => $imported,
                'confirmed_at' => now(),
                'completed_at' => now(),
            ]);

            $this->activityLogger->log(
                'import_completed',
                "{$user->name} imported {$imported} disciplinary cases from {$batch->original_filename}.",
                $batch->fresh(),
                $this->logProperties($batch->fresh(), $user)
            );

            return $batch->fresh(['rows']);
        });
    }

    /**
     * @return array<string, mixed>
     */
    private function logProperties(ImportBatch $batch, User $user): array
    {
        return [
            'import_type' => $batch->import_type,
            'batch_id' => $batch->id,
            'reference_no' => $batch->reference_no,
            'original_filename' => $batch->original_filename,
            'total_rows' => $batch->total_rows,
            'valid_rows' => $batch->valid_rows,
            'invalid_rows' => $batch->invalid_rows,
            'duplicate_rows' => $batch->duplicate_rows,
            'imported_rows' => $batch->imported_rows,
            'province_id' => $user->province_id,
            'province_name' => $user->province?->name,
        ];
    }
}
