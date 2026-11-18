@php
    $donutRows = collect($rows)->map(function ($row) use ($labels) {
        $rawLabel = is_array($row) ? $row['label'] : $row->label;

        return [
            'label' => $labels[$rawLabel] ?? str($rawLabel)->headline(),
            'total' => (int) (is_array($row) ? $row['total'] : $row->total),
        ];
    })->values();
    $donutTotal = $donutRows->sum('total');
    $donutColors = ['#2563eb', '#15803d', '#d97706', '#dc2626', '#0f766e', '#7c3aed', '#475569'];
    $donutOffset = 0;
    $donutStops = [];

    foreach ($donutRows as $index => $row) {
        $start = $donutTotal > 0 ? ($donutOffset / $donutTotal) * 100 : 0;
        $donutOffset += $row['total'];
        $end = $donutTotal > 0 ? ($donutOffset / $donutTotal) * 100 : 0;
        $donutStops[] = $donutColors[$index % count($donutColors)].' '.$start.'% '.$end.'%';
    }

    $donutBackground = $donutTotal > 0 ? implode(', ', $donutStops) : '#e2e8f0 0 100%';
@endphp

<div class="insight-panel">
    <div class="insight-heading">
        <div>
            <h3>{{ $title }}</h3>
            @isset($subtitle)<span>{{ $subtitle }}</span>@endisset
        </div>
        <strong>{{ $donutTotal }}</strong>
    </div>
    <div class="donut-layout">
        <div class="donut-chart" style="background: conic-gradient({{ $donutBackground }})" role="img" aria-label="{{ $title }}">
            <div><strong>{{ $donutTotal }}</strong><span>Total</span></div>
        </div>
        <div class="donut-legend">
            @forelse ($donutRows as $index => $row)
                <div class="donut-legend-item">
                    <span class="donut-swatch" style="background: {{ $donutColors[$index % count($donutColors)] }}"></span>
                    <span>{{ $row['label'] }}</span>
                    <strong>{{ $row['total'] }}</strong>
                </div>
            @empty
                <span class="empty-insight">No data available yet.</span>
            @endforelse
        </div>
    </div>
</div>
