<?php

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Department;
use App\Models\Employee;
use App\Models\EmploymentStatus;
use App\Models\JobTitle;
use App\Models\Project;
use App\Models\PromotionType;
use App\Models\RelocationReason;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$dir = storage_path('app/sample-imports');

if (! is_dir($dir)) {
    mkdir($dir, 0777, true);
}

$stamp = now()->format('YmdHis');
$employees = Employee::with(['province', 'district', 'facility', 'project', 'department', 'jobTitle'])->get();

if ($employees->isEmpty()) {
    echo "No employees found. Please create employees before generating test files.\n";
    exit(1);
}

$employeeFor = function (string $province) use ($employees): Employee {
    return $employees->first(fn (Employee $employee) => $employee->province?->name === $province) ?? $employees->first();
};

$northern = $employeeFor('Northern');
$luapula = $employeeFor('Luapula');
$muchinga = $employeeFor('Muchinga');
$lusaka = $employeeFor('Lusaka');

$project = Project::query()->value('name') ?: '';
$projectTwo = Project::query()->skip(1)->value('name') ?: $project;
$department = Department::query()->value('name') ?: '';
$departmentTwo = Department::query()->skip(1)->value('name') ?: $department;
$jobTitle = JobTitle::query()->value('name') ?: '';
$jobTitleTwo = JobTitle::query()->skip(1)->value('name') ?: $jobTitle;
$jobTitleThree = JobTitle::query()->skip(2)->value('name') ?: $jobTitleTwo;
$promotionType = PromotionType::query()->value('name') ?: '';
$relocationReason = RelocationReason::query()->value('name') ?: '';
$employmentStatus = EmploymentStatus::query()->value('name') ?: 'Active';

function writeWorkbook(string $path, string $sheetName, array $headings, array $rows, string $title): void
{
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle($sheetName);

    foreach ($headings as $columnIndex => $heading) {
        $sheet->setCellValue([$columnIndex + 1, 1], $heading);
    }

    foreach ($rows as $rowIndex => $row) {
        foreach ($headings as $columnIndex => $heading) {
            $sheet->setCellValue([$columnIndex + 1, $rowIndex + 2], $row[$heading] ?? null);
        }
    }

    $lastColumn = count($headings);
    $lastRow = max(count($rows) + 1, 2);
    $lastColumnLetter = $sheet->getCellByColumnAndRow($lastColumn, 1)->getColumn();
    $headerRange = 'A1:'.$lastColumnLetter.'1';
    $tableRange = 'A1:'.$lastColumnLetter.$lastRow;

    $sheet->getStyle($headerRange)->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
    $sheet->getStyle($headerRange)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('2563EB');
    $sheet->getStyle($tableRange)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('D1D5DB');
    $sheet->freezePane('A2');
    $sheet->setAutoFilter($tableRange);

    foreach (range(1, $lastColumn) as $column) {
        $sheet->getColumnDimensionByColumn($column)->setAutoSize(true);
    }

    $spreadsheet->getProperties()
        ->setCreator('People & Culture Records Management System')
        ->setTitle($title)
        ->setSubject('Import Test File');

    (new Xlsx($spreadsheet))->save($path);
}

function employeeImportRow(array $overrides, string $stamp, string $project, string $department, string $jobTitle, string $employmentStatus): array
{
    return array_merge([
        'employee_no' => "TEST-EMP-{$stamp}",
        'first_name' => 'Test',
        'last_name' => 'Employee',
        'gender' => 'Female',
        'date_of_birth' => '1993-04-10',
        'national_id' => '111111/10/1',
        'email' => "test.employee.{$stamp}@example.test",
        'phone' => '260971100001',
        'project' => $project,
        'department' => $department,
        'job_title' => $jobTitle,
        'province' => 'Northern',
        'district' => 'Kasama',
        'facility' => '',
        'employment_status' => $employmentStatus,
        'hire_date' => '2024-01-15',
        'supervisor_name' => 'Test Supervisor',
        'notes' => 'Generated employee import test row.',
    ], $overrides);
}

