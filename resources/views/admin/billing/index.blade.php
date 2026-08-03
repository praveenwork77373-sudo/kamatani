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

    <div class="space-y-6">
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
