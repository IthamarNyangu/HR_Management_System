@extends('layouts.app')

@section('title', 'Designer - '.$organisationChart->title)
@section('page-title', 'Organisation Chart Designer')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('organisation-chart.index') }}">Organisation Chart</a></li>
    <li class="breadcrumb-item"><a href="{{ route('organisation-chart.show', $organisationChart) }}">{{ $organisationChart->title }}</a></li>
    <li class="breadcrumb-item active" aria-current="page">Designer</li>
@endsection

@section('page-actions')
    <div class="d-flex flex-wrap gap-2">
        <button type="submit" form="organisationChartLayoutForm" class="btn btn-primary btn-md">Save Layout</button>
        <a href="{{ route('organisation-chart.edit', $organisationChart) }}" class="btn btn-primary-outline btn-md">Edit Box Details</a>
        <a href="{{ route('organisation-chart.show', $organisationChart) }}" class="btn btn-secondary btn-md">Back to Chart</a>
    </div>
@endsection

@push('styles')
    <style>
        .designer-canvas-shell {
            overflow: auto;
            min-height: 32rem;
            padding-bottom: 1rem;
            scroll-padding-left: 2rem;
        }
        .designer-root-zone {
            border: 1px dashed #a8b4c5;
            border-radius: .5rem;
            background: #f8fafc;
            padding: .85rem 1rem;
            color: #475467;
        }
        .designer-root {
            display: flex;
            align-items: flex-start;
            justify-content: flex-start;
            gap: 1rem;
            width: max-content;
            min-width: 100%;
            padding: 1.5rem 2rem 2rem;
        }
        .designer-node {
            display: inline-flex;
            flex-direction: column;
            align-items: center;
            position: relative;
        }
        .designer-card-wrap {
            display: flex;
            flex-direction: column;
            align-items: stretch;
            gap: .55rem;
        }
        .designer-card {
            width: 15rem;
            min-height: 6.5rem;
            border: 3px solid #9f2d14;
            border-radius: .25rem;
            background: #fff;
            padding: .85rem;
            box-shadow: 0 .25rem .9rem rgba(15, 23, 42, .06);
            cursor: grab;
            transition: border-color .15s ease, box-shadow .15s ease, transform .15s ease, background .15s ease;
        }
        .designer-card:hover,
        .designer-card:focus {
            box-shadow: 0 .7rem 1.3rem rgba(15, 23, 42, .12);
            transform: translateY(-1px);
        }
        .designer-card-title {
            font-weight: 700;
            line-height: 1.2;
            overflow-wrap: anywhere;
        }
        .designer-card-actions {
            display: flex;
            flex-wrap: wrap;
            gap: .4rem;
        }
        .designer-card-actions .btn {
            flex: 1 1 0;
        }
        .designer-drop-actions {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: .4rem;
        }
        .designer-drop-zone {
            border: 1px dashed #a8b4c5;
            border-radius: .375rem;
            background: #f8fafc;
            color: #475467;
            min-height: 2rem;
            padding: .35rem .45rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: .78rem;
            font-weight: 600;
            text-align: center;
        }
        .designer-children {
            display: flex;
            align-items: flex-start;
            justify-content: center;
            gap: 1rem;
            min-height: 2rem;
            margin-top: 2rem;
            padding-top: 1rem;
            position: relative;
        }
        .designer-children:has(> .designer-node)::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 1px;
            background: #1f2937;
        }
        .designer-node > .designer-children > .designer-node::before {
            content: '';
            position: absolute;
            top: -1rem;
            left: 50%;
            width: 1px;
            height: 1rem;
            background: #1f2937;
        }
        .designer-node:has(> .designer-children > .designer-node)::after {
            content: '';
            position: absolute;
            top: 9.1rem;
            left: 50%;
            width: 1px;
            height: 1rem;
            background: #1f2937;
        }
        .designer-node.is-dragging {
            opacity: .5;
        }
        .designer-card.is-drop-target,
        .designer-root-zone.is-drop-target,
        .designer-drop-zone.is-drop-target {
            border-color: #2563eb !important;
            background: #eff6ff !important;
            box-shadow: 0 0 0 .2rem rgba(37, 99, 235, .12);
        }
        .org-node-key {
            background: #8f260f;
            border-color: #9f2d14;
            color: #fff;
        }
        .org-node-central { border-color: #9f2d14; }
        .org-node-regional { border-color: #d97706; }
        .org-node-provincial { border-color: #8b6f61; }
        .org-node-hub { border-color: #b87838; }
        .org-node-external { border-color: #111827; }
        .org-node-support { border-color: #9f2d14; }
    </style>
@endpush

@section('content')
    <section class="bg-white border rounded-2 p-3">
            <div class="d-flex flex-column flex-lg-row justify-content-between gap-2 mb-3">
                <div>
                    <h2 class="h5 mb-1">{{ $organisationChart->title }}</h2>
                    <div class="text-muted small">{{ $organisationChart->project?->name ?? 'Organisation-wide' }}</div>
                    <div class="text-muted small mt-1">Drag a saved box, then use <strong>Drop under</strong> for reporting lines or <strong>Drop beside</strong> to place boxes side by side.</div>
                </div>
                <div class="alert alert-info py-2 px-3 mb-0 d-none" data-designer-message role="status"></div>
            </div>

            <form id="organisationChartLayoutForm" method="POST" action="{{ route('organisation-chart.layout.update', $organisationChart) }}" data-layout-form>
                @csrf
                @method('PATCH')

                <div data-layout-inputs>
                    @foreach ($nodes as $index => $node)
                        <input type="hidden" name="nodes[{{ $index }}][id]" value="{{ $node->id }}" data-layout-id="{{ $node->id }}">
                        <input type="hidden" name="nodes[{{ $index }}][parent_id]" value="{{ $node->parent_id }}" data-layout-parent="{{ $node->id }}">
                        <input type="hidden" name="nodes[{{ $index }}][sort_order]" value="{{ $node->sort_order }}" data-layout-sort="{{ $node->id }}">
                    @endforeach
                </div>

                @if ($nodes->isEmpty())
                    <div class="text-center text-muted py-5">
                        <div class="h5 text-body mb-2">No chart boxes added yet.</div>
                        <a href="{{ route('organisation-chart.edit', $organisationChart) }}" class="btn btn-primary btn-md">Add Boxes</a>
                    </div>
                @else
                    <div class="designer-root-zone mb-3" data-designer-root-zone>
                        Drop a box here to make it top level.
                    </div>
                    <div class="designer-canvas-shell">
                        <div class="designer-root" data-designer-root>
                            @foreach ($rootNodes as $node)
                                @include('organisation-chart._designer-node', [
                                    'node' => $node,
                                    'childrenByParent' => $childrenByParent,
                                    'organisationChart' => $organisationChart,
                                ])
                            @endforeach
                        </div>
                    </div>
                @endif
            </form>
            <form method="POST" action="#" class="d-none" data-designer-duplicate-form data-confirm="true" data-confirm-title="Duplicate chart box?" data-confirm-message="This will create a copied box without the linked employee. Save any unsaved layout changes first, then review the copied details before publishing." data-confirm-button="Duplicate box">
                @csrf
                <input type="hidden" name="redirect_to" value="designer">
            </form>
    </section>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const root = document.querySelector('[data-designer-root]');
            const rootZone = document.querySelector('[data-designer-root-zone]');
            const message = document.querySelector('[data-designer-message]');
            const duplicateForm = document.querySelector('[data-designer-duplicate-form]');
            let draggedNode = null;

            if (!root) {
                return;
            }

            function showMessage(text, type = 'info') {
                if (!message) {
                    return;
                }

                message.className = `alert alert-${type} py-2 px-3 mb-0`;
                message.textContent = text;

                window.clearTimeout(showMessage.timer);
                showMessage.timer = window.setTimeout(function () {
                    message.classList.add('d-none');
                }, 4500);
            }

            function clearDropTargets() {
                document.querySelectorAll('.is-drop-target').forEach(function (element) {
                    element.classList.remove('is-drop-target');
                });
            }

            function updateHiddenInput(nodeId, field, value) {
                const input = document.querySelector(`[data-layout-${field}="${nodeId}"]`);

                if (input) {
                    input.value = value;
                }
            }

            function refreshLayoutInputs() {
                function walk(container, parentId) {
                    Array.from(container.children)
                        .filter((element) => element.matches('[data-designer-node]'))
                        .forEach(function (node, index) {
                            const nodeId = node.dataset.nodeId;
                            updateHiddenInput(nodeId, 'parent', parentId || '');
                            updateHiddenInput(nodeId, 'sort', String(index + 1));

                            const children = node.querySelector(':scope > [data-designer-children]');
                            if (children) {
                                walk(children, nodeId);
                            }
                        });
                }

                walk(root, '');
            }

            function nodeTitle(node) {
                return node.querySelector('.designer-card-title')?.textContent?.trim() || 'Chart box';
            }

            function moveNodeToTarget(targetNode, mode) {
                if (!targetNode || targetNode === draggedNode || draggedNode.contains(targetNode)) {
                    return false;
                }

                if (mode === 'sibling') {
                    targetNode.after(draggedNode);
                    refreshLayoutInputs();
                    showMessage(`${nodeTitle(draggedNode)} is now beside ${nodeTitle(targetNode)}.`);
                    return true;
                }

                targetNode.querySelector(':scope > [data-designer-children]').appendChild(draggedNode);
                refreshLayoutInputs();
                showMessage(`${nodeTitle(draggedNode)} now reports to ${nodeTitle(targetNode)}.`);

                return true;
            }

            document.addEventListener('dragstart', function (event) {
                const card = event.target.closest('[data-designer-card]');

                if (!card) {
                    return;
                }

                draggedNode = card.closest('[data-designer-node]');
                draggedNode.classList.add('is-dragging');
                event.dataTransfer.effectAllowed = 'move';
                event.dataTransfer.setData('text/plain', draggedNode.dataset.nodeId);
            });

            document.addEventListener('dragover', function (event) {
                if (!draggedNode) {
                    return;
                }

                const dropZone = event.target.closest('[data-designer-drop]');

                if (dropZone) {
                    const targetNode = dropZone.closest('[data-designer-node]');

                    if (!targetNode || targetNode === draggedNode || draggedNode.contains(targetNode)) {
                        return;
                    }

                    event.preventDefault();
                    clearDropTargets();
                    dropZone.classList.add('is-drop-target');
                    return;
                }

                const targetCard = event.target.closest('[data-designer-card]');

                if (!targetCard) {
                    return;
                }

                const targetNode = targetCard.closest('[data-designer-node]');

                if (!targetNode || targetNode === draggedNode || draggedNode.contains(targetNode)) {
                    return;
                }

                event.preventDefault();
                clearDropTargets();
                targetCard.classList.add('is-drop-target');
            });

            document.addEventListener('drop', function (event) {
                if (!draggedNode) {
                    return;
                }

                const dropZone = event.target.closest('[data-designer-drop]');

                if (dropZone) {
                    const targetNode = dropZone.closest('[data-designer-node]');

                    event.preventDefault();
                    moveNodeToTarget(targetNode, dropZone.dataset.designerDrop || 'child');
                    clearDropTargets();
                    return;
                }

                const targetCard = event.target.closest('[data-designer-card]');

                if (!targetCard) {
                    return;
                }

                const targetNode = targetCard.closest('[data-designer-node]');

                if (!targetNode || targetNode === draggedNode || draggedNode.contains(targetNode)) {
                    return;
                }

                event.preventDefault();
                moveNodeToTarget(targetNode, 'child');
                clearDropTargets();
            });

            rootZone?.addEventListener('dragover', function (event) {
                if (!draggedNode) {
                    return;
                }

                event.preventDefault();
                clearDropTargets();
                rootZone.classList.add('is-drop-target');
            });

            rootZone?.addEventListener('drop', function (event) {
                if (!draggedNode) {
                    return;
                }

                event.preventDefault();
                root.appendChild(draggedNode);
                refreshLayoutInputs();
                showMessage(`${nodeTitle(draggedNode)} is now top level.`);
                clearDropTargets();
            });

            document.addEventListener('click', function (event) {
                const duplicateButton = event.target.closest('[data-designer-duplicate]');

                if (!duplicateButton || !duplicateForm) {
                    return;
                }

                duplicateForm.action = duplicateButton.dataset.duplicateUrl || '#';
                duplicateForm.requestSubmit();
            });

            document.addEventListener('dragend', function () {
                draggedNode?.classList.remove('is-dragging');
                draggedNode = null;
                clearDropTargets();
            });
        });
    </script>
@endpush
