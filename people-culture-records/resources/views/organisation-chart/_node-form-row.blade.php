@php
    $isTemplate = $isTemplate ?? false;
    $disabled = $isTemplate ? 'disabled' : '';
    $nodeId = data_get($node, 'id');
    $nodeLabel = old("nodes.$index.label", data_get($node, 'label'));
    $selectedEmployeeId = old("nodes.$index.employee_id", data_get($node, 'employee_id'));
    $selectedEmployee = $selectedEmployeeId ? $employees->firstWhere('id', (int) $selectedEmployeeId) : null;
    $selectedEmployeeOption = $selectedEmployee ? [
        'id' => $selectedEmployee->id,
        'text' => $selectedEmployee->display_name,
        'details' => collect([
            $selectedEmployee->jobTitle?->name,
            $selectedEmployee->province?->name,
            $selectedEmployee->district?->name,
            $selectedEmployee->facility?->name,
        ])->filter()->implode(' | '),
    ] : null;
@endphp

<div class="org-node-editor border rounded-2 p-3 mb-3 bg-light" data-org-node-row>
    <div class="d-flex flex-column flex-lg-row justify-content-between gap-2 mb-3">
        <div>
            <div class="fw-semibold">{{ $nodeLabel ?: 'New chart box' }}</div>
            <div class="small text-muted">Choose where this box sits, what it represents, and what it should link to.</div>
        </div>
        <div class="d-flex gap-2 align-items-start">
            @if ($nodeId)
                <input type="hidden" name="nodes[{{ $index }}][id]" value="{{ $nodeId }}" {{ $disabled }}>
                <label class="form-check-label small text-danger-emphasis">
                    <input type="checkbox" name="nodes[{{ $index }}][_delete]" value="1" class="form-check-input me-1" {{ $disabled }}>
                    Remove
                </label>
            @else
                <button type="button" class="btn btn-sm btn-outline-danger" data-remove-new-node {{ $disabled }}>Remove</button>
            @endif
        </div>
    </div>

    <div class="row g-3">
        <div class="col-md-6 col-xl-4">
            <label class="form-label">Box Label <span class="text-danger">*</span></label>
            <input type="text" name="nodes[{{ $index }}][label]" value="{{ old("nodes.$index.label", data_get($node, 'label')) }}" class="form-control @error("nodes.$index.label") is-invalid @enderror" placeholder="Chief of Party" {{ $disabled }}>
            @error("nodes.$index.label")
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
        <div class="col-md-6 col-xl-4">
            <label class="form-label">Subtitle</label>
            <input type="text" name="nodes[{{ $index }}][subtitle]" value="{{ old("nodes.$index.subtitle", data_get($node, 'subtitle')) }}" class="form-control" placeholder="USAID ACTION HIV Project" {{ $disabled }}>
        </div>
        <div class="col-md-6 col-xl-2">
            <label class="form-label">Type <span class="text-danger">*</span></label>
            <select name="nodes[{{ $index }}][node_type]" class="form-select @error("nodes.$index.node_type") is-invalid @enderror" {{ $disabled }}>
                @foreach ($nodeTypes as $value => $label)
                    <option value="{{ $value }}" @selected(old("nodes.$index.node_type", data_get($node, 'node_type', App\Models\OrganisationChartNode::TYPE_SUPPORT_UNIT)) === $value)>{{ $label }}</option>
                @endforeach
            </select>
            @error("nodes.$index.node_type")
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
        <div class="col-md-6 col-xl-2">
            <label class="form-label">Planned Positions</label>
            <input type="number" min="0" name="nodes[{{ $index }}][planned_positions]" value="{{ old("nodes.$index.planned_positions", data_get($node, 'planned_positions')) }}" class="form-control" placeholder="1" {{ $disabled }}>
        </div>

        <div class="col-md-6 col-xl-4">
            <label class="form-label">Reports To</label>
            <select name="nodes[{{ $index }}][parent_id]" class="form-select @error("nodes.$index.parent_id") is-invalid @enderror" {{ $disabled }}>
                <option value="">Top level</option>
                @foreach ($parentNodes as $parentNode)
                    @continue((string) $nodeId === (string) $parentNode->id)
                    <option value="{{ $parentNode->id }}" @selected((string) old("nodes.$index.parent_id", data_get($node, 'parent_id')) === (string) $parentNode->id)>{{ $parentNode->label }}</option>
                @endforeach
            </select>
            @error("nodes.$index.parent_id")
                <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
        </div>
        <div class="col-md-6 col-xl-4">
            <label class="form-label">Linked Employee</label>
            <div class="org-employee-picker" data-org-employee-picker data-url="{{ route('employees.search') }}" data-selected='@json($selectedEmployeeOption)'>
                <input
                    type="search"
                    class="form-control @error("nodes.$index.employee_id") is-invalid @enderror"
                    value="{{ $selectedEmployeeOption['text'] ?? '' }}"
                    placeholder="Search employee number, name, job title, or location"
                    autocomplete="off"
                    data-org-employee-input
                    {{ $disabled }}
                >
                <input type="hidden" name="nodes[{{ $index }}][employee_id]" value="{{ $selectedEmployeeId }}" data-org-employee-id {{ $disabled }}>
                <div class="org-employee-results d-none" data-org-employee-results role="listbox"></div>
                <div class="org-employee-selected mt-2 {{ $selectedEmployeeOption ? '' : 'd-none' }}" data-org-employee-selected>
                    <div class="d-flex justify-content-between gap-2 align-items-start">
                        <div class="min-w-0">
                            <div class="fw-semibold text-truncate" data-org-employee-selected-text>{{ $selectedEmployeeOption['text'] ?? '' }}</div>
                            <div class="small text-muted" data-org-employee-selected-details>{{ $selectedEmployeeOption['details'] ?? '' }}</div>
                        </div>
                        <button type="button" class="btn btn-sm btn-secondary flex-shrink-0" data-org-employee-clear {{ $disabled }}>Clear</button>
                    </div>
                </div>
            </div>
            <div class="form-text">Use this for people such as a Country Director serving as Chief of Party. Leave blank for generic boxes.</div>
            @error("nodes.$index.employee_id")
                <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
        </div>
        <div class="col-md-6 col-xl-2">
            <label class="form-label">Display Order</label>
            <input type="number" min="0" name="nodes[{{ $index }}][sort_order]" value="{{ old("nodes.$index.sort_order", data_get($node, 'sort_order', $index === '__INDEX__' ? '' : $index + 1)) }}" class="form-control" {{ $disabled }}>
        </div>

        <div class="col-md-6 col-xl-3">
            <label class="form-label">Job Title Link</label>
            <select name="nodes[{{ $index }}][job_title_id]" class="form-select" {{ $disabled }}>
                <option value="">Any job title</option>
                @foreach ($jobTitles as $jobTitle)
                    <option value="{{ $jobTitle->id }}" @selected((string) old("nodes.$index.job_title_id", data_get($node, 'job_title_id')) === (string) $jobTitle->id)>{{ $jobTitle->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-6 col-xl-3">
            <label class="form-label">Project Link</label>
            <select name="nodes[{{ $index }}][project_id]" class="form-select" {{ $disabled }}>
                <option value="">Use chart project</option>
                @foreach ($projects as $project)
                    <option value="{{ $project->id }}" @selected((string) old("nodes.$index.project_id", data_get($node, 'project_id')) === (string) $project->id)>{{ $project->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-6 col-xl-3">
            <label class="form-label">Department Link</label>
            <select name="nodes[{{ $index }}][department_id]" class="form-select" {{ $disabled }}>
                <option value="">Any department</option>
                @foreach ($departments as $department)
                    <option value="{{ $department->id }}" @selected((string) old("nodes.$index.department_id", data_get($node, 'department_id')) === (string) $department->id)>{{ $department->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-6 col-xl-3">
            <label class="form-label">Province Link</label>
            <select name="nodes[{{ $index }}][province_id]" class="form-select @error("nodes.$index.province_id") is-invalid @enderror" {{ $disabled }}>
                <option value="">Any province</option>
                @foreach ($provinces as $province)
                    <option value="{{ $province->id }}" @selected((string) old("nodes.$index.province_id", data_get($node, 'province_id')) === (string) $province->id)>{{ $province->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-6 col-xl-3">
            <label class="form-label">District Link</label>
            <select name="nodes[{{ $index }}][district_id]" class="form-select @error("nodes.$index.district_id") is-invalid @enderror" {{ $disabled }}>
                <option value="">Any district</option>
                @foreach ($districts as $district)
                    <option value="{{ $district->id }}" @selected((string) old("nodes.$index.district_id", data_get($node, 'district_id')) === (string) $district->id)>{{ $district->name }}{{ $district->province ? ' - '.$district->province->name : '' }}</option>
                @endforeach
            </select>
            @error("nodes.$index.district_id")
                <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
        </div>
        <div class="col-md-6 col-xl-3">
            <label class="form-label">Facility Link</label>
            <select name="nodes[{{ $index }}][facility_id]" class="form-select @error("nodes.$index.facility_id") is-invalid @enderror" {{ $disabled }}>
                <option value="">Any facility</option>
                @foreach ($facilities as $facility)
                    <option value="{{ $facility->id }}" @selected((string) old("nodes.$index.facility_id", data_get($node, 'facility_id')) === (string) $facility->id)>{{ $facility->name }}</option>
                @endforeach
            </select>
            @error("nodes.$index.facility_id")
                <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
        </div>
    </div>
</div>
