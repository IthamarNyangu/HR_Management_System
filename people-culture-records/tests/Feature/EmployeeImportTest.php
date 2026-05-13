<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\District;
use App\Models\Employee;
use App\Models\ImportBatch;
use App\Models\ImportRow;
use App\Models\Province;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class EmployeeImportTest extends TestCase
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
    }

    public function test_unauthenticated_users_cannot_access_imports(): void
    {
        $this->get(route('imports.index'))->assertRedirect('/login');
    }

    public function test_viewer_cannot_access_imports(): void
    {
        $this->actingAs($this->user($this->viewerRole, $this->northern))
            ->get(route('imports.index'))
            ->assertForbidden();
    }

    public function test_admin_can_access_employee_import_page(): void
    {
        $this->actingAs($this->user($this->adminRole))
            ->get(route('imports.employees.create'))
            ->assertOk()
            ->assertSee('Employee Import')
            ->assertSee('Upload and Preview');
    }

    public function test_upload_rejects_invalid_file_type(): void
    {
        $this->actingAs($this->user($this->adminRole))
            ->from(route('imports.employees.create'))
            ->post(route('imports.employees.upload'), [
                'file' => UploadedFile::fake()->create('employees.txt', 1, 'text/plain'),
            ])
            ->assertRedirect(route('imports.employees.create'))
            ->assertSessionHasErrors('file');
    }

    public function test_upload_creates_import_batch(): void
    {
        $this->actingAs($this->user($this->adminRole))
            ->post(route('imports.employees.upload'), [
                'file' => $this->employeeFile([$this->row(['employee_no' => 'EMP-001'])]),
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('import_batches', [
            'import_type' => 'employees',
            'status' => 'previewed',
            'total_rows' => 1,
            'valid_rows' => 1,
        ]);
    }

    public function test_valid_employee_rows_are_previewed_as_valid(): void
    {
        $batch = $this->uploadRows([$this->row(['employee_no' => 'EMP-002'])]);

        $this->assertDatabaseHas('import_rows', [
            'import_batch_id' => $batch->id,
            'status' => 'valid',
        ]);
    }

    public function test_missing_required_fields_mark_row_invalid(): void
    {
        $batch = $this->uploadRows([$this->row(['first_name' => '', 'employee_no' => 'EMP-003'])]);

        $row = $batch->rows()->firstOrFail();

        $this->assertSame('invalid', $row->status);
        $this->assertContains('First name is required.', $row->errors);
    }

    public function test_duplicate_employee_no_in_database_marks_row_duplicate(): void
    {
        Employee::create([
            'employee_no' => 'EMP-004',
            'first_name' => 'Existing',
            'last_name' => 'Employee',
            'province_id' => $this->northern->id,
            'district_id' => $this->kasama->id,
        ]);

        $batch = $this->uploadRows([$this->row(['employee_no' => 'EMP-004'])]);

        $this->assertSame('duplicate', $batch->rows()->firstOrFail()->status);
        $this->assertSame(1, $batch->fresh()->duplicate_rows);
    }

    public function test_duplicate_employee_no_inside_file_marks_rows_duplicate(): void
    {
        $batch = $this->uploadRows([
            $this->row(['employee_no' => 'EMP-005']),
            $this->row(['employee_no' => 'EMP-005', 'first_name' => 'Second']),
        ]);

        $this->assertSame(2, $batch->fresh()->duplicate_rows);
        $this->assertSame(2, $batch->rows()->where('status', 'duplicate')->count());
    }

    public function test_district_outside_province_marks_row_invalid(): void
    {
        $batch = $this->uploadRows([
            $this->row([
                'employee_no' => 'EMP-006',
                'province' => 'Northern',
                'district' => 'Mansa',
            ]),
        ]);

        $row = $batch->rows()->firstOrFail();

        $this->assertSame('invalid', $row->status);
        $this->assertContains("District 'Mansa' does not belong to province 'Northern'.", $row->errors);
    }

    public function test_confirm_import_creates_employees_from_valid_rows(): void
    {
        $admin = $this->user($this->adminRole);
        $batch = $this->uploadRows([$this->row(['employee_no' => 'EMP-007'])], $admin);

        $this->actingAs($admin)
            ->post(route('imports.employees.confirm', $batch))
            ->assertRedirect(route('imports.batches.show', $batch));

        $this->assertDatabaseHas('employees', [
            'employee_no' => 'EMP-007',
            'created_by' => $admin->id,
        ]);

        $this->assertSame('imported', $batch->fresh()->status);
        $this->assertSame(1, $batch->fresh()->imported_rows);
    }

    public function test_confirm_import_does_not_import_invalid_or_duplicate_rows(): void
    {
        Employee::create([
            'employee_no' => 'EMP-008',
            'first_name' => 'Existing',
            'last_name' => 'Employee',
            'province_id' => $this->northern->id,
            'district_id' => $this->kasama->id,
        ]);

        $admin = $this->user($this->adminRole);
        $batch = $this->uploadRows([
            $this->row(['employee_no' => 'EMP-009']),
            $this->row(['employee_no' => 'EMP-010', 'first_name' => '']),
            $this->row(['employee_no' => 'EMP-008']),
        ], $admin);

        $this->actingAs($admin)
            ->post(route('imports.employees.confirm', $batch))
            ->assertRedirect(route('imports.batches.show', $batch));

        $this->assertDatabaseHas('employees', ['employee_no' => 'EMP-009']);
        $this->assertDatabaseMissing('employees', ['employee_no' => 'EMP-010']);
        $this->assertSame(1, $batch->fresh()->imported_rows);
    }

    public function test_hr_officer_cannot_import_rows_outside_assigned_province(): void
    {
        $officer = $this->user($this->officerRole, $this->northern);

        $batch = $this->uploadRows([
            $this->row([
                'employee_no' => 'EMP-011',
                'province' => 'Luapula',
                'district' => 'Mansa',
            ]),
        ], $officer);

        $row = $batch->rows()->firstOrFail();

        $this->assertSame('invalid', $row->status);
        $this->assertContains('HR Officers can only import employees for their assigned province.', $row->errors);
    }

    public function test_activity_logs_are_created(): void
    {
        $admin = $this->user($this->adminRole);
        $batch = $this->uploadRows([$this->row(['employee_no' => 'EMP-012'])], $admin);

        $this->actingAs($admin)->post(route('imports.employees.confirm', $batch));

        $this->assertDatabaseHas('activity_logs', ['action' => 'import_uploaded', 'user_id' => $admin->id]);
        $this->assertDatabaseHas('activity_logs', ['action' => 'import_previewed', 'user_id' => $admin->id]);
        $this->assertDatabaseHas('activity_logs', ['action' => 'import_confirmed', 'user_id' => $admin->id]);
        $this->assertDatabaseHas('activity_logs', ['action' => 'import_completed', 'user_id' => $admin->id]);
    }

    private function uploadRows(array $rows, ?User $user = null): ImportBatch
    {
        $user ??= $this->user($this->adminRole);

        $this->actingAs($user)
            ->post(route('imports.employees.upload'), [
                'file' => $this->employeeFile($rows),
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
            'employee_no' => 'EMP-DEFAULT',
            'first_name' => 'Mary',
            'last_name' => 'Banda',
            'email' => 'mary.banda@example.test',
            'province' => 'Northern',
            'district' => 'Kasama',
        ], $overrides);
    }

    /**
     * @param list<array<string, mixed>> $rows
     */
    private function employeeFile(array $rows): UploadedFile
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $headings = ['employee_no', 'first_name', 'last_name', 'email', 'province', 'district'];

        foreach ($headings as $columnIndex => $heading) {
            $sheet->setCellValue([$columnIndex + 1, 1], $heading);
        }

        foreach ($rows as $rowIndex => $row) {
            foreach ($headings as $columnIndex => $heading) {
                $sheet->setCellValue([$columnIndex + 1, $rowIndex + 2], $row[$heading] ?? null);
            }
        }

        $path = sys_get_temp_dir().DIRECTORY_SEPARATOR.uniqid('employees_', true).'.xlsx';
        (new Xlsx($spreadsheet))->save($path);

        return new UploadedFile(
            $path,
            'employees.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true
        );
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
