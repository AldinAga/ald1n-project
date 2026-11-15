{{-- Comparison line of a KPI tile: arrow icon plus text, never colour alone. Author: Ald1n <https://ald1n.com> --}}
@php
    $deltaPercent = $delta['percent_change'];
    $deltaAbsolute = (float) $delta['absolute_change'];
    $deltaDirection = $deltaPercent === null || $deltaAbsolute == 0.0 ? 'none' : ($deltaAbsolute > 0 ? 'up' : 'down');
@endphp
<span class="a1-kpi__delta a1-kpi__delta--{{ $deltaDirection }}" data-dashboard-d2-delta="{{ $key }}">
    @if($deltaDirection !== 'none')<x-icon name="arrow-right" :size="16" :shell="true" />@endif
    @if($deltaPercent !== null)
        <span><b>{{ $deltaAbsolute > 0 ? '+' : '' }}{{ number_format((float) $deltaPercent, 1, ',', '.') }}%</b> u odnosu na prethodni period</span>
    @else
        <span>Nema uporedivog procenta (prethodni period: 0 RSD)</span>
    @endif
</span>
