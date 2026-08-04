@extends('layouts.app')

@section('title', __('pages.customer_contract_plans_title'))
@section('page_title', __('pages.customer_contract_plans_title'))

@section('content')
@php
    $ccpRole = auth()->user()->adminUser->role ?? null;
    $canWriteCcp = in_array($ccpRole, ['system_admin', 'contract_plan_manager'], true);
@endphp

<div class="ui-page-header">
    <div>
        <h1 class="text-xl sm:text-2xl font-bold text-slate-900">{{ __('pages.customer_contract_plans_title') }}</h1>
        <p class="text-sm text-slate-600 mt-1">{{ __('pages.customer_contract_plans_desc') }}</p>
    </div>
    @if($canWriteCcp)
    <a href="{{ route('admin.customer-contract-plans.create') }}" class="ui-btn ui-btn-primary">{{ __('pages.new_registration') }}</a>
    @endif
</div>

<form method="GET" class="ui-toolbar mb-4">
    <x-per-page-field />
    <div class="min-w-0 flex-1 sm:flex-none">
        <label class="block text-xs text-slate-500 mb-1">{{ __('pages.medical_institution') }}</label>
        <select name="medical_institution_id" class="ui-select">
            <option value="">{{ __('pages.all_institutions') }}</option>
            @foreach($institutions as $inst)
                <option value="{{ $inst->id }}" @selected((string)$selectedInstitutionId === (string)$inst->id)>{{ $inst->name }}</option>
            @endforeach
        </select>
    </div>
    <button class="ui-btn ui-btn-secondary">{{ __('pages.apply_filter') }}</button>
</form>

<div class="ui-card overflow-hidden">
    <div class="ui-table-wrap">
        <table class="ui-table">
            <thead>
                <tr>
                    <th>{{ __('pages.medical_institution') }}</th>
                    <th>{{ __('pages.contract_plans_short') }}</th>
                    <th class="text-right hidden sm:table-cell">{{ __('pages.contract_count_ref') }}</th>
                    <th class="hidden md:table-cell">{{ __('pages.period') }}</th>
                    <th>{{ __('pages.status') }}</th>
                    <th class="ui-table__actions">{{ __('common.actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($plans as $row)
                    <tr>
                        <td>{{ $row->medicalInstitution->name ?? '-' }}</td>
                        <td>
                            <span class="font-mono text-xs text-slate-500">{{ $row->contractPlan->code ?? '' }}</span>
                            {{ $row->contractPlan->name ?? '' }}
                        </td>
                        <td class="text-right hidden sm:table-cell">{{ $row->contract_count }}</td>
                        <td class="hidden md:table-cell text-xs whitespace-nowrap">
                            {{ optional($row->start_date)->format('Y-m-d') ?? '-' }}
                            〜
                            {{ optional($row->end_date)->format('Y-m-d') ?? '-' }}
                        </td>
                        <td>
                            @if($row->status === 'confirmed')
                                <span class="ui-status-pill ui-status-pill--success">{{ __('pages.status_confirmed') }}</span>
                            @else
                                <span class="ui-status-pill ui-status-pill--warning">{{ __('pages.status_draft') }}</span>
                            @endif
                        </td>
                        <td class="ui-table__actions">
                            <div class="ui-table__actions-row">
                                @if($canWriteCcp)
                                    @if($row->status === 'draft')
                                        <x-table-action
                                            :href="route('admin.customer-contract-plans.confirm', $row)"
                                            method="POST"
                                            icon="check"
                                            tone="success"
                                            :label="__('pages.confirm_plan')"
                                        />
                                    @endif
                                    <x-table-action
                                        :href="route('admin.customer-contract-plans.edit', $row)"
                                        icon="pencil"
                                        :label="__('common.edit')"
                                    />
                                @else
                                    <span class="text-slate-400 text-xs">{{ __('pages.view_only') }}</span>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-8 text-center text-slate-500">{{ __('common.no_data') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <x-table-pagination :paginator="$plans" />
</div>

@endsection
