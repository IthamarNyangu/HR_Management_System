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
            ->assertSee('Admin approved disciplinary case DC-2026-1001.');
    }

    public function test_viewer_cannot_see_activity_outside_assigned_province(): void
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
            ->assertDontSee('Created outside province case.');
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
