<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Dashboard') - {{ config('app.name') }}</title>
    <link rel="icon" type="image/png" href="{{ asset('images/right-to-care-zambia-logo-transparent.png') }}">
    <link rel="shortcut icon" type="image/png" href="{{ asset('images/right-to-care-zambia-logo-transparent.png') }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <script>
        (function () {
            try {
                if (localStorage.getItem('sidebar-state') === 'collapsed') {
                    document.documentElement.dataset.sidebar = 'collapsed';
                }
            } catch (error) {
                document.documentElement.dataset.sidebar = 'expanded';
            }
        })();
    </script>
    <style>
        :root { --sidebar-expanded-width: 280px; --sidebar-collapsed-width: 84px; }
        body { background: #f5f7fb; }
        .app-shell { min-height: 100vh; }
        .sidebar { width: var(--sidebar-expanded-width); min-width: var(--sidebar-expanded-width); flex: 0 0 var(--sidebar-expanded-width); background: #172033; color: #fff; transition: width .18s ease, min-width .18s ease, flex-basis .18s ease; }
        .sidebar-header { min-height: 3rem; }
        .sidebar-brand-text, .sidebar-label { min-width: 0; opacity: 1; transition: opacity .12s ease, width .18s ease; }
        .sidebar-toggle { flex: 0 0 auto; }
        .sidebar .nav-link { color: rgba(255,255,255,.78); border-radius: .375rem; white-space: nowrap; display: flex; align-items: center; gap: .75rem; }
        .sidebar button.nav-link { background: transparent; border: 0; width: 100%; }
        .sidebar .nav-link:hover, .sidebar .nav-link.active { background: rgba(255,255,255,.1); color: #fff; }
        .sidebar .nav-link.disabled { color: rgba(255,255,255,.35); }
        .nav-icon { width: 1.25rem; text-align: center; font-size: 1rem; flex: 0 0 1.25rem; }
        .sidebar-subnav { border-left: 1px solid rgba(255,255,255,.18); display: flex; flex-direction: column; gap: .25rem; margin: .15rem 0 .35rem 1.65rem; padding-left: .75rem; }
        .sidebar-subnav[hidden] { display: none; }
        .sidebar-subnav-link { border-radius: .375rem; color: rgba(255,255,255,.68); display: block; font-size: .92rem; padding: .45rem .65rem; text-decoration: none; white-space: nowrap; }
        .sidebar-subnav-link:hover, .sidebar-subnav-link.active { background: rgba(255,255,255,.1); color: #fff; }
        .sidebar-caret { font-size: .8rem; transition: transform .15s ease; }
        .sidebar-group-toggle[aria-expanded="true"] .sidebar-caret { transform: rotate(180deg); }
        html[data-sidebar="collapsed"] .sidebar { width: var(--sidebar-collapsed-width); min-width: var(--sidebar-collapsed-width); flex-basis: var(--sidebar-collapsed-width); }
        html[data-sidebar="collapsed"] .sidebar { padding-left: .75rem !important; padding-right: .75rem !important; }
        html[data-sidebar="collapsed"] .sidebar-header { justify-content: center; }
        html[data-sidebar="collapsed"] .sidebar-brand-text, html[data-sidebar="collapsed"] .sidebar-label { width: 0; opacity: 0; overflow: hidden; pointer-events: none; }
        html[data-sidebar="collapsed"] .sidebar .nav-link { justify-content: center; gap: 0; padding-left: .75rem; padding-right: .75rem; }
        html[data-sidebar="collapsed"] .sidebar .nav-link.disabled { justify-content: center; }
        html[data-sidebar="collapsed"] .sidebar-subnav { display: none; }
        .topbar { background: #fff; border-bottom: 1px solid #e7eaf0; }
        .content-wrap { min-width: 0; }
        .metric-card { border: 1px solid #e7eaf0; border-radius: .5rem; min-height: 9.5rem; display: flex; flex-direction: column; }
        .metric-label { min-height: 3rem; display: flex; align-items: flex-start; }
        .metric-value { margin-top: .25rem; line-height: 1; }
        .dashboard-card { border: 1px solid #e1e7f0; border-radius: .5rem; padding: 1rem; background: #fff; min-height: 8.5rem; transition: border-color .15s ease, box-shadow .15s ease, transform .15s ease; }
        .dashboard-card:hover { border-color: #b8c7dd; box-shadow: 0 .6rem 1.4rem rgba(23, 32, 51, .08); transform: translateY(-1px); }
        .dashboard-card-primary { border-left: 4px solid #2563eb; }
        .dashboard-card-warning { border-left: 4px solid #d97706; background: #fffaf0; }
        .dashboard-card-danger { border-left: 4px solid #dc2626; background: #fff7f7; }
        .dashboard-card-success { border-left: 4px solid #15803d; }
        .dashboard-label, .summary-label { color: #667085; font-size: .875rem; font-weight: 500; }
        .dashboard-value { font-size: 2.25rem; font-weight: 700; line-height: 1.1; margin-top: .75rem; color: #111827; }
        .dashboard-icon { width: 2.25rem; height: 2.25rem; border-radius: .5rem; display: inline-flex; align-items: center; justify-content: center; background: #f3f6fa; color: #344054; flex: 0 0 auto; }
        .summary-tile { border: 1px solid #e7eaf0; border-radius: .5rem; padding: 1rem; background: #f8fafc; min-height: 6.5rem; }
        .summary-value { font-size: 1.75rem; font-weight: 700; line-height: 1.1; margin-top: .5rem; color: #111827; }
        .activity-list { display: grid; gap: .85rem; }
        .activity-item { display: flex; gap: .85rem; align-items: flex-start; padding-bottom: .85rem; border-bottom: 1px solid #eef2f7; }
        .activity-item:last-child { border-bottom: 0; padding-bottom: 0; }
        .activity-dot { width: .65rem; height: .65rem; margin-top: .45rem; border-radius: 999px; background: #2563eb; flex: 0 0 .65rem; }
        .admin-card { border: 1px solid #e1e7f0; border-radius: .5rem; background: #fff; transition: border-color .16s ease, box-shadow .16s ease, transform .16s ease; }
        .admin-card:hover, .admin-card:focus-within { border-color: #b8c7dd; box-shadow: 0 .6rem 1.4rem rgba(23, 32, 51, .08); transform: translateY(-1px); }
        .admin-card-icon { width: 2.5rem; height: 2.5rem; border-radius: .5rem; display: inline-flex; align-items: center; justify-content: center; background: #eef4ff; color: #0d6efd; font-size: 1.15rem; flex: 0 0 auto; }
        .data-table-wrap { border: 1px solid #e1e7f0; border-radius: .5rem; overflow: hidden; background: #fff; }
        .table-responsive.data-table-wrap { overflow-x: auto; overflow-y: hidden; -webkit-overflow-scrolling: touch; }
        .data-table { margin-bottom: 0; }
        .data-table thead th { background: #f3f6fa; color: #344054; border-bottom: 1px solid #d8e0ec; font-size: .875rem; font-weight: 700; }
        .data-table tbody tr:hover { background: #f8fafc; }
        .text-pre-line { white-space: pre-line; }
        .brand-logo { width: 42px; height: 42px; object-fit: contain; flex: 0 0 auto; }
        .topbar-logo { width: 52px; height: 52px; object-fit: contain; }
        main .form-control, main .form-select { min-height: 40px; }
        .required-field-label::after {
            content: " *";
            color: #dc2626;
            font-weight: 700;
        }
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
        .searchable-select { position: relative; }
        .searchable-select-control { min-height: 40px; width: 100%; border: 1px solid #d1d5db; border-radius: .375rem; background: #fff; color: #111827; padding: .375rem 4.25rem .375rem .75rem; line-height: 1.5; text-overflow: ellipsis; }
        .searchable-select-control:focus { border-color: #86b7fe; outline: 0; box-shadow: 0 0 0 .25rem rgba(13, 110, 253, .25); }
        .searchable-select-toggle, .searchable-select-clear { position: absolute; top: 50%; transform: translateY(-50%); border: 0; background: transparent; color: #64748b; line-height: 1; padding: .15rem; }
        .searchable-select-toggle { right: .65rem; pointer-events: none; }
        .searchable-select-clear { right: 2.3rem; width: 1.5rem; height: 1.5rem; border-radius: 999px; display: inline-flex; align-items: center; justify-content: center; font-size: 1.15rem; background: #fff; }
        .searchable-select-clear:hover, .searchable-select-clear:focus { color: #111827; background: #eef2f7; outline: 0; }
        .searchable-select-menu { position: absolute; z-index: 1060; top: calc(100% + .25rem); left: 0; right: 0; max-height: 17rem; overflow-y: auto; border: 1px solid #d1d5db; border-radius: .375rem; background: #fff; box-shadow: 0 .75rem 1.75rem rgba(15, 23, 42, .15); padding: .25rem; display: none; }
        .searchable-select.open .searchable-select-menu { display: block; }
        .searchable-select-option { width: 100%; border: 0; background: transparent; color: #111827; text-align: left; padding: .45rem .55rem; border-radius: .3rem; display: block; }
        .searchable-select-option:hover, .searchable-select-option:focus, .searchable-select-option.active { background: #eff6ff; color: #1d4ed8; outline: 0; }
        .searchable-select-empty { color: #64748b; padding: .45rem .55rem; }
        select.facility-search-source,
        select.searchable-select-source { position: absolute !important; width: 1px !important; height: 1px !important; opacity: 0 !important; pointer-events: none !important; }
        @media (max-width: 991.98px) {
            .sidebar { width: 100%; min-width: 100%; flex-basis: auto; }
            .sidebar .nav-link { white-space: normal; }
            html[data-sidebar="collapsed"] .sidebar { width: 100%; min-width: 100%; flex-basis: auto; }
            html[data-sidebar="collapsed"] .sidebar { padding-left: 1rem !important; padding-right: 1rem !important; }
            html[data-sidebar="collapsed"] .sidebar-header { justify-content: flex-start; }
            html[data-sidebar="collapsed"] .sidebar-brand-text, html[data-sidebar="collapsed"] .sidebar-label { width: auto; opacity: 1; overflow: visible; pointer-events: auto; }
            html[data-sidebar="collapsed"] .sidebar .nav-link { justify-content: flex-start; gap: .75rem; padding-left: 1rem; padding-right: 1rem; }
            html[data-sidebar="collapsed"] .sidebar-subnav { display: flex; }
        }
    </style>
    @stack('styles')
</head>
<body>
    <div class="app-shell d-lg-flex">
        @include('partials.sidebar')

        <div class="content-wrap flex-grow-1">
            @include('partials.topbar')

            <main class="container-fluid py-4 px-3 px-lg-4">
                @hasSection('hide-page-header')
                @else
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
                @endif

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

            if (modalElement) {
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
            }

            const hasVisibleRequiredMarker = function (label) {
                return label.classList.contains('required')
                    || label.querySelector('.text-danger')
                    || label.textContent.includes('*');
            };

            const labelsForControl = function (control) {
                if (!control.id) {
                    return [];
                }

                const escapedId = window.CSS && CSS.escape ? CSS.escape(control.id) : control.id.replace(/"/g, '\\"');

                return Array.from(document.querySelectorAll(`label[for="${escapedId}"]`));
            };

            const refreshRequiredFieldLabels = function () {
                document.querySelectorAll('.required-field-label').forEach(function (label) {
                    label.classList.remove('required-field-label');
                });

                document.querySelectorAll('input[required], select[required], textarea[required]').forEach(function (control) {
                    if (control.type === 'hidden') {
                        return;
                    }

                    labelsForControl(control).forEach(function (label) {
                        if (!hasVisibleRequiredMarker(label)) {
                            label.classList.add('required-field-label');
                        }
                    });
                });
            };

            window.refreshRequiredFieldLabels = refreshRequiredFieldLabels;
            refreshRequiredFieldLabels();

            document.querySelectorAll('form').forEach(function (form) {
                new MutationObserver(refreshRequiredFieldLabels).observe(form, {
                    subtree: true,
                    attributes: true,
                    attributeFilter: ['required'],
                });
            });

            const sidebarToggle = document.getElementById('sidebarToggle');

            if (sidebarToggle) {
                const sidebarToggleIcon = sidebarToggle.querySelector('.bi');
                const setSidebarState = function (state) {
                    document.documentElement.dataset.sidebar = state;
                    sidebarToggle.setAttribute('aria-expanded', state === 'expanded' ? 'true' : 'false');
                    sidebarToggle.setAttribute('title', state === 'expanded' ? 'Collapse sidebar' : 'Expand sidebar');
                    sidebarToggle.setAttribute('aria-label', state === 'expanded' ? 'Collapse sidebar' : 'Expand sidebar');

                    if (sidebarToggleIcon) {
                        sidebarToggleIcon.classList.toggle('bi-chevron-left', state === 'expanded');
                        sidebarToggleIcon.classList.toggle('bi-chevron-right', state === 'collapsed');
                    }

                    try {
                        localStorage.setItem('sidebar-state', state);
                    } catch (error) {
                        // Ignore storage errors; the toggle still works for this page.
                    }
                };

                const currentState = document.documentElement.dataset.sidebar === 'collapsed' ? 'collapsed' : 'expanded';
                setSidebarState(currentState);

                sidebarToggle.addEventListener('click', function () {
                    const nextState = document.documentElement.dataset.sidebar === 'collapsed' ? 'expanded' : 'collapsed';
                    setSidebarState(nextState);
                });
            }

            document.querySelectorAll('[data-sidebar-subnav-toggle]').forEach(function (toggle) {
                const subnav = toggle.nextElementSibling;

                if (!subnav || !subnav.classList.contains('sidebar-subnav')) {
                    return;
                }

                toggle.addEventListener('click', function () {
                    const expanded = toggle.getAttribute('aria-expanded') === 'true';

                    toggle.setAttribute('aria-expanded', expanded ? 'false' : 'true');
                    subnav.hidden = expanded;
                });
            });

            const facilitySelectSelector = [
                'select.form-select[name="facility_id"]',
                'select.form-select[name="from_facility_id"]',
                'select.form-select[name="to_facility_id"]',
                'select.form-select[name$="[facility_id]"]',
                'select[data-facility-select]',
                'select[data-from-facility-select]',
                'select[data-to-facility-select]',
                'select[data-searchable-select]'
            ].join(',');

            const enhanceFacilitySelect = function (select) {
                if (!select || select.dataset.searchableFacilityEnhanced === 'true') {
                    return;
                }

                select.dataset.searchableFacilityEnhanced = 'true';
                select.classList.add(select.hasAttribute('data-searchable-select') ? 'searchable-select-source' : 'facility-search-source');

                const wrapper = document.createElement('div');
                wrapper.className = 'searchable-select';

                const input = document.createElement('input');
                input.type = 'text';
                input.className = 'searchable-select-control';
                input.autocomplete = 'off';
                input.placeholder = select.dataset.searchPlaceholder || select.options[0]?.textContent?.trim() || 'Search';
                input.setAttribute('aria-label', select.dataset.searchPlaceholder || 'Search');

                const clearButton = document.createElement('button');
                clearButton.type = 'button';
                clearButton.className = 'searchable-select-clear';
                clearButton.innerHTML = '&times;';
                clearButton.setAttribute('aria-label', 'Clear selected facility');

                const toggle = document.createElement('span');
                toggle.className = 'searchable-select-toggle';
                toggle.innerHTML = '<i class="bi bi-chevron-down" aria-hidden="true"></i>';

                const menu = document.createElement('div');
                menu.className = 'searchable-select-menu';
                menu.setAttribute('role', 'listbox');

                select.parentNode.insertBefore(wrapper, select.nextSibling);
                wrapper.append(input, clearButton, toggle, menu);

                const visibleOptions = function () {
                    return Array.from(select.options).filter(function (option) {
                        return !option.disabled && !option.hidden;
                    });
                };

                const currentOption = function () {
                    return Array.from(select.options).find(function (option) {
                        return option.value === select.value;
                    });
                };

                const syncInputFromSelect = function () {
                    const option = currentOption();
                    input.value = option && option.value !== '' ? option.textContent.trim() : '';
                    clearButton.hidden = !select.value;
                };

                const setValue = function (value) {
                    select.value = value;
                    select.dispatchEvent(new Event('change', { bubbles: true }));
                    syncInputFromSelect();
                    wrapper.classList.remove('open');
                };

                const renderOptions = function (searchTerm) {
                    const term = searchTerm.trim().toLowerCase();
                    const matches = visibleOptions().filter(function (option) {
                        if (option.value === '') {
                            return term === '';
                        }

                        return option.textContent.toLowerCase().includes(term);
                    }).slice(0, 80);

                    menu.innerHTML = '';

                    if (matches.length === 0) {
                        const empty = document.createElement('div');
                        empty.className = 'searchable-select-empty';
                        empty.textContent = select.dataset.searchEmpty || 'No matching options found.';
                        menu.appendChild(empty);
                        return;
                    }

                    matches.forEach(function (option) {
                        const button = document.createElement('button');
                        button.type = 'button';
                        button.className = 'searchable-select-option';
                        button.textContent = option.textContent.trim();

                        if (option.value === select.value) {
                            button.classList.add('active');
                        }

                        button.addEventListener('mousedown', function (event) {
                            event.preventDefault();
                            setValue(option.value);
                        });

                        menu.appendChild(button);
                    });
                };

                input.addEventListener('focus', function () {
                    wrapper.classList.add('open');
                    renderOptions(input.value);
                });

                input.addEventListener('input', function () {
                    wrapper.classList.add('open');
                    renderOptions(input.value);
                });

                input.addEventListener('keydown', function (event) {
                    if (event.key === 'Escape') {
                        wrapper.classList.remove('open');
                        syncInputFromSelect();
                    }
                });

                clearButton.addEventListener('click', function () {
                    setValue('');
                    input.focus();
                    renderOptions('');
                    wrapper.classList.add('open');
                });

                select.addEventListener('change', syncInputFromSelect);

                new MutationObserver(function () {
                    syncInputFromSelect();

                    if (wrapper.classList.contains('open')) {
                        renderOptions(input.value);
                    }
                }).observe(select, {
                    childList: true,
                    subtree: true,
                    attributes: true,
                    attributeFilter: ['disabled', 'hidden', 'selected']
                });

                document.addEventListener('click', function (event) {
                    if (!wrapper.contains(event.target)) {
                        wrapper.classList.remove('open');
                        syncInputFromSelect();
                    }
                });

                syncInputFromSelect();
            };

            window.enhanceFacilitySelect = enhanceFacilitySelect;
            window.enhanceSearchableSelect = enhanceFacilitySelect;

            document.querySelectorAll(facilitySelectSelector).forEach(enhanceFacilitySelect);

            window.setSearchableFacilityValue = function (select, value) {
                if (!select) {
                    return;
                }

                select.value = value || '';
                select.dispatchEvent(new Event('change', { bubbles: true }));
            };
            window.setSearchableSelectValue = window.setSearchableFacilityValue;
        });
    </script>
    @stack('scripts')
</body>
</html>
