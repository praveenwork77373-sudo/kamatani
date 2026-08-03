@extends('layouts.app')

@section('title', __('pages.billing_data') . ' | ' . config('app.name'))
@section('page_title', __('pages.billing_data'))

@section('content')
@php
    $user = auth()->user();
    $admin = $user->adminUser ?? null;
    $adminRole = $admin->role ?? null;
    $canManageBilling = in_array($adminRole, ['system_admin', 'operation', 'accounting'], true);
    $selectedInstitutionId = request('medical_institution_id');
    $billingMonth = $billingMonth ?? now()->format('Y-m');
    $statusFilter = $statusFilter ?? 'all';
    $boardStats = $boardStats ?? ['total' => 0, 'pending' => 0, 'generated' => 0, 'no_contract' => 0];
    $institutionMonthStatus = $institutionMonthStatus ?? collect();
    $filterBase = array_filter([
        'billing_month' => $billingMonth,
        'medical_institution_id' => $selectedInstitutionId ?: null,
    ]);
@endphp

<div class="space-y-6 min-w-0 w-full">
    {{-- Page header --}}
    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div class="min-w-0">
            <h1 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight flex items-center gap-2">
                <i data-lucide="receipt" class="w-5 h-5 sm:w-6 sm:h-6 text-primary-600 shrink-0"></i>
                <span class="truncate">{{ __('pages.billing_approval') }}</span>
            </h1>
            <p class="text-[13px] sm:text-[14px] text-slate-600 mt-1.5">{{ __('pages.billing_approval_description') }}</p>
        </div>
        @if($canManageBilling)
            <a href="{{ route('admin.billing.create') }}"
               class="inline-flex items-center justify-center gap-2 w-full sm:w-auto px-4 sm:px-5 py-2.5 rounded-lg bg-primary-600 hover:bg-primary-700 text-white text-[14px] font-semibold shadow-sm shrink-0">
                <i data-lucide="plus" class="w-4 h-4"></i>
                {{ __('pages.generate_billing_data') }}
            </a>
        @endif
    </div>

    <x-stat-grid :cols="4">
        <x-stat-card tone="amber" icon="clock" :value="$stats['total_billing_snapshots'] ?? 0" :label="__('pages.billing_snapshots')" :badge="__('pages.total')" />
        <x-stat-card tone="blue" icon="calendar" :value="$stats['this_month_billing_snapshots'] ?? 0" :label="__('pages.this_month_billing_snapshots')" badge="今月" />
        <x-stat-card tone="purple" icon="japanese-yen" :value="$stats['total_billing_amount'] ?? 0" :label="__('pages.total_billing_amount')" :badge="__('pages.total')" prefix="¥" />
        <x-stat-card tone="emerald" icon="japanese-yen" :value="$stats['this_month_billing_amount'] ?? 0" :label="__('pages.this_month_billing_amount')" badge="今月" prefix="¥" />
    </x-stat-grid>

    {{-- Month-wise institution status board --}}
    <section class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden min-w-0">
        <div class="p-4 sm:p-5 border-b border-slate-200 flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div class="min-w-0 flex-1">
                <h2 class="text-[15px] font-bold text-slate-900 flex items-center gap-2">
                    <i data-lucide="clipboard-list" class="w-4 h-4 text-primary-600 shrink-0"></i>
                    <span>{{ __('pages.billing_month_board') }}</span>
                </h2>
                <p class="text-[12px] sm:text-[13px] text-slate-500 mt-1.5 leading-relaxed max-w-3xl">
                    {{ __('pages.billing_month_board_hint') }}
                </p>
            </div>
            <form method="GET" action="{{ route('admin.billing.index') }}" class="flex flex-col sm:flex-row items-stretch sm:items-end gap-2 shrink-0 w-full lg:w-auto">
                <div class="min-w-0 flex-1 lg:flex-none">
                    <label class="block text-[11px] font-semibold text-slate-600 mb-1">{{ __('pages.target_month') }}</label>
                    <input type="month" name="billing_month" value="{{ $billingMonth }}"
                           class="ui-input py-2 w-full sm:w-44" onchange="this.form.submit()">
                </div>
                <input type="hidden" name="status_filter" value="{{ $statusFilter }}">
                @if($selectedInstitutionId)
                    <input type="hidden" name="medical_institution_id" value="{{ $selectedInstitutionId }}">
                @endif
            </form>
        </div>

        <div class="px-4 py-3 border-b border-slate-100 bg-slate-50/70 overflow-x-auto">
            <div class="flex items-center gap-2 min-w-max sm:min-w-0 sm:flex-wrap">
                <a href="{{ route('admin.billing.index', $filterBase + ['status_filter' => 'all']) }}"
                   class="shrink-0 px-3 py-1.5 rounded-full text-[12px] font-semibold border {{ $statusFilter === 'all' ? 'bg-slate-800 text-white border-slate-800' : 'bg-white text-slate-600 border-slate-200' }}">
                    {{ __('pages.all') }} ({{ $boardStats['total'] ?? 0 }})
                </a>
                <a href="{{ route('admin.billing.index', $filterBase + ['status_filter' => 'pending']) }}"
                   class="shrink-0 px-3 py-1.5 rounded-full text-[12px] font-semibold border {{ $statusFilter === 'pending' ? 'bg-amber-600 text-white border-amber-600' : 'bg-white text-amber-700 border-amber-200' }}">
                    {{ __('pages.billing_status_pending') }} ({{ $boardStats['pending'] ?? 0 }})
                </a>
                <a href="{{ route('admin.billing.index', $filterBase + ['status_filter' => 'generated']) }}"
                   class="shrink-0 px-3 py-1.5 rounded-full text-[12px] font-semibold border {{ $statusFilter === 'generated' ? 'bg-emerald-600 text-white border-emerald-600' : 'bg-white text-emerald-700 border-emerald-200' }}">
                    {{ __('pages.billing_status_generated') }} ({{ $boardStats['generated'] ?? 0 }})
                </a>
                <a href="{{ route('admin.billing.index', $filterBase + ['status_filter' => 'no_contract']) }}"
                   class="shrink-0 px-3 py-1.5 rounded-full text-[12px] font-semibold border {{ $statusFilter === 'no_contract' ? 'bg-slate-500 text-white border-slate-500' : 'bg-white text-slate-500 border-slate-200' }}">
                    {{ __('pages.billing_status_no_contract') }} ({{ $boardStats['no_contract'] ?? 0 }})
                </a>
            </div>
        </div>

        {{-- Mobile cards --}}
        <div class="md:hidden divide-y divide-slate-100">
            @forelse($institutionMonthStatus as $row)
                @php
                    $inst = $row['institution'];
                    $status = $row['status'];
                @endphp
                <div class="p-4 space-y-3">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="font-semibold text-slate-900 truncate">{{ $inst->name }}</p>
                            <p class="text-[12px] text-slate-500 mt-0.5">
                                {{ __('pages.every_month_day', ['day' => $row['closing_day']]) }}
                                · {{ $row['closing_date'] }}
                            </p>
                        </div>
                        @if($status === 'generated')
                            <span class="shrink-0 inline-flex items-center gap-1 px-2 py-1 rounded-md bg-emerald-50 text-emerald-700 text-[11px] font-semibold">
                                {{ __('pages.billing_status_generated') }}
                            </span>
                        @elseif($status === 'pending')
                            <span class="shrink-0 inline-flex items-center gap-1 px-2 py-1 rounded-md bg-amber-50 text-amber-700 text-[11px] font-semibold">
                                {{ __('pages.billing_status_pending') }}
                            </span>
                        @else
                            <span class="shrink-0 inline-flex items-center gap-1 px-2 py-1 rounded-md bg-slate-100 text-slate-500 text-[11px] font-semibold">
                                {{ __('pages.billing_status_no_contract') }}
                            </span>
                        @endif
                    </div>
                    <div class="flex items-center justify-between text-[13px]">
                        <span class="text-slate-500">{{ __('pages.assigned_count') }}</span>
                        <span class="font-medium text-slate-800">{{ $row['assigned_count'] !== null ? number_format($row['assigned_count']) : '—' }}</span>
                    </div>
                    <div class="flex items-center justify-between text-[13px]">
                        <span class="text-slate-500">{{ __('pages.billing_amount') }}</span>
                        <span class="font-semibold text-slate-900">{{ $row['amount'] !== null ? '¥'.number_format($row['amount']) : '—' }}</span>
                    </div>
                    <div class="flex flex-wrap gap-2 pt-1">
                        @if($status === 'generated' && $row['snapshot'])
                            <a href="{{ route('admin.billing.show', $row['snapshot']) }}"
                               class="inline-flex items-center gap-1 px-3 py-2 rounded-lg border border-slate-300 text-slate-700 text-[12px] font-medium">
                                {{ __('pages.details') }}
                            </a>
                        @endif
                        @if($canManageBilling && $status === 'pending')
                            <a href="{{ route('admin.billing.create', ['medical_institution_id' => $inst->id, 'target_month' => $billingMonth, 'target_date' => $row['closing_date']]) }}"
                               class="inline-flex items-center gap-1 px-3 py-2 rounded-lg bg-primary-600 text-white text-[12px] font-semibold">
                                {{ __('pages.generate_billing_data') }}
                            </a>
                        @elseif($canManageBilling && $status === 'generated')
                            <a href="{{ route('admin.billing.create', ['medical_institution_id' => $inst->id, 'target_month' => $billingMonth, 'target_date' => $row['closing_date']]) }}"
                               class="inline-flex items-center gap-1 px-3 py-2 rounded-lg border border-slate-300 text-slate-600 text-[12px] font-medium">
                                {{ __('pages.regenerate') }}
                            </a>
                        @endif
                    </div>
                </div>
            @empty
                <div class="px-4 py-10 text-center text-slate-500 text-[13px]">{{ __('pages.no_institutions') }}</div>
            @endforelse
        </div>

        {{-- Desktop table --}}
        <div class="hidden md:block overflow-x-auto">
            <table class="min-w-full text-[13px]">
                <thead class="bg-slate-50 text-slate-600">
                    <tr>
                        <th class="px-4 py-3 text-left whitespace-nowrap">{{ __('pages.medical_institution_label') }}</th>
                        <th class="px-4 py-3 text-left whitespace-nowrap">{{ __('pages.closing_day') }}</th>
                        <th class="px-4 py-3 text-left whitespace-nowrap">{{ __('pages.billing_reference_date') }}</th>
                        <th class="px-4 py-3 text-left whitespace-nowrap">{{ __('pages.status') }}</th>
                        <th class="px-4 py-3 text-right whitespace-nowrap">{{ __('pages.assigned_count') }}</th>
                        <th class="px-4 py-3 text-right whitespace-nowrap">{{ __('pages.billing_amount') }}</th>
                        <th class="px-4 py-3 text-right whitespace-nowrap">{{ __('pages.actions_label') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($institutionMonthStatus as $row)
                        @php
                            $inst = $row['institution'];
                            $status = $row['status'];
                        @endphp
                        <tr class="hover:bg-slate-50/80">
                            <td class="px-4 py-3 font-medium text-slate-900">{{ $inst->name }}</td>
                            <td class="px-4 py-3 text-slate-600 whitespace-nowrap">{{ __('pages.every_month_day', ['day' => $row['closing_day']]) }}</td>
                            <td class="px-4 py-3 font-mono text-slate-700 whitespace-nowrap">{{ $row['closing_date'] }}</td>
                            <td class="px-4 py-3">
                                @if($status === 'generated')
                                    <span class="inline-flex items-center gap-1 px-2 py-1 rounded-md bg-emerald-50 text-emerald-700 text-[12px] font-semibold whitespace-nowrap">
                                        <i data-lucide="check-circle" class="w-3.5 h-3.5"></i>
                                        {{ __('pages.billing_status_generated') }}
                                    </span>
                                @elseif($status === 'pending')
                                    <span class="inline-flex items-center gap-1 px-2 py-1 rounded-md bg-amber-50 text-amber-700 text-[12px] font-semibold whitespace-nowrap">
                                        <i data-lucide="clock" class="w-3.5 h-3.5"></i>
                                        {{ __('pages.billing_status_pending') }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2 py-1 rounded-md bg-slate-100 text-slate-500 text-[12px] font-semibold whitespace-nowrap">
                                        {{ __('pages.billing_status_no_contract') }}
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right text-slate-700">
                                {{ $row['assigned_count'] !== null ? number_format($row['assigned_count']) : '—' }}
                            </td>
                            <td class="px-4 py-3 text-right font-semibold text-slate-900 whitespace-nowrap">
                                {{ $row['amount'] !== null ? '¥'.number_format($row['amount']) : '—' }}
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="inline-flex items-center justify-end gap-2 flex-wrap">
                                    @if($status === 'generated' && $row['snapshot'])
                                        <a href="{{ route('admin.billing.show', $row['snapshot']) }}" class="text-primary-600 hover:underline font-medium whitespace-nowrap">{{ __('pages.details') }}</a>
                                    @endif
                                    @if($canManageBilling && $status === 'pending')
                                        <a href="{{ route('admin.billing.create', ['medical_institution_id' => $inst->id, 'target_month' => $billingMonth, 'target_date' => $row['closing_date']]) }}"
                                           class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg bg-primary-600 text-white text-[12px] font-semibold hover:bg-primary-700 whitespace-nowrap">
                                            {{ __('pages.generate_billing_data') }}
                                        </a>
                                    @elseif($canManageBilling && $status === 'generated')
                                        <a href="{{ route('admin.billing.create', ['medical_institution_id' => $inst->id, 'target_month' => $billingMonth, 'target_date' => $row['closing_date']]) }}"
                                           class="text-slate-500 hover:underline text-[12px] whitespace-nowrap">{{ __('pages.regenerate') }}</a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-10 text-center text-slate-500">{{ __('pages.no_institutions') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    {{-- Snapshot history --}}
    <section class="space-y-4 min-w-0">
        <h2 class="text-[15px] font-bold text-slate-900">{{ __('pages.billing_snapshot_history') }}</h2>

        <div class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden">
            <form method="GET" action="{{ route('admin.billing.index') }}" class="p-4" id="billing-filter-form">
                <input type="hidden" name="billing_month" value="{{ $billingMonth }}">
                <input type="hidden" name="status_filter" value="{{ $statusFilter }}">
                <x-per-page-field />
                <div class="mt-3 flex flex-col sm:flex-row sm:items-end gap-3">
                    <div class="flex-1 min-w-0">
                        <label class="block text-[12px] font-semibold text-slate-600 mb-1.5">{{ __('pages.medical_institution') }}</label>
                        <select name="medical_institution_id" id="billing-institution-select" class="ui-select" onchange="this.form.submit();">
                            <option value="">{{ __('pages.all_institutions') }}</option>
                            @foreach($institutions ?? [] as $institution)
                                <option value="{{ $institution->id }}" @selected($selectedInstitutionId == $institution->id)>
                                    {{ $institution->name }}
                                    @if(isset($billingCounts['institution'][$institution->id]) && $billingCounts['institution'][$institution->id] > 0)
                                        ({{ $billingCounts['institution'][$institution->id] }})
                                    @endif
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <a href="{{ route('admin.billing.index', ['billing_month' => $billingMonth]) }}" class="ui-btn ui-btn-secondary w-full sm:w-auto justify-center" title="{{ __('common.reset') }}">
                        <i data-lucide="x" class="w-4 h-4"></i>
                    </a>
                </div>
            </form>
        </div>

        <div class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden min-w-0">
            {{-- Mobile snapshot cards --}}
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
                        <p class="text-[12px] text-slate-600">{{ $snapshot->target_date->format('Y年n月') }} · {{ $snapshot->created_at->format('Y/m/d H:i') }}</p>
                        <a href="{{ route('admin.billing.show', $snapshot) }}"
                           class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg border border-slate-300 text-slate-700 text-[12px] font-medium">
                            <i data-lucide="eye" class="w-4 h-4"></i>
                            {{ __('pages.details') }}
                        </a>
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

            {{-- Desktop snapshot table --}}
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
                                <td class="px-5 py-4 text-slate-700 whitespace-nowrap">{{ $snapshot->target_date->format('Y年n月') }}</td>
                                <td class="px-5 py-4 text-slate-700">{{ $snapshot->data['invoice']['invoice_number'] ?? '-' }}</td>
                                <td class="px-5 py-4 text-right text-primary-700 font-semibold whitespace-nowrap">¥{{ number_format($snapshot->data['summary']['billing_amount'] ?? $snapshot->data['summary']['total_amount_ex_tax'] ?? 0) }}</td>
                                <td class="px-5 py-4 text-slate-600 whitespace-nowrap">{{ $snapshot->created_at->format('Y/m/d H:i') }}</td>
                                <td class="px-5 py-4 text-slate-600">{{ $snapshot->generatedBy?->name ?? '-' }}</td>
                                <td class="px-5 py-4 text-right">
                                    <a href="{{ route('admin.billing.show', $snapshot) }}"
                                       class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-50 text-[13px] font-medium">
                                        <i data-lucide="eye" class="w-4 h-4"></i>
                                        {{ __('pages.details') }}
                                    </a>
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
    </section>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    });
</script>
@endsection
