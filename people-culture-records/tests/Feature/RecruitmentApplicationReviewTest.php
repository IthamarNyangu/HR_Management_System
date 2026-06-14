<?php

namespace Tests\Feature;

use App\Mail\JobApplicationRejectedMail;
use App\Mail\JobApplicationShortlistedMail;
use App\Models\Department;
use App\Models\District;
use App\Models\Facility;
use App\Models\JobApplication;
use App\Models\JobOpening;
use App\Models\JobTitle;
use App\Models\Project;
use App\Models\Province;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RecruitmentApplicationReviewTest extends TestCase
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
    private Facility $kasamaFacility;
    private Department $department;
    private Project $project;
    private JobTitle $jobTitle;

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
        $this->kasamaFacility = Facility::create(['district_id' => $this->kasama->id, 'name' => 'Kasama Office', 'code' => 'KAS-OFF', 'is_active' => true]);
        $this->department = Department::create(['name' => 'People and Culture', 'code' => 'P&C', 'is_active' => true]);
        $this->project = Project::create(['name' => 'General Operations', 'code' => 'GO', 'is_active' => true]);
        $this->jobTitle = JobTitle::create(['name' => 'Data Analyst', 'code' => 'DATA-ANALYST', 'is_active' => true]);
    }

    public function test_admin_and_hr_manager_can_review_applications(): void
    {
        $application = $this->application();
        $admin = $this->user($this->adminRole);

        $this->actingAs($admin)
            ->patch(route('recruitment.applications.update-review', $application), [
                'qualification_score' => 80,
                'experience_score' => 70,
                'screening_score' => 90,
                'review_notes' => 'Strong candidate.',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $application->refresh();

        $this->assertSame('80.00', $application->qualification_score);
        $this->assertSame('70.00', $application->experience_score);
        $this->assertSame('90.00', $application->screening_score);
        $this->assertSame('80.00', $application->overall_score);
        $this->assertSame('Strong candidate.', $application->review_notes);
        $this->assertSame($admin->id, $application->reviewed_by);
        $this->assertDatabaseHas('activity_logs', ['action' => 'job_application_review_updated']);

        $this->actingAs($this->user($this->managerRole))
            ->patch(route('recruitment.applications.update-status', $application), [
                'status' => JobApplication::STATUS_UNDER_REVIEW,
                'comment' => 'Manager review started.',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame(JobApplication::STATUS_UNDER_REVIEW, $application->fresh()->status);
    }

    public function test_all_null_scores_leave_overall_score_null(): void
    {
        $application = $this->application();

        $this->actingAs($this->user($this->adminRole))
            ->patch(route('recruitment.applications.update-review', $application), [
                'qualification_score' => null,
                'experience_score' => null,
                'screening_score' => null,
                'review_notes' => 'Reviewed without scores.',
            ])
            ->assertRedirect();

        $this->assertNull($application->fresh()->overall_score);
    }

    public function test_hr_officer_can_review_assigned_province_but_not_outside_or_global_applications(): void
    {
        $officer = $this->user($this->officerRole, $this->northern);
        $assigned = $this->application();
        $outsideJob = $this->jobOpening([
            'reference_no' => 'JOB-2026-0200',
            'slug' => 'outside-job-2026-0200',
            'province_id' => $this->luapula->id,
            'district_id' => $this->mansa->id,
            'facility_id' => null,
        ]);
        $outside = $this->application(['job_opening_id' => $outsideJob->id, 'reference_no' => 'APP-2026-0200']);
        $globalJob = $this->jobOpening([
            'reference_no' => 'JOB-2026-0300',
            'slug' => 'global-job-2026-0300',
            'province_id' => null,
            'district_id' => null,
            'facility_id' => null,
        ]);
        $global = $this->application(['job_opening_id' => $globalJob->id, 'reference_no' => 'APP-2026-0300']);

        $this->actingAs($officer)
            ->patch(route('recruitment.applications.update-review', $assigned), ['screening_score' => 75])
            ->assertRedirect();

        $this->actingAs($officer)
            ->patch(route('recruitment.applications.update-review', $outside), ['screening_score' => 75])
            ->assertForbidden();

        $this->actingAs($officer)
            ->get(route('recruitment.applications.show', $global))
            ->assertOk();

        $this->actingAs($officer)
            ->patch(route('recruitment.applications.update-review', $global), ['screening_score' => 75])
            ->assertForbidden();
    }

    public function test_viewer_can_view_but_cannot_review_update_notes_or_email(): void
    {
        $viewer = $this->user($this->viewerRole, $this->northern);
        $application = $this->application();

        $this->actingAs($viewer)
            ->get(route('recruitment.applications.show', $application))
            ->assertOk();

        $this->actingAs($viewer)
            ->patch(route('recruitment.applications.update-review', $application), ['screening_score' => 50])
            ->assertForbidden();

        $this->actingAs($viewer)
            ->patch(route('recruitment.applications.update-status', $application), ['status' => JobApplication::STATUS_UNDER_REVIEW])
            ->assertForbidden();

        $this->actingAs($viewer)
            ->post(route('recruitment.applications.add-note', $application), ['note' => 'Internal note'])
            ->assertForbidden();

        $this->actingAs($viewer)
            ->post(route('recruitment.applications.send-email', $application), ['email_type' => 'shortlisted'])
            ->assertForbidden();
    }

    public function test_notes_and_status_history_are_created(): void
    {
        $application = $this->application();
        $admin = $this->user($this->adminRole);

        $this->actingAs($admin)
            ->post(route('recruitment.applications.add-note', $application), ['note' => 'Meets minimum screening criteria.'])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('job_application_notes', [
            'job_application_id' => $application->id,
            'user_id' => $admin->id,
            'note' => 'Meets minimum screening criteria.',
            'is_private' => true,
        ]);
        $this->assertDatabaseHas('activity_logs', ['action' => 'job_application_note_added']);

        $this->actingAs($admin)
            ->patch(route('recruitment.applications.update-status', $application), [
                'status' => JobApplication::STATUS_LONGLISTED,
                'comment' => 'Moved to longlist.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('job_application_status_histories', [
            'job_application_id' => $application->id,
            'from_status' => JobApplication::STATUS_SUBMITTED,
            'to_status' => JobApplication::STATUS_LONGLISTED,
            'changed_by' => $admin->id,
            'comment' => 'Moved to longlist.',
        ]);
    }

    public function test_shortlist_updates_status_and_sends_email_only_when_requested(): void
    {
        Mail::fake();
        $application = $this->application();
        $admin = $this->user($this->adminRole);

        $this->actingAs($admin)
            ->patch(route('recruitment.applications.shortlist', $application))
            ->assertRedirect()
            ->assertSessionHas('success');

        $application->refresh();

        $this->assertSame(JobApplication::STATUS_SHORTLISTED, $application->status);
        $this->assertNotNull($application->shortlisted_at);
        Mail::assertNothingSent();

        $this->actingAs($admin)
            ->post(route('recruitment.applications.send-email', $application), ['email_type' => 'shortlisted'])
            ->assertRedirect()
            ->assertSessionHas('success');

        Mail::assertSent(JobApplicationShortlistedMail::class);
        $this->assertDatabaseHas('activity_logs', ['action' => 'job_application_shortlist_email_sent']);
    }

    public function test_reject_requires_reason_and_sends_email_only_when_requested_without_exposing_scores_or_notes(): void
    {
        Mail::fake();
        $application = $this->application([
            'reference_no' => 'APP-2026-0600',
            'qualification_score' => 97.25,
            'review_notes' => 'Internal note that must not be disclosed.',
        ]);
        $admin = $this->user($this->adminRole);

        $this->actingAs($admin)
            ->patch(route('recruitment.applications.reject', $application), ['send_email' => '1'])
            ->assertSessionHasErrors(['rejection_reason']);

        $this->actingAs($admin)
            ->patch(route('recruitment.applications.reject', $application), [
                'rejection_reason' => 'Other candidates were a closer match.',
                'send_email' => '1',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $application->refresh();

        $this->assertSame(JobApplication::STATUS_REJECTED, $application->status);
        $this->assertNotNull($application->rejected_at);
        $this->assertSame('Other candidates were a closer match.', $application->rejection_reason);

        Mail::assertSent(JobApplicationRejectedMail::class, function (JobApplicationRejectedMail $mail): bool {
            $html = $mail->render();

            return ! str_contains($html, '97.25')
                && ! str_contains($html, 'Internal note that must not be disclosed.');
        });
        $this->assertDatabaseHas('activity_logs', ['action' => 'job_application_rejection_email_sent']);
    }

    public function test_withdrawn_application_cannot_be_shortlisted_or_rejected(): void
    {
        $application = $this->application([
            'status' => JobApplication::STATUS_WITHDRAWN,
            'withdrawn_at' => now(),
        ]);
        $admin = $this->user($this->adminRole);

        $this->actingAs($admin)
            ->patch(route('recruitment.applications.shortlist', $application))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->actingAs($admin)
            ->patch(route('recruitment.applications.reject', $application), [
                'rejection_reason' => 'Not proceeding.',
            ])
            ->assertSessionHasErrors();

        $this->assertSame(JobApplication::STATUS_WITHDRAWN, $application->fresh()->status);
    }

    public function test_application_index_filters_by_status_job_and_score(): void
    {
        $job = $this->jobOpening(['title' => 'Finance Officer', 'reference_no' => 'JOB-2026-0400', 'slug' => 'finance-officer-2026-0400']);
        $matching = $this->application([
            'job_opening_id' => $job->id,
            'reference_no' => 'APP-2026-0400',
            'first_name' => 'Alice',
            'status' => JobApplication::STATUS_SHORTLISTED,
            'overall_score' => 85,
        ]);
        $this->application([
            'reference_no' => 'APP-2026-0500',
            'first_name' => 'Brian',
            'status' => JobApplication::STATUS_SUBMITTED,
            'overall_score' => 40,
        ]);

        $this->actingAs($this->user($this->adminRole))
            ->get(route('recruitment.applications.index', [
                'status' => JobApplication::STATUS_SHORTLISTED,
                'job_opening_id' => $job->id,
                'score_min' => 80,
            ]))
            ->assertOk()
            ->assertSee($matching->reference_no)
            ->assertDontSee('APP-2026-0500');
    }

    public function test_document_download_authorization_still_works(): void
    {
        Storage::fake('local');
        $application = $this->application();
        $document = $application->documents()->create([
            'document_type' => JobApplication::DOCUMENT_CV,
            'original_filename' => 'cv.pdf',
            'stored_filename' => 'cv.pdf',
            'file_path' => 'job-applications/testing/cv.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 10,
            'uploaded_at' => now(),
        ]);
        Storage::disk('local')->put($document->file_path, 'document');

        $this->actingAs($this->user($this->officerRole, $this->northern))
            ->get(route('recruitment.applications.documents.view', [$application, $document]))
            ->assertOk();

        $this->actingAs($this->user($this->officerRole, $this->northern))
            ->get(route('recruitment.applications.documents.download', [$application, $document]))
            ->assertOk();

        $this->actingAs($this->user($this->officerRole, $this->luapula))
            ->get(route('recruitment.applications.documents.download', [$application, $document]))
            ->assertForbidden();
    }

    private function user(Role $role, ?Province $province = null): User
    {
        return User::factory()->create([
            'role_id' => $role->id,
            'province_id' => $province?->id,
            'is_active' => true,
            'must_change_password' => false,
        ]);
    }

    /**
     * @param array<string, mixed> $attributes
     */
    private function jobOpening(array $attributes = []): JobOpening
    {
        $reference = $attributes['reference_no'] ?? fake()->unique()->bothify('JOB-2026-####');

        return JobOpening::create(array_merge([
            'reference_no' => $reference,
            'slug' => str('data-analyst-'.$reference)->slug(),
            'title' => 'Data Analyst',
            'job_title_id' => $this->jobTitle->id,
            'project_id' => $this->project->id,
            'department_id' => $this->department->id,
            'province_id' => $this->northern->id,
            'district_id' => $this->kasama->id,
            'facility_id' => $this->kasamaFacility->id,
            'visibility' => JobOpening::VISIBILITY_EXTERNAL,
            'status' => JobOpening::STATUS_PUBLISHED,
            'number_of_positions' => 2,
            'show_number_of_positions' => true,
            'opening_date' => now()->toDateString(),
            'closing_date' => now()->addWeeks(2)->toDateString(),
            'summary' => 'A public recruitment opportunity.',
            'description' => 'This role supports programme reporting.',
            'application_instructions' => 'Apply through the careers portal.',
        ], $attributes));
    }

    /**
     * @param array<string, mixed> $attributes
     */
    private function application(array $attributes = []): JobApplication
    {
        return JobApplication::create(array_merge([
            'reference_no' => fake()->unique()->bothify('APP-2026-####'),
            'job_opening_id' => $this->jobOpening()->id,
            'source' => JobApplication::SOURCE_EXTERNAL,
            'status' => JobApplication::STATUS_SUBMITTED,
            'first_name' => 'Mary',
            'last_name' => 'Banda',
            'email' => fake()->unique()->safeEmail(),
            'phone' => '0977000000',
            'highest_qualification' => "Bachelor's Degree",
            'years_of_experience' => 4,
            'motivation' => 'I am interested in the role.',
            'consent_given_at' => now(),
            'submitted_at' => now(),
        ], $attributes));
    }
}
