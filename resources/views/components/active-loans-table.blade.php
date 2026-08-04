@props([
    'loans',
    'emptyMessage' => null,
    'showEmployee' => false,
])

@php
    $emptyMessage = $emptyMessage ?? __('pages.no_active_loans');
@endphp

<div class="overflow-x-auto">
    <table class="min-w-full text-[14px]">
        <thead class="bg-slate-50 border-b border-slate-200">
            <tr>
                @if($showEmployee)
                    <th class="px-4 py-3 text-left font-semibold text-slate-700">{{ __('pages.employee') }}</th>
                @endif
                <th class="px-4 py-3 text-left font-semibold text-slate-700">{{ __('pages.product_code') }}</th>
                <th class="px-4 py-3 text-left font-semibold text-slate-700">{{ __('pages.product_name') }}</th>
                <th class="px-4 py-3 text-left font-semibold text-slate-700">{{ __('pages.category') }}</th>
                <th class="px-4 py-3 text-left font-semibold text-slate-700">{{ __('pages.size') }}</th>
                <th class="px-4 py-3 text-right font-semibold text-slate-700">{{ __('pages.quantity') }}</th>
                <th class="px-4 py-3 text-left font-semibold text-slate-700">{{ __('pages.loan_start_date_short') }}</th>
                <th class="px-4 py-3 text-left font-semibold text-slate-700">{{ __('pages.status') }}</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @forelse($loans as $loan)
                @php $product = $loan->uniformProduct; @endphp
                <tr class="hover:bg-slate-50/50">
                    @if($showEmployee)
                        <td class="px-4 py-3">
                            <div class="font-medium text-slate-900">{{ $loan->employee?->name ?? '-' }}</div>
                            <div class="text-[11px] text-slate-500 font-mono">{{ $loan->employee?->staff_no ?? '-' }}</div>
                        </td>
                    @endif
                    <td class="px-4 py-3 font-mono text-slate-700">{{ $product?->product_code ?? '-' }}</td>
                    <td class="px-4 py-3 text-slate-900">
                        <div>{{ $product?->name ?? '-' }}</div>
                        @if($product?->maker?->name)
                            <div class="text-[11px] text-slate-500">{{ $product->maker->name }}</div>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-slate-600">{{ \App\Models\UniformCategory::labelFor($product?->category) }}</td>
                    <td class="px-4 py-3 text-slate-700">{{ $product?->size ?? '-' }}</td>
                    <td class="px-4 py-3 text-right font-semibold text-slate-900">{{ $loan->quantity }}</td>
                    <td class="px-4 py-3 text-slate-600 whitespace-nowrap">{{ optional($loan->loan_start_date)->format('Y-m-d') ?? '-' }}</td>
                    <td class="px-4 py-3">
                        <span class="ui-status-pill ui-status-pill--brand">{{ __('pages.on_loan') }}</span>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="{{ $showEmployee ? 8 : 7 }}" class="px-4 py-10 text-center text-slate-500">
                        {{ $emptyMessage }}
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
