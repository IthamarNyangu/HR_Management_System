@php
    $items = [
        ['label' => 'Dashboard', 'icon' => 'bi-speedometer2', 'route' => 'dashboard', 'active' => request()->routeIs('dashboard'), 'enabled' => true],
        ['label' => 'Employees', 'icon' => 'bi-people', 'route' => 'employees.index', 'active' => request()->routeIs('employees.*'), 'enabled' => auth()->user()->can('viewAny', App\Models\Employee::class)],
        ['label' => 'Disciplinary Cases', 'icon' => 'bi-shield-exclamation', 'route' => 'disciplinary-cases.index', 'active' => request()->routeIs('disciplinary-cases.*'), 'enabled' => auth()->user()->can('viewAny', App\Models\DisciplinaryCase::class)],
        ['label' => 'Staff Promotions', 'icon' => 'bi-graph-up-arrow', 'route' => null, 'active' => false, 'enabled' => false],
        ['label' => 'Staff Relocations', 'icon' => 'bi-geo-alt', 'route' => null, 'active' => false, 'enabled' => false],
        ['label' => 'Reports', 'icon' => 'bi-bar-chart', 'route' => null, 'active' => false, 'enabled' => false],
        ['label' => 'Imports / Exports', 'icon' => 'bi-cloud-arrow-up', 'route' => null, 'active' => false, 'enabled' => false],
        ['label' => 'Admin Panel', 'icon' => 'bi-sliders', 'route' => 'admin.index', 'active' => request()->routeIs('admin.index') || request()->routeIs('admin.master-data.*'), 'enabled' => auth()->user()->can('manage-master-data')],
        ['label' => 'User Management', 'icon' => 'bi-person-gear', 'route' => 'admin.users.index', 'active' => request()->routeIs('admin.users.*'), 'enabled' => auth()->user()->can('manage-users')],
        ['label' => 'Audit Logs', 'icon' => 'bi-clock-history', 'route' => null, 'active' => false, 'enabled' => false],
        ['label' => 'Archived Records', 'icon' => 'bi-archive', 'route' => null, 'active' => false, 'enabled' => false],
    ];
@endphp

<aside class="sidebar p-3 d-lg-block">
    <div class="d-flex align-items-center gap-3 mb-4">
        <div class="min-w-0">
            <div class="fw-bold">People & Culture</div>
            <div class="small text-white-50">Records Management</div>
        </div>
    </div>

    <nav class="nav flex-column gap-1">
        @foreach ($items as $item)
            @if ($item['enabled'] && $item['route'])
                <a class="nav-link {{ $item['active'] ? 'active' : '' }}" href="{{ route($item['route']) }}">
                    <i class="bi {{ $item['icon'] }} nav-icon" aria-hidden="true"></i>
                    <span>{{ $item['label'] }}</span>
                </a>
            @else
                <span class="nav-link disabled">
                    <i class="bi {{ $item['icon'] }} nav-icon" aria-hidden="true"></i>
                    <span>{{ $item['label'] }}</span>
                </span>
            @endif
        @endforeach
    </nav>
</aside>
