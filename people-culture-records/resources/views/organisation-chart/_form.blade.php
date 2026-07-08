@php
    $isEdit = $organisationChart->exists;
    $parentNodes = $organisationChart->nodes ?? collect();
    $nodeRows = collect(old('nodes', $parentNodes->map(fn ($node) => [
        'id' => $node->id,
        'parent_id' => $node->parent_id,
        'label' => $node->label,
        'subtitle' => $node->subtitle,
        'node_type' => $node->node_type,
        'planned_positions' => $node->planned_positions,
        'employee_id' => $node->employee_id,
        'job_title_id' => $node->job_title_id,
        'project_id' => $node->project_id,
        'department_id' => $node->department_id,
        'province_id' => $node->province_id,
        'district_id' => $node->district_id,
        'facility_id' => $node->facility_id,
        'sort_order' => $node->sort_order,
    ])->values()->all()));
@endphp

@push('styles')
    <style>
        .org-employee-picker { position: relative; }
        .org-employee-results {
            position: absolute;
            z-index: 1060;
            top: calc(100% + .35rem);
            left: 0;
            right: 0;
            max-height: 18rem;
            overflow-y: auto;
            border: 1px solid #d8e0ec;
            border-radius: .5rem;
            background: #fff;
            box-shadow: 0 .8rem 1.8rem rgba(23, 32, 51, .14);
        }
        .org-employee-option {
            width: 100%;
            border: 0;
            border-bottom: 1px solid #eef2f7;
            background: #fff;
            padding: .7rem .85rem;
            text-align: left;
        }
        .org-employee-option:hover,
        .org-employee-option:focus {
            background: #f3f6fa;
            outline: none;
        }
        .org-employee-option:last-child { border-bottom: 0; }
        .org-employee-selected {
            border: 1px solid #d8e0ec;
            border-radius: .5rem;
            background: #f8fafc;
            padding: .7rem;
        }
        .org-node-drag-handle { cursor: grab; }
        .org-node-drag-handle:active { cursor: grabbing; }
        .org-node-editor.is-dragging {
            opacity: .58;
            box-shadow: 0 .8rem 1.8rem rgba(23, 32, 51, .12);
        }
        .org-node-editor.is-drop-target,
        .org-root-drop-zone.is-drop-target {
            border-color: #2563eb !important;
            background: #eff6ff !important;
            box-shadow: 0 0 0 .2rem rgba(37, 99, 235, .12);
        }
        .org-root-drop-zone {
            border: 1px dashed #a8b4c5;
            border-radius: .5rem;
            background: #f8fafc;
            color: #475467;
            padding: .85rem 1rem;
        }
        .org-node-editor [data-bs-toggle="collapse"] .bi-chevron-down {
            transition: transform .15s ease;
        }
        .org-node-editor [data-bs-toggle="collapse"][aria-expanded="true"] .bi-chevron-down {
            transform: rotate(180deg);
        }
    </style>
@endpush

