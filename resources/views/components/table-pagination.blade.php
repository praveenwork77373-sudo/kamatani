@props(['paginator'])

@php
    use App\Support\Pagination;

    $perPage = Pagination::perPage(request());
    $query = request()->except(['page', 'per_page']);
    $selectId = 'per-page-'.substr(md5((string) spl_object_id($paginator)), 0, 8);
@endphp

@if($paginator->total() > 0)
    <div {{ $attributes->merge(['class' => 'ui-table-pagination']) }}>
        <div class="ui-table-pagination__inner">
            <span class="ui-table-pagination__meta">
                {{ __('pages.showing_results', [
                    'firstItem' => $paginator->firstItem() ?? 0,
                    'lastItem' => $paginator->lastItem() ?? 0,
                    'total' => $paginator->total(),
                ]) }}
            </span>

            <div class="ui-table-pagination__controls">
                <form method="GET" action="{{ url()->current() }}" class="ui-table-pagination__per-page">
                    @foreach($query as $key => $value)
                        @if(is_array($value))
                            @foreach($value as $nested)
                                @if(is_scalar($nested))
                                    <input type="hidden" name="{{ $key }}[]" value="{{ $nested }}">
                                @endif
                            @endforeach
                        @elseif(is_scalar($value))
                            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                        @endif
                    @endforeach
                    <label for="{{ $selectId }}" class="ui-table-pagination__label">{{ __('pages.items_per_page') }}</label>
                    <select id="{{ $selectId }}" name="per_page" onchange="this.form.submit()"
                            class="ui-select ui-table-pagination__select">
                        @foreach(Pagination::ALLOWED_PER_PAGE as $option)
                            <option value="{{ $option }}" @selected($perPage === $option)>{{ $option }}</option>
                        @endforeach
                    </select>
                </form>

                @if($paginator->hasPages())
                    {{ $paginator->onEachSide(1)->links('pagination::compact') }}
                @endif
            </div>
        </div>
    </div>
@endif
