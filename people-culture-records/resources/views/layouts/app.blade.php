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
        .metric-card { border: 1px solid #e7eaf0; border-radius: .5rem; min-height: 9.5rem; display: flex; flex-direction: column; }
        .metric-label { min-height: 3rem; display: flex; align-items: flex-start; }
        .metric-value { margin-top: .25rem; line-height: 1; }
        .admin-card { border: 1px solid #e1e7f0; border-radius: .5rem; background: #fff; transition: border-color .16s ease, box-shadow .16s ease, transform .16s ease; }
        .admin-card:hover, .admin-card:focus-within { border-color: #b8c7dd; box-shadow: 0 .6rem 1.4rem rgba(23, 32, 51, .08); transform: translateY(-1px); }
        .admin-card-icon { width: 2.5rem; height: 2.5rem; border-radius: .5rem; display: inline-flex; align-items: center; justify-content: center; background: #eef4ff; color: #0d6efd; font-size: 1.15rem; flex: 0 0 auto; }
        .data-table-wrap { border: 1px solid #e1e7f0; border-radius: .5rem; overflow: hidden; background: #fff; }
        .data-table { margin-bottom: 0; }
        .data-table thead th { background: #f3f6fa; color: #344054; border-bottom: 1px solid #d8e0ec; font-size: .875rem; font-weight: 700; }
        .data-table tbody tr:hover { background: #f8fafc; }
        .brand-logo { width: 42px; height: 42px; object-fit: contain; flex: 0 0 auto; }
        .topbar-logo { width: 34px; height: 34px; object-fit: contain; }
        main .form-control, main .form-select { min-height: 40px; }
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            height: 40px;
            padding: 0 16px;
            border-radius: 6px;
            font-size: 15px;
            font-weight: 500;
            line-height: 1;
            white-space: nowrap;
            cursor: pointer;
            border: 1px solid transparent;
            text-align: center;
            text-decoration: none;
            transition: background-color .15s ease, border-color .15s ease, color .15s ease, box-shadow .15s ease;
        }
        .btn:hover { text-decoration: none; }
        .btn:focus-visible { outline: 2px solid transparent; outline-offset: 2px; box-shadow: 0 0 0 3px rgba(37, 99, 235, .28); }
        .btn:disabled, .btn.disabled { cursor: not-allowed; opacity: .65; }
        .btn-sm { height: 32px !important; padding: 0 12px !important; font-size: 14px !important; }
        .btn-md { height: 40px !important; padding: 0 16px !important; font-size: 15px !important; }
        .btn-primary { background: #2563eb !important; border-color: #2563eb !important; color: #fff !important; }
        .btn-primary:hover, .btn-primary:focus { background: #1d4ed8 !important; border-color: #1d4ed8 !important; color: #fff !important; }
        .btn-secondary, .btn-outline-secondary { background: #fff !important; border-color: #d1d5db !important; color: #374151 !important; }
        .btn-secondary:hover, .btn-secondary:focus, .btn-outline-secondary:hover, .btn-outline-secondary:focus { background: #f9fafb !important; border-color: #9ca3af !important; color: #111827 !important; }
        .btn-primary-outline, .btn-outline-primary { background: #fff !important; border-color: #2563eb !important; color: #2563eb !important; }
        .btn-primary-outline:hover, .btn-primary-outline:focus, .btn-outline-primary:hover, .btn-outline-primary:focus { background: #eff6ff !important; border-color: #1d4ed8 !important; color: #1d4ed8 !important; }
        .btn-outline-warning { background: #fff !important; border-color: #d97706 !important; color: #d97706 !important; }
        .btn-outline-warning:hover, .btn-outline-warning:focus { background: #fff7ed !important; border-color: #b45309 !important; color: #92400e !important; }
        .btn-warning { background: #d97706 !important; border-color: #d97706 !important; color: #fff !important; }
        .btn-warning:hover, .btn-warning:focus { background: #b45309 !important; border-color: #b45309 !important; color: #fff !important; }
        .btn-outline-danger { background: #fff !important; border-color: #dc2626 !important; color: #dc2626 !important; }
        .btn-outline-danger:hover, .btn-outline-danger:focus { background: #fef2f2 !important; border-color: #b91c1c !important; color: #b91c1c !important; }
        .btn-danger { background: #dc2626 !important; border-color: #dc2626 !important; color: #fff !important; }
        .btn-danger:hover, .btn-danger:focus { background: #b91c1c !important; border-color: #b91c1c !important; color: #fff !important; }
        .btn-success { background: #15803d !important; border-color: #15803d !important; color: #fff !important; }
        .btn-success:hover, .btn-success:focus { background: #166534 !important; border-color: #166534 !important; color: #fff !important; }
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
                    confirmButton.className = 'btn btn-md ' + (form.dataset.confirmVariant || 'btn-primary');
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
