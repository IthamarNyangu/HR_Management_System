<section class="bg-white border rounded-2 p-3 mb-4">
    <div class="d-flex flex-column flex-lg-row justify-content-between gap-3 mb-3">
        <div>
            <h2 class="h5 mb-1">{{ $batch->original_filename }}</h2>
            <div class="text-muted small">
                {{ $batch->reference_no }} -
                Uploaded by {{ $batch->uploadedBy?->name ?? 'System' }} -
                {{ $batch->created_at->format('d M Y H:i') }}
            </div>
        </div>
        <span class="badge text-bg-light align-self-start">{{ ucfirst($batch->status) }}</span>
    </div>

    <div class="row row-cols-2 row-cols-md-5 g-3">
        <div class="col"><div class="summary-tile h-100"><div class="summary-label">Total Rows</div><div class="summary-value">{{ $batch->total_rows }}</div></div></div>
        <div class="col"><div class="summary-tile h-100"><div class="summary-label">Valid Rows</div><div class="summary-value text-success">{{ $batch->valid_rows }}</div></div></div>
        <div class="col"><div class="summary-tile h-100"><div class="summary-label">Invalid Rows</div><div class="summary-value text-danger">{{ $batch->invalid_rows }}</div></div></div>
        <div class="col"><div class="summary-tile h-100"><div class="summary-label">Duplicate Rows</div><div class="summary-value text-warning">{{ $batch->duplicate_rows }}</div></div></div>
        <div class="col"><div class="summary-tile h-100"><div class="summary-label">Imported Rows</div><div class="summary-value">{{ $batch->imported_rows }}</div></div></div>
    </div>

    @if (($batch->error_summary['_workbook_warnings'] ?? []) !== [])
        <div class="alert alert-warning small mb-0 mt-3">
            @foreach ($batch->error_summary['_workbook_warnings'] as $warning)
                <div>{{ $warning }}</div>
            @endforeach
        </div>
    @endif
</section>
