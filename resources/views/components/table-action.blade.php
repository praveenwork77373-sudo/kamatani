@props([
    'href' => null,
    'icon' => 'eye',
    'label' => '',
    'tone' => 'brand', // brand|muted|danger|success
    'method' => null,
    'confirm' => null,
    'confirmTitle' => null,
    'confirmOk' => null,
])

@php
    $classes = 'ui-icon-btn ui-icon-btn--'.$tone;
    $confirmTone = $tone === 'danger' ? 'danger' : 'brand';
    $resolvedConfirmTitle = $confirmTitle ?? ($tone === 'danger' ? __('pages.confirm_action_title_delete') : __('pages.confirm_action_title'));
    $resolvedConfirmOk = $confirmOk ?? ($tone === 'danger' ? __('common.delete') : __('pages.confirm_action_ok'));
@endphp

@if($method && $href)
    <form method="POST" action="{{ $href }}" class="inline-flex"
          @if($confirm)
              data-ui-confirm="{{ $confirm }}"
              data-ui-confirm-title="{{ $resolvedConfirmTitle }}"
              data-ui-confirm-tone="{{ $confirmTone }}"
              data-ui-confirm-ok="{{ $resolvedConfirmOk }}"
              data-ui-confirm-cancel="{{ __('pages.cancel') }}"
          @endif>
        @csrf
        @if(strtoupper($method) !== 'POST')
            @method($method)
        @endif
        <button type="submit"
                {{ $attributes->class([$classes]) }}
                data-tooltip="{{ $label }}"
                aria-label="{{ $label }}">
            <i data-lucide="{{ $icon }}"></i>
            <span class="sr-only">{{ $label }}</span>
        </button>
    </form>
@elseif($href)
    <a href="{{ $href }}"
       {{ $attributes->class([$classes]) }}
       data-tooltip="{{ $label }}"
       aria-label="{{ $label }}">
        <i data-lucide="{{ $icon }}"></i>
        <span class="sr-only">{{ $label }}</span>
    </a>
@else
    <button type="{{ $attributes->get('type', 'button') }}"
            {{ $attributes->except('type')->class([$classes]) }}
            data-tooltip="{{ $label }}"
            aria-label="{{ $label }}">
        <i data-lucide="{{ $icon }}"></i>
        <span class="sr-only">{{ $label }}</span>
    </button>
@endif
