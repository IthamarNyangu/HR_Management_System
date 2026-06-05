<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Apply - {{ $jobOpening->title }} - Right to Care Zambia</title>
    <link rel="icon" type="image/png" href="{{ asset('images/RTCZ.png') }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f7f8fb; color: #172033; }
        .career-header, .application-card { background: #fff; border: 1px solid #e1e7f0; border-radius: .5rem; }
        .career-logo { width: 46px; height: 46px; object-fit: contain; }
        .section-title { color: #c01818; font-size: 1.05rem; font-weight: 700; margin-bottom: .85rem; }
        .required::after { content: " *"; color: #c01818; }
        .visually-hidden-field { position: absolute; left: -9999px; opacity: 0; }
    </style>
</head>
<body>
    <main class="container py-5">
        <header class="career-header p-4 mb-4">
            <div class="d-flex align-items-center gap-3 mb-4">
                <img src="{{ asset('images/RTCZ.png') }}" alt="Right to Care Zambia" class="career-logo">
                <div>
                    <div class="fw-semibold">Right to Care Zambia</div>
                    <div class="small text-muted">Careers</div>
                </div>
            </div>

            <a href="{{ route('careers.show', $jobOpening->slug) }}" class="small text-decoration-none">&larr; Back to job details</a>
            <div class="small text-danger fw-semibold mt-3">{{ $jobOpening->reference_no }}</div>
            <h1 class="h2 mb-3">Apply for {{ $jobOpening->title }}</h1>
            <div class="row g-3 text-muted">
                <div class="col-md-3"><span class="fw-semibold text-dark">Department:</span> {{ $jobOpening->department?->name ?? '-' }}</div>
                <div class="col-md-3"><span class="fw-semibold text-dark">Location:</span> {{ $jobOpening->location_label }}</div>
                <div class="col-md-3"><span class="fw-semibold text-dark">Closing:</span> {{ $jobOpening->closing_date?->format('d M Y') }}</div>
            </div>
        </header>

        @if ($errors->any())
            <div class="alert alert-danger">
                <div class="fw-semibold">Please correct the highlighted fields before submitting.</div>
            </div>
        @endif

        <form method="POST" action="{{ route('careers.apply.store', $jobOpening->slug) }}" enctype="multipart/form-data" class="application-card p-4">
            @csrf
            <input type="text" name="company_website" value="" tabindex="-1" autocomplete="off" class="visually-hidden-field">

            <section class="mb-4">
                <h2 class="section-title">Job Summary</h2>
                <p class="mb-0 text-muted">{{ $jobOpening->summary ?? 'Complete the form below to submit your application.' }}</p>
            </section>

            <section class="mb-4">
                <h2 class="section-title">Personal Details</h2>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label required" for="first_name">First Name</label>
                        <input id="first_name" type="text" name="first_name" value="{{ old('first_name') }}" class="form-control @error('first_name') is-invalid @enderror">
                        @error('first_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label required" for="last_name">Last Name</label>
                        <input id="last_name" type="text" name="last_name" value="{{ old('last_name') }}" class="form-control @error('last_name') is-invalid @enderror">
                        @error('last_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label required" for="email">Email</label>
                        <input id="email" type="email" name="email" value="{{ old('email') }}" class="form-control @error('email') is-invalid @enderror">
                        @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label required" for="phone">Phone</label>
                        <input id="phone" type="text" name="phone" value="{{ old('phone') }}" class="form-control @error('phone') is-invalid @enderror">
                        @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="national_id">National ID</label>
                        <input id="national_id" type="text" name="national_id" value="{{ old('national_id') }}" class="form-control @error('national_id') is-invalid @enderror">
                        @error('national_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="province">Province</label>
                        <input id="province" type="text" name="province" value="{{ old('province') }}" class="form-control @error('province') is-invalid @enderror">
                        @error('province') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="district">District</label>
                        <input id="district" type="text" name="district" value="{{ old('district') }}" class="form-control @error('district') is-invalid @enderror">
                        @error('district') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>
            </section>

            <section class="mb-4">
                <h2 class="section-title">Education and Experience</h2>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label required" for="highest_qualification">Highest Qualification</label>
                        <input id="highest_qualification" type="text" name="highest_qualification" value="{{ old('highest_qualification') }}" class="form-control @error('highest_qualification') is-invalid @enderror">
                        @error('highest_qualification') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="field_of_study">Field of Study</label>
                        <input id="field_of_study" type="text" name="field_of_study" value="{{ old('field_of_study') }}" class="form-control @error('field_of_study') is-invalid @enderror">
                        @error('field_of_study') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="years_of_experience">Years of Experience</label>
                        <input id="years_of_experience" type="number" step="0.1" min="0" name="years_of_experience" value="{{ old('years_of_experience') }}" class="form-control @error('years_of_experience') is-invalid @enderror">
                        @error('years_of_experience') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="current_employer">Current Employer</label>
                        <input id="current_employer" type="text" name="current_employer" value="{{ old('current_employer') }}" class="form-control @error('current_employer') is-invalid @enderror">
                        @error('current_employer') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>
            </section>

            <section class="mb-4">
                <h2 class="section-title">Motivation</h2>
                <label class="form-label required" for="motivation">Why are you interested in this role?</label>
                <textarea id="motivation" name="motivation" rows="5" class="form-control @error('motivation') is-invalid @enderror">{{ old('motivation') }}</textarea>
                @error('motivation') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </section>

            <section class="mb-4">
                <h2 class="section-title">Documents</h2>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label required" for="cv">CV</label>
                        <input id="cv" type="file" name="cv" class="form-control @error('cv') is-invalid @enderror" accept=".pdf,.doc,.docx">
                        <div class="form-text">PDF, DOC, or DOCX. Maximum 10 MB.</div>
                        @error('cv') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label required" for="cover_letter">Cover Letter</label>
                        <input id="cover_letter" type="file" name="cover_letter" class="form-control @error('cover_letter') is-invalid @enderror" accept=".pdf,.doc,.docx">
                        <div class="form-text">PDF, DOC, or DOCX. Maximum 10 MB.</div>
                        @error('cover_letter') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label required" for="education_certificates">Education Certificates</label>
                        <input id="education_certificates" type="file" name="education_certificates" class="form-control @error('education_certificates') is-invalid @enderror" accept=".pdf">
                        <div class="form-text">One combined PDF. Maximum 10 MB.</div>
                        @error('education_certificates') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-12">
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
                <div class="form-check">
                    <input id="consent" type="checkbox" name="consent" value="1" class="form-check-input @error('consent') is-invalid @enderror" @checked(old('consent'))>
                    <label for="consent" class="form-check-label required">I confirm that the information provided is accurate and consent to Right to Care Zambia processing my application documents for recruitment purposes.</label>
                    @error('consent') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </section>

            <div class="border-top pt-4 d-flex flex-wrap justify-content-between gap-3">
                <a href="{{ route('applications.withdraw.request') }}" class="text-decoration-none">Need to withdraw an application?</a>
                <div class="d-flex gap-2">
                    <a href="{{ route('careers.show', $jobOpening->slug) }}" class="btn btn-outline-secondary">Cancel</a>
                    <button type="submit" class="btn btn-danger">Submit Application</button>
                </div>
            </div>
        </form>
    </main>
</body>
</html>