function disciplinaryImportRow(Employee $employee, array $overrides, string $project): array
{
    return array_merge([
        'employee_no' => $employee->employee_no,
        'first_name' => $employee->first_name,
        'last_name' => $employee->last_name,
        'project' => $project,
        'province' => $employee->province?->name,
        'district' => $employee->district?->name,
        'facility' => '',
        'supervisor_name' => 'Test Supervisor',
        'nature_of_offence' => 'Generated disciplinary import offence',
        'offence_category' => 'Attendance',
        'penalty_type' => 'Written Warning',
        'case_status' => 'Draft',
        'effective_date' => now()->toDateString(),
        'expiry_date' => now()->addMonth()->toDateString(),
        'comment' => 'Generated disciplinary case import test row.',
    ], $overrides);
}

function promotionImportRow(Employee $employee, array $overrides, string $oldJobTitle, string $newJobTitle, string $promotionType): array
{
    return array_merge([
        'employee_no' => $employee->employee_no,
        'first_name' => $employee->first_name,
        'last_name' => $employee->last_name,
        'province' => $employee->province?->name,
        'district' => $employee->district?->name,
        'facility' => '',
        'project' => $employee->project?->name ?? '',
        'department' => $employee->department?->name ?? '',
        'old_job_title' => $employee->jobTitle?->name ?: $oldJobTitle,
        'new_job_title' => $newJobTitle,
        'promotion_type' => $promotionType,
        'promotion_date' => now()->toDateString(),
        'effective_date' => now()->toDateString(),
        'comment' => 'Generated staff promotion import test row.',
    ], $overrides);
}

function relocationImportRow(Employee $employee, array $overrides, string $jobTitle, string $project, string $department, string $relocationReason): array
{
    return array_merge([
        'employee_no' => $employee->employee_no,
        'first_name' => $employee->first_name,
        'last_name' => $employee->last_name,
        'job_title' => $employee->jobTitle?->name ?: $jobTitle,
        'project' => $employee->project?->name ?: $project,
        'department' => $employee->department?->name ?: $department,
        'from_province' => $employee->province?->name,
        'from_district' => $employee->district?->name,
        'from_facility' => '',
        'to_province' => 'Luapula',
        'to_district' => 'Mansa',
        'to_facility' => '',
        'relocation_reason' => $relocationReason,
        'effective_date' => now()->toDateString(),
        'relocation_amount' => '1500.00',
        'comment' => 'Generated staff relocation import test row.',
    ], $overrides);
}

$employeeHeadings = ['employee_no', 'first_name', 'last_name', 'gender', 'date_of_birth', 'national_id', 'email', 'phone', 'project', 'department', 'job_title', 'province', 'district', 'facility', 'employment_status', 'hire_date', 'supervisor_name', 'notes'];
$caseHeadings = ['employee_no', 'first_name', 'last_name', 'project', 'province', 'district', 'facility', 'supervisor_name', 'nature_of_offence', 'offence_category', 'penalty_type', 'case_status', 'effective_date', 'expiry_date', 'comment'];
$promotionHeadings = ['employee_no', 'first_name', 'last_name', 'province', 'district', 'facility', 'project', 'department', 'old_job_title', 'new_job_title', 'promotion_type', 'promotion_date', 'effective_date', 'comment'];
$relocationHeadings = ['employee_no', 'first_name', 'last_name', 'job_title', 'project', 'department', 'from_province', 'from_district', 'from_facility', 'to_province', 'to_district', 'to_facility', 'relocation_reason', 'effective_date', 'relocation_amount', 'comment'];

$employeeValid = [
    employeeImportRow(['employee_no' => "EMP-VALID-{$stamp}-001", 'first_name' => 'Anna', 'last_name' => 'Mwila', 'email' => "anna.mwila.{$stamp}@example.test"], $stamp, $project, $department, $jobTitle, $employmentStatus),
    employeeImportRow(['employee_no' => "EMP-VALID-{$stamp}-002", 'first_name' => 'Bright', 'last_name' => 'Chileshe', 'email' => "bright.chileshe.{$stamp}@example.test", 'province' => 'Luapula', 'district' => 'Mansa', 'project' => $projectTwo, 'department' => $departmentTwo, 'job_title' => $jobTitleTwo], $stamp, $project, $department, $jobTitle, $employmentStatus),
];

