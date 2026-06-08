@php
    $children = $childrenByParent->get($node->id, collect());
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

<div class="designer-node" data-designer-node data-node-id="{{ $node->id }}">
    <div class="designer-card-wrap">
        <div class="designer-card {{ $cardClass }}" draggable="true" data-designer-card>
            <div class="d-flex justify-content-between gap-2 align-items-start mb-2">
                <div class="designer-card-title">{{ $node->label }}</div>
                <i class="bi bi-grip-vertical text-muted" aria-hidden="true"></i>
            </div>
            @if ($node->subtitle)
                <div class="small">{{ $node->subtitle }}</div>
            @endif
            @if ($node->employee)
                <div class="small text-muted mt-1">{{ $node->employee->employee_no }} - {{ $node->employee->full_name }}</div>
            @endif
            <div class="small text-muted mt-2">{{ $node->type_label }}</div>
        </div>

        <div class="designer-card-actions">
            <a href="{{ route('organisation-chart.edit', $organisationChart).'#node-'.$node->id }}" class="btn btn-sm btn-outline-primary">Edit</a>
            <button type="button" class="btn btn-sm btn-secondary" data-designer-duplicate data-duplicate-url="{{ route('organisation-chart.nodes.duplicate', [$organisationChart, $node]) }}">Duplicate</button>
            @if ($node->employee)
                <a href="{{ route('employees.show', $node->employee) }}" class="btn btn-sm btn-secondary">Profile</a>
            @endif
        </div>

        <div class="designer-drop-actions">
            <div class="designer-drop-zone" data-designer-drop="child" data-drop-target-id="{{ $node->id }}">
                Drop under
            </div>
            <div class="designer-drop-zone" data-designer-drop="sibling" data-drop-target-id="{{ $node->id }}">
                Drop beside
            </div>
        </div>
    </div>

    <div class="designer-children" data-designer-children data-parent-id="{{ $node->id }}">
        @foreach ($children as $child)
            @include('organisation-chart._designer-node', [
                'node' => $child,
                'childrenByParent' => $childrenByParent,
                'organisationChart' => $organisationChart,
            ])
        @endforeach
    </div>
</div>
