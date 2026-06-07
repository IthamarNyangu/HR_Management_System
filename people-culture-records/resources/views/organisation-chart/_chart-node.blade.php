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
    $filterUrl = route('employees.index', array_filter($filters, fn ($value) => filled($value)));
    $nodeUrl = $node->employee ? route('employees.show', $node->employee) : (collect($filters)->filter(fn ($value) => filled($value))->isNotEmpty() ? $filterUrl : null);
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
        @if ($nodeUrl)
            <a href="{{ $nodeUrl }}" class="formal-org-card-link" title="Open linked system record">
        @else
            <div class="formal-org-card-link">
        @endif
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
        @if ($nodeUrl)
            </a>
        @else
            </div>
        @endif
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
