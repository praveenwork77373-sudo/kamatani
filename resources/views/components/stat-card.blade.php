@props([
    'label',
    'value' => 0,
    'icon' => 'activity',
    'tone' => 'brand', // brand|emerald|amber|orange|red|slate|blue|indigo|green|purple
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
    <div class="ui-stat-card__body">
        <div class="ui-stat-card__copy min-w-0">
            <p class="ui-stat-card__label">{{ $label }}</p>
            <p class="ui-stat-card__value">{{ $display }}</p>
            @if($hint || $badge)
                <p class="ui-stat-card__hint">
                    @if($badge)<span class="ui-stat-card__badge">{{ $badge }}</span>@endif
                    @if($hint)<span>{{ $hint }}</span>@endif
                </p>
            @endif
        </div>
        <span class="ui-stat-card__icon" aria-hidden="true">
            <i data-lucide="{{ $icon }}"></i>
        </span>
    </div>
    {{ $slot }}
</{{ $tag }}>
