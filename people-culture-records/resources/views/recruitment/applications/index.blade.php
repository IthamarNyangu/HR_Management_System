@extends('layouts.app')

@section('title', 'Vacancy Applications')
@section('page-title', 'Vacancy Applications')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('recruitment.index') }}">Recruitment</a></li>
    <li class="breadcrumb-item active" aria-current="page">Vacancy Applications</li>
@endsection

@section('content')
    <div class="bg-white border rounded-2 p-3 mb-4">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-lg-3">
                <input type="search" name="search" value="{{ request('search') }}" class="form-control" placeholder="Search reference, applicant, email, or job">
            </div>
            <div class="col-lg-3">
                <select name="job_opening_id" class="form-select">
                    <option value="">All job openings</option>
                    @foreach ($jobOpenings as $jobOpening)
                        <option value="{{ $jobOpening->id }}" @selected(request('job_opening_id') == $jobOpening->id)>
                            {{ $jobOpening->reference_no }} - {{ $jobOpening->title }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-lg-2">
                <select name="status" class="form-select">
                    <option value="">All statuses</option>
                    @foreach (App\Models\JobApplication::statusLabels() as $value => $label)
                        <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-lg-2">
                <select name="visibility" class="form-select">
                    <option value="">All visibility</option>
                    @foreach (App\Models\JobOpening::VISIBILITIES as $visibility)
                        <option value="{{ $visibility }}" @selected(request('visibility') === $visibility)>{{ str($visibility)->headline() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-lg-2">
                <select name="province_id" class="form-select">
                    <option value="">All provinces</option>
                    @foreach ($provinces as $province)
                        <option value="{{ $province->id }}" @selected(request('province_id') == $province->id)>{{ $province->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-lg-2">
                <select name="district_id" class="form-select">
                    <option value="">All districts</option>
                    @foreach ($districts as $district)
                        <option value="{{ $district->id }}" @selected(request('district_id') == $district->id)>{{ $district->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-lg-2">
                <select name="facility_id" class="form-select">
                    <option value="">Facilities</option>
                    @foreach ($facilities as $facility)
                        <option value="{{ $facility->id }}" @selected(request('facility_id') == $facility->id)>{{ $facility->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-lg-3">
                <select name="highest_qualification" class="form-select">
                    <option value="">All qualifications</option>
                    @foreach (App\Models\JobApplication::HIGHEST_QUALIFICATIONS as $qualification)
                        <option value="{{ $qualification }}" @selected(request('highest_qualification') === $qualification)>{{ $qualification }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-lg-2">
                <input type="number" step="1" min="0" name="experience_min" value="{{ request('experience_min') }}" class="form-control" placeholder="Min experience">
            </div>
            <div class="col-lg-2">
                <input type="number" step="1" min="0" name="experience_max" value="{{ request('experience_max') }}" class="form-control" placeholder="Max experience">
            </div>
            <div class="col-lg-2">
                <input type="date" name="submitted_from" value="{{ request('submitted_from') }}" class="form-control" title="Submitted from">
            </div>
            <div class="col-lg-2">
                <input type="date" name="submitted_to" value="{{ request('submitted_to') }}" class="form-control" title="Submitted to">
            </div>
            <div class="col-lg-2">
                <input type="number" step="1" min="0" max="100" name="score_min" value="{{ request('score_min') }}" class="form-control" placeholder="Min score">
            </div>
            <div class="col-lg-2">
                <input type="number" step="1" min="0" max="100" name="score_max" value="{{ request('score_max') }}" class="form-control" placeholder="Max score">
            </div>
            <div class="col-lg-3">
                <select name="sort" class="form-select">
                    <option value="">Submitted newest</option>
                    <option value="submitted_oldest" @selected(request('sort') === 'submitted_oldest')>Submitted oldest</option>
                    <option value="score_desc" @selected(request('sort') === 'score_desc')>Overall score highest</option>
                    <option value="status" @selected(request('sort') === 'status')>Status</option>
                    <option value="job_title" @selected(request('sort') === 'job_title')>Job title</option>
                </select>
            </div>
            <div class="col-lg-4 d-flex flex-wrap align-items-center gap-3">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="has_education_certificate" value="1" id="has_education_certificate" @checked(request()->boolean('has_education_certificate'))>
                    <label class="form-check-label" for="has_education_certificate">Has certificates</label>
                </div>
            </div>
            <div class="col-lg-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary-outline btn-md">Filter</button>
                <a href="{{ route('recruitment.applications.index') }}" class="btn btn-secondary btn-md">Reset</a>
            </div>
        </form>

        <div class="d-flex flex-wrap gap-2 mt-3">
            <a href="{{ route('recruitment.applications.index', array_merge(request()->except('quick', 'status', 'page'), ['quick' => App\Models\JobApplication::STATUS_REJECTED])) }}" class="btn btn-sm {{ request('quick') === App\Models\JobApplication::STATUS_REJECTED ? 'btn-primary' : 'btn-secondary' }}">Rejected only</a>
            <a href="{{ route('recruitment.applications.index', array_merge(request()->except('quick', 'status', 'page'), ['quick' => App\Models\JobApplication::STATUS_WITHDRAWN])) }}" class="btn btn-sm {{ request('quick') === App\Models\JobApplication::STATUS_WITHDRAWN ? 'btn-primary' : 'btn-secondary' }}">Withdrawn only</a>
        </div>
    </div>

    <section class="bg-white border rounded-2 p-3">
        <div class="d-flex justify-content-between align-items-center gap-3 mb-3">
            <div>
                <h2 class="h5 mb-1">Vacancy Applications</h2>
                <div class="text-muted">{{ $applications->total() }} record(s) found.</div>
            </div>
        </div>

        <div class="table-responsive data-table-wrap">
            <table class="table table-hover align-middle data-table">
                <thead>
                    <tr>
                        <th>Reference</th>
                        <th>Applicant</th>
                        <th>Email</th>
                        <th>Job</th>
                        <th>Location</th>
                        <th>Status</th>
                        <th>Score</th>
                        <th>Submitted</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($applications as $application)
                        <tr>
                            <td><a href="{{ route('recruitment.applications.show', $application) }}">{{ $application->reference_no }}</a></td>
                            <td>{{ $application->full_name }}</td>
                            <td>{{ $application->email }}</td>
                            <td>
                                <div class="fw-semibold">{{ $application->jobOpening?->title }}</div>
                                <div class="small text-muted">{{ $application->jobOpening?->reference_no }}</div>
                            </td>
                            <td>{{ $application->jobOpening?->location_label }}</td>
                            <td>
                                <span class="badge text-bg-{{ $application->status_badge_class }}">
                                    {{ $application->status_label }}
                                </span>
                            </td>
                            <td>{{ $application->overall_score ?? '-' }}</td>
                            <td>{{ $application->submitted_at?->format('d M Y H:i') }}</td>
                            <td>
                                <a href="{{ route('recruitment.applications.show', $application) }}" class="btn btn-sm btn-secondary">View</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center text-muted py-4">No applications found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-3">
            {{ $applications->links() }}
        </div>
    </section>
@endsection
