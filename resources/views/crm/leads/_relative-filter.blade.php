@php
    $equal = $equal ?? false;
    $showUnit = $showUnit ?? true;
@endphp
<div class="lf-filter" data-filter="{{ $key }}" data-type="relative" data-applied="{{ !empty($applied) ? '1' : '0' }}">
    <button type="button" class="lf-pill {{ !empty($active) ? 'is-active' : '' }}">
        {{ $label }} @if(!empty($active))<span class="lf-dot"></span>@endif
        <i class="bi bi-chevron-down lf-caret"></i>
    </button>
    <div class="lf-drop lf-drop-compact">
        <div class="lf-drop-title">{{ $label }}</div>
        <div class="lf-relative">
            <select class="form-select" name="{{ $key }}_operator">
                <option value="more" @selected(($operator ?? 'more') === 'more')>More</option>
                <option value="less" @selected(($operator ?? '') === 'less')>Less</option>
                @if($equal)
                    <option value="equal" @selected(($operator ?? '') === 'equal')>Equal</option>
                @endif
            </select>
            <span class="lf-relative-than">than</span>
            <input type="number" min="0" class="form-control lf-relative-num" name="{{ $key }}_value" value="{{ $value ?? 0 }}">
            @if($showUnit)
                <select class="form-select" name="{{ $key }}_unit">
                    <option value="minutes" @selected(($unit ?? '') === 'minutes')>minutes</option>
                    <option value="hours" @selected(($unit ?? 'hours') === 'hours')>hours</option>
                    <option value="days" @selected(($unit ?? '') === 'days')>days</option>
                </select>
            @endif
        </div>
        <input type="hidden" name="{{ $key }}_applied" value="{{ !empty($applied) ? '1' : '' }}" data-applied-flag>
        <div class="lf-drop-foot"><button type="button" class="btn btn-yellow w-100 lf-apply">Apply</button></div>
    </div>
</div>
