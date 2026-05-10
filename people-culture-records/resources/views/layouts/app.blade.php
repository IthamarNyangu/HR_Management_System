<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Dashboard') - {{ config('app.name') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body { background: #f5f7fb; }
        .app-shell { min-height: 100vh; }
        .sidebar { width: 280px; min-width: 280px; flex: 0 0 280px; background: #172033; color: #fff; }
        .sidebar .nav-link { color: rgba(255,255,255,.78); border-radius: .375rem; white-space: nowrap; display: flex; align-items: center; gap: .75rem; }
        .sidebar .nav-link:hover, .sidebar .nav-link.active { background: rgba(255,255,255,.1); color: #fff; }
        .sidebar .nav-link.disabled { color: rgba(255,255,255,.35); }
        .nav-icon { width: 1.25rem; text-align: center; font-size: 1rem; flex: 0 0 1.25rem; }
        .topbar { background: #fff; border-bottom: 1px solid #e7eaf0; }
        .content-wrap { min-width: 0; }
        .metric-card { border: 1px solid #e7eaf0; border-radius: .5rem; }
        .admin-card { border: 1px solid #e1e7f0; border-radius: .5rem; background: #fff; transition: border-color .16s ease, box-shadow .16s ease, transform .16s ease; }
        .admin-card:hover, .admin-card:focus-within { border-color: #b8c7dd; box-shadow: 0 .6rem 1.4rem rgba(23, 32, 51, .08); transform: translateY(-1px); }
        .admin-card-icon { width: 2.5rem; height: 2.5rem; border-radius: .5rem; display: inline-flex; align-items: center; justify-content: center; background: #eef4ff; color: #0d6efd; font-size: 1.15rem; flex: 0 0 auto; }
        .brand-logo { width: 42px; height: 42px; object-fit: contain; flex: 0 0 auto; }
        .topbar-logo { width: 34px; height: 34px; object-fit: contain; }
        .btn { display: inline-flex; align-items: center; justify-content: center; gap: .4rem; min-height: 2.375rem; font-weight: 500; line-height: 1.1; text-align: center; white-space: nowrap; }
        .btn-sm { min-height: 2rem; }
        @media (max-width: 991.98px) {
            .sidebar { width: 100%; min-width: 100%; flex-basis: auto; }
            .sidebar .nav-link { white-space: normal; }
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

    @include('partials.confirm-modal')

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const modalElement = document.getElementById('confirmationModal');

            if (!modalElement) {
                return;
            }

            const modal = new bootstrap.Modal(modalElement);
            const title = document.getElementById('confirmationModalTitle');
            const message = document.getElementById('confirmationModalMessage');
            const confirmButton = document.getElementById('confirmationModalConfirm');
            let pendingForm = null;

            document.querySelectorAll('form[data-confirm="true"]').forEach(function (form) {
                form.addEventListener('submit', function (event) {
                    event.preventDefault();

                    pendingForm = form;
                    title.textContent = form.dataset.confirmTitle || 'Confirm action';
                    message.textContent = form.dataset.confirmMessage || 'Please confirm that you want to continue.';
                    confirmButton.textContent = form.dataset.confirmButton || 'Continue';
                    confirmButton.className = 'btn ' + (form.dataset.confirmVariant || 'btn-primary');
                    modal.show();
                });
            });

            confirmButton.addEventListener('click', function () {
                if (pendingForm) {
                    const form = pendingForm;
                    pendingForm = null;
                    modal.hide();
                    form.submit();
                }
            });
        });
    </script>
</body>
</html>
