@php
    $distributionMaximum = max(1, collect($rows)->max('total') ?? 0);
@endphp
<div class="insight-panel">
    <div class="insight-heading">
        <div>
            <h3>{{ $title }}</h3>
            @isset($subtitle)<span>{{ $subtitle }}</span>@endisset
        </div>
    </div>
    <div class="distribution-chart">
        @forelse ($rows as $row)
            @php
                $rawLabel = is_array($row) ? $row['label'] : $row->label;
                $total = is_array($row) ? $row['total'] : $row->total;
                $displayLabel = $labels[$rawLabel] ?? str($rawLabel)->headline();
                $width = max(4, round(($total / $distributionMaximum) * 100));
            @endphp
            <div class="distribution-row">
                <div class="distribution-meta"><span>{{ $displayLabel }}</span><strong>{{ $total }}</strong></div>
                <div class="distribution-track"><span style="width: {{ $width }}%"></span></div>
            </div>
        @empty
            <div class="empty-insight">No data available yet.</div>
        @endforelse
    </div>
</div>
