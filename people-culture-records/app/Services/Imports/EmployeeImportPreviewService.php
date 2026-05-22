<?php

namespace App\Services\Imports;

use App\Models\Department;
use App\Models\District;
use App\Models\Employee;
use App\Models\EmploymentStatus;
use App\Models\Facility;
use App\Models\ImportBatch;
use App\Models\JobTitle;
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
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use RuntimeException;
use Throwable;

class EmployeeImportPreviewService
{
    /**
     * @var array<string, list<string>>
     */
    private array $aliases = [
        'employee_no' => ['employee_no', 'employee_number', 'employee_no_', 'employee_no', 'emp_no', 'staff_id', 'staff_no', 'staff_number'],
        'first_name' => ['first_name', 'firstname', 'given_name'],
        'last_name' => ['last_name', 'lastname', 'surname', 'family_name'],
        'gender' => ['gender', 'sex'],
        'date_of_birth' => ['date_of_birth', 'dob', 'birth_date'],
        'national_id' => ['national_id', 'nrc', 'national_registration_card'],
        'email' => ['email', 'email_address', 'work_email'],
        'phone' => ['phone', 'phone_number', 'mobile', 'mobile_number'],
        'project' => ['project', 'project_name', 'project_code'],
        'department' => ['department', 'department_name', 'department_code'],
        'job_title' => ['job_title', 'position', 'designation', 'job_title_name'],
        'province' => ['province', 'province_name', 'province_code'],
        'district' => ['district', 'district_name', 'district_code'],
        'facility' => ['facility', 'facility_name', 'site', 'site_name'],
        'employment_status' => ['employment_status', 'status', 'employee_status'],
        'hire_date' => ['hire_date', 'start_date', 'date_hired', 'employment_date'],
        'supervisor_name' => ['supervisor_name', 'supervisor', 'line_manager'],
        'notes' => ['notes', 'comment', 'comments'],
    ];

    public function __construct(
        private ReferenceNumberService $referenceNumberService,
        private ActivityLogger $activityLogger,
    ) {
    }

