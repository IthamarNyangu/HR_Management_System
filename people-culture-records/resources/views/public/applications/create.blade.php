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
        .file-upload-drop {
            align-items: center;
            background: #f9fafb;
            border: 1px dashed #b8c7dd;
            border-radius: .5rem;
            cursor: pointer;
            display: flex;
            gap: .75rem;
            min-height: 72px;
            padding: .75rem;
            position: relative;
            transition: border-color .15s ease, background-color .15s ease;
        }
        .file-upload-drop:hover,
        .file-upload-drop:focus-within {
            background: #f3f6fb;
            border-color: #c01818;
        }
        .file-upload-input {
            cursor: pointer;
            height: 100%;
            inset: 0;
            opacity: 0;
            position: absolute;
            width: 100%;
        }
        .file-upload-icon {
            align-items: center;
            background: #fff;
            border: 1px solid #e1e7f0;
            border-radius: .45rem;
            color: #c01818;
            display: inline-flex;
            flex: 0 0 42px;
            height: 42px;
            justify-content: center;
            width: 42px;
        }
        .file-upload-list {
            display: flex;
            flex-direction: column;
            gap: .5rem;
            margin-top: .65rem;
            max-width: 100%;
        }
        .attached-file {
            align-items: center;
            background: #f8fafc;
            border: 1px solid #dbe3ec;
            border-radius: .45rem;
            display: flex;
            gap: .55rem;
            justify-content: space-between;
            min-width: 0;
            padding: .45rem .55rem;
            width: 100%;
        }
        .attached-file > div:first-child {
            flex: 1 1 auto;
            min-width: 0;
        }
        .attached-file-name {
            flex: 1 1 auto;
            min-width: 0;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .attached-file-meta {
            color: #64748b;
            font-size: .78rem;
            white-space: nowrap;
        }
        .attached-file-remove {
            align-items: center;
            background: transparent;
            border: 0;
            border-radius: .25rem;
            color: #334155;
            display: inline-flex;
            flex: 0 0 auto;
            font-size: 1.2rem;
            height: 28px;
            justify-content: center;
            line-height: 1;
            padding: 0;
            width: 28px;
        }
        .attached-file-remove:hover,
        .attached-file-remove:focus {
            background: #fee2e2;
            color: #b91c1c;
        }
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
           <h1 class="h2 mb-3" style="text-align: center;">Application for {{ $jobOpening->title }}</h1>
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
                <div class="fw-semibold mb-2">Your application was not submitted. Please correct the following:</div>
                <ul class="mb-2 ps-3">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <div class="small">For security reasons, uploaded files must be selected again after validation errors.</div>
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
                        <select id="highest_qualification" name="highest_qualification" class="form-select @error('highest_qualification') is-invalid @enderror" required>
                            <option value="">Select highest qualification</option>
                            @foreach (App\Models\JobApplication::HIGHEST_QUALIFICATIONS as $qualification)
                                <option value="{{ $qualification }}" @selected(old('highest_qualification') === $qualification)>{{ $qualification }}</option>
                            @endforeach
                        </select>
                        @error('highest_qualification') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="field_of_study">Field of Study</label>
                        <input id="field_of_study" type="text" name="field_of_study" value="{{ old('field_of_study') }}" class="form-control @error('field_of_study') is-invalid @enderror">
                        @error('field_of_study') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-2">
                        <label class="form-label" for="years_of_experience">Years of Experience</label>
                        <input id="years_of_experience" type="number" step="1" min="0" name="years_of_experience" value="{{ old('years_of_experience') }}" class="form-control @error('years_of_experience') is-invalid @enderror">
                        @error('years_of_experience') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                
                </div>
            </section>

            <section class="mb-4">
                <h2 class="section-title">Motivation</h2>
                <label class="form-label required" for="motivation">Why are you interested in this role?</label>
                <textarea id="motivation" name="motivation" rows="5" maxlength="2000" class="form-control @error('motivation') is-invalid @enderror" required>{{ old('motivation') }}</textarea>
                <div class="form-text d-flex justify-content-between gap-3">
                    <span>Maximum 2000 characters including spaces.</span>
                    <span id="motivationCounter">0 / 2000</span>
                </div>
                @error('motivation') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </section>

            <section class="mb-4">
                <h2 class="section-title">Documents</h2>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label required" for="cv">CV</label>
                        <div class="file-upload" data-file-upload="single">
                            <div class="file-upload-drop">
                                <input id="cv" type="file" name="cv" class="file-upload-input @error('cv') is-invalid @enderror" accept=".pdf,.doc,.docx" required>
                                <span class="file-upload-icon" aria-hidden="true">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                        <path d="M17 8l-5-5-5 5"></path>
                                        <path d="M12 3v12"></path>
                                    </svg>
                                </span>
                                <span>
                                    <span class="fw-semibold d-block">Choose or drop CV</span>
                                    <span class="small text-muted">PDF, DOC, or DOCX</span>
                                </span>
                            </div>
                            <div class="file-upload-list" data-file-list></div>
                        </div>
                        <div class="form-text">Maximum allowed file size: 5 MB.</div>
                        @error('cv') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label required" for="cover_letter">Cover Letter</label>
                        <div class="file-upload" data-file-upload="single">
                            <div class="file-upload-drop">
                                <input id="cover_letter" type="file" name="cover_letter" class="file-upload-input @error('cover_letter') is-invalid @enderror" accept=".pdf,.doc,.docx" required>
                                <span class="file-upload-icon" aria-hidden="true">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                        <path d="M17 8l-5-5-5 5"></path>
                                        <path d="M12 3v12"></path>
                                    </svg>
                                </span>
                                <span>
                                    <span class="fw-semibold d-block">Choose or drop cover letter</span>
                                    <span class="small text-muted">PDF, DOC, or DOCX</span>
                                </span>
                            </div>
                            <div class="file-upload-list" data-file-list></div>
                        </div>
                        <div class="form-text">Maximum allowed file size: 5 MB.</div>
                        @error('cover_letter') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label required" for="education_certificates">Education Certificates</label>
                        <div class="file-upload" data-file-upload="single">
                            <div class="file-upload-drop">
                                <input id="education_certificates" type="file" name="education_certificates" class="file-upload-input @error('education_certificates') is-invalid @enderror" accept=".pdf" required>
                                <span class="file-upload-icon" aria-hidden="true">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                        <path d="M17 8l-5-5-5 5"></path>
                                        <path d="M12 3v12"></path>
                                    </svg>
                                </span>
                                <span>
                                    <span class="fw-semibold d-block">Choose or drop certificates</span>
                                    <span class="small text-muted">One combined PDF</span>
                                </span>
                            </div>
                            <div class="file-upload-list" data-file-list></div>
                        </div>
                        <div class="form-text">Maximum allowed file size: 10 MB.</div>
                        @error('education_certificates') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="supporting_documents">Additional Supporting Documents</label>
                        <div class="file-upload" data-file-upload="multiple" data-max-files="4">
                            <div class="file-upload-drop">
                                <input id="supporting_documents" type="file" name="supporting_documents[]" multiple class="file-upload-input @error('supporting_documents') is-invalid @enderror @error('supporting_documents.*') is-invalid @enderror" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                                <span class="file-upload-icon" aria-hidden="true">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                        <path d="M17 8l-5-5-5 5"></path>
                                        <path d="M12 3v12"></path>
                                    </svg>
                                </span>
                                <span>
                                    <span class="fw-semibold d-block">Add supporting files</span>
                                    <span class="small text-muted">Attach up to 4 files, one by one</span>
                                </span>
                            </div>
                            <div class="file-upload-list" data-file-list></div>
                            <div class="small text-danger mt-2 d-none" data-file-warning></div>
                        </div>
                        <div class="form-text">Optional. Maximum allowed file size: 10 MB each.</div>
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
            const motivation = document.getElementById('motivation');
            const motivationCounter = document.getElementById('motivationCounter');
            const formatBytes = (bytes) => {
                if (!bytes) {
                    return '0 KB';
                }

                const units = ['bytes', 'KB', 'MB', 'GB'];
                const index = Math.min(Math.floor(Math.log(bytes) / Math.log(1024)), units.length - 1);
                const value = bytes / Math.pow(1024, index);

                return `${value.toFixed(index === 0 ? 0 : 1)} ${units[index]}`;
            };

            const syncInputFiles = (input, files) => {
                const dataTransfer = new DataTransfer();
                files.forEach((file) => dataTransfer.items.add(file));
                input.files = dataTransfer.files;
            };
            const escapeHtml = (value) => {
                const node = document.createElement('div');
                node.textContent = value;

                return node.innerHTML;
            };

            document.querySelectorAll('[data-file-upload]').forEach((wrapper) => {
                const input = wrapper.querySelector('input[type="file"]');
                const list = wrapper.querySelector('[data-file-list]');
                const warning = wrapper.querySelector('[data-file-warning]');
                const isMultiple = wrapper.dataset.fileUpload === 'multiple';
                const maxFiles = Number(wrapper.dataset.maxFiles || 1);
                let files = [];

                const showWarning = (message) => {
                    if (!warning) {
                        return;
                    }

                    warning.textContent = message;
                    warning.classList.toggle('d-none', !message);
                };

                const renderFiles = () => {
                    list.innerHTML = '';

                    files.forEach((file, index) => {
                        const item = document.createElement('div');
                        item.className = 'attached-file';
                        const fileName = escapeHtml(file.name);
                        item.innerHTML = `
                            <div class="d-flex align-items-center gap-2 min-w-0">
                                <span class="text-primary flex-shrink-0" aria-hidden="true">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
                                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                        <path d="M14 2v6h6" fill="#f8fafc"></path>
                                    </svg>
                                </span>
                                <span class="attached-file-name fw-semibold">${fileName}</span>
                                <span class="attached-file-meta">${formatBytes(file.size)}</span>
                            </div>
                            <button type="button" class="attached-file-remove" data-remove-file="${index}" aria-label="Remove ${fileName}">&times;</button>
                        `;
                        list.appendChild(item);
                    });

                    wrapper.querySelectorAll('[data-remove-file]').forEach((button) => {
                        button.addEventListener('click', () => {
                            files.splice(Number(button.dataset.removeFile), 1);
                            syncInputFiles(input, files);
                            showWarning('');
                            renderFiles();
                        });
                    });
                };

                input.addEventListener('change', () => {
                    showWarning('');
                    const selected = Array.from(input.files || []);

                    if (isMultiple) {
                        const combined = [...files];

                        selected.forEach((file) => {
                            const exists = combined.some((current) => current.name === file.name && current.size === file.size);

                            if (!exists && combined.length < maxFiles) {
                                combined.push(file);
                            }
                        });

                        if (selected.length + files.length > maxFiles) {
                            showWarning(`You can attach up to ${maxFiles} supporting documents.`);
                        }

                        files = combined.slice(0, maxFiles);
                    } else {
                        files = selected.slice(0, 1);
                    }

                    syncInputFiles(input, files);
                    renderFiles();
                });
            });

            const updateSubmitState = () => {
                submit.disabled = !(privacy.value === 'yes' && terms.value === 'yes');
            };

            privacy.addEventListener('change', updateSubmitState);
            terms.addEventListener('change', updateSubmitState);
            updateSubmitState();

            const updateMotivationCounter = () => {
                if (!motivation || !motivationCounter) {
                    return;
                }

                motivationCounter.textContent = `${motivation.value.length} / 2000`;
            };

            motivation?.addEventListener('input', updateMotivationCounter);
            updateMotivationCounter();
        });
    </script>
</body>
</html>
