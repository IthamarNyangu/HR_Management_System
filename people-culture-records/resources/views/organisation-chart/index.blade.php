@extends('layouts.app')

@php
    $pageTitle = $pageTitle ?? 'Reporting Structure';
    $indexRoute = $indexRoute ?? 'employees.reporting-structure';
    $linkRoute = $linkRoute ?? 'employees.reporting-structure.link-line-managers';
@endphp

@section('title', $pageTitle)
@section('page-title', $pageTitle)

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('employees.index') }}">Employees</a></li>
    <li class="breadcrumb-item active" aria-current="page">{{ $pageTitle }}</li>
@endsection

@push('styles')
    <style>
        .org-chart-shell {
            overflow-x: auto;
        }

        .org-tree {
            min-width: 720px;
            width: max-content;
        }

        .org-node {
            width: max-content;
            margin-left: calc(var(--org-level) * 1.5rem);
            position: relative;
        }

        .org-node + .org-node {
            margin-top: .85rem;
        }

        .org-card {
            width: 34rem;
            min-width: 34rem;
            max-width: 34rem;
            border: 1px solid #d8e0ec;
            border-radius: .5rem;
            background: #fff;
            padding: 1rem;
            box-shadow: 0 .25rem .9rem rgba(23, 32, 51, .04);
        }

        .org-card-header {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            gap: .75rem;
            align-items: start;
        }

        .org-direct-badge {
            white-space: normal;
            text-align: center;
            line-height: 1.2;
            max-width: 8.5rem;
        }

        .org-children {
            margin: .85rem 0 0 1.25rem;
            padding-left: 1rem;
            border-left: 2px solid #d8e0ec;
        }

        .org-branch {
            width: max-content;
            margin-top: .65rem;
        }

        .org-toggle {
            display: inline-flex;
            align-items: center;
            gap: .5rem;
            min-height: 2rem;
            padding: .25rem .65rem;
            border: 1px solid #d1d5db;
            border-radius: .375rem;
            background: #fff;
            color: #374151;
            font-size: .875rem;
            font-weight: 500;
            cursor: pointer;
            user-select: none;
            transition: background-color .15s ease, border-color .15s ease, color .15s ease;
        }

        .org-toggle:hover,
        .org-toggle:focus {
            border-color: #2563eb;
            color: #1d4ed8;
            background: #eff6ff;
            outline: none;
        }

        .org-toggle::-webkit-details-marker {
            display: none;
        }

        .org-toggle::marker {
            content: '';
        }

        .org-toggle-icon::before {
            content: '+';
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 1rem;
            height: 1rem;
            border-radius: 999px;
            background: #e5edff;
            color: #2563eb;
            font-weight: 700;
            line-height: 1;
        }

        .org-branch[open] > .org-toggle .org-toggle-icon::before {
            content: '-';
        }

        .org-toggle-hide {
            display: none;
        }

        .org-branch[open] > .org-toggle .org-toggle-show {
            display: none;
        }

        .org-branch[open] > .org-toggle .org-toggle-hide {
            display: inline;
        }

        .org-meta {
            display: grid;
            grid-template-columns: minmax(6rem, auto) minmax(0, 1fr);
            gap: .3rem .75rem;
            font-size: .9rem;
        }

        .org-meta dt {
            color: #64748b;
            font-weight: 600;
        }

        .org-meta dd {
            margin-bottom: 0;
            color: #1f2937;
            min-width: 0;
            overflow-wrap: anywhere;
        }

        @media (max-width: 640px) {
            .org-tree {
                min-width: 34rem;
            }

            .org-node {
                margin-left: calc(var(--org-level) * .75rem);
            }

            .org-card-header {
                grid-template-columns: 1fr;
            }

            .org-direct-badge {
                justify-self: start;
            }
        }
    </style>
@endpush

