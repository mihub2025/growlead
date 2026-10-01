@props([
    'name' => 'currency',
    'value' => null,
    'disabled' => false,
])
@php
    $value = strtoupper((string) ($value ?: 'USD'));
    $currencies = config('crm.currencies', ['USD' => 'USD — US Dollar']);
    if ($value !== '' && ! array_key_exists($value, $currencies)) {
        $currencies = [$value => $value] + $currencies;
    }
@endphp
<select name="{{ $name }}" {{ $attributes->merge(['class' => 'form-select']) }} @disabled($disabled)>
    @foreach($currencies as $code => $label)
        <option value="{{ $code }}" @selected($value === $code)>{{ $label }}</option>
    @endforeach
</select>
