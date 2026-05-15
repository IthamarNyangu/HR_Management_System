<?php

namespace App\Services\Imports;

use App\Models\CaseStatus;
use App\Models\DisciplinaryCase;
use App\Models\District;
use App\Models\Employee;
use App\Models\Facility;
use App\Models\ImportBatch;
use App\Models\OffenceCategory;
use App\Models\PenaltyType;
use App\Models\Project;
use App\Models\Province;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\ReferenceNumberService;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use RuntimeException;
use Throwable;

class DisciplinaryCaseImportPreviewService
{
    /**
     * @var array<string, list<string>>
     */
    private array $aliases = [
        'employee_no' => ['employee_no', 'employee_number', 'employee_no_', 'emp_no', 'staff_id', 'staff_no', 'staff_number'],
        'first_name' => ['first_name', 'firstname', 'given_name'],
        'last_name' => ['last_name', 'lastname', 'surname', 'family_name'],
        'project' => ['project', 'project_name', 'project_code'],
        'province' => ['province', 'province_name', 'province_code'],
        'district' => ['district', 'district_name', 'district_code'],
        'facility' => ['facility', 'facility_name', 'site', 'site_name'],
        'supervisor_name' => ['supervisor_name', 'supervisor', 'line_manager'],
        'nature_of_offence' => ['nature_of_offence', 'offence', 'nature_of_offense', 'nature_of_offence_', 'nature_of_offence_details'],
        'offence_category' => ['offence_category', 'category_of_offence', 'offence_category_name', 'offense_category'],
        'penalty_type' => ['penalty_type', 'penalty', 'penalty_given', 'penalty_type_name'],
        'case_status' => ['case_status', 'status', 'case_status_name'],
        'effective_date' => ['effective_date', 'effective'],
        'expiry_date' => ['expiry_date', 'expiry', 'expiration_date'],
        'comment' => ['comment', 'comments', 'notes'],
    ];

    public function __construct(
        private ReferenceNumberService $referenceNumberService,
        private ActivityLogger $activityLogger,
    ) {
    }

    public function preview(UploadedFile $file, User $user): ImportBatch
    {
        $path = $file->store('imports/disciplinary-cases', 'local');
        $batch = null;

        try {
            [$rawRows, $workbookWarnings] = $this->readRows($path);
            $normalizedRows = $rawRows->map(fn (array $row) => $this->normalizeRow($row));
            $employees = Employee::query()
                ->whereIn('employee_no', $normalizedRows->pluck('employee_no')->filter()->all())
                ->with(['province'])
                ->get()
                ->keyBy(fn (Employee $employee) => $this->lookupKey($employee->employee_no));
            $lookups = $this->lookups();

            $validatedRows = $normalizedRows->map(fn (array $normalized) => $this->validateRow($normalized, $lookups, $employees, $user));
            $fileDuplicateKeys = $validatedRows
                ->pluck('duplicate_key')
                ->filter()
                ->duplicates()
                ->unique()
                ->all();

            return DB::transaction(function () use ($file, $path, $user, $rawRows, $validatedRows, $fileDuplicateKeys, $workbookWarnings, &$batch) {
                $batch = ImportBatch::create([
                    'reference_no' => $this->referenceNumberService->generate('IMP', 'import_batches'),
                    'import_type' => 'disciplinary_cases',
                    'original_filename' => $file->getClientOriginalName(),
                    'stored_filename' => basename($path),
                    'file_path' => $path,
                    'uploaded_by' => $user->id,
                    'status' => 'uploaded',
                    'total_rows' => $rawRows->count(),
                ]);

                $this->activityLogger->log(
                    'import_uploaded',
                    "{$user->name} uploaded Disciplinary Cases Import file {$batch->original_filename}.",
                    $batch,
                    $this->logProperties($batch, $user)
                );

                $counts = ['valid' => 0, 'invalid' => 0, 'duplicate' => 0];
                $errorSummary = [];

                foreach ($rawRows as $index => $rawRow) {
                    $validated = $validatedRows[$index];
                    $errors = $validated['errors'];
                    $warnings = $validated['warnings'];

                    if ($validated['duplicate_key'] && in_array($validated['duplicate_key'], $fileDuplicateKeys, true)) {
                        $errors[] = 'This disciplinary case is duplicated within the import file.';
                    }

                    $status = collect($errors)->contains(fn (string $error) => str_contains($error, 'duplicated') || str_contains($error, 'already exists'))
                        ? 'duplicate'
                        : ($errors === [] ? 'valid' : 'invalid');

                    $counts[$status]++;

                    foreach ($errors as $error) {
                        $errorSummary[$error] = ($errorSummary[$error] ?? 0) + 1;
                    }

                    $batch->rows()->create([
                        'row_number' => $rawRow['_row_number'] ?? ($index + 2),
                        'raw_data' => collect($rawRow)->except('_row_number')->all(),
                        'normalized_data' => $validated['data'],
                        'status' => $status,
                        'errors' => $errors === [] ? null : $errors,
                        'warnings' => $warnings === [] ? null : $warnings,
                        'matched_employee_id' => $validated['data']['employee_id'] ?? null,
                    ]);
                }

                $batch->update([
                    'status' => 'previewed',
                    'valid_rows' => $counts['valid'],
                    'invalid_rows' => $counts['invalid'],
                    'duplicate_rows' => $counts['duplicate'],
                    'error_summary' => $this->buildErrorSummary($errorSummary, $workbookWarnings),
                ]);

                $this->activityLogger->log(
                    'import_previewed',
                    "{$user->name} previewed Disciplinary Cases Import file {$batch->original_filename}.",
                    $batch->fresh(),
                    $this->logProperties($batch->fresh(), $user)
                );

                return $batch->fresh(['rows']);
            });
        } catch (Throwable $exception) {
            if ($batch) {
                $batch->update([
                    'status' => 'failed',
                    'error_summary' => ['Import failed: '.$exception->getMessage()],
                ]);

                $this->activityLogger->log(
                    'import_failed',
                    "Disciplinary Cases Import file {$batch->original_filename} failed during preview.",
                    $batch->fresh(),
                    $this->logProperties($batch->fresh(), $user)
                );
            }

            throw $exception;
        }
    }

