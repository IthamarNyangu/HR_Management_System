<?php

namespace Tests\Feature;

use App\Mail\JobApplicationReceivedMail;
use App\Mail\JobApplicationRejectedMail;
use App\Mail\JobApplicationWithdrawnMail;
use App\Mail\JobApplicationWithdrawalLinkMail;
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
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class PublicJobApplicationTest extends TestCase
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

    public function test_application_form_only_loads_for_public_applyable_jobs(): void
    {
        $job = $this->jobOpening();

        $this->get(route('careers.apply', $job->slug))
            ->assertOk()
            ->assertSee('Submit Application');

        foreach ([
            ['visibility' => JobOpening::VISIBILITY_INTERNAL],
            ['status' => JobOpening::STATUS_DRAFT],
            ['status' => JobOpening::STATUS_CLOSED],
            ['status' => JobOpening::STATUS_CANCELLED],
            ['closing_date' => now()->subDay()->toDateString()],
        ] as $attributes) {
            $hidden = $this->jobOpening($attributes);

            $this->get(route('careers.apply', $hidden->slug))->assertNotFound();
        }
    }

    public function test_public_application_requires_documents_and_pdf_education_certificate(): void
    {
        $job = $this->jobOpening();

        $this->post(route('careers.apply.store', $job->slug), $this->payload([
            'cv' => null,
            'cover_letter' => null,
            'education_certificates' => UploadedFile::fake()->create('certificates.docx', 20),
        ]))
            ->assertSessionHasErrors(['cv', 'cover_letter', 'education_certificates']);
    }

    public function test_supporting_documents_are_limited_to_four_files(): void
    {
        $job = $this->jobOpening();

        $payload = $this->payload([
            'supporting_documents' => [
                UploadedFile::fake()->create('one.pdf', 20),
                UploadedFile::fake()->create('two.pdf', 20),
                UploadedFile::fake()->create('three.pdf', 20),
                UploadedFile::fake()->create('four.pdf', 20),
                UploadedFile::fake()->create('five.pdf', 20),
            ],
        ]);

        $this->post(route('careers.apply.store', $job->slug), $payload)
            ->assertSessionHasErrors(['supporting_documents']);
    }

    public function test_motivation_must_not_exceed_two_thousand_characters(): void
    {
        $job = $this->jobOpening();

        $this->post(route('careers.apply.store', $job->slug), $this->payload([
            'motivation' => str_repeat('A', 2001),
        ]))
            ->assertSessionHasErrors(['motivation']);
    }

    public function test_application_submission_saves_records_documents_and_sends_confirmation_email(): void
    {
        Storage::fake('local');
        Mail::fake();
        $job = $this->jobOpening();

        $this->post(route('careers.apply.store', $job->slug), $this->payload([
            'email' => 'Applicant@Example.ORG',
        ]))
            ->assertOk()
            ->assertSee('Application submitted');

        $application = JobApplication::firstOrFail();

        $this->assertSame('applicant@example.org', $application->email);
        $this->assertSame('Miss', $application->title);
        $this->assertSame('Female', $application->gender);
        $this->assertSame('No', $application->disability);
        $this->assertSame('APP-'.now()->year.'-0001', $application->reference_no);
        $this->assertCount(3, $application->documents);
        Storage::disk('local')->assertExists($application->documents()->firstOrFail()->file_path);
        Mail::assertSent(JobApplicationReceivedMail::class);
        $this->assertDatabaseHas('activity_logs', ['action' => 'job_application_submitted']);
    }

    public function test_duplicate_email_is_blocked_for_same_job_but_allowed_for_different_jobs(): void
    {
        Mail::fake();
        Storage::fake('local');
        $job = $this->jobOpening();
        $otherJob = $this->jobOpening(['reference_no' => 'JOB-2026-0999', 'slug' => 'other-job-2026-0999']);

        $this->post(route('careers.apply.store', $job->slug), $this->payload(['email' => 'same@example.org']))->assertOk();
        $this->post(route('careers.apply.store', $job->slug), $this->payload(['email' => 'SAME@example.org']))
            ->assertSessionHasErrors(['email']);
        $this->post(route('careers.apply.store', $otherJob->slug), $this->payload(['email' => 'same@example.org']))->assertOk();

        $this->assertSame(2, JobApplication::count());

        JobApplication::where('job_opening_id', $job->id)
            ->where('email', 'same@example.org')
            ->firstOrFail()
            ->update([
                'status' => JobApplication::STATUS_WITHDRAWN,
                'withdrawn_at' => now(),
            ]);

        $this->post(route('careers.apply.store', $job->slug), $this->payload(['email' => 'same@example.org']))->assertOk();
        $this->assertSame(3, JobApplication::count());
    }

    public function test_signed_withdrawal_link_withdraws_application(): void
    {
        Mail::fake();
        $application = $this->application();
        $token = 'withdraw-token';
        $application->update(['withdrawal_token_hash' => hash('sha256', $token)]);

        $this->get(URL::signedRoute('applications.withdraw.show', ['jobApplication' => $application, 'token' => $token]))
            ->assertOk()
            ->assertSee('Withdraw application?');

        $this->post(URL::signedRoute('applications.withdraw.confirm', ['jobApplication' => $application, 'token' => $token]))
            ->assertOk()
            ->assertSee('Application withdrawn');

        $this->assertSame(JobApplication::STATUS_WITHDRAWN, $application->fresh()->status);
        $this->assertNotNull($application->fresh()->withdrawn_at);
        Mail::assertSent(JobApplicationWithdrawnMail::class);
    }

    public function test_fallback_withdrawal_request_returns_generic_response_and_sends_when_matched(): void
    {
        Mail::fake();
        $application = $this->application();

        $this->post(route('applications.withdraw.link'), [
            'reference_no' => $application->reference_no,
            'email' => strtoupper($application->email),
        ])
            ->assertRedirect()
            ->assertSessionHas('success');

        Mail::assertSent(JobApplicationWithdrawalLinkMail::class);

        $this->post(route('applications.withdraw.link'), [
            'reference_no' => 'APP-2026-4040',
            'email' => 'missing@example.org',
        ])
            ->assertRedirect()
            ->assertSessionHas('success');
    }

    public function test_internal_application_list_and_download_respect_province_visibility(): void
    {
        Storage::fake('local');
        $assigned = $this->application();
        $outsideJob = $this->jobOpening([
            'reference_no' => 'JOB-2026-0200',
            'slug' => 'outside-job-2026-0200',
            'province_id' => $this->luapula->id,
            'district_id' => $this->mansa->id,
            'facility_id' => null,
        ]);
        $outside = $this->application(['job_opening_id' => $outsideJob->id, 'reference_no' => 'APP-2026-0200']);
        $document = $assigned->documents()->create([
            'document_type' => JobApplication::DOCUMENT_CV,
            'original_filename' => 'cv.pdf',
            'stored_filename' => 'cv.pdf',
            'file_path' => 'job-applications/testing/cv.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 10,
            'uploaded_at' => now(),
        ]);
        Storage::disk('local')->put($document->file_path, 'document');

        $officer = $this->user($this->officerRole, $this->northern);

        $this->actingAs($officer)
            ->get(route('recruitment.applications.index'))
            ->assertOk()
            ->assertSee($assigned->reference_no)
            ->assertDontSee($outside->reference_no);

        $this->actingAs($officer)
            ->get(route('recruitment.applications.show', $outside))
            ->assertForbidden();

        $this->actingAs($officer)
            ->get(route('recruitment.applications.documents.download', [$assigned, $document]))
            ->assertOk();
    }

    public function test_unauthenticated_users_cannot_access_internal_applications(): void
    {
        $this->get(route('recruitment.applications.index'))->assertRedirect('/login');
    }

    public function test_admin_can_reject_application_and_send_rejection_email(): void
    {
        Mail::fake();
        $application = $this->application();
        $admin = $this->user($this->adminRole);

        $this->actingAs($admin)
            ->patch(route('recruitment.applications.reject', $application), [
                'rejection_reason' => 'Does not meet the minimum requirements.',
                'send_email' => '1',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $application->refresh();

        $this->assertSame(JobApplication::STATUS_REJECTED, $application->status);
        $this->assertNotNull($application->rejected_at);
        $this->assertSame('Does not meet the minimum requirements.', $application->rejection_reason);
        $this->assertNotNull($application->outcome_sent_at);
        $this->assertSame($admin->id, $application->outcome_sent_by);
        Mail::assertSent(JobApplicationRejectedMail::class);
        $this->assertDatabaseHas('activity_logs', ['action' => 'job_application_rejection_email_sent']);
        $this->assertDatabaseHas('job_application_status_histories', [
            'job_application_id' => $application->id,
            'from_status' => JobApplication::STATUS_SUBMITTED,
            'to_status' => JobApplication::STATUS_REJECTED,
            'email_sent' => true,
        ]);
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
            'reference_no' => 'APP-2026-0100',
            'job_opening_id' => $this->jobOpening()->id,
            'source' => JobApplication::SOURCE_EXTERNAL,
            'status' => JobApplication::STATUS_SUBMITTED,
            'first_name' => 'Mary',
            'last_name' => 'Banda',
            'email' => 'mary@example.org',
            'phone' => '0977000000',
            'highest_qualification' => "Bachelor's Degree",
            'motivation' => 'I am interested in the role.',
            'consent_given_at' => now(),
            'submitted_at' => now(),
        ], $attributes));
    }

    /**
     * @param array<string, mixed> $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Miss',
            'first_name' => 'Mary',
            'last_name' => 'Banda',
            'email' => 'mary@example.org',
            'phone' => '0977000000',
            'national_id' => '123456/10/1',
            'gender' => 'Female',
            'disability' => 'No',
            'highest_qualification' => "Bachelor's Degree",
            'field_of_study' => 'Public Health',
            'years_of_experience' => 4,
            'current_employer' => 'Example Org',
            'motivation' => 'I am interested in this role.',
            'cv' => UploadedFile::fake()->create('cv.pdf', 20),
            'cover_letter' => UploadedFile::fake()->create('cover-letter.docx', 20),
            'education_certificates' => UploadedFile::fake()->create('certificates.pdf', 20),
            'privacy_consent' => 'yes',
            'terms_confirmed' => 'yes',
        ], $overrides);
    }
}