    public function preview(UploadedFile $file, User $user): ImportBatch
    {
        $path = $file->store('imports/employees', 'local');
        $batch = null;

        try {
            [$rawRows, $workbookWarnings] = $this->readRows($path);
            $normalizedRows = $rawRows->map(fn (array $row) => $this->normalizeRow($row));
            $employeeNumbers = $normalizedRows
                ->pluck('employee_no')
                ->filter()
                ->map(fn ($value) => mb_strtolower((string) $value))
                ->values();
            $fileDuplicateNumbers = $employeeNumbers->duplicates()->unique()->all();
            $existingEmployees = Employee::withTrashed()
                ->whereIn('employee_no', $normalizedRows->pluck('employee_no')->filter()->all())
                ->get()
                ->keyBy(fn (Employee $employee) => mb_strtolower($employee->employee_no));

            $lookups = $this->lookups();

            return DB::transaction(function () use ($file, $path, $user, $rawRows, $normalizedRows, $fileDuplicateNumbers, $existingEmployees, $lookups, $workbookWarnings, &$batch) {
                $batch = ImportBatch::create([
                    'reference_no' => $this->referenceNumberService->generate('IMP', 'import_batches'),
                    'import_type' => 'employees',
                    'original_filename' => $file->getClientOriginalName(),
                    'stored_filename' => basename($path),
                    'file_path' => $path,
                    'uploaded_by' => $user->id,
                    'status' => 'uploaded',
                    'total_rows' => $rawRows->count(),
                ]);

                $this->activityLogger->log(
                    'import_uploaded',
                    "{$user->name} uploaded Employee Import file {$batch->original_filename}.",
                    $batch,
                    $this->logProperties($batch, $user)
                );

                $counts = ['valid' => 0, 'invalid' => 0, 'duplicate' => 0];
                $errorSummary = [];

                foreach ($rawRows as $index => $rawRow) {
                    $normalized = $normalizedRows[$index];
                    [$status, $normalizedData, $errors, $warnings, $matchedEmployeeId] = $this->validateRow(
                        $normalized,
                        $lookups,
                        $fileDuplicateNumbers,
                        $existingEmployees,
                        $user
                    );

                    $counts[$status]++;

                    foreach ($errors as $error) {
                        $errorSummary[$error] = ($errorSummary[$error] ?? 0) + 1;
                    }

                    $batch->rows()->create([
                        'row_number' => $rawRow['_row_number'] ?? ($index + 2),
                        'raw_data' => collect($rawRow)->except('_row_number')->all(),
                        'normalized_data' => $normalizedData,
                        'status' => $status,
                        'errors' => $errors === [] ? null : $errors,
                        'warnings' => $warnings === [] ? null : $warnings,
                        'matched_employee_id' => $matchedEmployeeId,
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
                    "{$user->name} previewed Employee Import file {$batch->original_filename}.",
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
                    "Employee Import file {$batch->original_filename} failed during preview.",
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
            if (! in_array('Employees_Import', $sheetNames, true)) {
                throw new RuntimeException('Workbook has multiple sheets but no Employees_Import sheet. Please use the employee import template or rename the employee sheet to Employees_Import.');
            }

            $worksheet = $spreadsheet->getSheetByName('Employees_Import');
            $warnings[] = 'Workbook has multiple sheets. Only the Employees_Import sheet was processed.';
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
            'departments' => $this->masterLookup(Department::query()->get()),
            'job_titles' => $this->masterLookup(JobTitle::query()->get()),
            'provinces' => $this->masterLookup(Province::query()->get()),
            'districts' => $this->masterLookup(District::query()->with('province')->get()),
            'facilities' => $this->masterLookup(Facility::query()->with('district')->get()),
            'employment_statuses' => $this->masterLookup(EmploymentStatus::query()->get()),
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
     * @param array<int, string> $fileDuplicateNumbers
     * @param Collection<string, Employee> $existingEmployees
     * @return array{0: string, 1: array<string, mixed>, 2: list<string>, 3: list<string>, 4: int|null}
     */
    private function validateRow(array $normalized, array $lookups, array $fileDuplicateNumbers, Collection $existingEmployees, User $user): array
    {
        $errors = [];
        $warnings = [];
        $matchedEmployeeId = null;

        foreach (['employee_no' => 'Employee number', 'first_name' => 'First name', 'last_name' => 'Last name', 'province' => 'Province', 'district' => 'District'] as $field => $label) {
            if (blank($normalized[$field])) {
                $errors[] = "{$label} is required.";
            }
        }

        $employeeNoKey = $this->lookupKey($normalized['employee_no']);

        if ($employeeNoKey !== '' && in_array($employeeNoKey, $fileDuplicateNumbers, true)) {
            $errors[] = "Employee number '{$normalized['employee_no']}' is duplicated in this import file.";
        }

        if ($employeeNoKey !== '' && $existingEmployees->has($employeeNoKey)) {
            $matchedEmployeeId = $existingEmployees[$employeeNoKey]->id;
            $errors[] = "Employee number '{$normalized['employee_no']}' already exists in employees.";
        }

        if (filled($normalized['email']) && ! filter_var($normalized['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Email '{$normalized['email']}' is not a valid email address.";
        }

        $project = $this->matchOptionalMaster($normalized['project'], $lookups['projects'], 'Project', $errors);
        $department = $this->matchOptionalMaster($normalized['department'], $lookups['departments'], 'Department', $errors);
        $jobTitle = $this->matchOptionalMaster($normalized['job_title'], $lookups['job_titles'], 'Job title', $errors);
        $province = $this->matchRequiredMaster($normalized['province'], $lookups['provinces'], 'Province', $errors);
        $district = $this->matchRequiredMaster($normalized['district'], $lookups['districts'], 'District', $errors);
        $facility = $this->matchOptionalMaster($normalized['facility'], $lookups['facilities'], 'Facility', $errors);
        $employmentStatus = $this->matchOptionalMaster($normalized['employment_status'], $lookups['employment_statuses'], 'Employment status', $errors);

        if ($province && $district && (int) $district->province_id !== (int) $province->id) {
            $errors[] = "District '{$district->name}' does not belong to province '{$province->name}'.";
        }

        if ($district && $facility && (int) $facility->district_id !== (int) $district->id) {
            $errors[] = "Facility '{$facility->name}' does not belong to district '{$district->name}'.";
        }

        if ($user->hasRole('HR Officer') && $province && (int) $province->id !== (int) $user->province_id) {
            $errors[] = 'HR Officers can only import employees for their assigned province.';
        }

        $dateOfBirth = $this->parseDate($normalized['date_of_birth'], 'Date of birth', $errors);
        $hireDate = $this->parseDate($normalized['hire_date'], 'Hire date', $errors);

        $normalizedData = [
            'employee_no' => $normalized['employee_no'],
            'first_name' => $normalized['first_name'],
            'last_name' => $normalized['last_name'],
            'gender' => $normalized['gender'],
            'date_of_birth' => $dateOfBirth,
            'national_id' => $normalized['national_id'],
            'email' => $normalized['email'],
            'phone' => $normalized['phone'],
            'project_id' => $project?->id,
            'project_name' => $project?->name,
            'department_id' => $department?->id,
            'department_name' => $department?->name,
            'job_title_id' => $jobTitle?->id,
            'job_title_name' => $jobTitle?->name,
            'province_id' => $province?->id,
            'province_name' => $province?->name,
            'district_id' => $district?->id,
            'district_name' => $district?->name,
            'facility_id' => $facility?->id,
            'facility_name' => $facility?->name,
            'employment_status_id' => $employmentStatus?->id,
            'employment_status_name' => $employmentStatus?->name,
            'hire_date' => $hireDate,
            'supervisor_name' => $normalized['supervisor_name'],
            'notes' => $normalized['notes'],
        ];

        $status = collect($errors)->contains(fn (string $error) => str_contains($error, 'duplicated in this import file') || str_contains($error, 'already exists in employees'))
            ? 'duplicate'
            : ($errors === [] ? 'valid' : 'invalid');

        return [$status, $normalizedData, $errors, $warnings, $matchedEmployeeId];
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
        $key = trim((string) preg_replace('/\s+/', ' ', mb_strtolower((string) $value)));

        return trim((string) preg_replace('/\s+(province|district)$/', '', $key));
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
