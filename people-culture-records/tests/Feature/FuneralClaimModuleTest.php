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
        Queue::fake();
        Http::fake(['*' => Http::response(['status' => 'not_enabled'])]);
        $this->adminRole = Role::create(['name' => 'Admin', 'code' => 'ADMIN', 'is_active' => true]);
        $this->managerRole = Role::create(['name' => 'HR Manager', 'code' => 'HRM', 'is_active' => true]);
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

    public function test_registration_form_exposes_registered_dependant_details_for_auto_fill(): void
    {
        $admin = $this->user($this->adminRole);
        $employee = $this->employee();
        $employee->dependants()->create([
            'category' => 'child',
            'relationship_detail' => 'stepchild',
            'full_name' => 'Registered Child',
            'date_of_birth' => '2018-06-15',
            'verification_status' => EmployeeDependant::VERIFICATION_VERIFIED,
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->get(route('funeral-claims.create'))
            ->assertOk()
            ->assertSee('data-name="Registered Child"', false)
            ->assertSee('data-date-of-birth="2018-06-15"', false);
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

    public function test_document_statuses_save_independently_and_received_requires_attachment(): void
    {
        $admin = $this->user($this->adminRole);
        $employee = $this->employee();
        $this->actingAs($admin)->post(route('funeral-claims.store'), $this->payload($employee))->assertRedirect();
        $claim = FuneralClaim::firstOrFail();
        $deathCertificate = $claim->documents()->where('document_type', 'death_certificate')->firstOrFail();
        $certifiedNrc = $claim->documents()->where('document_type', 'certified_nrc')->firstOrFail();

        $this->assertFalse($certifiedNrc->is_required);
        $this->actingAs($admin)
            ->patch(route('funeral-claims.documents.update', [$claim, $deathCertificate]), ['status' => FuneralClaimDocument::RECEIVED])
            ->assertSessionHasErrors("document_status_{$deathCertificate->id}", null, 'documents');
        $errorPage = $this->actingAs($admin)->get(route('funeral-claims.show', $claim));
        $errorPage->assertOk();
        $this->assertSame(1, substr_count($errorPage->getContent(), 'Upload the supporting document before marking it as received.'));
        $errorPage
            ->assertSee("data-document-row=\"claim-document-{$deathCertificate->id}\"", false)
            ->assertSee('documentRow.scrollIntoView', false);

        $deathCertificate->attachments()->create(['original_filename' => 'death.pdf', 'stored_filename' => 'death.pdf', 'file_path' => 'test/death.pdf', 'mime_type' => 'application/pdf', 'file_size' => 100, 'uploaded_by' => $admin->id]);
        $this->actingAs($admin)
            ->patch(route('funeral-claims.documents.update', [$claim, $deathCertificate]), ['status' => FuneralClaimDocument::RECEIVED])
            ->assertSessionHasNoErrors();
        $this->actingAs($admin)
            ->patch(route('funeral-claims.documents.update', [$claim, $certifiedNrc]), ['status' => FuneralClaimDocument::ADDITIONAL_REQUESTED])
            ->assertSessionHasNoErrors();

        $this->assertSame(FuneralClaimDocument::RECEIVED, $deathCertificate->fresh()->status);
        $this->assertSame(FuneralClaimDocument::ADDITIONAL_REQUESTED, $certifiedNrc->fresh()->status);
    }

    public function test_former_employee_dependant_claim_requires_family_meeting(): void
    {
        $admin = $this->user($this->adminRole);
        $employee = $this->employee(['termination_date' => '2025-12-01']);
        $spouse = $employee->dependants()->create(['category' => 'spouse', 'relationship_detail' => 'spouse', 'full_name' => 'Former Spouse', 'date_of_birth' => '1982-01-01', 'verification_status' => EmployeeDependant::VERIFICATION_VERIFIED, 'is_active' => true]);

        $this->actingAs($admin)->post(route('funeral-claims.store'), $this->payload($employee, ['deceased_category' => 'spouse', 'employee_dependant_id' => $spouse->id]))->assertRedirect();

        $claim = FuneralClaim::firstOrFail();
        $this->assertTrue($claim->family_meeting_required);
        $this->assertSame('required', $claim->family_meeting_status);
        $this->actingAs($admin)
            ->get(route('funeral-claims.show', $claim))
            ->assertOk()
            ->assertSee('Meeting outcome');
    }

    public function test_current_employee_dependant_claim_does_not_show_meeting_fields(): void
    {
        $admin = $this->user($this->adminRole);
        $employee = $this->employee();
        $child = $employee->dependants()->create(['category' => 'child', 'relationship_detail' => 'biological_child', 'full_name' => 'Current Child', 'date_of_birth' => '2018-01-01', 'verification_status' => EmployeeDependant::VERIFICATION_VERIFIED, 'is_active' => true]);
        $this->actingAs($admin)->post(route('funeral-claims.store'), $this->payload($employee, ['deceased_category' => 'child', 'employee_dependant_id' => $child->id]))->assertRedirect();
        $claim = FuneralClaim::firstOrFail();

        $this->assertFalse($claim->family_meeting_required);
        $this->actingAs($admin)
            ->get(route('funeral-claims.show', $claim))
            ->assertOk()
            ->assertDontSee('Meeting outcome')
            ->assertDontSee('Scheduled for');
    }

    public function test_hr_officer_cannot_approve_claim_and_manager_cannot_approve_without_prerequisites(): void
    {
        $admin = $this->user($this->adminRole);
        $manager = $this->user($this->managerRole);
        $officer = $this->user($this->officerRole, $this->northern);
        $claim = $this->readyClaim($admin);

        $this->actingAs($officer)->patch(route('funeral-claims.decision', $claim), ['decision' => 'approve', 'comment' => 'Officer approval.'])->assertForbidden();
        $this->actingAs($manager)->patch(route('funeral-claims.decision', $claim), ['decision' => 'approve', 'comment' => 'Manager approval.'])->assertSessionHasErrors('decision');
        $this->assertSame(FuneralClaim::STATUS_READY_FOR_APPROVAL, $claim->fresh()->status);
    }

    public function test_approved_claim_can_progress_to_recorded_payment(): void
    {
        $admin = $this->user($this->adminRole);
        $manager = $this->user($this->managerRole);
        $claim = $this->readyClaim($admin);

        $this->actingAs($admin)->patch(route('funeral-claims.processing.update', $claim), [
            'family_meeting_status' => 'not_required',
            'grant_consent_confirmed' => 1,
            'grant_consent_date' => '2026-01-03',
            'banking_details_verified' => 1,
            'payreq_reference' => 'PR-1001',
            'payreq_raised_date' => '2026-01-03',
            'payreq_status' => 'approved',
            'payreq_approved_date' => '2026-01-04',
        ])->assertSessionHasNoErrors();

        $this->actingAs($manager)->patch(route('funeral-claims.decision', $claim), ['decision' => 'approve', 'grant_amount_approved' => 30000, 'comment' => 'Approved after verification.'])->assertSessionHasNoErrors();
        $this->actingAs($admin)->patch(route('funeral-claims.start-payment', $claim))->assertSessionHasNoErrors();
        $this->actingAs($admin)->patch(route('funeral-claims.payment', $claim), ['payment_date' => '2026-01-05', 'payment_reference' => 'PAY-1001', 'family_payment_confirmed_date' => '2026-01-05'])->assertSessionHasNoErrors();

        $claim->refresh();
        $this->assertSame(FuneralClaim::STATUS_PAID, $claim->status);
        $this->assertSame('paid', $claim->payment_status);
        $this->assertSame('30000.00', $claim->grant_amount_approved);
        $this->assertDatabaseHas('funeral_claim_workflow_events', ['funeral_claim_id' => $claim->id, 'event_type' => 'payment_recorded']);
    }

    public function test_only_manager_or_admin_can_reopen_rejected_claim(): void
    {
        $admin = $this->user($this->adminRole);
        $manager = $this->user($this->managerRole);
        $officer = $this->user($this->officerRole, $this->northern);
        $claim = $this->readyClaim($admin);
        $this->actingAs($manager)->patch(route('funeral-claims.decision', $claim), ['decision' => 'reject', 'comment' => 'Insufficient support.'])->assertSessionHasNoErrors();

        $this->actingAs($officer)->patch(route('funeral-claims.reopen', $claim), ['reason' => 'New documents received.'])->assertForbidden();
        $this->actingAs($manager)->patch(route('funeral-claims.reopen', $claim), ['reason' => 'New documents received.'])->assertSessionHasNoErrors();
        $this->assertSame(FuneralClaim::STATUS_UNDER_VERIFICATION, $claim->fresh()->status);
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

    private function readyClaim(User $creator): FuneralClaim
    {
        $employee = $this->employee();
        $this->actingAs($creator)->post(route('funeral-claims.store'), $this->payload($employee))->assertRedirect();
        $claim = FuneralClaim::latest('id')->firstOrFail();
        $claim->documents()->where('is_required', true)->update(['status' => FuneralClaimDocument::APPROVED_CLOSED]);
        $this->actingAs($creator)->patch(route('funeral-claims.update-status', $claim), ['status' => FuneralClaim::STATUS_READY_FOR_APPROVAL, 'comment' => 'Ready.'])->assertSessionHasNoErrors();

        return $claim->fresh();
    }
}
