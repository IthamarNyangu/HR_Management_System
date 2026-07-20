<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\CaseStatus;
use App\Models\DisciplinaryCase;
use App\Models\District;
use App\Models\Employee;
use App\Models\Province;
use App\Models\Role;
use App\Models\User;
use App\Notifications\DisciplinaryCaseSubmittedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardActivityTest extends TestCase
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

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminRole = Role::create(['name' => 'Admin', 'code' => 'ADMIN', 'is_active' => true]);
        $this->managerRole = Role::create(['name' => 'HR Manager', 'code' => 'HRM', 'is_active' => true]);
        $this->officerRole = Role::create(['name' => 'HR Officer', 'code' => 'HRO', 'is_active' => true]);
        $this->viewerRole = Role::create(['name' => 'Viewer', 'code' => 'VIEWER', 'is_active' => true]);

        foreach ([
            ['Draft', 'DRAFT'],
            ['Submitted', 'SUBMITTED'],
            ['Active', 'ACTIVE'],
            ['Closed', 'CLOSED'],
        ] as [$name, $code]) {
            CaseStatus::create(['name' => $name, 'code' => $code, 'is_active' => true]);
        }

        $this->northern = Province::create(['name' => 'Northern', 'code' => 'NOR', 'is_active' => true]);
        $this->luapula = Province::create(['name' => 'Luapula', 'code' => 'LUA', 'is_active' => true]);
        $this->kasama = District::create(['province_id' => $this->northern->id, 'name' => 'Kasama', 'code' => 'NOR-KAS', 'is_active' => true]);
        $this->mansa = District::create(['province_id' => $this->luapula->id, 'name' => 'Mansa', 'code' => 'LUA-MAN', 'is_active' => true]);

        $this->northernEmployee = $this->employee(['employee_no' => 'NOR-001', 'province_id' => $this->northern->id, 'district_id' => $this->kasama->id]);
        $this->luapulaEmployee = $this->employee(['employee_no' => 'LUA-001', 'province_id' => $this->luapula->id, 'district_id' => $this->mansa->id]);
    }

    public function test_dashboard_loads_for_authenticated_users(): void
    {
        $this->actingAs($this->user($this->adminRole))
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Quick Actions')
            ->assertSee('People Overview')
            ->assertSee('Disciplinary Case Overview')
            ->assertSee('Recent Activity');
    }

    public function test_dashboard_counts_respect_province_restrictions(): void
    {
        $officer = $this->user($this->officerRole, $this->northern);

        $this->disciplinaryCase(['reference_no' => 'DC-2026-1001', 'province_id' => $this->northern->id, 'district_id' => $this->kasama->id, 'employee_id' => $this->northernEmployee->id]);
        $this->disciplinaryCase(['reference_no' => 'DC-2026-2001', 'province_id' => $this->luapula->id, 'district_id' => $this->mansa->id, 'employee_id' => $this->luapulaEmployee->id]);

        $this->actingAs($officer)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Northern')
            ->assertDontSee('Luapula');
    }

    public function test_activity_log_is_created_when_employee_is_created(): void
    {
        $admin = $this->user($this->adminRole);

        $this->actingAs($admin)
            ->post(route('employees.store'), [
                'employee_no' => 'RTC-100',
                'first_name' => 'Mercy',
                'last_name' => 'Zulu',
                'province_id' => $this->northern->id,
                'district_id' => $this->kasama->id,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'employee_created',
            'user_id' => $admin->id,
        ]);
    }

    public function test_activity_log_is_created_when_case_is_submitted_approved_and_closed(): void
    {
        $admin = $this->user($this->adminRole);
        $case = $this->disciplinaryCase();

        $this->actingAs($admin)->patch(route('disciplinary-cases.submit', $case))->assertRedirect();
        $this->actingAs($admin)->patch(route('disciplinary-cases.approve', $case->fresh()))->assertRedirect();
        $this->actingAs($admin)->patch(route('disciplinary-cases.close', $case->fresh()))->assertRedirect();

        $this->assertDatabaseHas('activity_logs', ['action' => 'case_submitted', 'subject_id' => $case->id]);
        $this->assertDatabaseHas('activity_logs', ['action' => 'case_approved', 'subject_id' => $case->id]);
        $this->assertDatabaseHas('activity_logs', ['action' => 'case_closed', 'subject_id' => $case->id]);
    }

    public function test_recent_activity_shows_logged_events(): void
    {
        $admin = $this->user($this->adminRole);
        $case = $this->disciplinaryCase();

        ActivityLog::create([
            'user_id' => $admin->id,
            'action' => 'case_approved',
            'description' => 'Admin approved disciplinary case DC-2026-1001.',
            'subject_type' => DisciplinaryCase::class,
            'subject_id' => $case->id,
            'properties' => ['reference_no' => $case->reference_no, 'province_id' => $case->province_id],
        ]);

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Admin approved disciplinary case DC-2026-1001.')
            ->assertSee($this->northern->name);
    }

    public function test_dashboard_recent_activity_shows_only_latest_three_items(): void
    {
        $admin = $this->user($this->adminRole);

        foreach (range(1, 4) as $index) {
            ActivityLog::create([
                'user_id' => $admin->id,
                'action' => 'employee_created',
                'description' => "Activity {$index}",
                'properties' => [
                    'province_id' => $this->northern->id,
                    'province_name' => $this->northern->name,
                ],
                'created_at' => now()->addSeconds($index),
                'updated_at' => now()->addSeconds($index),
            ]);
        }

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Activity 4')
            ->assertSee('Activity 3')
            ->assertSee('Activity 2')
            ->assertDontSee('Activity 1');
    }

    public function test_recent_activity_page_is_paginated_and_shows_older_records(): void
    {
        $admin = $this->user($this->adminRole);

        foreach (range(1, 11) as $index) {
            ActivityLog::create([
                'user_id' => $admin->id,
                'action' => 'employee_created',
                'description' => sprintf('Paged Activity #%02d', $index),
                'properties' => [
                    'province_id' => $this->northern->id,
                    'province_name' => $this->northern->name,
                ],
                'created_at' => now()->addSeconds($index),
                'updated_at' => now()->addSeconds($index),
            ]);
        }

        $this->actingAs($admin)
            ->get(route('activity-logs.index'))
            ->assertOk()
            ->assertSee('Paged Activity #11')
            ->assertDontSee('Paged Activity #01');

        $this->actingAs($admin)
            ->get(route('activity-logs.index', ['page' => 2]))
            ->assertOk()
            ->assertSee('Paged Activity #01');
    }

    public function test_audit_logs_can_be_filtered_by_module_and_action(): void
    {
        $admin = $this->user($this->adminRole);

        ActivityLog::create([
            'user_id' => $admin->id,
            'action' => 'employee_created',
            'description' => 'Created employee record for audit.',
            'subject_type' => Employee::class,
            'subject_id' => $this->northernEmployee->id,
            'properties' => [
                'employee_no' => $this->northernEmployee->employee_no,
                'province_id' => $this->northern->id,
                'province_name' => $this->northern->name,
            ],
        ]);
        ActivityLog::create([
            'user_id' => $admin->id,
            'action' => 'case_approved',
            'description' => 'Approved disciplinary case for audit.',
            'subject_type' => DisciplinaryCase::class,
            'properties' => [
                'reference_no' => 'DC-2026-9001',
                'province_id' => $this->northern->id,
                'province_name' => $this->northern->name,
            ],
        ]);

        $this->actingAs($admin)
            ->get(route('activity-logs.index', [
                'module' => 'employees',
                'action' => 'employee_created',
            ]))
            ->assertOk()
            ->assertSee('Audit Trail')
            ->assertSee('Created employee record for audit.')
            ->assertDontSee('Approved disciplinary case for audit.');
    }

    public function test_hr_officer_can_see_dashboard_recent_activity_but_cannot_access_audit_logs(): void
    {
        $officer = $this->user($this->officerRole, $this->northern);

        ActivityLog::create([
            'user_id' => $officer->id,
            'action' => 'employee_updated',
            'description' => 'Updated Northern employee record.',
            'subject_type' => Employee::class,
            'subject_id' => $this->northernEmployee->id,
            'properties' => [
                'employee_no' => $this->northernEmployee->employee_no,
                'province_id' => $this->northern->id,
                'province_name' => $this->northern->name,
            ],
        ]);

        ActivityLog::create([
            'action' => 'employee_updated',
            'description' => 'Updated Luapula employee record.',
            'subject_type' => Employee::class,
            'subject_id' => $this->luapulaEmployee->id,
            'properties' => [
                'employee_no' => $this->luapulaEmployee->employee_no,
                'province_id' => $this->luapula->id,
                'province_name' => $this->luapula->name,
            ],
        ]);

        $this->actingAs($officer)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Updated Northern employee record.')
            ->assertDontSee('Updated Luapula employee record.')
            ->assertDontSee('View all activity');

        $this->actingAs($officer)
            ->get(route('activity-logs.index'))
            ->assertForbidden();
    }

    public function test_audit_logs_pdf_export_uses_filters_and_logs_export(): void
    {
        $admin = $this->user($this->adminRole);

        ActivityLog::create([
            'user_id' => $admin->id,
            'action' => 'employee_created',
            'description' => 'PDF export visible activity.',
            'subject_type' => Employee::class,
            'subject_id' => $this->northernEmployee->id,
            'properties' => [
                'employee_no' => $this->northernEmployee->employee_no,
                'province_id' => $this->northern->id,
                'province_name' => $this->northern->name,
            ],
        ]);

        $this->actingAs($admin)
            ->get(route('activity-logs.export.pdf', ['module' => 'employees']))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'audit_logs_exported_pdf',
            'user_id' => $admin->id,
        ]);
    }

    public function test_viewer_can_see_dashboard_recent_activity_for_province_but_cannot_access_audit_logs(): void
    {
        $viewer = $this->user($this->viewerRole, $this->northern);
        $outsideCase = $this->disciplinaryCase(['reference_no' => 'DC-2026-2001', 'province_id' => $this->luapula->id, 'district_id' => $this->mansa->id, 'employee_id' => $this->luapulaEmployee->id]);

        ActivityLog::create([
            'action' => 'case_created',
            'description' => 'Created outside province case.',
            'subject_type' => DisciplinaryCase::class,
            'subject_id' => $outsideCase->id,
            'properties' => ['reference_no' => $outsideCase->reference_no, 'province_id' => $outsideCase->province_id],
        ]);

        $this->actingAs($viewer)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('Created outside province case.')
            ->assertDontSee('View all activity');

        $this->actingAs($viewer)
            ->get(route('activity-logs.index'))
            ->assertForbidden();
    }

    public function test_notification_count_appears_for_users_with_unread_notifications(): void
    {
        $admin = $this->user($this->adminRole);
        $case = $this->disciplinaryCase();

        $admin->notify(new DisciplinaryCaseSubmittedNotification($case));

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('1')
            ->assertSee('Disciplinary case submitted');

        $this->actingAs($admin)
            ->post(route('notifications.mark-all-read'))
            ->assertRedirect();

        $this->assertSame(0, $admin->fresh()->unreadNotifications()->count());
    }

    private function user(Role $role, ?Province $province = null): User
    {
        return User::factory()->create([
            'role_id' => $role->id,
            'province_id' => $province?->id,
            'is_active' => true,
        ]);
    }

    /**
     * @param array<string, mixed> $attributes
     */
    private function employee(array $attributes = []): Employee
    {
        return Employee::create(array_merge([
            'employee_no' => fake()->unique()->bothify('EMP-###'),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'province_id' => $this->northern->id,
            'district_id' => $this->kasama->id,
        ], $attributes));
    }

    /**
     * @param array<string, mixed> $attributes
     */
    private function disciplinaryCase(array $attributes = []): DisciplinaryCase
    {
        return DisciplinaryCase::create(array_merge([
            'reference_no' => fake()->unique()->bothify('DC-2026-####'),
            'employee_id' => $this->northernEmployee->id,
            'province_id' => $this->northern->id,
            'district_id' => $this->kasama->id,
            'nature_of_offence' => 'Attendance concern',
            'case_status_id' => CaseStatus::where('code', 'DRAFT')->firstOrFail()->id,
            'effective_date' => now()->toDateString(),
            'expiry_date' => now()->addDays(30)->toDateString(),
        ], $attributes));
    }
}
