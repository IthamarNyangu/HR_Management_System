<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Apply - {{ $jobOpening->title }} - Right to Care Zambia</title>
    <link rel="icon" type="image/png" href="{{ asset('images/right-to-care-zambia-logo-transparent.png') }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f7f8fb; color: #172033; }
        .application-card { background: #fff; border: 1px solid #e1e7f0; border-radius: .5rem; }
        .career-logo { width: 82px; height: 82px; object-fit: contain; }
        .section-title { color: #c01818; font-size: 1.05rem; font-weight: 700; margin-bottom: .85rem; }
        .required::after { content: " *"; color: #c01818; }
        .visually-hidden-field { position: absolute; left: -9999px; opacity: 0; }
        .consent-panel { background: #f9fafb; border: 1px solid #e1e7f0; border-radius: .5rem; }
    </style>
</head>
<body>
    <main class="container py-5">
        <div class="d-flex justify-content-between align-items-start gap-3 mb-4">
            <img src="{{ asset('images/right-to-care-zambia-logo-transparent.png') }}" alt="Right to Care Zambia" class="career-logo">
            <a href="{{ route('careers.show', $jobOpening->slug) }}" class="btn btn-outline-secondary btn-sm">Back to details</a>
        </div>

        <div class="application-card p-4 mb-4">
            <div class="small text-danger fw-semibold">{{ $jobOpening->reference_no }}</div>
            <h1 class="h2 mb-3">Apply for {{ $jobOpening->title }}</h1>
            <div class="d-flex flex-wrap align-items-center gap-2 text-muted">
                <span>Closing: {{ $jobOpening->closing_date?->format('d M Y') }}</span>
                <span>&middot;</span>
                <span>{{ $jobOpening->public_location_label }}</span>
                <span>&middot;</span>
                <a href="{{ route('careers.announcement.pdf', $jobOpening->slug) }}" class="text-decoration-none">Download job announcement PDF</a>
            </div>
        </div>

        @if ($errors->any())
            <div class="alert alert-danger">
                <div class="fw-semibold">Please correct the highlighted fields before submitting.</div>
            </div>
        @endif

        <form method="POST" action="{{ route('careers.apply.store', $jobOpening->slug) }}" enctype="multipart/form-data" class="application-card p-4">
            @csrf
            <input type="text" name="company_website" value="" tabindex="-1" autocomplete="off" class="visually-hidden-field">

            <section class="mb-4">
                <h2 class="section-title">Personal Details</h2>
                <div class="row g-3">
                    <div class="col-md-2">
                        <label class="form-label required" for="title">Title</label>
                        <select id="title" name="title" class="form-select @error('title') is-invalid @enderror" required>
                            <option value="">Select title</option>
                            @foreach (['Mr', 'Mrs', 'Miss', 'Sir', 'Doctor', 'Professor', 'Advocate', 'Judge', 'Pastor', 'Rabbi', 'Reverend'] as $title)
                                <option value="{{ $title }}" @selected(old('title') === $title)>{{ $title }}</option>
                            @endforeach
                        </select>
                        @error('title') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label required" for="first_name">First Name</label>
                        <input id="first_name" type="text" name="first_name" value="{{ old('first_name') }}" class="form-control @error('first_name') is-invalid @enderror" required>
                        @error('first_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label required" for="last_name">Last Name</label>
                        <input id="last_name" type="text" name="last_name" value="{{ old('last_name') }}" class="form-control @error('last_name') is-invalid @enderror" required>
                        @error('last_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-5">
                        <label class="form-label required" for="email">Email</label>
                        <input id="email" type="email" name="email" value="{{ old('email') }}" class="form-control @error('email') is-invalid @enderror" required>
                        @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-5">
                        <label class="form-label required" for="phone">Phone</label>
                        <input id="phone" type="tel" inputmode="numeric" pattern="[0-9]+" name="phone" value="{{ old('phone') }}" class="form-control @error('phone') is-invalid @enderror" required>
                        @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="national_id">ID / Passport / Visa Number</label>
                        <input id="national_id" type="text" name="national_id" value="{{ old('national_id') }}" class="form-control @error('national_id') is-invalid @enderror">
                        @error('national_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label required" for="gender">Gender</label>
                        <select id="gender" name="gender" class="form-select @error('gender') is-invalid @enderror" required>
                            <option value="">Select gender</option>
                            @foreach (['Male', 'Female', 'Other'] as $gender)
                                <option value="{{ $gender }}" @selected(old('gender') === $gender)>{{ $gender }}</option>
                            @endforeach
                        </select>
                        @error('gender') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label required" for="disability">Disability</label>
                        <select id="disability" name="disability" class="form-select @error('disability') is-invalid @enderror" required>
                            <option value="">Select option</option>
                            @foreach (['No', 'Yes'] as $disability)
                                <option value="{{ $disability }}" @selected(old('disability') === $disability)>{{ $disability }}</option>
                            @endforeach
                        </select>
                        @error('disability') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>
            </section>

            <section class="mb-4">
                <h2 class="section-title">Education and Experience</h2>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label required" for="highest_qualification">Highest Qualification</label>
                        <input id="highest_qualification" type="text" name="highest_qualification" value="{{ old('highest_qualification') }}" class="form-control @error('highest_qualification') is-invalid @enderror" required>
                        @error('highest_qualification') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="field_of_study">Field of Study</label>
                        <input id="field_of_study" type="text" name="field_of_study" value="{{ old('field_of_study') }}" class="form-control @error('field_of_study') is-invalid @enderror">
                        @error('field_of_study') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-2">
                        <label class="form-label" for="years_of_experience">Years of Experience</label>
                        <input id="years_of_experience" type="number" step="0.1" min="0" name="years_of_experience" value="{{ old('years_of_experience') }}" class="form-control @error('years_of_experience') is-invalid @enderror">
                        @error('years_of_experience') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                
                </div>
            </section>

            <section class="mb-4">
                <h2 class="section-title">Motivation</h2>
                <label class="form-label required" for="motivation">Why are you interested in this role?</label>
                <textarea id="motivation" name="motivation" rows="5" class="form-control @error('motivation') is-invalid @enderror" required>{{ old('motivation') }}</textarea>
                @error('motivation') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </section>

            <section class="mb-4">
                <h2 class="section-title">Documents</h2>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label required" for="cv">CV</label>
                        <input id="cv" type="file" name="cv" class="form-control @error('cv') is-invalid @enderror" accept=".pdf,.doc,.docx" required>
                        <div class="form-text">PDF, DOC, or DOCX. Maximum allowed file size: 5 MB.</div>
                        @error('cv') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label required" for="cover_letter">Cover Letter</label>
                        <input id="cover_letter" type="file" name="cover_letter" class="form-control @error('cover_letter') is-invalid @enderror" accept=".pdf,.doc,.docx" required>
                        <div class="form-text">PDF, DOC, or DOCX. Maximum allowed file size: 5 MB.</div>
                        @error('cover_letter') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label required" for="education_certificates">Education Certificates</label>
                        <input id="education_certificates" type="file" name="education_certificates" class="form-control @error('education_certificates') is-invalid @enderror" accept=".pdf" required>
                        <div class="form-text">One combined PDF. Maximum 10 MB.</div>
                        @error('education_certificates') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="supporting_documents">Additional Supporting Documents</label>
                        <input id="supporting_documents" type="file" name="supporting_documents[]" multiple class="form-control @error('supporting_documents') is-invalid @enderror @error('supporting_documents.*') is-invalid @enderror" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                        <div class="form-text">Optional. Up to 4 files, maximum 10 MB each.</div>
                        @error('supporting_documents') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        @error('supporting_documents.*') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>
            </section>

            <section class="mb-4">
                <h2 class="section-title">Consent</h2>
                <div class="row g-3">
                    <div class="col-lg-6">
                        <div class="consent-panel p-3 h-100">
                            <p class="mb-3">
                                Do you consent to the processing of your personal information in accordance with the
                                <button type="button" class="btn btn-link p-0 align-baseline" data-bs-toggle="modal" data-bs-target="#privacyPolicyModal">Privacy Policy</button>
                                of this platform?
                            </p>
                            <select id="privacy_consent" name="privacy_consent" class="form-select @error('privacy_consent') is-invalid @enderror" required>
                                <option value="">Select consent option</option>
                                <option value="yes" @selected(old('privacy_consent') === 'yes')>Yes, I understand, agree and consent</option>
                                <option value="no" @selected(old('privacy_consent') === 'no')>No, I do not understand, agree or consent</option>
                            </select>
                            @error('privacy_consent') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="consent-panel p-3 h-100">
                            <p class="mb-3">
                                Do you confirm that you have read, understood and agree to the
                                <button type="button" class="btn btn-link p-0 align-baseline" data-bs-toggle="modal" data-bs-target="#termsModal">Terms and Conditions of Application and use of this platform</button>?
                            </p>
                            <select id="terms_confirmed" name="terms_confirmed" class="form-select @error('terms_confirmed') is-invalid @enderror" required>
                                <option value="">Select confirmation option</option>
                                <option value="no" @selected(old('terms_confirmed') === 'no')>No, I do not confirm</option>
                                <option value="yes" @selected(old('terms_confirmed') === 'yes')>Yes, I confirm</option>
                            </select>
                            @error('terms_confirmed') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                        </div>
                    </div>
                </div>
            </section>

            <div class="border-top pt-4 d-flex justify-content-end gap-2">
                <a href="{{ route('careers.show', $jobOpening->slug) }}" class="btn btn-outline-secondary">Cancel</a>
                <button id="submitApplicationButton" type="submit" class="btn btn-danger" disabled>Submit Application</button>
            </div>
        </form>
    </main>

    <div class="modal fade" id="privacyPolicyModal" tabindex="-1" aria-labelledby="privacyPolicyTitle" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="modal-title h5" id="privacyPolicyTitle">Privacy Policy</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Right to Care Zambia collects and processes personal information submitted through this careers platform for recruitment, selection, verification, appointment administration, legal compliance, and related People & Culture purposes.</p>
                    <p>Information may include identity details, contact details, qualifications, work history, application documents, disability information where voluntarily provided, and other information needed to assess your application.</p>
                    <p>Your information will be accessed only by authorised Right to Care Zambia personnel and approved service providers who support recruitment or system hosting. We take reasonable administrative, technical, and organisational steps to protect applicant information from unauthorised access, loss, misuse, or disclosure.</p>
                    <p>By submitting an application, you consent to Right to Care Zambia processing your personal information for recruitment purposes, including reference, qualification, identity, and background checks where applicable and lawful.</p>
                    <p>You may contact the People & Culture Department if you need to correct submitted information or ask questions about how your application data is handled.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="termsModal" tabindex="-1" aria-labelledby="termsTitle" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="modal-title h5" id="termsTitle">Terms and Conditions of Application</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>This platform allows applicants to view Right to Care Zambia vacancies and submit applications electronically. You are responsible for ensuring that the information and documents you submit are complete, accurate, current, and truthful.</p>
                    <p>Submitting an application does not guarantee shortlisting, interview, appointment, or employment. Right to Care Zambia may verify submitted information and may withdraw, amend, suspend, or close a vacancy at any time where business or operational needs require it.</p>
                    <p>You must not submit false, misleading, offensive, unlawful, or fraudulent information. Right to Care Zambia may reject or withdraw an application if inaccurate or improper information is discovered.</p>
                    <p>You are responsible for using this platform lawfully and for keeping any application reference or withdrawal link secure. Messages sent through email or the internet cannot be guaranteed to be completely secure.</p>
                    <p>Right to Care Zambia does not charge any fee at any stage of the recruitment process.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const privacy = document.getElementById('privacy_consent');
            const terms = document.getElementById('terms_confirmed');
            const submit = document.getElementById('submitApplicationButton');

            const updateSubmitState = () => {
                submit.disabled = !(privacy.value === 'yes' && terms.value === 'yes');
            };

            privacy.addEventListener('change', updateSubmitState);
            terms.addEventListener('change', updateSubmitState);
            updateSubmitState();
        });
    </script>
</body>
</html>
