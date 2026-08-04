@props([
    'action' => null,
    'method' => 'GET',
    'formId' => null,
    'active' => false,
])

@php
    $fid = $formId ?: 'filter-bar-'.uniqid();
@endphp

<div {{ $attributes->class(['ui-filter-bar']) }} data-filter-bar>
    <button
        type="button"
        class="ui-filter-bar__toggle"
        data-filter-toggle
        aria-expanded="{{ $active ? 'true' : 'false' }}"
        aria-controls="{{ $fid }}-panel"
    >
        <i data-lucide="sliders-horizontal" class="w-4 h-4" aria-hidden="true"></i>
        <span>{{ __('pages.filters') }}</span>
        @if($active)
            <span class="ui-filter-bar__dot" title="{{ __('pages.filters') }}"></span>
        @endif
        <i data-lucide="chevron-down" class="ui-filter-bar__chevron w-4 h-4" aria-hidden="true"></i>
    </button>

    <div
        id="{{ $fid }}-panel"
        class="ui-filter-bar__panel{{ $active ? ' is-open' : '' }}"
        data-filter-panel
    >
        <form
            method="{{ $method }}"
            @if($action) action="{{ $action }}" @endif
            id="{{ $fid }}"
            class="ui-filter-bar__form"
        >
            <x-per-page-field />
            {{ $before ?? '' }}
            <div class="ui-filter-bar__row">
                {{ $slot }}
                @isset($actions)
                    <div class="ui-filter-bar__actions">
                        {{ $actions }}
                    </div>
                @endisset
            </div>
        </form>
    </div>
</div>

@once
    @push('scripts')
        <script>
            (function () {
                function bindFilterBars(root) {
                    (root || document).querySelectorAll('[data-filter-bar]').forEach(function (bar) {
                        if (bar.dataset.filterBound === '1') return;
                        var btn = bar.querySelector('[data-filter-toggle]');
                        var panel = bar.querySelector('[data-filter-panel]');
                        if (!btn || !panel) return;
                        bar.dataset.filterBound = '1';
                        btn.addEventListener('click', function () {
                            var open = panel.classList.toggle('is-open');
                            btn.setAttribute('aria-expanded', open ? 'true' : 'false');
                        });
                    });
                }
                if (document.readyState === 'loading') {
                    document.addEventListener('DOMContentLoaded', function () { bindFilterBars(); });
                } else {
                    bindFilterBars();
                }
                window.refreshFilterBars = bindFilterBars;
            })();
        </script>
    @endpush
@endonce
