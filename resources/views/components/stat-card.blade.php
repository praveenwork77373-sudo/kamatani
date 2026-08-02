@props([
    'label',
    'value' => 0,
    'icon' => 'activity',
    'tone' => 'blue', // blue|emerald|amber|orange|red|slate|purple|indigo|green
    'badge' => null,
    'hint' => null,
    'prefix' => '',
    'format' => true,
    'href' => null,
])

@php
    $display = $format && is_numeric($value)
        ? $prefix.number_format((float) $value)
        : $prefix.$value;

    $tag = $href ? 'a' : 'div';
@endphp

<{{ $tag }}
    @if($href) href="{{ $href }}" @endif
    {{ $attributes->class(['ui-stat-card', 'ui-stat-card--'.$tone, $href ? 'ui-stat-card--link' : '']) }}
>
    <div class="ui-stat-card__top">
        <span class="ui-stat-card__icon" aria-hidden="true">
            <i data-lucide="{{ $icon }}"></i>
        </span>
        @if($badge)
            <span class="ui-stat-card__badge">{{ $badge }}</span>
        @endif
    </div>
    <p class="ui-stat-card__value">{{ $display }}</p>
    <p class="ui-stat-card__label">{{ $label }}</p>
    @if($hint)
        <p class="ui-stat-card__hint">{{ $hint }}</p>
    @endif
    {{ $slot }}
</{{ $tag }}>