$employeeErrors = [
    employeeImportRow(['employee_no' => "EMP-ERR-{$stamp}-001", 'first_name' => 'Valid', 'last_name' => 'Employee', 'email' => "valid.employee.{$stamp}@example.test"], $stamp, $project, $department, $jobTitle, $employmentStatus),
    employeeImportRow(['employee_no' => "EMP-ERR-{$stamp}-002", 'first_name' => '', 'last_name' => 'MissingFirstName'], $stamp, $project, $department, $jobTitle, $employmentStatus),
    employeeImportRow(['employee_no' => "EMP-ERR-{$stamp}-003", 'province' => 'Northern', 'district' => 'Mansa'], $stamp, $project, $department, $jobTitle, $employmentStatus),
    employeeImportRow(['employee_no' => "EMP-ERR-{$stamp}-004", 'job_title' => 'Unknown Job Title'], $stamp, $project, $department, $jobTitle, $employmentStatus),
    employeeImportRow(['employee_no' => "EMP-ERR-{$stamp}-005"], $stamp, $project, $department, $jobTitle, $employmentStatus),
    employeeImportRow(['employee_no' => "EMP-ERR-{$stamp}-005", 'first_name' => 'Duplicate'], $stamp, $project, $department, $jobTitle, $employmentStatus),
];

$duplicateCaseOffence = "Duplicate disciplinary import offence {$stamp}";
$caseValid = [
    disciplinaryImportRow($northern, ['nature_of_offence' => "Valid disciplinary import {$stamp}-001", 'case_status' => 'Draft'], $project),
    disciplinaryImportRow($luapula, ['nature_of_offence' => "Valid disciplinary import {$stamp}-002", 'case_status' => 'Submitted', 'offence_category' => 'Conduct', 'penalty_type' => 'Verbal Warning'], $project),
];

$caseErrors = [
    disciplinaryImportRow($northern, ['nature_of_offence' => "Valid row in error file {$stamp}-001"], $project),
    disciplinaryImportRow($northern, ['employee_no' => '', 'nature_of_offence' => "Missing employee no {$stamp}"], $project),
    disciplinaryImportRow($northern, ['employee_no' => "UNKNOWN-{$stamp}", 'first_name' => 'Unknown', 'last_name' => 'Employee', 'nature_of_offence' => "Unknown employee {$stamp}"], $project),
    disciplinaryImportRow($northern, ['district' => 'Mansa', 'nature_of_offence' => "Wrong district {$stamp}"], $project),
    disciplinaryImportRow($luapula, ['penalty_type' => 'Unknown Penalty', 'nature_of_offence' => "Unknown penalty {$stamp}"], $project),
    disciplinaryImportRow($muchinga, ['nature_of_offence' => $duplicateCaseOffence, 'effective_date' => now()->addDays(3)->toDateString(), 'expiry_date' => now()->addDays(20)->toDateString()], $project),
    disciplinaryImportRow($muchinga, ['nature_of_offence' => $duplicateCaseOffence, 'effective_date' => now()->addDays(3)->toDateString(), 'expiry_date' => now()->addDays(20)->toDateString()], $project),
    disciplinaryImportRow($lusaka, ['nature_of_offence' => "Bad expiry {$stamp}", 'effective_date' => now()->addMonth()->toDateString(), 'expiry_date' => now()->toDateString()], $project),
    disciplinaryImportRow($northern, ['first_name' => 'WrongName', 'nature_of_offence' => "Name warning {$stamp}"], $project),
];

$promotionValid = [
    promotionImportRow($northern, ['new_job_title' => $jobTitleTwo, 'comment' => 'Valid promotion import row.'], $jobTitle, $jobTitleTwo, $promotionType),
    promotionImportRow($luapula, ['new_job_title' => $jobTitleThree, 'promotion_date' => now()->addDay()->toDateString(), 'effective_date' => now()->addDay()->toDateString(), 'comment' => 'Future-dated valid promotion row.'], $jobTitle, $jobTitleThree, $promotionType),
];

