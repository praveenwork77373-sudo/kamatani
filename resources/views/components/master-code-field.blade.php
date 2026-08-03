@props([
    'name' => 'code',
    'type',
    'value' => '',
    'excludeId' => null,
    'institutionId' => null,
    'institutionInput' => null,
    'label' => null,
    'mode' => null, // auto | readonly — default: readonly if value, else auto
])

@php
    $label = $label ?? __('pages.code');
    $fieldId = $attributes->get('id', $name.'-field');
    $hasValue = is_string($value) && trim($value) !== '';
    $mode = $mode ?? ($hasValue ? 'readonly' : 'auto');
@endphp

@if($mode === 'readonly' && $hasValue)
    {{-- Edit: show assigned code only (immutable) --}}
    <div class="space-y-1.5">
        <label for="{{ $fieldId }}" class="ui-label">{{ $label }}</label>
        <div class="ui-field-icon">
            <i data-lucide="hash"></i>
            <input type="text"
                   id="{{ $fieldId }}"
                   value="{{ $value }}"
                   class="ui-input bg-slate-50 text-slate-700 font-mono"
                   readonly
                   tabindex="-1">
        </div>
        <input type="hidden" name="{{ $name }}" value="{{ $value }}">
        @error($name)
            <p class="ui-error">{{ $message }}</p>
        @enderror
    </div>
@else
    {{-- Create: no UI — backend assigns on save --}}
    <input type="hidden" name="{{ $name }}" id="{{ $fieldId }}" value="">
@endif
