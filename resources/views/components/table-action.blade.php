@props([
    'href' => null,
    'icon' => 'eye',
    'label' => '',
    'tone' => 'brand', // brand|muted|danger|success
    'method' => null,
    'confirm' => null,
])

@php
    $classes = 'ui-icon-btn ui-icon-btn--'.$tone;
@endphp

@if($method && $href)
    <form method="POST" action="{{ $href }}" class="inline-flex"
          @if($confirm) onsubmit="return confirm(@js($confirm))" @endif>
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
