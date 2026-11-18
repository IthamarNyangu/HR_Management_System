@php
    $trendMaximum = max(1, collect($points)->max('value') ?? 0);
    $trendTotal = collect($points)->sum('value');
@endphp
<div class="insight-panel">
    <div class="insight-heading">
        <div>
            <h3>{{ $title }}</h3>
            <span>{{ $subtitle ?? 'Last six months' }}</span>
        </div>
        <strong>{{ $trendTotal }}</strong>
    </div>
    <div class="trend-chart" role="img" aria-label="{{ $title }} over the last six months">
        @foreach ($points as $point)
            @php $height = $point['value'] > 0 ? max(9, round(($point['value'] / $trendMaximum) * 100)) : 3; @endphp
            <div class="trend-column">
                <span class="trend-number">{{ $point['value'] }}</span>
                <div class="trend-track"><span class="trend-bar" style="height: {{ $height }}%"></span></div>
                <span class="trend-label">{{ $point['label'] }}</span>
            </div>
        @endforeach
    </div>
</div>
