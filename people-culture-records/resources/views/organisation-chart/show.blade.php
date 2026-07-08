@extends('layouts.app')

@php
    $chartDisplayTitle = Str::contains(Str::lower($organisationChart->title), 'management overview')
        ? $organisationChart->title
        : trim($organisationChart->title.' Management Overview');
    $chartDownloadFilename = Str::slug($chartDisplayTitle.' org-chart').'.png';
@endphp

@section('title', $chartDisplayTitle)
@section('page-title', $chartDisplayTitle)

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('organisation-chart.index') }}">Organisation Chart</a></li>
    <li class="breadcrumb-item active" aria-current="page">{{ $chartDisplayTitle }}</li>
@endsection

@section('page-actions')
    <div class="d-flex flex-wrap gap-2">
        @if ($rootNodes->isNotEmpty())
            <button type="button" class="btn btn-secondary btn-md" data-download-org-chart-png data-chart-filename="{{ $chartDownloadFilename }}">
                <i class="bi bi-download" aria-hidden="true"></i>
                Download PNG
            </button>
        @endif
        @can('update', $organisationChart)
            <a href="{{ route('organisation-chart.designer', $organisationChart) }}" class="btn btn-primary-outline btn-md">Open Designer</a>
            <a href="{{ route('organisation-chart.edit', $organisationChart) }}" class="btn btn-primary btn-md">Edit Chart</a>
        @endcan
        <a href="{{ route('organisation-chart.index') }}" class="btn btn-secondary btn-md">Back</a>
    </div>
@endsection

@push('styles')
    <style>
        .formal-org-shell {
            overflow-x: auto;
            padding-bottom: .5rem;
        }

        .formal-org-canvas {
            min-width: 960px;
            width: max-content;
            padding: 1rem;
            margin-inline: auto;
        }

        .formal-org-node {
            display: inline-flex;
            flex-direction: column;
            align-items: center;
            position: relative;
        }

        .formal-org-card {
            width: 13.5rem;
            min-height: 5.75rem;
            border: 3px solid #9f2d14;
            background: #fff;
            color: #111827;
            display: flex;
            align-items: stretch;
            justify-content: center;
            text-align: center;
            box-shadow: 0 .25rem .9rem rgba(15, 23, 42, .05);
            position: relative;
        }

        .formal-org-card-link {
            color: inherit;
            text-decoration: none;
            border: 0;
            background: transparent;
            width: 100%;
            min-height: 100%;
            padding: .85rem .7rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
            gap: .15rem;
            cursor: pointer;
        }

        .formal-org-card-link:hover,
        .formal-org-card-link:focus {
            box-shadow: 0 0 0 .25rem rgba(37, 99, 235, .14);
            outline: 2px solid #2563eb;
            outline-offset: 3px;
        }

        .formal-org-label {
            font-weight: 700;
            line-height: 1.15;
            overflow-wrap: anywhere;
        }

        .formal-org-subtitle,
        .formal-org-count,
        .formal-org-linked {
            font-size: .85rem;
            line-height: 1.2;
        }

        .formal-org-linked {
            color: #4b5563;
            margin-top: .2rem;
        }

        .formal-org-children {
            display: flex;
            align-items: flex-start;
            justify-content: center;
            gap: 1.25rem;
            margin-top: 2.35rem;
            position: relative;
        }

        .formal-org-node > .formal-org-children > .formal-org-node::before {
            content: '';
            position: absolute;
            top: -1.15rem;
            left: 50%;
            width: 1px;
            height: 1.15rem;
            background: #1f2937;
        }

        .formal-org-node > .formal-org-children:has(> .formal-org-node + .formal-org-node)::before {
            content: '';
            position: absolute;
            top: -1.15rem;
            left: var(--connector-left, 50%);
            width: calc(var(--connector-right, 50%) - var(--connector-left, 50%));
            height: 1px;
            background: #1f2937;
            pointer-events: none;
        }

        .formal-org-node:has(> .formal-org-children) > .formal-org-card::after {
            content: '';
            position: absolute;
            top: 100%;
            left: 50%;
            width: 1px;
            height: 1.2rem;
            background: #1f2937;
            pointer-events: none;
        }

        .org-node-key {
            background: #8f260f;
            border-color: #9f2d14;
            color: #fff;
        }

        .org-node-central {
            border-color: #9f2d14;
        }

        .org-node-regional {
            border-color: #d97706;
        }

        .org-node-provincial {
            border-color: #8b6f61;
        }

        .org-node-hub {
            border-color: #b87838;
        }

        .org-node-external {
            border-color: #111827;
        }

        .org-node-support {
            border-color: #9f2d14;
        }

        @media (max-width: 768px) {
            .formal-org-canvas {
                min-width: 42rem;
                margin-inline: 0;
            }

            .formal-org-card {
                width: 12rem;
            }
        }
    </style>
