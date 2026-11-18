<div class="metric-strip">
    @foreach ($metrics as $metric)
        <div class="metric-item">
            <span class="metric-label">{{ $metric['label'] }}</span>
            <strong class="metric-value">{{ $metric['value'] }}</strong>
        </div>
    @endforeach
</div>
