<?php

namespace Tests\Feature;

use App\Models\District;
use App\Models\Employee;
use App\Models\EmployeeDependant;
use App\Models\FuneralClaim;
use App\Models\FuneralClaimDocument;
use App\Models\Province;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class FuneralClaimModuleTest extends TestCase
{
    use RefreshDatabase;

    private Role $adminRole;

    private Role $officerRole;

    private Role $viewerRole;

    private Province $northern;

    private Province $luapula;

    private District $kasama;

    private District $mansa;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
        Http::fake(['*' => Http::response(['status' => 'not_enabled'])]);
        $this->adminRole = Role::create(['name' => 'Admin', 'code' => 'ADMIN', 'is_active' => true]);
        $this->officerRole = Role::create(['name' => 'HR Officer', 'code' => 'HRO', 'is_active' => true]);
        $this->viewerRole = Role::create(['name' => 'Viewer', 'code' => 'VIEWER', 'is_active' => true]);
        $this->northern = Province::create(['name' => 'Northern', 'code' => 'NOR', 'is_active' => true]);
        $this->luapula = Province::create(['name' => 'Luapula', 'code' => 'LUA', 'is_active' => true]);
        $this->kasama = District::create(['province_id' => $this->northern->id, 'name' => 'Kasama', 'code' => 'NOR-KAS', 'is_active' => true]);
        $this->mansa = District::create(['province_id' => $this->luapula->id, 'name' => 'Mansa', 'code' => 'LUA-MAN', 'is_active' => true]);
    }

    public function test_unauthenticated_and_viewer_users_cannot_access_claims(): void
    {
        $this->get(route('funeral-claims.index'))->assertRedirect('/login');
        $this->actingAs($this->user($this->viewerRole, $this->northern))->get(route('funeral-claims.index'))->assertForbidden();
    }

    public function test_authorized_user_can_open_claim_registration_form(): void
    {
        $admin = $this->user($this->adminRole);
        $employee = $this->employee();

        $this->actingAs($admin)
            ->get(route('funeral-claims.create'))
            ->assertOk()
            ->assertSee('Register Funeral Claim')
            ->assertSee($employee->employee_no);
    }

    public function test_hr_officer_can_only_register_and_view_assigned_province_claims(): void
    {
        $officer = $this->user($this->officerRole, $this->northern);
        $assigned = $this->employee();
        $outside = $this->employee(['employee_no' => 'EMP-LUA', 'province_id' => $this->luapula->id, 'district_id' => $this->mansa->id]);
        $this->actingAs($officer)->post(route('funeral-claims.store'), $this->payload($assigned))->assertRedirect();
        $this->actingAs($officer)->post(route('funeral-claims.store'), $this->payload($outside))->assertSessionHasErrors('employee_id');
        $this->assertSame(1, FuneralClaim::count());
    }

    public function test_verified_child_claim_calculates_age_band_and_creates_document_checklist(): void
    {
        $admin = $this->user($this->adminRole);
        $employee = $this->employee();
        $child = $employee->dependants()->create(['category' => 'child', 'relationship_detail' => 'biological_child', 'full_name' => 'Test Child', 'date_of_birth' => '2016-01-01', 'verification_status' => EmployeeDependant::VERIFICATION_VERIFIED, 'is_active' => true]);

        $this->actingAs($admin)->post(route('funeral-claims.store'), $this->payload($employee, ['deceased_category' => 'child', 'employee_dependant_id' => $child->id, 'date_of_death' => '2026-01-01', 'notification_date' => '2026-01-02']))->assertRedirect();
        $claim = FuneralClaim::firstOrFail();
        $this->assertSame(FuneralClaim::ELIGIBLE, $claim->eligibility_status);
        $this->assertSame('15000.00', $claim->eligible_amount);
        $this->assertSame(3, $claim->documents()->count());
        $this->assertDatabaseHas('funeral_claim_status_histories', ['funeral_claim_id' => $claim->id, 'from_status' => null, 'to_status' => FuneralClaim::STATUS_AWAITING_DOCUMENTS]);
    }

    public function test_preterm_and_employee_over_70_rules_are_applied(): void
    {
        $admin = $this->user($this->adminRole);
        $employee = $this->employee(['date_of_birth' => '1940-01-01']);
        $this->actingAs($admin)->post(route('funeral-claims.store'), $this->payload($employee))->assertRedirect();
        $over70 = FuneralClaim::firstOrFail();
        $this->assertSame(FuneralClaim::REQUIRES_REVIEW, $over70->eligibility_status);
        $this->assertSame('30000.00', $over70->eligible_amount);

        $mother = $this->employee(['employee_no' => 'EMP-MOTHER']);
        $this->actingAs($admin)->post(route('funeral-claims.store'), $this->payload($mother, ['deceased_category' => 'child', 'deceased_name' => 'Baby', 'gestational_age_weeks' => 24]))->assertRedirect();
        $preterm = FuneralClaim::latest('id')->firstOrFail();
        $this->assertSame(FuneralClaim::REQUIRES_REVIEW, $preterm->eligibility_status);
        $this->assertSame('7500.00', $preterm->eligible_amount);
    }

    public function test_claim_cannot_be_ready_until_eligible_and_required_documents_complete(): void
    {
        $admin = $this->user($this->adminRole);
        $employee = $this->employee(['date_of_birth' => '1980-01-01']);
        $this->actingAs($admin)->post(route('funeral-claims.store'), $this->payload($employee))->assertRedirect();
        $claim = FuneralClaim::firstOrFail();
        $this->actingAs($admin)->patch(route('funeral-claims.update-status', $claim), ['status' => FuneralClaim::STATUS_READY_FOR_APPROVAL])->assertSessionHasErrors('status');
        $claim->documents()->where('is_required', true)->update(['status' => FuneralClaimDocument::APPROVED_CLOSED]);
        $this->actingAs($admin)->patch(route('funeral-claims.update-status', $claim), ['status' => FuneralClaim::STATUS_READY_FOR_APPROVAL])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('funeral_claims', ['id' => $claim->id, 'status' => FuneralClaim::STATUS_READY_FOR_APPROVAL]);
    }

    private function user(Role $role, ?Province $province = null): User
    {
        return User::factory()->create(['role_id' => $role->id, 'province_id' => $province?->id, 'is_active' => true]);
    }

    private function employee(array $attributes = []): Employee
    {
        return Employee::create(array_merge(['employee_no' => fake()->unique()->bothify('EMP-###'), 'first_name' => 'Test', 'last_name' => 'Employee', 'province_id' => $this->northern->id, 'district_id' => $this->kasama->id, 'date_of_birth' => '1980-01-01'], $attributes));
    }

    private function payload(Employee $employee, array $overrides = []): array
    {
        return array_merge(['employee_id' => $employee->id, 'deceased_category' => 'employee', 'date_of_death' => '2026-01-01', 'notification_date' => '2026-01-02', 'internal_notes' => 'Private claim note.'], $overrides);
    }
}
