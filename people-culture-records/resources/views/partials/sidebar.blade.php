@php
    $items = [
        ['label' => 'Dashboard', 'route' => 'dashboard', 'active' => request()->routeIs('dashboard'), 'enabled' => true],
        ['label' => 'Employees', 'route' => null, 'active' => false, 'enabled' => false],
        ['label' => 'Disciplinary Cases', 'route' => null, 'active' => false, 'enabled' => false],
        ['label' => 'Staff Promotions', 'route' => null, 'active' => false, 'enabled' => false],
        ['label' => 'Staff Relocations', 'route' => null, 'active' => false, 'enabled' => false],
        ['label' => 'Reports', 'route' => null, 'active' => false, 'enabled' => false],
        ['label' => 'Imports / Exports', 'route' => null, 'active' => false, 'enabled' => false],
        ['label' => 'Admin Panel', 'route' => 'admin.index', 'active' => request()->routeIs('admin.index') || request()->routeIs('admin.master-data.*'), 'enabled' => auth()->user()->can('manage-master-data')],
        ['label' => 'User Management', 'route' => 'admin.users.index', 'active' => request()->routeIs('admin.users.*'), 'enabled' => auth()->user()->can('manage-users')],
        ['label' => 'Audit Logs', 'route' => null, 'active' => false, 'enabled' => false],
        ['label' => 'Archived Records', 'route' => null, 'active' => false, 'enabled' => false],
    ];
@endphp

<aside class="sidebar p-3 d-lg-block">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <div class="fw-bold">People & Culture</div>
            <div class="small text-white-50">Records Management</div>
        </div>
    </div>

    <nav class="nav flex-column gap-1">
        @foreach ($items as $item)
            @if ($item['enabled'] && $item['route'])
                <a class="nav-link {{ $item['active'] ? 'active' : '' }}" href="{{ route($item['route']) }}">
                    {{ $item['label'] }}
                </a>
            @else
                <span class="nav-link disabled">{{ $item['label'] }}</span>
            @endif
        @endforeach
    </nav>
</aside>
