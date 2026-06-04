@php
    $children = $childrenBySupervisor->get($employee->id, collect());
    $level = $level ?? 0;
@endphp

<div class="org-node" style="--org-level: {{ min($level, 6) }};">
    <article class="org-card">
        <div class="org-card-header">
            <div class="min-w-0">
                <div class="small text-muted">{{ $employee->employee_no }}</div>
                <h3 class="h6 mb-1">
                    <a href="{{ route('employees.show', $employee) }}">{{ $employee->full_name }}</a>
                </h3>
                <div class="small text-muted">
                    {{ $employee->jobTitle?->name ?? 'No job title recorded' }}
                </div>
            </div>
            <span class="badge text-bg-light border org-direct-badge">
                {{ $children->count() === 0 ? 'No direct reports' : $children->count().' direct report'.($children->count() === 1 ? '' : 's') }}
            </span>
        </div>

        <dl class="org-meta mb-0 mt-3">
            <dt>Department</dt>
            <dd>{{ $employee->department?->name ?? '-' }}</dd>
            <dt>Location</dt>
            <dd>{{ $employee->province?->name ?? '-' }}</dd>
        </dl>

        @if (! $employee->supervisor_employee_id && $employee->supervisor_name)
            <div class="alert alert-warning py-2 px-3 mt-3 mb-0 small">
                Line Manager: {{ $employee->supervisor_name }}
            </div>
        @endif
    </article>

    @if ($children->isNotEmpty())
        <details class="org-branch">
            <summary class="org-toggle">
                <span class="org-toggle-icon" aria-hidden="true"></span>
                <span class="org-toggle-show">Show {{ $children->count() }} direct report{{ $children->count() === 1 ? '' : 's' }}</span>
                <span class="org-toggle-hide">Hide {{ $children->count() }} direct report{{ $children->count() === 1 ? '' : 's' }}</span>
            </summary>
            <div class="org-children">
                @foreach ($children as $child)
                    @include('organisation-chart._node', ['employee' => $child, 'childrenBySupervisor' => $childrenBySupervisor, 'level' => $level + 1])
                @endforeach
            </div>
        </details>
    @endif
</div>