@endpush

@section('content')
    <section class="bg-white border rounded-2 p-4 mb-3">
        <div class="row g-3 align-items-start">
            <div class="col-lg-12">
                <div class="d-flex flex-wrap gap-2 mb-2">
                    <span class="badge text-bg-{{ $organisationChart->status === 'published' ? 'success' : 'secondary' }}">{{ ucfirst($organisationChart->status) }}</span>
                    <span class="badge text-bg-light border">{{ $organisationChart->project?->name ?? 'Organisation-wide' }}</span>
                    @if ($organisationChart->effective_date)
                        <span class="badge text-bg-light border">Effective {{ $organisationChart->effective_date->format('d M Y') }}</span>
                    @endif
                </div>
                @if ($organisationChart->description)
                    <p class="text-muted mb-0">{{ $organisationChart->description }}</p>
                @endif
            </div>
        </div>
    </section>

    <section class="bg-white border rounded-2 p-4">
        @if ($rootNodes->isEmpty())
            <div class="text-center text-muted py-5">
                <div class="h5 text-body mb-2">No chart boxes added yet.</div>
                @can('update', $organisationChart)
                    <a href="{{ route('organisation-chart.edit', $organisationChart) }}" class="btn btn-primary btn-md mt-2">Add Boxes</a>
                @endcan
            </div>
        @else
            <div class="formal-org-shell">
                <div class="formal-org-canvas" data-org-chart-canvas>
                    <div class="formal-org-children" style="margin-top: 0;">
                        @foreach ($rootNodes as $node)
                            @include('organisation-chart._chart-node', [
                                'node' => $node,
                                'childrenByParent' => $childrenByParent,
                                'organisationChart' => $organisationChart,
                            ])
                        @endforeach
                    </div>
                </div>
            </div>
        @endif
    </section>

    <div class="modal fade" id="orgNodeActionModal" tabindex="-1" aria-labelledby="orgNodeActionModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h2 class="modal-title h5" id="orgNodeActionModalLabel">Chart Box</h2>
                        <div class="small text-muted" data-org-node-modal-subtitle></div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted mb-3">What would you like to do with this chart box?</p>
                    <div class="d-grid gap-2">
                        <a href="#" class="btn btn-secondary btn-md justify-content-start d-none" data-org-node-employee-link>
                            <i class="bi bi-person-vcard" aria-hidden="true"></i>
                            View Employee Profile
                        </a>
                        <a href="#" class="btn btn-secondary btn-md justify-content-start d-none" data-org-node-filter-link>
                            <i class="bi bi-people" aria-hidden="true"></i>
                            View Matching Employees
                        </a>
                        @can('update', $organisationChart)
                            <a href="#" class="btn btn-primary-outline btn-md justify-content-start" data-org-node-edit-link>
                                <i class="bi bi-pencil-square" aria-hidden="true"></i>
                                Edit Box
                            </a>
                            <form method="POST" action="#" data-org-node-duplicate-form data-confirm="true" data-confirm-title="Duplicate chart box?" data-confirm-message="This will create a copied box without the linked employee. You should review and update the copied details before publishing. Do you want to continue?" data-confirm-button="Duplicate box">
                                @csrf
                                <button type="submit" class="btn btn-secondary btn-md justify-content-start w-100">
                                    <i class="bi bi-copy" aria-hidden="true"></i>
                                    Duplicate Box
                                </button>
                            </form>
                            <form method="POST" action="#" data-org-node-delete-form data-confirm="true" data-confirm-title="Delete chart box?" data-confirm-message="This chart box will be removed. Any boxes below it will be moved to the top level so the chart is not broken. Do you want to continue?" data-confirm-button="Delete box" data-confirm-variant="btn-danger">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-outline-danger btn-md justify-content-start w-100">
                                    <i class="bi bi-trash" aria-hidden="true"></i>
                                    Delete Box
                                </button>
                            </form>
                        @endcan
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-md" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/html-to-image@1.11.11/dist/html-to-image.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const downloadButton = document.querySelector('[data-download-org-chart-png]');
            const chartCanvas = document.querySelector('[data-org-chart-canvas]');
            const modalElement = document.getElementById('orgNodeActionModal');

            function alignChartConnectors(rootElement = document) {
                rootElement.querySelectorAll('.formal-org-children').forEach(function (container) {
                    const nodes = Array.from(container.querySelectorAll(':scope > .formal-org-node'));

                    if (nodes.length < 2) {
                        container.style.removeProperty('--connector-left');
                        container.style.removeProperty('--connector-right');
                        return;
                    }

                    const containerRect = container.getBoundingClientRect();
                    const firstCard = nodes[0].querySelector(':scope > .formal-org-card') || nodes[0];
                    const lastCard = nodes[nodes.length - 1].querySelector(':scope > .formal-org-card') || nodes[nodes.length - 1];
                    const firstRect = firstCard.getBoundingClientRect();
                    const lastRect = lastCard.getBoundingClientRect();
                    const left = firstRect.left + (firstRect.width / 2) - containerRect.left;
                    const right = lastRect.left + (lastRect.width / 2) - containerRect.left;

                    container.style.setProperty('--connector-left', `${left}px`);
                    container.style.setProperty('--connector-right', `${right}px`);
                });
            }

            function waitForPaint() {
                return new Promise(function (resolve) {
                    requestAnimationFrame(function () {
                        requestAnimationFrame(resolve);
                    });
                });
            }

            function buildExportCanvas() {
                if (!chartCanvas) {
                    return null;
                }

                const wrapper = document.createElement('div');
                const header = document.createElement('div');
                const title = document.createElement('div');
                const subtitle = document.createElement('div');
                const clonedCanvas = chartCanvas.cloneNode(true);
                const chartWidth = Math.ceil(Math.max(chartCanvas.scrollWidth, chartCanvas.getBoundingClientRect().width, 960));

                wrapper.dataset.orgChartExportSurface = 'true';
                wrapper.style.position = 'fixed';
                wrapper.style.left = '0';
                wrapper.style.top = '0';
                wrapper.style.zIndex = '2147483647';
                wrapper.style.background = '#ffffff';
                wrapper.style.color = '#111827';
                wrapper.style.padding = '24px';
                wrapper.style.width = `${chartWidth + 48}px`;
                wrapper.style.minHeight = '1px';
                wrapper.style.pointerEvents = 'none';
                wrapper.style.boxShadow = '0 0 0 9999px rgba(255, 255, 255, .96)';

                header.style.marginBottom = '18px';
                title.style.fontSize = '22px';
                title.style.fontWeight = '700';
                title.style.lineHeight = '1.2';
                title.textContent = @json($chartDisplayTitle);
                subtitle.style.color = '#4b5563';
                subtitle.style.fontSize = '14px';
                subtitle.style.marginTop = '4px';
                subtitle.textContent = @json(collect([
                    $organisationChart->project?->name ?? 'Organisation-wide',
                    $organisationChart->effective_date ? 'Effective '.$organisationChart->effective_date->format('d M Y') : null,
                ])->filter()->implode(' | '));

                clonedCanvas.style.margin = '0';
                clonedCanvas.style.padding = '1rem';
                clonedCanvas.style.width = `${chartWidth}px`;
                clonedCanvas.style.minWidth = `${chartWidth}px`;

                header.appendChild(title);
                header.appendChild(subtitle);
                wrapper.appendChild(header);
                wrapper.appendChild(clonedCanvas);
                document.body.appendChild(wrapper);
                alignChartConnectors(wrapper);

                return wrapper;
            }

            function downloadPng(dataUrl, filename) {
                const link = document.createElement('a');

                link.download = filename || 'organisation-chart.png';
                link.href = dataUrl;
                document.body.appendChild(link);
                link.click();
                link.remove();
            }

            downloadButton?.addEventListener('click', async function () {
                if (!window.htmlToImage || !chartCanvas) {
                    alert('PNG download is not ready. Please check your internet connection and refresh the page.');
                    return;
                }

                const originalText = downloadButton.innerHTML;
                let exportCanvas = null;

                try {
                    downloadButton.disabled = true;
                    downloadButton.innerHTML = '<span class="spinner-border spinner-border-sm" aria-hidden="true"></span> Preparing PNG';

                    if (document.fonts?.ready) {
                        await document.fonts.ready;
                    }

                    exportCanvas = buildExportCanvas();
                    await waitForPaint();

                    const exportWidth = Math.ceil(exportCanvas.scrollWidth || exportCanvas.getBoundingClientRect().width);
                    const exportHeight = Math.ceil(exportCanvas.scrollHeight || exportCanvas.getBoundingClientRect().height);

                    const dataUrl = await window.htmlToImage.toPng(exportCanvas, {
                        backgroundColor: '#ffffff',
                        cacheBust: true,
                        pixelRatio: 2,
                        width: exportWidth,
                        height: exportHeight,
                        canvasWidth: exportWidth * 2,
                        canvasHeight: exportHeight * 2,
                        style: {
                            margin: '0',
                        },
                    });

                    if (!dataUrl || dataUrl.length < 2000) {
                        throw new Error('The generated PNG was empty.');
                    }

                    downloadPng(dataUrl, downloadButton.dataset.chartFilename || 'organisation-chart.png');
                } catch (error) {
                    alert('The chart could not be downloaded as PNG. Please try again after the page finishes loading.');
                } finally {
                    exportCanvas?.remove();
                    downloadButton.disabled = false;
                    downloadButton.innerHTML = originalText;
                }
            });

            if (!modalElement) {
                return;
            }

            const modal = new bootstrap.Modal(modalElement);
            const title = modalElement.querySelector('#orgNodeActionModalLabel');
            const subtitle = modalElement.querySelector('[data-org-node-modal-subtitle]');
            const employeeLink = modalElement.querySelector('[data-org-node-employee-link]');
            const filterLink = modalElement.querySelector('[data-org-node-filter-link]');
            const editLink = modalElement.querySelector('[data-org-node-edit-link]');
            const duplicateForm = modalElement.querySelector('[data-org-node-duplicate-form]');
            const deleteForm = modalElement.querySelector('[data-org-node-delete-form]');

            function toggleLink(link, url) {
                if (!link) {
                    return;
                }

                if (url) {
                    link.href = url;
                    link.classList.remove('d-none');
                    return;
                }

                link.href = '#';
                link.classList.add('d-none');
            }

            document.querySelectorAll('[data-org-node-action]').forEach(function (button) {
                button.addEventListener('click', function () {
                    title.textContent = button.dataset.nodeLabel || 'Chart Box';
                    subtitle.textContent = button.dataset.nodeSubtitle || '';
                    toggleLink(employeeLink, button.dataset.employeeUrl || '');
                    toggleLink(filterLink, button.dataset.filterUrl || '');

                    if (editLink) {
                        editLink.href = button.dataset.editUrl || '#';
                    }

                    if (duplicateForm) {
                        duplicateForm.action = button.dataset.duplicateUrl || '#';
                    }

                    if (deleteForm) {
                        deleteForm.action = button.dataset.deleteUrl || '#';
                    }

                    modal.show();
                });
            });

            duplicateForm?.addEventListener('submit', function () {
                modal.hide();
            });

            deleteForm?.addEventListener('submit', function () {
                modal.hide();
            });

            requestAnimationFrame(function () {
                alignChartConnectors();
            });

            if (document.fonts?.ready) {
                document.fonts.ready.then(function () {
                    alignChartConnectors();
                });
            }

            window.addEventListener('resize', function () {
                alignChartConnectors();
            });
        });
    </script>
@endpush
