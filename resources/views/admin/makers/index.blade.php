@extends('layouts.app')

@section('title', __('pages.maker_master_title'))
@section('page_title', __('pages.maker_master_title'))

@section('content')
@php
    $adminRole = auth()->user()->adminUser->role ?? null;
    $canWriteMasters = in_array($adminRole, ['system_admin', 'contract_plan_manager'], true);
@endphp
<div class="ui-page-header">
    <div>
        <h1 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight">{{ __('pages.maker_master_title') }}</h1>
        <p class="text-[13px] text-slate-600 mt-1">{{ __('pages.maker_master_desc') }}</p>
    </div>
    @if($canWriteMasters)
    <a href="{{ route('admin.makers.create') }}" class="ui-btn ui-btn-primary">{{ __('pages.new_registration') }}</a>
@endif
</div>

<div class="ui-card overflow-hidden">
    <div class="ui-table-wrap">
        <table class="ui-table">
            <thead>
                <tr>
                    <th>{{ __('pages.plan_code') }}</th>
                    <th>{{ __('pages.name_label') }}</th>
                    <th class="hidden sm:table-cell">TEL</th>
                    <th>{{ __('pages.status') }}</th>
                    <th class="ui-table__actions">{{ __('common.actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($makers as $maker)
                    <tr>
                        <td class="font-mono">{{ $maker->code }}</td>
                        <td>{{ $maker->name }}</td>
                        <td class="hidden sm:table-cell">{{ $maker->tel ?: '—' }}</td>
                        <td>
                            @if($maker->is_active)
                                <span class="ui-status-pill ui-status-pill--success">{{ __('pages.active') }}</span>
                            @else
                                <span class="ui-status-pill ui-status-pill--muted">{{ __('pages.inactive') }}</span>
                            @endif
                        </td>
                        <td class="ui-table__actions">
                            @if($canWriteMasters)
                            <div class="ui-table__actions-row justify-end">
                                <x-table-action
                                    :href="route('admin.makers.edit', $maker)"
                                    icon="pencil"
                                    :label="__('common.edit')"
                                />
                            </div>
                        @else
                            <span class="text-slate-400 text-xs">{{ __('pages.view_only') }}</span>
                        @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-8 text-center text-slate-500">{{ __('pages.no_makers') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

            <x-table-pagination :paginator="$makers" />

</div>

@endsection
