<header class="topbar px-3 px-lg-4 py-3">
    <div class="d-flex justify-content-between align-items-center gap-3">
        <div class="d-flex align-items-center gap-2">
            <img src="{{ asset('images/RTCZ.png') }}" alt="right to care zambia logo" class="topbar-logo">
            <div>
                <div class="fw-semibold text-dark lh-sm">Right to Care Zambia</div>
            </div>
        </div>

        <div class="dropdown">
            <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                {{ auth()->user()->name }}
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
                <li><span class="dropdown-item-text small text-muted">{{ auth()->user()->role?->name ?? 'No role assigned' }}</span></li>
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
</header>
