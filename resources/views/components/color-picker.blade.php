@php
    $value = $value ?? '#4F46E5';
    $name = $name ?? 'color';
@endphp
<div class="cp-color">
    <input type="color" class="cp-color-native" value="{{ $value }}" aria-label="Pick color">
    <input type="text" name="{{ $name }}" class="form-control cp-color-hex" value="{{ $value }}" maxlength="7" spellcheck="false" autocomplete="off" aria-label="Color hex">
</div>
