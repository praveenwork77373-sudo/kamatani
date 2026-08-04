@extends('layouts.app')

@section('title', __('menu.contract_info'))
@section('page_title', __('menu.contract_info'))

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-slate-900">{{ __('pages.ui_10fd78192e') }}</h1>
    <p class="text-sm text-slate-600 mt-1">{{ __('pages.confirmed_plans_status', ['name' => $institution->name ?? '']) }}</p>
</div>

@if($institution)
    <p class="text-sm text-slate-600 mb-4">
        {{ __('pages.closing_day_monthly', ['day' => $institution->billing_closing_day ?? 1]) }}
        @if($institution->sales_person_name)
            {{ __('pages.sales_person_prefix', ['name' => $institution->sales_person_name]) }}
        @endif
    </p>
@endif

<div class="bg-white border border-slate-200 rounded-xl overflow-hidden">
    <table class="min-w-full text-sm">
        <thead class="bg-slate-50 text-slate-600">
            <tr>
                <th class="px-4 py-3 text-left">{{ __('pages.plan_code') }}</th>
                <th class="px-4 py-3 text-left">{{ __('pages.plan_name') }}</th>
                <th class="px-4 py-3 text-right">{{ __('pages.rentable_qty') }}</th>
                <th class="px-4 py-3 text-right">{{ __('pages.unit_price_ex_tax') }}</th>
                <th class="px-4 py-3 text-right">{{ __('pages.contract_count') }}</th>
                <th class="px-4 py-3 text-right">{{ __('pages.ui_433b7faaf4') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse($plans as $row)
                @php $assigned = (int)($assignmentCounts[$row->id] ?? 0); @endphp
                <tr class="border-t">
                    <td class="px-4 py-3 font-mono">{{ $row->contractPlan->code ?? '-' }}</td>
                    <td class="px-4 py-3">{{ $row->contractPlan->name ?? '-' }}</td>
                    <td class="px-4 py-3 text-right">{{ $row->contractPlan->rentable_points ?? '-' }}</td>
                    <td class="px-4 py-3 text-right">¥{{ number_format($row->contractPlan->unit_price_ex_tax ?? 0) }}</td>
                    <td class="px-4 py-3 text-right">{{ $row->contract_count }}</td>
                    <td class="px-4 py-3 text-right {{ $assigned > $row->contract_count ? 'text-red-600 font-semibold' : '' }}">
                        {{ $assigned }}
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="px-4 py-8 text-center text-slate-500">{{ __('pages.ui_50a869e66e') }}</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
