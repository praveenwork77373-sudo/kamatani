@extends('layouts.app')

@section('title', __('pages.contract_plan_master'))
@section('page_title', __('pages.contract_plan_master'))

@section('content')
@php
    $adminRole = auth()->user()->adminUser->role ?? null;
    $canWriteMasters = in_array($adminRole, ['system_admin', 'contract_plan_manager'], true);
@endphp

<div class="ui-page-header">
    <div>
        <h1 class="text-xl sm:text-2xl font-bold text-slate-900">{{ __('pages.contract_plan_master') }}</h1>
        <p class="text-sm text-slate-600 mt-1">{{ __('pages.contract_plan_master_desc') }}</p>
    </div>
    @if($canWriteMasters)
    <a href="{{ route('admin.contract-plans.create') }}" class="ui-btn ui-btn-primary">{{ __('pages.new_registration') }}</a>
@endif
</div>

<div class="ui-card overflow-hidden">
    <div class="ui-table-wrap">
    <table class="ui-table">
        <thead>
            <tr>
                <th>{{ __('pages.plan_code') }}</th>
                <th>{{ __('pages.plan_name') }}</th>
                <th class="text-right hidden sm:table-cell">{{ __('pages.rentable_qty') }}</th>
                <th class="text-right hidden md:table-cell">{{ __('pages.unit_price_ex_tax') }}</th>
                <th class="text-right hidden lg:table-cell">{{ __('pages.product_count') }}</th>
                <th>{{ __('pages.status') }}</th>
                <th class="ui-table__actions">{{ __('common.actions') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse($plans as $plan)
                <tr>
                    <td class="font-mono">{{ $plan->code }}</td>
                    <td>{{ $plan->name }}</td>
                    <td class="text-right hidden sm:table-cell">{{ $plan->rentable_points }}</td>
                    <td class="text-right hidden md:table-cell">{{ number_format($plan->unit_price_ex_tax) }}</td>
                    <td class="text-right hidden lg:table-cell">{{ $plan->uniform_products_count }}</td>
                    <td>
                        @if($plan->is_active)
                            <span class="ui-status-pill ui-status-pill--success">{{ __('pages.active') }}</span>
                        @else
                            <span class="ui-status-pill ui-status-pill--muted">{{ __('pages.inactive') }}</span>
                        @endif
                    </td>
                    <td class="ui-table__actions">
                        <div class="ui-table__actions-row">
                            <x-table-action
                                :href="route('admin.contract-plans.show', $plan)"
                                icon="eye"
                                tone="muted"
                                :label="__('common.view')"
                            />
                            @if($canWriteMasters)
                                <x-table-action
                                    :href="route('admin.contract-plans.edit', $plan)"
                                    icon="pencil"
                                    :label="__('common.edit')"
                                />
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="px-4 py-8 text-center text-slate-500">{{ __('pages.no_contract_plans') }}</td></tr>
            @endforelse
        </tbody>
    </table>
    </div>
    <x-table-pagination :paginator="$plans" />
</div>

@endsection
