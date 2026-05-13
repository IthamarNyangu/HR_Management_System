<?php

namespace App\Services\Imports;

use App\Models\Employee;
use App\Models\ImportBatch;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class EmployeeImportCommitService
{
    public function __construct(private ActivityLogger $activityLogger)
    {
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
                "{$user->name} confirmed Employee Import file {$batch->original_filename}.",
                $batch,
                $this->logProperties($batch, $user)
            );

            $imported = 0;

            foreach ($validRows as $row) {
                $data = $row->normalized_data ?? [];

                Employee::create(array_merge(Arr::only($data, [
                    'employee_no',
                    'first_name',
                    'last_name',
                    'gender',
                    'date_of_birth',
                    'national_id',
                    'email',
                    'phone',
                    'project_id',
                    'department_id',
                    'job_title_id',
                    'province_id',
                    'district_id',
                    'facility_id',
                    'employment_status_id',
                    'hire_date',
                    'supervisor_name',
                    'notes',
                ]), [
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
                "{$user->name} imported {$imported} employees from {$batch->original_filename}.",
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
