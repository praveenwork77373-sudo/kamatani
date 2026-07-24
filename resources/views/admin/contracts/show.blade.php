@extends('layouts.app')

@section('title', __('pages.contract_details') . ' | ' . __('auth.system_title'))
@section('page_title', __('pages.contract_details'))

@section('content')
    <div class="mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-slate-900 tracking-tight flex items-center gap-2">
                    <i data-lucide="file-text" class="w-6 h-6 text-primary-600"></i>
                    {{ __('pages.contract_details') }}
                </h1>
                <p class="text-[14px] text-slate-600 mt-1.5">{{ __('pages.contract_description') }}</p>
            </div>
            <div class="flex items-center gap-2">
                @php
                    $admin = auth()->user()->adminUser ?? null;
                    $adminRole = $admin->role ?? null;
                    $canManage = in_array($adminRole, ['system_admin', 'operation', 'sales']);
                @endphp
                @if($canManage)
                    <a href="{{ route('admin.contracts.edit', $contract) }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg border border-primary-300 bg-primary-50 text-primary-700 hover:bg-primary-100 text-[14px] font-medium transition-all">
                        <i data-lucide="edit" class="w-4 h-4"></i>
                        {{ __('pages.edit') }}
                    </a>
                @endif
                <a href="{{ route('admin.contracts.index') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-50 text-[14px] font-medium transition-all">
                    <i data-lucide="arrow-left" class="w-4 h-4"></i>
                    {{ __('pages.back_to_list') }}
                </a>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200 shadow-lg overflow-hidden">
        {{-- Basic Information Section --}}
        <div class="p-6 border-b border-slate-200 bg-gradient-to-r from-primary-50 via-blue-50 to-indigo-50">
            <h2 class="text-lg font-semibold text-slate-900 mb-4 flex items-center gap-2">
                <i data-lucide="info" class="w-5 h-5 text-primary-600"></i>
                {{ __('pages.basic_info') }}
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-slate-500 text-[13px] font-medium mb-1.5 flex items-center gap-2">
                        <i data-lucide="building" class="w-4 h-4"></i>
                        {{ __('pages.medical_institution') }}
                    </label>
                    <p class="text-[15px] font-semibold text-slate-900">
                        {{ $contract->medicalInstitution->name ?? '-' }}
                        @if($contract->medicalInstitution->code ?? null)
                            <span class="text-[13px] text-slate-500 font-normal">({{ $contract->medicalInstitution->code }})</span>
                        @endif
                    </p>
                </div>

                @if($contract->contract_number)
                <div>
                    <label class="block text-slate-500 text-[13px] font-medium mb-1.5 flex items-center gap-2">
                        <i data-lucide="hash" class="w-4 h-4"></i>
                        {{ __('pages.contract_number') }}
                    </label>
                    <p class="text-[15px] font-semibold text-slate-900">
                        {{ $contract->contract_number }}
                    </p>
                </div>
                @endif

                <div>
                    <label class="block text-slate-500 text-[13px] font-medium mb-1.5 flex items-center gap-2">
                        <i data-lucide="file-signature" class="w-4 h-4"></i>
                        {{ __('pages.contract_type') }}
                    </label>
                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-[14px] font-medium
                        {{ $contract->contract_type === 'FIXED' ? 'bg-blue-50 text-blue-700 border border-blue-200' : 'bg-purple-50 text-purple-700 border border-purple-200' }}">
                        <i data-lucide="{{ $contract->contract_type === 'FIXED' ? 'dollar-sign' : 'coins' }}" class="w-4 h-4"></i>
                        {{ $contract->contract_type === 'FIXED' ? __('pages.fixed_price_system') : __('pages.point_system') }}
                    </span>
                </div>

                <div>
                    <label class="block text-slate-500 text-[13px] font-medium mb-1.5 flex items-center gap-2">
                        <i data-lucide="dollar-sign" class="w-4 h-4"></i>
                        {{ __('pages.billing_model') }}
                    </label>
                    <p class="text-[15px] font-semibold text-slate-900">
                        {{ $contract->billing_model === 'fixed_per_person' ? __('pages.per_employee') : __('pages.per_usage') }}
                    </p>
                </div>

                <div>
                    <label class="block text-slate-500 text-[13px] font-medium mb-1.5 flex items-center gap-2">
                        <i data-lucide="calendar" class="w-4 h-4"></i>
                        {{ __('pages.contract_start') }}
                    </label>
                    <p class="text-[15px] font-semibold text-slate-900">
                        {{ $contract->valid_from ? $contract->valid_from->format('Y年m月d日') : '-' }}
                    </p>
                </div>

                <div>
                    <label class="block text-slate-500 text-[13px] font-medium mb-1.5 flex items-center gap-2">
                        <i data-lucide="calendar-check" class="w-4 h-4"></i>
                        {{ __('pages.contract_end') }}
                    </label>
                    <p class="text-[15px] font-semibold text-slate-900">
                        {{ $contract->valid_until ? $contract->valid_until->format('Y年m月d日') : __('pages.unlimited') }}
                    </p>
                </div>
            </div>
        </div>

        {{-- Loan Limit Settings Section --}}
        <div class="p-6 border-b border-slate-200">
            <h2 class="text-lg font-semibold text-slate-900 mb-4 flex items-center gap-2">
                <i data-lucide="sliders" class="w-5 h-5 text-primary-600"></i>
                {{ __('pages.loan_limit_settings') }}
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-slate-500 text-[13px] font-medium mb-1.5 flex items-center gap-2">
                        <i data-lucide="user" class="w-4 h-4"></i>
                        {{ __('pages.per_person_limit') }}
                    </label>
                    <p class="text-[15px] font-semibold text-slate-900">
                        {{ $contract->per_person_loan_limit ? number_format($contract->per_person_loan_limit) . ' ' . __('pages.points') : __('pages.unlimited') }}
                    </p>
                    @if($contract->per_person_loan_limit)
                        <p class="text-[12px] text-slate-500 mt-1">{{ __('pages.per_person_limit_desc') }}</p>
                    @endif
                </div>

                <div>
                    <label class="block text-slate-500 text-[13px] font-medium mb-1.5 flex items-center gap-2">
                        <i data-lucide="package" class="w-4 h-4"></i>
                        {{ __('pages.per_item_limit') }}
                    </label>
                    <p class="text-[15px] font-semibold text-slate-900">
                        {{ $contract->loan_limit_per_item ? number_format($contract->loan_limit_per_item) . ' ' . __('pages.points') : __('pages.unlimited') }}
                    </p>
                    @if($contract->loan_limit_per_item)
                        <p class="text-[12px] text-slate-500 mt-1">{{ __('pages.per_item_limit_desc') }}</p>
                    @endif
                </div>
            </div>
        </div>

        {{-- Pricing Settings Section --}}
        <div class="p-6 border-b border-slate-200">
            <h2 class="text-lg font-semibold text-slate-900 mb-4 flex items-center gap-2">
                <i data-lucide="dollar-sign" class="w-5 h-5 text-primary-600"></i>
                {{ __('pages.pricing_settings') }}
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                @if($contract->billing_model === 'fixed_per_person' && $contract->fixed_unit_price)
                <div>
                    <label class="block text-slate-500 text-[13px] font-medium mb-1.5 flex items-center gap-2">
                        <i data-lucide="yen" class="w-4 h-4"></i>
                        {{ __('pages.fixed_price') }}
                    </label>
                    <p class="text-[15px] font-semibold text-slate-900">
                        ¥{{ number_format($contract->fixed_unit_price, 0) }} / {{ __('pages.per_employee') }}
                    </p>
                    <p class="text-[12px] text-slate-500 mt-1">{{ __('pages.fixed_price_desc') }}</p>
                </div>
                @endif

                @if($contract->billing_model === 'point_usage' && $contract->point_unit_price)
                <div>
                    <label class="block text-slate-500 text-[13px] font-medium mb-1.5 flex items-center gap-2">
                        <i data-lucide="dollar-sign" class="w-4 h-4"></i>
                        {{ __('pages.point_price') }}
                    </label>
                    <p class="text-[15px] font-semibold text-slate-900">
                        ¥{{ number_format($contract->point_unit_price, 0) }} / {{ __('pages.points') }}
                    </p>
                    <p class="text-[12px] text-slate-500 mt-1">{{ __('pages.point_price_desc') }}</p>
                </div>
                @endif

                @if($contract->cleaning_multiplier)
                <div>
                    <label class="block text-slate-500 text-[13px] font-medium mb-1.5 flex items-center gap-2">
                        <i data-lucide="refresh-cw" class="w-4 h-4"></i>
                        {{ __('pages.cleaning_multiplier') }}
                    </label>
                    <p class="text-[15px] font-semibold text-slate-900">
                        {{ number_format($contract->cleaning_multiplier, 2) }}
                    </p>
                    <p class="text-[12px] text-slate-500 mt-1">{{ __('pages.cleaning_multiplier_desc') }}</p>
                </div>
                @endif
            </div>
        </div>

        {{-- Contract Status Section --}}
        <div class="p-6 bg-gradient-to-r from-slate-50 to-slate-100">
            <h2 class="text-lg font-semibold text-slate-900 mb-4 flex items-center gap-2">
                <i data-lucide="info" class="w-5 h-5 text-primary-600"></i>
                {{ __('pages.contract_status') }}
            </h2>
            <div class="flex items-center gap-3">
                @php
                    $now = now();
                    $isActive = (!$contract->valid_from || $contract->valid_from <= $now) && 
                                (!$contract->valid_until || $contract->valid_until >= $now);
                    $isExpired = $contract->valid_until && $contract->valid_until < $now;
                    $isFuture = $contract->valid_from && $contract->valid_from > $now;
                @endphp
                @if($isActive)
                    <span class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg bg-emerald-50 text-emerald-700 border border-emerald-200 text-[14px] font-semibold">
                        <i data-lucide="check-circle" class="w-4 h-4"></i>
                        {{ __('pages.active') }}
                    </span>
                @elseif($isExpired)
                    <span class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg bg-red-50 text-red-700 border border-red-200 text-[14px] font-semibold">
                        <i data-lucide="x-circle" class="w-4 h-4"></i>
                        {{ __('pages.expired') }}
                    </span>
                @elseif($isFuture)
                    <span class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg bg-blue-50 text-blue-700 border border-blue-200 text-[14px] font-semibold">
                        <i data-lucide="clock" class="w-4 h-4"></i>
                        {{ __('pages.future') }}
                    </span>
                @endif
                <span class="text-[13px] text-slate-600">
                    @if($contract->valid_from && $contract->valid_until)
                        {{ __('pages.contract_period') }}: {{ $contract->valid_from->format('Y/m/d') }} - {{ $contract->valid_until->format('Y/m/d') }}
                    @elseif($contract->valid_from)
                        {{ __('pages.contract_period') }}: {{ $contract->valid_from->format('Y/m/d') }} - {{ __('pages.unlimited') }}
                    @else
                        {{ __('pages.contract_period') }}: {{ __('pages.unlimited') }}
                    @endif
                </span>
            </div>
        </div>
    </div>

    {{-- Action Buttons --}}
    @php
        $admin = auth()->user()->adminUser ?? null;
        $adminRole = $admin->role ?? null;
        $canManage = in_array($adminRole, ['system_admin', 'operation', 'sales']);
    @endphp
    <div class="mt-6 flex items-center justify-end gap-3">
        <a href="{{ route('admin.contracts.index') }}" class="px-5 py-2.5 rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-50 text-[14px] font-medium transition-all">
            {{ __('pages.back_to_list') }}
        </a>
        @if($canManage)
            <a href="{{ route('admin.contracts.edit', $contract) }}" class="px-6 py-2.5 rounded-lg bg-gradient-to-r from-primary-600 via-primary-700 to-blue-600 hover:from-primary-700 hover:via-primary-800 hover:to-blue-700 text-white text-[14px] font-semibold shadow-lg transition-all flex items-center gap-2">
                <i data-lucide="edit" class="w-4 h-4"></i>
                {{ __('pages.edit') }}
            </a>
        @endif
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

