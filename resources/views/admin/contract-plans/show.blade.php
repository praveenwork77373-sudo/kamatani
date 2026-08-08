@extends('layouts.app')

@section('title', __('pages.contract_plan_detail'))
@section('page_title', __('pages.contract_plan_detail'))

@section('content')
<div class="mb-6 flex items-center justify-between">
    <div>
        <h1 class="text-2xl font-bold text-slate-900">{{ $plan->name }}</h1>
        <p class="text-sm text-slate-600 mt-1 font-mono">{{ $plan->code }}</p>
    </div>
    <a href="{{ route('admin.contract-plans.edit', $plan) }}" class="px-4 py-2 rounded-lg bg-primary-600 text-white text-sm font-semibold">{{ __('common.edit') }}</a>
</div>

<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
    <div class="bg-white border rounded-xl p-4"><p class="text-xs text-slate-500">{{ __('pages.rentable_qty') }}</p><p class="text-2xl font-bold">{{ $plan->rentable_points }}</p></div>
    <div class="bg-white border rounded-xl p-4"><p class="text-xs text-slate-500">{{ __('pages.unit_price_ex_tax') }}</p><p class="text-2xl font-bold">¥{{ number_format($plan->unit_price_ex_tax) }}</p></div>
    <div class="bg-white border rounded-xl p-4"><p class="text-xs text-slate-500">{{ __('pages.status') }}</p><p class="text-2xl font-bold">{{ $plan->is_active ? __('pages.active') : __('pages.inactive') }}</p></div>
</div>

@if($plan->hasCategoryCaps() || $plan->partner_unit_price_ex_tax !== null)
<div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3 mb-6">
    <div class="bg-white border rounded-xl p-3"><p class="text-[11px] text-slate-500">{{ __('pages.cap_top') }}</p><p class="text-lg font-bold">{{ $plan->rentable_points_top }}</p></div>
    <div class="bg-white border rounded-xl p-3"><p class="text-[11px] text-slate-500">{{ __('pages.cap_bottom') }}</p><p class="text-lg font-bold">{{ $plan->rentable_points_bottom }}</p></div>
    <div class="bg-white border rounded-xl p-3"><p class="text-[11px] text-slate-500">{{ __('pages.cap_other') }}</p><p class="text-lg font-bold">{{ $plan->rentable_points_other }}</p></div>
    <div class="bg-white border rounded-xl p-3"><p class="text-[11px] text-slate-500">{{ __('pages.cap_other2') }}</p><p class="text-lg font-bold">{{ $plan->rentable_points_other2 }}</p></div>
    <div class="bg-white border rounded-xl p-3"><p class="text-[11px] text-slate-500">{{ __('pages.cap_other3') }}</p><p class="text-lg font-bold">{{ $plan->rentable_points_other3 }}</p></div>
    <div class="bg-white border rounded-xl p-3"><p class="text-[11px] text-slate-500">{{ __('pages.partner_unit_price_ex_tax') }}</p><p class="text-lg font-bold">{{ $plan->partner_unit_price_ex_tax !== null ? '¥'.number_format($plan->partner_unit_price_ex_tax) : '—' }}</p></div>
</div>
@endif

<div class="bg-white border rounded-xl overflow-hidden">
    <div class="px-4 py-3 border-b font-semibold text-sm">{{ __('pages.applicable_uniforms') }}</div>
    <table class="min-w-full text-sm">
        <thead class="bg-slate-50"><tr>
            <th class="px-4 py-2 text-left">{{ __('pages.product_code') }}</th>
            <th class="px-4 py-2 text-left">{{ __('pages.name_label') }}</th>
            <th class="px-4 py-2 text-left">{{ __('pages.size') }}</th>
        </tr></thead>
        <tbody>
            @forelse($plan->uniformProducts as $p)
                <tr class="border-t"><td class="px-4 py-2 font-mono">{{ $p->product_code }}</td><td class="px-4 py-2">{{ $p->name }}</td><td class="px-4 py-2">{{ $p->size }}</td></tr>
            @empty
                <tr><td colspan="3" class="px-4 py-6 text-center text-slate-500">{{ __('pages.ui_9c784af660') }}</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