<form method="POST" action="{{ $action }}">
    @csrf
    @if (($method ?? 'POST') !== 'POST')
        @method($method)
    @endif

    <section class="bg-white border rounded-2 p-4 mb-3">
        <h2 class="h5 mb-3">Chart Details</h2>
        <div class="row g-3">
            <div class="col-lg-6">
                <label for="title" class="form-label">Chart Title <span class="text-danger">*</span></label>
                <input id="title" name="title" type="text" class="form-control @error('title') is-invalid @enderror" value="{{ old('title', $organisationChart->title) }}" placeholder="RTCZ USAID Action HIV Project Management Overview" required>
                @error('title')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-md-6 col-lg-3">
                <label for="project_id" class="form-label">Project</label>
                <select id="project_id" name="project_id" class="form-select @error('project_id') is-invalid @enderror">
                    <option value="">Organisation-wide</option>
                    @foreach ($projects as $project)
                        <option value="{{ $project->id }}" @selected((string) old('project_id', $organisationChart->project_id) === (string) $project->id)>{{ $project->name }}</option>
                    @endforeach
                </select>
                @error('project_id')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-md-6 col-lg-3">
                <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                <select id="status" name="status" class="form-select @error('status') is-invalid @enderror" required>
                    @foreach ($statuses as $status)
                        <option value="{{ $status }}" @selected(old('status', $organisationChart->status) === $status)>{{ ucfirst($status) }}</option>
                    @endforeach
                </select>
                @error('status')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-md-6 col-lg-3">
                <label for="effective_date" class="form-label">Effective Date</label>
                <input id="effective_date" name="effective_date" type="date" class="form-control @error('effective_date') is-invalid @enderror" value="{{ old('effective_date', $organisationChart->effective_date?->format('Y-m-d')) }}">
                @error('effective_date')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-12">
                <label for="description" class="form-label">Description</label>
                <textarea id="description" name="description" class="form-control @error('description') is-invalid @enderror" rows="3" placeholder="Short note about this chart version or structure">{{ old('description', $organisationChart->description) }}</textarea>
                @error('description')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>
    </section>

    @if ($isEdit)
        <section class="bg-white border rounded-2 p-4 mb-3">
            <div class="d-flex flex-column flex-lg-row justify-content-between gap-3 mb-3">
                <div>
                    <h2 class="h5 mb-1">Chart Boxes</h2>
                    <p class="text-muted mb-0">Create the project structure by linking each box to a parent box. Drag saved boxes onto another saved box to quickly update Reports To.</p>
                </div>
                <button type="button" class="btn btn-primary-outline btn-md align-self-start" data-add-org-node>Add Box</button>
            </div>

            <div class="org-root-drop-zone mb-3" data-org-root-drop>
                Drop a saved box here to make it top level.
            </div>

            <div class="alert alert-info py-2 px-3 d-none" data-org-drag-message role="status"></div>

            <div data-org-node-list>
                @forelse ($nodeRows as $index => $node)
                    @include('organisation-chart._node-form-row', [
                        'node' => $node,
                        'index' => $index,
                        'isTemplate' => false,
                    ])
                @empty
                    <div class="text-muted border rounded-2 p-4 mb-3" data-empty-node-message>No boxes added yet. Use Add Box to start building the chart.</div>
                @endforelse
            </div>

            <template data-org-node-template>
                @include('organisation-chart._node-form-row', [
                    'node' => [],
                    'index' => '__INDEX__',
                    'isTemplate' => true,
                ])
            </template>
        </section>
    @else
        <section class="bg-white border rounded-2 p-4 mb-3">
            <h2 class="h5 mb-2">Chart Boxes</h2>
            <p class="text-muted mb-0">Save the chart first, then add editable boxes and hierarchy from the Edit page.</p>
        </section>
    @endif

    <section class="bg-white border rounded-2 p-3">
        <div class="d-flex flex-wrap gap-2">
            <button type="submit" class="btn btn-primary btn-md">{{ $isEdit ? 'Save Chart' : 'Create Chart' }}</button>
            <a href="{{ $isEdit ? route('organisation-chart.show', $organisationChart) : route('organisation-chart.index') }}" class="btn btn-secondary btn-md">Cancel</a>
        </div>
    </section>
</form>

