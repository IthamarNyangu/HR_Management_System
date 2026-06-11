<header class="topbar px-3 px-lg-4 py-3">
    @php
        $unreadNotifications = auth()->user()->unreadNotifications()->latest()->take(5)->get();
        $unreadNotificationCount = auth()->user()->unreadNotifications()->count();
    @endphp

    <div class="d-flex justify-content-between align-items-center gap-3">
        <div class="d-flex align-items-center gap-2">
            <img src="{{ asset('images/right-to-care-zambia-logo.png') }}" alt="right to care zambia logo" class="topbar-logo">
            <div>
                <div class="fw-semibold text-dark lh-sm">Right to Care Zambia</div>
            </div>
        </div>

        <div class="d-flex align-items-center gap-2">
            <div class="dropdown">
                <button class="btn btn-secondary btn-sm position-relative" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Notifications">
                    <i class="bi bi-bell" aria-hidden="true"></i>
                    @if ($unreadNotificationCount > 0)
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill text-bg-danger">
                            {{ $unreadNotificationCount > 9 ? '9+' : $unreadNotificationCount }}
                        </span>
                    @endif
                </button>
                <ul class="dropdown-menu dropdown-menu-end" style="min-width: 340px;">
                    <li class="d-flex justify-content-between align-items-center gap-2 px-3 py-2">
                        <span class="fw-semibold small">Notifications</span>
                        @if ($unreadNotificationCount > 0)
                            <form method="POST" action="{{ route('notifications.mark-all-read') }}">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-secondary">Mark all read</button>
                            </form>
                        @endif
                    </li>
                    <li><hr class="dropdown-divider my-0"></li>
                    @forelse ($unreadNotifications as $notification)
                        <li>
                            <a class="dropdown-item text-wrap small" href="{{ $notification->data['url'] ?? '#' }}">
                                <span class="fw-semibold d-block">{{ $notification->data['title'] ?? 'Notification' }}</span>
                                <span class="text-muted">{{ $notification->data['message'] ?? '' }}</span>
                            </a>
                        </li>
                    @empty
                        <li><span class="dropdown-item-text small text-muted">No unread notifications.</span></li>
                    @endforelse
                </ul>
            </div>

            <div class="dropdown">
                <button class="btn btn-secondary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    {{ auth()->user()->name }} - {{ auth()->user()->province?->name ?? 'HQ' }}
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><span class="dropdown-item-text small text-muted">{{ auth()->user()->role?->name ?? 'No role assigned' }}</span></li>
                    <li><span class="dropdown-item-text small text-muted">Province: {{ auth()->user()->province?->name ?? 'HQ' }}</span></li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <form method="POST" action="{{ url('/sign-out') }}">
                            @csrf
                            <button type="submit" class="dropdown-item">Logout</button>
                        </form>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</header>
