@props([
    'cols' => 4, // 3|4|5
])

@php
    $colsClass = match ((int) $cols) {
        3 => 'ui-stat-grid--3',
        5 => 'ui-stat-grid--5',
        default => 'ui-stat-grid--4',
    };
@endphp

<div {{ $attributes->class(['ui-stat-grid', $colsClass]) }}>
    {{ $slot }}
</div>
