<?php

namespace Tests\Feature;

use App\Mail\SharedJobOpeningMail;
use App\Mail\JobOpeningReadvertisedMail;
use App\Models\Department;
use App\Models\District;
use App\Models\EmploymentType;
use App\Models\Facility;
use App\Models\JobOpening;
use App\Models\JobApplication;
use App\Models\JobTitle;
use App\Models\Project;
use App\Models\Province;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class RecruitmentJobOpeningTest extends TestCase
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
    private EmploymentType $employmentType;

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
        $this->employmentType = EmploymentType::create(['name' => 'Full-time', 'code' => 'FULL_TIME', 'is_active' => true]);
    }

    public function test_unauthenticated_users_cannot_access_internal_recruitment_pages(): void
    {
        $this->get(route('recruitment.job-openings.index'))->assertRedirect('/login');
    }

    public function test_admin_can_manage_job_openings(): void
    {
        $admin = $this->user($this->adminRole);

        $this->actingAs($admin)
            ->post(route('recruitment.job-openings.store'), $this->payload())
            ->assertRedirect();

        $job = JobOpening::firstOrFail();

        $this->assertSame('RTCZ'.now()->format('y').'-001', $job->reference_no);
        $this->assertSame('data-analyst-rtcz'.now()->format('y').'-001', $job->slug);
        $this->assertDatabaseHas('activity_logs', ['action' => 'job_opening_created']);
        $this->assertSame($this->jobTitle->id, $job->reporting_to_job_title_id);

        $this->actingAs($admin)
            ->patch(route('recruitment.job-openings.publish', $job))
            ->assertRedirect();

        $this->assertSame(JobOpening::STATUS_PUBLISHED, $job->fresh()->status);
    }

    public function test_cancelled_recruitment_is_preserved_and_can_be_prepared_for_readvertising(): void
    {
        $admin = $this->user($this->adminRole);
        $job = $this->jobOpening([
            'visibility' => JobOpening::VISIBILITY_EXTERNAL,
            'status' => JobOpening::STATUS_PUBLISHED,
        ]);

        $this->actingAs($admin)
            ->patch(route('recruitment.job-openings.cancel', $job))
            ->assertRedirect();

        $this->assertSame(JobOpening::STATUS_CANCELLED, $job->fresh()->status);
        $this->assertDatabaseHas('job_openings', ['id' => $job->id, 'reference_no' => $job->reference_no]);
        $this->assertDatabaseHas('activity_logs', ['action' => 'job_opening_cancelled']);

        $this->actingAs($admin)
            ->patch(route('recruitment.job-openings.prepare-readvertising', $job))
            ->assertRedirect(route('recruitment.job-openings.edit', $job));

        $this->assertSame(JobOpening::STATUS_DRAFT, $job->fresh()->status);
        $this->assertTrue($job->fresh()->opening_date->isSameDay(today()));
        $this->assertNull($job->fresh()->published_at);
        $this->assertDatabaseHas('activity_logs', ['action' => 'job_opening_prepared_for_readvertising']);

        $this->actingAs($admin)
            ->get(route('recruitment.job-openings.show', $job->fresh()))
            ->assertOk()
            ->assertSee('Cancel Recruitment')
            ->assertDontSee('Re-advertise');
    }

    public function test_cancelled_recruitment_shows_re_advertise_action_and_cannot_publish_directly(): void
    {
        $admin = $this->user($this->adminRole);
        $job = $this->jobOpening(['status' => JobOpening::STATUS_CANCELLED]);

        $this->actingAs($admin)
            ->get(route('recruitment.job-openings.show', $job))
            ->assertOk()
            ->assertSee('Re-advertise')
            ->assertDontSee('>Publish<', false);

        $this->actingAs($admin)
            ->patch(route('recruitment.job-openings.publish', $job))
            ->assertRedirect();

        $this->assertSame(JobOpening::STATUS_CANCELLED, $job->fresh()->status);
    }

    public function test_published_readvertisement_can_notify_previous_non_withdrawn_applicants_once(): void
    {
        Mail::fake();

        $admin = $this->user($this->adminRole);
        $job = $this->jobOpening([
            'status' => JobOpening::STATUS_PUBLISHED,
            'advertisement_round' => 2,
        ]);
        $eligible = $this->applicationFor($job, [
            'reference_no' => 'APP-2026-2001',
            'email' => 'previous@example.org',
        ]);
        $withdrawn = $this->applicationFor($job, [
            'reference_no' => 'APP-2026-2002',
            'email' => 'withdrawn@example.org',
            'status' => JobApplication::STATUS_WITHDRAWN,
            'withdrawn_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('recruitment.job-openings.show', $job))
            ->assertOk()
            ->assertSee('Notify Previous Applicants (1)');

        $this->actingAs($admin)
            ->post(route('recruitment.job-openings.notify-previous-applicants', $job))
            ->assertRedirect()
            ->assertSessionHas('success');

        Mail::assertSent(JobOpeningReadvertisedMail::class, function (JobOpeningReadvertisedMail $mail): bool {
            return $mail->hasTo('previous@example.org');
        });
        Mail::assertNotSent(JobOpeningReadvertisedMail::class, function (JobOpeningReadvertisedMail $mail): bool {
            return $mail->hasTo('withdrawn@example.org');
        });
        $this->assertDatabaseHas('job_opening_readvertisement_notifications', [
            'job_opening_id' => $job->id,
            'job_application_id' => $eligible->id,
            'advertisement_round' => 2,
        ]);
        $this->assertDatabaseMissing('job_opening_readvertisement_notifications', [
            'job_application_id' => $withdrawn->id,
        ]);
        $this->assertDatabaseHas('activity_logs', ['action' => 'job_opening_readvertisement_notices_sent']);

        $this->actingAs($admin)
            ->post(route('recruitment.job-openings.notify-previous-applicants', $job))
            ->assertRedirect()
            ->assertSessionHas('info');

        Mail::assertSent(JobOpeningReadvertisedMail::class, 1);
    }

    public function test_authorized_user_can_download_vacancy_announcement_pdf(): void
    {
        $admin = $this->user($this->adminRole);
        $job = $this->jobOpening([
            'responsibilities' => "Prepare reports\nSupport data quality",
            'requirements' => "Strong Excel skills\nGood communication",
        ]);

        $this->actingAs($admin)
            ->get(route('recruitment.job-openings.index'))
            ->assertOk()
            ->assertSee(route('recruitment.job-openings.announcement.pdf', $job), false);

        $response = $this->actingAs($admin)
            ->get(route('recruitment.job-openings.announcement.pdf', $job));

        $response->assertOk();
        $this->assertStringContainsString('application/pdf', $response->headers->get('content-type'));
        $this->assertStringContainsString('attachment;', $response->headers->get('content-disposition'));
    }

    public function test_hr_manager_can_manage_job_openings(): void
    {
        $this->actingAs($this->user($this->managerRole))
            ->post(route('recruitment.job-openings.store'), $this->payload([
                'title' => 'HR Officer',
                'province_id' => $this->luapula->id,
                'district_id' => $this->mansa->id,
                'facility_id' => null,
            ]))
            ->assertRedirect();

        $this->assertDatabaseHas('job_openings', [
            'title' => 'Data Analyst',
            'job_title_id' => $this->jobTitle->id,
            'province_id' => $this->luapula->id,
        ]);
    }

    public function test_hr_officer_can_view_assigned_and_global_jobs_but_cannot_manage_job_postings(): void
    {
        $officer = $this->user($this->officerRole, $this->northern);
        $outsideJob = $this->jobOpening([
            'province_id' => $this->luapula->id,
            'district_id' => $this->mansa->id,
        ]);
        $assignedJob = $this->jobOpening();
        $globalJob = $this->jobOpening(['province_id' => null, 'district_id' => null]);

        $this->actingAs($officer)
            ->post(route('recruitment.job-openings.store'), $this->payload())
            ->assertForbidden();

        $this->actingAs($officer)->get(route('recruitment.job-openings.show', $assignedJob))->assertOk();
        $this->actingAs($officer)->get(route('recruitment.job-openings.show', $outsideJob))->assertForbidden();
        $this->actingAs($officer)->get(route('recruitment.job-openings.show', $globalJob))->assertOk();
        $this->actingAs($officer)->get(route('recruitment.job-openings.create'))->assertForbidden();
        $this->actingAs($officer)->get(route('recruitment.job-openings.edit', $assignedJob))->assertForbidden();
        $this->actingAs($officer)->get(route('recruitment.job-openings.edit', $globalJob))->assertForbidden();
        $this->actingAs($officer)->patch(route('recruitment.job-openings.archive', $assignedJob))->assertForbidden();
    }

    public function test_viewer_can_view_but_cannot_create_update_or_archive(): void
    {
        $viewer = $this->user($this->viewerRole, $this->northern);
        $job = $this->jobOpening();

        $this->actingAs($viewer)->get(route('recruitment.job-openings.show', $job))->assertOk();
        $this->actingAs($viewer)->get(route('recruitment.job-openings.create'))->assertForbidden();
        $this->actingAs($viewer)->get(route('recruitment.job-openings.edit', $job))->assertForbidden();
        $this->actingAs($viewer)->patch(route('recruitment.job-openings.archive', $job))->assertForbidden();
    }

    public function test_public_careers_page_shows_only_public_published_non_expired_jobs(): void
    {
        $public = $this->jobOpening([
            'title' => 'Public Nurse',
            'visibility' => JobOpening::VISIBILITY_EXTERNAL,
            'status' => JobOpening::STATUS_PUBLISHED,
            'closing_date' => now()->addWeek()->toDateString(),
        ]);
        $this->jobOpening(['title' => 'Internal Only', 'visibility' => JobOpening::VISIBILITY_INTERNAL, 'status' => JobOpening::STATUS_PUBLISHED]);
        $this->jobOpening(['title' => 'Draft Job', 'visibility' => JobOpening::VISIBILITY_EXTERNAL, 'status' => JobOpening::STATUS_DRAFT]);
        $this->jobOpening(['title' => 'Closed Job', 'visibility' => JobOpening::VISIBILITY_EXTERNAL, 'status' => JobOpening::STATUS_CLOSED]);
        $this->jobOpening(['title' => 'Cancelled Job', 'visibility' => JobOpening::VISIBILITY_EXTERNAL, 'status' => JobOpening::STATUS_CANCELLED]);
        $this->jobOpening(['title' => 'Expired Job', 'visibility' => JobOpening::VISIBILITY_EXTERNAL, 'status' => JobOpening::STATUS_PUBLISHED, 'closing_date' => now()->subDay()->toDateString()]);

        $this->get(route('careers.index'))
            ->assertOk()
            ->assertSee($public->title)
            ->assertDontSee('Internal Only')
            ->assertDontSee('Draft Job')
            ->assertDontSee('Closed Job')
            ->assertDontSee('Cancelled Job')
            ->assertDontSee('Expired Job');
    }

    public function test_public_job_can_be_shared_by_email(): void
    {
        Mail::fake();

        $job = $this->jobOpening([
            'visibility' => JobOpening::VISIBILITY_EXTERNAL,
            'status' => JobOpening::STATUS_PUBLISHED,
            'closing_date' => now()->addWeek()->toDateString(),
        ]);

        $this->get(route('careers.show', $job->slug))
            ->assertOk()
            ->assertSee('Share Job');

        $this->post(route('careers.share', $job->slug), [
            'recipient_email' => 'friend@example.org',
        ])->assertRedirect();

        Mail::assertSent(SharedJobOpeningMail::class, function (SharedJobOpeningMail $mail): bool {
            return $mail->hasTo('friend@example.org')
                && $mail->jobOpening->title === 'Data Analyst';
        });
    }

    public function test_hidden_public_jobs_cannot_be_shared_by_email(): void
    {
        Mail::fake();

        $job = $this->jobOpening([
            'visibility' => JobOpening::VISIBILITY_INTERNAL,
            'status' => JobOpening::STATUS_PUBLISHED,
            'closing_date' => now()->addWeek()->toDateString(),
        ]);

        $this->post(route('careers.share', $job->slug), [
            'recipient_email' => 'friend@example.org',
        ])->assertNotFound();

        Mail::assertNothingSent();
    }

    public function test_public_api_returns_only_public_safe_jobs_and_hides_position_count_when_disabled(): void
    {
        $job = $this->jobOpening([
            'visibility' => JobOpening::VISIBILITY_BOTH,
            'status' => JobOpening::STATUS_PUBLISHED,
            'show_number_of_positions' => false,
        ]);
        $this->jobOpening(['title' => 'Hidden Internal', 'visibility' => JobOpening::VISIBILITY_INTERNAL, 'status' => JobOpening::STATUS_PUBLISHED]);

        $this->getJson(route('api.careers.jobs.index'))
            ->assertOk()
            ->assertJsonFragment(['reference_no' => $job->reference_no])
            ->assertJsonMissing(['title' => 'Hidden Internal']);

        $this->getJson(route('api.careers.jobs.show', $job->slug))
            ->assertOk()
            ->assertJsonFragment(['reference_no' => $job->reference_no])
            ->assertJsonMissingPath('created_by')
            ->assertJsonMissingPath('number_of_positions');
    }

    public function test_auto_close_command_closes_expired_published_jobs(): void
    {
        $expired = $this->jobOpening([
            'reference_no' => 'JOB-2026-0100',
            'visibility' => JobOpening::VISIBILITY_EXTERNAL,
            'status' => JobOpening::STATUS_PUBLISHED,
            'closing_date' => now()->subDay()->toDateString(),
        ]);
        $open = $this->jobOpening([
            'reference_no' => 'JOB-2026-0101',
            'visibility' => JobOpening::VISIBILITY_EXTERNAL,
            'status' => JobOpening::STATUS_PUBLISHED,
            'closing_date' => now()->addDay()->toDateString(),
        ]);

        $this->artisan('recruitment:close-expired-jobs')
            ->expectsOutput('Closed 1 expired recruitment job(s).')
            ->assertExitCode(0);

        $this->assertSame(JobOpening::STATUS_CLOSED, $expired->fresh()->status);
        $this->assertNotNull($expired->fresh()->closed_at);
        $this->assertSame(JobOpening::STATUS_PUBLISHED, $open->fresh()->status);
        $this->assertDatabaseHas('activity_logs', ['action' => 'job_opening_auto_closed']);
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
            'employment_type_id' => $this->employmentType->id,
            'visibility' => JobOpening::VISIBILITY_EXTERNAL,
            'status' => JobOpening::STATUS_PUBLISHED,
            'number_of_positions' => 2,
            'show_number_of_positions' => true,
            'contract_duration' => '12 months',
            'job_grade' => 'C2',
            'reporting_to_job_title_id' => $this->jobTitle->id,
            'reporting_to_tba' => false,
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
    private function applicationFor(JobOpening $jobOpening, array $attributes = []): JobApplication
    {
        return JobApplication::create(array_merge([
            'reference_no' => fake()->unique()->bothify('APP-2026-####'),
            'job_opening_id' => $jobOpening->id,
            'advertisement_round' => 1,
            'source' => JobApplication::SOURCE_EXTERNAL,
            'status' => JobApplication::STATUS_SUBMITTED,
            'first_name' => 'Previous',
            'last_name' => 'Applicant',
            'email' => fake()->unique()->safeEmail(),
            'phone' => '0977000000',
            'highest_qualification' => "Bachelor's Degree",
            'submitted_at' => now()->subMonth(),
        ], $attributes));
    }

    /**
     * @param array<string, mixed> $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Data Analyst',
            'job_title_id' => $this->jobTitle->id,
            'project_id' => $this->project->id,
            'department_id' => $this->department->id,
            'province_id' => $this->northern->id,
            'district_id' => $this->kasama->id,
            'facility_id' => $this->kasamaFacility->id,
            'employment_type_id' => $this->employmentType->id,
            'visibility' => JobOpening::VISIBILITY_EXTERNAL,
            'status' => JobOpening::STATUS_DRAFT,
            'number_of_positions' => 1,
            'show_number_of_positions' => 1,
            'contract_duration' => '12 months',
            'job_grade' => 'C2',
            'reporting_to_job_title_id' => $this->jobTitle->id,
            'opening_date' => now()->toDateString(),
            'closing_date' => now()->addWeeks(2)->toDateString(),
            'summary' => 'Short job summary.',
            'description' => 'Role description.',
            'responsibilities' => 'Key responsibilities.',
            'requirements' => 'Role requirements.',
            'qualifications' => 'Required qualifications.',
            'experience_required' => 'Relevant experience.',
            'contract_details' => 'Fixed term.',
            'work_level' => 'Officer.',
            'location_details' => 'Kasama.',
            'application_instructions' => 'Submit application through HR.',
        ], $overrides);
    }
}
