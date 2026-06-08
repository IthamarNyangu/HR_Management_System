@php
    $children = $childrenByParent->get($node->id, collect());
    $filters = [
        'project_id' => $node->project_id ?: $organisationChart->project_id,
        'department_id' => $node->department_id,
        'job_title_id' => $node->job_title_id,
        'province_id' => $node->province_id,
        'district_id' => $node->district_id,
        'facility_id' => $node->facility_id,
    ];
    $hasEmployeeFilters = collect($filters)->filter(fn ($value) => filled($value))->isNotEmpty();
    $filterUrl = $hasEmployeeFilters ? route('employees.index', array_filter($filters, fn ($value) => filled($value))) : '';
    $employeeUrl = $node->employee ? route('employees.show', $node->employee) : '';
    $editUrl = route('organisation-chart.edit', $organisationChart).'#node-'.$node->id;
    $duplicateUrl = route('organisation-chart.nodes.duplicate', [$organisationChart, $node]);
    $canUpdateChart = auth()->user()?->can('update', $organisationChart) ?? false;
    $nodeClasses = [
        'central_head_office' => 'org-node-central',
        'regional_level' => 'org-node-regional',
        'provincial_level' => 'org-node-provincial',
        'hub_facility' => 'org-node-hub',
        'key_position' => 'org-node-key',
        'external_partner' => 'org-node-external',
        'support_unit' => 'org-node-support',
    ];
    $cardClass = $nodeClasses[$node->node_type] ?? 'org-node-support';
@endphp

<div class="formal-org-node">
    <div class="formal-org-card {{ $cardClass }}">
        <button
            type="button"
            class="formal-org-card-link"
            title="Choose what to do with this chart box"
            data-org-node-action
            data-node-label="{{ $node->label }}"
            data-node-subtitle="{{ $node->subtitle }}"
            data-employee-url="{{ $employeeUrl }}"
            data-filter-url="{{ $filterUrl }}"
            data-edit-url="{{ $editUrl }}"
            data-duplicate-url="{{ $duplicateUrl }}"
            data-can-update="{{ $canUpdateChart ? '1' : '0' }}"
        >
            <div class="formal-org-label">{{ $node->label }}</div>
            @if ($node->subtitle)
                <div class="formal-org-subtitle">{{ $node->subtitle }}</div>
            @endif
            @if ($node->planned_positions !== null)
                <div class="formal-org-count">({{ $node->planned_positions }})</div>
            @endif
            @if ($node->employee)
                <div class="formal-org-linked">{{ $node->employee->employee_no }} - {{ $node->employee->full_name }}</div>
            @endif
        </button>
    </div>

    @if ($children->isNotEmpty())
        <div class="formal-org-children">
            @foreach ($children as $child)
                @include('organisation-chart._chart-node', [
                    'node' => $child,
                    'childrenByParent' => $childrenByParent,
                    'organisationChart' => $organisationChart,
                ])
            @endforeach
        </div>
    @endif
</div>
