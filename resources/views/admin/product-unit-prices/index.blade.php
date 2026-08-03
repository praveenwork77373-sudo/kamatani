@extends('layouts.app')

@section('title', __('pages.product_unit_prices'))
@section('page_title', __('pages.product_unit_prices'))
@section('header_category', __('menu.master_data'))

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <p class="text-[14px] text-slate-600">{{ __('pages.product_unit_prices_description') }}</p>
        <a href="{{ route('admin.product-unit-prices.create') }}" class="ui-btn ui-btn-primary">{{ __('pages.new_registration') }}</a>
    </div>

    @if(session('status'))
        <div class="p-3 bg-emerald-50 border border-emerald-200 rounded-lg text-emerald-800 text-sm">{{ session('status') }}</div>
    @endif

    <div class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden">
        <form method="GET" class="p-4 border-b border-slate-200">
            <x-per-page-field />
            <div class="flex gap-2">
                <input type="text" name="q" value="{{ $searchQuery }}" class="ui-input" placeholder="{{ __('common.search') }}">
                <button type="submit" class="ui-btn ui-btn-primary">{{ __('common.search') }}</button>
            </div>
        </form>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 text-slate-600">
                    <tr>
                        <th class="px-4 py-3 text-left">{{ __('pages.product_code') ?? '商品コード' }}</th>
                        <th class="px-4 py-3 text-left">{{ __('pages.size_name') }}</th>
                        <th class="px-4 py-3 text-right">{{ __('pages.purchase_unit_price') }}</th>
                        <th class="px-4 py-3 text-left">{{ __('pages.supplier_notes') }}</th>
                        <th class="px-4 py-3 text-right">{{ __('pages.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($prices as $row)
                        <tr class="border-t border-slate-100">
                            <td class="px-4 py-3 font-mono">{{ $row->product_code }}</td>
                            <td class="px-4 py-3">{{ $row->size_name ?? '—' }}</td>
                            <td class="px-4 py-3 text-right">{{ number_format((float) $row->purchase_unit_price, 2) }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $row->notes ?? '—' }}</td>
                            <td class="px-4 py-3 text-right space-x-2">
                                <a href="{{ route('admin.product-unit-prices.edit', $row) }}" class="text-primary-600 hover:underline">{{ __('common.edit') }}</a>
                                <form method="POST" action="{{ route('admin.product-unit-prices.destroy', $row) }}" class="inline" onsubmit="return confirm('OK?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:underline">{{ __('common.delete') }}</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-8 text-center text-slate-500">{{ __('pages.no_data') ?? 'データがありません' }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <x-table-pagination :paginator="$prices" />
    </div>
</div>
@endsection
