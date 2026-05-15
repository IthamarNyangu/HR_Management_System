<?php

namespace Tests\Feature;

use App\Models\CaseStatus;
use App\Models\DisciplinaryCase;
use App\Models\District;
use App\Models\Employee;
use App\Models\ImportBatch;
use App\Models\OffenceCategory;
use App\Models\PenaltyType;
use App\Models\Province;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class DisciplinaryCaseImportTest extends TestCase
{
    use RefreshDatabase;

    private Role $adminRole;
    private Role $managerRole;
    private Role $officerRole;
    private Role $viewerRole;
    private Province $northern;
    private Province $luapula;
    private District $kasama;
    private District $mansa;
    private Employee $northernEmployee;
    private Employee $luapulaEmployee;
    private CaseStatus $draft;
    private CaseStatus $active;
    private OffenceCategory $attendance;
    private PenaltyType $warning;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminRole = Role::create(['name' => 'Admin', 'code' => 'ADMIN', 'is_active' => true]);
        $this->managerRole = Role::create(['name' => 'HR Manager', 'code' => 'HRM', 'is_active' => true]);
        $this->officerRole = Role::create(['name' => 'HR Officer', 'code' => 'HRO', 'is_active' => true]);
        $this->viewerRole = Role::create(['name' => 'Viewer', 'code' => 'VIEWER', 'is_active' => true]);

        $this->northern = Province::create(['name' => 'Northern', 'code' => 'NOR', 'is_active' => true]);
        $this->luapula = Province::create(['name' => 'Luapula', 'code' => 'LUA', 'is_active' => true]);
        $this->kasama = District::create(['province_id' => $this->northern->id, 'name' => 'Kasama', 'code' => 'NOR-KAS', 'is_active' => true]);
        $this->mansa = District::create(['province_id' => $this->luapula->id, 'name' => 'Mansa', 'code' => 'LUA-MAN', 'is_active' => true]);

        $this->draft = CaseStatus::create(['name' => 'Draft', 'code' => 'DRAFT', 'is_active' => true]);
        $this->active = CaseStatus::create(['name' => 'Active', 'code' => 'ACTIVE', 'is_active' => true]);
        $this->attendance = OffenceCategory::create(['name' => 'Attendance', 'code' => 'ATT', 'is_active' => true]);
        $this->warning = PenaltyType::create(['name' => 'Final Warning', 'code' => 'FW', 'is_active' => true]);

        $this->northernEmployee = $this->employee('EMP-NOR-001', 'Mary', 'Banda', $this->northern, $this->kasama);
        $this->luapulaEmployee = $this->employee('EMP-LUA-001', 'John', 'Phiri', $this->luapula, $this->mansa);
    }

    public function test_unauthenticated_users_cannot_access_disciplinary_imports(): void
    {
        $this->get(route('imports.disciplinary-cases.create'))->assertRedirect('/login');
    }

    public function test_viewer_cannot_access_disciplinary_imports(): void
    {
        $this->actingAs($this->user($this->viewerRole, $this->northern))
            ->get(route('imports.disciplinary-cases.create'))
            ->assertForbidden();
    }

    public function test_admin_can_access_disciplinary_import_page(): void
    {
        $this->actingAs($this->user($this->adminRole))
            ->get(route('imports.disciplinary-cases.create'))
            ->assertOk()
            ->assertSee('Disciplinary Cases Import')
            ->assertSee('Download Disciplinary Cases Import Template');
    }

    public function test_template_can_be_downloaded(): void
    {
        Excel::fake();

        $this->actingAs($this->user($this->adminRole))
            ->get(route('imports.disciplinary-cases.template'))
            ->assertOk();

        Excel::assertDownloaded('disciplinary-cases-import-template.xlsx');
    }

    public function test_upload_rejects_invalid_file_type(): void
    {
        $this->actingAs($this->user($this->adminRole))
            ->from(route('imports.disciplinary-cases.create'))
            ->post(route('imports.disciplinary-cases.upload'), [
                'file' => UploadedFile::fake()->create('cases.txt', 1, 'text/plain'),
            ])
            ->assertRedirect(route('imports.disciplinary-cases.create'))
            ->assertSessionHasErrors('file');
    }

    public function test_upload_creates_import_batch(): void
    {
        $this->uploadRows([$this->row()]);

        $this->assertDatabaseHas('import_batches', [
            'import_type' => 'disciplinary_cases',
            'status' => 'previewed',
            'total_rows' => 1,
            'valid_rows' => 1,
        ]);
    }

    public function test_valid_disciplinary_row_is_previewed_as_valid(): void
    {
        $batch = $this->uploadRows([$this->row(['nature_of_offence' => 'Late reporting'])]);

        $row = $batch->rows()->firstOrFail();

        $this->assertSame('valid', $row->status);
        $this->assertSame($this->northernEmployee->id, $row->normalized_data['employee_id']);
        $this->assertSame('Draft', $row->normalized_data['case_status_name']);
    }

    public function test_missing_employee_no_marks_row_invalid(): void
    {
        $batch = $this->uploadRows([$this->row(['employee_no' => ''])]);

        $row = $batch->rows()->firstOrFail();

        $this->assertSame('invalid', $row->status);
        $this->assertContains('Employee number is required.', $row->errors);
    }

    public function test_unknown_employee_no_marks_row_invalid(): void
    {
        $batch = $this->uploadRows([$this->row(['employee_no' => 'UNKNOWN-001'])]);

        $row = $batch->rows()->firstOrFail();

        $this->assertSame('invalid', $row->status);
        $this->assertContains("Employee number 'UNKNOWN-001' does not exist.", $row->errors);
    }

    public function test_name_mismatch_marks_warning_not_invalid(): void
    {
        $batch = $this->uploadRows([$this->row(['first_name' => 'Maria'])]);

        $row = $batch->rows()->firstOrFail();

        $this->assertSame('valid', $row->status);
        $this->assertContains("First name 'Maria' does not match employee record 'Mary'.", $row->warnings);
    }

    public function test_unknown_master_data_marks_row_invalid(): void
    {
        $batch = $this->uploadRows([$this->row(['penalty_type' => 'Unknown Penalty'])]);

        $row = $batch->rows()->firstOrFail();

        $this->assertSame('invalid', $row->status);
        $this->assertContains("Penalty type 'Unknown Penalty' does not exist in master data.", $row->errors);
    }

    public function test_district_outside_province_marks_row_invalid(): void
    {
        $batch = $this->uploadRows([$this->row(['province' => 'Northern', 'district' => 'Mansa'])]);

        $row = $batch->rows()->firstOrFail();

        $this->assertSame('invalid', $row->status);
        $this->assertContains("District 'Mansa' does not belong to province 'Northern'.", $row->errors);
    }

    public function test_expiry_date_before_effective_date_marks_row_invalid(): void
    {
        $batch = $this->uploadRows([$this->row(['effective_date' => '2026-05-20', 'expiry_date' => '2026-05-10'])]);

        $row = $batch->rows()->firstOrFail();

        $this->assertSame('invalid', $row->status);
        $this->assertContains('Expiry date must be after or equal to effective date.', $row->errors);
    }

    public function test_duplicate_case_in_database_marks_row_duplicate(): void
    {
        DisciplinaryCase::create([
            'reference_no' => 'DC-2026-0999',
            'employee_id' => $this->northernEmployee->id,
            'province_id' => $this->northern->id,
            'district_id' => $this->kasama->id,
            'nature_of_offence' => 'Late reporting',
            'penalty_type_id' => $this->warning->id,
            'case_status_id' => $this->draft->id,
            'effective_date' => '2026-05-14',
        ]);

        $batch = $this->uploadRows([$this->row(['nature_of_offence' => 'Late reporting', 'effective_date' => '2026-05-14'])]);

        $this->assertSame('duplicate', $batch->rows()->firstOrFail()->status);
    }

    public function test_duplicate_case_inside_file_marks_rows_duplicate(): void
    {
        $batch = $this->uploadRows([
            $this->row(['nature_of_offence' => 'Duplicate offence']),
            $this->row(['nature_of_offence' => 'Duplicate offence']),
        ]);

        $this->assertSame(2, $batch->fresh()->duplicate_rows);
        $this->assertSame(2, $batch->rows()->where('status', 'duplicate')->count());
    }

    public function test_hr_officer_cannot_import_row_outside_assigned_province(): void
    {
        $officer = $this->user($this->officerRole, $this->northern);

        $batch = $this->uploadRows([
            $this->row([
                'employee_no' => $this->luapulaEmployee->employee_no,
                'first_name' => 'John',
                'last_name' => 'Phiri',
                'province' => 'Luapula',
                'district' => 'Mansa',
            ]),
        ], $officer);

        $row = $batch->rows()->firstOrFail();

        $this->assertSame('invalid', $row->status);
        $this->assertContains('HR Officers can only import disciplinary cases for their assigned province.', $row->errors);
    }

    public function test_hr_officer_cannot_import_active_case(): void
    {
        $officer = $this->user($this->officerRole, $this->northern);

        $batch = $this->uploadRows([$this->row(['case_status' => 'Active'])], $officer);

        $row = $batch->rows()->firstOrFail();

        $this->assertSame('invalid', $row->status);
        $this->assertContains('HR Officers cannot import Active disciplinary cases.', $row->errors);
    }

    public function test_confirm_import_creates_disciplinary_cases_from_valid_rows(): void
    {
        $admin = $this->user($this->adminRole);
        $batch = $this->uploadRows([$this->row(['nature_of_offence' => 'Absenteeism'])], $admin);

        $this->assertDatabaseCount('disciplinary_cases', 0);

        $this->actingAs($admin)
            ->post(route('imports.disciplinary-cases.confirm', $batch))
            ->assertRedirect(route('imports.batches.show', $batch));

        $this->assertDatabaseHas('disciplinary_cases', [
            'employee_id' => $this->northernEmployee->id,
            'nature_of_offence' => 'Absenteeism',
            'created_by' => $admin->id,
        ]);
    }

    public function test_confirm_import_does_not_import_invalid_or_duplicate_rows(): void
    {
        $admin = $this->user($this->adminRole);
        $batch = $this->uploadRows([
            $this->row(['nature_of_offence' => 'Valid offence']),
            $this->row(['employee_no' => 'UNKNOWN-002', 'nature_of_offence' => 'Unknown employee']),
            $this->row(['nature_of_offence' => 'Repeated offence']),
            $this->row(['nature_of_offence' => 'Repeated offence']),
        ], $admin);

        $this->actingAs($admin)
            ->post(route('imports.disciplinary-cases.confirm', $batch))
            ->assertRedirect(route('imports.batches.show', $batch));

        $this->assertDatabaseHas('disciplinary_cases', ['nature_of_offence' => 'Valid offence']);
        $this->assertDatabaseMissing('disciplinary_cases', ['nature_of_offence' => 'Unknown employee']);
        $this->assertDatabaseMissing('disciplinary_cases', ['nature_of_offence' => 'Repeated offence']);
        $this->assertSame(1, $batch->fresh()->imported_rows);
    }

    public function test_reference_numbers_are_generated_during_confirm(): void
    {
        $admin = $this->user($this->adminRole);
        $batch = $this->uploadRows([$this->row(['nature_of_offence' => 'Reference test'])], $admin);

        $this->assertDatabaseMissing('disciplinary_cases', ['nature_of_offence' => 'Reference test']);

        $this->actingAs($admin)->post(route('imports.disciplinary-cases.confirm', $batch));

        $case = DisciplinaryCase::where('nature_of_offence', 'Reference test')->firstOrFail();

        $this->assertMatchesRegularExpression('/^DC-\d{4}-\d{4}$/', $case->reference_no);
    }

    public function test_activity_logs_are_created(): void
    {
        $admin = $this->user($this->adminRole);
        $batch = $this->uploadRows([$this->row(['nature_of_offence' => 'Activity test'])], $admin);

        $this->actingAs($admin)->post(route('imports.disciplinary-cases.confirm', $batch));

        $this->assertDatabaseHas('activity_logs', ['action' => 'import_uploaded', 'user_id' => $admin->id]);
        $this->assertDatabaseHas('activity_logs', ['action' => 'import_previewed', 'user_id' => $admin->id]);
        $this->assertDatabaseHas('activity_logs', ['action' => 'import_confirmed', 'user_id' => $admin->id]);
        $this->assertDatabaseHas('activity_logs', ['action' => 'import_completed', 'user_id' => $admin->id]);
    }

    private function uploadRows(array $rows, ?User $user = null): ImportBatch
    {
        $user ??= $this->user($this->adminRole);

        $this->actingAs($user)
            ->post(route('imports.disciplinary-cases.upload'), [
                'file' => $this->caseFile($rows),
            ])
            ->assertRedirect();

        return ImportBatch::latest('id')->firstOrFail();
    }

    /**
     * @param array<string, mixed> $overrides
     * @return array<string, mixed>
     */
    private function row(array $overrides = []): array
    {
        return array_merge([
            'employee_no' => $this->northernEmployee->employee_no,
            'first_name' => 'Mary',
            'last_name' => 'Banda',
            'project' => '',
            'province' => 'Northern',
            'district' => 'Kasama',
            'facility' => '',
            'supervisor_name' => 'Supervisor',
            'nature_of_offence' => 'Late reporting',
            'offence_category' => 'Attendance',
            'penalty_type' => 'Final Warning',
            'case_status' => '',
            'effective_date' => '2026-05-14',
            'expiry_date' => '2026-06-14',
            'comment' => 'Imported test row',
        ], $overrides);
    }

    /**
     * @param list<array<string, mixed>> $rows
     */
    private function caseFile(array $rows): UploadedFile
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Disciplinary_Cases_Import');
        $headings = [
            'employee_no',
            'first_name',
            'last_name',
            'project',
            'province',
            'district',
            'facility',
            'supervisor_name',
            'nature_of_offence',
            'offence_category',
            'penalty_type',
            'case_status',
            'effective_date',
            'expiry_date',
            'comment',
        ];

        foreach ($headings as $columnIndex => $heading) {
            $sheet->setCellValue([$columnIndex + 1, 1], $heading);
        }

        foreach ($rows as $rowIndex => $row) {
            foreach ($headings as $columnIndex => $heading) {
                $sheet->setCellValue([$columnIndex + 1, $rowIndex + 2], $row[$heading] ?? null);
            }
        }

        $path = sys_get_temp_dir().DIRECTORY_SEPARATOR.uniqid('disciplinary_cases_', true).'.xlsx';
        (new Xlsx($spreadsheet))->save($path);

        return new UploadedFile(
            $path,
            'disciplinary-cases.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true
        );
    }

    private function employee(string $employeeNo, string $firstName, string $lastName, Province $province, District $district): Employee
    {
        return Employee::create([
            'employee_no' => $employeeNo,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'province_id' => $province->id,
            'district_id' => $district->id,
        ]);
    }

    private function user(Role $role, ?Province $province = null): User
    {
        return User::factory()->create([
            'role_id' => $role->id,
            'province_id' => $province?->id,
            'is_active' => true,
        ]);
    }
}
