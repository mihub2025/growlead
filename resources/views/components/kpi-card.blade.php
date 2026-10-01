<article class="card kpi">
    <div class="row">
        <span>{{ $label }}</span>
        <div class="ico-well"><i class="bi {{ $icon }}"></i></div>
    </div>
    <b class="serif num">{{ $value }}</b>
    @isset($change)
        <span class="chip {{ $change < 0 ? 'down' : '' }}">{{ $change >= 0 ? '↑' : '↓' }} {{ number_format(abs($change), 1) }}%</span>
    @endisset
</article>
