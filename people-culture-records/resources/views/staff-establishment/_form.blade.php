@php
    $lineRows = collect(old('lines', $plan->lines->map(fn ($line) => [
        'id' => $line->id,
        'job_title_id' => $line->job_title_id,
        'province_id' => $line->province_id,
        'district_id' => $line->district_id,
        'facility_id' => $line->facility_id,
        'department_id' => $line->department_id,
        'budgeted_positions' => $line->budgeted_positions,
        'notes' => $line->notes,
    ])->values()->all()));

    if ($lineRows->isEmpty()) {
        $lineRows = collect([[
            'id' => null,
            'job_title_id' => null,
            'province_id' => null,
            'district_id' => null,
            'facility_id' => null,
            'department_id' => null,
            'budgeted_positions' => 0,
            'notes' => null,
        ]]);
    }
@endphp

<form method="POST" action="{{ $action }}" data-staff-establishment-form>
    @csrf
    @if (($method ?? 'POST') !== 'POST')
        @method($method)
    @endif

    <div class="d-flex flex-column gap-3">
        <section class="bg-white border rounded-2 p-4">
            <h2 class="h5 mb-3">Plan Details</h2>
            <div class="row g-3">
                <div class="col-lg-6">
                    <label for="title" class="form-label">Plan Title <span class="text-danger">*</span></label>
                    <input id="title" name="title" type="text" class="form-control @error('title') is-invalid @enderror" value="{{ old('title', $plan->title) }}" placeholder="May 2026 Staff Establishment" required>
                    @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6 col-lg-3">
                    <label for="project_id" class="form-label">Project</label>
                    <select id="project_id" name="project_id" class="form-select @error('project_id') is-invalid @enderror">
                        <option value="">All projects</option>
                        @foreach ($projects as $project)
                            <option value="{{ $project->id }}" @selected((string) old('project_id', $plan->project_id) === (string) $project->id)>{{ $project->name }}</option>
                        @endforeach
                    </select>
                    @error('project_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6 col-lg-3">
                    <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                    <select id="status" name="status" class="form-select @error('status') is-invalid @enderror" required>
                        @foreach ($statuses as $status)
                            <option value="{{ $status }}" @selected(old('status', $plan->status) === $status)>{{ str($status)->headline() }}</option>
                        @endforeach
                    </select>
                    @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label for="effective_month" class="form-label">Effective Month <span class="text-danger">*</span></label>
                    <input id="effective_month" name="effective_month" type="month" class="form-control @error('effective_month') is-invalid @enderror" value="{{ old('effective_month', $plan->effective_month?->format('Y-m')) }}" required>
                    @error('effective_month')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-8">
                    <label for="notes" class="form-label">Notes</label>
                    <input id="notes" name="notes" type="text" class="form-control @error('notes') is-invalid @enderror" value="{{ old('notes', $plan->notes) }}" placeholder="Optional note about this establishment version">
                    @error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
        </section>

        <section class="bg-white border rounded-2 p-4">
            <div class="d-flex flex-column flex-lg-row justify-content-between gap-3 mb-3">
                <div>
                    <h2 class="h5 mb-1">Establishment Lines</h2>
                    <p class="text-muted mb-0">Each line defines the approved headcount for a job title and location. Filled and vacancy numbers are calculated from active employee records.</p>
                </div>
                <button type="button" class="btn btn-primary-outline btn-md align-self-start" data-add-establishment-line>Add Line</button>
            </div>

            @error('lines')<div class="alert alert-danger py-2">{{ $message }}</div>@enderror

            <div class="d-flex flex-column gap-3" data-establishment-line-list>
                @foreach ($lineRows as $index => $line)
                    @include('staff-establishment._line-row', [
                        'line' => $line,
                        'index' => $index,
                        'isTemplate' => false,
                    ])
                @endforeach
            </div>

            <template data-establishment-line-template>
                @include('staff-establishment._line-row', [
                    'line' => [
                        'id' => null,
                        'job_title_id' => null,
                        'province_id' => null,
                        'district_id' => null,
                        'facility_id' => null,
                        'department_id' => null,
                        'budgeted_positions' => 0,
                        'notes' => null,
                    ],
                    'index' => '__INDEX__',
                    'isTemplate' => true,
                ])
            </template>
        </section>

        <section class="bg-white border rounded-2 p-3">
            <div class="d-flex flex-wrap gap-2">
                <button type="submit" class="btn btn-primary btn-md">Save Establishment</button>
                <a href="{{ $plan->exists ? route('staff-establishment.show', $plan) : route('staff-establishment.index') }}" class="btn btn-secondary btn-md">Cancel</a>
            </div>
        </section>
    </div>
</form>

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const form = document.querySelector('[data-staff-establishment-form]');

            if (!form) {
                return;
            }

            const list = form.querySelector('[data-establishment-line-list]');
            const template = form.querySelector('[data-establishment-line-template]');
            const addButton = form.querySelector('[data-add-establishment-line]');
            let nextIndex = {{ $lineRows->count() }};

            function enableFields(row) {
                row.querySelectorAll('[disabled]').forEach(function (field) {
                    field.disabled = false;
                });
            }

            function filterLocation(row) {
                const province = row.querySelector('[data-line-province]');
                const district = row.querySelector('[data-line-district]');
                const facility = row.querySelector('[data-line-facility]');
                const provinceId = province?.value || '';
                const districtId = district?.value || '';

                district?.querySelectorAll('option[data-province-id]').forEach(function (option) {
                    const visible = !provinceId || option.dataset.provinceId === provinceId;
                    option.hidden = !visible;
                    option.disabled = !visible;
                });

                if (district?.selectedOptions[0]?.disabled) {
                    district.value = '';
                }

                facility?.querySelectorAll('option[data-district-id]').forEach(function (option) {
                    const activeDistrictId = district?.value || '';
                    const visible = !activeDistrictId || option.dataset.districtId === activeDistrictId;
                    option.hidden = !visible;
                    option.disabled = !visible;
                });

                if (facility?.selectedOptions[0]?.disabled) {
                    facility.value = '';
                }
            }

            function initializeLine(row) {
                row.querySelector('[data-line-province]')?.addEventListener('change', function () {
                    filterLocation(row);
                });
                row.querySelector('[data-line-district]')?.addEventListener('change', function () {
                    filterLocation(row);
                });
                row.querySelector('[data-remove-establishment-line]')?.addEventListener('click', function () {
                    if (list.querySelectorAll('[data-establishment-line]').length === 1) {
                        return;
                    }

                    row.remove();
                });
                filterLocation(row);
            }

            list.querySelectorAll('[data-establishment-line]').forEach(initializeLine);

            addButton?.addEventListener('click', function () {
                const wrapper = document.createElement('div');
                wrapper.innerHTML = template.innerHTML.replaceAll('__INDEX__', String(nextIndex)).trim();

                const row = wrapper.firstElementChild;
                enableFields(row);
                list.appendChild(row);
                initializeLine(row);
                nextIndex++;
            });
        });
    </script>
@endpush
