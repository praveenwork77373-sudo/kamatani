@extends('layouts.app')

@section('title', __('pages.billing_data') . ' | ' . config('app.name'))
@section('page_title', __('pages.billing_data'))

@section('content')
    @php
        $user = auth()->user();
        $admin = $user->adminUser ?? null;
        $adminRole = $admin->role ?? null;
        $canManageBilling = in_array($adminRole, ['system_admin', 'operation', 'accounting']);
    @endphp
    
    <div class="mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-slate-900 tracking-tight flex items-center gap-2">
                    <i data-lucide="receipt" class="w-6 h-6 text-primary-600"></i>
                    {{ __('pages.billing_approval') }}
                </h1>
                <p class="text-[14px] text-slate-600 mt-1.5">{{ __('pages.billing_approval_description') }}</p>
            </div>
            @if($canManageBilling)
                <a href="{{ route('admin.billing.create') }}" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg bg-gradient-to-r from-primary-600 to-primary-700 hover:from-primary-700 hover:to-primary-800 text-white text-[14px] font-semibold shadow-lg transition-all">
                    <i data-lucide="plus" class="w-4 h-4"></i>
                    {{ __('pages.generate_billing_data') }}
                </a>
            @endif
        </div>
    </div>

    @if(session('status'))
        <div class="mb-6 p-4 bg-emerald-50 border-l-4 border-emerald-500 rounded-lg">
            <div class="flex items-center gap-2">
                <i data-lucide="check-circle" class="w-5 h-5 text-emerald-600"></i>
                <span class="text-[14px] text-emerald-800 font-medium">{{ session('status') }}</span>
            </div>
        </div>
    @endif

    {{-- Statistics Cards --}}
    <x-stat-grid :cols="4">
        <x-stat-card tone="amber" icon="clock" :value="$stats['total_billing_snapshots'] ?? 0" :label="__('pages.billing_snapshots')" :badge="__('pages.total')" />
        <x-stat-card tone="blue" icon="calendar" :value="$stats['this_month_billing_snapshots'] ?? 0" :label="__('pages.this_month_billing_snapshots')" badge="今月" />
        <x-stat-card tone="purple" icon="japanese-yen" :value="$stats['total_billing_amount'] ?? 0" :label="__('pages.total_billing_amount')" :badge="__('pages.total')" prefix="¥" />
        <x-stat-card tone="emerald" icon="japanese-yen" :value="$stats['this_month_billing_amount'] ?? 0" :label="__('pages.this_month_billing_amount')" badge="今月" prefix="¥" />
    </x-stat-grid>

    @php
        $selectedInstitutionId = request('medical_institution_id');
    @endphp

    </div>

    {{-- Month-wise institution billing status board --}}
    <div class="mb-6 bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden">
        <div class="p-4 border-b border-slate-200 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3">
            <div>
                <h2 class="text-[15px] font-bold text-slate-900 flex items-center gap-2">
                    <i data-lucide="clipboard-list" class="w-4 h-4 text-primary-600"></i>
                    {{ __('pages.billing_month_board') }}
                </h2>
                <p class="text-[12px] text-slate-500 mt-1">{{ __('pages.billing_month_board_hint') }}</p>
            </div>
            <form method="GET" action="{{ route('admin.billing.index') }}" class="flex flex-wrap items-end gap-2">
                <div>
                    <label class="block text-[11px] font-semibold text-slate-600 mb-1">{{ __('pages.target_month') }}</label>
                    <input type="month" name="billing_month" value="{{ $billingMonth }}" class="ui-input py-2" onchange="this.form.submit()">
                </div>
                <input type="hidden" name="status_filter" value="{{ $statusFilter }}">
                @if(request('medical_institution_id'))
                    <input type="hidden" name="medical_institution_id" value="{{ request('medical_institution_id') }}">
                @endif
            </form>
        </div>

        <div class="px-4 py-3 flex flex-wrap gap-2 border-b border-slate-100 bg-slate-50/60">
            @php
                $filterBase = array_filter([
                    'billing_month' => $billingMonth,
                    'medical_institution_id' => request('medical_institution_id') ?: null,
                ]);
            @endphp
            <a href="{{ route('admin.billing.index', $filterBase + ['status_filter' => 'all']) }}"
               class="px-3 py-1.5 rounded-full text-[12px] font-semibold border {{ $statusFilter === 'all' ? 'bg-slate-800 text-white border-slate-800' : 'bg-white text-slate-600 border-slate-200' }}">
                {{ __('pages.all') }} ({{ $boardStats['total'] ?? 0 }})
            </a>
            <a href="{{ route('admin.billing.index', $filterBase + ['status_filter' => 'pending']) }}"
               class="px-3 py-1.5 rounded-full text-[12px] font-semibold border {{ $statusFilter === 'pending' ? 'bg-amber-600 text-white border-amber-600' : 'bg-white text-amber-700 border-amber-200' }}">
                {{ __('pages.billing_status_pending') }} ({{ $boardStats['pending'] ?? 0 }})
            </a>
            <a href="{{ route('admin.billing.index', $filterBase + ['status_filter' => 'generated']) }}"
               class="px-3 py-1.5 rounded-full text-[12px] font-semibold border {{ $statusFilter === 'generated' ? 'bg-emerald-600 text-white border-emerald-600' : 'bg-white text-emerald-700 border-emerald-200' }}">
                {{ __('pages.billing_status_generated') }} ({{ $boardStats['generated'] ?? 0 }})
            </a>
            <a href="{{ route('admin.billing.index', $filterBase + ['status_filter' => 'no_contract']) }}"
               class="px-3 py-1.5 rounded-full text-[12px] font-semibold border {{ $statusFilter === 'no_contract' ? 'bg-slate-500 text-white border-slate-500' : 'bg-white text-slate-500 border-slate-200' }}">
                {{ __('pages.billing_status_no_contract') }} ({{ $boardStats['no_contract'] ?? 0 }})
            </a>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full text-[13px]">
                <thead class="bg-slate-50 text-slate-600">
                    <tr>
                        <th class="px-4 py-3 text-left">{{ __('pages.medical_institution_label') }}</th>
                        <th class="px-4 py-3 text-left">{{ __('pages.closing_day') }}</th>
                        <th class="px-4 py-3 text-left">{{ __('pages.billing_reference_date') }}</th>
                        <th class="px-4 py-3 text-left">{{ __('pages.status') }}</th>
                        <th class="px-4 py-3 text-right">{{ __('pages.assigned_count') }}</th>
                        <th class="px-4 py-3 text-right">{{ __('pages.billing_amount') }}</th>
                        <th class="px-4 py-3 text-right">{{ __('pages.actions_label') }}</th>
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
                            <td class="px-4 py-3 text-slate-600">{{ __('pages.every_month_day', ['day' => $row['closing_day']]) }}</td>
                            <td class="px-4 py-3 font-mono text-slate-700">{{ $row['closing_date'] }}</td>
                            <td class="px-4 py-3">
                                @if($status === 'generated')
                                    <span class="inline-flex items-center gap-1 px-2 py-1 rounded-md bg-emerald-50 text-emerald-700 text-[12px] font-semibold">
                                        <i data-lucide="check-circle" class="w-3.5 h-3.5"></i>
                                        {{ __('pages.billing_status_generated') }}
                                    </span>
                                @elseif($status === 'pending')
                                    <span class="inline-flex items-center gap-1 px-2 py-1 rounded-md bg-amber-50 text-amber-700 text-[12px] font-semibold">
                                        <i data-lucide="clock" class="w-3.5 h-3.5"></i>
                                        {{ __('pages.billing_status_pending') }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2 py-1 rounded-md bg-slate-100 text-slate-500 text-[12px] font-semibold">
                                        {{ __('pages.billing_status_no_contract') }}
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right text-slate-700">
                                {{ $row['assigned_count'] !== null ? number_format($row['assigned_count']) : '—' }}
                            </td>
                            <td class="px-4 py-3 text-right font-semibold text-slate-900">
                                {{ $row['amount'] !== null ? '¥'.number_format($row['amount']) : '—' }}
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="inline-flex items-center gap-2">
                                    @if($status === 'generated' && $row['snapshot'])
                                        <a href="{{ route('admin.billing.show', $row['snapshot']) }}" class="text-primary-600 hover:underline font-medium">{{ __('pages.details') }}</a>
                                    @endif
                                    @if($canManageBilling && $status === 'pending')
                                        <a href="{{ route('admin.billing.create', ['medical_institution_id' => $inst->id, 'target_month' => $billingMonth, 'target_date' => $row['closing_date']]) }}"
                                           class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg bg-primary-600 text-white text-[12px] font-semibold hover:bg-primary-700">
                                            {{ __('pages.generate_billing_data') }}
                                        </a>
                                    @elseif($canManageBilling && $status === 'generated')
                                        <a href="{{ route('admin.billing.create', ['medical_institution_id' => $inst->id, 'target_month' => $billingMonth, 'target_date' => $row['closing_date']]) }}"
                                           class="text-slate-500 hover:underline text-[12px]">{{ __('pages.regenerate') }}</a>
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
    </div>

    <div class="space-y-6">
        <h2 class="text-[15px] font-bold text-slate-900">{{ __('pages.billing_snapshot_history') }}</h2>
        {{-- Horizontal filters above table --}}
        <div class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden">
            <form method="GET" action="{{ route('admin.billing.index') }}" class="p-4" id="billing-filter-form">
                <x-per-page-field />
                <div class="flex flex-col lg:flex-row lg:items-end gap-3">
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
                    <div class="flex gap-2 shrink-0">
                        <a href="{{ route('admin.billing.index') }}" class="ui-btn ui-btn-secondary" title="{{ __('common.reset') }}">
                            <i data-lucide="x" class="w-4 h-4"></i>
                        </a>
                    </div>
                </div>
            </form>
        </div>

        {{-- Full-width Billing List --}}
        <div class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full text-[14px]">
                    <thead class="bg-slate-50 border-b border-slate-200">
                        <tr>
                            <th class="px-5 py-3.5 text-left font-semibold text-slate-700">{{ __('pages.billing_id') }}</th>
                            <th class="px-5 py-3.5 text-left font-semibold text-slate-700">{{ __('pages.medical_institution_label') }}</th>
                            <th class="px-5 py-3.5 text-left font-semibold text-slate-700">{{ __('pages.target_month') }}</th>
                            <th class="px-5 py-3.5 text-left font-semibold text-slate-700">{{ __('pages.invoice_number') }}</th>
                            <th class="px-5 py-3.5 text-right font-semibold text-slate-700">{{ __('pages.billing_amount') }}</th>
                            <th class="px-5 py-3.5 text-left font-semibold text-slate-700">{{ __('pages.generated_at') }}</th>
                            <th class="px-5 py-3.5 text-left font-semibold text-slate-700">{{ __('pages.generated_by') }}</th>
                            <th class="px-5 py-3.5 text-right font-semibold text-slate-700">{{ __('pages.actions_label') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200" id="billing-table-body">
                        @forelse($billingSnapshots as $snapshot)
                            <tr class="hover:bg-slate-50 transition-colors">
                                <td class="px-5 py-4 font-semibold text-slate-900">
                                    <div class="flex items-center gap-2">
                                        <i data-lucide="receipt" class="w-4 h-4 text-slate-400"></i>
                                        <span>#BILL-{{ str_pad($snapshot->id, 4, '0', STR_PAD_LEFT) }}</span>
                                    </div>
                                </td>
                                <td class="px-5 py-4 text-slate-800">
                                    <div class="flex items-center gap-2">
                                        <i data-lucide="building" class="w-4 h-4 text-slate-400"></i>
                                        <span>{{ $snapshot->medicalInstitution?->name ?? '-' }}</span>
                                    </div>
                                </td>
                                <td class="px-5 py-4 text-slate-700">
                                    <div class="flex items-center gap-2">
                                        <i data-lucide="calendar" class="w-4 h-4 text-slate-400"></i>
                                        <span>{{ $snapshot->target_date->format('Y年n月') }}</span>
                                    </div>
                                </td>
                                <td class="px-5 py-4 text-slate-700">
                                    <div class="flex items-center gap-2">
                                        <i data-lucide="file-text" class="w-4 h-4 text-slate-400"></i>
                                        <span>{{ $snapshot->data['invoice']['invoice_number'] ?? '-' }}</span>
                                    </div>
                                </td>
                                <td class="px-5 py-4 text-right text-slate-900 font-semibold">
                                    <div class="flex items-center justify-end gap-2">
                                        <i data-lucide="yen-sign" class="w-4 h-4 text-slate-400"></i>
                                        <span class="text-primary-700">¥{{ number_format($snapshot->data['summary']['billing_amount'] ?? 0) }}</span>
                                    </div>
                                </td>
                                <td class="px-5 py-4 text-slate-600">
                                    <div class="flex items-center gap-2">
                                        <i data-lucide="clock" class="w-4 h-4 text-slate-400"></i>
                                        <span>{{ $snapshot->created_at->format('Y/m/d H:i') }}</span>
                                    </div>
                                </td>
                                <td class="px-5 py-4 text-slate-600">
                                    <div class="flex items-center gap-2">
                                        <i data-lucide="user" class="w-4 h-4 text-slate-400"></i>
                                        <span>{{ $snapshot->generatedBy?->name ?? '-' }}</span>
                                    </div>
                                </td>
                                <td class="px-5 py-4 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <a href="{{ route('admin.billing.show', $snapshot) }}"
                                           class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-50 hover:border-slate-400 text-[13px] font-medium transition-all">
                                            <i data-lucide="eye" class="w-4 h-4"></i>
                                            {{ __('pages.details') }}
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="px-5 py-12 text-center">
                                    <div class="flex flex-col items-center gap-3">
                                        <div class="w-16 h-16 rounded-full bg-slate-100 flex items-center justify-center">
                                            <i data-lucide="receipt" class="w-8 h-8 text-slate-300"></i>
                                        </div>
                                        <p class="text-slate-500 text-[14px] font-medium">{{ __('pages.no_billing_snapshots') }}</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <x-table-pagination :paginator="$billingSnapshots" />



        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }
        });
    </script>
@endsection
