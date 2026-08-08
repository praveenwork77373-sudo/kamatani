@extends('layouts.app')

@section('title', __('pages.billing_data_detail') . ' | ' . config('app.name'))
@section('page_title', __('pages.billing_data_detail'))

@section('content')
@php
    $data = $snapshot->data ?? [];
    $isPlanBased = ($data['model'] ?? null) === 'plan_based';
    $summary = $data['summary'] ?? [];
    $planSummary = $data['plan_summary'] ?? [];
    $staffRows = $data['staff_rows'] ?? [];
    $billingAmount = (float) ($summary['billing_amount'] ?? $summary['total_amount_ex_tax'] ?? 0);

    $invoice = $data['invoice'] ?? [];
    $referenceDate = $data['reference_date'] ?? optional($snapshot->target_date)->format('Y-m-d');
    $billingMonth = $data['billing_month'] ?? (\Carbon\Carbon::parse($referenceDate ?: now())->format('Y-m'));
    $closingDay = $data['closing_day'] ?? ($snapshot->medicalInstitution?->billing_closing_day ?: 1);
    $assignedCount = (int) ($summary['total_assigned'] ?? $summary['total_targets'] ?? 0);
    $planCount = (int) ($summary['plan_count'] ?? count($planSummary));
    $contractSlots = (int) ($summary['total_contract_slots'] ?? collect($planSummary)->sum('contracted_slots'));
    $capacityAmount = (float) ($summary['contract_capacity_amount'] ?? collect($planSummary)->sum('amount_by_contract_slots'));
    $monthLabel = \Carbon\Carbon::parse($billingMonth.'-01')->translatedFormat(app()->getLocale() === 'ja' ? 'Y年n月' : 'F Y');
    $institutionCode = $data['institution']['code'] ?? $snapshot->medicalInstitution?->code;
    $onoeCode = $data['institution']['onoe_code'] ?? $snapshot->medicalInstitution?->onoe_code;
@endphp

