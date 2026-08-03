@props([
    'name' => 'code',
    'type',
    'value' => '',
    'excludeId' => null,
    'institutionId' => null,
    'institutionInput' => null, // CSS selector or name of select that holds institution id
    'label' => null,
    'required' => false,
    'placeholder' => null,
])

@php
    $label = $label ?? __('pages.code');
    $fieldId = $attributes->get('id', $name.'-field');
    $hintId = $fieldId.'-hint';
    $statusId = $fieldId.'-status';
@endphp

<div class="space-y-1.5" data-master-code-wrap
     data-code-type="{{ $type }}"
     data-exclude-id="{{ $excludeId }}"
     data-institution-id="{{ $institutionId }}"
     @if($institutionInput) data-institution-input="{{ $institutionInput }}" @endif
     data-check-url="{{ route('api.master_code.check') }}">
    <label for="{{ $fieldId }}" class="ui-label">
        {{ $label }}
        @if($required)
            <span class="text-red-500">*</span>
        @else
            <span class="text-slate-400 font-normal text-[12px]">（{{ __('pages.optional_auto') }}）</span>
        @endif
    </label>
    <div class="ui-field-icon">
        <i data-lucide="hash"></i>
        <input type="text"
               id="{{ $fieldId }}"
               name="{{ $name }}"
               value="{{ $value }}"
               class="ui-input"
               data-master-code-input
               autocomplete="off"
               placeholder="{{ $placeholder ?? __('pages.code_auto_placeholder') }}"
               @if($required) required @endif>
    </div>
    <p id="{{ $hintId }}" class="ui-hint">{{ __('pages.code_auto_note') }}</p>
    <p id="{{ $statusId }}" class="text-[13px] mt-1 hidden" data-master-code-status aria-live="polite"></p>
    @error($name)
        <p class="ui-error">{{ $message }}</p>
    @enderror
</div>
