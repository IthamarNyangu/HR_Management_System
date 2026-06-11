@php
    $query = request()->except(['page', 'per_page']);
    $hasRows = false;

    if (isset($paginator) && method_exists($paginator, 'total')) {
        $hasRows = $paginator->total() > 0;
    } elseif (isset($count)) {
        $hasRows = (int) $count > 0;
    }
@endphp

@can('export-reports')
    @if ($hasRows)
        <a href="{{ route($excelRoute, $query) }}" class="btn btn-primary-outline btn-md">
            <i class="bi bi-file-earmark-excel" aria-hidden="true"></i>
            Export Excel
        </a>
        <a href="{{ route($pdfRoute, $query) }}" class="btn btn-secondary btn-md">
            <i class="bi bi-file-earmark-pdf" aria-hidden="true"></i>
            Export PDF
        </a>
    @else
        <button type="button" class="btn btn-primary-outline btn-md opacity-50" disabled>
            <i class="bi bi-file-earmark-excel" aria-hidden="true"></i>
            Export Excel
        </button>
        <button type="button" class="btn btn-secondary btn-md opacity-50" disabled>
            <i class="bi bi-file-earmark-pdf" aria-hidden="true"></i>
            Export PDF
        </button>
    @endif
@endcan