<div class="space-y-6 min-w-0 w-full">
    <div class="rounded-xl border border-sky-200 bg-sky-50 px-4 py-3 text-[13px] text-sky-900">
        <p class="font-semibold">{{ __('pages.billing_how_it_works_title') }}</p>
        <ul class="mt-1 list-disc pl-5 space-y-0.5 text-sky-800">
            <li>{{ __('pages.billing_how_it_works_1') }}</li>
            <li>{{ __('pages.billing_how_it_works_2') }}</li>
        </ul>
    </div>

    {{-- Header --}}
    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
        <div class="min-w-0">
            <p class="text-[12px] font-semibold text-slate-500 uppercase tracking-wide">#BILL-{{ str_pad($snapshot->id, 4, '0', STR_PAD_LEFT) }}</p>
            <h1 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight mt-1">
                {{ __('pages.generated_billing_data') }}
            </h1>
            <div class="mt-3 flex flex-wrap items-center gap-x-4 gap-y-2 text-[13px] text-slate-600">
                <span class="inline-flex items-center gap-1.5 font-medium text-slate-800">
                    <i data-lucide="building" class="w-4 h-4 text-slate-400 shrink-0"></i>
                    {{ $snapshot->medicalInstitution?->name ?? '-' }}
                </span>
                @if($institutionCode)
                    <span class="inline-flex items-center gap-1.5 font-mono text-[12px]">
                        {{ __('pages.customer_code_label') }}: {{ $institutionCode }}
                    </span>
                @endif
                @if($onoeCode)
                    <span class="inline-flex items-center gap-1.5 font-mono text-[12px]">
                        {{ __('pages.onoe_code') }}: {{ $onoeCode }}
                    </span>
                @endif
                <span class="inline-flex items-center gap-1.5">
                    <i data-lucide="calendar" class="w-4 h-4 text-slate-400 shrink-0"></i>
                    {{ __('pages.target_month') }}: {{ $monthLabel }}
                </span>
                <span class="inline-flex items-center gap-1.5">
                    <i data-lucide="calendar-check" class="w-4 h-4 text-slate-400 shrink-0"></i>
                    {{ __('pages.reference_date') }}: {{ $referenceDate }}
                </span>
                <span class="inline-flex items-center gap-1.5">
                    <i data-lucide="clock" class="w-4 h-4 text-slate-400 shrink-0"></i>
                    {{ __('pages.closing_day') }}: {{ __('pages.every_month_day', ['day' => $closingDay]) }}
                </span>
                @if($snapshot->generatedBy)
                    <span class="inline-flex items-center gap-1.5">
                        <i data-lucide="user" class="w-4 h-4 text-slate-400 shrink-0"></i>
                        {{ __('pages.generated_by_label') }}: {{ $snapshot->generatedBy->name }}
                    </span>
                @endif
            </div>
        </div>
        <div class="flex flex-col sm:flex-row gap-2 shrink-0 w-full lg:w-auto">
            <a href="{{ route('admin.billing.export', $snapshot) }}"
               class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg bg-primary-600 hover:bg-primary-700 text-white text-[14px] font-semibold shadow-sm">
                <i data-lucide="download" class="w-4 h-4"></i>
                {{ __('pages.excel_export') }}
            </a>
            <a href="{{ route('admin.billing.history') }}"
               class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-50 text-[14px] font-medium">
                <i data-lucide="history" class="w-4 h-4"></i>
                {{ __('pages.billing_snapshot_history') }}
            </a>
            <a href="{{ route('admin.billing.index') }}"
               class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-50 text-[14px] font-medium">
                <i data-lucide="arrow-left" class="w-4 h-4"></i>
                {{ __('pages.back_to_billing_list') }}
            </a>
        </div>
    </div>

    @if($isPlanBased)
        @if($assignedCount === 0 && $planCount > 0)
            <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-[13px] text-amber-900">
                {{ __('pages.billing_zero_assigned_body') }}
            </div>
        @endif

        {{-- Plan-based KPI cards --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-3">
            <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-[12px] font-semibold text-slate-500">{{ __('pages.billing_billed_headcount') }}</p>
                <p class="mt-2 text-2xl font-bold text-slate-900">{{ number_format($assignedCount) }}<span class="text-sm font-medium text-slate-500 ml-1">{{ __('pages.people') }}</span></p>
                <p class="mt-1 text-[11px] text-slate-500">{{ __('pages.reference_date') }}: {{ $referenceDate }}</p>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-[12px] font-semibold text-slate-500">{{ __('pages.billing_plan_count') }}</p>
                <p class="mt-2 text-2xl font-bold text-slate-900">{{ number_format($planCount) }}</p>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-[12px] font-semibold text-slate-500">{{ __('pages.billing_contract_slots') }}</p>
                <p class="mt-2 text-2xl font-bold text-slate-900">{{ number_format($contractSlots) }}</p>
                <p class="mt-1 text-[12px] text-slate-500">{{ __('pages.billing_reference_capacity') }}: ¥{{ number_format($capacityAmount) }}</p>
            </div>
            <div class="rounded-xl border border-primary-200 bg-primary-50/40 p-4 shadow-sm">
                <p class="text-[12px] font-semibold text-primary-700">{{ __('pages.billing_official_amount') }}</p>
                <p class="mt-2 text-2xl font-bold text-primary-800">¥{{ number_format($billingAmount) }}</p>
                <p class="mt-1 text-[12px] text-slate-600">{{ __('pages.billing_amount_excl_tax') }} · {{ number_format($assignedCount) }}{{ __('pages.people') }}</p>
            </div>
        </div>

        {{-- Plan summary --}}
        <section class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden min-w-0">
            <div class="px-4 sm:px-6 py-4 border-b border-slate-200 bg-slate-50">
                <h2 class="text-[15px] font-bold text-slate-900">{{ __('pages.billing_plan_summary_title') }}</h2>
                <p class="text-[12px] text-slate-500 mt-1">
                    {{ __('pages.reference_date') }}: {{ $referenceDate }}
                    · {{ __('pages.closing_day') }}: {{ __('pages.every_month_day', ['day' => $closingDay]) }}
                </p>
            </div>

            <div class="md:hidden divide-y divide-slate-100">
                @forelse($planSummary as $plan)
                    <div class="p-4 space-y-2">
                        <div class="flex items-start justify-between gap-2">
                            <div class="min-w-0">
                                <p class="font-semibold text-slate-900 truncate">{{ $plan['plan_name'] }}</p>
                                <p class="text-[12px] text-slate-500">{{ $plan['plan_code'] }}</p>
                            </div>
                            <p class="shrink-0 font-bold text-primary-700">¥{{ number_format($plan['amount_ex_tax'] ?? 0) }}</p>
                        </div>
                        <p class="text-[12px] text-slate-600">
                            {{ __('pages.contract_period_label') }}:
                            {{ $plan['contract_start_date'] ?? '—' }}
                            〜
                            {{ $plan['contract_end_date'] ?? __('pages.billing_continued') }}
                        </p>
                        <div class="grid grid-cols-2 gap-2 text-[12px]">
                            <div class="rounded-lg bg-slate-50 px-3 py-2">
                                <p class="text-slate-500">{{ __('pages.billing_contract_slots') }}</p>
                                <p class="font-semibold text-slate-800">{{ number_format($plan['contracted_slots'] ?? 0) }}</p>
                            </div>
                            <div class="rounded-lg bg-slate-50 px-3 py-2">
                                <p class="text-slate-500">{{ __('pages.assigned_count') }}</p>
                                <p class="font-semibold text-slate-800">{{ number_format($plan['assigned_count'] ?? 0) }}</p>
                            </div>
                            <div class="rounded-lg bg-slate-50 px-3 py-2">
                                <p class="text-slate-500">{{ __('pages.unit_price') }}</p>
                                <p class="font-semibold text-slate-800">¥{{ number_format($plan['unit_price_ex_tax'] ?? 0) }}</p>
                            </div>
                            <div class="rounded-lg bg-slate-50 px-3 py-2">
                                <p class="text-slate-500">{{ __('pages.billing_reference_capacity') }}</p>
                                <p class="font-semibold text-slate-500">¥{{ number_format($plan['amount_by_contract_slots'] ?? 0) }}</p>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="px-4 py-10 text-center text-slate-500 text-[13px]">{{ __('pages.billing_no_plan_rows') }}</div>
                @endforelse
            </div>

            <div class="hidden md:block overflow-x-auto">
                <table class="min-w-full text-[13px]">
                    <thead class="bg-slate-50 text-slate-600">
                        <tr>
                            <th class="px-4 py-3 text-left whitespace-nowrap">{{ __('pages.plan_name') }}</th>
                            <th class="px-4 py-3 text-left whitespace-nowrap">{{ __('pages.plan_code') }}</th>
                            <th class="px-4 py-3 text-left whitespace-nowrap">{{ __('pages.contract_period_label') }}</th>
                            <th class="px-4 py-3 text-right whitespace-nowrap">{{ __('pages.billing_contract_slots') }}</th>
                            <th class="px-4 py-3 text-right whitespace-nowrap">{{ __('pages.assigned_count') }}</th>
                            <th class="px-4 py-3 text-right whitespace-nowrap">{{ __('pages.unit_price') }}</th>
                            <th class="px-4 py-3 text-right whitespace-nowrap">{{ __('pages.billing_official_amount') }}</th>
                            <th class="px-4 py-3 text-right whitespace-nowrap">{{ __('pages.billing_reference_capacity') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($planSummary as $plan)
                            <tr class="hover:bg-slate-50/80">
                                <td class="px-4 py-3 font-medium text-slate-900">{{ $plan['plan_name'] }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ $plan['plan_code'] }}</td>
                                <td class="px-4 py-3 text-[12px] text-slate-600 whitespace-nowrap">
                                    {{ $plan['contract_start_date'] ?? '—' }}
                                    〜
                                    {{ $plan['contract_end_date'] ?? __('pages.billing_continued') }}
                                </td>
                                <td class="px-4 py-3 text-right">{{ number_format($plan['contracted_slots'] ?? 0) }}</td>
                                <td class="px-4 py-3 text-right font-semibold">{{ number_format($plan['assigned_count'] ?? 0) }}</td>
                                <td class="px-4 py-3 text-right whitespace-nowrap">¥{{ number_format($plan['unit_price_ex_tax'] ?? 0) }}</td>
                                <td class="px-4 py-3 text-right font-bold text-primary-700 whitespace-nowrap">¥{{ number_format($plan['amount_ex_tax'] ?? 0) }}</td>
                                <td class="px-4 py-3 text-right text-slate-400 whitespace-nowrap">¥{{ number_format($plan['amount_by_contract_slots'] ?? 0) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-4 py-10 text-center text-slate-500">{{ __('pages.billing_no_plan_rows') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        {{-- Staff detail --}}
        <section class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden min-w-0">
            <div class="px-4 sm:px-6 py-4 border-b border-slate-200 bg-slate-50">
                <h2 class="text-[15px] font-bold text-slate-900">{{ __('pages.billing_staff_detail_title') }}</h2>
            </div>

            <div class="md:hidden divide-y divide-slate-100">
                @forelse($staffRows as $row)
                    <div class="p-4 space-y-1">
                        <div class="flex items-start justify-between gap-2">
                            <div class="min-w-0">
                                <p class="font-semibold text-slate-900">{{ $row['person_name'] }}</p>
                                <p class="text-[12px] text-slate-500">{{ $row['staff_no'] }} · {{ $row['department_name'] ?? '-' }}</p>
                            </div>
                            <p class="shrink-0 font-semibold text-slate-900">¥{{ number_format($row['amount_ex_tax'] ?? 0) }}</p>
                        </div>
                        <p class="text-[12px] text-slate-600">{{ $row['plan_name'] }} ({{ $row['plan_code'] }})</p>
                    </div>
                @empty
                    <div class="px-4 py-10 text-center text-slate-500 text-[13px]">{{ __('pages.billing_no_staff_rows') }}</div>
                @endforelse
            </div>

            <div class="hidden md:block overflow-x-auto">
                <table class="min-w-full text-[13px]">
                    <thead class="bg-slate-50 text-slate-600">
                        <tr>
                            <th class="px-3 py-2 text-left whitespace-nowrap">{{ __('pages.staff_no') }}</th>
                            <th class="px-3 py-2 text-left whitespace-nowrap">{{ __('common.name') }}</th>
                            <th class="px-3 py-2 text-left whitespace-nowrap">{{ __('pages.department_name') }}</th>
                            <th class="px-3 py-2 text-left whitespace-nowrap">{{ __('pages.join_date_label') }}</th>
                            <th class="px-3 py-2 text-left whitespace-nowrap">{{ __('pages.retire_date_label') }}</th>
                            <th class="px-3 py-2 text-left whitespace-nowrap">{{ __('pages.plan_name') }}</th>
                            <th class="px-3 py-2 text-left whitespace-nowrap">{{ __('pages.plan_code') }}</th>
                            <th class="px-3 py-2 text-right whitespace-nowrap">{{ __('pages.unit_price') }}</th>
                            <th class="px-3 py-2 text-right whitespace-nowrap">{{ __('pages.billing_amount') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($staffRows as $row)
                            <tr>
                                <td class="px-3 py-2">{{ $row['staff_no'] }}</td>
                                <td class="px-3 py-2">{{ $row['person_name'] }}</td>
                                <td class="px-3 py-2">{{ $row['department_name'] ?? '-' }}</td>
                                <td class="px-3 py-2 whitespace-nowrap">{{ $row['join_date'] ?? '-' }}</td>
                                <td class="px-3 py-2 whitespace-nowrap">{{ $row['retire_date'] ?? '-' }}</td>
                                <td class="px-3 py-2">{{ $row['plan_name'] }}</td>
                                <td class="px-3 py-2">{{ $row['plan_code'] }}</td>
                                <td class="px-3 py-2 text-right whitespace-nowrap">¥{{ number_format($row['unit_price_ex_tax'] ?? 0) }}</td>
                                <td class="px-3 py-2 text-right font-semibold whitespace-nowrap">¥{{ number_format($row['amount_ex_tax'] ?? 0) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="px-3 py-10 text-center text-slate-500">{{ __('pages.billing_no_staff_rows') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        {{-- Amount summary --}}
        <section class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden">
            <div class="px-4 sm:px-6 py-4 border-b border-slate-200 bg-slate-50">
                <h2 class="text-[15px] font-bold text-slate-900">{{ __('pages.billing_amount_summary') }}</h2>
            </div>
            <div class="p-4 sm:p-6 grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="rounded-xl border border-slate-200 p-5 space-y-3">
                    <div class="flex items-center justify-between text-[14px]">
                        <span class="text-slate-600">{{ __('pages.subtotal_excl_tax') }}</span>
                        <span class="font-semibold text-slate-900">¥{{ number_format($billingAmount) }}</span>
                    </div>
                    <p class="text-[12px] text-slate-500">{{ __('pages.billing_tax_external_note') }}</p>
                    <div class="pt-3 border-t border-slate-200 flex items-center justify-between">
                        <span class="text-[14px] font-semibold text-slate-700">{{ __('pages.billing_amount_ex_tax') }}</span>
                        <span class="text-2xl font-bold text-primary-700">¥{{ number_format($billingAmount) }}</span>
                    </div>
                </div>
                <div class="rounded-xl border border-slate-200 p-5 space-y-3 text-[13px]">
                    <div class="flex justify-between gap-3">
                        <span class="text-slate-500">{{ __('pages.created_at') }}</span>
                        <span class="text-slate-900 font-medium">{{ $snapshot->created_at->format('Y/m/d H:i') }}</span>
                    </div>
                    <div class="flex justify-between gap-3">
                        <span class="text-slate-500">{{ __('pages.invoice_number') }}</span>
                        <span class="text-slate-700">{{ $invoice['invoice_number'] ?? '—' }}</span>
                    </div>
                    <div class="flex justify-between gap-3">
                        <span class="text-slate-500">{{ __('pages.issue_date') }}</span>
                        <span class="text-slate-700">{{ $invoice['issue_date'] ?? '—' }}</span>
                    </div>
                    <div class="flex justify-between gap-3">
                        <span class="text-slate-500">{{ __('pages.payment_due_date') }}</span>
                        <span class="text-slate-700">{{ $invoice['payment_due_date'] ?? '—' }}</span>
                    </div>
                </div>
            </div>
            <div class="px-4 sm:px-6 py-4 border-t border-slate-200 flex flex-col sm:flex-row justify-end gap-2">
                <button type="button" onclick="window.print()"
                        class="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-50 text-[14px] font-medium">
                    <i data-lucide="printer" class="w-4 h-4"></i>
                    {{ __('pages.print_preview') }}
                </button>
                <a href="{{ route('admin.billing.export', $snapshot) }}"
                   class="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-lg bg-primary-600 hover:bg-primary-700 text-white text-[14px] font-semibold">
                    <i data-lucide="download" class="w-4 h-4"></i>
                    {{ __('pages.excel_export') }}
                </a>
            </div>
        </section>
    @else
        <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-[13px] text-amber-900">
            <p class="font-semibold">{{ __('pages.legacy_billing_snapshot_title') }}</p>
            <p class="mt-1">{{ __('pages.legacy_billing_snapshot_hint') }}</p>
            <p class="mt-2 font-medium">{{ __('pages.billing_amount_excl_tax') }}: ¥{{ number_format($billingAmount) }}</p>
        </div>
    @endif
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        if (typeof lucide !== 'undefined') lucide.createIcons();
    });
</script>
@endsection
