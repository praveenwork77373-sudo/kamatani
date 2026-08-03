@props(['paginator'])

@php
    use App\Support\Pagination;

    $perPage = Pagination::perPage(request());
    $query = request()->except(['page', 'per_page']);
@endphp

@if($paginator->total() > 0)
    <div {{ $attributes->merge(['class' => 'px-5 py-4 border-t border-slate-200 bg-slate-50']) }}>
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <span class="text-[13px] text-slate-600">
                {{ __('pages.showing_results', [
                    'firstItem' => $paginator->firstItem() ?? 0,
                    'lastItem' => $paginator->lastItem() ?? 0,
                    'total' => $paginator->total(),
                ]) }}
            </span>

            <div class="flex flex-wrap items-center gap-3">
                <form method="GET" class="flex items-center gap-2">
                    @foreach($query as $key => $value)
                        @if(is_array($value))
                            @foreach($value as $nested)
                                <input type="hidden" name="{{ $key }}[]" value="{{ $nested }}">
                            @endforeach
                        @else
                            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                        @endif
                    @endforeach
                    <label for="per-page-select" class="text-[13px] text-slate-600 whitespace-nowrap">{{ __('pages.items_per_page') }}</label>
                    <select id="per-page-select" name="per_page" onchange="this.form.submit()"
                            class="ui-select py-1.5 text-[13px] min-w-[4.5rem] w-auto">
                        @foreach(Pagination::ALLOWED_PER_PAGE as $option)
                            <option value="{{ $option }}" @selected($perPage === $option)>{{ $option }}</option>
                        @endforeach
                    </select>
                </form>

                @if($paginator->hasPages())
                    <div class="flex items-center gap-2">
                        {{ $paginator->onEachSide(1)->links('pagination::tailwind') }}
                    </div>
                @endif
            </div>
        </div>
    </div>
@endif
