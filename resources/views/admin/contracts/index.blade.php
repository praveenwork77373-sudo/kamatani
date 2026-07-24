@extends('layouts.app')

@section('title', __('menu.contracts') . ' | ' . __('auth.system_title'))
@section('page_title', __('menu.contracts'))

@section('content')
    @php
        $user = auth()->user();
        $admin = $user->adminUser ?? null;
        $adminRole = $admin->role ?? null;
        $canManage = in_array($adminRole, ['system_admin', 'operation', 'sales']);
    @endphp
    
    @if(session('status'))
        <div class="mb-6 p-4 bg-emerald-50 border-l-4 border-emerald-500 rounded-lg">
            <div class="flex items-center gap-2">
                <i data-lucide="check-circle" class="w-5 h-5 text-emerald-600"></i>
                <span class="text-[14px] text-emerald-800 font-medium">{{ session('status') }}</span>
            </div>
        </div>
    @endif

    <div class="mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-slate-900 tracking-tight flex items-center gap-2">
                    <i data-lucide="file-signature" class="w-6 h-6 text-primary-600"></i>
                    {{ __('pages.contract_management') }}
                </h1>
                <p class="text-[14px] text-slate-600 mt-1.5">{{ __('pages.contract_description') }}</p>
            </div>
            @if($canManage)
                <a href="{{ route('admin.contracts.create') }}" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg bg-gradient-to-r from-primary-600 to-primary-700 hover:from-primary-700 hover:to-primary-800 text-white text-[14px] font-semibold shadow-lg transition-all">
                    <i data-lucide="plus" class="w-4 h-4"></i>
                    {{ __('pages.new_contract_registration') }}
                </a>
            @endif
        </div>
    </div>

    {{-- Statistics Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4 mb-8">
        {{-- Total Contracts --}}
        <div class="relative overflow-hidden rounded-xl bg-gradient-to-br from-blue-50 via-blue-100 to-indigo-100 border border-blue-200/50 p-4 shadow-sm hover:shadow-md transition-all duration-300">
            <div class="relative z-10">
                <div class="flex items-center justify-between mb-3">
                    <div class="h-10 w-10 rounded-lg bg-blue-500/10 flex items-center justify-center">
                        <i data-lucide="file-text" class="w-5 h-5 text-blue-600"></i>
                    </div>
                    <span class="px-2 py-0.5 rounded-full bg-blue-500/10 text-blue-700 text-[10px] font-semibold">{{ __('pages.total') }}</span>
                </div>
                <p class="text-3xl font-bold text-slate-900 mb-1">{{ number_format($stats['total_contracts'] ?? 0) }}</p>
                <p class="text-slate-600 text-[13px] font-medium">{{ __('pages.total_contracts') }}</p>
            </div>
        </div>

        {{-- Active Contracts --}}
        <div class="relative overflow-hidden rounded-xl bg-gradient-to-br from-emerald-50 via-teal-50 to-cyan-50 border border-emerald-200/50 p-4 shadow-sm hover:shadow-md transition-all duration-300">
            <div class="relative z-10">
                <div class="flex items-center justify-between mb-3">
                    <div class="h-10 w-10 rounded-lg bg-emerald-500/10 flex items-center justify-center">
                        <i data-lucide="check-circle" class="w-5 h-5 text-emerald-600"></i>
                    </div>
                    <span class="px-2 py-0.5 rounded-full bg-emerald-500/10 text-emerald-700 text-[10px] font-semibold">{{ __('pages.active') }}</span>
                </div>
                <p class="text-3xl font-bold text-slate-900 mb-1">{{ number_format($stats['active_contracts'] ?? 0) }}</p>
                <p class="text-slate-600 text-[13px] font-medium">有効契約</p>
                <p class="text-slate-500 text-[12px] mt-1.5">
                    {{ $stats['total_contracts'] > 0 ? round(($stats['active_contracts'] / $stats['total_contracts']) * 100, 1) : 0 }}% の割合
                </p>
            </div>
        </div>

        {{-- Expiring Soon --}}
        <div class="relative overflow-hidden rounded-xl bg-gradient-to-br from-amber-50 via-orange-50 to-red-50 border border-amber-200/50 p-4 shadow-sm hover:shadow-md transition-all duration-300">
            <div class="relative z-10">
                <div class="flex items-center justify-between mb-3">
                    <div class="h-10 w-10 rounded-lg bg-amber-500/10 flex items-center justify-center">
                        <i data-lucide="alert-triangle" class="w-5 h-5 text-amber-600"></i>
                    </div>
                    <span class="px-2 py-0.5 rounded-full bg-amber-500/10 text-amber-700 text-[10px] font-semibold">{{ __('pages.within_30_days') }}</span>
                </div>
                <p class="text-3xl font-bold text-slate-900 mb-1">{{ number_format($stats['expiring_soon'] ?? 0) }}</p>
                <p class="text-slate-600 text-[13px] font-medium">{{ __('pages.expiring_soon') }}</p>
            </div>
        </div>

        {{-- Fixed Price Contracts --}}
        <div class="relative overflow-hidden rounded-xl bg-gradient-to-br from-purple-50 via-pink-50 to-rose-50 border border-purple-200/50 p-4 shadow-sm hover:shadow-md transition-all duration-300">
            <div class="relative z-10">
                <div class="flex items-center justify-between mb-3">
                    <div class="h-10 w-10 rounded-lg bg-purple-500/10 flex items-center justify-center">
                        <i data-lucide="dollar-sign" class="w-5 h-5 text-purple-600"></i>
                    </div>
                    <span class="px-2 py-0.5 rounded-full bg-purple-500/10 text-purple-700 text-[10px] font-semibold">{{ __('pages.fixed_price_system') }}</span>
                </div>
                <p class="text-3xl font-bold text-slate-900 mb-1">{{ number_format($stats['fixed_price_contracts'] ?? 0) }}</p>
                <p class="text-slate-600 text-[13px] font-medium">{{ __('pages.fixed_price_contracts') }}</p>
            </div>
        </div>

        {{-- Point Based Contracts --}}
        <div class="relative overflow-hidden rounded-xl bg-gradient-to-br from-indigo-50 via-blue-50 to-cyan-50 border border-indigo-200/50 p-4 shadow-sm hover:shadow-md transition-all duration-300">
            <div class="relative z-10">
                <div class="flex items-center justify-between mb-3">
                    <div class="h-10 w-10 rounded-lg bg-indigo-500/10 flex items-center justify-center">
                        <i data-lucide="coins" class="w-5 h-5 text-indigo-600"></i>
                    </div>
                    <span class="px-2 py-0.5 rounded-full bg-indigo-500/10 text-indigo-700 text-[10px] font-semibold">{{ __('pages.point_system') }}</span>
                </div>
                <p class="text-3xl font-bold text-slate-900 mb-1">{{ number_format($stats['point_based_contracts'] ?? 0) }}</p>
                <p class="text-slate-600 text-[13px] font-medium">{{ __('pages.point_based_contracts') }}</p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="p-4 border-b border-slate-200 bg-gradient-to-r from-slate-50 to-slate-100">
            <div class="flex items-center justify-between">
                <h2 class="text-[15px] font-semibold text-slate-900 flex items-center gap-2">
                    <i data-lucide="file-signature" class="w-4 h-4 text-primary-600"></i>
                    {{ __('pages.contract_list_title') }}
                </h2>
                <span class="text-[13px] text-slate-600">
                    {{ __('pages.all_items_showing') }} {{ $contracts->total() }}{{ __('pages.items_showing') }} {{ $contracts->firstItem() ?? 0 }}-{{ $contracts->lastItem() ?? 0 }}{{ __('pages.items_displayed') }}
                </span>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full text-[14px]">
                <thead class="bg-slate-50 border-b border-slate-200">
                    <tr>
                        <th class="px-5 py-3.5 text-left font-semibold text-slate-700">{{ __('pages.medical_institution_label') }}</th>
                        <th class="px-5 py-3.5 text-left font-semibold text-slate-700">{{ __('pages.contract_type_label') }}</th>
                        <th class="px-5 py-3.5 text-left font-semibold text-slate-700">{{ __('pages.billing_model_label') }}</th>
                        <th class="px-5 py-3.5 text-left font-semibold text-slate-700">{{ __('pages.contract_period_label') }}</th>
                        <th class="px-5 py-3.5 text-right font-semibold text-slate-700">{{ __('pages.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @forelse($contracts as $contract)
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="h-10 w-10 rounded-lg bg-gradient-to-br from-primary-400 to-primary-600 flex items-center justify-center text-white">
                                        <i data-lucide="building" class="w-5 h-5"></i>
                                    </div>
                                    <span class="font-medium text-slate-900">{{ $contract->medicalInstitution->name }}</span>
                                </div>
                            </td>
                            <td class="px-5 py-4">
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[13px] font-medium
                                    {{ $contract->contract_type === 'FIXED' ? 'bg-blue-50 text-blue-700' : 'bg-purple-50 text-purple-700' }}">
                                    <i data-lucide="{{ $contract->contract_type === 'FIXED' ? 'dollar-sign' : 'coins' }}" class="w-3.5 h-3.5"></i>
                                    {{ $contract->contract_type === 'FIXED' ? __('pages.fixed_price_system') : __('pages.point_system') }}
                                </span>
                            </td>
                            <td class="px-5 py-4">
                                <span class="text-slate-700">{{ $contract->billing_model === 'fixed_per_person' ? __('pages.per_employee') : __('pages.per_usage') }}</span>
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-2 text-slate-600">
                                    <i data-lucide="calendar" class="w-4 h-4"></i>
                                    <span class="text-[13px]">
                                        @if($contract->valid_from && $contract->valid_until)
                                            {{ $contract->valid_from->format('Y/m/d') }} - {{ $contract->valid_until->format('Y/m/d') }}
                                        @elseif($contract->valid_from)
                                            {{ $contract->valid_from->format('Y/m/d') }} - {{ __('pages.unlimited') }}
                                        @else
                                            {{ __('pages.unlimited') }}
                                        @endif
                                    </span>
                                </div>
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('admin.contracts.show', $contract) }}" class="p-2 text-slate-600 hover:text-primary-600 hover:bg-primary-50 rounded-lg transition-all" title="{{ __('pages.view') }}">
                                        <i data-lucide="eye" class="w-4 h-4"></i>
                                    </a>
                                    @if($canManage)
                                        <a href="{{ route('admin.contracts.edit', $contract) }}" class="p-2 text-slate-600 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-all" title="{{ __('pages.edit') }}">
                                            <i data-lucide="edit" class="w-4 h-4"></i>
                                        </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-12 text-center">
                                <div class="flex flex-col items-center gap-3 text-slate-400">
                                    <i data-lucide="file-x" class="w-12 h-12"></i>
                                    <p class="text-[14px] font-medium">{{ __('pages.no_data') }}</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-6 py-4 border-t border-slate-200 bg-slate-50">
            {{ $contracts->links() }}
        </div>
    </div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    });
</script>
@endpush
