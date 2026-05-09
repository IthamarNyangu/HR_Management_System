<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Dashboard') - {{ config('app.name') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f5f7fb; }
        .app-shell { min-height: 100vh; }
        .sidebar { width: 280px; background: #172033; color: #fff; }
        .sidebar .nav-link { color: rgba(255,255,255,.78); border-radius: .375rem; }
        .sidebar .nav-link:hover, .sidebar .nav-link.active { background: rgba(255,255,255,.1); color: #fff; }
        .sidebar .nav-link.disabled { color: rgba(255,255,255,.35); }
        .topbar { background: #fff; border-bottom: 1px solid #e7eaf0; }
        .content-wrap { min-width: 0; }
        .metric-card { border: 1px solid #e7eaf0; border-radius: .5rem; }
        @media (max-width: 991.98px) {
            .sidebar { width: 100%; }
        }
    </style>
</head>
<body>
    <div class="app-shell d-lg-flex">
        @include('partials.sidebar')

        <div class="content-wrap flex-grow-1">
            @include('partials.topbar')

            <main class="container-fluid py-4 px-3 px-lg-4">
                <div class="d-flex flex-column flex-md-row justify-content-between gap-2 mb-3">
                    <div>
                        <h1 class="h4 mb-1">@yield('page-title', 'Dashboard')</h1>
                        @hasSection('breadcrumb')
                            <nav aria-label="breadcrumb">
                                <ol class="breadcrumb small mb-0">
                                    @yield('breadcrumb')
                                </ol>
                            </nav>
                        @endif
                    </div>

                    @yield('page-actions')
                </div>

                @include('partials.flash')

                @yield('content')
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
