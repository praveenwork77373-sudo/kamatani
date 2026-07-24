@extends('layouts.app')

@section('title', __('pages.medical_institutions_management') . ' - ' . __('pages.new_registration') . ' | ' . config('app.name'))
@section('page_title', __('pages.medical_institutions_management'))

@section('content')
    <div class="mb-8">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h1 class="text-2xl font-bold text-slate-900 tracking-tight">{{ __('pages.new_registration') }}</h1>
                <p class="text-[14px] text-slate-600 mt-1.5">{{ __('pages.medical_institutions_management_description') }}</p>
            </div>
            <a href="{{ route('admin.masters.institutions') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-50 text-[14px] font-medium transition-all">
                <i data-lucide="arrow-left" class="w-4 h-4"></i>
                {{ __('common.back_to_list') }}
            </a>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.masters.institutions.store') }}" class="max-w-4xl mx-auto">
        @csrf

        <div class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden">
            {{-- Form Header --}}
            <div class="px-6 py-4 border-b border-slate-200 bg-gradient-to-r from-slate-50 to-white">
                <div class="flex items-center gap-3">
                    <div class="h-10 w-10 rounded-lg bg-primary-100 flex items-center justify-center">
                        <i data-lucide="building-2" class="w-5 h-5 text-primary-600"></i>
                    </div>
                    <div>
                        <h2 class="text-lg font-semibold text-slate-900">{{ __('pages.basic_information') }}</h2>
                        <p class="text-[13px] text-slate-500">{{ __('pages.fill_in_basic_information') }}</p>
                    </div>
                </div>
            </div>

            {{-- Form Body --}}
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    {{-- Institution Code --}}
                    <div>
                        <label class="block text-slate-700 text-[14px] font-semibold mb-2">
                            {{ __('pages.institution_code') }} <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <i data-lucide="hash" class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 pointer-events-none"></i>
                            <input type="text" name="code" value="{{ old('code') }}" required
                                   class="w-full pl-10 pr-4 py-2.5 rounded-lg border border-slate-300 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 text-[14px] bg-white shadow-sm transition-all placeholder:text-slate-400"
                                   placeholder="{{ __('pages.institution_code_placeholder') }}">
                        </div>
                        @error('code')
                            <p class="text-[13px] text-red-600 mt-2 flex items-center gap-1.5">
                                <i data-lucide="alert-circle" class="w-3.5 h-3.5"></i>
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    {{-- Institution Name --}}
                    <div class="md:col-span-2">
                        <label class="block text-slate-700 text-[14px] font-semibold mb-2">
                            {{ __('pages.institution_name') }} <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <i data-lucide="building-2" class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 pointer-events-none"></i>
                            <input type="text" name="name" value="{{ old('name') }}" required
                                   class="w-full pl-10 pr-4 py-2.5 rounded-lg border border-slate-300 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 text-[14px] bg-white shadow-sm transition-all placeholder:text-slate-400"
                                   placeholder="{{ __('pages.institution_name_placeholder') }}">
                        </div>
                        @error('name')
                            <p class="text-[13px] text-red-600 mt-2 flex items-center gap-1.5">
                                <i data-lucide="alert-circle" class="w-3.5 h-3.5"></i>
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    {{-- Billing Model --}}
                    <div>
                        <label class="block text-slate-700 text-[14px] font-semibold mb-2">
                            {{ __('pages.billing_model') }} <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <i data-lucide="credit-card" class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 pointer-events-none z-10"></i>
                            <select name="billing_model" required
                                    class="w-full pl-10 pr-10 py-2.5 rounded-lg border border-slate-300 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 text-[14px] bg-white shadow-sm transition-all appearance-none cursor-pointer">
                                <option value="">{{ __('pages.select_please') }}</option>
                                <option value="fixed_per_person" @selected(old('billing_model')==='fixed_per_person')>{{ __('pages.fixed_per_person_billing') }}</option>
                                <option value="point_based" @selected(old('billing_model')==='point_based')>{{ __('pages.point_usage_billing') }}</option>
                            </select>
                            <i data-lucide="chevron-down" class="absolute right-4 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 pointer-events-none"></i>
                        </div>
                        @error('billing_model')
                            <p class="text-[13px] text-red-600 mt-2 flex items-center gap-1.5">
                                <i data-lucide="alert-circle" class="w-3.5 h-3.5"></i>
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    {{-- Notification Email --}}
                    <div>
                        <label class="block text-slate-700 text-[14px] font-semibold mb-2">
                            {{ __('pages.notification_email') }} <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <i data-lucide="mail" class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 pointer-events-none"></i>
                            <input type="email" name="notification_email" value="{{ old('notification_email') }}" required
                                   class="w-full pl-10 pr-4 py-2.5 rounded-lg border border-slate-300 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 text-[14px] bg-white shadow-sm transition-all placeholder:text-slate-400"
                                   placeholder="example@hospital.com">
                        </div>
                        @error('notification_email')
                            <p class="text-[13px] text-red-600 mt-2 flex items-center gap-1.5">
                                <i data-lucide="alert-circle" class="w-3.5 h-3.5"></i>
                                {{ $message }}
                            </p>
                        @enderror
                    </div>
                </div>

                {{-- Divider --}}
                <div class="my-6 border-t border-slate-200"></div>

                {{-- Overall Manager Account Section --}}
                <div class="mb-4">
                    <div class="flex items-center gap-2 mb-4">
                        <i data-lucide="user-circle" class="w-4 h-4 text-slate-500"></i>
                        <h3 class="text-[15px] font-semibold text-slate-700">{{ __('pages.overall_manager_account') }}</h3>
                    </div>
                    <p class="text-[13px] text-slate-500 mb-4">{{ __('pages.overall_manager_account_description') }}</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    {{-- Manager Name --}}
                    <div>
                        <label class="block text-slate-700 text-[14px] font-semibold mb-2">
                            {{ __('pages.manager_name') }} <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <i data-lucide="user" class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 pointer-events-none"></i>
                            <input type="text" name="manager_name" value="{{ old('manager_name') }}" required
                                   class="w-full pl-10 pr-4 py-2.5 rounded-lg border border-slate-300 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 text-[14px] bg-white shadow-sm transition-all placeholder:text-slate-400"
                                   placeholder="{{ __('pages.manager_name_placeholder') }}">
                        </div>
                        @error('manager_name')
                            <p class="text-[13px] text-red-600 mt-2 flex items-center gap-1.5">
                                <i data-lucide="alert-circle" class="w-3.5 h-3.5"></i>
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    {{-- Manager Email (Login Email) --}}
                    <div>
                        <label class="block text-slate-700 text-[14px] font-semibold mb-2">
                            {{ __('pages.manager_email') }} <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <i data-lucide="mail" class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 pointer-events-none"></i>
                            <input type="email" name="manager_email" value="{{ old('manager_email') }}" required
                                   class="w-full pl-10 pr-4 py-2.5 rounded-lg border border-slate-300 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 text-[14px] bg-white shadow-sm transition-all placeholder:text-slate-400"
                                   placeholder="manager@hospital.com">
                        </div>
                        @error('manager_email')
                            <p class="text-[13px] text-red-600 mt-2 flex items-center gap-1.5">
                                <i data-lucide="alert-circle" class="w-3.5 h-3.5"></i>
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    {{-- Manager Password --}}
                    <div>
                        <label class="block text-slate-700 text-[14px] font-semibold mb-2">
                            {{ __('pages.manager_password') }} <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <i data-lucide="lock" class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 pointer-events-none"></i>
                            <input type="password" name="manager_password" required
                                   class="w-full pl-10 pr-4 py-2.5 rounded-lg border border-slate-300 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 text-[14px] bg-white shadow-sm transition-all placeholder:text-slate-400"
                                   placeholder="{{ __('pages.password_placeholder') }}">
                        </div>
                        @error('manager_password')
                            <p class="text-[13px] text-red-600 mt-2 flex items-center gap-1.5">
                                <i data-lucide="alert-circle" class="w-3.5 h-3.5"></i>
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    {{-- Manager Password Confirmation --}}
                    <div>
                        <label class="block text-slate-700 text-[14px] font-semibold mb-2">
                            {{ __('pages.manager_password_confirmation') }} <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <i data-lucide="lock" class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 pointer-events-none"></i>
                            <input type="password" name="manager_password_confirmation" required
                                   class="w-full pl-10 pr-4 py-2.5 rounded-lg border border-slate-300 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 text-[14px] bg-white shadow-sm transition-all placeholder:text-slate-400"
                                   placeholder="{{ __('pages.password_placeholder') }}">
                        </div>
                        @error('manager_password_confirmation')
                            <p class="text-[13px] text-red-600 mt-2 flex items-center gap-1.5">
                                <i data-lucide="alert-circle" class="w-3.5 h-3.5"></i>
                                {{ $message }}
                            </p>
                        @enderror
                    </div>
                </div>

                {{-- Divider --}}
                <div class="my-6 border-t border-slate-200"></div>

                {{-- Additional Settings Section --}}
                <div class="mb-4">
                    <div class="flex items-center gap-2 mb-4">
                        <i data-lucide="settings" class="w-4 h-4 text-slate-500"></i>
                        <h3 class="text-[15px] font-semibold text-slate-700">{{ __('pages.additional_settings') }}</h3>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    {{-- Per Person Loan Limit --}}
                    <div>
                        <label class="block text-slate-700 text-[14px] font-semibold mb-2">
                            {{ __('pages.per_person_limit') }}
                            <span class="text-[12px] font-normal text-slate-500 ml-1">({{ __('pages.optional') }})</span>
                        </label>
                        <div class="relative">
                            <i data-lucide="users" class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 pointer-events-none"></i>
                            <input type="number" name="per_person_loan_limit" value="{{ old('per_person_loan_limit') }}" min="0"
                                   class="w-full pl-10 pr-4 py-2.5 rounded-lg border border-slate-300 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 text-[14px] bg-white shadow-sm transition-all placeholder:text-slate-400"
                                   placeholder="0">
                        </div>
                        @error('per_person_loan_limit')
                            <p class="text-[13px] text-red-600 mt-2 flex items-center gap-1.5">
                                <i data-lucide="alert-circle" class="w-3.5 h-3.5"></i>
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    {{-- Point Unit Price --}}
                    <div>
                        <label class="block text-slate-700 text-[14px] font-semibold mb-2">
                            {{ __('pages.point_unit_price_label') }}
                            <span class="text-[12px] font-normal text-slate-500 ml-1">({{ __('pages.optional') }})</span>
                        </label>
                        <div class="relative">
                            <i data-lucide="coins" class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 pointer-events-none"></i>
                            <input type="number" name="point_unit_price" value="{{ old('point_unit_price') }}" min="0" step="0.01"
                                   class="w-full pl-10 pr-4 py-2.5 rounded-lg border border-slate-300 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 text-[14px] bg-white shadow-sm transition-all placeholder:text-slate-400"
                                   placeholder="0.00">
                        </div>
                        @error('point_unit_price')
                            <p class="text-[13px] text-red-600 mt-2 flex items-center gap-1.5">
                                <i data-lucide="alert-circle" class="w-3.5 h-3.5"></i>
                                {{ $message }}
                            </p>
                        @enderror
                    </div>
                </div>
            </div>

            {{-- Form Footer --}}
            <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex items-center justify-between">
                <p class="text-[12px] text-slate-500 flex items-center gap-1.5">
                    <i data-lucide="info" class="w-3.5 h-3.5"></i>
                    {{ __('pages.required_fields_note') }}
                </p>
                <div class="flex items-center gap-3">
                    <a href="{{ route('admin.masters.institutions') }}" class="px-5 py-2.5 rounded-lg border border-slate-300 text-slate-700 hover:bg-white text-[14px] font-medium transition-all flex items-center gap-2">
                        <i data-lucide="x" class="w-4 h-4"></i>
                        {{ __('common.cancel') }}
                    </a>
                    <button type="submit" class="px-6 py-2.5 rounded-lg bg-primary-600 hover:bg-primary-700 text-white text-[14px] font-semibold shadow-md transition-all flex items-center gap-2">
                        <i data-lucide="check" class="w-4 h-4"></i>
                        {{ __('pages.register') }}
                    </button>
                </div>
            </div>
        </div>
    </form>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
        setTimeout(() => { if (typeof lucide !== 'undefined') lucide.createIcons(); }, 100);
        setTimeout(() => { if (typeof lucide !== 'undefined') lucide.createIcons(); }, 500);
    }
});
</script>
@endpush
@endsection