@if ($isEdit)
    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const list = document.querySelector('[data-org-node-list]');
                const template = document.querySelector('[data-org-node-template]');
                const addButton = document.querySelector('[data-add-org-node]');
                const rootDropZone = document.querySelector('[data-org-root-drop]');
                const dragMessage = document.querySelector('[data-org-drag-message]');
                let nextIndex = {{ $nodeRows->count() }};
                let draggedRow = null;

                if (!list || !template || !addButton) {
                    return;
                }

                function enableFields(container) {
                    container.querySelectorAll('[disabled]').forEach(function (field) {
                        field.disabled = false;
                    });
                }

                function clearEmployeeResults(picker) {
                    picker.querySelector('[data-org-employee-results]')?.classList.add('d-none');
                }

                function renderSelectedEmployee(picker, employee) {
                    const selected = picker.querySelector('[data-org-employee-selected]');
                    const selectedText = picker.querySelector('[data-org-employee-selected-text]');
                    const selectedDetails = picker.querySelector('[data-org-employee-selected-details]');

                    if (!selected || !selectedText || !selectedDetails) {
                        return;
                    }

                    if (!employee) {
                        selected.classList.add('d-none');
                        selectedText.textContent = '';
                        selectedDetails.textContent = '';
                        return;
                    }

                    selectedText.textContent = employee.text || '';
                    selectedDetails.textContent = employee.details || '';
                    selected.classList.remove('d-none');
                }

                function selectEmployee(picker, employee) {
                    const input = picker.querySelector('[data-org-employee-input]');
                    const idInput = picker.querySelector('[data-org-employee-id]');

                    if (!input || !idInput) {
                        return;
                    }

                    input.value = employee.text || '';
                    idInput.value = employee.id || '';
                    picker.dataset.selectedEmployee = JSON.stringify(employee);
                    renderSelectedEmployee(picker, employee);
                    clearEmployeeResults(picker);
                }

                function renderEmployeeResults(picker, employees) {
                    const results = picker.querySelector('[data-org-employee-results]');

                    if (!results) {
                        return;
                    }

                    results.innerHTML = '';

                    if (!employees.length) {
                        const empty = document.createElement('div');
                        empty.className = 'px-3 py-2 text-muted small';
                        empty.textContent = 'No employees found.';
                        results.appendChild(empty);
                        results.classList.remove('d-none');
                        return;
                    }

                    employees.forEach(function (employee) {
                        const button = document.createElement('button');
                        const primary = document.createElement('div');
                        const details = document.createElement('div');

                        button.type = 'button';
                        button.className = 'org-employee-option';
                        primary.className = 'fw-semibold';
                        details.className = 'small text-muted';
                        primary.textContent = employee.text || '';
                        details.textContent = employee.details || 'No job/location details recorded';

                        button.appendChild(primary);
                        button.appendChild(details);

                        if (employee.email) {
                            const email = document.createElement('div');
                            email.className = 'small text-muted';
                            email.textContent = employee.email;
                            button.appendChild(email);
                        }

                        button.addEventListener('click', function () {
                            selectEmployee(picker, employee);
                        });

                        results.appendChild(button);
                    });

                    results.classList.remove('d-none');
                }

                function initializeEmployeePicker(picker) {
                    if (!picker || picker.dataset.initialized === 'true') {
                        return;
                    }

                    picker.dataset.initialized = 'true';

                    const input = picker.querySelector('[data-org-employee-input]');
                    const idInput = picker.querySelector('[data-org-employee-id]');
                    const clearButton = picker.querySelector('[data-org-employee-clear]');
                    const url = picker.dataset.url;
                    let abortController = null;
                    let searchTimer = null;

                    try {
                        const selected = JSON.parse(picker.dataset.selected || 'null');
                        if (selected) {
                            picker.dataset.selectedEmployee = JSON.stringify(selected);
                        }
                    } catch (error) {
                        picker.dataset.selectedEmployee = '';
                    }

                    input?.addEventListener('input', function () {
                        const query = input.value.trim();

                        if (idInput) {
                            idInput.value = '';
                        }

                        picker.dataset.selectedEmployee = '';
                        renderSelectedEmployee(picker, null);
                        clearTimeout(searchTimer);

                        if (query.length < 1) {
                            clearEmployeeResults(picker);
                            return;
                        }

                        searchTimer = setTimeout(function () {
                            if (abortController) {
                                abortController.abort();
                            }

                            abortController = new AbortController();

                            const results = picker.querySelector('[data-org-employee-results]');
                            if (results) {
                                results.innerHTML = '';

                                const loading = document.createElement('div');
                                loading.className = 'px-3 py-2 text-muted small';
                                loading.textContent = 'Searching employees...';
                                results.appendChild(loading);
                                results.classList.remove('d-none');
                            }

                            fetch(`${url}?q=${encodeURIComponent(query)}&limit=10`, {
                                headers: { 'Accept': 'application/json' },
                                signal: abortController.signal,
                            })
                                .then(function (response) {
                                    if (!response.ok) {
                                        throw new Error('Employee search failed.');
                                    }

                                    return response.json();
                                })
                                .then(function (employees) {
                                    renderEmployeeResults(picker, employees);
                                })
                                .catch(function (error) {
                                    if (error.name === 'AbortError') {
                                        return;
                                    }

                                    const results = picker.querySelector('[data-org-employee-results]');
                                    if (!results) {
                                        return;
                                    }

                                    results.innerHTML = '';

                                    const message = document.createElement('div');
                                    message.className = 'px-3 py-2 text-danger small';
                                    message.textContent = 'Unable to search employees right now.';
                                    results.appendChild(message);
                                    results.classList.remove('d-none');
                                });
                        }, 220);
                    });

                    input?.addEventListener('focus', function () {
                        if (input.value.trim().length >= 1 && !idInput?.value) {
                            input.dispatchEvent(new Event('input'));
                        }
                    });

                    clearButton?.addEventListener('click', function () {
                        if (input) {
                            input.value = '';
                            input.focus();
                        }

                        if (idInput) {
                            idInput.value = '';
                        }

                        picker.dataset.selectedEmployee = '';
                        renderSelectedEmployee(picker, null);
                        clearEmployeeResults(picker);
                    });
                }

                function initializeEmployeePickers(scope) {
                    scope.querySelectorAll('[data-org-employee-picker]').forEach(initializeEmployeePicker);
                }

                function initializeNodeCollapseButtons(scope) {
                    scope.querySelectorAll('[data-org-node-row]').forEach(function (row) {
                        const body = row.querySelector('[data-org-node-body]');
                        const button = row.querySelector('[data-bs-toggle="collapse"]');
                        const label = button?.querySelector('[data-collapse-label]');

                        if (!body || !button || button.dataset.collapseInitialized === 'true') {
                            return;
                        }

                        button.dataset.collapseInitialized = 'true';

                        body.addEventListener('shown.bs.collapse', function () {
                            button.setAttribute('aria-expanded', 'true');
                            if (label) {
                                label.textContent = 'Hide Details';
                            }
                        });

                        body.addEventListener('hidden.bs.collapse', function () {
                            button.setAttribute('aria-expanded', 'false');
                            if (label) {
                                label.textContent = 'Show Details';
                            }
                        });
                    });
                }

                function openNodeFromHash() {
                    const hash = window.location.hash;

                    if (!hash || !hash.startsWith('#node-')) {
                        return;
                    }

                    const selector = window.CSS && CSS.escape
                        ? `#${CSS.escape(hash.slice(1))}`
                        : hash;
                    const row = document.querySelector(selector);
                    const body = row?.querySelector('[data-org-node-body]');

                    if (!row || !body) {
                        return;
                    }

                    bootstrap.Collapse.getOrCreateInstance(body, { toggle: false }).show();

                    window.setTimeout(function () {
                        row.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    }, 120);
                }

                function nodeLabel(row) {
                    const labelInput = row?.querySelector('input[name$="[label]"]');
                    const label = labelInput?.value?.trim() || row?.dataset.nodeLabel || 'Chart box';

                    return label;
                }

                function showDragMessage(message, type = 'info') {
                    if (!dragMessage) {
                        return;
                    }

                    dragMessage.className = `alert alert-${type} py-2 px-3`;
                    dragMessage.textContent = message;

                    window.clearTimeout(showDragMessage.timer);
                    showDragMessage.timer = window.setTimeout(function () {
                        dragMessage.classList.add('d-none');
                    }, 4500);
                }

                function parentSelect(row) {
                    return row?.querySelector('select[name$="[parent_id]"]');
                }

                function sortInput(row) {
                    return row?.querySelector('input[name$="[sort_order]"]');
                }

                function refreshSiblingSortOrders(parentId) {
                    const rows = Array.from(list.querySelectorAll('[data-org-node-row]'));
                    let sortOrder = 1;

                    rows.forEach(function (row) {
                        const select = parentSelect(row);

                        if (!select || String(select.value || '') !== String(parentId || '')) {
                            return;
                        }

                        const input = sortInput(row);
                        if (input) {
                            input.value = sortOrder;
                        }

                        sortOrder++;
                    });
                }

                function clearDropTargets() {
                    list.querySelectorAll('.is-drop-target').forEach(function (row) {
                        row.classList.remove('is-drop-target');
                    });
                    rootDropZone?.classList.remove('is-drop-target');
                }

                function updateParentByDrop(row, parentId, parentLabel) {
                    const select = parentSelect(row);

                    if (!select) {
                        showDragMessage('This box cannot be moved right now.', 'warning');
                        return false;
                    }

                    if (parentId && String(row.dataset.nodeId || '') === String(parentId)) {
                        showDragMessage('A chart box cannot report to itself.', 'warning');
                        return false;
                    }

                    if (parentId && !Array.from(select.options).some((option) => option.value === String(parentId))) {
                        showDragMessage('Save the target box first before using it as a parent.', 'warning');
                        return false;
                    }

                    select.value = parentId || '';

                    if (parentId) {
                        showDragMessage(`${nodeLabel(row)} now reports to ${parentLabel}.`);
                    } else {
                        showDragMessage(`${nodeLabel(row)} is now top level.`);
                    }

                    refreshSiblingSortOrders(parentId || '');

                    return true;
                }

                initializeEmployeePickers(document);
                initializeNodeCollapseButtons(document);
                openNodeFromHash();

                addButton.addEventListener('click', function () {
                    const html = template.innerHTML.replaceAll('__INDEX__', String(nextIndex));
                    const wrapper = document.createElement('div');

                    wrapper.innerHTML = html.trim();

                    const row = wrapper.firstElementChild;
                    enableFields(row);

                    const emptyMessage = list.querySelector('[data-empty-node-message]');
                    if (emptyMessage) {
                        emptyMessage.remove();
                    }

                    list.appendChild(row);
                    initializeEmployeePickers(row);
                    initializeNodeCollapseButtons(row);
                    row.querySelectorAll('select[name$="[facility_id]"]').forEach(function (select) {
                        window.enhanceFacilitySelect?.(select);
                    });
                    nextIndex++;
                });

                list.addEventListener('click', function (event) {
                    const removeButton = event.target.closest('[data-remove-new-node]');

                    if (!removeButton) {
                        return;
                    }

                    removeButton.closest('[data-org-node-row]')?.remove();
                });

                list.addEventListener('dragstart', function (event) {
                    const handle = event.target.closest('[data-org-node-drag-handle]');

                    if (!handle) {
                        return;
                    }

                    const row = handle.closest('[data-org-node-row]');

                    if (!row?.dataset.nodeId) {
                        event.preventDefault();
                        showDragMessage('Save this box before dragging it.', 'warning');
                        return;
                    }

                    draggedRow = row;
                    row.classList.add('is-dragging');
                    event.dataTransfer.effectAllowed = 'move';
                    event.dataTransfer.setData('text/plain', row.dataset.nodeId);
                });

                list.addEventListener('dragover', function (event) {
                    if (!draggedRow) {
                        return;
                    }

                    const targetRow = event.target.closest('[data-org-node-row]');

                    if (!targetRow || targetRow === draggedRow || !targetRow.dataset.nodeId) {
                        return;
                    }

                    event.preventDefault();
                    clearDropTargets();
                    targetRow.classList.add('is-drop-target');
                });

                list.addEventListener('drop', function (event) {
                    if (!draggedRow) {
                        return;
                    }

                    const targetRow = event.target.closest('[data-org-node-row]');

                    if (!targetRow || targetRow === draggedRow || !targetRow.dataset.nodeId) {
                        return;
                    }

                    event.preventDefault();

                    if (updateParentByDrop(draggedRow, targetRow.dataset.nodeId, nodeLabel(targetRow))) {
                        targetRow.after(draggedRow);
                        refreshSiblingSortOrders(targetRow.dataset.nodeId);
                    }

                    clearDropTargets();
                });

                list.addEventListener('dragend', function () {
                    draggedRow?.classList.remove('is-dragging');
                    draggedRow = null;
                    clearDropTargets();
                });

                rootDropZone?.addEventListener('dragover', function (event) {
                    if (!draggedRow) {
                        return;
                    }

                    event.preventDefault();
                    clearDropTargets();
                    rootDropZone.classList.add('is-drop-target');
                });

                rootDropZone?.addEventListener('drop', function (event) {
                    if (!draggedRow) {
                        return;
                    }

                    event.preventDefault();

                    if (updateParentByDrop(draggedRow, '', 'Top level')) {
                        list.prepend(draggedRow);
                        refreshSiblingSortOrders('');
                    }

                    clearDropTargets();
                });

                document.addEventListener('click', function (event) {
                    document.querySelectorAll('[data-org-employee-picker]').forEach(function (picker) {
                        if (!picker.contains(event.target)) {
                            clearEmployeeResults(picker);
                        }
                    });
                });
            });
        </script>
    @endpush
@endif
