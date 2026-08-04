@extends('layouts.app')

@section('title', __('pages.billing_snapshot_history') . ' | ' . config('app.name'))
@section('page_title', __('pages.billing_snapshot_history'))

@section('content')
@php
    $selectedInstitutionId = $selectedInstitutionId ?? request('medical_institution_id');
    $billingMonth = $billingMonth ?? null;
    $filtersActive = filled($selectedInstitutionId) || filled($billingMonth);
@endphp

<div class="space-y-6 min-w-0 w-full">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div class="min-w-0">
            <h1 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight flex items-center gap-2">
                <i data-lucide="history" class="w-5 h-5 sm:w-6 sm:h-6 text-primary-600 shrink-0"></i>
                <span class="truncate">{{ __('pages.billing_snapshot_history') }}</span>
            </h1>
            <p class="text-[14px] text-slate-600 mt-1.5">{{ __('pages.billing_snapshot_history_desc') }}</p>
        </div>
        <a href="{{ route('admin.billing.index') }}"
           class="inline-flex items-center justify-center gap-2 w-full sm:w-auto px-4 py-2.5 rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-50 text-[14px] font-medium shrink-0">
            <i data-lucide="clipboard-list" class="w-4 h-4"></i>
            {{ __('pages.billing_month_board') }}
        </a>
    </div>

    <x-filter-bar :action="route('admin.billing.history')" form-id="billing-history-filter" :active="$filtersActive">
        <div class="ui-filter-field ui-filter-field--sm">
            <label class="ui-filter-field__label">{{ __('pages.target_month') }}</label>
            <x-month-picker name="billing_month" :value="$billingMonth" class="ui-input" />
        </div>
        <div class="ui-filter-field">
            <label class="ui-filter-field__label">{{ __('pages.medical_institution') }}</label>
            <select name="medical_institution_id" class="ui-select">
                <option value="">{{ __('pages.all_institutions') }}</option>
                @foreach($institutions ?? [] as $institution)
                    <option value="{{ $institution->id }}" @selected((string) $selectedInstitutionId === (string) $institution->id)>
                        {{ $institution->name }}
                        @if(isset($billingCounts['institution'][$institution->id]) && $billingCounts['institution'][$institution->id] > 0)
                            ({{ $billingCounts['institution'][$institution->id] }})
                        @endif
                    </option>
                @endforeach
            </select>
        </div>
        <x-slot:actions>
            <button type="submit" class="ui-btn ui-btn-secondary">{{ __('pages.apply_filter') }}</button>
            <a href="{{ route('admin.billing.history') }}" class="ui-btn ui-btn-secondary" title="{{ __('common.reset') }}">
                <i data-lucide="x" class="w-4 h-4"></i>
            </a>
        </x-slot:actions>
    </x-filter-bar>

    <div class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden min-w-0">
        <div class="md:hidden divide-y divide-slate-100">
            @forelse($billingSnapshots as $snapshot)
                <div class="p-4 space-y-2">
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0">
                            <p class="font-semibold text-slate-900">#BILL-{{ str_pad($snapshot->id, 4, '0', STR_PAD_LEFT) }}</p>
                            <p class="text-[12px] text-slate-500 truncate">{{ $snapshot->medicalInstitution?->name ?? '-' }}</p>
                        </div>
                        <p class="shrink-0 font-semibold text-primary-700">¥{{ number_format($snapshot->data['summary']['billing_amount'] ?? $snapshot->data['summary']['total_amount_ex_tax'] ?? 0) }}</p>
                    </div>
                    <p class="text-[12px] text-slate-600">{{ $snapshot->target_date->format(app()->getLocale() === 'ja' ? 'Y年n月' : 'M Y') }} · {{ $snapshot->created_at->format('Y/m/d H:i') }}</p>
                    <div class="ui-table__actions-row">
                        <x-table-action
                            :href="route('admin.billing.show', $snapshot)"
                            icon="eye"
                            :label="__('pages.details')"
                        />
                    </div>
                </div>
            @empty
                <div class="px-5 py-12 text-center">
                    <div class="w-16 h-16 rounded-full bg-slate-100 flex items-center justify-center mx-auto mb-3">
                        <i data-lucide="receipt" class="w-8 h-8 text-slate-300"></i>
                    </div>
                    <p class="text-slate-500 text-[14px] font-medium">{{ __('pages.no_billing_snapshots') }}</p>
                </div>
            @endforelse
        </div>

        <div class="hidden md:block overflow-x-auto">
            <table class="min-w-full text-[14px]">
                <thead class="bg-slate-50 border-b border-slate-200">
                    <tr>
                        <th class="px-5 py-3.5 text-left font-semibold text-slate-700 whitespace-nowrap">{{ __('pages.billing_id') }}</th>
                        <th class="px-5 py-3.5 text-left font-semibold text-slate-700 whitespace-nowrap">{{ __('pages.medical_institution_label') }}</th>
                        <th class="px-5 py-3.5 text-left font-semibold text-slate-700 whitespace-nowrap">{{ __('pages.target_month') }}</th>
                        <th class="px-5 py-3.5 text-left font-semibold text-slate-700 whitespace-nowrap">{{ __('pages.invoice_number') }}</th>
                        <th class="px-5 py-3.5 text-right font-semibold text-slate-700 whitespace-nowrap">{{ __('pages.billing_amount') }}</th>
                        <th class="px-5 py-3.5 text-left font-semibold text-slate-700 whitespace-nowrap">{{ __('pages.generated_at') }}</th>
                        <th class="px-5 py-3.5 text-left font-semibold text-slate-700 whitespace-nowrap">{{ __('pages.generated_by') }}</th>
                        <th class="px-5 py-3.5 text-right font-semibold text-slate-700 whitespace-nowrap">{{ __('pages.actions_label') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @forelse($billingSnapshots as $snapshot)
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="px-5 py-4 font-semibold text-slate-900 whitespace-nowrap">#BILL-{{ str_pad($snapshot->id, 4, '0', STR_PAD_LEFT) }}</td>
                            <td class="px-5 py-4 text-slate-800">{{ $snapshot->medicalInstitution?->name ?? '-' }}</td>
                            <td class="px-5 py-4 text-slate-700 whitespace-nowrap">{{ $snapshot->target_date->format(app()->getLocale() === 'ja' ? 'Y年n月' : 'M Y') }}</td>
                            <td class="px-5 py-4 text-slate-700">{{ $snapshot->data['invoice']['invoice_number'] ?? '-' }}</td>
                            <td class="px-5 py-4 text-right text-primary-700 font-semibold whitespace-nowrap">¥{{ number_format($snapshot->data['summary']['billing_amount'] ?? $snapshot->data['summary']['total_amount_ex_tax'] ?? 0) }}</td>
                            <td class="px-5 py-4 text-slate-600 whitespace-nowrap">{{ $snapshot->created_at->format('Y/m/d H:i') }}</td>
                            <td class="px-5 py-4 text-slate-600">{{ $snapshot->generatedBy?->name ?? '-' }}</td>
                            <td class="px-5 py-4 text-right ui-table__actions">
                                <div class="ui-table__actions-row justify-end">
                                    <x-table-action
                                        :href="route('admin.billing.show', $snapshot)"
                                        icon="eye"
                                        :label="__('pages.details')"
                                    />
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-5 py-12 text-center text-slate-500">{{ __('pages.no_billing_snapshots') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <x-table-pagination :paginator="$billingSnapshots" />
    </div>
</div>
@endsection