    /**
     * @return array{0: Collection<int, array<string, mixed>>, 1: list<string>}
     */
    private function readRows(string $path): array
    {
        $absolutePath = Storage::disk('local')->path($path);
        $reader = IOFactory::createReaderForFile($absolutePath);
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($absolutePath);
        $sheetNames = $spreadsheet->getSheetNames();
        $warnings = [];

        if (count($sheetNames) > 1) {
            if (! in_array('Disciplinary_Cases_Import', $sheetNames, true)) {
                throw new RuntimeException('Workbook has multiple sheets but no Disciplinary_Cases_Import sheet. Please use the disciplinary cases import template or rename the case sheet to Disciplinary_Cases_Import.');
            }

            $worksheet = $spreadsheet->getSheetByName('Disciplinary_Cases_Import');
            $warnings[] = 'Workbook has multiple sheets. Only the Disciplinary_Cases_Import sheet was processed.';
        } else {
            $worksheet = $spreadsheet->getSheet(0);
        }

        return [$this->nonEmptyRowsFromWorksheet($worksheet), $warnings];
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function nonEmptyRowsFromWorksheet(Worksheet $worksheet): Collection
    {
        $sheetRows = $worksheet->toArray(null, true, true, true);
        $headingRow = array_shift($sheetRows) ?? [];
        $headings = [];

        foreach ($headingRow as $column => $heading) {
            $normalizedHeading = $this->normalizeKey((string) $heading);

            if ($normalizedHeading !== '') {
                $headings[$column] = $normalizedHeading;
            }
        }

        return collect($sheetRows)
            ->map(function (array $row, int $index) use ($headings) {
                $normalized = ['_row_number' => $index + 2];

                foreach ($headings as $column => $heading) {
                    $value = $row[$column] ?? null;
                    $normalized[$heading] = is_string($value) ? trim($value) : $value;
                }

                return $normalized;
            })
            ->filter(function (array $row) {
                return collect($row)
                    ->except('_row_number')
                    ->filter(fn ($value) => $value !== null && $value !== '')
                    ->isNotEmpty();
            })
            ->values();
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function normalizeRow(array $row): array
    {
        $normalized = [];

        foreach ($this->aliases as $field => $aliases) {
            $normalized[$field] = null;

            foreach ($aliases as $alias) {
                if (array_key_exists($alias, $row) && $row[$alias] !== '') {
                    $normalized[$field] = is_string($row[$alias]) ? trim($row[$alias]) : $row[$alias];
                    break;
                }
            }
        }

        return $normalized;
    }

    /**
     * @return array<string, mixed>
     */
    private function lookups(): array
    {
        return [
            'projects' => $this->masterLookup(Project::query()->get()),
            'provinces' => $this->masterLookup(Province::query()->get()),
            'districts' => $this->masterLookup(District::query()->with('province')->get()),
            'facilities' => $this->masterLookup(Facility::query()->with('district')->get()),
            'offence_categories' => $this->masterLookup(OffenceCategory::query()->get()),
            'penalty_types' => $this->masterLookup(PenaltyType::query()->get()),
            'case_statuses' => $this->masterLookup(CaseStatus::query()->get()),
        ];
    }

    /**
     * @param Collection<int, mixed> $records
     * @return array<string, mixed>
     */
    private function masterLookup(Collection $records): array
    {
        $lookup = [];

        foreach ($records as $record) {
            $lookup[$this->lookupKey($record->name)] = $record;

            if ($record->code) {
                $lookup[$this->lookupKey($record->code)] = $record;
            }
        }

        return $lookup;
    }

    /**
     * @param array<string, mixed> $normalized
     * @param array<string, mixed> $lookups
     * @param Collection<string, Employee> $employees
     * @return array{data: array<string, mixed>, errors: list<string>, warnings: list<string>, duplicate_key: string|null}
     */
    private function validateRow(array $normalized, array $lookups, Collection $employees, User $user): array
    {
        $errors = [];
        $warnings = [];

        foreach (['employee_no' => 'Employee number', 'province' => 'Province', 'district' => 'District', 'nature_of_offence' => 'Nature of offence', 'effective_date' => 'Effective date'] as $field => $label) {
            if (blank($normalized[$field])) {
                $errors[] = "{$label} is required.";
            }
        }

        $employee = null;
        $employeeNoKey = $this->lookupKey($normalized['employee_no']);

        if ($employeeNoKey !== '') {
            $employee = $employees->get($employeeNoKey);

            if (! $employee) {
                $errors[] = "Employee number '{$normalized['employee_no']}' does not exist.";
            }
        }

        if ($employee) {
            if (filled($normalized['first_name']) && $this->lookupKey($normalized['first_name']) !== $this->lookupKey($employee->first_name)) {
                $warnings[] = "First name '{$normalized['first_name']}' does not match employee record '{$employee->first_name}'.";
            }

            if (filled($normalized['last_name']) && $this->lookupKey($normalized['last_name']) !== $this->lookupKey($employee->last_name)) {
                $warnings[] = "Last name '{$normalized['last_name']}' does not match employee record '{$employee->last_name}'.";
            }
        }

        $project = $this->matchOptionalMaster($normalized['project'], $lookups['projects'], 'Project', $errors);
        $province = $this->matchRequiredMaster($normalized['province'], $lookups['provinces'], 'Province', $errors);
        $district = $this->matchRequiredMaster($normalized['district'], $lookups['districts'], 'District', $errors);
        $facility = $this->matchOptionalMaster($normalized['facility'], $lookups['facilities'], 'Facility', $errors);
        $offenceCategory = $this->matchOptionalMaster($normalized['offence_category'], $lookups['offence_categories'], 'Offence category', $errors);
        $penaltyType = $this->matchOptionalMaster($normalized['penalty_type'], $lookups['penalty_types'], 'Penalty type', $errors);
        $caseStatus = blank($normalized['case_status'])
            ? $this->matchRequiredMaster('Draft', $lookups['case_statuses'], 'Case status', $errors)
            : $this->matchRequiredMaster($normalized['case_status'], $lookups['case_statuses'], 'Case status', $errors);

        if ($province && $district && (int) $district->province_id !== (int) $province->id) {
            $errors[] = "District '{$district->name}' does not belong to province '{$province->name}'.";
        }

        if ($district && $facility && (int) $facility->district_id !== (int) $district->id) {
            $errors[] = "Facility '{$facility->name}' does not belong to district '{$district->name}'.";
        }

        if ($employee && $province && (int) $employee->province_id !== (int) $province->id) {
            $errors[] = "Employee '{$employee->employee_no}' does not belong to province '{$province->name}'.";
        }

        if ($user->hasRole('HR Officer') && $province && (int) $province->id !== (int) $user->province_id) {
            $errors[] = 'HR Officers can only import disciplinary cases for their assigned province.';
        }

        if ($user->hasRole('HR Officer') && $caseStatus && strtoupper((string) $caseStatus->code) === 'ACTIVE') {
            $errors[] = 'HR Officers cannot import Active disciplinary cases.';
        }

        $effectiveDate = $this->parseDate($normalized['effective_date'], 'Effective date', $errors);
        $expiryDate = $this->parseDate($normalized['expiry_date'], 'Expiry date', $errors);

        if ($effectiveDate && $expiryDate && Carbon::parse($expiryDate)->lt(Carbon::parse($effectiveDate))) {
            $errors[] = 'Expiry date must be after or equal to effective date.';
        }

        $data = [
            'employee_id' => $employee?->id,
            'employee_no' => $employee?->employee_no ?? $normalized['employee_no'],
            'employee_name' => $employee?->full_name,
            'project_id' => $project?->id,
            'project_name' => $project?->name,
            'province_id' => $province?->id,
            'province_name' => $province?->name,
            'district_id' => $district?->id,
            'district_name' => $district?->name,
            'facility_id' => $facility?->id,
            'facility_name' => $facility?->name,
            'supervisor_name' => $normalized['supervisor_name'],
            'nature_of_offence' => $normalized['nature_of_offence'],
            'offence_category_id' => $offenceCategory?->id,
            'offence_category_name' => $offenceCategory?->name,
            'penalty_type_id' => $penaltyType?->id,
            'penalty_type_name' => $penaltyType?->name,
            'case_status_id' => $caseStatus?->id,
            'case_status_name' => $caseStatus?->name,
            'effective_date' => $effectiveDate,
            'expiry_date' => $expiryDate,
            'comment' => $normalized['comment'],
        ];

        $duplicateKey = null;

        if ($employee && filled($normalized['nature_of_offence']) && $effectiveDate) {
            $duplicateKey = $this->duplicateKey($employee->id, $normalized['nature_of_offence'], $effectiveDate, $penaltyType?->id);

            if ($this->existingDuplicate($employee->id, $normalized['nature_of_offence'], $effectiveDate, $penaltyType?->id)) {
                $errors[] = 'This disciplinary case already exists in the system.';
            }
        }

        return [
            'data' => $data,
            'errors' => $errors,
            'warnings' => $warnings,
            'duplicate_key' => $duplicateKey,
        ];
    }

    private function existingDuplicate(int $employeeId, string $natureOfOffence, string $effectiveDate, ?int $penaltyTypeId): bool
    {
        return DisciplinaryCase::withTrashed()
            ->where('employee_id', $employeeId)
            ->where('nature_of_offence', $natureOfOffence)
            ->whereDate('effective_date', $effectiveDate)
            ->when($penaltyTypeId, fn ($query) => $query->where('penalty_type_id', $penaltyTypeId), fn ($query) => $query->whereNull('penalty_type_id'))
            ->exists();
    }

    private function duplicateKey(int $employeeId, string $natureOfOffence, string $effectiveDate, ?int $penaltyTypeId): string
    {
        return implode('|', [
            $employeeId,
            $this->lookupKey($natureOfOffence),
            $effectiveDate,
            $penaltyTypeId ?: 'null',
        ]);
    }

    /**
     * @param array<string, mixed> $lookup
     * @param list<string> $errors
     */
    private function matchRequiredMaster(mixed $value, array $lookup, string $label, array &$errors): mixed
    {
        if (blank($value)) {
            return null;
        }

        return $this->matchOptionalMaster($value, $lookup, $label, $errors);
    }

    /**
     * @param array<string, mixed> $lookup
     * @param list<string> $errors
     */
    private function matchOptionalMaster(mixed $value, array $lookup, string $label, array &$errors): mixed
    {
        if (blank($value)) {
            return null;
        }

        $key = $this->lookupKey($value);

        if (isset($lookup[$key])) {
            return $lookup[$key];
        }

        $errors[] = "{$label} '{$value}' does not exist in master data.";

        return null;
    }

    /**
     * @param list<string> $errors
     */
    private function parseDate(mixed $value, string $label, array &$errors): ?string
    {
        if (blank($value)) {
            return null;
        }

        try {
            if (is_numeric($value)) {
                return Carbon::instance(ExcelDate::excelToDateTimeObject((float) $value))->toDateString();
            }

            return Carbon::parse((string) $value)->toDateString();
        } catch (Throwable) {
            $errors[] = "{$label} '{$value}' is not a valid date.";

            return null;
        }
    }

    private function normalizeKey(string $key): string
    {
        return trim((string) preg_replace('/[^a-z0-9]+/', '_', mb_strtolower($key)), '_');
    }

    private function lookupKey(mixed $value): string
    {
        return trim((string) preg_replace('/\s+/', ' ', mb_strtolower((string) $value)));
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

    /**
     * @param array<string, int> $errorSummary
     * @param list<string> $workbookWarnings
     * @return array<string, mixed>|null
     */
    private function buildErrorSummary(array $errorSummary, array $workbookWarnings): ?array
    {
        $summary = $errorSummary;

        if ($workbookWarnings !== []) {
            $summary['_workbook_warnings'] = $workbookWarnings;
        }

        return $summary === [] ? null : $summary;
    }
}