$promotionErrors = [
    promotionImportRow($northern, ['new_job_title' => $jobTitleTwo, 'comment' => 'Valid row in error file.'], $jobTitle, $jobTitleTwo, $promotionType),
    promotionImportRow($northern, ['employee_no' => '', 'comment' => 'Missing employee number.'], $jobTitle, $jobTitleTwo, $promotionType),
    promotionImportRow($northern, ['employee_no' => "UNKNOWN-{$stamp}", 'first_name' => 'Unknown', 'last_name' => 'Employee', 'comment' => 'Unknown employee number.'], $jobTitle, $jobTitleTwo, $promotionType),
    promotionImportRow($northern, ['new_job_title' => 'Unknown Job Title', 'comment' => 'Unknown new job title.'], $jobTitle, $jobTitleTwo, $promotionType),
    promotionImportRow($luapula, ['promotion_date' => 'bad-date', 'comment' => 'Invalid promotion date.'], $jobTitle, $jobTitleTwo, $promotionType),
    promotionImportRow($muchinga, ['new_job_title' => '', 'comment' => 'Missing required new job title.'], $jobTitle, $jobTitleTwo, $promotionType),
];

$relocationValid = [
    relocationImportRow($northern, ['to_province' => 'Luapula', 'to_district' => 'Mansa', 'comment' => 'Valid relocation from Northern to Luapula.'], $jobTitle, $project, $department, $relocationReason),
    relocationImportRow($luapula, ['to_province' => 'Muchinga', 'to_district' => 'Chinsali', 'relocation_amount' => '2200.00', 'comment' => 'Valid relocation from Luapula to Muchinga.'], $jobTitle, $project, $department, $relocationReason),
];

$relocationErrors = [
    relocationImportRow($northern, ['to_province' => 'Luapula', 'to_district' => 'Mansa', 'comment' => 'Valid row in error file.'], $jobTitle, $project, $department, $relocationReason),
    relocationImportRow($northern, ['employee_no' => '', 'comment' => 'Missing employee number.'], $jobTitle, $project, $department, $relocationReason),
    relocationImportRow($northern, ['employee_no' => "UNKNOWN-{$stamp}", 'first_name' => 'Unknown', 'last_name' => 'Employee', 'comment' => 'Unknown employee number.'], $jobTitle, $project, $department, $relocationReason),
    relocationImportRow($northern, ['from_province' => 'Northern', 'from_district' => 'Mansa', 'comment' => 'From district does not belong to from province.'], $jobTitle, $project, $department, $relocationReason),
    relocationImportRow($luapula, ['to_province' => 'Northern', 'to_district' => 'Mansa', 'comment' => 'To district does not belong to to province.'], $jobTitle, $project, $department, $relocationReason),
    relocationImportRow($muchinga, ['relocation_amount' => '-500', 'comment' => 'Negative relocation amount.'], $jobTitle, $project, $department, $relocationReason),
    relocationImportRow($lusaka, ['effective_date' => 'bad-date', 'comment' => 'Invalid effective date.'], $jobTitle, $project, $department, $relocationReason),
];

$workbooks = [
    ['employee-import-valid-test.xlsx', 'Employees_Import', $employeeHeadings, $employeeValid, 'Employee Import Valid Test'],
    ['employee-import-errors-test.xlsx', 'Employees_Import', $employeeHeadings, $employeeErrors, 'Employee Import Error Test'],
    ['disciplinary-cases-import-valid-test.xlsx', 'Disciplinary_Cases_Import', $caseHeadings, $caseValid, 'Disciplinary Cases Import Valid Test'],
    ['disciplinary-cases-import-errors-test.xlsx', 'Disciplinary_Cases_Import', $caseHeadings, $caseErrors, 'Disciplinary Cases Import Error Test'],
    ['staff-promotions-import-valid-test.xlsx', 'Staff_Promotions_Import', $promotionHeadings, $promotionValid, 'Staff Promotions Import Valid Test'],
    ['staff-promotions-import-errors-test.xlsx', 'Staff_Promotions_Import', $promotionHeadings, $promotionErrors, 'Staff Promotions Import Error Test'],
    ['staff-relocations-import-valid-test.xlsx', 'Staff_Relocations_Import', $relocationHeadings, $relocationValid, 'Staff Relocations Import Valid Test'],
    ['staff-relocations-import-errors-test.xlsx', 'Staff_Relocations_Import', $relocationHeadings, $relocationErrors, 'Staff Relocations Import Error Test'],
];

$files = [];

foreach ($workbooks as [$filename, $sheet, $headings, $rows, $title]) {
    $path = $dir.DIRECTORY_SEPARATOR.$filename;
    writeWorkbook($path, $sheet, $headings, $rows, $title);
    $files[] = $path;
}

echo "Created:\n".implode("\n", $files)."\n";