@section('content')
    <form method="GET" action="{{ route($indexRoute) }}" class="bg-white border rounded-2 p-3 mb-3">
        <div class="row g-3 align-items-end">
            <div class="col-md-6 col-xl-2">
                <input name="search" type="search" class="form-control" value="{{ request('search') }}" placeholder="Search employee, title, location, line manager">
            </div>
            <div class="col-md-6 col-xl-2">
                <select name="province_id" class="form-select">
                    <option value="">All provinces</option>
                    @foreach ($provinces as $province)
                        <option value="{{ $province->id }}" @selected((string) request('province_id') === (string) $province->id)>{{ $province->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6 col-xl-2">
                <select name="department_id" class="form-select">
                    <option value="">All departments</option>
                    @foreach ($departments as $department)
                        <option value="{{ $department->id }}" @selected((string) request('department_id') === (string) $department->id)>{{ $department->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6 col-xl-2">
                <select name="project_id" class="form-select">
                    <option value="">All projects</option>
                    @foreach ($projects as $project)
                        <option value="{{ $project->id }}" @selected((string) request('project_id') === (string) $project->id)>{{ $project->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6 col-xl-2">
                <select name="facility_id" class="form-select">
                    <option value="">All facilities</option>
                    @foreach ($facilities as $facility)
                        <option value="{{ $facility->id }}" @selected((string) request('facility_id') === (string) $facility->id)>{{ $facility->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-xl-2 d-flex flex-wrap gap-2 justify-content-start justify-content-xl-end">
                <button type="submit" class="btn btn-primary btn-md">Filter</button>
                <a href="{{ route($indexRoute) }}" class="btn btn-secondary btn-md">Reset</a>
            </div>
        </div>
    </form>

    @if ($hasFilters)
        <div class="row g-3 mb-3">
            <div class="col-md-6 col-xl-3">
                <section class="bg-white border rounded-2 p-3 h-100">
                    <div class="text-muted">Visible Employees</div>
                    <div class="display-6 fw-semibold">{{ $stats['visible_employees'] }}</div>
                </section>
            </div>
            <div class="col-md-6 col-xl-3">
                <section class="bg-white border rounded-2 p-3 h-100">
                    <div class="text-muted">Linked Line Managers</div>
                    <div class="display-6 fw-semibold">{{ $stats['linked_supervisors'] }}</div>
                </section>
            </div>
            <div class="col-md-6 col-xl-3">
                <section class="bg-white border rounded-2 p-3 h-100">
                    <div class="text-muted">Unlinked Line Managers</div>
                    <div class="display-6 fw-semibold">{{ $stats['text_only_supervisors'] }}</div>
                </section>
            </div>
            <div class="col-md-6 col-xl-3">
                <section class="bg-white border rounded-2 p-3 h-100">
                    <div class="text-muted">Top-level Records</div>
                    <div class="display-6 fw-semibold">{{ $stats['top_level'] }}</div>
                </section>
            </div>
        </div>
    @endif

    <section class="bg-white border rounded-2 p-4">
        <div class="d-flex flex-column flex-lg-row justify-content-between gap-3 mb-3">
            <div>
                <h2 class="h5 mb-1">Reporting Structure</h2>
            </div>
            <div class="d-flex flex-wrap gap-2 align-self-start">
                @if (auth()->user()->isAdmin() || auth()->user()->isHrManager())
                    <form method="POST" action="{{ route($linkRoute) }}" data-confirm="true" data-confirm-title="Auto-link line managers?" data-confirm-message="The system will match unlinked line manager text to employee records using the employee number at the start of the line manager field. Unmatched records will remain unlinked. Do you want to continue?" data-confirm-button="Auto-link line managers">
                        @csrf
                        <button type="submit" class="btn btn-primary-outline btn-md">Auto-link Line Managers</button>
                    </form>
                @endif
                <a href="{{ route('employees.index') }}" class="btn btn-secondary btn-md">Manage Employees</a>
            </div>
        </div>

        @if (! $hasFilters)
            <div class="text-center text-muted py-5">
                <div class="h5 text-body mb-2">Search or apply a filter to view the reporting structure.</div>
                <p class="mb-0">Start with an employee name, employee number, province, department, project, or facility.</p>
            </div>
        @elseif ($rootEmployees->count() === 0)
            <div class="text-center text-muted py-5">No employees found for the selected filters.</div>
        @else
            <div class="org-chart-shell">
                <div class="org-tree">
                    @foreach ($rootEmployees as $employee)
                        @include('organisation-chart._node', ['employee' => $employee, 'childrenBySupervisor' => $childrenBySupervisor, 'level' => 0])
                    @endforeach
                </div>
            </div>
            <div class="mt-3">
                {{ $rootEmployees->links() }}
            </div>
        @endif
    </section>
@endsection
