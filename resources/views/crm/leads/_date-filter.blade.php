<div class="lf-filter" data-filter="{{ $key }}" data-type="date">
    <button type="button" class="lf-pill {{ !empty($active) ? 'is-active' : '' }}">
        {{ $label }} @if(!empty($active))<span class="lf-dot"></span>@endif
        <i class="bi bi-chevron-down lf-caret"></i>
    </button>
    <div class="lf-drop">
        <div class="lf-drop-title">{{ $label }}</div>
        <div class="lf-options">
            @foreach($presets as $value => $presetLabel)
                <label class="lf-option">
                    <input type="checkbox" name="{{ $param }}" value="{{ $value }}" data-date-preset @checked(($selected ?? '') === $value)>
                    <span>{{ $presetLabel }}</span>
                </label>
            @endforeach
        </div>
        <button type="button" class="lf-custom-toggle {{ ($selected ?? '') === 'custom' ? 'is-on' : '' }}">Select Custom</button>
        <div class="lf-custom-dates {{ ($selected ?? '') === 'custom' ? '' : 'd-none' }}" data-custom>
            <input type="hidden" name="{{ $param }}_custom" value="{{ ($selected ?? '') === 'custom' ? 'custom' : '' }}" data-custom-flag>
            <label class="form-label">From Date</label>
            <input type="date" class="form-control mb-2" name="{{ $key }}_from" value="{{ $from }}">
            <label class="form-label">To Date</label>
            <input type="date" class="form-control mb-2" name="{{ $key }}_to" value="{{ $to }}">
        </div>
        <div class="lf-drop-foot"><button type="button" class="btn btn-yellow w-100 lf-apply">Apply</button></div>
    </div>
</div>
