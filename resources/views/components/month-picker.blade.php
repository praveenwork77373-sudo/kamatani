@props([
    'name' => 'billing_month',
    'value' => null,
    'id' => null,
    'autoSubmit' => false,
])

@php
    $inputId = $id ?: 'month-picker-'.uniqid();
    $displayValue = $value ? (string) $value : '';
@endphp

<input
    type="text"
    id="{{ $inputId }}"
    name="{{ $name }}"
    value="{{ $displayValue }}"
    data-month-picker
    @if($autoSubmit) data-auto-submit="1" @endif
    autocomplete="off"
    placeholder="YYYY-MM"
    {{ $attributes->class(['ui-input', 'ui-month-picker']) }}
>

@once
    @push('scripts')
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.css">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/plugins/monthSelect/style.css">
        <script src="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/plugins/monthSelect/index.js"></script>
        @if(app()->getLocale() === 'ja')
            <script src="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/l10n/ja.js"></script>
        @endif
        <script src="{{ asset('js/month-picker.js') }}?v={{ filemtime(public_path('js/month-picker.js')) }}"></script>
    @endpush
@endonce
