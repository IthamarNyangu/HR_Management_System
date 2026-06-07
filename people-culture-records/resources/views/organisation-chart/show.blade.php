@extends('layouts.app')

@section('title', $organisationChart->title)
@section('page-title', $organisationChart->title)

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('organisation-chart.index') }}">Organisation Chart</a></li>
    <li class="breadcrumb-item active" aria-current="page">{{ $organisationChart->title }}</li>
@endsection

@section('page-actions')
    <div class="d-flex flex-wrap gap-2">
        @can('update', $organisationChart)
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
        }

        .formal-org-card-link {
            color: inherit;
            text-decoration: none;
            width: 100%;
            min-height: 100%;
            padding: .85rem .7rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
            gap: .15rem;
        }

        a.formal-org-card-link:hover,
        a.formal-org-card-link:focus {
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

        .formal-org-children::before {
            content: '';
            position: absolute;
            top: -1.15rem;
            left: 0;
            right: 0;
            height: 1px;
            background: #1f2937;
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

        .formal-org-node:has(> .formal-org-children)::after {
            content: '';
            position: absolute;
            top: 5.75rem;
            left: 50%;
            width: 1px;
            height: 1.2rem;
            background: #1f2937;
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

        .formal-org-legend {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(11rem, 1fr));
            gap: .75rem;
        }

        .formal-org-legend-swatch {
            width: 1.75rem;
            height: 1.25rem;
            border: 3px solid #9f2d14;
            background: #fff;
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
            <div class="col-lg-8">
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
            <div class="col-lg-4">
                <div class="formal-org-legend">
                    @foreach ($nodeTypes as $value => $label)
                        <div class="d-flex align-items-center gap-2 small">
                            <span class="formal-org-legend-swatch {{ [
                                'central_head_office' => 'org-node-central',
                                'regional_level' => 'org-node-regional',
                                'provincial_level' => 'org-node-provincial',
                                'hub_facility' => 'org-node-hub',
                                'key_position' => 'org-node-key',
                                'external_partner' => 'org-node-external',
                                'support_unit' => 'org-node-support',
                            ][$value] ?? 'org-node-support' }}"></span>
                            <span>{{ $label }}</span>
                        </div>
                    @endforeach
                </div>
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
                <div class="formal-org-canvas">
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
@endsection
