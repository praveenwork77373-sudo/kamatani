@extends('layouts.app')

@section('title', __('pages.edit_contract') . ' | ' . __('auth.system_title'))
@section('page_title', __('pages.edit_contract'))

@section('content')
    <div class="mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-slate-900 tracking-tight flex items-center gap-2">
                    <i data-lucide="file-edit" class="w-6 h-6 text-primary-600"></i>
                    {{ __('pages.edit_contract') }}
                </h1>
                <p class="text-[14px] text-slate-600 mt-1.5">{{ __('pages.contract_description') }}</p>
            </div>
            <a href="{{ route('admin.contracts.index') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-50 text-[14px] font-medium transition-all">
                <i data-lucide="arrow-left" class="w-4 h-4"></i>
                {{ __('pages.back_to_list') }}
            </a>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.contracts.update', $contract) }}">
        @csrf
        @method('PUT')
        
        <div class="bg-white rounded-2xl border border-slate-200 shadow-lg overflow-hidden">
            <div class="p-6 border-b border-slate-200 bg-gradient-to-r from-primary-50 via-blue-50 to-indigo-50">
                <h2 class="text-lg font-semibold text-slate-900 mb-4 flex items-center gap-2">
                    <i data-lucide="info" class="w-5 h-5 text-primary-600"></i>
                    {{ __('pages.basic_info') }}
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-slate-700 text-[14px] font-semibold mb-2 flex items-center gap-2">
                            <i data-lucide="building" class="w-4 h-4 text-slate-500"></i>
                            {{ __('pages.medical_institution') }} <span class="text-red-500">*</span>
                        </label>
                        <select name="medical_institution_id" required
                                class="w-full px-4 py-2.5 rounded-lg border border-slate-300 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 text-[14px] bg-white shadow-sm transition-all">
                            <option value="">{{ __('pages.select_please') }}</option>
                            @foreach($institutions as $inst)
                                <option value="{{ $inst->id }}" @selected(old('medical_institution_id', $contract->medical_institution_id)==$inst->id)>
                                    {{ $inst->name }} ({{ $inst->code }})
                                </option>
                            @endforeach
                        </select>
                        @error('medical_institution_id')
                            <p class="text-[13px] text-red-600 mt-1.5 flex items-center gap-1">
                                <i data-lucide="alert-circle" class="w-3 h-3"></i>
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-slate-700 text-[14px] font-semibold mb-2 flex items-center gap-2">
                            <i data-lucide="hash" class="w-4 h-4 text-slate-500"></i>
                            {{ __('pages.contract_number') }}
                        </label>
                        <input type="text" name="contract_number" value="{{ old('contract_number', $contract->contract_number) }}" maxlength="50"
                               class="w-full px-4 py-2.5 rounded-lg border border-slate-300 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 text-[14px] bg-white shadow-sm transition-all"
                               placeholder="CT-2025-0001">
                        <p class="text-[12px] text-slate-500 mt-1">{{ __('pages.contract_number_desc') }}</p>
                        @error('contract_number')
                            <p class="text-[13px] text-red-600 mt-1.5 flex items-center gap-1">
                                <i data-lucide="alert-circle" class="w-3 h-3"></i>
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-slate-700 text-[14px] font-semibold mb-2 flex items-center gap-2">
                            <i data-lucide="file-signature" class="w-4 h-4 text-slate-500"></i>
                            {{ __('pages.contract_type') }} <span class="text-red-500">*</span>
                        </label>
                        <select name="contract_type" id="contract_type" required
                                class="w-full px-4 py-2.5 rounded-lg border border-slate-300 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 text-[14px] bg-white shadow-sm transition-all">
                            <option value="FIXED" @selected(old('contract_type', $contract->contract_type)==='FIXED')>{{ __('pages.fixed_price_system') }} (FIXED)</option>
                            <option value="POINT_BASED" @selected(old('contract_type', $contract->contract_type)==='POINT_BASED')>{{ __('pages.point_system') }} (POINT_BASED)</option>
                        </select>
                        @error('contract_type')
                            <p class="text-[13px] text-red-600 mt-1.5 flex items-center gap-1">
                                <i data-lucide="alert-circle" class="w-3 h-3"></i>
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-slate-700 text-[14px] font-semibold mb-2 flex items-center gap-2">
                            <i data-lucide="dollar-sign" class="w-4 h-4 text-slate-500"></i>
                            {{ __('pages.billing_model') }} <span class="text-red-500">*</span>
                        </label>
                        <select name="billing_model" required
                                class="w-full px-4 py-2.5 rounded-lg border border-slate-300 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 text-[14px] bg-white shadow-sm transition-all">
                            <option value="fixed_per_person" @selected(old('billing_model', $contract->billing_model)==='fixed_per_person')>{{ __('pages.per_employee') }}</option>
                            <option value="point_usage" @selected(old('billing_model', $contract->billing_model)==='point_usage')>{{ __('pages.per_usage') }}</option>
                        </select>
                        @error('billing_model')
                            <p class="text-[13px] text-red-600 mt-1.5 flex items-center gap-1">
                                <i data-lucide="alert-circle" class="w-3 h-3"></i>
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-slate-700 text-[14px] font-semibold mb-2 flex items-center gap-2">
                            <i data-lucide="calendar" class="w-4 h-4 text-slate-500"></i>
                            {{ __('pages.contract_start') }} <span class="text-red-500">*</span>
                        </label>
                        <input type="date" name="valid_from" value="{{ old('valid_from', $contract->valid_from?->format('Y-m-d')) }}" required
                               class="w-full px-4 py-2.5 rounded-lg border border-slate-300 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 text-[14px] bg-white shadow-sm transition-all">
                        @error('valid_from')
                            <p class="text-[13px] text-red-600 mt-1.5 flex items-center gap-1">
                                <i data-lucide="alert-circle" class="w-3 h-3"></i>
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-slate-700 text-[14px] font-semibold mb-2 flex items-center gap-2">
                            <i data-lucide="calendar-check" class="w-4 h-4 text-slate-500"></i>
                            {{ __('pages.contract_end') }}
                        </label>
                        <input type="date" name="valid_until" value="{{ old('valid_until', $contract->valid_until?->format('Y-m-d')) }}"
                               class="w-full px-4 py-2.5 rounded-lg border border-slate-300 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 text-[14px] bg-white shadow-sm transition-all">
                        @error('valid_until')
                            <p class="text-[13px] text-red-600 mt-1.5 flex items-center gap-1">
                                <i data-lucide="alert-circle" class="w-3 h-3"></i>
                                {{ $message }}
                            </p>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="p-6 border-b border-slate-200">
                <h2 class="text-lg font-semibold text-slate-900 mb-4 flex items-center gap-2">
                    <i data-lucide="sliders" class="w-5 h-5 text-primary-600"></i>
                    {{ __('pages.loan_limit_settings') }}
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-slate-700 text-[14px] font-semibold mb-2 flex items-center gap-2">
                            <i data-lucide="user" class="w-4 h-4 text-slate-500"></i>
                            {{ __('pages.per_person_limit') }}
                        </label>
                        <input type="number" name="per_person_loan_limit" value="{{ old('per_person_loan_limit', $contract->per_person_loan_limit) }}" min="1"
                               class="w-full px-4 py-2.5 rounded-lg border border-slate-300 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 text-[14px] bg-white shadow-sm transition-all"
                               placeholder="例: 10">
                        <p class="text-[12px] text-slate-500 mt-1">{{ __('pages.per_person_limit_desc') }}</p>
                        @error('per_person_loan_limit')
                            <p class="text-[13px] text-red-600 mt-1.5 flex items-center gap-1">
                                <i data-lucide="alert-circle" class="w-3 h-3"></i>
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-slate-700 text-[14px] font-semibold mb-2 flex items-center gap-2">
                            <i data-lucide="package" class="w-4 h-4 text-slate-500"></i>
                            {{ __('pages.per_item_limit') }}
                        </label>
                        <input type="number" name="loan_limit_per_item" value="{{ old('loan_limit_per_item', $contract->loan_limit_per_item) }}" min="1"
                               class="w-full px-4 py-2.5 rounded-lg border border-slate-300 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 text-[14px] bg-white shadow-sm transition-all"
                               placeholder="例: 5">
                        <p class="text-[12px] text-slate-500 mt-1">{{ __('pages.per_item_limit_desc') }}</p>
                        @error('loan_limit_per_item')
                            <p class="text-[13px] text-red-600 mt-1.5 flex items-center gap-1">
                                <i data-lucide="alert-circle" class="w-3 h-3"></i>
                                {{ $message }}
                            </p>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="p-6 border-b border-slate-200">
                <h2 class="text-lg font-semibold text-slate-900 mb-4 flex items-center gap-2">
                    <i data-lucide="dollar-sign" class="w-5 h-5 text-primary-600"></i>
                    {{ __('pages.pricing_settings') }}
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div id="fixed_price_field">
                        <label class="block text-slate-700 text-[14px] font-semibold mb-2 flex items-center gap-2">
                            <i data-lucide="yen" class="w-4 h-4 text-slate-500"></i>
                            {{ __('pages.fixed_price') }}
                        </label>
                        <input type="number" name="fixed_unit_price" value="{{ old('fixed_unit_price', $contract->fixed_unit_price) }}" min="0" step="0.01"
                               class="w-full px-4 py-2.5 rounded-lg border border-slate-300 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 text-[14px] bg-white shadow-sm transition-all"
                               placeholder="例: 5000">
                        @error('fixed_unit_price')
                            <p class="text-[13px] text-red-600 mt-1.5 flex items-center gap-1">
                                <i data-lucide="alert-circle" class="w-3 h-3"></i>
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    <div id="point_price_field">
                        <label class="block text-slate-700 text-[14px] font-semibold mb-2 flex items-center gap-2">
                            <i data-lucide="dollar-sign" class="w-4 h-4 text-slate-500"></i>
                            {{ __('pages.point_price') }}
                        </label>
                        <input type="number" name="point_unit_price" value="{{ old('point_unit_price', $contract->point_unit_price) }}" min="0" step="0.01"
                               class="w-full px-4 py-2.5 rounded-lg border border-slate-300 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 text-[14px] bg-white shadow-sm transition-all"
                               placeholder="例: 100">
                        @error('point_unit_price')
                            <p class="text-[13px] text-red-600 mt-1.5 flex items-center gap-1">
                                <i data-lucide="alert-circle" class="w-3 h-3"></i>
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-slate-700 text-[14px] font-semibold mb-2 flex items-center gap-2">
                            <i data-lucide="refresh-cw" class="w-4 h-4 text-slate-500"></i>
                            {{ __('pages.cleaning_multiplier') }}
                        </label>
                        <input type="number" name="cleaning_multiplier" value="{{ old('cleaning_multiplier', $contract->cleaning_multiplier ?? 1.67) }}" min="0.1" max="10" step="0.01"
                               class="w-full px-4 py-2.5 rounded-lg border border-slate-300 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 text-[14px] bg-white shadow-sm transition-all"
                               placeholder="1.67">
                        <p class="text-[12px] text-slate-500 mt-1">{{ __('pages.cleaning_multiplier_desc') }}</p>
                        @error('cleaning_multiplier')
                            <p class="text-[13px] text-red-600 mt-1.5 flex items-center gap-1">
                                <i data-lucide="alert-circle" class="w-3 h-3"></i>
                                {{ $message }}
                            </p>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="p-6 bg-gradient-to-r from-slate-50 to-slate-100 flex items-center justify-end gap-3">
                <a href="{{ route('admin.contracts.index') }}" class="px-5 py-2.5 rounded-lg border border-slate-300 text-slate-700 hover:bg-white text-[14px] font-medium transition-all">
                    {{ __('pages.cancel') }}
                </a>
                <button type="submit" class="px-6 py-2.5 rounded-lg bg-gradient-to-r from-primary-600 via-primary-700 to-blue-600 hover:from-primary-700 hover:via-primary-800 hover:to-blue-700 text-white text-[14px] font-semibold shadow-lg transition-all flex items-center gap-2">
                    <i data-lucide="save" class="w-4 h-4"></i>
                    {{ __('pages.update_contract') }}
                </button>
            </div>
        </div>
    </form>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }
            
            document.getElementById('contract_type')?.addEventListener('change', function() {
                const type = this.value;
                const fixedField = document.getElementById('fixed_price_field');
                const pointField = document.getElementById('point_price_field');
                
                if (type === 'FIXED') {
                    fixedField.style.display = 'block';
                    pointField.style.display = 'none';
                } else {
                    fixedField.style.display = 'none';
                    pointField.style.display = 'block';
                }
            });
            
            // Trigger on load
            document.getElementById('contract_type')?.dispatchEvent(new Event('change'));
        });
    </script>
    @endpush
@endsection

