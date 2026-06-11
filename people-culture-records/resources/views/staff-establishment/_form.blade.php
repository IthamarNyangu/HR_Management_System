@php
    $lineRows = collect(old('lines', $plan->lines->map(fn ($line) => [
        'id' => $line->id,
        'job_title_id' => $line->job_title_id,
        'province_id' => $line->province_id,
        'district_id' => $line->district_id,
        'department_id' => $line->department_id,
        'budgeted_positions' => $line->budgeted_positions,
    ])->values()->all()));

    if ($lineRows->isEmpty()) {
        $lineRows = collect([[
            'id' => null,
            'job_title_id' => null,
            'province_id' => null,
            'district_id' => null,
            'department_id' => null,
            'budgeted_positions' => 0,
        ]]);
    }

    $jobTitleOptions = $jobTitles->map(fn ($jobTitle) => [
        'id' => (string) $jobTitle->id,
        'name' => $jobTitle->name,
    ])->values();

    $provinceOptions = $provinces->map(fn ($province) => [
        'id' => (string) $province->id,
        'name' => $province->name,
    ])->values();

@endphp

<form method="POST" action="{{ $action }}" data-staff-establishment-form>
    @csrf
    @if (($method ?? 'POST') !== 'POST')
        @method($method)
    @endif

    <input type="hidden" name="matrix_generated" value="{{ old('matrix_generated', 0) }}" data-matrix-generated-flag>
    <input type="hidden" name="matrix_created_count" value="{{ old('matrix_created_count', 0) }}" data-matrix-created-count>
    <input type="hidden" name="matrix_updated_count" value="{{ old('matrix_updated_count', 0) }}" data-matrix-updated-count>
    <input type="hidden" name="matrix_skipped_count" value="{{ old('matrix_skipped_count', 0) }}" data-matrix-skipped-count>
    <input type="hidden" name="matrix_selected_job_titles" value="{{ old('matrix_selected_job_titles') }}" data-matrix-selected-job-titles>
    <input type="hidden" name="matrix_selected_locations" value="{{ old('matrix_selected_locations') }}" data-matrix-selected-locations>

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
                    <p class="text-muted mb-0">Each line defines the approved headcount for a job title, department, province, and district. Filled and vacancy numbers are calculated from active employee records.</p>
                </div>
            </div>

            @error('lines')<div class="alert alert-danger py-2">{{ $message }}</div>@enderror

            <div class="row g-3 mb-3">
                <div class="col-lg-12">
                    <div class="border rounded-2 bg-light p-3 h-100">
                        <h3 class="h6 mb-2">Exports</h3>
                        @if ($plan->exists)
                            <div class="d-flex flex-wrap gap-2">
                                <a href="{{ route('staff-establishment.export.excel', $plan) }}" class="btn btn-primary btn-md">
                                    <i class="bi bi-file-earmark-excel" aria-hidden="true"></i>
                                    Export Excel
                                </a>
                                <a href="{{ route('staff-establishment.export.pdf', $plan) }}" class="btn btn-secondary btn-md">
                                    <i class="bi bi-file-earmark-pdf" aria-hidden="true"></i>
                                    Export PDF
                                </a>
                            </div>
                        @else
                            <div class="d-flex flex-wrap gap-2 align-items-center">
                                <button type="button" class="btn btn-secondary btn-md" disabled>Export Excel</button>
                                <button type="button" class="btn btn-secondary btn-md" disabled>Export PDF</button>
                                <span class="text-muted small">Save the establishment plan first to enable exports.</span>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="border rounded-2 bg-light p-3 mb-3" data-matrix-builder>
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="summary-tile h-100">
                            <div class="summary-label">Positions</div>
                            <div class="summary-value">{{ $jobTitles->count() }}</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="summary-tile h-100">
                            <div class="summary-label">Overall Budgeted Number</div>
                            <div class="summary-value" data-matrix-overall-budget>0</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="summary-tile h-100">
                            <div class="summary-label">Total Vacancies</div>
                            <div class="summary-value" data-matrix-overall-vacancies>0</div>
                        </div>
                    </div>
                </div>

                <div class="alert alert-warning py-2 mt-3 mb-0" data-matrix-message hidden></div>

                <div class="mt-3" data-matrix-table-wrap>
                    <div class="table-responsive data-table-wrap bg-white">
                        <table class="table table-sm align-middle mb-0 data-table">
                            <thead data-matrix-table-head></thead>
                            <tbody data-matrix-table-body></tbody>
                            <tfoot data-matrix-table-foot></tfoot>
                        </table>
                    </div>
                </div>
            </div>

            <div class="d-none" data-establishment-line-list>
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
                        'department_id' => null,
                        'budgeted_positions' => 0,
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
            const projectSelect = form.querySelector('#project_id');
            const jobTitles = @json($jobTitleOptions);
            const provinces = @json($provinceOptions);
            const currentEstablishment = @json($matrixCurrentEstablishment);
            const matrixMessage = form.querySelector('[data-matrix-message]');
            const matrixTableWrap = form.querySelector('[data-matrix-table-wrap]');
            const matrixTableHead = form.querySelector('[data-matrix-table-head]');
            const matrixTableBody = form.querySelector('[data-matrix-table-body]');
            const matrixTableFoot = form.querySelector('[data-matrix-table-foot]');
            const matrixOverallBudget = form.querySelector('[data-matrix-overall-budget]');
            const matrixOverallVacancies = form.querySelector('[data-matrix-overall-vacancies]');
            const matrixGeneratedFlag = form.querySelector('[data-matrix-generated-flag]');
            const matrixCreatedCount = form.querySelector('[data-matrix-created-count]');
            const matrixUpdatedCount = form.querySelector('[data-matrix-updated-count]');
            const matrixSkippedCount = form.querySelector('[data-matrix-skipped-count]');
            const matrixSelectedJobTitles = form.querySelector('[data-matrix-selected-job-titles]');
            const matrixSelectedLocations = form.querySelector('[data-matrix-selected-locations]');
            let nextIndex = {{ $lineRows->count() }};

            function enableFields(row) {
                row.querySelectorAll('[disabled]').forEach(function (field) {
                    field.disabled = false;
                });
            }

            function filterLocation(row) {
                const province = row.querySelector('[data-line-province]');
                const district = row.querySelector('[data-line-district]');
                const provinceId = province?.value || '';

                district?.querySelectorAll('option[data-province-id]').forEach(function (option) {
                    const visible = !provinceId || option.dataset.provinceId === provinceId;
                    option.hidden = !visible;
                    option.disabled = !visible;
                });

                if (district?.selectedOptions[0]?.disabled) {
                    district.value = '';
                }
            }

            function createLine() {
                const wrapper = document.createElement('div');
                wrapper.innerHTML = template.innerHTML.replaceAll('__INDEX__', String(nextIndex)).trim();

                const row = wrapper.firstElementChild;
                enableFields(row);
                list.appendChild(row);
                initializeLine(row);
                nextIndex++;

                return row;
            }

            function lineKey(row) {
                return keyFromParts(
                    row.querySelector('[data-line-job-title]')?.value || '',
                    row.querySelector('[data-line-province]')?.value || '',
                    row.querySelector('[data-line-district]')?.value || '',
                    row.querySelector('[data-line-department]')?.value || '',
                );
            }

            function existingLineKeys() {
                const keys = new Set();

                list.querySelectorAll('[data-establishment-line]').forEach(function (row) {
                    const key = lineKey(row);

                    if (key) {
                        keys.add(key);
                    }
                });

                return keys;
            }

            function removeBlankNewRows() {
                list.querySelectorAll('[data-establishment-line]').forEach(function (row) {
                    const id = row.querySelector('input[name$="[id]"]')?.value || '';
                    const jobTitle = row.querySelector('[data-line-job-title]')?.value || '';
                    const province = row.querySelector('[data-line-province]')?.value || '';
                    const district = row.querySelector('[data-line-district]')?.value || '';
                    const department = row.querySelector('[data-line-department]')?.value || '';
                    const budgeted = row.querySelector('input[name$="[budgeted_positions]"]')?.value || '0';

                    if (!id && !jobTitle && !province && !district && !department && Number(budgeted) === 0) {
                        row.remove();
                    }
                });
            }

            function setRowValue(row, selector, value) {
                const field = row.querySelector(selector);

                if (field) {
                    field.value = value || '';
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

            function keyFromParts(jobTitleId, provinceId, districtId, departmentId) {
                if (!jobTitleId) {
                    return null;
                }

                return [
                    jobTitleId || '',
                    provinceId || '',
                    districtId || '',
                    departmentId || '',
                ].join('|');
            }

            function showMatrixMessage(message) {
                if (!matrixMessage) {
                    return;
                }

                matrixMessage.textContent = message;
                matrixMessage.hidden = !message;
            }

            function appendMatrixCell(row, text, className = '') {
                const cell = document.createElement('td');
                cell.textContent = text;

                if (className) {
                    cell.className = className;
                }

                row.appendChild(cell);

                return cell;
            }

            function applyStickyHeader(cell, top, zIndex = 4) {
                cell.style.position = 'sticky';
                cell.style.top = top;
                cell.style.zIndex = String(zIndex);
                cell.style.backgroundClip = 'padding-box';

                return cell;
            }

            const provincePalette = [
                { background: '#eff6ff', border: '#2563eb' },
                { background: '#ecfdf5', border: '#15803d' },
                { background: '#fff7ed', border: '#d97706' },
                { background: '#fdf2f8', border: '#be185d' },
                { background: '#f5f3ff', border: '#7c3aed' },
                { background: '#f0fdfa', border: '#0f766e' },
            ];

            function applyProvinceStyle(cell, provinceIndex, position = 'middle') {
                const theme = provincePalette[provinceIndex % provincePalette.length];

                cell.style.backgroundColor = theme.background;

                if (position === 'first' || position === 'single') {
                    cell.style.borderLeft = `3px solid ${theme.border}`;
                }

                if (position === 'last' || position === 'single') {
                    cell.style.borderRight = `3px solid ${theme.border}`;
                }

                return cell;
            }

            function currentCount(jobTitleId, provinceId) {
                const key = `${jobTitleId}|${provinceId}`;
                const projectId = projectSelect?.value || '';

                if (projectId) {
                    return Number(currentEstablishment.projects?.[projectId]?.[key] || 0);
                }

                return Number(currentEstablishment.all?.[key] || 0);
            }

            function buildMatrixTable() {
                matrixTableHead.innerHTML = '';
                matrixTableBody.innerHTML = '';
                matrixTableFoot.innerHTML = '';

                const headRow = document.createElement('tr');
                const subHeadRow = document.createElement('tr');
                const fixedHeaders = [
                    { lines: ['Position'], width: '14rem', sticky: true },
                    { lines: ['Overall Budgeted', 'Number'], width: '7rem' },
                    { lines: ['Total Current', 'Establishment'], width: '7rem' },
                    { lines: ['Vacancies'], width: '6rem' },
                ];

                fixedHeaders.forEach(function (header, index) {
                    const th = document.createElement('th');
                    th.innerHTML = header.lines.join('<br>');
                    th.rowSpan = 2;
                    th.className = `${index === 0 ? 'text-start' : 'text-center'} align-middle`;
                    th.style.minWidth = header.width;
                    th.style.lineHeight = '1.15';
                    th.style.backgroundColor = '#f8fafc';
                    applyStickyHeader(th, '0', header.sticky ? 7 : 6);

                    if (header.sticky) {
                        th.style.left = '0';
                    }

                    headRow.appendChild(th);
                });

                provinces.forEach(function (province, provinceIndex) {
                    const th = document.createElement('th');
                    th.textContent = province.name;
                    th.colSpan = 3;
                    th.className = 'text-center text-nowrap';
                    th.style.borderTop = `3px solid ${provincePalette[provinceIndex % provincePalette.length].border}`;
                    th.style.borderLeft = `3px solid ${provincePalette[provinceIndex % provincePalette.length].border}`;
                    th.style.borderRight = `3px solid ${provincePalette[provinceIndex % provincePalette.length].border}`;
                    th.style.backgroundColor = provincePalette[provinceIndex % provincePalette.length].background;
                    applyStickyHeader(th, '0', 5);
                    headRow.appendChild(th);

                    ['Current', 'Budgeted Staff', 'Vacancies'].forEach(function (label, columnIndex) {
                        const subHead = document.createElement('th');
                        subHead.textContent = label;
                        subHead.className = 'text-center text-nowrap small';
                        subHead.style.minWidth = '6.25rem';
                        subHead.style.borderBottom = `3px solid ${provincePalette[provinceIndex % provincePalette.length].border}`;
                        applyStickyHeader(subHead, '2.35rem', 4);
                        applyProvinceStyle(
                            subHead,
                            provinceIndex,
                            columnIndex === 0 ? 'first' : (columnIndex === 2 ? 'last' : 'middle'),
                        );
                        subHeadRow.appendChild(subHead);
                    });
                });

                matrixTableHead.appendChild(headRow);
                matrixTableHead.appendChild(subHeadRow);

                jobTitles.forEach(function (jobTitle) {
                    const row = document.createElement('tr');
                    row.dataset.matrixPositionRow = 'true';
                    row.dataset.jobTitleId = jobTitle.id;
                    row.dataset.jobTitleName = jobTitle.name;
                    row.title = jobTitle.name;

                    const positionCell = appendMatrixCell(row, jobTitle.name, 'fw-semibold text-nowrap');
                    positionCell.style.position = 'sticky';
                    positionCell.style.left = '0';
                    positionCell.style.zIndex = '2';
                    positionCell.style.backgroundColor = '#ffffff';

                    appendMatrixCell(row, '0', 'text-center fw-semibold').dataset.rowOverallBudget = 'true';
                    appendMatrixCell(row, '0', 'text-center').dataset.rowCurrent = 'true';
                    appendMatrixCell(row, '0', 'text-center fw-semibold').dataset.rowVacancies = 'true';

                    provinces.forEach(function (province, provinceIndex) {
                        const currentCell = appendMatrixCell(row, '0', 'text-center');
                        currentCell.dataset.matrixCurrent = 'true';
                        applyProvinceStyle(currentCell, provinceIndex, 'first');

                        const budgetCell = document.createElement('td');
                        applyProvinceStyle(budgetCell, provinceIndex);
                        const input = document.createElement('input');
                        input.type = 'number';
                        input.min = '0';
                        input.step = '1';
                        input.className = 'form-control form-control-sm text-center';
                        input.placeholder = '0';
                        input.dataset.matrixBudget = 'true';
                        input.dataset.jobTitleId = jobTitle.id;
                        input.dataset.jobTitleName = jobTitle.name;
                        input.dataset.provinceId = province.id;
                        input.dataset.locationLabel = province.name;
                        input.title = `${jobTitle.name} - ${province.name} budgeted staff`;
                        const existingValue = existingBudget(jobTitle.id, province.id);

                        if (existingValue !== null) {
                            input.value = String(existingValue);
                        }

                        input.addEventListener('input', updateMatrixTotals);
                        budgetCell.appendChild(input);
                        row.appendChild(budgetCell);

                        const vacancyCell = appendMatrixCell(row, '0', 'text-center');
                        vacancyCell.dataset.matrixVacancy = 'true';
                        applyProvinceStyle(vacancyCell, provinceIndex, 'last');
                    });

                    matrixTableBody.appendChild(row);
                });

                const totalsRow = document.createElement('tr');
                totalsRow.dataset.matrixTotalsRow = 'true';
                totalsRow.className = 'table-light fw-semibold';

                const totalsLabel = appendMatrixCell(totalsRow, 'Totals', 'text-start');
                totalsLabel.style.position = 'sticky';
                totalsLabel.style.left = '0';
                totalsLabel.style.zIndex = '2';
                totalsLabel.style.backgroundColor = '#f8fafc';

                appendMatrixCell(totalsRow, '0', 'text-center').dataset.totalOverallBudget = 'true';
                appendMatrixCell(totalsRow, '0', 'text-center').dataset.totalOverallCurrent = 'true';
                appendMatrixCell(totalsRow, '0', 'text-center').dataset.totalOverallVacancies = 'true';

                provinces.forEach(function (province, provinceIndex) {
                    const currentCell = appendMatrixCell(totalsRow, '0', 'text-center');
                    currentCell.dataset.provinceTotalCurrent = 'true';
                    applyProvinceStyle(currentCell, provinceIndex, 'first');

                    const budgetCell = appendMatrixCell(totalsRow, '0', 'text-center');
                    budgetCell.dataset.provinceTotalBudget = 'true';
                    applyProvinceStyle(budgetCell, provinceIndex);

                    const vacancyCell = appendMatrixCell(totalsRow, '0', 'text-center');
                    vacancyCell.dataset.provinceTotalVacancies = 'true';
                    applyProvinceStyle(vacancyCell, provinceIndex, 'last');
                });

                matrixTableFoot.appendChild(totalsRow);
                matrixTableWrap.hidden = false;
                updateMatrixTotals();
            }

            function updateMatrixTotals() {
                let overallBudget = 0;
                let overallCurrent = 0;
                let overallVacancies = 0;
                const provinceTotals = provinces.map(function () {
                    return { current: 0, budget: 0, vacancies: 0 };
                });

                matrixTableBody.querySelectorAll('[data-matrix-position-row]').forEach(function (row) {
                    let rowBudget = 0;
                    let rowCurrent = 0;
                    let rowVacancies = 0;
                    const currentCells = row.querySelectorAll('[data-matrix-current]');
                    const vacancyCells = row.querySelectorAll('[data-matrix-vacancy]');
                    const inputs = row.querySelectorAll('[data-matrix-budget]');

                    inputs.forEach(function (input, index) {
                        const budgeted = Number(input.value || 0);
                        const current = currentCount(input.dataset.jobTitleId, input.dataset.provinceId);
                        const vacancy = Math.max(budgeted - current, 0);

                        currentCells[index].textContent = String(current);
                        vacancyCells[index].textContent = String(vacancy);
                        rowBudget += Number.isFinite(budgeted) ? budgeted : 0;
                        rowCurrent += current;
                        rowVacancies += vacancy;

                        provinceTotals[index].current += current;
                        provinceTotals[index].budget += Number.isFinite(budgeted) ? budgeted : 0;
                        provinceTotals[index].vacancies += vacancy;
                    });

                    row.querySelector('[data-row-overall-budget]').textContent = String(rowBudget);
                    row.querySelector('[data-row-current]').textContent = String(rowCurrent);
                    row.querySelector('[data-row-vacancies]').textContent = String(rowVacancies);
                    overallBudget += rowBudget;
                    overallCurrent += rowCurrent;
                    overallVacancies += rowVacancies;
                });

                matrixTableFoot.querySelector('[data-total-overall-budget]').textContent = String(overallBudget);
                matrixTableFoot.querySelector('[data-total-overall-current]').textContent = String(overallCurrent);
                matrixTableFoot.querySelector('[data-total-overall-vacancies]').textContent = String(overallVacancies);

                matrixTableFoot.querySelectorAll('[data-province-total-current]').forEach(function (cell, index) {
                    cell.textContent = String(provinceTotals[index]?.current || 0);
                });

                matrixTableFoot.querySelectorAll('[data-province-total-budget]').forEach(function (cell, index) {
                    cell.textContent = String(provinceTotals[index]?.budget || 0);
                });

                matrixTableFoot.querySelectorAll('[data-province-total-vacancies]').forEach(function (cell, index) {
                    cell.textContent = String(provinceTotals[index]?.vacancies || 0);
                });

                matrixOverallBudget.textContent = String(overallBudget);
                matrixOverallVacancies.textContent = String(overallVacancies);
            }

            function existingRowsByKey() {
                const rows = new Map();

                list.querySelectorAll('[data-establishment-line]').forEach(function (row) {
                    const key = keyFromParts(
                        row.querySelector('[data-line-job-title]')?.value || '',
                        row.querySelector('[data-line-province]')?.value || '',
                        '',
                        '',
                    );

                    if (!key) {
                        return;
                    }

                    if (! rows.has(key)) {
                        rows.set(key, {
                            row,
                            rows: [],
                            budgeted: 0,
                        });
                    }

                    const entry = rows.get(key);
                    entry.rows.push(row);
                    entry.budgeted += Number(row.querySelector('input[name$="[budgeted_positions]"]')?.value || 0);
                });

                return rows;
            }

            function existingBudget(jobTitleId, provinceId) {
                return existingRowsByKey().get(keyFromParts(jobTitleId, provinceId, '', ''))?.budgeted ?? null;
            }

            function collectMatrixRows() {
                if (matrixTableWrap.hidden) {
                    showMatrixMessage('The establishment matrix is not available.');

                    return [];
                }

                const existing = existingRowsByKey();
                const seen = new Set();
                const preview = [];

                matrixTableBody.querySelectorAll('[data-matrix-budget]').forEach(function (input) {
                    const rawValue = input.value.trim();
                    const budgeted = rawValue === '' ? 0 : Number(rawValue);
                    const key = keyFromParts(
                        input.dataset.jobTitleId,
                        input.dataset.provinceId,
                        '',
                        '',
                    );

                    const item = {
                        key,
                        status: 'skipped',
                        jobTitleId: input.dataset.jobTitleId,
                        jobTitleName: input.dataset.jobTitleName,
                        provinceId: input.dataset.provinceId,
                        districtId: '',
                        departmentId: '',
                        locationLabel: input.dataset.locationLabel,
                        budgeted,
                        existingRow: existing.get(key)?.row || null,
                        existingRows: existing.get(key)?.rows || [],
                        note: '',
                    };

                    if (!Number.isFinite(budgeted) || budgeted < 0) {
                        item.status = 'invalid';
                        item.note = 'Budget must be zero or higher.';
                        preview.push(item);
                        return;
                    }

                    if (! Number.isInteger(budgeted)) {
                        item.status = 'invalid';
                        item.note = 'Budget must be a whole number.';
                        preview.push(item);
                        return;
                    }

                    if (rawValue === '' || budgeted === 0) {
                        if (item.existingRow) {
                            item.status = 'remove';
                            item.note = 'Existing line will be removed.';
                        } else {
                            item.note = rawValue === '' ? 'Blank budget skipped.' : 'Zero budget skipped.';
                        }

                        preview.push(item);
                        return;
                    }

                    if (seen.has(key)) {
                        item.status = 'duplicate';
                        item.note = 'This combination appears more than once in the matrix.';
                        preview.push(item);
                        return;
                    }

                    seen.add(key);

                    if (existing.has(key)) {
                        if (existing.get(key).budgeted === budgeted) {
                            item.status = 'skipped';
                            item.note = 'Matching line already exists with the same budget.';
                        } else {
                            item.status = 'update';
                            item.note = `Existing budget ${existing.get(key).budgeted} will be updated.`;
                        }
                    } else {
                        item.status = 'create';
                        item.note = 'New line will be created.';
                    }

                    preview.push(item);
                });

                return preview;
            }

            function hasSavedLines() {
                return Array.from(list.querySelectorAll('[data-establishment-line]')).some(function (row) {
                    return row.querySelector('[data-line-job-title]')?.value
                        && Number(row.querySelector('input[name$="[budgeted_positions]"]')?.value || 0) > 0;
                });
            }

            function applyMatrixRowsBeforeSubmit() {
                const matrixRows = collectMatrixRows();
                const invalidRows = matrixRows.filter(function (item) {
                    return item.status === 'invalid' || item.status === 'duplicate';
                });

                if (invalidRows.length > 0) {
                    showMatrixMessage('Fix the matrix values before saving. Budgeted staff must be whole numbers of zero or higher.');

                    return false;
                }

                const actionableRows = matrixRows.filter(function (item) {
                    return item.status === 'create' || item.status === 'update' || item.status === 'remove';
                });

                removeBlankNewRows();

                actionableRows.forEach(function (item) {
                    if (item.status === 'remove') {
                        item.existingRows.forEach((row) => row.remove());

                        return;
                    }

                    const row = item.existingRow || createLine();
                    item.existingRows
                        .filter((existingRow) => existingRow !== row)
                        .forEach((existingRow) => existingRow.remove());
                    setRowValue(row, '[data-line-job-title]', item.jobTitleId);
                    setRowValue(row, '[data-line-province]', item.provinceId);
                    filterLocation(row);
                    setRowValue(row, '[data-line-district]', '');
                    setRowValue(row, '[data-line-department]', '');
                    setRowValue(row, 'input[name$="[budgeted_positions]"]', String(item.budgeted));
                    filterLocation(row);
                });

                removeBlankNewRows();

                const createCount = matrixRows.filter((item) => item.status === 'create').length;
                const updateCount = matrixRows.filter((item) => item.status === 'update').length;
                const skippedCount = matrixRows.filter((item) => item.status === 'skipped').length;
                const selectedJobTitleNames = [...new Set(actionableRows
                    .filter((item) => item.status !== 'remove')
                    .map((item) => item.jobTitleName))].join(', ');
                const selectedLocationNames = [...new Set(actionableRows
                    .filter((item) => item.status !== 'remove')
                    .map((item) => item.locationLabel))].join(', ');

                matrixGeneratedFlag.value = actionableRows.length > 0 ? '1' : '0';
                matrixCreatedCount.value = String(createCount);
                matrixUpdatedCount.value = String(updateCount);
                matrixSkippedCount.value = String(skippedCount);
                matrixSelectedJobTitles.value = selectedJobTitleNames;
                matrixSelectedLocations.value = selectedLocationNames;
                showMatrixMessage('');

                return true;
            }

            list.querySelectorAll('[data-establishment-line]').forEach(initializeLine);
            buildMatrixTable();

            addButton?.addEventListener('click', function () {
                createLine();
            });

            projectSelect?.addEventListener('change', updateMatrixTotals);
            form.addEventListener('submit', function (event) {
                if (! applyMatrixRowsBeforeSubmit()) {
                    event.preventDefault();
                    event.stopPropagation();
                }
            });
        });
    </script>
@endpush
