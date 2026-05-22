<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\District;
use App\Models\Employee;
use App\Models\Province;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserEmployeeLinkTest extends TestCase
{
    use RefreshDatabase;

    private Role $adminRole;
    private Role $managerRole;
    private Role $officerRole;
    private Role $viewerRole;
    private Province $northern;
    private District $kasama;
    private Department $peopleCulture;
    private Employee $employee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminRole = Role::create(['name' => 'Admin', 'code' => 'ADMIN', 'is_active' => true]);
        $this->managerRole = Role::create(['name' => 'HR Manager', 'code' => 'HRM', 'is_active' => true]);
        $this->officerRole = Role::create(['name' => 'HR Officer', 'code' => 'HRO', 'is_active' => true]);
        $this->viewerRole = Role::create(['name' => 'Viewer', 'code' => 'VIEWER', 'is_active' => true]);

        $this->northern = Province::create(['name' => 'Northern', 'code' => 'NOR', 'is_active' => true]);
        $this->kasama = District::create(['province_id' => $this->northern->id, 'name' => 'Kasama', 'code' => 'NOR-KAS', 'is_active' => true]);
        $this->peopleCulture = Department::create(['name' => 'People & Culture', 'code' => 'PC', 'is_active' => true]);

        $this->employee = Employee::create([
            'employee_no' => 'RTC-001',
            'first_name' => 'Grace',
            'last_name' => 'Banda',
            'email' => 'grace.banda@example.test',
            'province_id' => $this->northern->id,
            'district_id' => $this->kasama->id,
            'department_id' => $this->peopleCulture->id,
        ]);
    }

    public function test_admin_can_create_user_from_employee(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post(route('admin.users.store'), [
                'employee_id' => $this->employee->id,
                'name' => '',
                'email' => '',
                'role_id' => $this->officerRole->id,
                'province_id' => '',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'is_active' => '1',
            ])
            ->assertRedirect(route('admin.users.index'));

        $this->assertDatabaseHas('users', [
            'employee_id' => $this->employee->id,
            'name' => 'Grace Banda',
            'email' => 'grace.banda@example.test',
            'province_id' => $this->northern->id,
            'role_id' => $this->officerRole->id,
            'must_change_password' => true,
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'user_created_from_employee',
            'user_id' => $admin->id,
        ]);
    }

    public function test_cannot_create_two_users_for_same_employee(): void
    {
        $admin = $this->admin();

        User::factory()->create([
            'employee_id' => $this->employee->id,
            'role_id' => $this->officerRole->id,
            'province_id' => $this->northern->id,
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->from(route('admin.users.create'))
            ->post(route('admin.users.store'), [
                'employee_id' => $this->employee->id,
                'name' => 'Duplicate User',
                'email' => 'duplicate@example.test',
                'role_id' => $this->officerRole->id,
                'province_id' => $this->northern->id,
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'is_active' => '1',
            ])
            ->assertRedirect(route('admin.users.create'))
            ->assertSessionHasErrors('employee_id');
    }

    public function test_can_create_manual_user_without_employee(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.users.store'), [
                'employee_id' => '',
                'name' => 'External Auditor',
                'email' => 'auditor@example.test',
                'role_id' => $this->viewerRole->id,
                'province_id' => $this->northern->id,
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'is_active' => '1',
            ])
            ->assertRedirect(route('admin.users.index'));

        $this->assertDatabaseHas('users', [
            'employee_id' => null,
            'email' => 'auditor@example.test',
            'province_id' => $this->northern->id,
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'user_created',
        ]);
    }

    public function test_hr_officer_and_viewer_province_rules_still_work(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->from(route('admin.users.create'))
            ->post(route('admin.users.store'), [
                'name' => 'Viewer User',
                'email' => 'viewer@example.test',
                'role_id' => $this->viewerRole->id,
                'province_id' => '',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'is_active' => '1',
            ])
            ->assertRedirect(route('admin.users.create'))
            ->assertSessionHasErrors('province_id');
    }

    public function test_user_edit_can_keep_same_employee_link(): void
    {
        $admin = $this->admin();
        $user = User::factory()->create([
            'employee_id' => $this->employee->id,
            'name' => 'Grace Banda',
            'email' => 'grace.user@example.test',
            'role_id' => $this->officerRole->id,
            'province_id' => $this->northern->id,
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.users.edit', $user))
            ->assertOk()
            ->assertSee('Grace Banda')
            ->assertSee('RTC-001');

        $this->actingAs($admin)
            ->put(route('admin.users.update', $user), [
                'employee_id' => $this->employee->id,
                'name' => 'Grace Banda',
                'email' => 'grace.user@example.test',
                'role_id' => $this->officerRole->id,
                'province_id' => $this->northern->id,
                'password' => '',
                'password_confirmation' => '',
                'is_active' => '1',
            ])
            ->assertRedirect(route('admin.users.index'));

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'employee_id' => $this->employee->id,
        ]);
    }

    public function test_user_edit_cannot_link_to_employee_already_linked_to_another_user(): void
    {
        $admin = $this->admin();
        $linkedUser = User::factory()->create([
            'employee_id' => $this->employee->id,
            'role_id' => $this->officerRole->id,
            'province_id' => $this->northern->id,
            'is_active' => true,
        ]);
        $otherUser = User::factory()->create([
            'role_id' => $this->viewerRole->id,
            'province_id' => $this->northern->id,
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->from(route('admin.users.edit', $otherUser))
            ->put(route('admin.users.update', $otherUser), [
                'employee_id' => $this->employee->id,
                'name' => $otherUser->name,
                'email' => $otherUser->email,
                'role_id' => $this->viewerRole->id,
                'province_id' => $this->northern->id,
                'password' => '',
                'password_confirmation' => '',
                'is_active' => '1',
            ])
            ->assertRedirect(route('admin.users.edit', $otherUser))
            ->assertSessionHasErrors('employee_id');

        $this->assertDatabaseHas('users', [
            'id' => $linkedUser->id,
            'employee_id' => $this->employee->id,
        ]);
    }

    public function test_user_access_register_shows_employee_job_title_and_access_profile(): void
    {
        $admin = $this->admin();
        $user = User::factory()->create([
            'employee_id' => $this->employee->id,
            'name' => 'Grace Banda',
            'email' => 'grace.user@example.test',
            'role_id' => $this->officerRole->id,
            'province_id' => $this->northern->id,
            'is_active' => true,
            'must_change_password' => true,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('User Access Register')
            ->assertSee('System Role / Access Profile')
            ->assertSee('RTC-001')
            ->assertSee('Grace Banda')
            ->assertSee('HR Officer')
            ->assertSee('Change required');
    }

    public function test_user_with_temporary_password_must_change_password_before_dashboard(): void
    {
        $user = User::factory()->create([
            'role_id' => $this->officerRole->id,
            'province_id' => $this->northern->id,
            'is_active' => true,
            'must_change_password' => true,
            'password' => Hash::make('temporary123'),
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertRedirect(route('password.change'));

        $this->actingAs($user)
            ->get(route('password.change'))
            ->assertOk()
            ->assertSee('Set Your Own Password');
    }

    public function test_user_can_change_temporary_password_and_continue(): void
    {
        $user = User::factory()->create([
            'role_id' => $this->officerRole->id,
            'province_id' => $this->northern->id,
            'is_active' => true,
            'must_change_password' => true,
            'password' => Hash::make('temporary123'),
        ]);

        $this->actingAs($user)
            ->put(route('password.change.update'), [
                'password' => 'new-password-123',
                'password_confirmation' => 'new-password-123',
            ])
            ->assertRedirect(route('dashboard'));

        $user->refresh();

        $this->assertFalse($user->must_change_password);
        $this->assertTrue(Hash::check('new-password-123', $user->password));
        $this->assertDatabaseHas('activity_logs', [
            'action' => 'password_changed',
            'user_id' => $user->id,
        ]);
    }

    private function admin(): User
    {
        return User::factory()->create([
            'role_id' => $this->adminRole->id,
            'province_id' => null,
            'is_active' => true,
        ]);
    }
}
